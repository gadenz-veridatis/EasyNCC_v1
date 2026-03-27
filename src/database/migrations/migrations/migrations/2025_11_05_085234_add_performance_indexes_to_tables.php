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
        // Add indexes to services table for frequently queried columns
        Schema::table('services', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('status_id');
            $table->index('vehicle_id');
            $table->index('client_id');
            $table->index('pickup_datetime');
            $table->index('dropoff_datetime');
            $table->index(['pickup_datetime', 'dropoff_datetime']); // Composite index for date range queries
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('role');
            $table->index('is_active');
            $table->index(['company_id', 'role']); // Composite index for filtering by company and role
        });

        // Add indexes to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('status');
        });

        // Add indexes to service_driver pivot table for driver queries
        Schema::table('service_driver', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status_id']);
            $table->dropIndex(['vehicle_id']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['pickup_datetime']);
            $table->dropIndex(['dropoff_datetime']);
            $table->dropIndex(['pickup_datetime', 'dropoff_datetime']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['company_id', 'role']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('service_driver', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['service_id']);
        });
    }
};
