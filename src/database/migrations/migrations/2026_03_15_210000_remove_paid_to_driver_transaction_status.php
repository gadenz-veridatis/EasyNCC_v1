<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the duplicate "paid_to_driver" status, keeping only "collected_driver".
     */
    public function up(): void
    {
        // Update any accounting transactions using paid_to_driver to collected_driver
        DB::table('accounting_transactions')
            ->where('status', 'paid_to_driver')
            ->update(['status' => 'collected_driver']);

        // Remove the paid_to_driver status
        DB::table('transaction_statuses')
            ->where('code', 'paid_to_driver')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-insert paid_to_driver for all companies
        $companies = DB::table('companies')->pluck('id');
        $now = now();

        foreach ($companies as $companyId) {
            DB::table('transaction_statuses')->insert([
                'company_id' => $companyId,
                'code' => 'paid_to_driver',
                'name' => 'Pagato al Driver',
                'abbreviation' => 'PTD',
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
};
