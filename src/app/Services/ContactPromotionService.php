<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactPromotionService
{
    /**
     * Promote a contact to a user (committente role).
     * If a user with the same email already exists, links them.
     * Otherwise creates a new user with role collaboratore.
     *
     * @return User The linked or created user
     */
    public function promote(Contact $contact): User
    {
        // 1. Check if user already exists with this email
        $existingUser = User::withoutGlobalScopes()
            ->where('company_id', $contact->company_id)
            ->where('email', $contact->email)
            ->first();

        if ($existingUser) {
            // Link contact to existing user
            if (!$contact->user_id) {
                $contact->update(['user_id' => $existingUser->id]);
            }

            Log::info("Contact {$contact->id} linked to existing user {$existingUser->id}");
            return $existingUser;
        }

        // 2. Create new user with collaboratore role
        $nameParts = $this->splitName($contact->name);

        $user = User::create([
            'company_id' => $contact->company_id,
            'name' => $nameParts['name'],
            'surname' => $nameParts['surname'],
            'email' => $contact->email,
            'phone' => $contact->phone,
            'username' => $this->generateUsername($contact),
            'password' => Hash::make(Str::random(32)),
            'role' => 'collaboratore',
            'is_active' => true,
        ]);

        // Link contact to new user
        $contact->update(['user_id' => $user->id]);

        Log::info("Contact {$contact->id} promoted to new user {$user->id} (collaboratore)");

        return $user;
    }

    /**
     * Split a full name into name and surname.
     */
    private function splitName(?string $fullName): array
    {
        if (empty($fullName)) {
            return ['name' => null, 'surname' => null];
        }

        $parts = explode(' ', trim($fullName), 2);
        return [
            'name' => $parts[0] ?? null,
            'surname' => $parts[1] ?? null,
        ];
    }

    /**
     * Generate a unique username for the new user.
     */
    private function generateUsername(Contact $contact): string
    {
        $base = strtolower(str_replace(' ', '.', $contact->name ?? 'user'));
        $base = preg_replace('/[^a-z0-9.]/', '', $base);

        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (User::withoutGlobalScopes()
            ->where('company_id', $contact->company_id)
            ->where('username', $username)
            ->exists()
        ) {
            $username = $base . '.' . $counter;
            $counter++;
        }

        return $username;
    }
}
