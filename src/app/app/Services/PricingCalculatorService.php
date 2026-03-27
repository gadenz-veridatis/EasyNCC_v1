<?php

namespace App\Services;

use App\Models\Settings;
use Database\Seeders\PricingConfigSeeder;

class PricingCalculatorService
{
    /**
     * Get pricing config for a company, merging saved settings with defaults.
     */
    public static function getConfig(int $companyId): array
    {
        $settings = Settings::withoutGlobalScopes()->where('company_id', $companyId)->first();
        $defaults = PricingConfigSeeder::getDefaults();

        $config = [];
        foreach ($defaults as $key => $defaultValue) {
            $saved = $settings?->$key;
            $config[$key] = $saved !== null ? $saved : $defaultValue;
        }

        return $config;
    }

    /**
     * Calculate vehicle depreciation per km based on config.
     */
    public static function calculateDepreciationPerKm(array $config): float
    {
        $assumptions = $config['pricing_vehicle_assumptions'];
        $depreciation = $config['pricing_depreciation'];
        $vehicleCost = $assumptions['vehicle_cost'];
        $vehicleLifeKm = $assumptions['vehicle_life_km'];
        $annualUsageKm = $assumptions['annual_usage_km'];
        $vehicleAge = $assumptions['vehicle_age'];

        // Calculate residual values year by year
        $residuals = [];
        $residuals[0] = $vehicleCost / (1 + $depreciation[0]);
        $depreciationAmounts = [];
        $depreciationAmounts[0] = $vehicleCost - $residuals[0];

        for ($i = 1; $i < count($depreciation); $i++) {
            $residuals[$i] = $residuals[$i - 1] * (1 - $depreciation[$i]);
            $depreciationAmounts[$i] = ($i < count($depreciation) - 1)
                ? $residuals[$i] - ($residuals[$i] * (1 - ($depreciation[$i + 1] ?? 0.2)))
                : $residuals[$i] * $depreciation[$i];
        }

        // Calculate expected lifespan (years within vehicle life km)
        $cumulativeKm = 0;
        $lifespanYears = [];
        for ($i = 0; $i < count($depreciation) - 1; $i++) {
            $cumulativeKm += $annualUsageKm;
            $lifespanYears[$i] = $cumulativeKm <= $vehicleLifeKm ? 1 : 0;
        }

        // Calculate depreciation per km per year
        $depPerKm = [];
        // Year 0+1 combined for first year
        $depPerKm[0] = ($depreciationAmounts[0] + ($depreciationAmounts[1] ?? 0)) / $annualUsageKm;
        for ($i = 1; $i < count($depreciation) - 1; $i++) {
            $depPerKm[$i] = ($depreciationAmounts[$i + 1] ?? $depreciationAmounts[$i]) / $annualUsageKm;
        }

        // If vehicle age is 0 or greater than lifespan, use average
        if ($vehicleAge == 0 || $vehicleAge > array_sum($lifespanYears)) {
            $totalDep = 0;
            $totalLife = 0;
            for ($i = 0; $i < count($depPerKm); $i++) {
                if (isset($lifespanYears[$i]) && $lifespanYears[$i] == 1) {
                    $totalDep += $depPerKm[$i];
                    $totalLife++;
                }
            }
            return $totalLife > 0 ? $totalDep / $totalLife : 0;
        }

        return $depPerKm[$vehicleAge - 1] ?? 0;
    }

    /**
     * Calculate base operating costs per km.
     */
    public static function calculateCostsPerKm(array $config): array
    {
        $vehicleCosts = $config['pricing_vehicle_costs'];
        $assumptions = $config['pricing_vehicle_assumptions'];
        $expenses = $config['pricing_annual_expenses'];
        $tollConfig = $config['pricing_toll'];

        // Fuel cost per km
        $fuelPricePerLiter = $vehicleCosts['fuel_price'] + $vehicleCosts['adblue'];
        $fuelCostPerKm = $fuelPricePerLiter / $assumptions['fuel_consumption'];

        // Depreciation per km
        $depreciationPerKm = self::calculateDepreciationPerKm($config);

        // Annual fixed expenses
        $tiresCostPerYear = $assumptions['annual_usage_km'] / $expenses['tire_change_km'] * $expenses['tire_cost'];
        $baseExpenses = $expenses['insurance'] + $expenses['tax'] + $expenses['inspection']
            + $expenses['service'] + $tiresCostPerYear + $expenses['bodywork']
            + $expenses['mechanic'] + $expenses['washing'];
        $forfeitCosts = $baseExpenses * $expenses['forfeit_pct'];
        $totalAnnualExpenses = $baseExpenses + $forfeitCosts;
        $fixedCostPerKm = $totalAnnualExpenses / $assumptions['annual_usage_km'];

        // Toll cost per year (for fixed cost calculation)
        $tollShareKm = $assumptions['annual_usage_km'] * $tollConfig['toll_road_share'];
        $tollCostPerYear = $tollConfig['avg_price_per_km'] * $tollShareKm;
        $tollCostPerKm = $tollCostPerYear / $assumptions['annual_usage_km'];

        // Total variable cost per km (for extra driver calculations)
        $totalVariableCostPerKm = $fuelCostPerKm + $depreciationPerKm + $fixedCostPerKm;

        return [
            'fuel_cost_per_km' => $fuelCostPerKm,
            'depreciation_per_km' => $depreciationPerKm,
            'fixed_cost_per_km' => $fixedCostPerKm,
            'toll_cost_per_km' => $tollCostPerKm,
            'total_variable_cost_per_km' => $totalVariableCostPerKm,
            'driver_hourly' => $vehicleCosts['driver_hourly'],
        ];
    }

