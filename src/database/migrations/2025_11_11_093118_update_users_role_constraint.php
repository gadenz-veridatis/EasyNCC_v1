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
        // Step 1: Drop the old constraint first
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // Step 2: Update users with old role 'committente' to 'collaboratore'
        DB::table('users')
            ->where('role', 'committente')
            ->update(['role' => 'collaboratore']);

        // Step 3: Update any remaining old roles to collaboratore as fallback
        DB::table('users')
            ->whereIn('role', ['intermediario', 'fornitore', 'passeggero'])
            ->update(['role' => 'collaboratore']);

        // Step 4: Add the new constraint with updated roles
        DB::statement("
            ALTER TABLE users ADD CONSTRAINT users_role_check
            CHECK (role IN (
                'super-admin',
                'admin',
                'operator',
                'driver',
                'collaboratore',
                'contabilita'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // Restore the old constraint (for rollback purposes)
        DB::statement("
            ALTER TABLE users ADD CONSTRAINT users_role_check
            CHECK (role IN (
                'super-admin',
                'admin',
                'operator',
                'driver',
                'committente',
                'intermediario',
                'fornitore',
                'passeggero'
            ))
        ");
    }
};
