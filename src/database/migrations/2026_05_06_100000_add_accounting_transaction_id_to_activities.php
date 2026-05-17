<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_transaction_id')->nullable()->after('confirmation_assignee_id');
            $table->foreign('accounting_transaction_id')->references('id')->on('accounting_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['accounting_transaction_id']);
            $table->dropColumn('accounting_transaction_id');
        });
    }
};