    /**
     * Apply km-based rectification (exponential decay + linear attenuation).
     * Replicates: cost * ((100 + MAX_ADD * e^(-km/DECAY)) / 100) * IF(km < THRESHOLD, 1, ...)
     */
    public static function applyKmRectification(float $cost, float $km, array $attConfig): float
    {
        $maxAdd = $attConfig['max_add'];
        $decaySpeed = $attConfig['decay_speed'];
        $attSpeed = $attConfig['att_speed'];
        $threshold = $attConfig['threshold'];

        // Exponential addition factor
        $expFactor = (100 + $maxAdd * exp(-1 * $km / $decaySpeed)) / 100;

        // Linear attenuation factor for high km
        if ($km < $threshold) {
            $attFactor = 1;
        } else {
            $attFactor = (100 - ($attSpeed / 100 * $km) + ($threshold * $attSpeed / 100)) / 100;
        }

        return $cost * $expFactor * $attFactor;
    }

    /**
     * Calculate the full pricing from inputs.
     */
    public static function calculate(array $inputs, array $config): array
    {
        $km = floatval($inputs['mileage'] ?? 0);
        $extraKm = floatval($inputs['extra_km'] ?? 0);
        $hours = floatval($inputs['duration_hours'] ?? 0);
        $extensionHours = floatval($inputs['extension_hours'] ?? 0);
        $extraTravelHours = floatval($inputs['extra_travel_hours'] ?? 0);
        $toll = floatval($inputs['toll_cost'] ?? 0);
        $pax = intval($inputs['pax_count'] ?? 0);
        $expPerPax = floatval($inputs['experience_per_pax'] ?? 0);
        $seasonality = $inputs['seasonality'] ?? 'average';
        $vehicleFill = $inputs['vehicle_fill'] ?? 'car';
        $vatPct = floatval($inputs['vat_percentage'] ?? 10) / 100;
        $cardPct = floatval($inputs['card_fees_percentage'] ?? 5) / 100;
        $surchargePct = floatval($inputs['surcharge_percentage'] ?? 0) / 100;
        $travelCosts = floatval($inputs['travel_costs'] ?? 0);
        $serviceType = $inputs['service_type'] ?? '';

        $costsPerKm = self::calculateCostsPerKm($config);
        $markups = $config['pricing_markups'];
        $seasonService = $config['pricing_season_service'];
        $vehicleService = $config['pricing_vehicle_service'];
        $seasonExp = $config['pricing_season_experience'];
        $vehicleExp = $config['pricing_vehicle_experience'];
        $attTransport = $config['pricing_attenuation_transport'];
        $attDriver = $config['pricing_attenuation_driver'];

        // 1. Base transport costs
        $fuelCost = $km * $costsPerKm['fuel_cost_per_km'];
        $depreciationCost = $km * $costsPerKm['depreciation_per_km'];
        $fixedCost = $km * $costsPerKm['fixed_cost_per_km'];
        $driverCost = $hours * $costsPerKm['driver_hourly'];
        $totalCostTransport = $fuelCost + $depreciationCost + $fixedCost + $driverCost + $toll;

        // 2. Rectified transport cost (exponential + attenuation)
        $rectifiedCost = self::applyKmRectification($totalCostTransport, $km, $attTransport);

        // 3. Extra driver cost (for extra km and hours when not starting from base)
        $extraDriverKmCost = $extraKm * $costsPerKm['total_variable_cost_per_km'];
        $rectifiedExtraKm = self::applyKmRectification($extraDriverKmCost, $extraKm, $attDriver);

        // Extension hour cost (service extension with client)
        $extensionCost = 0;
        if ($extensionHours > 0) {
            $extensionMultipliers = $config['pricing_extension'];
            $vehicleExtMult = $extensionMultipliers[$vehicleFill] ?? 1.0;
            // Extension uses empty/low/average/high based on season but typically same as season
            $seasonExtMult = $extensionMultipliers[$seasonality] ?? 1.0;
            $extensionCost = $extensionHours * $costsPerKm['driver_hourly'] * $vehicleExtMult * $seasonExtMult;
        }

        // Extra travel hours cost (driver-only cost, calculated like extension)
        $extraTravelCost = 0;
        if ($extraTravelHours > 0) {
            $extraTravelCost = $extraTravelHours * $costsPerKm['driver_hourly'];
        }

        $totalExtraCost = max($rectifiedExtraKm, $extraTravelCost) + $extensionCost;

        // 4. Transport price with markups
        $seasonMult = $seasonService[$seasonality] ?? 1.0;
        $vehicleMult = $vehicleService[$vehicleFill] ?? 1.0;
        $baseMarkup = $markups['transport'] / 100;
        $tourMarkup = ($serviceType !== 'TRF') ? ($markups['tour'] / 100) : 0;
        $driverMarkup = $markups['driver'] / 100;

        $taxableTransport = ($rectifiedCost * (1 + $baseMarkup * $seasonMult) * $vehicleMult
            * (1 + $tourMarkup))
            + ($totalExtraCost * (1 + $driverMarkup));

        // 5. Experience price
        $expSeasonMult = $seasonExp[$seasonality] ?? 1.0;
        $expVehicleMult = $vehicleExp[$vehicleFill] ?? 1.0;
        $expMarkup = $markups['experiences'] / 100;
        $totalExpCost = $pax * $expPerPax;
        $taxableExperience = $totalExpCost * (1 + $expMarkup * $expSeasonMult) * $expVehicleMult;

        // 6. Final price calculation
        // Round transport taxable up to nearest unit
        $taxableTransportRounded = ceil($taxableTransport);
        $taxableExperienceRounded = round($taxableExperience, 2);

        $surchargeAmount = ($taxableTransportRounded + $taxableExperienceRounded) * $surchargePct;
        $taxablePrice = $taxableTransportRounded + $taxableExperienceRounded + $surchargeAmount + $travelCosts;
        $taxablePriceRounded = ceil($taxablePrice / 5) * 5;

        // VAT and CC Fees calculated on rounded taxable
        $vatAmount = $taxablePriceRounded * $vatPct;
        $ccFeesAmount = $taxablePriceRounded * $cardPct;
        $finalPrice = $taxablePriceRounded + $vatAmount + $ccFeesAmount;
        $finalPriceRounded = ceil($finalPrice / 5) * 5;

        // 7. Sconto e Deposito - uses override or calculated taxable as base
        $overrideTaxable = floatval($inputs['override_taxable'] ?? 0);
        $imponibileBase = ($overrideTaxable > 0) ? $overrideTaxable : $taxablePriceRounded;
        $discountPct = floatval($inputs['discount_percentage'] ?? 0);
        $discountedTaxable = $discountPct > 0
            ? ceil($imponibileBase * (1 - $discountPct / 100) / 5) * 5
            : $imponibileBase;
        $imponibile = $discountedTaxable;
        $depositPct = floatval($inputs['deposit_percentage'] ?? 30);

        // Intermediate prices (matching Service Form formulas)
        $prezzoConIva = $imponibile * (100 + $vatPct * 100) / 100;
        $prezzoConIvaECardFees = $prezzoConIva * (100 + $cardPct * 100) / 100;

        // Acconto Imponibile = Imponibile × Acconto% / 100 (arr. 5€)
        $depositTaxable = ceil($imponibile * $depositPct / 100 / 5) * 5;

        // Acconto Handling Fees = Acconto Imponibile × (1 + IVA%) (arr. 5€)
        $depositHandlingFees = ceil($depositTaxable * (1 + $vatPct) / 5) * 5;

        // Acconto Totale = Con IVA e Card Fees × Acconto% (arr. 5€)
        $depositTotal = ceil($prezzoConIvaECardFees * ($depositPct / 100) / 5) * 5;

        // Saldo Imponibile = Imponibile - Acconto Imponibile
        $balanceTaxable = round($imponibile - $depositTaxable, 2);

        // Saldo Handling Fees = Con IVA × (100 - Acconto%) / 100 (arr. 5€)
        $balanceHandlingFees = ceil($prezzoConIva * (100 - $depositPct) / 100 / 5) * 5;

        // Saldo Card Fees = Con IVA e Card Fees × (100 - Acconto%) / 100 (arr. 5€)
        $balanceCardFees = ceil($prezzoConIvaECardFees * (100 - $depositPct) / 100 / 5) * 5;

        return [
            'taxable_transport' => $taxableTransportRounded,
            'taxable_experience' => $taxableExperienceRounded,
            'surcharge_amount' => round($surchargeAmount, 2),
            'taxable_price' => round($taxablePrice, 2),
            'taxable_price_rounded' => $taxablePriceRounded,
            'vat_amount' => round($vatAmount, 2),
            'cc_fees_amount' => round($ccFeesAmount, 2),
            'final_price' => round($finalPrice, 2),
            'final_price_rounded' => $finalPriceRounded,
            'discounted_taxable' => $discountedTaxable,
            'discount_amount' => round($imponibileBase - $discountedTaxable, 2),
            'deposit_percentage' => $depositPct,
            'deposit_taxable' => $depositTaxable,
            'deposit_handling_fees' => $depositHandlingFees,
            'deposit_total' => $depositTotal,
            'balance_taxable' => $balanceTaxable,
            'balance_handling_fees' => $balanceHandlingFees,
            'balance_card_fees' => $balanceCardFees,
        ];
    }

