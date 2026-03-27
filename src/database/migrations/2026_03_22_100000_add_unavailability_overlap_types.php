<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change overlap_type from enum to varchar to support new types
        // PostgreSQL: alter the enum type to add new values
        DB::statement("ALTER TABLE service_overlaps ALTER COLUMN overlap_type TYPE VARCHAR(30)");
    }

    public function down(): void
    {
        // Revert to original enum (remove new values first)
        DB::statement("DELETE FROM service_overlaps WHERE overlap_type IN ('driver_unavailability', 'vehicle_unavailability')");
        DB::statement("ALTER TABLE service_overlaps ALTER COLUMN overlap_type TYPE VARCHAR(30)");
    }
};
