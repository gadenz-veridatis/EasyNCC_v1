<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceEmailToken;
use App\Services\ServiceEmailNotificationService;
use Illuminate\Http\Request;

class ServiceEmailActionController extends Controller
{
    /**
     * Show the action page and process the token.
     */
    public function handle(string $tokenString)
    {
        $token = ServiceEmailToken::where('token', $tokenString)->first();

        if (!$token) {
            return $this->renderPage('Token non valido', 'Il link che hai utilizzato non è valido.', 'danger');
        }

        if ($token->used_at) {
            $actionLabel = $token->action === 'accept' ? 'accettato' : 'chiuso';
            return $this->renderPage(
                'Azione già eseguita',
                "Questo servizio è già stato {$actionLabel}.",
                'warning'
            );
        }

        if ($token->expires_at->isPast()) {
            return $this->renderPage(
                'Link scaduto',
                'Il link è scaduto. Contatta l\'azienda per richiederne uno nuovo.',
                'warning'
            );
        }

        // Load service and company for branding
        $service = Service::with(['vehicle', 'passengers'])->find($token->service_id);
        $company = Company::find($token->company_id);

        if (!$service) {
            return $this->renderPage('Servizio non trovato', 'Il servizio associato non è più disponibile.', 'danger');
        }

        $actionLabel = $token->action === 'accept' ? 'Accetta Servizio' : 'Chiudi Servizio';
        $actionColor = $token->action === 'accept' ? '#28a745' : '#007bff';

        // Show confirmation page
        return $this->renderActionPage($service, $company, $token, $actionLabel, $actionColor);
    }

    /**
     * Process the confirmed action.
     */
    public function confirm(Request $request, string $tokenString)
    {
        $token = ServiceEmailToken::where('token', $tokenString)->first();

        if (!$token || $token->used_at || $token->expires_at->isPast()) {
            return $this->renderPage('Errore', 'Azione non valida o link scaduto.', 'danger');
        }

        try {
            $emailService = app(ServiceEmailNotificationService::class);

            if ($token->action === 'accept') {
                $emailService->handleAcceptAction($token);
                return $this->renderPage(
                    'Servizio Accettato',
                    'Hai accettato il servizio con successo. Riceverai una email con il link per la chiusura.',
                    'success'
                );
            } elseif ($token->action === 'close') {
                $emailService->handleCloseAction($token);
                return $this->renderPage(
                    'Servizio Chiuso',
                    'Hai confermato la chiusura del servizio con successo.',
                    'success'
                );
            }

            return $this->renderPage('Errore', 'Azione non riconosciuta.', 'danger');
        } catch (\Exception $e) {
            return $this->renderPage('Errore', 'Si è verificato un errore: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Render a simple status page.
     */
    private function renderPage(string $title, string $message, string $type): \Illuminate\Http\Response
    {
        $colors = [
            'success' => ['bg' => '#d4edda', 'border' => '#c3e6cb', 'text' => '#155724', 'icon' => '✓'],
            'warning' => ['bg' => '#fff3cd', 'border' => '#ffc107', 'text' => '#856404', 'icon' => '⚠'],
            'danger' => ['bg' => '#f8d7da', 'border' => '#f5c6cb', 'text' => '#721c24', 'icon' => '✗'],
        ];
        $c = $colors[$type] ?? $colors['danger'];

        $html = $this->pageWrapper("
            <div style='text-align:center;padding:60px 20px;'>
                <div style='font-size:60px;margin-bottom:20px;'>{$c['icon']}</div>
                <h1 style='color:{$c['text']};margin-bottom:15px;'>{$title}</h1>
                <p style='font-size:18px;color:#555;'>{$message}</p>
            </div>
        ");

        return response($html);
    }

    /**
     * Render the action confirmation page with service details.
     */
    private function renderActionPage(Service $service, ?Company $company, ServiceEmailToken $token, string $actionLabel, string $actionColor): \Illuminate\Http\Response
    {
        $pickupDate = $service->pickup_datetime
            ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y H:i')
            : 'N/D';

        $passengerName = '';
        if ($service->passengers && $service->passengers->isNotEmpty()) {
            $passengerName = $service->passengers->first()->name ?? '';
        }

        $vehicleInfo = '';
        if ($service->vehicle) {
            $vehicleInfo = $service->vehicle->license_plate . ' - ' . $service->vehicle->brand . ' ' . $service->vehicle->model;
        }

        $companyName = $company->name ?? 'EasyNCC';

        $html = $this->pageWrapper("
            <div style='text-align:center;padding:40px 20px;'>
                <h1 style='color:#333;margin-bottom:30px;'>{$actionLabel}</h1>
                <div style='max-width:500px;margin:0 auto;text-align:left;'>
                    <table style='width:100%;border-collapse:collapse;margin-bottom:30px;'>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Riferimento</td><td style='padding:10px;border:1px solid #ddd;'>" . e($service->reference_number) . "</td></tr>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Data Pickup</td><td style='padding:10px;border:1px solid #ddd;'>{$pickupDate}</td></tr>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Pickup</td><td style='padding:10px;border:1px solid #ddd;'>" . e($service->pickup_address) . "</td></tr>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Dropoff</td><td style='padding:10px;border:1px solid #ddd;'>" . e($service->dropoff_address) . "</td></tr>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Passeggero</td><td style='padding:10px;border:1px solid #ddd;'>" . e($passengerName) . "</td></tr>
                        <tr><td style='padding:10px;border:1px solid #ddd;background:#f8f9fa;font-weight:bold;'>Veicolo</td><td style='padding:10px;border:1px solid #ddd;'>" . e($vehicleInfo) . "</td></tr>
                    </table>
                    <form method='POST' action='" . url("/service-action/{$token->token}/confirm") . "' style='text-align:center;'>
                        " . csrf_field() . "
                        <button type='submit' style='display:inline-block;padding:15px 40px;background-color:{$actionColor};color:#fff;border:none;border-radius:5px;font-size:18px;font-weight:bold;cursor:pointer;'>
                            {$actionLabel}
                        </button>
                    </form>
                </div>
                <p style='margin-top:30px;color:#888;font-size:14px;'>{$companyName}</p>
            </div>
        ");

        return response($html);
    }

    /**
     * Wrap content in a basic HTML page.
     */
    private function pageWrapper(string $content): string
    {
        return "<!DOCTYPE html><html lang='it'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'><title>EasyNCC</title><style>body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f5f5;}*{box-sizing:border-box;}</style></head><body><div style='max-width:700px;margin:40px auto;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);overflow:hidden;'>{$content}</div></body></html>";
    }
}
