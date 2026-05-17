<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentType::with('company');

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
        $paymentTypes = $query->paginate($perPage);

        return response()->json($paymentTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $paymentType = PaymentType::create($validated);

        return response()->json($paymentType->load('company'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentType $paymentType): JsonResponse
    {
        return response()->json($paymentType->load('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentType $paymentType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $paymentType->update($validated);

        return response()->json($paymentType->load('company'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentType $paymentType): JsonResponse
    {
        $paymentType->delete();

        return response()->json(['message' => 'Payment type deleted successfully'], 200);
    }
}
