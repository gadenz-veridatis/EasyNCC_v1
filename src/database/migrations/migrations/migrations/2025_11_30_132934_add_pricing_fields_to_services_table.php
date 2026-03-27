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
        Schema::table('services', function (Blueprint $table) {
            // Add pricing calculation fields
            $table->decimal('vat_rate', 5, 2)->nullable()->after('service_price')->comment('Aliquota IVA in percentuale');
            $table->decimal('card_fees_percentage', 5, 2)->nullable()->after('vat_rate')->comment('Percentuale commissioni carta');
            $table->decimal('deposit_percentage', 5, 2)->nullable()->after('card_fees_percentage')->comment('Percentuale acconto');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('deposit_percentage')->comment('Importo acconto calcolato');
            $table->decimal('balance_taxable', 10, 2)->nullable()->after('deposit_amount')->comment('Saldo imponibile');
            $table->decimal('balance_handling_fees', 10, 2)->nullable()->after('balance_taxable')->comment('Diritti di agenzia');
            $table->decimal('balance_card_fees', 10, 2)->nullable()->after('balance_handling_fees')->comment('Commissioni carta saldo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'vat_rate',
                'card_fees_percentage',
                'deposit_percentage',
                'deposit_amount',
                'balance_taxable',
                'balance_handling_fees',
                'balance_card_fees'
            ]);
        });
    }
};
