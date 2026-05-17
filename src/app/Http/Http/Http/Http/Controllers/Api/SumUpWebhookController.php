<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\QuoteStateMachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SumUpWebhookController extends Controller
{
    /**
     * Handle incoming SumUp webhook.
     * Public endpoint - no auth required.
     * Always returns HTTP 200.
     */
    public function handle(Request $request, int $companyId)
    {
        try {
            $payload = $request->all();

            Log::info('SumUp webhook received', [
                'company_id' => $companyId,
                'event_type' => $payload['event_type'] ?? null,
                'checkout_reference' => $payload['checkout_reference'] ?? null,
            ]);

            $eventType = $payload['event_type'] ?? null;

            // Only process checkout.paid events
            if ($eventType !== 'checkout.paid') {
                Log::info('SumUp webhook ignored: not a checkout.paid event', [
                    'event_type' => $eventType,
                ]);
                return response()->json(['ok' => true]);
            }

            $checkoutId = $payload['id'] ?? null;
            if (!$checkoutId) {
                Log::warning('SumUp webhook: missing checkout id');
                return response()->json(['ok' => true]);
            }

            // Find quote by sumup_checkout_id
            $quote = Quote::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('sumup_checkout_id', $checkoutId)
                ->where('status', Quote::STATUS_SENT)
                ->first();

            if (!$quote) {
                Log::warning('SumUp webhook: quote not found for checkout', [
                    'company_id' => $companyId,
                    'checkout_id' => $checkoutId,
                ]);
                return response()->json(['ok' => true]);
            }

            $stateMachine = new QuoteStateMachineService();
            $stateMachine->transitionToDepositReceived($quote, $payload);

            Log::info('SumUp webhook: quote transitioned to deposit_received', [
                'quote_id' => $quote->id,
                'checkout_id' => $checkoutId,
            ]);
        } catch (\Exception $e) {
            Log::error('SumUp webhook error', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200
        return response()->json(['ok' => true]);
    }
}