    /**
     * Calculate quote-level totals from the sum of item taxable prices.
     * Used when quote has multiple items - each item provides its own taxable_price,
     * and this function aggregates them with VAT, card fees, discount, and deposit.
     */
    public static function calculateQuoteTotals(array $params): array
    {
        $itemsTaxableSum = floatval($params['items_taxable_sum'] ?? 0);
        $vatPct = floatval($params['vat_percentage'] ?? 10) / 100;
        $cardPct = floatval($params['card_fees_percentage'] ?? 5) / 100;
        $overrideTaxable = floatval($params['override_taxable'] ?? 0);
        $discountPct = floatval($params['discount_percentage'] ?? 0);
        $depositPct = floatval($params['deposit_percentage'] ?? 30);

        $taxablePrice = $itemsTaxableSum;
        $taxablePriceRounded = ceil($taxablePrice / 5) * 5;

        $vatAmount = $taxablePriceRounded * $vatPct;
        $ccFeesAmount = $taxablePriceRounded * $cardPct;
        $finalPrice = $taxablePriceRounded + $vatAmount + $ccFeesAmount;
        $finalPriceRounded = ceil($finalPrice / 5) * 5;

        // Discount and deposit - uses override or calculated taxable as base
        $imponibileBase = ($overrideTaxable > 0) ? $overrideTaxable : $taxablePriceRounded;
        $discountedTaxable = $discountPct > 0
            ? ceil($imponibileBase * (1 - $discountPct / 100) / 5) * 5
            : $imponibileBase;
        $imponibile = $discountedTaxable;

        // Intermediate prices
        $prezzoConIva = $imponibile * (100 + $vatPct * 100) / 100;
        $prezzoConIvaECardFees = $prezzoConIva * (100 + $cardPct * 100) / 100;

        $depositTaxable = ceil($imponibile * $depositPct / 100 / 5) * 5;
        $depositHandlingFees = ceil($depositTaxable * (1 + $vatPct) / 5) * 5;
        $depositTotal = ceil($prezzoConIvaECardFees * ($depositPct / 100) / 5) * 5;
        $balanceTaxable = round($imponibile - $depositTaxable, 2);
        $balanceHandlingFees = ceil($prezzoConIva * (100 - $depositPct) / 100 / 5) * 5;
        $balanceCardFees = ceil($prezzoConIvaECardFees * (100 - $depositPct) / 100 / 5) * 5;

        return [
            'taxable_transport' => 0,
            'taxable_experience' => 0,
            'surcharge_amount' => 0,
            'taxable_price' => round($taxablePrice, 2),
            'taxable_price_rounded' => $taxablePriceRounded,
            'vat_amount' => round($vatAmount, 2),
            'cc_fees_amount' => round($ccFeesAmount, 2),
            'final_price' => round($finalPrice, 2),
            'final_price_rounded' => $finalPriceRounded,
            'discounted_taxable' => $discountedTaxable,
            'discount_amount' => round($imponibileBase - $discountedTaxable, 2),
            'deposit_percentage' => $depositPct,
            'deposit_taxable' => $depositTaxable,
            'deposit_handling_fees' => $depositHandlingFees,
            'deposit_total' => $depositTotal,
            'balance_taxable' => $balanceTaxable,
            'balance_handling_fees' => $balanceHandlingFees,
            'balance_card_fees' => $balanceCardFees,
        ];
    }
}
