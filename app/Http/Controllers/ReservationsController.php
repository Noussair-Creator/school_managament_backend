<?php

namespace App\Http\Controllers;

// Updated Model Imports
use App\Models\Location;
use App\Models\Reservations;
use App\Models\User;
use App\Models\Material; // <-- Import Material model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // <-- Import Log facade
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder; // <-- Import Builder for scopes if needed

class ReservationsController extends Controller
{
    public function __construct()
    {
        // Apply auth middleware globally first
        $this->middleware('auth:sanctum');

        // Apply specific permission middleware
        $this->middleware('role.permission:list reservations')->only('listReservations');
        $this->middleware('role.permission:make reservation')->only('makeReservation');
        // Update/Cancel might depend on status, check logic inside methods too
        $this->middleware('role.permission:update reservation')->only('updateReservation');
        $this->middleware('role.permission:cancel reservation')->only('cancelReservation');
        // Add middleware for approval actions (assuming one permission covers both)
        $this->middleware('role.permission:approve reservation')->only(['approve', 'reject']);
    }

    /**
     * Make a reservation request for a specific Location.
     * Reservation starts in 'pending' status.
     */
    public function makeReservation(Request $request, Location $location)
    {
        // Define $reservation variable outside try block to access in catch if needed
        $reservation = null;

        try {
            // --- Validation ---
            $validated = $request->validate([
                'start_time' => 'required|date|after_or_equal:now',
                'end_time'   => 'required|date|after:start_time',
                'teacher_id' => 'required|integer|exists:users,id',
                'materials'               => 'sometimes|array',
                'materials.*.material_id' => 'required_with:materials|integer|exists:materials,id',
                'materials.*.quantity'    => 'required_with:materials|integer|min:1',
            ]);

            // Verify Teacher Role
            $teacher = User::find($validated['teacher_id']);
            if (!$teacher || !$teacher->hasRole('teacher')) {
                throw ValidationException::withMessages(['teacher_id' => 'The selected user is not a valid teacher.']);
            }

            // --- Check for Overlapping APPROVED Reservations ---
            // (Conflict check logic remains the same)
            $existingApprovedReservation = Reservations::where('reservable_id', $location->id)
                ->where('reservable_type', Location::class)
                ->where('status', Reservations::STATUS_APPROVED)
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                })
                ->exists();

            if ($existingApprovedReservation) {
                return response()->json(['message' => "{$location->name} ({$location->type}) has an approved reservation during this time slot."], 409);
            }

            // --- Create the Reservation ---
            $reservation = Reservations::create([
                'reservable_id'   => $location->id,
                'reservable_type' => Location::class,
                'user_id'         => Auth::id(),
                'teacher_id'      => $validated['teacher_id'],
                'start_time'      => $validated['start_time'],
                'end_time'        => $validated['end_time'],
            ]);

            // --- Attach Materials (with added safety checks and logging) ---
            // Check if 'materials' key exists in the validated data specifically
            if (isset($validated['materials']) && is_array($validated['materials'])) {
                $materialsToAttach = [];
                foreach ($validated['materials'] as $materialRequest) {
                    // Double-check required keys exist before accessing
                    if (isset($materialRequest['material_id']) && isset($materialRequest['quantity'])) {
                        $materialsToAttach[$materialRequest['material_id']] = [
                            'quantity_requested' => $materialRequest['quantity']
                        ];
                    } else {
                        Log::warning("Skipping material attachment due to missing keys in request data for Reservation ID: " . $reservation->id, ['request_item' => $materialRequest]);
                    }
                }

                if (!empty($materialsToAttach)) {
                    Log::info("Attempting to attach materials to Reservation ID: {$reservation->id}", ['materials' => $materialsToAttach]);
                    try {
                        // The actual attach operation
                        $reservation->requestedMaterials()->attach($materialsToAttach);
                        Log::info("Successfully attached materials to Reservation ID: {$reservation->id}");
                    } catch (\Exception $e) {
                        // Log the specific error during attach
                        Log::error("Failed to attach materials for Reservation ID {$reservation->id}: " . $e->getMessage(), [
                            'exception' => $e,
                            'attach_data' => $materialsToAttach
                        ]);

                        // Optional: Clean up the created reservation since attach failed
                        // $reservation->delete();
                        // Log::info("Deleted Reservation ID {$reservation->id} due to material attach failure.");

                        // Throw a new exception to be caught by the outer catch block
                        throw new \Exception("Failed to process materials for the reservation.");
                    }
                }
            } else if ($request->has('materials')) {
                // Log if materials were in request but not in validated (likely validation failure within the array)
                Log::warning("Materials key present in request but not in validated data for Reservation ID: {$reservation->id}. Check validation rules/input.", ['request_materials' => $request->input('materials')]);
            }


