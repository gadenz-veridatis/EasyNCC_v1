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
        // Find the first super-admin user to use as default creator/updater
        $superAdmin = DB::table('users')
            ->where('role', 'super-admin')
            ->orderBy('id')
            ->first();

        if ($superAdmin) {
            // Update users with null created_by
            DB::table('users')
                ->whereNull('created_by')
                ->update(['created_by' => $superAdmin->id]);

            // Update users with null updated_by
            DB::table('users')
                ->whereNull('updated_by')
                ->update(['updated_by' => $superAdmin->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
    }
};
