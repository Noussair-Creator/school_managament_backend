<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Material;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::query();

        // Lab Manager sees all reservations with materials
        // if (Auth::user()->hasRole('responsablelabo')) {
        //     $query->with('materials');
        // }

        // Filters
        $query->when($request->has('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        })
            ->when($request->has('location_id'), function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            });

        // Paginate results
        return response()->json($query->paginate(15));
    }

    public function show(Reservation $reservation)
    {
        // Ensure the user is authorized
        if (!$this->isAuthorized($reservation)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'reservation' => $reservation->load('materials', 'user', 'location', 'approvedBy')
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (!$this->isAuthorized($reservation)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'start_time' => 'nullable|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'purpose' => 'nullable|string|max:1000',
            'location_id' => 'nullable|exists:locations,id',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required_with:materials|exists:materials,id',
            'materials.*.quantity' => 'required_with:materials|integer|min:1',
        ]);

        DB::beginTransaction();

        $originalMaterials = $reservation->materials()->get();
        $wasApproved = $reservation->status === Reservation::STATUS_APPROVED;

        if ($wasApproved && isset($validated['materials'])) {
            // Restore original materials' stock
            foreach ($originalMaterials as $material) {
                $material->increment('quantity_available', $material->pivot->quantity_requested);
            }

            // Check new materials' availability
            foreach ($validated['materials'] as $materialData) {
                $material = Material::find($materialData['id']);
                if ($material->quantity_available < $materialData['quantity']) {
                    // Restore original stock on failure
                    foreach ($originalMaterials as $originalMaterial) {
                        $originalMaterial->decrement('quantity_available', $originalMaterial->pivot->quantity_requested);
                    }
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient stock for ' . $material->name], 400);
                }
            }

            // Decrement new materials' stock
            foreach ($validated['materials'] as $materialData) {
                $material = Material::find($materialData['id']);
                $material->decrement('quantity_available', $materialData['quantity']);
            }
        }

        // Update reservation
        $reservation->update($validated);

        if (!empty($validated['materials'])) {
            $materialsToAttach = $this->getMaterialsToAttach($validated['materials']);
            $reservation->materials()->sync($materialsToAttach);
        }

        DB::commit();

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'reservation' => $reservation->load('materials', 'user', 'location', 'approvedBy'),
        ]);
    }

    public function destroy(Reservation $reservation)
    {
        if (!$this->isAuthorized($reservation)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        if ($reservation->status === Reservation::STATUS_APPROVED) {
            foreach ($reservation->materials as $material) {
                $material->increment('quantity_available', $material->pivot->quantity_requested);
            }
        }

        $reservation->delete();

        DB::commit();

        return response()->json(['message' => 'Reservation deleted successfully.']);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReservation($request);

        // Check location type
        $location = Location::find($validated['location_id']);
        if (!in_array($location->type, ['laboratory', 'amphitheater'])) {
            return response()->json(['message' => 'Only laboratories and amphitheaters can be booked.'], 400);
        }

        // Check materials stock
        if (!$this->checkMaterialsStock($validated['materials'])) {
            return response()->json(['message' => 'Not enough stock for materials.'], 400);
        }

        // Create reservation
        $reservation = Reservation::create($this->prepareReservationData($validated));

        // Attach materials and update stock
        if (!empty($validated['materials'])) {
            $this->handleMaterials($validated['materials'], $reservation);
        }

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservation' => $reservation->load('materials', 'user', 'location'),
        ], 201);
    }

    public function approveOrReject(Request $request, Reservation $reservation)
    {
        // if (!Auth::user()->hasRole('responsablelabo')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'status' => ['required', 'in:' . Reservation::STATUS_APPROVED . ',' . Reservation::STATUS_REJECTED],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();

        $originalStatus = $reservation->status;

        if ($validated['status'] === Reservation::STATUS_APPROVED) {
            // Check material availability with lock
            $reservation->load(['materials' => function ($query) {
                $query->lockForUpdate();
            }]);

            foreach ($reservation->materials as $material) {
                if ($material->quantity_available < $material->pivot->quantity_requested) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Insufficient stock for ' . $material->name,
                    ], 400);
                }
            }

            // Decrement stock
            foreach ($reservation->materials as $material) {
                $material->decrement('quantity_available', $material->pivot->quantity_requested);
            }
        }

        $reservation->status = $validated['status'];
        if ($reservation->status === Reservation::STATUS_REJECTED) {
            $reservation->rejection_reason = $validated['rejection_reason'];
            // Restore stock if previously approved
            if ($originalStatus === Reservation::STATUS_APPROVED) {
                foreach ($reservation->materials as $material) {
                    $material->increment('quantity_available', $material->pivot->quantity_requested);
                }
            }
        }

        $reservation->approved_by = Auth::id();
        $reservation->approved_at = now();
        $reservation->save();

        DB::commit();

        return response()->json([
            'message' => 'Reservation ' . $reservation->status . ' successfully.',
            'reservation' => $reservation->load('materials', 'user', 'location', 'approvedBy'),
        ]);
    }

    public function search(Request $request)
    {
        $query = Reservation::query();

        $query->when($request->has('user_id'), function ($query) use ($request) {
            $query->where('user_id', $request->user_id);
        })
            ->when($request->has('location_id'), function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->when($request->has('start_date'), function ($query) use ($request) {
                $query->whereDate('start_time', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                $query->whereDate('end_time', '<=', $request->end_date);
            });

        return response()->json($query->get());
    }

    public function getMaterialsForReservation(Reservation $reservation)
    {
        return response()->json(['materials' => $reservation->materials]);
    }

    // Helper Methods

    private function isAuthorized(Reservation $reservation)
    {
        return Auth::user()->id === $reservation->user_id || Auth::user()->hasRole('superadmin');
    }

    private function validateReservation(Request $request)
    {
        return $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'nullable|string|max:1000',
            'location_id' => 'required|exists:locations,id',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required_with:materials|exists:materials,id',
            'materials.*.quantity' => 'required_with:materials|integer|min:1',
        ]);
    }

    private function checkMaterialsStock($materials)
    {
        foreach ($materials as $materialData) {
            $material = Material::find($materialData['id']);
            if ($material->quantity_available < $materialData['quantity']) {
                return false;
            }
        }
        return true;
    }

    private function prepareReservationData($validated)
    {
        return [
            'user_id' => Auth::id(),
            'location_id' => $validated['location_id'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'purpose' => $validated['purpose'],
            'status' => Reservation::STATUS_PENDING,
        ];
    }

    private function handleMaterials($materials, $reservation)
    {
        $materialsToAttach = $this->getMaterialsToAttach($materials);
        $reservation->materials()->sync($materialsToAttach);
    }

    private function getMaterialsToAttach($materials)
    {
        return collect($materials)->mapWithKeys(function ($materialData) {
            return [$materialData['id'] => ['quantity_requested' => $materialData['quantity']]];
        })->toArray();
    }
}
