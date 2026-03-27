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
            // Vehicle costs (Spese Veicolo)
            $table->decimal('fuel_cost', 10, 2)->nullable()->after('expenses')->comment('Costo carburanti');
            $table->decimal('toll_cost', 10, 2)->nullable()->after('fuel_cost')->comment('Costo pedaggio');
            $table->decimal('parking_cost', 10, 2)->nullable()->after('toll_cost')->comment('Costo parcheggio');
            $table->decimal('other_vehicle_costs', 10, 2)->nullable()->after('parking_cost')->comment('Altri costi veicolo');

            // Driver costs (Spese Driver)
            $table->decimal('colleague_cost', 10, 2)->nullable()->after('other_vehicle_costs')->comment('Costo collega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'fuel_cost',
                'toll_cost',
                'parking_cost',
                'other_vehicle_costs',
                'colleague_cost',
            ]);
        });
    }
};
