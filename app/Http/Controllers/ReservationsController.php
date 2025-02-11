<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\Reservations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationsController extends Controller
{
    // Teacher reserves a classroom
    public function makeReservation(Request $request, $classroomId)
    {
        // Only teachers can reserve classrooms
        /**
         * @var \App\Models\User
         */
        if (!Auth::user()) {
            return response()->json(['message' => 'Access denied. Only teachers can reserve classrooms.'], 403);
        }

        // Validate reservation time
        $validated = $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time', // end_time must be after start_time
        ]);

        // Find the classroom by ID
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

    // Teacher unreserves a classroom
    // public function unreserve($classroomId)
    // {
    //     // Only teachers can unreserve classrooms
    //     /**
    //      * @var \App\Models\User
    //      */

    //     if (!Auth::user()) {
    //         return response()->json(['message' => 'Access denied. Only teachers can unreserve classrooms.'], 403);
    //     }

    //     // Find the reservation for the classroom
    //     $reservation = Reservations::where('classroom_id', $classroomId)
    //         ->where('user_id', Auth::id()) // Ensure the teacher is the one who reserved it
    //         ->first();

    //     if (!$reservation) {
    //         return response()->json(['message' => 'No reservation found or you cannot unreserve this classroom.'], 404);
    //     }

    //     // Delete the reservation
    //     $reservation->delete();

    //     return response()->json(['message' => 'Classroom unreserved successfully']);
    // }

    // List all reservations for a teacher
    public function listReservations()
    {
        // Only teachers can view their reservations
        /**
         * @var \App\Models\User
         */
        if (!Auth::user()) {
            return response()->json(['message' => 'Access denied. Only teachers can view their reservations.'], 403);
        }

        // Get all reservations for the authenticated user with classroom details and creator
        // $reservations = Reservations::where('user_id', Auth::id())->with('classroom', 'user')->get();
        // Get all reservations for the authenticated user
        $reservations = Reservations::with('classroom', 'user')->get();

        return response()->json($reservations);
    }
    public function updateReservation(Request $request, $reservationId)
    {
        // Ensure the user is authenticated
        if (!Auth::user()) {
            return response()->json(['message' => 'Access denied. Only teachers can update reservations.'], 403);
        }

        // Find the reservation
        $reservation = Reservations::find($reservationId);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Ensure the authenticated user is the owner of the reservation
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only update your own reservations.'], 403);
        }

        // Validate the new reservation times
        $validated = $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        // Check if the new time slot is available
        $existingReservation = Reservations::where('classroom_id', $reservation->classroom_id)
            ->where('id', '!=', $reservationId) // Exclude current reservation
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
            })
            ->exists();

        if ($existingReservation) {
            return response()->json(['message' => 'Classroom is already reserved during this time.'], 400);
        }

        // Update reservation details
        $reservation->start_time = $validated['start_time'];
        $reservation->end_time = $validated['end_time'];
        $reservation->save();

        return response()->json([
            'message' => 'Reservation updated successfully',
            'reservation' => $reservation
        ]);
    }
    public function show($reservationId)
    {
        $reservation = Reservations::with('classroom', 'user')->find($reservationId);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        return response()->json($reservation);
    }
    public function cancelReservation($reservationId)
    {
        // Ensure the user is authenticated
        if (!Auth::user()) {
            return response()->json(['message' => 'Access denied. Only teachers can cancel reservations.'], 403);
        }

        // Find the reservation
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

