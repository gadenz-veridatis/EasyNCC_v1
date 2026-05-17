<?php

namespace App\Services;

use App\Models\Company;
use App\Models\QuoteEmailTemplate;
use App\Models\Service;

class ServiceTemplateService
{
    /**
     * Render a template with service data substituted for placeholders.
     */
    public function render(QuoteEmailTemplate $template, Service $service, array $extraPlaceholders = []): array
    {
        $service->loadMissing([
            'vehicle', 'client', 'supplier.clientProfile', 'intermediary',
            'drivers', 'passengers', 'stops', 'activities.activityType', 'dressCode', 'status',
        ]);

        $placeholders = array_merge(
            $this->buildPlaceholderMap($service),
            $extraPlaceholders
        );

        $subject = $this->replacePlaceholders($template->subject, $placeholders);
        $body = $this->replacePlaceholders($template->body_html, $placeholders);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get all available placeholders for service templates.
     */
    public static function getAvailablePlaceholders(string $type = 'service_assignment'): array
    {
        $common = [
            ['key' => '{{reference_number}}', 'description' => 'Numero riferimento servizio'],
            ['key' => '{{service_date}}', 'description' => 'Data servizio (DD/MM/YYYY)'],
            ['key' => '{{pickup_time}}', 'description' => 'Ora pickup (HH:mm)'],
            ['key' => '{{pickup_address}}', 'description' => 'Indirizzo pickup'],
            ['key' => '{{dropoff_time}}', 'description' => 'Ora dropoff (HH:mm)'],
            ['key' => '{{dropoff_address}}', 'description' => 'Indirizzo dropoff'],
            ['key' => '{{vehicle_departure_time}}', 'description' => 'Ora uscita mezzo (HH:mm)'],
            ['key' => '{{vehicle_return_time}}', 'description' => 'Ora rientro mezzo (HH:mm)'],
            ['key' => '{{vehicle_info}}', 'description' => 'Targa - Marca Modello veicolo'],
            ['key' => '{{driver_name}}', 'description' => 'Nome driver assegnato'],
            ['key' => '{{passenger_name}}', 'description' => 'Nome primo passeggero'],
            ['key' => '{{passenger_count}}', 'description' => 'Numero passeggeri'],
            ['key' => '{{passenger_phone}}', 'description' => 'Telefono primo passeggero'],
            ['key' => '{{client_name}}', 'description' => 'Nome committente'],
            ['key' => '{{supplier_name}}', 'description' => 'Nome fornitore'],
            ['key' => '{{company_name}}', 'description' => 'Nome azienda'],
            ['key' => '{{service_type}}', 'description' => 'Tipologia servizio'],
            ['key' => '{{dress_code}}', 'description' => 'Dress code'],
            ['key' => '{{status}}', 'description' => 'Stato servizio'],
            ['key' => '{{notes}}', 'description' => 'Note servizio'],
            ['key' => '{{stops_list}}', 'description' => 'Lista fermate intermedie'],
        ];

        if ($type === 'service_assignment') {
            $common[] = ['key' => '{{accept_link}}', 'description' => 'Pulsante/link per accettare il servizio'];
        }

        if ($type === 'service_closure') {
            $common[] = ['key' => '{{closure_link}}', 'description' => 'Pulsante/link per chiudere il servizio'];
        }

        return $common;
    }

    /**
     * Build the placeholder → value map from a service.
     */
    private function buildPlaceholderMap(Service $service): array
    {
        $company = $service->company ?? Company::find($service->company_id);

        // Driver name
        $driverName = '';
        if ($service->drivers && $service->drivers->isNotEmpty()) {
            $driverName = $service->drivers->map(fn($d) => trim($d->name . ' ' . $d->surname))->implode(', ');
        }

        // Passenger info
        $passengerName = '';
        $passengerPhone = '';
        if ($service->passengers && $service->passengers->isNotEmpty()) {
            $first = $service->passengers->first();
            $passengerName = $first->name ?? '';
            $passengerPhone = $first->phone ?? '';
        }

        // Vehicle info
        $vehicleInfo = '';
        if ($service->vehicle) {
            $vehicleInfo = trim($service->vehicle->license_plate . ' - ' . $service->vehicle->brand . ' ' . $service->vehicle->model);
        }

        // Client name
        $clientName = '';
        if ($service->client) {
            $clientName = $service->client->clientProfile?->business_name
                ?: trim(($service->client->name ?? '') . ' ' . ($service->client->surname ?? ''));
        }

        // Supplier name
        $supplierName = '';
        if ($service->supplier) {
            $supplierName = $service->supplier->clientProfile?->business_name
                ?: trim(($service->supplier->name ?? '') . ' ' . ($service->supplier->surname ?? ''));
        }

        // Stops list
        $stopsList = '';
        if ($service->stops && $service->stops->isNotEmpty()) {
            $stopsList = '<ul>';
            foreach ($service->stops as $stop) {
                $stopsList .= '<li>' . e($stop->name ?? '') . ' - ' . e($stop->address ?? '') . '</li>';
            }
            $stopsList .= '</ul>';
        }

        return [
            '{{reference_number}}' => $service->reference_number ?? '',
            '{{service_date}}' => $service->pickup_datetime ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y') : '',
            '{{pickup_time}}' => $service->pickup_datetime ? \Carbon\Carbon::parse($service->pickup_datetime)->format('H:i') : '',
            '{{pickup_address}}' => $service->pickup_address ?? '',
            '{{dropoff_time}}' => $service->dropoff_datetime ? \Carbon\Carbon::parse($service->dropoff_datetime)->format('H:i') : '',
            '{{dropoff_address}}' => $service->dropoff_address ?? '',
            '{{vehicle_departure_time}}' => $service->vehicle_departure_datetime ? \Carbon\Carbon::parse($service->vehicle_departure_datetime)->format('H:i') : '',
            '{{vehicle_return_time}}' => $service->vehicle_return_datetime ? \Carbon\Carbon::parse($service->vehicle_return_datetime)->format('H:i') : '',
            '{{vehicle_info}}' => $vehicleInfo,
            '{{driver_name}}' => $driverName,
            '{{passenger_name}}' => $passengerName,
            '{{passenger_count}}' => (string)($service->passenger_count ?? 0),
            '{{passenger_phone}}' => $passengerPhone,
            '{{client_name}}' => $clientName,
            '{{supplier_name}}' => $supplierName,
            '{{company_name}}' => $company->name ?? '',
            '{{service_type}}' => $service->service_type ?? '',
            '{{dress_code}}' => $service->dressCode?->name ?? '',
            '{{status}}' => $service->status?->name ?? '',
            '{{notes}}' => $service->notes ?? '',
            '{{stops_list}}' => $stopsList,
            // accept_link and closure_link are injected via $extraPlaceholders
            '{{accept_link}}' => '',
            '{{closure_link}}' => '',
        ];
    }

    private function replacePlaceholders(string $text, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
