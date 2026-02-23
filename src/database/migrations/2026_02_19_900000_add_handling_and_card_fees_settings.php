<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('handling_fees_accounting_entry_id')->nullable()->after('experience_reason');
            $table->string('handling_fees_reason')->nullable()->after('handling_fees_accounting_entry_id');
            $table->unsignedBigInteger('card_fees_accounting_entry_id')->nullable()->after('handling_fees_reason');
            $table->string('card_fees_reason')->nullable()->after('card_fees_accounting_entry_id');

            $table->foreign('handling_fees_accounting_entry_id')->references('id')->on('accounting_entries')->nullOnDelete();
            $table->foreign('card_fees_accounting_entry_id')->references('id')->on('accounting_entries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['handling_fees_accounting_entry_id']);
            $table->dropForeign(['card_fees_accounting_entry_id']);
            $table->dropColumn([
                'handling_fees_accounting_entry_id',
                'handling_fees_reason',
                'card_fees_accounting_entry_id',
                'card_fees_reason',
            ]);
        });
    }
};