            // Reload relationships for the response
            // Use fresh() if you modified the reservation instance (like deleting it in catch)
            // Use load() if the instance is still valid
            $reservation->load(['reservable:id,name,type', 'user:id,name', 'teacher:id,name', 'requestedMaterials']);

            return response()->json([
                'message' => "Location '{$location->name}' reservation requested successfully. Awaiting approval.",
                'reservation' => $reservation
            ], 201);
        } catch (ValidationException $e) {
            // Log validation errors if helpful
            Log::warning("Reservation validation failed.", ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log the generic error, include reservation ID if available
            $reservationId = $reservation ? $reservation->id : 'N/A';
            Log::error("Reservation creation/processing failed (Reservation ID: {$reservationId}): " . $e->getMessage(), [
                'exception' => $e, // Include full exception for stack trace in logs
                'request' => $request->except(['password']) // Log request data (excluding sensitive fields)
            ]);
            // Return generic error message to the user
            return response()->json(['message' => 'An unexpected error occurred while making the reservation.'], 500);
        }
    }

    /**
     * List reservations based on user role and optional status filter for admin.
     */
    public function listReservations(Request $request) // <-- Added Request
    {
        $user = Auth::user();
        $query = Reservations::with([
            'reservable:id,name,type', // Select columns for efficiency
            'user:id,first_name',
            'teacher:id,first_name',
            'requestedMaterials:id,name', // Select columns for materials
            'approver:id,first_name' // Load approver info
        ])->latest();

        // Admin/Approver Role Filtering
        if ($user->hasRole('superadmin') || $user->can('approve reservation')) { // Check capability too if needed
            if ($request->has('status')) {
                $status = $request->query('status');
                // Validate status input
                if (in_array($status, [Reservations::STATUS_PENDING, Reservations::STATUS_APPROVED, Reservations::STATUS_REJECTED, Reservations::STATUS_CANCELLED])) {
                    $query->where('status', $status);
                }
            }
            // Admins see all by default unless filtered
        }
        // Lab Manager sees reservations they created
        elseif ($user->hasRole('responsable_labo')) {
            $query->where('user_id', $user->id);
        }
        // Teacher sees reservations they are assigned to
        elseif ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
            // Teachers typically only see APPROVED or maybe PENDING ones assigned to them?
            $query->whereIn('status', [Reservations::STATUS_APPROVED, Reservations::STATUS_PENDING]);
        } else {
            // If user has 'list reservations' but none of the above roles, what should they see?
            // Maybe return empty or a more specific unauthorized message.
            Log::warning("User ID {$user->id} has 'list reservations' permission but no specific role view logic.");
            return response()->json(['message' => 'No reservations accessible for your role.'], 403);
            // OR return response()->json([]);
        }

        // Add pagination
        $perPage = $request->query('per_page', 15);
        $reservations = $query->paginate((int)$perPage);

        return response()->json($reservations);
    }

    /**
     * Update a PENDING reservation.
     * Admins might be allowed to update other statuses depending on policy.
     */
    public function updateReservation(Request $request, Reservations $reservation)
    {
        $user = Auth::user();

        // --- Authorization & Status Check ---
        $isCreator = $user->hasRole('responsable_labo') && $reservation->user_id === $user->id;
        $isAdmin = $user->hasRole('superadmin');

        // Generally, only creators can update PENDING reservations. Admins might override.
        if (!$isAdmin && !($isCreator && $reservation->status === Reservations::STATUS_PENDING)) {
            return response()->json(['message' => 'Unauthorized or reservation cannot be updated in its current status.'], 403);
        }
        // Optional: Explicitly prevent admin from updating already approved/cancelled?
        // if ($isAdmin && !in_array($reservation->status, [Reservations::STATUS_PENDING, Reservations::STATUS_REJECTED])) {
        //     return response()->json(['message' => 'Admins cannot update approved or cancelled reservations.'], 403);
        // }


        try {
            $validated = $request->validate([
                'start_time' => 'sometimes|required|date|after_or_equal:now',
                'end_time'   => ['sometimes', 'required', 'date', function ($attribute, $value, $fail) use ($request, $reservation) {
                    $startTime = $request->input('start_time', $reservation->start_time);
                    if (strtotime($value) <= strtotime($startTime)) {
                        $fail('The end time must be after the start time.');
                    }
                }],
                'teacher_id' => 'sometimes|required|integer|exists:users,id',
                // --- Materials Validation ---
                'materials'               => 'sometimes|array',
                'materials.*.material_id' => 'required_with:materials|integer|exists:materials,id',
                'materials.*.quantity'    => 'required_with:materials|integer|min:1',
            ]);

            // Validate teacher role if teacher_id is being updated
            if ($request->has('teacher_id')) {
                $teacher = User::find($validated['teacher_id']);
                if (!$teacher || !$teacher->hasRole('teacher')) {
                    throw ValidationException::withMessages(['teacher_id' => 'The selected user is not a valid teacher.']);
                }
            }

            // --- Check Time Conflicts vs APPROVED (if time changed) ---
            $checkStartTime = $validated['start_time'] ?? $reservation->start_time;
            $checkEndTime = $validated['end_time'] ?? $reservation->end_time;

            if ($request->has('start_time') || $request->has('end_time')) {
                $existingConflict = Reservations::where('reservable_id', $reservation->reservable_id)
                    ->where('reservable_type', $reservation->reservable->getMorphClass())
                    ->where('id', '!=', $reservation->id)
                    ->where('status', Reservations::STATUS_APPROVED) // Check only against APPROVED
                    ->where(function ($query) use ($checkStartTime, $checkEndTime) {
                        $query->where('start_time', '<', $checkEndTime)
                            ->where('end_time', '>', $checkStartTime);
                    })
                    ->exists();

                if ($existingConflict) {
                    $locationType = $reservation->reservable->type ?? 'Location';
                    return response()->json(['message' => "Time conflict: The {$locationType} has an approved reservation during the updated time slot."], 409);
                }
            }

            // --- Update the Reservation Fields ---
            $reservation->update($validated); // Only updates fillable fields directly

            // --- Sync Materials ---
            if ($request->has('materials')) { // Check if the key exists, even if empty array
                $materialsToSync = [];
                if (is_array($validated['materials'])) { // Ensure it's an array after validation
                    foreach ($validated['materials'] as $materialRequest) {
                        $materialsToSync[$materialRequest['material_id']] = [
                            'quantity_requested' => $materialRequest['quantity']
                        ];
                    }
                }
                // Sync will add new, update existing pivots, and remove missing ones
                $reservation->requestedMaterials()->sync($materialsToSync);
            }


            // Reload data for response
            $reservation->refresh()->load(['reservable', 'user', 'teacher', 'requestedMaterials', 'approver']);

            return response()->json([
                'message' => 'Reservation updated successfully.',
                'reservation' => $reservation
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Reservation update failed for ID {$reservation->id}: " . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while updating the reservation.'], 500);
        }
    }

    /**
     * Cancel a reservation by updating its status.
     * Creator can cancel 'pending'. Admin can cancel ('pending', 'approved'?).
     */
    public function cancelReservation(Reservations $reservation)
    {
        $user = Auth::user();

        // --- Authorization ---
        $isCreator = $user->hasRole('responsable_labo') && $reservation->user_id === $user->id;
        $isAdmin = $user->hasRole('superadmin');

        $canCancel = false;
        if ($isCreator && $reservation->status === Reservations::STATUS_PENDING) {
            $canCancel = true; // Creator can cancel their pending requests
        } elseif ($isAdmin && in_array($reservation->status, [Reservations::STATUS_PENDING, Reservations::STATUS_APPROVED])) {
            // Admin can cancel pending or approved reservations
            // Policy Decision: Can admin cancel rejected/already cancelled? Probably not.
            $canCancel = true;
        }

        if (!$canCancel) {
            return response()->json(['message' => 'Unauthorized or reservation cannot be cancelled in its current status.'], 403);
        }

        try {
            $reservation->status = Reservations::STATUS_CANCELLED;
            // Optional: Track who cancelled it if admin did
            if ($isAdmin && !$isCreator) {
                $reservation->approved_by = $user->id; // Re-use field for canceller ID
                $reservation->approved_at = now(); // Timestamp of cancellation
                $reservation->rejection_reason = 'Cancelled by Admin.'; // Optional note
            }
            $reservation->save();

            Log::info("Reservation ID {$reservation->id} cancelled by User ID {$user->id}.");

            return response()->json([
                'message' => 'Reservation cancelled successfully',
                'reservation' => $reservation->fresh(['reservable', 'user', 'teacher', 'requestedMaterials', 'approver'])
            ]);
        } catch (\Exception $e) {
            Log::error("Reservation cancellation failed for ID {$reservation->id}: " . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while cancelling the reservation.'], 500);
        }
    }

    /**
     * Approve a pending reservation. (Admin/Approver Only)
     */
    public function approve(Reservations $reservation)
    {
        // Permission check done by middleware

        if ($reservation->status !== Reservations::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending reservations can be approved.'], 400);
        }

        // --- CRITICAL: Check for conflicts AGAIN at the moment of approval ---
        $conflict = Reservations::where('reservable_id', $reservation->reservable_id)
            ->where('reservable_type', $reservation->reservable->getMorphClass())
            ->where('id', '!=', $reservation->id)
            ->where('status', Reservations::STATUS_APPROVED) // Check against already approved
            ->where(function ($query) use ($reservation) {
                $query->where('start_time', '<', $reservation->end_time)
                    ->where('end_time', '>', $reservation->start_time);
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Approval failed: Time slot conflict detected with an existing approved reservation.'], 409);
        }

        try {
            $reservation->status = Reservations::STATUS_APPROVED;
            $reservation->approved_by = Auth::id();
            $reservation->approved_at = now();
            $reservation->rejection_reason = null;
            $reservation->save();

            // TODO: Optionally dispatch notification event
            // event(new ReservationApproved($reservation));

            Log::info("Reservation ID {$reservation->id} approved by User ID " . Auth::id());

            return response()->json([
                'message' => 'Reservation approved successfully.',
                'reservation' => $reservation->fresh(['reservable', 'user', 'teacher', 'requestedMaterials', 'approver'])
            ]);
        } catch (\Exception $e) {
            Log::error("Reservation approval failed for ID {$reservation->id}: " . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while approving the reservation.'], 500);
        }
    }

    /**
     * Reject a pending reservation. (Admin/Approver Only)
     */
    public function reject(Request $request, Reservations $reservation)
    {
        // Permission check done by middleware

        if ($reservation->status !== Reservations::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending reservations can be rejected.'], 400);
        }

        try {
            $validated = $request->validate([
                // Making reason required for rejection
                'reason' => 'required|string|max:1000'
            ]);

            $reservation->status = Reservations::STATUS_REJECTED;
            $reservation->approved_by = Auth::id();
            $reservation->approved_at = now();
            $reservation->rejection_reason = $validated['reason'];
            $reservation->save();

            // TODO: Optionally dispatch notification event
            // event(new ReservationRejected($reservation));

            Log::info("Reservation ID {$reservation->id} rejected by User ID " . Auth::id() . " Reason: " . $validated['reason']);

            return response()->json([
                'message' => 'Reservation rejected successfully.',
                'reservation' => $reservation->fresh(['reservable', 'user', 'teacher', 'requestedMaterials', 'approver'])
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Reservation rejection failed for ID {$reservation->id}: " . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while rejecting the reservation.'], 500);
        }
    }
}
