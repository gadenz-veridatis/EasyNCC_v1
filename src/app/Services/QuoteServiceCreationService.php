<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceStatus;
use Illuminate\Support\Facades\Log;

class QuoteServiceCreationService
{
    /**
     * Create a partial Service from a Quote.
     * The service is created with status "preventivo" and minimal data.
     */
    public function createFromQuote(Quote $quote): Service
    {
        $preventivoStatus = $this->findPreventivoStatus($quote->company_id);

        if (!$preventivoStatus) {
            throw new \RuntimeException('Service status "preventivo" not found for company ' . $quote->company_id);
        }

        $serviceData = $this->mapQuoteToServiceData($quote, $preventivoStatus);

        $service = Service::create($serviceData);

        Log::info("Service #{$service->id} created from Quote #{$quote->id}");

        return $service;
    }

    /**
     * Find the "preventivo" service status for a company.
     */
    private function findPreventivoStatus(int $companyId): ?ServiceStatus
    {
        return ServiceStatus::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('name', 'ilike', 'preventivo')
            ->first();
    }

    /**
     * Map quote data to service fields.
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
