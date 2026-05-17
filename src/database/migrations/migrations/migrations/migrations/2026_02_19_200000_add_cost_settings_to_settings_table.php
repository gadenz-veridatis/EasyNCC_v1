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
            // Commissioni
            $table->foreignId('commission_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('commission_reason')->nullable();

            // Acquisto Carburanti
            $table->foreignId('fuel_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('fuel_reason')->nullable();

            // Acquisto Pedaggio
            $table->foreignId('toll_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('toll_reason')->nullable();

            // Acquisto Parcheggio
            $table->foreignId('parking_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('parking_reason')->nullable();

            // Acquisto Altro Veicolo
            $table->foreignId('other_vehicle_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('other_vehicle_reason')->nullable();

            // Costi Driver
            $table->foreignId('driver_cost_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('driver_cost_reason')->nullable();

            // Costi Collega
            $table->foreignId('colleague_cost_accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->string('colleague_cost_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commission_accounting_entry_id');
            $table->dropColumn('commission_reason');
            $table->dropConstrainedForeignId('fuel_accounting_entry_id');
            $table->dropColumn('fuel_reason');
            $table->dropConstrainedForeignId('toll_accounting_entry_id');
            $table->dropColumn('toll_reason');
            $table->dropConstrainedForeignId('parking_accounting_entry_id');
            $table->dropColumn('parking_reason');
            $table->dropConstrainedForeignId('other_vehicle_accounting_entry_id');
            $table->dropColumn('other_vehicle_reason');
            $table->dropConstrainedForeignId('driver_cost_accounting_entry_id');
            $table->dropColumn('driver_cost_reason');
            $table->dropConstrainedForeignId('colleague_cost_accounting_entry_id');
            $table->dropColumn('colleague_cost_reason');
        });
    }
};
