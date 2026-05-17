/**
 * Client-side pricing calculator - mirrors PricingCalculatorService.php
 */

export function usePricingCalculator() {

    function calculateDepreciationPerKm(config) {
        const assumptions = config.pricing_vehicle_assumptions;
        const depreciation = config.pricing_depreciation;
        const vehicleCost = assumptions.vehicle_cost;
        const vehicleLifeKm = assumptions.vehicle_life_km;
        const annualUsageKm = assumptions.annual_usage_km;
        const vehicleAge = assumptions.vehicle_age;

        const residuals = [];
        residuals[0] = vehicleCost / (1 + depreciation[0]);
        const depreciationAmounts = [];
        depreciationAmounts[0] = vehicleCost - residuals[0];

        for (let i = 1; i < depreciation.length; i++) {
            residuals[i] = residuals[i - 1] * (1 - depreciation[i]);
            depreciationAmounts[i] = (i < depreciation.length - 1)
                ? residuals[i] - (residuals[i] * (1 - (depreciation[i + 1] || 0.2)))
                : residuals[i] * depreciation[i];
        }

        let cumulativeKm = 0;
        const lifespanYears = [];
        for (let i = 0; i < depreciation.length - 1; i++) {
            cumulativeKm += annualUsageKm;
            lifespanYears[i] = cumulativeKm <= vehicleLifeKm ? 1 : 0;
        }

        const depPerKm = [];
        depPerKm[0] = (depreciationAmounts[0] + (depreciationAmounts[1] || 0)) / annualUsageKm;
        for (let i = 1; i < depreciation.length - 1; i++) {
            depPerKm[i] = (depreciationAmounts[i + 1] || depreciationAmounts[i]) / annualUsageKm;
        }

        if (vehicleAge === 0 || vehicleAge > lifespanYears.reduce((a, b) => a + b, 0)) {
            let totalDep = 0;
            let totalLife = 0;
            for (let i = 0; i < depPerKm.length; i++) {
                if (lifespanYears[i] !== undefined && lifespanYears[i] === 1) {
                    totalDep += depPerKm[i];
                    totalLife++;
                }
            }
            return totalLife > 0 ? totalDep / totalLife : 0;
        }

        return depPerKm[vehicleAge - 1] || 0;
    }

    function calculateCostsPerKm(config) {
        const vehicleCosts = config.pricing_vehicle_costs;
        const assumptions = config.pricing_vehicle_assumptions;
        const expenses = config.pricing_annual_expenses;
        const tollConfig = config.pricing_toll;

        const fuelPricePerLiter = vehicleCosts.fuel_price + vehicleCosts.adblue;
        const fuelCostPerKm = fuelPricePerLiter / assumptions.fuel_consumption;

        const depreciationPerKm = calculateDepreciationPerKm(config);

        const tiresCostPerYear = assumptions.annual_usage_km / expenses.tire_change_km * expenses.tire_cost;
        const baseExpenses = expenses.insurance + expenses.tax + expenses.inspection
            + expenses.service + tiresCostPerYear + expenses.bodywork
            + expenses.mechanic + expenses.washing;
        const forfeitCosts = baseExpenses * expenses.forfeit_pct;
        const totalAnnualExpenses = baseExpenses + forfeitCosts;
        const fixedCostPerKm = totalAnnualExpenses / assumptions.annual_usage_km;

        const tollShareKm = assumptions.annual_usage_km * tollConfig.toll_road_share;
        const tollCostPerYear = tollConfig.avg_price_per_km * tollShareKm;
        const tollCostPerKm = tollCostPerYear / assumptions.annual_usage_km;

        const totalVariableCostPerKm = fuelCostPerKm + depreciationPerKm + fixedCostPerKm;

        return {
            fuel_cost_per_km: fuelCostPerKm,
            depreciation_per_km: depreciationPerKm,
            fixed_cost_per_km: fixedCostPerKm,
            toll_cost_per_km: tollCostPerKm,
            total_variable_cost_per_km: totalVariableCostPerKm,
            driver_hourly: vehicleCosts.driver_hourly,
        };
    }

    function applyKmRectification(cost, km, attConfig) {
        const maxAdd = attConfig.max_add;
        const decaySpeed = attConfig.decay_speed;
        const attSpeed = attConfig.att_speed;
        const threshold = attConfig.threshold;

        const expFactor = (100 + maxAdd * Math.exp(-1 * km / decaySpeed)) / 100;

        let attFactor;
        if (km < threshold) {
            attFactor = 1;
        } else {
            attFactor = (100 - (attSpeed / 100 * km) + (threshold * attSpeed / 100)) / 100;
        }

        return cost * expFactor * attFactor;
    }

    function calculate(inputs, config) {
        const km = parseFloat(inputs.mileage || 0);
        const extraKm = parseFloat(inputs.extra_km || 0);
        const hours = parseFloat(inputs.duration_hours || 0);
        const extensionHours = parseFloat(inputs.extension_hours || 0);
        const extraTravelHours = parseFloat(inputs.extra_travel_hours || 0);
        const toll = parseFloat(inputs.toll_cost || 0);
        const pax = parseInt(inputs.pax_count || 0);
        const expPerPax = parseFloat(inputs.experience_per_pax || 0);
        const seasonality = inputs.seasonality || 'average';
        const vehicleFill = inputs.vehicle_fill || 'car';
        const vatPct = parseFloat(inputs.vat_percentage || 10) / 100;
        const cardPct = parseFloat(inputs.card_fees_percentage || 5) / 100;
        const surchargePct = parseFloat(inputs.surcharge_percentage || 0) / 100;
        const travelCosts = parseFloat(inputs.travel_costs || 0);
        const serviceType = inputs.service_type || '';

        const costsPerKm = calculateCostsPerKm(config);
        const markups = config.pricing_markups;
        const seasonService = config.pricing_season_service;
        const vehicleService = config.pricing_vehicle_service;
        const seasonExp = config.pricing_season_experience;
        const vehicleExp = config.pricing_vehicle_experience;
        const attTransport = config.pricing_attenuation_transport;
        const attDriver = config.pricing_attenuation_driver;

        // 1. Base transport costs
        const fuelCost = km * costsPerKm.fuel_cost_per_km;
        const depreciationCost = km * costsPerKm.depreciation_per_km;
        const fixedCost = km * costsPerKm.fixed_cost_per_km;
        const driverCost = hours * costsPerKm.driver_hourly;
        const totalCostTransport = fuelCost + depreciationCost + fixedCost + driverCost + toll;

        // 2. Rectified transport cost
        const rectifiedCost = applyKmRectification(totalCostTransport, km, attTransport);

        // 3. Extra driver cost
        const extraDriverKmCost = extraKm * costsPerKm.total_variable_cost_per_km;
        const rectifiedExtraKm = applyKmRectification(extraDriverKmCost, extraKm, attDriver);

        // Extension hour cost
        let extensionCost = 0;
        if (extensionHours > 0) {
            const extensionMultipliers = config.pricing_extension;
            const vehicleExtMult = extensionMultipliers[vehicleFill] || 1.0;
            const seasonExtMult = extensionMultipliers[seasonality] || 1.0;
            extensionCost = extensionHours * costsPerKm.driver_hourly * vehicleExtMult * seasonExtMult;
        }

        // Extra travel hours cost
        let extraTravelCost = 0;
        if (extraTravelHours > 0) {
            extraTravelCost = extraTravelHours * costsPerKm.driver_hourly;
        }

        const totalExtraCost = Math.max(rectifiedExtraKm, extraTravelCost) + extensionCost;

        // 4. Transport price with markups
        const seasonMult = seasonService[seasonality] || 1.0;
        const vehicleMult = vehicleService[vehicleFill] || 1.0;
        const baseMarkup = markups.transport / 100;
        const tourMarkup = (serviceType !== 'TRF') ? (markups.tour / 100) : 0;
        const driverMarkup = markups.driver / 100;

        const taxableTransport = (rectifiedCost * (1 + baseMarkup * seasonMult) * vehicleMult
            * (1 + tourMarkup))
            + (totalExtraCost * (1 + driverMarkup));

        // 5. Experience price
        const expSeasonMult = seasonExp[seasonality] || 1.0;
        const expVehicleMult = vehicleExp[vehicleFill] || 1.0;
        const expMarkup = markups.experiences / 100;
        const totalExpCost = pax * expPerPax;
        const taxableExperience = totalExpCost * (1 + expMarkup * expSeasonMult) * expVehicleMult;

        // 6. Final price calculation
        // Round transport taxable up to nearest unit
        const taxableTransportRounded = Math.ceil(taxableTransport);
        const taxableExperienceRounded = round2(taxableExperience);

        const surchargeAmount = (taxableTransportRounded + taxableExperienceRounded) * surchargePct;
        const taxablePrice = taxableTransportRounded + taxableExperienceRounded + surchargeAmount + travelCosts;
        const taxablePriceRounded = Math.ceil(taxablePrice / 5) * 5;

        // VAT and CC Fees calculated on rounded taxable
        const vatAmount = taxablePriceRounded * vatPct;
        const ccFeesAmount = taxablePriceRounded * cardPct;
        const finalPrice = taxablePriceRounded + vatAmount + ccFeesAmount;
        const finalPriceRounded = Math.ceil(finalPrice / 5) * 5;

        // 7. Sconto e Deposito - uses override or calculated taxable as base
        const overrideTaxable = parseFloat(inputs.override_taxable);
        const imponibileBase = (overrideTaxable > 0) ? overrideTaxable : taxablePriceRounded;
        const discountPct = parseFloat(inputs.discount_percentage || 0);
        const discountedTaxable = discountPct > 0
            ? roundUpTo5(imponibileBase * (1 - discountPct / 100))
            : imponibileBase;
        const imponibile = discountedTaxable;
        const depositPct = parseFloat(inputs.deposit_percentage || 30);

        // Prezzi intermedi (come nella pagina Modifica Servizio)
        const prezzoConIva = imponibile * (100 + vatPct * 100) / 100;
        const prezzoConIvaECardFees = prezzoConIva * (100 + cardPct * 100) / 100;

        // Acconto Imponibile = Imponibile × Acconto% / 100 (arr. 5€)
        const depositTaxable = roundUpTo5(imponibile * depositPct / 100);

        // Acconto Handling Fees = Acconto Imponibile × (1 + IVA%) (arr. 5€)
        const depositHandlingFees = roundUpTo5(depositTaxable * (1 + vatPct));

        // Acconto Totale = Con IVA e Card Fees × Acconto% (arr. 5€)
        const depositTotal = roundUpTo5(prezzoConIvaECardFees * (depositPct / 100));

        // Saldo Imponibile = Imponibile - Acconto Imponibile
        const balanceTaxable = round2(imponibile - depositTaxable);

        // Saldo Handling Fees = Con IVA × (100 - Acconto%) / 100 (arr. 5€)
        const balanceHandlingFees = roundUpTo5(prezzoConIva * (100 - depositPct) / 100);

        // Saldo Card Fees = Con IVA e Card Fees × (100 - Acconto%) / 100 (arr. 5€)
        const balanceCardFees = roundUpTo5(prezzoConIvaECardFees * (100 - depositPct) / 100);

        return {
            taxable_transport: taxableTransportRounded,
            taxable_experience: taxableExperienceRounded,
            surcharge_amount: round2(surchargeAmount),
            taxable_price: round2(taxablePrice),
            taxable_price_rounded: taxablePriceRounded,
            vat_amount: round2(vatAmount),
            cc_fees_amount: round2(ccFeesAmount),
            final_price: round2(finalPrice),
            final_price_rounded: finalPriceRounded,
            discounted_taxable: discountedTaxable,
            discount_amount: round2(imponibileBase - discountedTaxable),
            deposit_percentage: depositPct,
            deposit_taxable: depositTaxable,
            deposit_handling_fees: depositHandlingFees,
            deposit_total: depositTotal,
            balance_taxable: balanceTaxable,
            balance_handling_fees: balanceHandlingFees,
            balance_card_fees: balanceCardFees,
        };
    }

    function round2(val) {
        return Math.round(val * 100) / 100;
    }

    function roundUpTo5(val) {
        return Math.ceil(val / 5) * 5;
    }

    function calculateQuoteTotals(params) {
        const itemsTaxableSum = parseFloat(params.items_taxable_sum || 0);
        const vatPct = parseFloat(params.vat_percentage || 10) / 100;
        const cardPct = parseFloat(params.card_fees_percentage || 5) / 100;
        const overrideTaxable = parseFloat(params.override_taxable || 0);
        const discountPct = parseFloat(params.discount_percentage || 0);
        const depositPct = parseFloat(params.deposit_percentage || 30);

        const taxablePrice = itemsTaxableSum;
        const taxablePriceRounded = roundUpTo5(taxablePrice);

        const vatAmount = taxablePriceRounded * vatPct;
        const ccFeesAmount = taxablePriceRounded * cardPct;
        const finalPrice = taxablePriceRounded + vatAmount + ccFeesAmount;
        const finalPriceRounded = roundUpTo5(finalPrice);

        const imponibileBase = (overrideTaxable > 0) ? overrideTaxable : taxablePriceRounded;
        const discountedTaxable = discountPct > 0
            ? roundUpTo5(imponibileBase * (1 - discountPct / 100))
            : imponibileBase;
        const imponibile = discountedTaxable;

        const prezzoConIva = imponibile * (100 + vatPct * 100) / 100;
        const prezzoConIvaECardFees = prezzoConIva * (100 + cardPct * 100) / 100;

        const depositTaxable = roundUpTo5(imponibile * depositPct / 100);
        const depositHandlingFees = roundUpTo5(depositTaxable * (1 + vatPct));
        const depositTotal = roundUpTo5(prezzoConIvaECardFees * (depositPct / 100));
        const balanceTaxable = round2(imponibile - depositTaxable);
        const balanceHandlingFees = roundUpTo5(prezzoConIva * (100 - depositPct) / 100);
        const balanceCardFees = roundUpTo5(prezzoConIvaECardFees * (100 - depositPct) / 100);

        return {
            taxable_price: round2(taxablePrice),
            taxable_price_rounded: taxablePriceRounded,
            vat_amount: round2(vatAmount),
            cc_fees_amount: round2(ccFeesAmount),
            final_price: round2(finalPrice),
            final_price_rounded: finalPriceRounded,
            discounted_taxable: discountedTaxable,
            discount_amount: round2(imponibileBase - discountedTaxable),
            deposit_percentage: depositPct,
            deposit_taxable: depositTaxable,
            deposit_handling_fees: depositHandlingFees,
            deposit_total: depositTotal,
            balance_taxable: balanceTaxable,
            balance_handling_fees: balanceHandlingFees,
            balance_card_fees: balanceCardFees,
        };
    }

    return {
        calculate,
        calculateQuoteTotals,
        calculateCostsPerKm,
        calculateDepreciationPerKm,
        applyKmRectification,
    };
}
