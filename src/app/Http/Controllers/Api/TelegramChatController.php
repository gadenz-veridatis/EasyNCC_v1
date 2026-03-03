<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramConfig;
use App\Models\TelegramMessage;
use App\Models\TelegramNotification;
use App\Models\TelegramUser;
use App\Services\TelegramAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TelegramChatController extends Controller
{
    /**
     * Get conversations list (one per telegram user, with last message and unread count).
     */
    public function conversations(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $telegramUsers = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['driver:id,name,surname'])
            ->get();

        $conversations = [];

        foreach ($telegramUsers as $tgUser) {
            // Last message
            $lastMessage = TelegramMessage::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('telegram_user_id', $tgUser->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Unread inbound count
            $unreadCount = TelegramMessage::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('telegram_user_id', $tgUser->id)
                ->where('direction', 'inbound')
                ->where('is_read', false)
                ->count();

            $conversations[] = [
                'id' => $tgUser->id,
                'telegram_chat_id' => $tgUser->telegram_chat_id,
                'first_name' => $tgUser->first_name,
                'last_name' => $tgUser->last_name,
                'username' => $tgUser->username,
                'driver' => $tgUser->driver,
                'user_id' => $tgUser->user_id,
                'last_message' => $lastMessage ? [
                    'content' => $lastMessage->content,
                    'direction' => $lastMessage->direction,
                    'message_type' => $lastMessage->message_type,
                    'created_at' => $lastMessage->created_at,
                ] : null,
                'unread_count' => $unreadCount,
            ];
        }

        // Sort by last message date (most recent first)
        usort($conversations, function ($a, $b) {
            $aTime = $a['last_message']['created_at'] ?? null;
            $bTime = $b['last_message']['created_at'] ?? null;
            if (!$aTime && !$bTime) return 0;
            if (!$aTime) return 1;
            if (!$bTime) return -1;
            return $bTime <=> $aTime;
        });

        return response()->json(['data' => $conversations]);
    }

    /**
     * Get messages for a conversation.
     * Supports ?after_id=X to fetch only newer messages (for polling).
     */
    public function messages(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $request->validate([
            'telegram_user_id' => 'required|integer',
        ]);

        $query = TelegramMessage::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('telegram_user_id', $request->telegram_user_id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        // If after_id is provided, only return messages newer than that ID
        if ($request->filled('after_id')) {
            $query->where('id', '>', $request->after_id);
        }

        $messages = $query->get();

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message to a Telegram user.
     */
    public function send(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'telegram_user_id' => 'required|integer',
            'content' => 'required|string|max:4096',
        ]);

        $telegramUser = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($validated['telegram_user_id']);

        // Get bot config
        $config = TelegramConfig::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        if (!$config || !$config->getRawOriginal('bot_token')) {
            return response()->json([
                'message' => 'Bot Telegram non configurato per questa azienda'
            ], 422);
        }

        $api = new TelegramAPI($config->getRawOriginal('bot_token'));
        $result = $api->sendMessage($telegramUser->telegram_chat_id, $validated['content']);

        if (!$result || !($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Errore nell\'invio del messaggio: ' . ($result['description'] ?? 'errore sconosciuto')
            ], 500);
        }

        // Save outbound message
        $message = TelegramMessage::create([
            'company_id' => $companyId,
            'telegram_user_id' => $telegramUser->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $validated['content'],
            'telegram_message_id' => $result['result']['message_id'] ?? null,
            'is_read' => true,
        ]);

        return response()->json([
            'message' => 'Messaggio inviato',
            'data' => $message,
        ]);
    }

    /**
     * Mark all inbound messages as read for a conversation.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $request->validate([
            'telegram_user_id' => 'required|integer',
        ]);

        $now = now();

        // Mark messages as read
        TelegramMessage::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('telegram_user_id', $request->telegram_user_id)
            ->where('direction', 'inbound')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => $now,
            ]);

        // Mark related notifications as read
        TelegramNotification::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('telegram_user_id', $request->telegram_user_id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => $now,
            ]);

        return response()->json(['message' => 'Messaggi segnati come letti']);
    }

    /**
     * Get company_id based on user role.
     */
    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->filled('company_id')) {
            return (int) $request->company_id;
        }

        return $user->company_id;
    }
}
