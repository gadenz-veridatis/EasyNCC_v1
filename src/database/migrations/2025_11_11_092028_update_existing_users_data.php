<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize is_intermediario to false for all existing users (default already set in previous migration)
        // This is just to ensure consistency
        DB::table('users')
            ->whereNull('is_intermediario')
            ->update(['is_intermediario' => false]);

        // Initialize percentuale_commissione to null for all existing users (already null by default)
        // No action needed as it's nullable

        // Update client_profiles: set is_committente and is_fornitore based on existing data
        // If a client profile exists, we assume they could be either committente or fornitore
        // Set is_committente to true for all existing client profiles
        DB::table('client_profiles')
            ->whereNull('is_committente')
            ->update(['is_committente' => true]);

        // Keep is_fornitore as false by default (can be updated manually if needed)
        DB::table('client_profiles')
            ->whereNull('is_fornitore')
            ->update(['is_fornitore' => false]);

        // Create operator profiles for existing operator users
        $operators = DB::table('users')
            ->where('role', 'operator')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('operator_profiles')
                    ->whereRaw('operator_profiles.user_id = users.id');
            })
            ->get(['id']);

        foreach ($operators as $operator) {
            DB::table('operator_profiles')->insert([
                'user_id' => $operator->id,
                'is_contabilita' => false, // Default to false
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert is_committente for client profiles
        DB::table('client_profiles')
            ->where('is_committente', true)
            ->update(['is_committente' => false]);

        // Delete operator profiles created by this migration
        DB::table('operator_profiles')
            ->where('is_contabilita', false)
            ->delete();
    }
};
