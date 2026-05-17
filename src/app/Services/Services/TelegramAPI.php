<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramAPI
{
    private string $token;
    private string $baseUrl;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    /**
     * Send a text message to a chat.
     */
    public function sendMessage(int $chatId, string $text, ?array $keyboard = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->call('sendMessage', $params);
    }

    /**
     * Send a document (file) to a chat.
     */
    public function sendDocument(int $chatId, string $filePath, ?string $caption = null, ?array $keyboard = null): ?array
    {
        try {
            $params = [
                'chat_id' => $chatId,
            ];

            if ($caption) {
                $params['caption'] = $caption;
                $params['parse_mode'] = 'HTML';
            }

            if ($keyboard) {
                $params['reply_markup'] = json_encode($keyboard);
            }

            $request = Http::timeout(30)
                ->attach('document', file_get_contents($filePath), basename($filePath));

            foreach ($params as $key => $value) {
                $request = $request->attach($key, $value);
            }

            // Use multipart for file uploads
            $response = Http::timeout(30)
                ->asMultipart()
                ->post("{$this->baseUrl}/sendDocument", [
                    ['name' => 'chat_id', 'contents' => (string) $chatId],
                    ['name' => 'document', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
                    ...($caption ? [['name' => 'caption', 'contents' => $caption], ['name' => 'parse_mode', 'contents' => 'HTML']] : []),
                    ...($keyboard ? [['name' => 'reply_markup', 'contents' => json_encode($keyboard)]] : []),
                ]);

            $result = $response->json();

            if (!$result || !($result['ok'] ?? false)) {
                Log::error('Telegram sendDocument failed', [
                    'chat_id' => $chatId,
                    'error' => $result['description'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Telegram sendDocument exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Answer a callback query (button press acknowledgment).
     */
    public function answerCallbackQuery(string $callbackId, ?string $text = null): ?array
    {
        $params = [
            'callback_query_id' => $callbackId,
        ];

        if ($text) {
            $params['text'] = $text;
            $params['show_alert'] = true;
        }

        return $this->call('answerCallbackQuery', $params);
    }

    /**
     * Edit the caption of an existing message.
     */
    public function editMessageCaption(int $chatId, int $messageId, string $caption): ?array
    {
        return $this->call('editMessageCaption', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Edit the reply markup (keyboard) of an existing message.
     */
    public function editMessageReplyMarkup(int $chatId, int $messageId, ?array $keyboard = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        } else {
            // Remove keyboard
            $params['reply_markup'] = json_encode(['inline_keyboard' => []]);
        }

        return $this->call('editMessageReplyMarkup', $params);
    }

    /**
     * Set the webhook URL.
     */
    public function setWebhook(string $url): ?array
    {
        return $this->call('setWebhook', ['url' => $url]);
    }

    /**
     * Delete the webhook.
     */
    public function deleteWebhook(): ?array
    {
        return $this->call('deleteWebhook');
    }

    /**
     * Get webhook info.
     */
    public function getWebhookInfo(): ?array
    {
        return $this->call('getWebhookInfo');
    }

    /**
     * Get bot info.
     */
    public function getMe(): ?array
    {
        return $this->call('getMe');
    }

    /**
     * Generic API call.
     */
    private function call(string $method, array $params = []): ?array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/{$method}", $params);
            $result = $response->json();

            if (!$result || !($result['ok'] ?? false)) {
                Log::channel('telegram')->error("Telegram API [{$method}] failed", [
                    'params' => $this->sanitizeParams($params),
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('telegram')->error("Telegram API [{$method}] exception", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sanitize params for logging (hide sensitive data).
     */
    private function sanitizeParams(array $params): array
    {
        $safe = $params;
        unset($safe['document']);
        return $safe;
    }
}
