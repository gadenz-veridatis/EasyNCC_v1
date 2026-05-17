import moment from 'moment';

/**
 * Composable for service pricing calculations and accounting transaction generation.
 * Extracted from Form.vue to be reusable in Index.vue modal.
 */
export function useServicePricing() {

    const roundUpTo5 = (value) => Math.ceil(value / 5) * 5;

    /**
     * Calculate all deposit/balance fields from base pricing inputs.
     * Mutates the form object in place (same behavior as Form.vue calculateTotals).
     * Returns false if validation fails, true on success.
     */
    const calculateServiceTotals = (form) => {
        if (!form.service_price || form.service_price <= 0) return false;
        if (!form.vat_rate) return false;
        if (form.card_fees_percentage === null || form.card_fees_percentage === undefined) return false;
        if (form.deposit_percentage === null || form.deposit_percentage === undefined) return false;

        const imponibile = parseFloat(form.service_price);
        const vatRate = parseFloat(form.vat_rate);
        const cardFeesPerc = parseFloat(form.card_fees_percentage);
        const depositPerc = parseFloat(form.deposit_percentage);

        const prezzoConIva = imponibile * (100 + vatRate) / 100;
        const prezzoConIvaECardFees = prezzoConIva * (100 + cardFeesPerc) / 100;

        form.deposit_taxable = roundUpTo5(imponibile * depositPerc / 100);
        form.deposit_handling_fees = roundUpTo5(form.deposit_taxable * (1 + vatRate / 100));
        form.deposit_amount = roundUpTo5(prezzoConIvaECardFees * (depositPerc / 100));
        form.balance_taxable = parseFloat((imponibile - form.deposit_taxable).toFixed(2));
        form.balance_handling_fees = roundUpTo5(prezzoConIva * (100 - depositPerc) / 100);
        form.balance_card_fees = roundUpTo5(prezzoConIvaECardFees * (100 - depositPerc) / 100);

        return true;
    };

    /**
     * Build the accounting transaction operations array for the batch API.
     * Returns the operations array (does NOT call the API).
     */
    const buildAccountingOperations = (form, settings) => {
        if (!form.client_id || !settings) return [];

        const pickupDate = form.pickup_datetime
            ? moment(form.pickup_datetime).format('YYYY-MM-DD')
            : moment().format('YYYY-MM-DD');
        const clientId = form.client_id;
        const handlingFeesEntryId = settings.handling_fees_accounting_entry_id;
        const cardFeesEntryId = settings.card_fees_accounting_entry_id;

        const operations = [];

        // === ACCONTO VENDITA ===
        let depositSaleAmount;
        switch (form.deposit_sale_type) {
            case 'deposit_handling_fees': depositSaleAmount = form.deposit_handling_fees; break;
            case 'deposit_taxable': depositSaleAmount = form.deposit_taxable; break;
            default: depositSaleAmount = form.deposit_amount; // deposit_card_fees
        }

        if (depositSaleAmount && depositSaleAmount > 0) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'sale', installment: 'deposit', accounting_entry_id: settings.deposit_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: depositSaleAmount,
                    transaction_type: 'sale', installment: 'deposit',
                    accounting_entry_id: settings.deposit_accounting_entry_id,
                    counterpart_id: clientId, payment_type: 'carta_di_credito',
                    payment_reason: settings.deposit_reason, status: 'to_collect',
                }
            });
        }

        // === ACCONTO HANDLING FEES ===
        const accontoHandlingAmount = (form.deposit_handling_fees || 0) - (form.deposit_taxable || 0);
        if ((form.deposit_sale_type === 'deposit_handling_fees' || form.deposit_sale_type === 'deposit_card_fees')
            && accontoHandlingAmount > 0 && handlingFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: handlingFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(accontoHandlingAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'deposit',
                    accounting_entry_id: handlingFeesEntryId,
                    counterpart_id: clientId, payment_type: 'carta_di_credito',
                    payment_reason: settings.handling_fees_reason, status: 'to_pay',
                }
            });
        } else if (handlingFeesEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: handlingFeesEntryId }, data: {} });
        }

        // === ACCONTO CARD FEES ===
        const accontoCardAmount = (form.deposit_amount || 0) - (form.deposit_handling_fees || 0);
        if (form.deposit_sale_type === 'deposit_card_fees'
            && accontoCardAmount > 0 && cardFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: cardFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(accontoCardAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'deposit',
                    accounting_entry_id: cardFeesEntryId,
                    counterpart_id: clientId, payment_type: 'carta_di_credito',
                    payment_reason: settings.card_fees_reason, status: 'to_pay',
                }
            });
        } else if (cardFeesEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: cardFeesEntryId }, data: {} });
        }

        // === SALDO VENDITA ===
        let balanceAmount;
        switch (form.balance_sale_type) {
            case 'balance_handling_fees': balanceAmount = form.balance_handling_fees; break;
            case 'balance_card_fees': balanceAmount = form.balance_card_fees; break;
            default: balanceAmount = form.balance_taxable;
        }
        if (balanceAmount && balanceAmount > 0) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'sale', installment: 'balance', accounting_entry_id: settings.balance_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: balanceAmount,
                    transaction_type: 'sale', installment: 'balance',
                    accounting_entry_id: settings.balance_accounting_entry_id,
                    counterpart_id: clientId, payment_type: 'contanti',
                    payment_reason: settings.balance_reason, status: 'to_collect',
                }
            });
        }

        // === SALDO HANDLING FEES ===
        const saldoHandlingAmount = (form.balance_handling_fees || 0) - (form.balance_taxable || 0);
        if ((form.balance_sale_type === 'balance_handling_fees' || form.balance_sale_type === 'balance_card_fees')
            && saldoHandlingAmount > 0 && handlingFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: handlingFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(saldoHandlingAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: handlingFeesEntryId,
                    counterpart_id: clientId, payment_type: 'contanti',
                    payment_reason: settings.handling_fees_reason, status: 'to_pay',
                }
            });
        } else if (handlingFeesEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: handlingFeesEntryId }, data: {} });
        }

        // === SALDO CARD FEES ===
        const saldoCardAmount = (form.balance_card_fees || 0) - (form.balance_handling_fees || 0);
        if (form.balance_sale_type === 'balance_card_fees' && saldoCardAmount > 0 && cardFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: cardFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(saldoCardAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: cardFeesEntryId,
                    counterpart_id: clientId, payment_type: 'contanti',
                    payment_reason: settings.card_fees_reason, status: 'to_pay',
                }
            });
        } else if (cardFeesEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: cardFeesEntryId }, data: {} });
        }

        // === INTERMEDIAZIONE ===
        if (form.intermediary_commission && form.intermediary_commission > 0 && form.intermediary_id) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'intermediation', installment: 'balance', accounting_entry_id: settings.commission_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: form.intermediary_commission,
                    transaction_type: 'intermediation', installment: 'balance',
                    accounting_entry_id: settings.commission_accounting_entry_id,
                    counterpart_id: form.intermediary_id,
                    payment_reason: settings.commission_reason, status: 'to_pay',
                }
            });
        } else {
            operations.push({ action: 'delete', find_by: { transaction_type: 'intermediation', installment: 'balance', accounting_entry_id: settings.commission_accounting_entry_id }, data: {} });
        }

        // === CARBURANTE ===
        if (form.fuel_cost && form.fuel_cost > 0 && form.supplier_id) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: settings.fuel_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: form.fuel_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: settings.fuel_accounting_entry_id,
                    counterpart_id: form.supplier_id,
                    payment_reason: settings.fuel_reason, status: 'to_pay',
                }
            });
        } else {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: settings.fuel_accounting_entry_id }, data: {} });
        }

        // === COSTO DRIVER ===
        const driverCostEntryId = settings.driver_cost_accounting_entry_id;
        const firstDriverId = form.driver_ids && form.driver_ids.length > 0 ? form.driver_ids[0] : null;
        if (form.driver_compensation && form.driver_compensation > 0 && firstDriverId && driverCostEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: driverCostEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.driver_compensation,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: driverCostEntryId,
                    counterpart_id: firstDriverId,
                    payment_reason: settings.driver_cost_reason, status: 'to_pay',
                }
            });
        } else if (driverCostEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: driverCostEntryId }, data: {} });
        }

        // === COSTO COLLEGA ===
        const colleagueCostEntryId = settings.colleague_cost_accounting_entry_id;
        if (form.colleague_cost && form.colleague_cost > 0 && form.supplier_id && colleagueCostEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: colleagueCostEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.colleague_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: colleagueCostEntryId,
                    counterpart_id: form.supplier_id,
                    payment_reason: settings.colleague_cost_reason, status: 'to_pay',
                }
            });
        } else if (colleagueCostEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: colleagueCostEntryId }, data: {} });
        }

        // === PEDAGGI ===
        const tollEntryId = settings.toll_accounting_entry_id;
        if (form.toll_cost && form.toll_cost > 0 && form.supplier_id && tollEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: tollEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.toll_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: tollEntryId,
                    counterpart_id: form.supplier_id,
                    payment_reason: settings.toll_reason, status: 'to_pay',
                }
            });
        } else if (tollEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: tollEntryId }, data: {} });
        }

        // === PARCHEGGI ===
        const parkingEntryId = settings.parking_accounting_entry_id;
        if (form.parking_cost && form.parking_cost > 0 && form.supplier_id && parkingEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: parkingEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.parking_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: parkingEntryId,
                    counterpart_id: form.supplier_id,
                    payment_reason: settings.parking_reason, status: 'to_pay',
                }
            });
        } else if (parkingEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: parkingEntryId }, data: {} });
        }

        // === ALTRI COSTI VEICOLO ===
        const otherVehicleEntryId = settings.other_vehicle_accounting_entry_id;
        if (form.other_vehicle_costs && form.other_vehicle_costs > 0 && form.supplier_id && otherVehicleEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: otherVehicleEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.other_vehicle_costs,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: otherVehicleEntryId,
                    counterpart_id: form.supplier_id,
                    payment_reason: settings.other_vehicle_reason, status: 'to_pay',
                }
            });
        } else if (otherVehicleEntryId) {
            operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: otherVehicleEntryId }, data: {} });
        }

        // === COSTO ESPERIENZE ===
        const experienceEntryId = settings.experience_accounting_entry_id;
        if (experienceEntryId) {
            const accountableActivities = (form.activities || []).filter(a => a.should_account && parseFloat(a.cost) > 0);
            const totalExperienceCost = accountableActivities.reduce((sum, a) => sum + parseFloat(a.cost || 0), 0);
            const experienceSupplierId = accountableActivities.length > 0 ? accountableActivities[0].supplier_id : null;

            if (totalExperienceCost > 0) {
                operations.push({
                    action: 'upsert',
                    find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: experienceEntryId },
                    data: {
                        transaction_date: pickupDate, amount: Math.round(totalExperienceCost * 100) / 100,
                        transaction_type: 'purchase', installment: 'balance',
                        accounting_entry_id: experienceEntryId,
                        counterpart_id: experienceSupplierId,
                        payment_reason: settings.experience_reason, status: 'to_pay',
                    }
                });
            } else {
                operations.push({ action: 'delete', find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: experienceEntryId }, data: {} });
            }
        }

        return operations;
    };

    return {
        roundUpTo5,
        calculateServiceTotals,
        buildAccountingOperations,
    };
}
