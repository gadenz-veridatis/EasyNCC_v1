<?php

namespace App\Services;

use App\Models\Service;
use App\Models\TelegramConfig;
use App\Models\TelegramMessage;
use App\Models\TelegramNotification;
use App\Models\TelegramUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Send service notification with PDF to all assigned drivers via Telegram.
     * Called when a service status changes to "confermato".
     */
    public function notifyServiceConfirmed(Service $service): void
    {
        $companyId = $service->company_id;

        // Get bot config
        $config = TelegramConfig::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        if (!$config || !$config->getRawOriginal('bot_token') || !$config->webhook_active) {
            Log::channel('telegram')->info('Telegram notification skipped: bot not configured or inactive', [
                'company_id' => $companyId,
                'service_id' => $service->id,
            ]);
            return;
        }

        $api = new TelegramAPI($config->getRawOriginal('bot_token'));

        // Load service with all relations needed for the PDF
        $service->load([
            'vehicle',
            'status',
            'drivers',
            'passengers',
            'stops',
            'client',
            'intermediary',
            'supplier',
        ]);

        // Get assigned drivers
        $drivers = $service->drivers;

        if ($drivers->isEmpty()) {
            Log::channel('telegram')->info('No drivers assigned to service', [
                'service_id' => $service->id,
            ]);
            return;
        }

        // Generate PDF
        $pdfPath = $this->generateServicePdf($service);

        if (!$pdfPath) {
            Log::channel('telegram')->error('Failed to generate PDF for service notification', [
                'service_id' => $service->id,
            ]);
            return;
        }

        try {
            // Send to each driver that has a Telegram account
            foreach ($drivers as $driver) {
                $this->sendToDriver($driver, $service, $pdfPath, $companyId, $api);
            }
        } finally {
            // Clean up temp PDF file
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }
    }

    /**
     * Send service PDF and accept button to a specific driver.
     */
    private function sendToDriver($driver, Service $service, string $pdfPath, int $companyId, TelegramAPI $api): void
    {
        // Find the driver's Telegram user
        $telegramUser = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('user_id', $driver->id)
            ->where('is_active', true)
            ->first();

        if (!$telegramUser) {
            Log::channel('telegram')->info('Driver not registered on Telegram', [
                'driver_id' => $driver->id,
                'driver_name' => "{$driver->surname} {$driver->name}",
                'service_id' => $service->id,
            ]);
            return;
        }

        $chatId = $telegramUser->telegram_chat_id;

        // Build caption text
        $pickupDate = $service->pickup_datetime
            ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y H:i')
            : 'N/D';

        // HTML version for Telegram API
        $caption = "<b>NUOVO SERVIZIO ASSEGNATO</b>\n\n"
            . "<b>Rif.:</b> {$service->reference_number}\n"
            . "<b>Data Pickup:</b> {$pickupDate}\n"
            . "<b>Pickup:</b> {$service->pickup_address}\n"
            . "<b>Dropoff:</b> {$service->dropoff_address}\n\n"
            . "Controlla il PDF allegato per tutti i dettagli.\n"
            . "Premi il bottone per accettare il servizio.";

        // Plain text version for DB storage (web chat display)
        $captionPlain = "NUOVO SERVIZIO ASSEGNATO\n\n"
            . "Rif.: {$service->reference_number}\n"
            . "Data Pickup: {$pickupDate}\n"
            . "Pickup: {$service->pickup_address}\n"
            . "Dropoff: {$service->dropoff_address}\n\n"
            . "Controlla il PDF allegato per tutti i dettagli.\n"
            . "Premi il bottone per accettare il servizio.";

        // Inline keyboard with "Accept" button
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'ACCETTA SERVIZIO',
                        'callback_data' => "accetta_servizio:{$service->id}",
                    ]
                ]
            ]
        ];

        // Send document with caption and button
        $result = $api->sendDocument($chatId, $pdfPath, $caption, $keyboard);

        if ($result && ($result['ok'] ?? false)) {
            // Save outbound message
            TelegramMessage::create([
                'company_id' => $companyId,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'document',
                'content' => $captionPlain,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            // Create notification for the web UI
            TelegramNotification::create([
                'company_id' => $companyId,
                'telegram_user_id' => $telegramUser->id,
                'type' => 'service_notification_sent',
                'title' => "Notifica servizio #{$service->id} inviata",
                'body' => "Notifica inviata a {$driver->surname} {$driver->name} per il servizio del {$pickupDate}",
                'is_read' => false,
            ]);

            Log::channel('telegram')->info('Service notification sent', [
                'service_id' => $service->id,
                'driver_id' => $driver->id,
                'driver_name' => "{$driver->surname} {$driver->name}",
                'chat_id' => $chatId,
            ]);
        } else {
            Log::channel('telegram')->error('Failed to send service notification', [
                'service_id' => $service->id,
                'driver_id' => $driver->id,
                'chat_id' => $chatId,
                'error' => $result['description'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Generate a PDF with service details.
     * Returns the temp file path, or null on failure.
     */
    private function generateServicePdf(Service $service): ?string
    {
        try {
            $pdf = Pdf::loadView('pdf.service-details', ['service' => $service]);
            $pdf->setPaper('A4', 'portrait');

            $tempPath = storage_path("app/temp_service_{$service->id}_" . time() . '.pdf');

            // Ensure directory exists
            $dir = dirname($tempPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdf->save($tempPath);

            return $tempPath;
        } catch (\Exception $e) {
            Log::channel('telegram')->error('PDF generation failed', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
