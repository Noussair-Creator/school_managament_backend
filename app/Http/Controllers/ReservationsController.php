<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
// use App\Models\Reservation;
use App\Models\Reservations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationsController extends Controller
{
    public function __construct()
    {
        // Apply authentication middleware
        $this->middleware('auth'); // Ensure user is authenticated

        // Add role-based permission middleware
        $this->middleware('role.permission:,create reservation')->only('makeReservation');
        $this->middleware('role.permission:,update reservation')->only('updateReservation');
        $this->middleware('role.permission:,delete reservation')->only('cancelReservation');
        $this->middleware('role.permission:,show reservation')->only('listReservations', 'show');
    }

    // Teacher reserves a classroom
    public function makeReservation(Request $request, $classroomId)
    {
        // Validate reservation time
        $validated = $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        $classroom = Classroom::find($classroomId);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Check if the classroom is already reserved during the requested time
        $existingReservation = Reservations::where('classroom_id', $classroom->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($existingReservation) {
            return response()->json(['message' => 'Classroom is already reserved during this time.'], 400);
        }

        // Create a new reservation
        $reservation = Reservations::create([
            'classroom_id' => $classroom->id,
            'user_id' => Auth::id(),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return response()->json([
            'message' => 'Classroom reserved successfully',
            'reservation' => $reservation
        ]);
    }

    // List all reservations for a teacher
    public function listReservations()
    {
        $reservations = Reservations::with('classroom', 'user')->where('user_id', Auth::id())->get();
        return response()->json($reservations);
    }

    // Show reservation details
    public function show($reservationId)
    {
        $reservation = Reservations::with('classroom', 'user')->find($reservationId);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        return response()->json($reservation);
    }

    // Update a reservation (only the owner can update)
    public function updateReservation(Request $request, $reservationId)
    {
        $reservation = Reservations::find($reservationId);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Ensure the authenticated user is the owner of the reservation
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only update your own reservations.'], 403);
        }

        $validated = $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        // Check if the new time slot is available
        $existingReservation = Reservations::where('classroom_id', $reservation->classroom_id)
            ->where('id', '!=', $reservationId)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($existingReservation) {
            return response()->json(['message' => 'Classroom is already reserved during this time.'], 400);
        }

        $reservation->start_time = $validated['start_time'];
        $reservation->end_time = $validated['end_time'];
        $reservation->save();

        return response()->json([
            'message' => 'Reservation updated successfully',
            'reservation' => $reservation
        ]);
    }

    // Cancel a reservation (only the owner can cancel)
    public function cancelReservation($reservationId)
    {
        $reservation = Reservations::find($reservationId);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Ensure the authenticated user is the owner of the reservation
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only cancel your own reservations.'], 403);
        }

        // Delete the reservation
        $reservation->delete();

        return response()->json(['message' => 'Reservation canceled successfully']);
    }
}
