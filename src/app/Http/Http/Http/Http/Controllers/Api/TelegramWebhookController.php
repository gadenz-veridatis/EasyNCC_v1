<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use App\Models\Task;
use App\Models\TelegramConfig;
use App\Models\TelegramMessage;
use App\Models\TelegramNotification;
use App\Models\TelegramUser;
use App\Models\VehicleMileageEntry;
use App\Services\TelegramAPI;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook.
     * This is a public endpoint - no auth required.
     * Always returns HTTP 200 to Telegram.
     */
    public function handle(Request $request, int $companyId)
    {
        try {
            $update = $request->all();

            Log::channel('telegram')->info('Webhook received', [
                'company_id' => $companyId,
                'update_id' => $update['update_id'] ?? null,
            ]);

            // Verify company has a telegram config
            $config = TelegramConfig::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->first();

            if (!$config) {
                Log::channel('telegram')->warning('Webhook for unconfigured company', [
                    'company_id' => $companyId,
                ]);
                return response()->json(['ok' => true]);
            }

            $api = new TelegramAPI($config->getRawOriginal('bot_token'));

            // Handle different update types
            if (isset($update['message'])) {
                $this->handleMessage($update['message'], $companyId, $api);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query'], $companyId, $api);
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Webhook error', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 to Telegram
        return response()->json(['ok' => true]);
    }

    /**
     * Handle an incoming message.
     */
    private function handleMessage(array $message, int $companyId, TelegramAPI $api): void
    {
        $chatId = $message['chat']['id'];
        $from = $message['from'] ?? [];
        $text = $message['text'] ?? '';

        // Register or update the Telegram user
        $telegramUser = $this->upsertTelegramUser($companyId, $chatId, $from);

        // Check if it's a command
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($telegramUser, $chatId, $message, $api);
            return;
        }

        // Generic text message
        $this->handleTextMessage($telegramUser, $chatId, $text, $message, $api);
    }

    /**
     * Handle /start command.
     */
    private function handleStartCommand(TelegramUser $telegramUser, int $chatId, array $message, TelegramAPI $api): void
    {
        $firstName = $telegramUser->first_name ?? 'utente';

        $welcomeText = "Benvenuto {$firstName}!\n\n"
            . "Sei stato registrato correttamente.\n"
            . "Invia la tua matricola o email aziendale per il riconoscimento.\n\n"
            . "Riceverai qui le notifiche dei servizi assegnati.";

        $result = $api->sendMessage($chatId, $welcomeText);

        // Save outbound welcome message
        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $welcomeText,
            'telegram_message_id' => $result['result']['message_id'] ?? null,
            'is_read' => true,
        ]);

        // Save inbound /start message
        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => '/start',
            'telegram_message_id' => $message['message_id'] ?? null,
            'is_read' => true,
        ]);

        Log::channel('telegram')->info('User started bot', [
            'company_id' => $telegramUser->company_id,
            'chat_id' => $chatId,
            'username' => $telegramUser->username,
        ]);
    }

    /**
     * Handle generic text message.
     */
    private function handleTextMessage(TelegramUser $telegramUser, int $chatId, string $text, array $message, TelegramAPI $api): void
    {
        // Check for pending conversational actions
        if ($telegramUser->pending_action) {
            if (str_starts_with($telegramUser->pending_action, 'awaiting_ko_comment:')) {
                $this->handleKoComment($text, $telegramUser, $chatId, $message, $api);
                return;
            }
            if (str_starts_with($telegramUser->pending_action, 'awaiting_mileage:')) {
                $this->handleMileageReading($text, $telegramUser, $chatId, $message, $api);
                return;
            }
        }

        // Save inbound message
        $savedMessage = TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => $text,
            'telegram_message_id' => $message['message_id'] ?? null,
            'is_read' => false,
        ]);

        // Create notification
        $displayName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));
        if (!$displayName) {
            $displayName = $telegramUser->username ?? 'Utente Telegram';
        }

        TelegramNotification::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'telegram_message_id' => $savedMessage->id,
            'type' => 'new_message',
            'title' => "Messaggio da {$displayName}",
            'body' => mb_substr($text, 0, 200),
            'is_read' => false,
        ]);

        // Send confirmation reply
        $confirmText = "Messaggio ricevuto. Grazie!";
        $result = $api->sendMessage($chatId, $confirmText);

        // Save outbound confirmation
        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $confirmText,
            'telegram_message_id' => $result['result']['message_id'] ?? null,
            'is_read' => true,
        ]);
    }

    /**
     * Handle callback query (inline button press).
     */
    private function handleCallbackQuery(array $callbackQuery, int $companyId, TelegramAPI $api): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $from = $callbackQuery['from'] ?? [];
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        if (!$chatId) {
            return;
        }

        // Find or create the telegram user
        $telegramUser = $this->upsertTelegramUser($companyId, $chatId, $from);

        // Save callback as a message
        TelegramMessage::create([
            'company_id' => $companyId,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'callback',
            'content' => null,
            'telegram_message_id' => $messageId,
            'callback_data' => $data,
            'is_read' => false,
        ]);

        // Handle service acceptance callback
        if (str_starts_with($data, 'accetta_servizio:')) {
            $this->handleAcceptService($data, $telegramUser, $chatId, $messageId, $callbackId, $api);
            return;
        }

        // Handle payment collection callback
        if (str_starts_with($data, 'incasso:')) {
            $this->handlePaymentCollection($data, $telegramUser, $chatId, $messageId, $callbackId, $api);
            return;
        }

        // Handle service closure callback
        if (str_starts_with($data, 'chiusura:')) {
            $this->handleServiceClosure($data, $telegramUser, $chatId, $messageId, $callbackId, $api);
            return;
        }

        // Unknown callback - just acknowledge
        $api->answerCallbackQuery($callbackId, 'Azione ricevuta');
    }

    /**
     * Handle "accept service" callback.
     */
    private function handleAcceptService(string $data, TelegramUser $telegramUser, int $chatId, int $messageId, string $callbackId, TelegramAPI $api): void
    {
        $serviceId = (int) str_replace('accetta_servizio:', '', $data);

        try {
            // Find the service
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->with(['status', 'drivers'])
                ->first();

            if (!$service) {
                $api->answerCallbackQuery($callbackId, 'Servizio non trovato');
                return;
            }

            // Check if the Telegram user is associated to a driver
            if (!$telegramUser->user_id) {
                $api->answerCallbackQuery($callbackId, 'Il tuo account Telegram non è associato a un driver. Contatta l\'amministrazione.');
                return;
            }

            // Check if this driver is actually assigned to this service
            $isAssigned = $service->drivers->contains('id', $telegramUser->user_id);
            if (!$isAssigned) {
                $api->answerCallbackQuery($callbackId, 'Non sei assegnato a questo servizio.');
                return;
            }

            // Check if the service is already accepted/confirmed
            $currentStatusName = strtolower(trim($service->status->name ?? ''));
            if (str_contains($currentStatusName, 'confermato')) {
                $api->answerCallbackQuery($callbackId, 'Servizio già accettato');

                // Remove inline keyboard
                $api->editMessageReplyMarkup($chatId, $messageId, null);
                return;
            }

            // Check if service is in a state that can be accepted (only "preventivo" or similar)
            $nonAcceptableStatuses = ['completato', 'cancellato', 'no-show', 'in corso'];
            if (in_array($currentStatusName, $nonAcceptableStatuses)) {
                $api->answerCallbackQuery($callbackId, "Impossibile accettare: il servizio è in stato \"{$service->status->name}\"");
                return;
            }

            // Load company settings to get acceptance status
            $settings = \App\Models\Settings::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->first();

            // If no accepted status configured, acknowledge but don't change status
            if (!$settings || !$settings->telegram_accepted_status_id) {
                $api->answerCallbackQuery($callbackId, 'Servizio registrato come accettato');

                // Still remove the button
                $api->editMessageReplyMarkup($chatId, $messageId, null);

                Log::channel('telegram')->warning('Service accepted but no status configured', [
                    'service_id' => $serviceId,
                    'company_id' => $telegramUser->company_id,
                ]);

                // Continue to send confirmation message (will be handled after this block)
            } else {
                // Update service status to configured acceptance status
                $service->status_id = $settings->telegram_accepted_status_id;
                $service->save();

                // Acknowledge the callback
                $api->answerCallbackQuery($callbackId, 'Servizio accettato!');

                // Remove the button
                $api->editMessageReplyMarkup($chatId, $messageId, null);
            }

            // Send confirmation message
            $timestamp = now()->format('d/m/Y H:i');
            $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));

            $pickupDate = $service->pickup_datetime
                ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y H:i')
                : '';

            $confirmText = "<b>SERVIZIO ACCETTATO</b>\n\n"
                . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
                . "<b>Pickup:</b> {$pickupDate}\n"
                . "<b>Accettato da:</b> {$driverName}\n"
                . "<b>Data/Ora:</b> {$timestamp}";

            $result = $api->sendMessage($chatId, $confirmText);

            // Save confirmation as outbound message
            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $confirmText,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            // Create notification
            TelegramNotification::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'type' => 'service_accepted',
                'title' => "Servizio #{$serviceId} accettato",
                'body' => "Accettato da {$driverName} il {$timestamp}",
                'is_read' => false,
            ]);

            Log::channel('telegram')->info('Service accepted via Telegram', [
                'service_id' => $serviceId,
                'company_id' => $telegramUser->company_id,
                'driver_id' => $telegramUser->user_id,
                'driver' => $driverName,
            ]);

            // Complete the acceptance task via unique tag
            $acceptTask = Task::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->where('name', 'LIKE', "[TG:ACCEPT:{$serviceId}]%")
                ->where('status', 'to_complete')
                ->first();

            if ($acceptTask) {
                $acceptTask->update(['status' => 'completed']);
                Log::channel('telegram')->info('Accept task completed', [
                    'task_id' => $acceptTask->id,
                    'service_id' => $serviceId,
                ]);
            }

            // Handle payment collection if driver must collect
            $this->handleDriverMustCollect($service, $serviceId, $telegramUser, $chatId, $api);

            // If no payment collection required, proceed directly to service closure
            if (!$service->driver_must_collect) {
                $this->requestServiceClosure($service, $serviceId, $telegramUser, $chatId, $api);
            }

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error accepting service', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $api->answerCallbackQuery($callbackId, 'Errore nell\'accettazione del servizio');
        }
    }

    /**
     * If driver_must_collect is true, send payment collection message with buttons.
     */
    private function handleDriverMustCollect(\App\Models\Service $service, int $serviceId, TelegramUser $telegramUser, int $chatId, TelegramAPI $api): void
    {
        $service->refresh();
        $service->load(['client', 'passengers']);

        if (!$service->driver_must_collect) {
            return;
        }

        $balanceTaxable = (float) ($service->balance_taxable ?? 0);
        $balanceCardFees = (float) ($service->balance_card_fees ?? 0);

        // If both amounts are 0, notify driver and admin, skip payment flow
        if ($balanceTaxable <= 0 && $balanceCardFees <= 0) {
            $noAmountText = "<b>⚠️ ATTENZIONE</b>\n\n"
                . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
                . "Il servizio è contrassegnato come \"Da Incassare\" ma gli importi saldo risultano a zero.\n"
                . "Contatta l'amministrazione per verificare.";

            $result = $api->sendMessage($chatId, $noAmountText);

            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $noAmountText,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            TelegramNotification::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'type' => 'payment_warning',
                'title' => "Incasso servizio #{$serviceId} - Importi a zero",
                'body' => "Il servizio ha driver_must_collect attivo ma importi saldo a zero.",
                'is_read' => false,
            ]);

            Log::channel('telegram')->warning('Payment collection skipped: amounts are zero', [
                'service_id' => $serviceId,
            ]);

            // Still proceed to service closure even if payment amounts are zero
            $this->requestServiceClosure($service, $serviceId, $telegramUser, $chatId, $api);
            return;
        }

        // Send payment collection message with two buttons
        $amountCassa = number_format($balanceTaxable, 2, ',', '.');
        $amountCarta = number_format($balanceCardFees, 2, ',', '.');

        $paymentText = "<b>INCASSO RICHIESTO</b>\n\n"
            . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
            . "<b>Saldo Imponibile:</b> € {$amountCassa}\n"
            . "<b>Saldo Card Fees:</b> € {$amountCarta}\n\n"
            . "Seleziona la modalità di incasso:";

        $paymentKeyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => "💵 CASSA € {$amountCassa}",
                        'callback_data' => "incasso:{$serviceId}:cassa",
                    ],
                    [
                        'text' => "💳 CARTA € {$amountCarta}",
                        'callback_data' => "incasso:{$serviceId}:carta",
                    ],
                ],
                [
                    [
                        'text' => "❌ ANNULLA INCASSO",
                        'callback_data' => "incasso:{$serviceId}:annulla",
                    ],
                ]
            ]
        ];

        $paymentResult = $api->sendMessage($chatId, $paymentText, $paymentKeyboard);

        if ($paymentResult && ($paymentResult['ok'] ?? false)) {
            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $paymentText,
                'telegram_message_id' => $paymentResult['result']['message_id'] ?? null,
                'is_read' => true,
            ]);
        }

        // Create collection task with unique tag
        $pickupTime = $service->pickup_datetime
            ? Carbon::parse($service->pickup_datetime)->format('H:i')
            : '';
        $serviceType = $service->service_type ?? '';
        $passengerCount = $service->passengers ? $service->passengers->count() : 0;
        $contactName = '';
        if ($service->client) {
            $contactName = trim(($service->client->name ?? '') . ' ' . ($service->client->surname ?? ''));
        }
        $clientName = $service->client->business_name ?? $contactName;

        $titleParts = array_filter([$pickupTime, $serviceType, "{$passengerCount} pax", $contactName, $clientName]);
        $collectionTaskTitle = "[TG:COLLECT:{$serviceId}] " . implode(' | ', $titleParts) . ' - Segnalare modalità di incasso';

        $collectionTask = Task::create([
            'company_id' => $telegramUser->company_id,
            'service_id' => $serviceId,
            'name' => $collectionTaskTitle,
            'due_date' => $service->pickup_datetime
                ? Carbon::parse($service->pickup_datetime)->toDateString()
                : null,
            'notes' => "Task creato automaticamente all'accettazione del servizio #{$serviceId}. Driver deve incassare.",
            'status' => 'to_complete',
        ]);

        if ($telegramUser->user_id) {
            $collectionTask->assignedUsers()->sync([$telegramUser->user_id]);
        }

        Log::channel('telegram')->info('Payment collection message sent and task created', [
            'service_id' => $serviceId,
            'task_id' => $collectionTask->id,
            'balance_taxable' => $balanceTaxable,
            'balance_card_fees' => $balanceCardFees,
        ]);
    }

    /**
     * Handle payment collection callback (incasso:{serviceId}:{metodo}).
     */
    private function handlePaymentCollection(string $data, TelegramUser $telegramUser, int $chatId, int $messageId, string $callbackId, TelegramAPI $api): void
    {
        $parts = explode(':', $data);
        if (count($parts) !== 3) {
            $api->answerCallbackQuery($callbackId, 'Formato callback non valido');
            return;
        }

        $serviceId = (int) $parts[1];
        $metodo = $parts[2]; // 'cassa', 'carta' o 'annulla'

        try {
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->first();

            if (!$service) {
                $api->answerCallbackQuery($callbackId, 'Servizio non trovato');
                return;
            }

            $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));
            $timestamp = now()->format('d/m/Y H:i');

            // Handle cancellation
            if ($metodo === 'annulla') {
                // Append cancellation note to service
                $cancelNote = "Il driver {$driverName} ha annullato la riscossione ({$timestamp})";
                $service->update([
                    'notes' => ($service->notes ? $service->notes . "\n" : '') . $cancelNote,
                ]);

                // Complete the collection task
                $collectionTask = Task::withoutGlobalScopes()
                    ->where('company_id', $telegramUser->company_id)
                    ->where('name', 'LIKE', "[TG:COLLECT:{$serviceId}]%")
                    ->where('status', 'to_complete')
                    ->first();

                if ($collectionTask) {
                    $collectionTask->update(['status' => 'completed']);
                }

                // Create task for operators to verify
                $cancelTaskTitle = "[TG:CANCEL_COLLECT:{$serviceId}] Annullamento incasso - Verificare motivazioni";
                $cancelTaskNotes = "Il driver {$driverName} ha annullato la riscossione per il servizio #{$serviceId} - {$service->reference_number} il {$timestamp}.\n\n"
                    . "Azioni richieste:\n"
                    . "- Verificare le motivazioni dell'annullamento con il driver\n"
                    . "- Verificare lo stato del servizio e dei movimenti contabili";

                $cancelTask = Task::create([
                    'company_id' => $telegramUser->company_id,
                    'service_id' => $serviceId,
                    'name' => $cancelTaskTitle,
                    'due_date' => now()->toDateString(),
                    'notes' => $cancelTaskNotes,
                    'status' => 'to_complete',
                ]);

                // Assign to all active operators
                $operatorIds = \App\Models\User::withoutGlobalScopes()
                    ->where('company_id', $telegramUser->company_id)
                    ->whereIn('role', ['operator', 'admin'])
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();

                if (!empty($operatorIds)) {
                    $cancelTask->assignedUsers()->sync($operatorIds);
                }

                // Remove inline keyboard
                $api->editMessageReplyMarkup($chatId, $messageId, null);

                // Acknowledge callback
                $api->answerCallbackQuery($callbackId, 'Incasso annullato');

                // Send confirmation to driver
                $confirmText = "<b>INCASSO ANNULLATO</b>\n\n"
                    . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
                    . "<b>Annullato da:</b> {$driverName}\n"
                    . "<b>Data/Ora:</b> {$timestamp}\n\n"
                    . "L'operatore è stato notificato e verificherà la situazione.";

                $result = $api->sendMessage($chatId, $confirmText);

                TelegramMessage::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'direction' => 'outbound',
                    'message_type' => 'text',
                    'content' => $confirmText,
                    'telegram_message_id' => $result['result']['message_id'] ?? null,
                    'is_read' => true,
                ]);

                // Create notification for admin
                TelegramNotification::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'type' => 'payment_cancelled',
                    'title' => "Annullamento incasso servizio #{$serviceId}",
                    'body' => "Il driver {$driverName} ha annullato la riscossione il {$timestamp}. Verificare motivazioni e stato del servizio.",
                    'is_read' => false,
                ]);

                Log::channel('telegram')->info('Payment collection cancelled by driver', [
                    'service_id' => $serviceId,
                    'driver_id' => $telegramUser->user_id,
                    'task_id' => $cancelTask->id,
                ]);

                // Proceed to service closure step
                $this->requestServiceClosure($service, $serviceId, $telegramUser, $chatId, $api);
                return;
            }

            $metodoLabel = $metodo === 'cassa' ? 'Contanti' : 'Carta';
            $amount = $metodo === 'cassa'
                ? (float) ($service->balance_taxable ?? 0)
                : (float) ($service->balance_card_fees ?? 0);
            $amountFormatted = number_format($amount, 2, ',', '.');

            // Find balance sale transaction
            $balanceTransaction = AccountingTransaction::where('service_id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->where('transaction_type', 'sale')
                ->where('installment', 'balance')
                ->first();

            if (!$balanceTransaction) {
                // Transaction not found - notify driver and admin
                $api->answerCallbackQuery($callbackId, 'Movimento contabile saldo non trovato');

                $errorText = "<b>⚠️ ATTENZIONE</b>\n\n"
                    . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
                    . "Il movimento contabile saldo vendita non è stato trovato.\n"
                    . "L'incasso <b>non</b> è stato registrato. Contatta l'amministrazione.";

                $result = $api->sendMessage($chatId, $errorText);

                TelegramMessage::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'direction' => 'outbound',
                    'message_type' => 'text',
                    'content' => $errorText,
                    'telegram_message_id' => $result['result']['message_id'] ?? null,
                    'is_read' => true,
                ]);

                TelegramNotification::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'type' => 'payment_error',
                    'title' => "Errore incasso servizio #{$serviceId}",
                    'body' => "Movimento contabile saldo vendita non trovato. Driver {$driverName} ha tentato incasso {$metodoLabel}.",
                    'is_read' => false,
                ]);

                // Remove buttons to prevent repeated attempts
                $api->editMessageReplyMarkup($chatId, $messageId, null);
                return;
            }

            // Update service balance_sale_type based on payment method
            $balanceSaleType = $metodo === 'cassa' ? 'balance_taxable' : 'balance_card_fees';
            $service->update(['balance_sale_type' => $balanceSaleType]);

            // Determine collected status from settings (fallback to 'collected_driver')
            $settings = \App\Models\Settings::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->first();
            $collectedStatus = $settings->telegram_collected_status_id ?? null;

            // If a service status is configured, load its name for the transaction status
            $transactionStatus = 'collected_driver';
            if ($collectedStatus) {
                $configuredStatus = \App\Models\ServiceStatus::withoutGlobalScopes()->find($collectedStatus);
                if ($configuredStatus) {
                    $transactionStatus = $configuredStatus->name;
                }
            }

            // Update accounting transaction status
            $balanceTransaction->update([
                'status' => $transactionStatus,
                'amount' => $amount,
                'payment_date' => now(),
                'notes' => ($balanceTransaction->notes ? $balanceTransaction->notes . "\n" : '')
                    . "Incassato via Telegram ({$metodoLabel}) il " . now()->format('d/m/Y H:i'),
            ]);

            // Create/update handling fees and card fees debit movements for card payments
            $this->syncBalanceFeeTransactions($service, $metodo, $telegramUser->company_id);

            // Complete the collection task via unique tag
            $collectionTask = Task::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->where('name', 'LIKE', "[TG:COLLECT:{$serviceId}]%")
                ->where('status', 'to_complete')
                ->first();

            if ($collectionTask) {
                $collectionTask->update(['status' => 'completed']);
            }

            // Remove inline keyboard buttons
            $api->editMessageReplyMarkup($chatId, $messageId, null);

            // Acknowledge callback
            $api->answerCallbackQuery($callbackId, "Incasso registrato: {$metodoLabel}");

            // Send confirmation message
            $confirmText = "<b>INCASSO REGISTRATO</b>\n\n"
                . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
                . "<b>Modalità:</b> {$metodoLabel}\n"
                . "<b>Importo:</b> € {$amountFormatted}\n"
                . "<b>Registrato da:</b> {$driverName}\n"
                . "<b>Data/Ora:</b> {$timestamp}";

            $result = $api->sendMessage($chatId, $confirmText);

            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $confirmText,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            // Create notification for admin
            TelegramNotification::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'type' => 'payment_collected',
                'title' => "Incasso servizio #{$serviceId}",
                'body' => "Incassato € {$amountFormatted} ({$metodoLabel}) da {$driverName} il {$timestamp}",
                'is_read' => false,
            ]);

            Log::channel('telegram')->info('Payment collection registered', [
                'service_id' => $serviceId,
                'method' => $metodo,
                'amount' => $amount,
                'driver_id' => $telegramUser->user_id,
            ]);

            // Proceed to service closure step
            $this->requestServiceClosure($service, $serviceId, $telegramUser, $chatId, $api);

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error handling payment collection', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $api->answerCallbackQuery($callbackId, 'Errore nella registrazione dell\'incasso');
        }
    }

    /**
     * Request service closure from driver (OK/KO buttons).
     * Called after payment collection or after acceptance if no payment required.
     */
    private function requestServiceClosure(\App\Models\Service $service, int $serviceId, TelegramUser $telegramUser, int $chatId, TelegramAPI $api): void
    {
        // Check if closure statuses are configured
        $settings = \App\Models\Settings::withoutGlobalScopes()
            ->where('company_id', $telegramUser->company_id)
            ->first();

        if (!$settings || !$settings->telegram_closed_ok_status_id) {
            Log::channel('telegram')->info('Service closure skipped: no closure status configured', [
                'service_id' => $serviceId,
            ]);
            return;
        }

        $closureText = "<b>CHIUSURA SERVIZIO</b>\n\n"
            . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
            . "Come si è concluso il servizio?";

        $closureKeyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Concluso OK',
                        'callback_data' => "chiusura:{$serviceId}:ok",
                    ],
                    [
                        'text' => 'Concluso KO',
                        'callback_data' => "chiusura:{$serviceId}:ko",
                    ],
                ]
            ]
        ];

        $result = $api->sendMessage($chatId, $closureText, $closureKeyboard);

        if ($result && ($result['ok'] ?? false)) {
            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $closureText,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_read' => true,
            ]);
        }

        // Create closure task assigned to driver
        $pickupTime = $service->pickup_datetime
            ? Carbon::parse($service->pickup_datetime)->format('H:i')
            : '';
        $serviceType = $service->service_type ?? '';

        $closureTaskTitle = "[TG:CLOSE:{$serviceId}] {$pickupTime} | {$serviceType} - Confermare chiusura servizio";

        $closureTask = Task::create([
            'company_id' => $telegramUser->company_id,
            'service_id' => $serviceId,
            'name' => $closureTaskTitle,
            'due_date' => $service->pickup_datetime
                ? Carbon::parse($service->pickup_datetime)->toDateString()
                : null,
            'notes' => "Task creato automaticamente. Il driver deve confermare la chiusura del servizio #{$serviceId} via Telegram.",
            'status' => 'to_complete',
        ]);

        if ($telegramUser->user_id) {
            $closureTask->assignedUsers()->sync([$telegramUser->user_id]);
        }

        Log::channel('telegram')->info('Service closure request sent', [
            'service_id' => $serviceId,
            'task_id' => $closureTask->id,
        ]);
    }

    /**
     * Handle service closure callback (chiusura:{serviceId}:{result}).
     */
    private function handleServiceClosure(string $data, TelegramUser $telegramUser, int $chatId, int $messageId, string $callbackId, TelegramAPI $api): void
    {
        $parts = explode(':', $data);
        if (count($parts) !== 3) {
            $api->answerCallbackQuery($callbackId, 'Formato callback non valido');
            return;
        }

        $serviceId = (int) $parts[1];
        $result = $parts[2]; // 'ok' or 'ko'

        try {
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->first();

            if (!$service) {
                $api->answerCallbackQuery($callbackId, 'Servizio non trovato');
                return;
            }

            $settings = \App\Models\Settings::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->first();

            // Complete the closure task
            $closeTask = Task::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->where('name', 'LIKE', "[TG:CLOSE:{$serviceId}]%")
                ->where('status', 'to_complete')
                ->first();

            if ($closeTask) {
                $closeTask->update(['status' => 'completed']);
            }

            // Remove inline keyboard buttons
            $api->editMessageReplyMarkup($chatId, $messageId, null);

            $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));
            $timestamp = now()->format('d/m/Y H:i');

            if ($result === 'ok') {
                // Update status to closed OK
                if ($settings && $settings->telegram_closed_ok_status_id) {
                    $service->update(['status_id' => $settings->telegram_closed_ok_status_id]);
                }

                // Add note to service
                $noteText = "Il driver {$driverName} ha dichiarato che il servizio si è concluso con successo ({$timestamp})";
                $service->update([
                    'notes' => ($service->notes ? $service->notes . "\n" : '') . $noteText,
                ]);

                $api->answerCallbackQuery($callbackId, 'Servizio chiuso con successo');

                $confirmText = "<b>SERVIZIO CONCLUSO OK</b>\n\n"
                    . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
                    . "<b>Chiuso da:</b> {$driverName}\n"
                    . "<b>Data/Ora:</b> {$timestamp}\n\n"
                    . "Grazie per la conferma!";

                $msgResult = $api->sendMessage($chatId, $confirmText);

                TelegramMessage::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'direction' => 'outbound',
                    'message_type' => 'text',
                    'content' => $confirmText,
                    'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
                    'is_read' => true,
                ]);

                TelegramNotification::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'type' => 'service_closed_ok',
                    'title' => "Servizio #{$serviceId} concluso OK",
                    'body' => "Chiuso con successo da {$driverName} il {$timestamp}",
                    'is_read' => false,
                ]);

                Log::channel('telegram')->info('Service closed OK', [
                    'service_id' => $serviceId,
                    'driver_id' => $telegramUser->user_id,
                ]);

                // Request mileage reading
                $this->requestMileageReading($service, $serviceId, $telegramUser, $chatId, $api);

            } else {
                // KO flow
                if ($settings && $settings->telegram_closed_ko_status_id) {
                    $service->update(['status_id' => $settings->telegram_closed_ko_status_id]);
                }

                $api->answerCallbackQuery($callbackId, 'Servizio segnalato come KO');

                // Set pending action for KO comment
                $telegramUser->update(['pending_action' => "awaiting_ko_comment:{$serviceId}"]);

                $koText = "<b>SERVIZIO CONCLUSO KO</b>\n\n"
                    . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
                    . "Descrivi brevemente il problema riscontrato:";

                $msgResult = $api->sendMessage($chatId, $koText);

                TelegramMessage::create([
                    'company_id' => $telegramUser->company_id,
                    'telegram_user_id' => $telegramUser->id,
                    'direction' => 'outbound',
                    'message_type' => 'text',
                    'content' => $koText,
                    'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
                    'is_read' => true,
                ]);

                Log::channel('telegram')->info('Service closed KO, awaiting comment', [
                    'service_id' => $serviceId,
                    'driver_id' => $telegramUser->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error handling service closure', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $api->answerCallbackQuery($callbackId, 'Errore nella chiusura del servizio');
        }
    }

    /**
     * Handle KO comment from driver (text message while awaiting_ko_comment).
     */
    private function handleKoComment(string $text, TelegramUser $telegramUser, int $chatId, array $message, TelegramAPI $api): void
    {
        $serviceId = (int) str_replace('awaiting_ko_comment:', '', $telegramUser->pending_action);

        // Save inbound message
        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => $text,
            'telegram_message_id' => $message['message_id'] ?? null,
            'is_read' => false,
        ]);

        try {
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->first();

            if (!$service) {
                $telegramUser->update(['pending_action' => null]);
                $api->sendMessage($chatId, "Servizio non trovato. Operazione annullata.");
                return;
            }

            $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));
            $timestamp = now()->format('d/m/Y H:i');

            // Append comment to service notes
            $commentNote = "Commento driver via Telegram ({$driverName}, {$timestamp}): {$text}";
            $service->update([
                'notes' => ($service->notes ? $service->notes . "\n" : '') . $commentNote,
            ]);

            // Create ISSUE task assigned to operators
            $pickupTime = $service->pickup_datetime
                ? Carbon::parse($service->pickup_datetime)->format('H:i')
                : '';
            $serviceType = $service->service_type ?? '';

            $issueTaskTitle = "[TG:ISSUE:{$serviceId}] {$pickupTime} | {$serviceType} - Problematica segnalata dal driver";

            $issueTask = Task::create([
                'company_id' => $telegramUser->company_id,
                'service_id' => $serviceId,
                'name' => $issueTaskTitle,
                'due_date' => now()->toDateString(),
                'notes' => "Il driver {$driverName} ha segnalato un problema per il servizio #{$serviceId}:\n\n{$text}",
                'status' => 'to_complete',
            ]);

            // Assign task to all operators of the company
            $operators = \App\Models\User::withoutGlobalScopes()
                ->where('company_id', $telegramUser->company_id)
                ->where('role', 'operator')
                ->where('is_active', true)
                ->pluck('id');

            if ($operators->isNotEmpty()) {
                $issueTask->assignedUsers()->sync($operators->toArray());
            }

            // Create notification for admin
            TelegramNotification::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'type' => 'service_closed_ko',
                'title' => "Servizio #{$serviceId} - Problema segnalato",
                'body' => "Driver {$driverName}: " . mb_substr($text, 0, 200),
                'is_read' => false,
            ]);

            // Reset pending action
            $telegramUser->update(['pending_action' => null]);

            // Send confirmation
            $confirmText = "<b>COMMENTO REGISTRATO</b>\n\n"
                . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
                . "Il tuo commento è stato registrato e un task è stato assegnato agli operatori.\n"
                . "Grazie per la segnalazione.";

            $msgResult = $api->sendMessage($chatId, $confirmText);

            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $confirmText,
                'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            Log::channel('telegram')->info('KO comment registered and ISSUE task created', [
                'service_id' => $serviceId,
                'task_id' => $issueTask->id,
                'driver_id' => $telegramUser->user_id,
            ]);

            // Request mileage reading
            $this->requestMileageReading($service, $serviceId, $telegramUser, $chatId, $api);

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error handling KO comment', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $api->sendMessage($chatId, "Errore nella registrazione del commento. Riprova.");
        }
    }

    /**
     * Request mileage reading from driver after service closure.
     */
    private function requestMileageReading(\App\Models\Service $service, int $serviceId, TelegramUser $telegramUser, int $chatId, TelegramAPI $api): void
    {
        // Check if service has a vehicle assigned
        if (!$service->vehicle_id) {
            Log::channel('telegram')->info('Mileage reading skipped: no vehicle assigned', [
                'service_id' => $serviceId,
            ]);
            return;
        }

        // Set pending action for mileage reading
        $telegramUser->update(['pending_action' => "awaiting_mileage:{$serviceId}"]);

        $mileageText = "<b>LETTURA CHILOMETRAGGIO</b>\n\n"
            . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n\n"
            . "Inserisci la lettura attuale del contachilometri (km):";

        $msgResult = $api->sendMessage($chatId, $mileageText);

        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $mileageText,
            'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
            'is_read' => true,
        ]);
    }

    /**
     * Handle mileage reading from driver (text message while awaiting_mileage).
     */
    private function handleMileageReading(string $text, TelegramUser $telegramUser, int $chatId, array $message, TelegramAPI $api): void
    {
        $serviceId = (int) str_replace('awaiting_mileage:', '', $telegramUser->pending_action);

        // Save inbound message
        TelegramMessage::create([
            'company_id' => $telegramUser->company_id,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => $text,
            'telegram_message_id' => $message['message_id'] ?? null,
            'is_read' => false,
        ]);

        // Clean text: remove spaces and dots (e.g., "125.000" or "125 000")
        $cleanText = str_replace(['.', ',', ' '], '', trim($text));

        // Validate that it's a positive integer
        if (!ctype_digit($cleanText) || (int) $cleanText <= 0) {
            $errorText = "Inserisci un numero valido di km (es. 125000):";
            $msgResult = $api->sendMessage($chatId, $errorText);

            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $errorText,
                'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
                'is_read' => true,
            ]);
            return; // Don't reset pending_action, let them retry
        }

        $mileage = (int) $cleanText;

        try {
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->first();

            if (!$service || !$service->vehicle_id) {
                $telegramUser->update(['pending_action' => null]);
                $api->sendMessage($chatId, "Servizio o veicolo non trovato. Operazione annullata.");
                return;
            }

            // Create mileage entry
            VehicleMileageEntry::create([
                'vehicle_id' => $service->vehicle_id,
                'mileage' => $mileage,
                'entry_date' => now()->toDateString(),
                'update_type' => 'service',
                'created_by' => $telegramUser->user_id,
                'notes' => "Lettura di fine servizio #{$serviceId} effettuata via Telegram",
            ]);

            // Reset pending action
            $telegramUser->update(['pending_action' => null]);

            $formattedKm = number_format($mileage, 0, ',', '.');
            $confirmText = "<b>KM REGISTRATI</b>\n\n"
                . "<b>Servizio:</b> #{$serviceId} - {$service->reference_number}\n"
                . "<b>Km registrati:</b> {$formattedKm} km\n\n"
                . "Grazie! La lettura è stata registrata con successo.";

            $msgResult = $api->sendMessage($chatId, $confirmText);

            TelegramMessage::create([
                'company_id' => $telegramUser->company_id,
                'telegram_user_id' => $telegramUser->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'content' => $confirmText,
                'telegram_message_id' => $msgResult['result']['message_id'] ?? null,
                'is_read' => true,
            ]);

            Log::channel('telegram')->info('Mileage reading registered via Telegram', [
                'service_id' => $serviceId,
                'vehicle_id' => $service->vehicle_id,
                'mileage' => $mileage,
                'driver_id' => $telegramUser->user_id,
            ]);

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error handling mileage reading', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $telegramUser->update(['pending_action' => null]);
            $api->sendMessage($chatId, "Errore nella registrazione dei km. Riprova o contatta l'amministrazione.");
        }
    }

    /**
     * Register or update a Telegram user.
     */
    private function upsertTelegramUser(int $companyId, int $chatId, array $from): TelegramUser
    {
        return TelegramUser::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $companyId,
                'telegram_chat_id' => $chatId,
            ],
            [
                'telegram_user_id' => $from['id'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
                'is_active' => true,
            ]
        );
    }

    /**
     * Create or delete balance fee transactions (handling fees / card fees)
     * mirroring the logic in processAccountingTransactions() on the frontend.
     */
    private function syncBalanceFeeTransactions(\App\Models\Service $service, string $metodo, int $companyId): void
    {
        try {
            $settings = \App\Models\Settings::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->first();

            if (!$settings) return;

            $handlingEntryId = $settings->handling_fees_accounting_entry_id ?? null;
            $cardFeesEntryId = $settings->card_fees_accounting_entry_id ?? null;
            $transactionDate = $service->pickup_datetime
                ? Carbon::parse($service->pickup_datetime)->toDateString()
                : now()->toDateString();
            $counterpartId = $service->client_id;

            if ($metodo === 'carta') {
                // Create Saldo Handling Fees if applicable
                $handlingAmount = round((float) ($service->balance_handling_fees ?? 0) - (float) ($service->balance_taxable ?? 0), 2);
                if ($handlingAmount > 0 && $handlingEntryId) {
                    AccountingTransaction::updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'company_id' => $companyId,
                            'transaction_type' => 'purchase',
                            'installment' => 'balance',
                            'accounting_entry_id' => $handlingEntryId,
                        ],
                        [
                            'amount' => $handlingAmount,
                            'counterpart_id' => $counterpartId,
                            'transaction_date' => $transactionDate,
                            'payment_type' => 'contanti',
                            'payment_reason' => $settings->handling_fees_reason ?? null,
                            'status' => 'to_pay',
                            'is_automatic' => true,
                        ]
                    );
                }

                // Create Saldo Card Fees if applicable
                $cardFeesAmount = round((float) ($service->balance_card_fees ?? 0) - (float) ($service->balance_handling_fees ?? 0), 2);
                if ($cardFeesAmount > 0 && $cardFeesEntryId) {
                    AccountingTransaction::updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'company_id' => $companyId,
                            'transaction_type' => 'purchase',
                            'installment' => 'balance',
                            'accounting_entry_id' => $cardFeesEntryId,
                        ],
                        [
                            'amount' => $cardFeesAmount,
                            'counterpart_id' => $counterpartId,
                            'transaction_date' => $transactionDate,
                            'payment_type' => 'contanti',
                            'payment_reason' => $settings->card_fees_reason ?? null,
                            'status' => 'to_pay',
                            'is_automatic' => true,
                        ]
                    );
                }

                Log::channel('telegram')->info('Balance fee transactions created for card payment', [
                    'service_id' => $service->id,
                    'handling_amount' => $handlingAmount ?? 0,
                    'card_fees_amount' => $cardFeesAmount ?? 0,
                ]);
            } else {
                // Cash payment: delete any existing handling/card fees balance transactions
                if ($handlingEntryId) {
                    AccountingTransaction::where('service_id', $service->id)
                        ->where('company_id', $companyId)
                        ->where('transaction_type', 'purchase')
                        ->where('installment', 'balance')
                        ->where('accounting_entry_id', $handlingEntryId)
                        ->where('is_automatic', true)
                        ->delete();
                }
                if ($cardFeesEntryId) {
                    AccountingTransaction::where('service_id', $service->id)
                        ->where('company_id', $companyId)
                        ->where('transaction_type', 'purchase')
                        ->where('installment', 'balance')
                        ->where('accounting_entry_id', $cardFeesEntryId)
                        ->where('is_automatic', true)
                        ->delete();
                }
            }
        } catch (\Exception $e) {
            Log::channel('telegram')->error('Error syncing balance fee transactions', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
