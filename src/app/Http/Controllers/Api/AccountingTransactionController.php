<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountingTransactionController extends Controller
{
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
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
            'status' => 'required|in:to_pay,paid,suspended,cancelled,to_collect,collected',
            'notes' => 'nullable|string',
        ]);

        // Set company_id based on user role
        if ($request->user()->isSuperAdmin() && $request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        } else {
            $validated['company_id'] = $request->user()->company_id;
        }

        $transaction = AccountingTransaction::create($validated);

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
            'status' => 'sometimes|required|in:to_pay,paid,suspended,cancelled,to_collect,collected',
            'notes' => 'nullable|string',
        ]);

        $accountingTransaction->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $accountingTransaction->load(['service', 'accountingEntry', 'counterpart', 'company']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccountingTransaction $accountingTransaction): JsonResponse
    {
        $accountingTransaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ], 200);
    }
}
