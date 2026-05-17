<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Quote;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    protected array $typeMap = [
        'services' => Service::class,
        'users' => User::class,
        'vehicles' => Vehicle::class,
        'quotes' => Quote::class,
        'contacts' => Contact::class,
    ];

    protected function getQuery(string $type, Request $request)
    {
        $modelClass = $this->typeMap[$type] ?? null;
        if (!$modelClass) {
            abort(404, 'Tipo non valido');
        }

        $query = $modelClass::withoutGlobalScopes()->onlyTrashed();

        // Multi-tenancy
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query;
    }

    /**
     * List trashed records for a given type.
     */
    public function index(Request $request, string $type): JsonResponse
    {
        $query = $this->getQuery($type, $request);

        // Add type-specific eager loading
        switch ($type) {
            case 'services':
                $query->with([
                    'vehicle:id,license_plate,brand,model',
                    'passengers:id,service_id,name,surname',
                    'status:id,name',
                ]);
                break;
            case 'users':
                $query->select('id', 'name', 'surname', 'role', 'email', 'company_id', 'deleted_at');
                break;
            case 'vehicles':
                $query->select('id', 'license_plate', 'brand', 'model', 'company_id', 'deleted_at');
                break;
            case 'quotes':
                $query->select('id', 'client_name', 'client_email', 'service_date', 'final_price_rounded', 'status', 'version', 'company_id', 'deleted_at');
                break;
            case 'contacts':
                $query->select('id', 'name', 'email', 'phone', 'company_id', 'deleted_at');
                break;
        }

        $items = $query->orderBy('deleted_at', 'desc')->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Count trashed records per type.
     */
    public function counts(Request $request): JsonResponse
    {
        $counts = [];
        foreach (array_keys($this->typeMap) as $type) {
            $counts[$type] = $this->getQuery($type, $request)->count();
        }
        return response()->json($counts);
    }

    /**
     * Restore a trashed record.
     */
    public function restore(Request $request, string $type, int $id): JsonResponse
    {
        $modelClass = $this->typeMap[$type] ?? null;
        if (!$modelClass) {
            abort(404, 'Tipo non valido');
        }

        $record = $modelClass::withoutGlobalScopes()->onlyTrashed()->findOrFail($id);

        // Verify company access
        if (!$request->user()->isSuperAdmin() && $record->company_id !== $request->user()->company_id) {
            abort(403, 'Non autorizzato');
        }

        $record->restore();

        // Cascade restore for services
        if ($type === 'services') {
            $record->activities()->onlyTrashed()->restore();
            $record->tasks()->onlyTrashed()->restore();
            $record->accountingTransactions()->onlyTrashed()->restore();
            $record->attachments()->onlyTrashed()->restore();
            $record->passengers()->onlyTrashed()->restore();
        }

        return response()->json(['success' => true, 'message' => 'Record ripristinato con successo']);
    }

    /**
     * Permanently delete a trashed record.
     */
    public function forceDelete(Request $request, string $type, int $id): JsonResponse
    {
        $modelClass = $this->typeMap[$type] ?? null;
        if (!$modelClass) {
            abort(404, 'Tipo non valido');
        }

        $record = $modelClass::withoutGlobalScopes()->onlyTrashed()->findOrFail($id);

        // Verify company access
        if (!$request->user()->isSuperAdmin() && $record->company_id !== $request->user()->company_id) {
            abort(403, 'Non autorizzato');
        }

        // Cascade force-delete for services
        if ($type === 'services') {
            // Delete attachment files from filesystem
            foreach ($record->attachments()->withTrashed()->get() as $att) {
                if ($att->file_path && Storage::disk('private')->exists($att->file_path)) {
                    Storage::disk('private')->delete($att->file_path);
                }
            }

            $record->activities()->withTrashed()->forceDelete();
            $record->tasks()->withTrashed()->forceDelete();
            $record->accountingTransactions()->withTrashed()->forceDelete();
            $record->attachments()->withTrashed()->forceDelete();
            $record->passengers()->withTrashed()->forceDelete();
        }

        $record->forceDelete();

        return response()->json(['success' => true, 'message' => 'Record eliminato definitivamente']);
    }
}
