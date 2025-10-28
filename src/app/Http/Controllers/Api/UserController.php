<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['company', 'driverProfile', 'clientProfile', 'intermediaryProfile', 'supplierProfile']);

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search on name, surname, email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('surname', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'role' => 'required|in:super-admin,admin,operator,driver,committente,intermediario,fornitore,passeggero',
            'name' => 'required|string',
            'surname' => 'required|string',
            'nickname' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = User::create($validated);

        return response()->json($user->load(['company', 'driverProfile', 'clientProfile', 'intermediaryProfile', 'supplierProfile']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $relations = ['company'];

        // Load role-specific profiles
        if ($user->isDriver()) {
            $relations[] = 'driverProfile';
        } elseif ($user->isClient()) {
            $relations[] = 'clientProfile';
        } elseif ($user->isIntermediary()) {
            $relations[] = 'intermediaryProfile';
        } elseif ($user->isSupplier()) {
            $relations[] = 'supplierProfile';
        }

        // Load services based on role
        if ($user->isDriver()) {
            $relations[] = 'driverServices';
        } elseif ($user->isClient()) {
            $relations[] = 'clientServices';
        } elseif ($user->isIntermediary()) {
            $relations[] = 'intermediaryServices';
        } elseif ($user->isSupplier()) {
            $relations[] = 'supplierServices';
        }

        return response()->json($user->load($relations));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'role' => 'sometimes|in:super-admin,admin,operator,driver,committente,intermediario,fornitore,passeggero',
            'name' => 'sometimes|string',
            'surname' => 'sometimes|string',
            'nickname' => 'nullable|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::defaults()],
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Remove null password from update
        if (isset($validated['password']) && is_null($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->load(['company', 'driverProfile', 'clientProfile', 'intermediaryProfile', 'supplierProfile']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
