<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramNotificationController extends Controller
{
    /**
     * Get recent notifications for the current user's company.
     * Returns the latest 20 notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $notifications = TelegramNotification::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['telegramUser:id,first_name,last_name,username'])
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['data' => $notifications]);
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $count = TelegramNotification::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark all notifications as read for the current company.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        TelegramNotification::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Tutte le notifiche segnate come lette']);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $notification = TelegramNotification::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notifica segnata come letta']);
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
