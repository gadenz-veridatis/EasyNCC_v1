<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DriverAttachment;
use App\Models\Service;
use App\Models\VehicleMileageEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServicePdfService
{
    /**
     * Generate a PDF with service details.
     * Returns the temp file path, or null on failure.
     */
    public function generate(Service $service, $driver = null): ?string
    {
        try {
            $service->loadMissing([
                'vehicle', 'status', 'drivers', 'drivers.driverAttachments',
                'passengers', 'stops', 'activities', 'client', 'intermediary', 'supplier',
            ]);

            $data = $this->preparePdfData($service, $driver);

            $pdf = Pdf::loadView('pdf.service-details', $data);
            $pdf->setPaper('A4', 'portrait');

            $tempPath = storage_path("app/temp_service_{$service->id}_" . time() . '.pdf');

            $dir = dirname($tempPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdf->save($tempPath);

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function preparePdfData(Service $service, $driver = null): array
    {
        // Company stamp
        $stampUrl = null;
        $company = Company::find($service->company_id);
        if ($company && $company->stamp) {
            $stampPath = Storage::disk('public')->path($company->stamp);
            if (file_exists($stampPath)) {
                $stampUrl = $stampPath;
            }
        }

        // Driver name
        $driverName = '';
        if ($driver) {
            $driverName = $driver->display_name;
        } elseif ($service->drivers && $service->drivers->isNotEmpty()) {
            $firstDriver = $service->drivers->first();
            $driverName = $firstDriver->display_name;
            $driver = $firstDriver;
        }

        // Date/time fields
        $dataServizio = '';
        $dataPickup = '';
        $oraPickup = '';
        $dataDropoff = '';
        $oraDropoff = '';

        if ($service->pickup_datetime) {
            $pickup = Carbon::parse($service->pickup_datetime);
            $dataServizio = $pickup->format('d/m/Y');
            $dataPickup = $pickup->format('d/m/Y');
            $oraPickup = $pickup->format('H:i');
        }

        if ($service->dropoff_datetime) {
            $dropoff = Carbon::parse($service->dropoff_datetime);
            $dataDropoff = $dropoff->format('d/m/Y');
            $oraDropoff = $dropoff->format('H:i');
        }

        // Vehicle mileage
        $kmVeicolo = '';
        if ($service->vehicle_id) {
            $latestEntry = VehicleMileageEntry::where('vehicle_id', $service->vehicle_id)
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestEntry) {
                $kmVeicolo = number_format($latestEntry->mileage, 0, ',', '.');
            }
        }

        // Passenger
        $nomePasseggero = '';
        $telefonoPasseggero = '';
        if ($service->passengers && $service->passengers->isNotEmpty()) {
            $firstPassenger = $service->passengers->first();
            $nomePasseggero = $firstPassenger->name ?? '';
            $telefonoPasseggero = $firstPassenger->phone ?? '';
        }

        // Driver digital signature
        $firmaDriverUrl = null;
        if ($driver) {
            $signatureAttachment = DriverAttachment::where('user_id', $driver->id)
                ->where('attachment_type', 'Firma digitale')
                ->latest()
                ->first();

            if ($signatureAttachment && $signatureAttachment->file_path) {
                $sigPath = Storage::disk('public')->path($signatureAttachment->file_path);
                if (file_exists($sigPath)) {
                    $firmaDriverUrl = $sigPath;
                }
            }
        }

        return [
            'service' => $service,
            'stampUrl' => $stampUrl,
            'driverName' => $driverName,
            'dataServizio' => $dataServizio,
            'dataPickup' => $dataPickup,
            'oraPickup' => $oraPickup,
            'dataDropoff' => $dataDropoff,
            'oraDropoff' => $oraDropoff,
            'kmVeicolo' => $kmVeicolo,
            'nomePasseggero' => $nomePasseggero,
            'telefonoPasseggero' => $telefonoPasseggero,
            'firmaDriverUrl' => $firmaDriverUrl,
        ];
    }
}
