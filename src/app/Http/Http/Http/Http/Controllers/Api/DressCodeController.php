<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DressCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DressCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DressCode::with('company');

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $dressCodes = $query->paginate($perPage);

        return response()->json($dressCodes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'color_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $dressCode = DressCode::create($validated);

        return response()->json($dressCode->load('company'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(DressCode $dressCode): JsonResponse
    {
        return response()->json($dressCode->load('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DressCode $dressCode): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'color_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $dressCode->update($validated);

        return response()->json($dressCode->load('company'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DressCode $dressCode): JsonResponse
    {
        $dressCode->delete();

        return response()->json(['message' => 'Dress code deleted successfully'], 200);
    }
}
