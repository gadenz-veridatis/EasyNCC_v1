<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->foreignId('vehicle_unavailability_type_id')->nullable()->after('type')
                ->constrained('vehicle_unavailability_types')->nullOnDelete();
        });

        // Migrate existing type string values to the new dictionary FK
        $unavailabilities = DB::table('vehicle_unavailabilities')->get();
        foreach ($unavailabilities as $u) {
            // Find the vehicle's company
            $vehicle = DB::table('vehicles')->where('id', $u->vehicle_id)->first();
            if (!$vehicle) continue;

            // Find matching dictionary entry by name (case-insensitive)
            $typeName = ucfirst(strtolower($u->type));
            $typeRecord = DB::table('vehicle_unavailability_types')
                ->where('company_id', $vehicle->company_id)
                ->whereRaw('LOWER(name) = ?', [strtolower($u->type)])
                ->first();

            if ($typeRecord) {
                DB::table('vehicle_unavailabilities')
                    ->where('id', $u->id)
                    ->update(['vehicle_unavailability_type_id' => $typeRecord->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehicle_unavailabilities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_unavailability_type_id');
        });
    }
};
