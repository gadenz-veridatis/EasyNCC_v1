<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    /**
     * Global search across all entities.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->q;
        $companyId = $this->getCompanyId($request);
        $limit = 5;

        $results = [
            'services' => $this->searchServices($query, $companyId, $limit),
            'drivers' => $this->searchDrivers($query, $companyId, $limit),
            'vehicles' => $this->searchVehicles($query, $companyId, $limit),
            'clients' => $this->searchClients($query, $companyId, $limit),
            'suppliers' => $this->searchSuppliers($query, $companyId, $limit),
            'accounting' => $this->searchAccounting($query, $companyId, $limit),
        ];

        return response()->json($results);
    }

    private function searchServices(string $query, int $companyId, int $limit): array
    {
        return Service::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('reference_number', 'ILIKE', "%{$query}%")
                  ->orWhere('pickup_address', 'ILIKE', "%{$query}%")
                  ->orWhere('dropoff_address', 'ILIKE', "%{$query}%")
                  ->orWhereHas('passengers', function ($pq) use ($query) {
                      $pq->where('name', 'ILIKE', "%{$query}%")
                         ->orWhere('surname', 'ILIKE', "%{$query}%");
                  });
            })
            ->with(['status:id,name,color_code'])
            ->select('id', 'reference_number', 'pickup_address', 'dropoff_address', 'pickup_datetime', 'status_id', 'service_price')
            ->orderBy('pickup_datetime', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchDrivers(string $query, int $companyId, int $limit): array
    {
        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('role', 'driver')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('surname', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%")
                  ->orWhere('phone', 'ILIKE', "%{$query}%");
            })
            ->select('id', 'name', 'surname', 'email', 'phone')
            ->orderBy('surname')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchVehicles(string $query, int $companyId, int $limit): array
    {
        return Vehicle::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('license_plate', 'ILIKE', "%{$query}%")
                  ->orWhere('brand', 'ILIKE', "%{$query}%")
                  ->orWhere('model', 'ILIKE', "%{$query}%");
            })
            ->select('id', 'license_plate', 'brand', 'model')
            ->orderBy('license_plate')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchClients(string $query, int $companyId, int $limit): array
    {
        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('role', 'collaboratore')
            ->whereHas('clientProfile', function ($pq) {
                $pq->where('is_committente', true);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('surname', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%")
                  ->orWhereHas('clientProfile', function ($pq) use ($query) {
                      $pq->where('business_name', 'ILIKE', "%{$query}%");
                  });
            })
            ->with(['clientProfile:id,user_id,business_name'])
            ->select('id', 'name', 'surname', 'email')
            ->orderBy('surname')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchSuppliers(string $query, int $companyId, int $limit): array
    {
        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('role', 'collaboratore')
            ->whereHas('clientProfile', function ($pq) {
                $pq->where('is_fornitore', true);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('surname', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%")
                  ->orWhereHas('clientProfile', function ($pq) use ($query) {
                      $pq->where('business_name', 'ILIKE', "%{$query}%");
                  });
            })
            ->with(['clientProfile:id,user_id,business_name'])
            ->select('id', 'name', 'surname', 'email')
            ->orderBy('surname')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function searchAccounting(string $query, int $companyId, int $limit): array
    {
        return AccountingTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('document_number', 'ILIKE', "%{$query}%")
                  ->orWhere('transaction_type', 'ILIKE', "%{$query}%")
                  ->orWhereHas('service', function ($sq) use ($query) {
                      $sq->where('reference_number', 'ILIKE', "%{$query}%");
                  })
                  ->orWhereHas('counterpart', function ($cq) use ($query) {
                      $cq->where('name', 'ILIKE', "%{$query}%")
                         ->orWhere('surname', 'ILIKE', "%{$query}%");
                  });
            })
            ->with([
                'service:id,reference_number',
                'counterpart:id,name,surname',
            ])
            ->select('id', 'service_id', 'counterpart_id', 'transaction_type', 'amount', 'status', 'transaction_date')
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();

        if ($user->role === 'super-admin' && $request->filled('company_id')) {
            return (int) $request->company_id;
        }

        return $user->company_id;
    }
}
