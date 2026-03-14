<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GmailAccount;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GmailAccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $accounts = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('account_label')
            ->get()
            ->map(fn($a) => $this->formatAccount($a));

        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_label' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
            'client_id' => 'required|string|max:500',
            'client_secret' => 'required|string|max:500',
            'refresh_token' => 'required|string|max:2000',
            'access_token' => 'nullable|string|max:2000',
            'token_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $companyId = $this->getCompanyId($request);

        $account = GmailAccount::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail creato con successo',
            'data' => $this->formatAccount($account),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'account_label' => 'sometimes|required|string|max:255',
            'email_address' => 'sometimes|required|email|max:255',
            'client_id' => 'sometimes|required|string|max:500',
            'client_secret' => 'sometimes|required|string|max:500',
            'refresh_token' => 'sometimes|required|string|max:2000',
            'access_token' => 'nullable|string|max:2000',
            'token_expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail aggiornato con successo',
            'data' => $this->formatAccount($account->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account Gmail eliminato con successo',
        ]);
    }

    public function testConnection(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $account = GmailAccount::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        try {
            $gmailService = new GmailService($account);
            $gmailService->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'Connessione riuscita! Token aggiornato.',
                'data' => $this->formatAccount($account->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connessione fallita: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function formatAccount(GmailAccount $account): array
    {
        return [
            'id' => $account->id,
            'company_id' => $account->company_id,
            'account_label' => $account->account_label,
            'email_address' => $account->email_address,
            'client_id' => $account->getRawOriginal('client_id'),
            'client_secret' => $account->getRawOriginal('client_secret'),
            'refresh_token' => $account->getRawOriginal('refresh_token'),
            'access_token' => $account->getRawOriginal('access_token'),
            'token_expires_at' => $account->token_expires_at,
            'is_active' => $account->is_active,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }

    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            return (int) $request->company_id;
        }
        return $user->company_id;
    }
}
