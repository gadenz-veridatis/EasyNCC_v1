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
        // For list view, load company, clientProfile for collaboratore users, and driverProfile for drivers
        $relationships = [
            'company:id,name',
            'clientProfile:user_id,is_committente,is_fornitore',
            'driverProfile:user_id,color,fiscal_code,vat_number,allow_overlapping',
        ];

        // Only load driverAttachments when filtering for drivers
        if ($request->get('role') === 'driver') {
            $relationships[] = 'driverAttachments';
        }

        $query = User::with($relationships);

        // Multi-tenancy: Filter by company
        // Super-admin can see all companies or filter by company_id
        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            // If no company_id specified, show all users
        } else {
            // Other users see only their company's users
            $query->where('company_id', $request->user()->company_id);
        }

        // Filter by role (only if not empty)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by active status (only if not empty)
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by is_intermediario (only if not empty)
        if ($request->filled('is_intermediario')) {
            $query->where('is_intermediario', $request->boolean('is_intermediario'));
        }

        // Filter by is_committente (for collaboratore role)
        if ($request->filled('is_committente')) {
            $query->whereHas('clientProfile', function($q) use ($request) {
                $q->where('is_committente', $request->boolean('is_committente'));
            });
        }

        // Filter by is_fornitore (for collaboratore role)
        if ($request->filled('is_fornitore')) {
            $query->whereHas('clientProfile', function($q) use ($request) {
                $q->where('is_fornitore', $request->boolean('is_fornitore'));
            });
        }

        // Search on name, surname, email, username (only if not empty)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('surname', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%");
            });
        }

        // Filter by expiring documents (for drivers)
        if ($request->filled('expiring') && $request->role === 'driver') {
            $days = (int) $request->expiring;
            if ($days > 0) {
                $query->whereHas('driverAttachments', function($q) use ($days) {
                    $q->whereNotNull('expiration_date')
                      ->where('expiration_date', '<=', now()->addDays($days))
                      ->where('expiration_date', '>=', now());
                });
            }
        }

        // Sorting - support both sort_order and sort_direction for compatibility
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_direction', $request->get('sort_order', 'desc'));
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
        // Convert boolean fields before validation
        $request->merge([
            'is_active' => $request->boolean('is_active', true),
            'is_intermediario' => $request->boolean('is_intermediario', false),
        ]);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'role' => 'required|in:super-admin,admin,operator,driver,collaboratore,contabilita',
            'name' => 'nullable|string',
            'surname' => 'required|string',
            'nickname' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => ['required', Password::defaults()],
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'required|boolean',
            'is_intermediario' => 'required|boolean',
            'percentuale_commissione' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'user_photo' => 'nullable|image|max:2048',
            'profile' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
        ]);

        // Handle user photo upload
        if ($request->hasFile('user_photo')) {
            $validated['user_photo'] = $request->file('user_photo')->store('user_photos', 'public');
        }

        // Extract profile data and logo
        $profileData = $validated['profile'] ?? null;
        $logoFile = $request->file('logo');
        unset($validated['profile'], $validated['logo']);

        // Set audit fields
        $currentUser = $request->user();
        if (!$currentUser) {
            \Log::error('UserController@store: No authenticated user found when creating user');
            throw new \Exception('Authentication required to create user');
        }
        $validated['created_by'] = $currentUser->id;
        $validated['updated_by'] = $currentUser->id;

        $user = User::create($validated);

        // Handle logo upload for collaboratore profile
        if ($logoFile && $user->role === 'collaboratore') {
            $logoPath = $logoFile->store('logos', 'public');
            if (!$profileData) {
                $profileData = [];
            }
            $profileData['logo'] = $logoPath;
        }

        // Create role-specific profile if data provided
        if ($profileData && $this->hasProfile($user->role)) {
            $this->createOrUpdateProfile($user, $profileData);
        }

        return response()->json($user->load(['company', 'driverProfile', 'clientProfile', 'operatorProfile', 'creator', 'updater']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $relations = ['company', 'creator', 'updater'];

        // Load role-specific profiles
        if ($user->isDriver()) {
            $relations[] = 'driverProfile.assignedVehicle';
            $relations[] = 'driverAttachments';
        } elseif ($user->isClient()) {
            $relations[] = 'clientProfile.businessContacts';
        } elseif ($user->isOperator()) {
            $relations[] = 'operatorProfile';
        }

        // Load services based on role
        if ($user->isDriver()) {
            $relations[] = 'driverServices';
        } elseif ($user->isClient()) {
            $relations[] = 'clientServices';
        }

        return response()->json($user->load($relations));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Convert boolean fields before validation if present
        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        }
        if ($request->has('is_intermediario')) {
            $request->merge(['is_intermediario' => $request->boolean('is_intermediario')]);
        }

        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'role' => 'sometimes|in:super-admin,admin,operator,driver,collaboratore,contabilita',
            'name' => 'nullable|string',
            'surname' => 'sometimes|required|string',
            'nickname' => 'nullable|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|unique:users,username,' . $user->id,
            'password' => ['nullable', Password::defaults()],
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_intermediario' => 'sometimes|boolean',
            'percentuale_commissione' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'user_photo' => 'nullable|image|max:2048',
            'profile' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
        ]);

        // Handle user photo upload
        if ($request->hasFile('user_photo')) {
            // Delete old photo if exists
            if ($user->user_photo) {
                \Storage::disk('public')->delete($user->user_photo);
            }
            $validated['user_photo'] = $request->file('user_photo')->store('user_photos', 'public');
        }

        // Extract profile data and logo
        $profileData = $validated['profile'] ?? null;
        $logoFile = $request->file('logo');
        unset($validated['profile'], $validated['logo']);

        // Remove null password from update
        if (isset($validated['password']) && is_null($validated['password'])) {
            unset($validated['password']);
        }

        // Set audit field
        $currentUser = $request->user();
        if (!$currentUser) {
            \Log::error('UserController@update: No authenticated user found when updating user');
            throw new \Exception('Authentication required to update user');
        }

        \Log::info('UserController@update: Setting updated_by', [
            'current_user_id' => $currentUser->id,
            'current_user_name' => $currentUser->name . ' ' . $currentUser->surname,
            'updating_user_id' => $user->id,
            'updating_user_name' => $user->name . ' ' . $user->surname
        ]);

        $validated['updated_by'] = $currentUser->id;

        // Update user data
        $user->update($validated);

        // Force update the timestamp even if no fields changed
        $user->touch();

        // Refresh the user model to get the latest data
        $user->refresh();

        \Log::info('UserController@update: After update', [
            'user_id' => $user->id,
            'updated_by_in_db' => $user->updated_by,
            'updated_at' => $user->updated_at
        ]);

        // Handle logo upload for collaboratore profile
        if ($logoFile && $user->role === 'collaboratore') {
            // Delete old logo if exists
            if ($user->clientProfile && $user->clientProfile->logo) {
                \Storage::disk('public')->delete($user->clientProfile->logo);
            }
            $logoPath = $logoFile->store('logos', 'public');
            if (!$profileData) {
                $profileData = [];
            }
            $profileData['logo'] = $logoPath;
        }

        // Update role-specific profile if data provided
        if ($profileData && $this->hasProfile($user->role)) {
            $this->createOrUpdateProfile($user, $profileData);
        }

        return response()->json($user->load(['company', 'driverProfile', 'clientProfile', 'operatorProfile', 'creator', 'updater']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * Check if role has a profile
     */
    private function hasProfile(string $role): bool
    {
        return in_array($role, ['driver', 'collaboratore', 'operator']);
    }

    /**
     * Create or update user profile based on role
     */
    private function createOrUpdateProfile(User $user, array $profileData): void
    {
        switch ($user->role) {
            case 'driver':
                $user->driverProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter($profileData, fn($value) => $value !== null)
                );
                break;

            case 'collaboratore':
                // Extract business_contacts from profile data
                $businessContacts = $profileData['business_contacts'] ?? [];
                unset($profileData['business_contacts']);

                // Update or create client profile
                $profile = $user->clientProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter($profileData, fn($value) => $value !== null)
                );

                // Sync business contacts
                if (!empty($businessContacts)) {
                    // Delete existing contacts
                    $profile->businessContacts()->delete();

                    // Create new contacts
                    foreach ($businessContacts as $contact) {
                        if (!empty($contact['name']) || !empty($contact['phone']) || !empty($contact['email'])) {
                            $profile->businessContacts()->create($contact);
                        }
                    }
                }
                break;

            case 'operator':
                $user->operatorProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter($profileData, fn($value) => $value !== null)
                );
                break;
        }
    }
}
