<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the first super-admin or admin user to use as default creator/updater
        $defaultUser = DB::table('users')
            ->whereIn('role', ['super-admin', 'admin'])
            ->orderBy('id')
            ->first();

        if ($defaultUser) {
            // Update all vehicles that have null created_by or updated_by
            DB::table('vehicles')
                ->whereNull('created_by')
                ->orWhereNull('updated_by')
                ->update([
                    'created_by' => DB::raw("COALESCE(created_by, {$defaultUser->id})"),
                    'updated_by' => DB::raw("COALESCE(updated_by, {$defaultUser->id})")
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't revert this data migration
        // The fields will remain populated
    }
};
