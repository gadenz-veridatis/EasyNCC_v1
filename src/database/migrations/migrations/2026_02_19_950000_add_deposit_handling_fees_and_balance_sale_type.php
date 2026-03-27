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
            $table->decimal('deposit_handling_fees', 10, 2)->nullable()->after('deposit_taxable');
            $table->string('balance_sale_type')->nullable()->default('balance_taxable')->after('balance_card_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['deposit_handling_fees', 'balance_sale_type']);
        });
    }
};
