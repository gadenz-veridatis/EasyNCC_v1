<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceStatus;
use Illuminate\Support\Facades\Log;

class QuoteServiceCreationService
{
    /**
     * Create Service(s) from a Quote - one per quote item.
     * Returns the first created service.
     */
    public function createFromQuote(Quote $quote): Service
    {
        $preventivoStatus = $this->findPreventivoStatus($quote->company_id);

        if (!$preventivoStatus) {
            throw new \RuntimeException('Service status "preventivo" not found for company ' . $quote->company_id);
        }

        $quote->loadMissing('items');
        $items = $quote->items;

        // If no items, create one service from quote-level data (legacy fallback)
        if ($items->isEmpty()) {
            $service = Service::create($this->mapQuoteToServiceData($quote, $preventivoStatus));
            Log::info("Service #{$service->id} created from Quote #{$quote->id} (no items)");
            return $service;
        }

        $firstService = null;
        foreach ($items as $index => $item) {
            $serviceData = $this->mapItemToServiceData($quote, $item, $preventivoStatus, $index);
            $service = Service::create($serviceData);
            Log::info("Service #{$service->id} created from Quote #{$quote->id}, Item #{$item->id}");

            if ($firstService === null) {
                $firstService = $service;
            }
        }

        return $firstService;
    }

    private function findPreventivoStatus(int $companyId): ?ServiceStatus
    {
        return ServiceStatus::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('name', 'ilike', 'preventivo')
            ->first();
    }

    /**
     * Map a quote item to service fields.
     */
    private function mapItemToServiceData(Quote $quote, $item, ServiceStatus $status, int $index): array
    {
        $pickupDatetime = $quote->service_date
            ? $quote->service_date->startOfDay()
            : now()->startOfDay();

        $durationHours = floatval($item->duration_hours ?: 0);
        $dropoffDatetime = $durationHours > 0
            ? $pickupDatetime->copy()->addHours($durationHours)
            : $pickupDatetime->copy()->addHours(1);

        $notes = "Creato automaticamente da Preventivo #{$quote->id} - Servizio " . ($index + 1);
        if ($quote->notes) {
            $notes .= "\n\n" . $quote->notes;
        }

        // For the first item, use the full quote pricing data
        // For subsequent items, just set the item-level price
        $servicePrice = $index === 0
            ? ($quote->discounted_taxable ?? $quote->taxable_price_rounded)
            : floatval($item->taxable_price);

        return [
            'company_id' => $quote->company_id,
            'status_id' => $status->id,
            'service_type' => $item->service_type ?: $quote->service_type,
            'passenger_count' => $item->pax_count ?: ($quote->pax_count ?? 1),
            'contact_name' => $quote->client_name,
            'pickup_datetime' => $pickupDatetime,
            'pickup_location' => $item->destination_name ?: 'Da definire',
            'pickup_address' => 'Da definire',
            'vehicle_departure_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
            'dropoff_location' => $item->destination_name ?: 'Da definire',
            'dropoff_address' => 'Da definire',
            'vehicle_return_datetime' => $dropoffDatetime,
            'service_price' => $servicePrice,
            'vat_rate' => $quote->vat_percentage,
            'card_fees_percentage' => $quote->card_fees_percentage,
            'deposit_percentage' => $index === 0 ? $quote->deposit_percentage : null,
            'deposit_taxable' => $index === 0 ? $quote->deposit_taxable : null,
            'deposit_handling_fees' => $index === 0 ? $quote->deposit_handling_fees : null,
            'deposit_amount' => $index === 0 ? $quote->deposit_total : null,
            'balance_taxable' => $index === 0 ? $quote->balance_taxable : null,
            'balance_handling_fees' => $index === 0 ? $quote->balance_handling_fees : null,
            'balance_card_fees' => $index === 0 ? $quote->balance_card_fees : null,
            'toll_cost' => $item->toll_cost,
            'notes' => $notes,
            'created_by' => $quote->created_by,
            'updated_by' => $quote->created_by,
        ];
    }

    /**
     * Legacy: map quote-level data to service fields (for quotes without items).
     */
    private function mapQuoteToServiceData(Quote $quote, ServiceStatus $status): array
    {
        $pickupDatetime = $quote->service_date
            ? $quote->service_date->startOfDay()
            : now()->startOfDay();

        $dropoffDatetime = $quote->duration_hours > 0
            ? $pickupDatetime->copy()->addHours(floatval($quote->duration_hours))
            : $pickupDatetime->copy()->addHours(1);

        return [
            'company_id' => $quote->company_id,
            'status_id' => $status->id,
            'service_type' => $quote->service_type,
            'passenger_count' => $quote->pax_count ?? 1,
            'contact_name' => $quote->client_name,
            'pickup_datetime' => $pickupDatetime,
            'pickup_location' => $quote->destination_name ?? 'Da definire',
            'pickup_address' => 'Da definire',
            'vehicle_departure_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
            'dropoff_location' => $quote->destination_name ?? 'Da definire',
            'dropoff_address' => 'Da definire',
            'vehicle_return_datetime' => $dropoffDatetime,
            'service_price' => $quote->discounted_taxable ?? $quote->taxable_price_rounded,
            'vat_rate' => $quote->vat_percentage,
            'card_fees_percentage' => $quote->card_fees_percentage,
            'deposit_percentage' => $quote->deposit_percentage,
            'deposit_taxable' => $quote->deposit_taxable,
            'deposit_handling_fees' => $quote->deposit_handling_fees,
            'deposit_amount' => $quote->deposit_total,
            'balance_taxable' => $quote->balance_taxable,
            'balance_handling_fees' => $quote->balance_handling_fees,
            'balance_card_fees' => $quote->balance_card_fees,
            'toll_cost' => $quote->toll_cost,
            'notes' => "Creato automaticamente da Preventivo #{$quote->id}" .
                ($quote->notes ? "\n\n" . $quote->notes : ''),
            'created_by' => $quote->created_by,
            'updated_by' => $quote->created_by,
        ];
    }
}
