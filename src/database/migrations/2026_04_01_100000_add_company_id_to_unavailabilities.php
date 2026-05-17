<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add company_id to driver_unavailabilities
        Schema::table('driver_unavailabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
        });

        // Backfill company_id from users table for existing records
        DB::statement('
            UPDATE driver_unavailabilities
            SET company_id = users.company_id
            FROM users
            WHERE driver_unavailabilities.user_id = users.id
        ');

        // Make NOT NULL after backfill
        Schema::table('driver_unavailabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Add company_id to vehicle_unavailabilities
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
        });

        // Backfill company_id from vehicles table for existing records
        DB::statement('
            UPDATE vehicle_unavailabilities
            SET company_id = vehicles.company_id
            FROM vehicles
            WHERE vehicle_unavailabilities.vehicle_id = vehicles.id
        ');

        // Make NOT NULL after backfill
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('driver_unavailabilities', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
