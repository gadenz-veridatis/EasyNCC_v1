<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Seed default transaction statuses for all existing companies.
     */
    public function up(): void
    {
        $companies = DB::table('companies')->pluck('id');

        $now = now();

        $defaultStatuses = [
            ['code' => 'to_pay',     'name' => 'Da Pagare',     'abbreviation' => 'DP',  'color' => 'warning',   'transaction_type_group' => 'purchase', 'is_final' => false, 'sort_order' => 1],
            ['code' => 'paid',       'name' => 'Pagato',        'abbreviation' => 'PAG', 'color' => 'success',   'transaction_type_group' => 'purchase', 'is_final' => true,  'sort_order' => 2],
            ['code' => 'to_collect', 'name' => 'Da Incassare',  'abbreviation' => 'DI',  'color' => 'info',      'transaction_type_group' => 'sale',     'is_final' => false, 'sort_order' => 3],
            ['code' => 'collected',  'name' => 'Incassato',     'abbreviation' => 'INC', 'color' => 'success',   'transaction_type_group' => 'sale',     'is_final' => true,  'sort_order' => 4],
            ['code' => 'suspended',  'name' => 'Sospeso',       'abbreviation' => 'SOS', 'color' => 'secondary', 'transaction_type_group' => 'both',     'is_final' => false, 'sort_order' => 5],
            ['code' => 'cancelled',  'name' => 'Annullato',     'abbreviation' => 'ANN', 'color' => 'danger',    'transaction_type_group' => 'both',     'is_final' => false, 'sort_order' => 6],
        ];

        foreach ($companies as $companyId) {
            foreach ($defaultStatuses as $status) {
                DB::table('transaction_statuses')->insert(array_merge($status, [
                    'company_id' => $companyId,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transaction_statuses')->truncate();
    }
};
