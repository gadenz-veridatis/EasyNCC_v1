<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->index('transaction_type');
            $table->index('status');
            $table->index('accounting_entry_id');
            $table->index(['company_id', 'transaction_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['accounting_entry_id']);
            $table->dropIndex(['company_id', 'transaction_type', 'status']);
        });
    }
};
