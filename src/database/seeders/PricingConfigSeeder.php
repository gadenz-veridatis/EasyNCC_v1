<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings;
use App\Models\Company;

class PricingConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = self::getDefaults();

        $companies = Company::all();
        foreach ($companies as $company) {
            $settings = Settings::firstOrCreate(
                ['company_id' => $company->id],
                ['deposit_percentage' => 30.00, 'card_fees_percentage' => 5.00]
            );

            // Only set pricing fields if they are null (don't overwrite existing)
            $update = [];
            foreach ($defaults as $key => $value) {
                if ($settings->$key === null) {
                    $update[$key] = $value;
                }
            }
            if (!empty($update)) {
                $settings->update($update);
            }
        }
    }

    public static function getDefaults(): array
    {
        return [
            'pricing_markups' => [
                'transport' => 45,
                'experiences' => 10,
                'tour' => 25,
                'driver' => 0,
            ],
            'pricing_vehicle_costs' => [
                'fuel_price' => 1.8,
                'adblue' => 0.1,
                'driver_hourly' => 22,
                'vehicle_hourly' => 77,
            ],
            'pricing_vehicle_assumptions' => [
                'fuel_consumption' => 10,
                'vehicle_cost' => 67500,
                'vehicle_life_km' => 250000,
                'annual_usage_km' => 80000,
                'vehicle_age' => 0,
            ],
            'pricing_annual_expenses' => [
                'insurance' => 2500,
                'tax' => 400,
                'inspection' => 100,
                'service' => 3000,
                'bodywork' => 1000,
                'mechanic' => 1000,
                'washing' => 1200,
                'forfeit_pct' => 0.15,
                'tire_change_km' => 40000,
                'tire_cost' => 1000,
            ],
            'pricing_season_service' => [
                'low' => 0.8,
                'average' => 1.0,
                'high' => 1.3,
            ],
            'pricing_vehicle_service' => [
                'car' => 0.85,
                'van' => 1.0,
                'full' => 1.2,
            ],
            'pricing_season_experience' => [
                'low' => 0.5,
                'average' => 1.0,
                'high' => 1.7,
            ],
            'pricing_vehicle_experience' => [
                'car' => 1.0,
                'van' => 1.1,
                'full' => 1.2,
            ],
            'pricing_attenuation_transport' => [
                'max_add' => 120,
                'decay_speed' => 45,
                'att_speed' => 5,
                'threshold' => 600,
            ],
            'pricing_attenuation_driver' => [
                'max_add' => 60,
                'decay_speed' => 30,
                'att_speed' => 25,
                'threshold' => 60,
            ],
            'pricing_extension' => [
                'car' => 0.9,
                'van' => 1.0,
                'full' => 1.1,
                'empty' => 0.71,
                'low' => 0.85,
                'average' => 1.0,
                'high' => 1.15,
            ],
            'pricing_depreciation' => [0.22, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2],
            'pricing_toll' => [
                'avg_price_per_km' => 0.07,
                'toll_road_share' => 0.35,
            ],
        ];
    }
}
