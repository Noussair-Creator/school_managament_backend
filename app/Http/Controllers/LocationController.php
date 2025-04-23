<?php

namespace App\Http\Controllers;

use App\Models\Location; // Import the unified Location model
use App\Models\User;     // Needed for potentially loading creator info
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // For validating the 'type' field
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log; // Optional: for logging errors

class LocationController extends Controller
{
    /**
     * Apply middleware to controller methods.
     */
    public function __construct()
    {


        // $this->middleware('auth:sanctum')->except(['index', 'show']);


        // Permission for creating locations
        $this->middleware('role.permission:create location')->only('store');

        // Permission for updating locations
        $this->middleware('role.permission:update location')->only('update');

        // Permission for deleting locations
        $this->middleware('role.permission:delete location')->only('destroy');

        $this->middleware('role.permission:list locations')->only('index'); // Uncomment if index needs permission
        $this->middleware('role.permission:show location')->only('show');   // Uncomment if show needs permission
    }

    /**
     * Display a listing of the locations.
     * Accessible publicly. Handles filtering, sorting, pagination.
     * Assumes route: GET /locations
     */
    public function index(Request $request)
    {
        $query = Location::query()->with('creator:id,first_name'); // Eager load creator info, select only needed columns

        // --- Filtering ---
        if ($request->has('type') && in_array($request->query('type'), [Location::TYPE_CLASSROOM, Location::TYPE_LABORATORY, Location::TYPE_AMPHITHEATER])) {
            $query->where('type', $request->query('type'));
        }
        if ($request->has('min_capacity') && is_numeric($request->query('min_capacity'))) {
            $query->where('capacity', '>=', $request->query('min_capacity'));
        }
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        // --- Sorting ---
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDir = $request->query('sortDir', 'desc');
        if (in_array($sortBy, ['name', 'capacity', 'type', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc'); // Default sort if invalid column provided
        }


        // --- Pagination ---
        $perPage = $request->query('per_page', 15);
        $locations = $query->paginate((int)$perPage); // Cast to int

        return response()->json($locations);
    }

    /**
     * Store a newly created location in storage.
     * Requires 'create location' permission.
     * Assumes route: POST /locations
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:locations,name',
                'capacity' => 'required|integer|min:1',
                'type' => ['required', 'string', Rule::in([Location::TYPE_CLASSROOM, Location::TYPE_LABORATORY, Location::TYPE_AMPHITHEATER])],
            ]);

            $validated['created_by'] = Auth::id(); // Get authenticated user's ID

            $location = Location::create($validated);

            return response()->json([
                'message' => ucfirst($location->type) . ' created successfully.',
                'location' => $location->load('creator:id,first_name') // Load creator with specific columns
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Location creation failed: ' . $e->getMessage()); // Log the error
            return response()->json(['message' => 'An error occurred while creating the location.'], 500);
        }
    }

    /**
     * Display the specified location.
     * Accessible publicly.
     * Assumes route: GET /locations/{location}
     */
    public function show(Location $location)
    {
        $location->load(['creator:id,first_name']); // Load creator with specific columns

        // Optional: Load upcoming reservations efficiently
        // $location->load(['reservations' => function ($query) {
        //     $query->where('end_time', '>', now())
        //           ->orderBy('start_time', 'asc')
        //           ->with('teacher:id,name') // Also load teacher info for reservations
        //           ->limit(10); // Limit the number of reservations loaded
        // }]);

        return response()->json($location);
    }

    /**
     * Update the specified location in storage.
     * Requires 'update location' permission.
     * Assumes route: PUT/PATCH /locations/{location}
     */
    public function update(Request $request, Location $location)
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('locations', 'name')->ignore($location->id)],
                'capacity' => 'sometimes|required|integer|min:1',
                'type' => ['sometimes', 'required', 'string', Rule::in([Location::TYPE_CLASSROOM, Location::TYPE_LABORATORY, Location::TYPE_AMPHITHEATER])],
            ]);

            // Only update if validation passed and there's data to update
            if (!empty($validated)) {
                $location->update($validated);
            }

            return response()->json([
                'message' => ucfirst($location->type) . ' updated successfully.',
                'location' => $location->fresh('creator:id,first_name') // Get fresh data
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Location update failed for ID {$location->id}: " . $e->getMessage()); // Log the error
            return response()->json(['message' => 'An error occurred while updating the location.'], 500);
        }
    }

    /**
     * Remove the specified location from storage.
     * Requires 'delete location' permission.
     * Assumes route: DELETE /locations/{location}
     */
    public function destroy(Location $location)
    {
        try {
            $locationType = $location->type;
            $locationName = $location->name; // Get name for logging/message
            $locationId = $location->id;

            $location->delete();

            // Log the deletion action
            Log::info("Location '{$locationName}' (ID: {$locationId}, Type: {$locationType}) deleted by User ID: " . Auth::id());

            return response()->json([
                'message' => ucfirst($locationType) . ' deleted successfully.'
            ]); // 200 OK is fine, 204 No Content also valid

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                Log::warning("Attempt to delete Location ID {$location->id} failed due to existing reservations.");
                return response()->json(['message' => 'Cannot delete this location because it has existing reservations. Please cancel reservations first.'], 409);
            }
            Log::error("Location deletion query failed for ID {$location->id}: " . $e->getMessage());
            return response()->json(['message' => 'Database error occurred while deleting the location.'], 500);
        } catch (\Exception $e) {
            Log::error("Location deletion failed for ID {$location->id}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while deleting the location.'], 500);
        }
    }
}
