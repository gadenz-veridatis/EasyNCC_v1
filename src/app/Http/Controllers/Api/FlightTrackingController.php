<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Services\FlightTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightTrackingController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flight_code' => 'required|string|max:20',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $user = $request->user();
        $companyId = $user->isSuperAdmin() && $request->filled('company_id')
            ? $request->company_id
            : $user->company_id;

        $settings = Settings::withoutGlobalScopes()->where('company_id', $companyId)->first();

        if (!$settings || !$settings->flight_tracking_enabled || !$settings->aviationstack_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'Tracking voli non configurato. Inserisci la API key nelle Impostazioni.',
            ], 422);
        }

        $service = new FlightTrackingService($settings->aviationstack_api_key);
        $result = $service->trackFlight($validated['flight_code'], $validated['date'] ?? null);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la ricerca del volo. Verifica la API key o riprova.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
