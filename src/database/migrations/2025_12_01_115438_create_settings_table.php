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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Percentuale acconto di default
            $table->decimal('deposit_percentage', 5, 2)->default(30.00)->comment('Percentuale acconto vendita (es. 30.00 per 30%)');

            // Causali contabili per vendita
            $table->foreignId('deposit_accounting_entry_id')->nullable()->constrained('accounting_entries')->onDelete('set null')->comment('Causale contabile acconto vendita');
            $table->string('deposit_reason')->nullable()->comment('Causale movimento acconto vendita');

            $table->foreignId('balance_accounting_entry_id')->nullable()->constrained('accounting_entries')->onDelete('set null')->comment('Causale contabile saldo vendita');
            $table->string('balance_reason')->nullable()->comment('Causale movimento saldo vendita');

            $table->timestamps();

            // Un solo record di settings per azienda
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
