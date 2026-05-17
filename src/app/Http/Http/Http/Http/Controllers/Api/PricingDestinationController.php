<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PricingDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingDestinationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PricingDestination::query();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $query->withoutGlobalScopes()->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('destination', 'ilike', "%{$search}%");
            });
        }

        $destinations = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['data' => $destinations]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'service_type' => 'required|string|max:50',
            'duration_hours' => 'required|numeric|min:0',
            'mileage_km' => 'required|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'experience_a' => 'nullable|numeric|min:0',
            'experience_b' => 'nullable|numeric|min:0',
            'experience_c' => 'nullable|numeric|min:0',
            'experience_d' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->has('company_id'))
            ? $request->company_id
            : $user->company_id;

        $validated['company_id'] = $companyId;
        $validated['name'] = $validated['destination'] . ' - ' . $validated['service_type'];

        $destination = PricingDestination::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Destinazione creata con successo',
            'data' => $destination,
        ], 201);
    }

    public function update(Request $request, PricingDestination $pricingDestination)
    {
        $validated = $request->validate([
            'destination' => 'sometimes|required|string|max:255',
            'service_type' => 'sometimes|required|string|max:50',
            'duration_hours' => 'sometimes|numeric|min:0',
            'mileage_km' => 'sometimes|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'experience_a' => 'nullable|numeric|min:0',
            'experience_b' => 'nullable|numeric|min:0',
            'experience_c' => 'nullable|numeric|min:0',
            'experience_d' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        if (isset($validated['destination']) || isset($validated['service_type'])) {
            $dest = $validated['destination'] ?? $pricingDestination->destination;
            $type = $validated['service_type'] ?? $pricingDestination->service_type;
            $validated['name'] = $dest . ' - ' . $type;
        }

        $pricingDestination->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Destinazione aggiornata con successo',
            'data' => $pricingDestination->fresh(),
        ]);
    }

    public function destroy(PricingDestination $pricingDestination)
    {
        $pricingDestination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Destinazione eliminata con successo',
        ]);
    }
}
