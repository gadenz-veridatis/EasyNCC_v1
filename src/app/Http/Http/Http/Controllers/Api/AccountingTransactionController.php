<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use App\Models\Service;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AccountingTransactionController extends Controller
{
    /**
     * Apply common filters to the query based on request parameters.
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        // Multi-tenancy: Filter by company
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by counterpart
        if ($request->filled('counterpart_id')) {
            $query->where('counterpart_id', $request->counterpart_id);
        }

        // Filter by accounting entry
        if ($request->filled('accounting_entry_id')) {
            $query->where('accounting_entry_id', $request->accounting_entry_id);
        }

        // Search on document number, payment_reason, and notes
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'ilike', "%{$search}%")
                  ->orWhere('payment_reason', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        return $query;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccountingTransaction::with([
            'service:id,reference_number',
            'accountingEntry:id,name,abbreviation',
            'counterpart:id,name,surname,username,email',
            'company:id,name'
        ]);

        $this->applyFilters($query, $request);

        // Sorting
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get aggregated summary totals (SQL-level aggregation, no record loading).
     */
    public function summary(Request $request): JsonResponse
    {
        $query = AccountingTransaction::query();
        $this->applyFilters($query, $request);

        $results = $query->select(
            'transaction_type',
            'installment',
            DB::raw('SUM(amount) as total_amount')
        )
        ->groupBy('transaction_type', 'installment')
        ->get();

        $sales = 0;
        $purchases = 0;
        $intermediations = 0;
        $supplierRefunds = 0;
        $customerRefunds = 0;

        foreach ($results as $row) {
            $amount = (float) $row->total_amount;

            if ($row->transaction_type === 'sale') {
                if ($row->installment === 'customer_refund') {
                    $customerRefunds += $amount;
                } else {
                    $sales += $amount;
                }
            } elseif ($row->transaction_type === 'purchase') {
                if ($row->installment === 'supplier_refund') {
                    $supplierRefunds += $amount;
                } else {
                    $purchases += $amount;
                }
            } elseif ($row->transaction_type === 'intermediation') {
                $intermediations += $amount;
            }
        }

        $total = $sales + $supplierRefunds - $purchases - $intermediations - $customerRefunds;

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => round($sales, 2),
                'purchases' => round($purchases, 2),
                'intermediations' => round($intermediations, 2),
                'supplierRefunds' => round($supplierRefunds, 2),
                'customerRefunds' => round($customerRefunds, 2),
                'total' => round($total, 2),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Get valid status codes for the user's company
        $companyId = $request->user()->isSuperAdmin() && $request->filled('company_id')
            ? $request->company_id
            : $request->user()->company_id;
        $validStatuses = TransactionStatus::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('code')
            ->toArray();

        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:purchase,sale,intermediation',
            'accounting_entry_id' => 'nullable|exists:accounting_entries,id',
            'installment' => 'required|in:deposit,balance,supplier_refund,customer_refund',
            'counterpart_id' => 'nullable|exists:users,id',
            'document_number' => 'nullable|string|max:255',
            'document_due_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'payment_type' => 'nullable|string|max:255',
            'payment_reason' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'status' => 'required|string|in:' . implode(',', $validStatuses),
            'notes' => 'nullable|string',
            'is_automatic' => 'sometimes|boolean',
        ]);

        // Set company_id based on user role
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        } else {
            $validated['company_id'] = $request->user()->company_id;
        }

        $transaction = AccountingTransaction::create($validated);

        // Refresh status map
        if ($transaction->service_id) {
            $transaction->service->refreshTransactionStatusMap();
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['service', 'accountingEntry', 'counterpart', 'company']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AccountingTransaction $accountingTransaction): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $accountingTransaction->load(['service', 'accountingEntry', 'counterpart', 'company']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccountingTransaction $accountingTransaction): JsonResponse
    {
        // Get valid status codes for the transaction's company
        $companyId = $accountingTransaction->company_id;
        $validStatuses = TransactionStatus::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('code')
            ->toArray();

        $validated = $request->validate([
            'service_id' => 'sometimes|nullable|exists:services,id',
            'transaction_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
            'transaction_type' => 'sometimes|required|in:purchase,sale,intermediation',
            'accounting_entry_id' => 'sometimes|nullable|exists:accounting_entries,id',
            'installment' => 'sometimes|required|in:deposit,balance,supplier_refund,customer_refund',
            'counterpart_id' => 'sometimes|nullable|exists:users,id',
            'document_number' => 'nullable|string|max:255',
            'document_due_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'payment_type' => 'nullable|string|max:255',
            'payment_reason' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'status' => 'sometimes|required|string|in:' . implode(',', $validStatuses),
            'notes' => 'nullable|string',
            'is_automatic' => 'sometimes|boolean',
        ]);

        $accountingTransaction->update($validated);

        // Refresh status map if status changed
        if (isset($validated['status']) && $accountingTransaction->service_id) {
            $accountingTransaction->service->refreshTransactionStatusMap();
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $accountingTransaction->load(['service', 'accountingEntry', 'counterpart', 'company']),
        ]);
    }

    /**
     * Lightweight endpoint for services dropdown (only id + reference_number).
     */
    public function servicesForDropdown(Request $request): JsonResponse
    {
        $query = Service::select('id', 'reference_number', 'company_id');

        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        $services = $query->orderBy('reference_number', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * Lightweight endpoint for counterparts dropdown (only id, name, surname, role).
     */
    public function counterpartsForDropdown(Request $request): JsonResponse
    {
        $query = User::select('id', 'name', 'surname', 'username', 'email', 'role', 'is_intermediario', 'company_id')
            ->with(['clientProfile:user_id,is_committente,is_fornitore']);

        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        // Only return users that can be counterparts (have intermediario, committente, or fornitore flags)
        $query->where(function ($q) {
            $q->where('is_intermediario', true)
              ->orWhereHas('clientProfile', function ($sub) {
                  $sub->where('is_committente', true)
                      ->orWhere('is_fornitore', true);
              });
        });

        $users = $query->orderBy('surname')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Batch upsert/delete accounting transactions for a service.
     * Replaces multiple sequential API calls with a single atomic operation.
     */
    public function batchUpsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'operations' => 'required|array|min:1',
            'operations.*.action' => 'required|in:upsert,delete',
            'operations.*.find_by' => 'required|array',
            'operations.*.find_by.transaction_type' => 'required|string',
            'operations.*.find_by.installment' => 'required|string',
            'operations.*.find_by.accounting_entry_id' => 'nullable|integer',
            'operations.*.data' => 'required_if:operations.*.action,upsert|array',
        ]);

        $serviceId = $validated['service_id'];

        // Determine company_id
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $companyId = $request->company_id;
        } else {
            $companyId = $request->user()->company_id;
        }

        // Load final status codes for this company to skip updates on finalized transactions
        $finalStatusCodes = TransactionStatus::where('company_id', $companyId)
            ->where('is_final', true)
            ->pluck('code')
            ->toArray();

        DB::transaction(function () use ($validated, $serviceId, $companyId, $finalStatusCodes) {
            foreach ($validated['operations'] as $operation) {
                $findBy = $operation['find_by'];

                // Build query to find existing transaction
                $query = AccountingTransaction::where('service_id', $serviceId)
                    ->where('transaction_type', $findBy['transaction_type'])
                    ->where('installment', $findBy['installment']);

                if (!empty($findBy['accounting_entry_id'])) {
                    $query->where('accounting_entry_id', $findBy['accounting_entry_id']);
                }

                $existing = $query->first();

                if ($operation['action'] === 'upsert') {
                    $data = $operation['data'];
                    if ($existing) {
                        // Skip update if the existing transaction has a final status
                        if (in_array($existing->status, $finalStatusCodes)) {
                            continue;
                        }
                        // Update only restricted fields for automatic transactions
                        $existing->update([
                            'amount' => $data['amount'] ?? $existing->amount,
                            'counterpart_id' => $data['counterpart_id'] ?? $existing->counterpart_id,
                            'transaction_type' => $data['transaction_type'] ?? $existing->transaction_type,
                            'accounting_entry_id' => $data['accounting_entry_id'] ?? $existing->accounting_entry_id,
                            'transaction_date' => $data['transaction_date'] ?? $existing->transaction_date,
                        ]);
                    } else {
                        // Create new automatic transaction
                        AccountingTransaction::create(array_merge($data, [
                            'service_id' => $serviceId,
                            'company_id' => $companyId,
                            'is_automatic' => true,
                        ]));
                    }
                } elseif ($operation['action'] === 'delete') {
                    if ($existing) {
                        // Skip delete if the existing transaction has a final status
                        if (in_array($existing->status, $finalStatusCodes)) {
                            continue;
                        }
                        $existing->delete();
                    }
                }
            }
        });

        // Refresh the denormalized status map on the service
        $service = Service::find($serviceId);
        if ($service) {
            $service->refreshTransactionStatusMap();
        }

        // Return updated list of transactions for this service
        $transactions = AccountingTransaction::where('service_id', $serviceId)
            ->with(['accountingEntry:id,name,abbreviation', 'counterpart:id,name,surname'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccountingTransaction $accountingTransaction): JsonResponse
    {
        $serviceId = $accountingTransaction->service_id;
        $accountingTransaction->delete();

        // Refresh status map
        if ($serviceId) {
            Service::find($serviceId)?->refreshTransactionStatusMap();
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ], 200);
    }
}
