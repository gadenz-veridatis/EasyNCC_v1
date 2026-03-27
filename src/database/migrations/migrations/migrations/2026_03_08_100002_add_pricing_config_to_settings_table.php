<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('pricing_markups')->nullable()->comment('{"transport":45,"experiences":10,"tour":25,"driver":0}');
            $table->json('pricing_vehicle_costs')->nullable()->comment('{"fuel_price":1.8,"adblue":0.1,"driver_hourly":22,"vehicle_hourly":77}');
            $table->json('pricing_vehicle_assumptions')->nullable()->comment('{"fuel_consumption":10,"vehicle_cost":67500,"vehicle_life_km":250000,"annual_usage_km":80000,"vehicle_age":0}');
            $table->json('pricing_annual_expenses')->nullable()->comment('Annual fixed expenses JSON');
            $table->json('pricing_season_service')->nullable()->comment('{"low":0.8,"average":1.0,"high":1.3}');
            $table->json('pricing_vehicle_service')->nullable()->comment('{"car":0.85,"van":1.0,"full":1.2}');
            $table->json('pricing_season_experience')->nullable()->comment('{"low":0.5,"average":1.0,"high":1.7}');
            $table->json('pricing_vehicle_experience')->nullable()->comment('{"car":1.0,"van":1.1,"full":1.2}');
            $table->json('pricing_attenuation_transport')->nullable()->comment('{"max_add":120,"decay_speed":45,"att_speed":5,"threshold":600}');
            $table->json('pricing_attenuation_driver')->nullable()->comment('{"max_add":60,"decay_speed":30,"att_speed":25,"threshold":60}');
            $table->json('pricing_extension')->nullable()->comment('Extension multipliers');
            $table->json('pricing_depreciation')->nullable()->comment('Array of yearly depreciation percentages');
            $table->json('pricing_toll')->nullable()->comment('{"avg_price_per_km":0.07,"toll_road_share":0.35}');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_markups',
                'pricing_vehicle_costs',
                'pricing_vehicle_assumptions',
                'pricing_annual_expenses',
                'pricing_season_service',
                'pricing_vehicle_service',
                'pricing_season_experience',
                'pricing_vehicle_experience',
                'pricing_attenuation_transport',
                'pricing_attenuation_driver',
                'pricing_extension',
                'pricing_depreciation',
                'pricing_toll',
            ]);
        });
    }
};
