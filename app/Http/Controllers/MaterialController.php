<?php

namespace App\Http\Controllers;

use App\Models\Material; // Import the Material model
use App\Models\User;     // For creator relationship if needed
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    /**
     * Apply middleware for authorization.
     * Assumes a 'manage materials' permission exists and is assigned to admins.
     */
    public function __construct()
    {
        // Apply authentication (except potentially for public viewing)
        // $this->middleware('auth:sanctum')->except(['index', 'show']);

        // Apply permission checks for modification actions
        // Use 'manage materials' or create granular permissions (create, update, delete)
        $this->middleware('role.permission:manage materials')->only(['store', 'update', 'destroy']);

        // Optional: If index/show should also require a permission (e.g., 'list materials')
        $this->middleware('role.permission:list materials')->only(['index', 'show']);
    }

    /**
     * Display a listing of the materials.
     * Route: GET /materials
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Consider if non-admins should see this - adjust middleware if needed
        $query = Material::query()->with('creator:id,first_name'); // Eager load creator

        // --- Basic Filtering/Searching ---
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%')
                ->orWhere('identifier', 'like', '%' . $request->query('search') . '%');
        }

        // --- Sorting ---
        $sortBy = $request->query('sortBy', 'name');
        $sortDir = $request->query('sortDir', 'asc');
        if (in_array($sortBy, ['name', 'identifier', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc'); // Default sort
        }


        // --- Pagination ---
        $perPage = $request->query('per_page', 20);
        $materials = $query->paginate((int)$perPage);

        return response()->json($materials);
    }

    /**
     * Store a newly created material in storage.
     * Route: POST /materials
     * Requires 'manage materials' permission.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:materials,name',
                'description' => 'nullable|string',
                // 'identifier' => 'nullable|string|max:100|unique:materials,identifier', // Optional SKU/Asset Tag
                'quantity_available' => 'sometimes|required|integer|min:0', // If tracking stock
            ]);

            // Add creator ID
            $validated['created_by'] = Auth::id();

            $material = Material::create($validated);

            Log::info("Material '{$material->name}' (ID: {$material->id}) created by User ID: " . Auth::id());

            return response()->json([
                'message' => 'Material created successfully.',
                'material' => $material->load('creator:id,first_name')
            ], 201); // 201 Created

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Material creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while creating the material.'], 500);
        }
    }

    /**
     * Display the specified material.
     * Route: GET /materials/{material}
     *
     * @param Material $material Injected by Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Material $material)
    {
        // Consider if non-admins should see this - adjust middleware if needed
        $material->load('creator:id,first_name');
        // Maybe load recent reservations using this material? (Be careful with performance)
        // $material->load(['reservations' => fn($q) => $q->latest()->limit(5)]);

        return response()->json($material);
    }

    /**
     * Update the specified material in storage.
     * Route: PUT/PATCH /materials/{material}
     * Requires 'manage materials' permission.
     *
     * @param Request $request
     * @param Material $material Injected by Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Material $material)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materials', 'name')->ignore($material->id) // Ignore self on unique check
                ],
                'description' => 'sometimes|nullable|string',
                // 'identifier' => [
                //     'sometimes',
                //     'nullable',
                //     'string',
                //     'max:100',
                //     Rule::unique('materials', 'identifier')->ignore($material->id) // Ignore self on unique check
                // ],
                'quantity_available' => 'sometimes|required|integer|min:0', // If tracking stock
            ]);

            if (empty($validated)) {
                return response()->json(['message' => 'No valid fields provided for update.'], 400);
            }

            $material->update($validated);

            Log::info("Material '{$material->name}' (ID: {$material->id}) updated by User ID: " . Auth::id());


            return response()->json([
                'message' => 'Material updated successfully.',
                'material' => $material->fresh('creator:id,first_name') // Get fresh data
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Material update failed for ID {$material->id}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the material.'], 500);
        }
    }

    /**
     * Remove the specified material from storage.
     * Route: DELETE /materials/{material}
     * Requires 'manage materials' permission.
     *
     * @param Material $material Injected by Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Material $material)
    {
        try {
            $materialName = $material->name;
            $materialId = $material->id;

            // Note: The reservation_material migration used cascadeOnDelete for material_id.
            // This means deleting a material will automatically remove entries from the pivot table,
            // effectively detaching it from any reservations it was linked to.
            // This is usually the desired behavior for this kind of relationship.
            $material->delete();

            Log::info("Material '{$materialName}' (ID: {$materialId}) deleted by User ID: " . Auth::id());

            return response()->json(['message' => 'Material deleted successfully.'], 200); // Or 204 No Content

        } catch (\Exception $e) {
            // Catching potential DB errors, although cascade should handle FKs.
            Log::error("Material deletion failed for ID {$material->id}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while deleting the material.'], 500);
        }
    }
}
