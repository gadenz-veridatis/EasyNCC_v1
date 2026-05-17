<?php

namespace App\Services;

use App\Models\SumupConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SumUpService
{
    private SumupConfig $config;
    private string $baseUrl = 'https://api.sumup.com/v0.1/checkouts';

    public function __construct(SumupConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Create a checkout (payment link).
     */
    public function createCheckout(
        string $reference,
        float $amount,
        string $currency,
        string $description,
        ?string $returnUrl = null
    ): array {
        $payload = [
            'checkout_reference' => $reference,
            'amount' => round($amount, 2),
            'currency' => $currency,
            'pay_to_email' => $this->config->merchant_code,
            'description' => $description,
            'hosted_checkout' => ['enabled' => true],
        ];

        if ($returnUrl) {
            $payload['redirect_url'] = $returnUrl;
        }

        $response = $this->call('POST', '', $payload);

        $checkoutId = $response['id'] ?? null;
        // Use hosted_checkout_url from response, or construct fallback
        $checkoutUrl = $response['hosted_checkout_url']
            ?? ($checkoutId ? "https://checkout.sumup.com/pay/{$checkoutId}" : null);

        return [
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    /**
     * Get checkout status.
     */
    public function getCheckout(string $checkoutId): array
    {
        return $this->call('GET', "/{$checkoutId}");
    }

    /**
     * Deactivate a checkout (cannot delete, but can deactivate).
     */
    public function deactivateCheckout(string $checkoutId): bool
    {
        try {
            $this->call('PUT', "/{$checkoutId}", [
                'status' => 'INACTIVE',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::channel('sumup')->warning("Failed to deactivate checkout {$checkoutId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Make an API call to SumUp.
     */
    private function call(string $method, string $endpoint, array $data = []): array
    {
        try {
            $apiKey = $this->config->getRawOriginal('api_key');

            $request = Http::withToken($apiKey)
                ->timeout(15)
                ->acceptJson();

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url, $data),
            };

            if ($response->failed()) {
                Log::channel('sumup')->error("SumUp API error: {$response->status()}", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'response' => $response->json(),
                ]);
                throw new \RuntimeException("SumUp API error: {$response->status()} - " . ($response->json('message') ?? $response->body()));
            }

            return $response->json() ?? [];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('sumup')->error("SumUp connection error: " . $e->getMessage());
            throw new \RuntimeException("SumUp connection error: " . $e->getMessage());
        }
    }
}
