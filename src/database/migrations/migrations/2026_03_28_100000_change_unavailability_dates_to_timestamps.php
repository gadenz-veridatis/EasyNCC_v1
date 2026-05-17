<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Driver unavailabilities: convert date to timestamp
        // First, migrate existing data: end_date gets time 23:59:59
        DB::statement("ALTER TABLE driver_unavailabilities ALTER COLUMN start_date TYPE timestamp USING start_date::timestamp");
        DB::statement("ALTER TABLE driver_unavailabilities ALTER COLUMN end_date TYPE timestamp USING (end_date::timestamp + interval '23 hours 59 minutes 59 seconds')");

        // Add all_day boolean flag (default true for existing records)
        Schema::table('driver_unavailabilities', function (Blueprint $table) {
            $table->boolean('all_day')->default(true)->after('end_date');
        });

        // Vehicle unavailabilities: same treatment
        DB::statement("ALTER TABLE vehicle_unavailabilities ALTER COLUMN start_date TYPE timestamp USING start_date::timestamp");
        DB::statement("ALTER TABLE vehicle_unavailabilities ALTER COLUMN end_date TYPE timestamp USING (end_date::timestamp + interval '23 hours 59 minutes 59 seconds')");

        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->boolean('all_day')->default(true)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('driver_unavailabilities', function (Blueprint $table) {
            $table->dropColumn('all_day');
        });
        DB::statement("ALTER TABLE driver_unavailabilities ALTER COLUMN start_date TYPE date USING start_date::date");
        DB::statement("ALTER TABLE driver_unavailabilities ALTER COLUMN end_date TYPE date USING end_date::date");

        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->dropColumn('all_day');
        });
        DB::statement("ALTER TABLE vehicle_unavailabilities ALTER COLUMN start_date TYPE date USING start_date::date");
        DB::statement("ALTER TABLE vehicle_unavailabilities ALTER COLUMN end_date TYPE date USING end_date::date");
    }
};
