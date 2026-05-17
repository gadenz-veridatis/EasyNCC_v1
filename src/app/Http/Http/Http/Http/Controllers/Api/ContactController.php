<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Contact::query();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $query->withoutGlobalScopes()->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_order', 'asc');
        $allowedSorts = ['id', 'name', 'email', 'phone', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = $request->get('per_page', 20);
        $contacts = $query->withCount('activeQuotes as quotes_count')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $contacts->items(),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $user = Auth::user();
        $query = Contact::query();

        if ($user->role === 'super-admin' && $request->has('company_id')) {
            $query->withoutGlobalScopes()->where('company_id', $request->company_id);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json(['data' => $contacts]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = ($user->role === 'super-admin' && $request->has('company_id'))
            ? $request->company_id
            : $user->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('contacts')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId)->whereNull('deleted_at');
                }),
            ],
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $contact = Contact::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Contatto creato con successo',
            'data' => $contact,
        ], 201);
    }

    public function show(Contact $contact)
    {
        return response()->json([
            'data' => $contact->loadCount('activeQuotes as quotes_count'),
        ]);
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('contacts')->where(function ($q) use ($contact) {
                    return $q->where('company_id', $contact->company_id)->whereNull('deleted_at');
                })->ignore($contact->id),
            ],
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contatto aggiornato con successo',
            'data' => $contact,
        ]);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contatto eliminato con successo',
        ]);
    }
}
