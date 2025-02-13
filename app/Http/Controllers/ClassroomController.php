<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    // Apply authentication middleware
    public function __construct()
{
    $this->middleware('auth')->only('index', 'show'); // Ensure user is authenticated for general viewing
    $this->middleware('role.permission:,create classroom')->only('store'); // Only users with permission to create classrooms
    $this->middleware('role.permission:,update classroom')->only('update'); // Only users with permission to update classrooms
    $this->middleware('role.permission:,delete classroom')->only('destroy'); // Only users with permission to delete classrooms
}

    // List all classrooms
    public function index()
    {
        $classrooms = Classroom::all();

        return response()->json($classrooms);
    }

    // Superadmin creates a classroom
    public function store(Request $request)
    {
        // Only superadmin can create classrooms
        /**
         * @var \App\Models\User
         */
        if (!Auth::user() || !Auth::user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Access denied. Only superadmins can create classrooms.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
            'capacity' => 'required|integer',
        ]);

        $classroom = Classroom::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Classroom created successfully',
            'classroom' => $classroom
        ], 201);
    }

    // Show classroom details
    public function show($classroomId)
    {
        $classroom = Classroom::find($classroomId);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        return response()->json($classroom);
    }

    // Update classroom details by superadmin
    public function update(Request $request, $classroomId)
    {
        // Only superadmin can update classrooms
        /**
         * @var \App\Models\User
         */
        if (!Auth::user() || !Auth::user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Access denied. Only superadmins can update classrooms.'], 403);
        }

        $classroom = Classroom::find($classroomId);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255|unique:classrooms,name,' . $classroomId,
            'capacity' => 'integer',
        ]);

        $classroom->update($validated);

        return response()->json(['message' => 'Classroom updated successfully', 'classroom' => $classroom]);
    }

    // Delete a classroom by superadmin
    public function destroy($classroomId)
    {
        // Only superadmin can delete classrooms
        if (!Auth::user() || !Auth::user()->hasRole('superadmin')) {
            return response()->json(['message' => 'Access denied. Only superadmins can delete classrooms.'], 403);
        }

        $classroom = Classroom::find($classroomId);

        if (!$classroom) {
            return response()->json(['message' => 'Classroom not found'], 404);
        }

        // Check if the classroom has active reservations
        if ($classroom->reservations()->exists()) {
            return response()->json(['message' => 'Classroom has active reservations and cannot be deleted.'], 400);
        }

        $classroom->delete();

        return response()->json(['message' => 'Classroom deleted successfully']);
    }
}
