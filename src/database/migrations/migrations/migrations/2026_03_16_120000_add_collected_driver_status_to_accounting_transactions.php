<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old check constraint and create a new one that includes 'collected_driver'
        DB::statement('ALTER TABLE accounting_transactions DROP CONSTRAINT IF EXISTS accounting_transactions_status_check');
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status IN ('to_pay', 'paid', 'suspended', 'cancelled', 'to_collect', 'collected', 'collected_driver'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE accounting_transactions DROP CONSTRAINT IF EXISTS accounting_transactions_status_check');
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status IN ('to_pay', 'paid', 'suspended', 'cancelled', 'to_collect', 'collected'))");
    }
};
