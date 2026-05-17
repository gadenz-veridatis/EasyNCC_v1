<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add "Incassato Driver" status to transaction_statuses for all companies.
     */
    public function up(): void
    {
        $companies = DB::table('companies')->pluck('id');
        $now = now();

        foreach ($companies as $companyId) {
            // Only insert if not already present
            $exists = DB::table('transaction_statuses')
                ->where('company_id', $companyId)
                ->where('code', 'collected_driver')
                ->exists();

            if (!$exists) {
                DB::table('transaction_statuses')->insert([
                    'company_id' => $companyId,
                    'code' => 'collected_driver',
                    'name' => 'Incassato Driver',
                    'abbreviation' => 'IND',
                    'color' => 'primary',
                    'transaction_type_group' => 'sale',
                    'is_final' => true,
                    'is_active' => true,
                    'sort_order' => 5,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transaction_statuses')->where('code', 'collected_driver')->delete();
    }
};
