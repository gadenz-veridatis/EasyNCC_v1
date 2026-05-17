<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramUserController extends Controller
{
    /**
     * List Telegram users for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->filled('company_id'))
            ? $request->company_id
            : $user->company_id;

        $query = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['driver:id,name,surname,email,role']);

        // Filter: associated / not_associated
        if ($request->filled('filter')) {
            if ($request->filter === 'associated') {
                $query->whereNotNull('user_id');
            } elseif ($request->filter === 'not_associated') {
                $query->whereNull('user_id');
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%");
            });
        }

        $telegramUsers = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $telegramUsers]);
    }

    /**
     * Associate a Telegram user with a system driver.
     */
    public function associate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->filled('company_id'))
            ? $request->company_id
            : $user->company_id;

        $telegramUser = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $telegramUser->update(['user_id' => $validated['user_id']]);
        $telegramUser->load('driver:id,name,surname,email,role');

        return response()->json([
            'message' => $validated['user_id']
                ? 'Driver associato con successo'
                : 'Associazione rimossa',
            'data' => $telegramUser,
        ]);
    }

    /**
     * Get available drivers (not yet associated to any Telegram user).
     */
    public function availableDrivers(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->filled('company_id'))
            ? $request->company_id
            : $user->company_id;

        // Get IDs of drivers already associated
        $associatedDriverIds = TelegramUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        // Get all drivers of this company not yet associated
        $drivers = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('role', 'driver')
            ->where('is_active', true)
            ->whereNotIn('id', $associatedDriverIds)
            ->select('id', 'name', 'surname', 'email')
            ->orderBy('surname')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $drivers]);
    }
}
