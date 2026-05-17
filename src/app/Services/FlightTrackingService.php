<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlightTrackingService
{
    private string $baseUrl = 'http://api.aviationstack.com/v1';
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Track a flight by IATA code and optional date.
     *
     * @param string $flightIata Flight IATA code (e.g., AZ1234)
     * @param string|null $date Date in YYYY-MM-DD format
     * @return array|null
     */
    public function trackFlight(string $flightIata, ?string $date = null): ?array
    {
        try {
            $params = [
                'access_key' => $this->apiKey,
                'flight_iata' => strtoupper(trim($flightIata)),
            ];

            if ($date) {
                $params['flight_date'] = $date;
            }

            $response = Http::timeout(15)
                ->acceptJson()
                ->get($this->baseUrl . '/flights', $params);

            if ($response->failed()) {
                Log::channel('daily')->error('AviationStack API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'flight' => $flightIata,
                ]);
                return null;
            }

            $data = $response->json();

            // Check for API errors
            if (isset($data['error'])) {
                Log::channel('daily')->error('AviationStack API error response', [
                    'error' => $data['error'],
                    'flight' => $flightIata,
                ]);
                return null;
            }

            $flights = $data['data'] ?? [];

            if (empty($flights)) {
                return ['found' => false, 'message' => 'Nessun volo trovato'];
            }

            // Take the most relevant flight (first result)
            $flight = $flights[0];

            return [
                'found' => true,
                'flight_date' => $flight['flight_date'] ?? null,
                'flight_status' => $flight['flight_status'] ?? null,
                'airline' => [
                    'name' => $flight['airline']['name'] ?? null,
                    'iata' => $flight['airline']['iata'] ?? null,
                ],
                'flight' => [
                    'number' => $flight['flight']['number'] ?? null,
                    'iata' => $flight['flight']['iata'] ?? null,
                ],
                'departure' => [
                    'airport' => $flight['departure']['airport'] ?? null,
                    'iata' => $flight['departure']['iata'] ?? null,
                    'terminal' => $flight['departure']['terminal'] ?? null,
                    'gate' => $flight['departure']['gate'] ?? null,
                    'scheduled' => $flight['departure']['scheduled'] ?? null,
                    'estimated' => $flight['departure']['estimated'] ?? null,
                    'actual' => $flight['departure']['actual'] ?? null,
                    'delay' => $flight['departure']['delay'] ?? null,
                ],
                'arrival' => [
                    'airport' => $flight['arrival']['airport'] ?? null,
                    'iata' => $flight['arrival']['iata'] ?? null,
                    'terminal' => $flight['arrival']['terminal'] ?? null,
                    'gate' => $flight['arrival']['gate'] ?? null,
                    'scheduled' => $flight['arrival']['scheduled'] ?? null,
                    'estimated' => $flight['arrival']['estimated'] ?? null,
                    'actual' => $flight['arrival']['actual'] ?? null,
                    'delay' => $flight['arrival']['delay'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            Log::channel('daily')->error('FlightTracking exception', [
                'flight' => $flightIata,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
