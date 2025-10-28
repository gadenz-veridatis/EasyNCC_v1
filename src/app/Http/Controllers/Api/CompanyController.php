<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['users', 'vehicles', 'services']);

        // Search on name and email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $companies = $query->paginate($perPage);

        return response()->json($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:companies,name',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string',
            'vat_number' => 'nullable|string|unique:companies,vat_number',
            'sdi' => 'nullable|string',
            'pec' => 'nullable|email',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $company = Company::create($validated);

        return response()->json($company->load(['users', 'vehicles', 'services']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json($company->load([
            'users',
            'vehicles',
            'services',
            'dressCodes',
            'paymentTypes',
            'serviceStatuses',
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:companies,name,' . $company->id,
            'email' => 'sometimes|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string',
            'vat_number' => 'nullable|string|unique:companies,vat_number,' . $company->id,
            'sdi' => 'nullable|string',
            'pec' => 'nullable|email',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return response()->json($company->load(['users', 'vehicles', 'services']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully'], 200);
    }
}
