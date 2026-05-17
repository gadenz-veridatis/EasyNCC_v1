<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->renameColumn('deposit_full', 'deposit_handling_fees');
            $table->renameColumn('balance_bank', 'balance_handling_fees');
            $table->renameColumn('balance_cc', 'balance_card_fees');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('deposit_total', 10, 2)->default(0)->after('deposit_handling_fees');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('deposit_total');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->renameColumn('deposit_handling_fees', 'deposit_full');
            $table->renameColumn('balance_handling_fees', 'balance_bank');
            $table->renameColumn('balance_card_fees', 'balance_cc');
        });
    }
};
