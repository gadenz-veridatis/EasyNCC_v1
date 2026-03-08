<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use App\Models\Task;
use App\Models\TelegramConfig;
use App\Models\TelegramMessage;
use App\Models\TelegramNotification;
use App\Models\TelegramUser;
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
        $metodo = $parts[2]; // 'cassa' o 'carta'

        try {
            $service = \App\Models\Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('company_id', $telegramUser->company_id)
                ->first();

            if (!$service) {
                $api->answerCallbackQuery($callbackId, 'Servizio non trovato');
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

                $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));
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

            // Update accounting transaction to 'collected' with correct amount
            $balanceTransaction->update([
                'status' => 'collected',
                'amount' => $amount,
                'payment_date' => now(),
                'notes' => ($balanceTransaction->notes ? $balanceTransaction->notes . "\n" : '')
                    . "Incassato via Telegram ({$metodoLabel}) il " . now()->format('d/m/Y H:i'),
            ]);

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
            $timestamp = now()->format('d/m/Y H:i');
            $driverName = trim(($telegramUser->first_name ?? '') . ' ' . ($telegramUser->last_name ?? ''));

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
}
