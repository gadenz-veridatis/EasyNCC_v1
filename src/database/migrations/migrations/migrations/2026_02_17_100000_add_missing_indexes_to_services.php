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
            $table->index('intermediary_id');
            $table->index('supplier_id');
            $table->index('vehicle_departure_datetime');
            $table->index('vehicle_return_datetime');
            $table->index(['vehicle_departure_datetime', 'vehicle_return_datetime']); // Composite for overlap queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['intermediary_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['vehicle_departure_datetime']);
            $table->dropIndex(['vehicle_return_datetime']);
            $table->dropIndex(['vehicle_departure_datetime', 'vehicle_return_datetime']);
        });
    }
};
