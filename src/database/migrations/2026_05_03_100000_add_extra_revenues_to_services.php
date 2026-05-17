<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('extra_revenues')->nullable()->after('expenses');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('extra_revenue_accounting_entry_id')->nullable()->after('card_fees_reason');
            $table->string('extra_revenue_reason')->nullable()->after('extra_revenue_accounting_entry_id');
            $table->foreign('extra_revenue_accounting_entry_id')->references('id')->on('accounting_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('extra_revenues');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['extra_revenue_accounting_entry_id']);
            $table->dropColumn(['extra_revenue_accounting_entry_id', 'extra_revenue_reason']);
        });
    }
};
