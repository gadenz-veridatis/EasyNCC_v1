<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramConfigController extends Controller
{
    /**
     * Get telegram config for the current company.
     */
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $config = TelegramConfig::where('company_id', $companyId)->first();

        if (!$config) {
            return response()->json([
                'data' => [
                    'company_id' => $companyId,
                    'bot_token' => '',
                    'bot_username' => null,
                    'webhook_url' => null,
                    'webhook_active' => false,
                ]
            ]);
        }

        // Return token unmasked for the form
        return response()->json([
            'data' => [
                'id' => $config->id,
                'company_id' => $config->company_id,
                'bot_token' => $config->getRawOriginal('bot_token'),
                'bot_username' => $config->bot_username,
                'webhook_url' => $config->webhook_url,
                'webhook_active' => $config->webhook_active,
            ]
        ]);
    }

    /**
     * Update or create telegram config.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'bot_token' => 'required|string|max:255',
            'webhook_url' => 'nullable|string|url|max:500',
        ]);

        $companyId = $this->getCompanyId($request);

        // Verify bot token with Telegram API
        $botInfo = $this->callTelegramApi($validated['bot_token'], 'getMe');

        if (!$botInfo || !$botInfo['ok']) {
            return response()->json([
                'message' => 'Token bot non valido. Verifica il token con @BotFather.',
                'errors' => ['bot_token' => ['Token non valido']]
            ], 422);
        }

        $config = TelegramConfig::updateOrCreate(
            ['company_id' => $companyId],
            [
                'bot_token' => $validated['bot_token'],
                'bot_username' => $botInfo['result']['username'] ?? null,
                'webhook_url' => $validated['webhook_url'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Configurazione Telegram salvata con successo',
            'data' => [
                'id' => $config->id,
                'company_id' => $config->company_id,
                'bot_token' => $config->getRawOriginal('bot_token'),
                'bot_username' => $config->bot_username,
                'webhook_url' => $config->webhook_url,
                'webhook_active' => $config->webhook_active,
            ]
        ]);
    }

    /**
     * Activate webhook.
     */
    public function activateWebhook(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $config = TelegramConfig::where('company_id', $companyId)->first();

        if (!$config || !$config->getRawOriginal('bot_token')) {
            return response()->json([
                'message' => 'Configurazione bot non trovata. Salva prima il token.'
            ], 404);
        }

        if (!$config->webhook_url) {
            return response()->json([
                'message' => 'URL webhook non configurato.'
            ], 422);
        }

        $result = $this->callTelegramApi(
            $config->getRawOriginal('bot_token'),
            'setWebhook',
            ['url' => $config->webhook_url]
        );

        if ($result && $result['ok']) {
            $config->update(['webhook_active' => true]);

            return response()->json([
                'message' => 'Webhook attivato con successo',
                'data' => ['webhook_active' => true]
            ]);
        }

        return response()->json([
            'message' => 'Errore nell\'attivazione del webhook: ' . ($result['description'] ?? 'Errore sconosciuto')
        ], 500);
    }

    /**
     * Deactivate webhook.
     */
    public function deactivateWebhook(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $config = TelegramConfig::where('company_id', $companyId)->first();

        if (!$config || !$config->getRawOriginal('bot_token')) {
            return response()->json([
                'message' => 'Configurazione bot non trovata.'
            ], 404);
        }

        $result = $this->callTelegramApi(
            $config->getRawOriginal('bot_token'),
            'deleteWebhook'
        );

        if ($result && $result['ok']) {
            $config->update(['webhook_active' => false]);

            return response()->json([
                'message' => 'Webhook disattivato con successo',
                'data' => ['webhook_active' => false]
            ]);
        }

        return response()->json([
            'message' => 'Errore nella disattivazione del webhook: ' . ($result['description'] ?? 'Errore sconosciuto')
        ], 500);
    }

    /**
     * Get webhook info from Telegram.
     */
    public function webhookInfo(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $config = TelegramConfig::where('company_id', $companyId)->first();

        if (!$config || !$config->getRawOriginal('bot_token')) {
            return response()->json([
                'data' => null,
                'message' => 'Configurazione bot non trovata.'
            ]);
        }

        $result = $this->callTelegramApi(
            $config->getRawOriginal('bot_token'),
            'getWebhookInfo'
        );

        if ($result && $result['ok']) {
            return response()->json([
                'data' => $result['result']
            ]);
        }

        return response()->json([
            'data' => null,
            'message' => 'Errore nel recupero info webhook'
        ], 500);
    }

    /**
     * Helper: get company_id based on user role.
     */
    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            return (int) $request->company_id;
        }

        return $user->company_id;
    }

    /**
     * Helper: call Telegram Bot API.
     */
    private function callTelegramApi(string $token, string $method, array $params = []): ?array
    {
        try {
            $url = "https://api.telegram.org/bot{$token}/{$method}";
            $response = Http::timeout(10)->post($url, $params);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Telegram API error [{$method}]: " . $e->getMessage());
            return null;
        }
    }
}
