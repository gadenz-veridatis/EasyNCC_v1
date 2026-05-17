<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SumupConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SumupConfigController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $configs = SumupConfig::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('merchant_name')
            ->get()
            ->map(fn($c) => $this->formatConfig($c));

        return response()->json(['data' => $configs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_name' => 'required|string|max:255',
            'api_key' => 'required|string|max:500',
            'merchant_code' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $companyId = $this->getCompanyId($request);

        $config = SumupConfig::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Configurazione SumUp creata con successo',
            'data' => $this->formatConfig($config),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $config = SumupConfig::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'merchant_name' => 'sometimes|required|string|max:255',
            'api_key' => 'sometimes|required|string|max:500',
            'merchant_code' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $config->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Configurazione SumUp aggiornata con successo',
            'data' => $this->formatConfig($config->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $config = SumupConfig::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configurazione SumUp eliminata con successo',
        ]);
    }

    private function formatConfig(SumupConfig $config): array
    {
        return [
            'id' => $config->id,
            'company_id' => $config->company_id,
            'merchant_name' => $config->merchant_name,
            'api_key' => $config->getRawOriginal('api_key'),
            'merchant_code' => $config->merchant_code,
            'is_active' => $config->is_active,
            'created_at' => $config->created_at,
            'updated_at' => $config->updated_at,
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
