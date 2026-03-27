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
        Schema::create('service_overlaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('overlapping_service_id')->constrained('services')->onDelete('cascade');
            $table->enum('overlap_type', ['vehicle', 'driver', 'both']);
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->boolean('confirmed_by_user')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index('service_id');
            $table->index('overlapping_service_id');
            $table->index('driver_id');
            $table->index('vehicle_id');

            // Unique constraint to avoid duplicate overlaps
            $table->unique(['service_id', 'overlapping_service_id', 'overlap_type', 'driver_id', 'vehicle_id'], 'unique_overlap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_overlaps');
    }
};
