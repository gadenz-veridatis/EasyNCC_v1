<template>
    <BModal :modelValue="show" @update:modelValue="$emit('update:show', $event)" title="Calcolatore Prezzi" hide-footer size="xl">
        <BRow>
            <BCol md="8">
                <!-- Dettagli Servizio -->
                <h6 class="mb-3"><i class="ri-route-line me-2"></i>Dettagli Servizio</h6>
                <BRow>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Destinazione</label>
                        <select v-model="calcForm.pricing_destination_id" class="form-select" @change="onDestinationChange">
                            <option :value="null">-- Personalizzato --</option>
                            <option v-for="dest in pricingDestinations" :key="dest.id" :value="dest.id">
                                {{ dest.destination }} - {{ dest.service_type }}
                            </option>
                        </select>
                    </BCol>
                    <BCol md="3" class="mb-3">
                        <label class="form-label">Tipo Servizio</label>
                        <select v-model="calcForm.service_type" class="form-select" @change="recalculate">
                            <option value="TRF">TRF</option>
                            <option value="TOUR HD">TOUR HD</option>
                            <option value="TOUR FD">TOUR FD</option>
                            <option value="TOUR FD+">TOUR FD+</option>
                        </select>
                    </BCol>
                    <BCol md="3" class="mb-3">
                        <label class="form-label">Nome Dest.</label>
                        <input v-model="calcForm.destination_name" type="text" class="form-control" />
                    </BCol>
                </BRow>
                <BRow>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Km</label>
                        <input v-model.number="calcForm.mileage" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Extra Km</label>
                        <input v-model.number="calcForm.extra_km" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Ore</label>
                        <input v-model.number="calcForm.duration_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Estensione</label>
                        <input v-model.number="calcForm.extension_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Ore Spost.</label>
                        <input v-model.number="calcForm.extra_travel_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Pedaggio</label>
                        <input v-model.number="calcForm.toll_cost" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                </BRow>
                <BRow>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Pax</label>
                        <input v-model.number="calcForm.pax_count" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Esp. p.p. (&euro;)</label>
                        <input v-model.number="calcForm.experience_per_pax" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                </BRow>

                <hr />

                <!-- Politiche di Pricing -->
                <h6 class="mb-3"><i class="ri-settings-3-line me-2"></i>Politiche di Pricing</h6>
                <BRow>
                    <BCol md="4" class="mb-3">
                        <label class="form-label">Stagionalit&agrave;</label>
                        <div class="d-flex gap-2">
                            <div v-for="opt in ['low', 'average', 'high']" :key="opt" class="form-check">
                                <input :id="'calc_season_' + opt" v-model="calcForm.seasonality" type="radio" class="form-check-input" :value="opt" @change="recalculate" />
                                <label :for="'calc_season_' + opt" class="form-check-label text-capitalize">{{ opt }}</label>
                            </div>
                        </div>
                    </BCol>
                    <BCol md="5" class="mb-3">
                        <label class="form-label">Riempimento Veicolo</label>
                        <div class="d-flex gap-2">
                            <div v-for="opt in vehicleFillOptions" :key="opt.value" class="form-check">
                                <input :id="'calc_fill_' + opt.value" v-model="calcForm.vehicle_fill" type="radio" class="form-check-input" :value="opt.value" @change="recalculate" />
                                <label :for="'calc_fill_' + opt.value" class="form-check-label">{{ opt.label }}</label>
                            </div>
                        </div>
                    </BCol>
                </BRow>
                <BRow>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">VAT (%)</label>
                        <select v-model.number="calcForm.vat_percentage" class="form-select" @change="recalculate">
                            <option :value="10">10%</option>
                            <option :value="22">22%</option>
                        </select>
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Card Fees (%)</label>
                        <input v-model.number="calcForm.card_fees_percentage" type="number" class="form-control" min="0" max="100" step="0.5" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Maggiorazioni (%)</label>
                        <input v-model.number="calcForm.surcharge_percentage" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                    </BCol>
                    <BCol md="2" class="mb-3">
                        <label class="form-label">Trasferta (&euro;)</label>
                        <input v-model.number="calcForm.travel_costs" type="number" class="form-control" min="0" step="5" @input="recalculate" />
                    </BCol>
                </BRow>
            </BCol>

            <BCol md="4">
                <!-- Risultato -->
                <div class="border rounded p-3 bg-light sticky-top" style="top: 0;">
                    <h6 class="mb-3"><i class="ri-calculator-line me-2"></i>Risultato</h6>
                    <div class="mb-2">
                        <small class="text-muted">Imponibile Trasporto</small>
                        <div class="fw-semibold">{{ formatCurrency(calcResult.taxable_transport) }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Imponibile Esperienze</small>
                        <div class="fw-semibold">{{ formatCurrency(calcResult.taxable_experience) }}</div>
                    </div>
                    <div v-if="calcResult.surcharge_amount > 0" class="mb-2">
                        <small class="text-muted">Maggiorazione</small>
                        <div class="fw-semibold">{{ formatCurrency(calcResult.surcharge_amount) }}</div>
                    </div>
                    <hr />
                    <div class="mb-3">
                        <small class="text-muted">Imponibile Totale (arr. 5&euro;)</small>
                        <div class="fs-4 fw-bold text-primary">{{ formatCurrency(calcResult.taxable_price_rounded) }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Prezzo Finale (arr. 5&euro;)</small>
                        <div class="fs-5 fw-bold text-success">{{ formatCurrency(calcResult.final_price_rounded) }}</div>
                    </div>
                    <hr />
                    <button type="button" class="btn btn-primary w-100" @click="applyResult">
                        <i class="ri-check-line me-1"></i>Applica {{ formatCurrency(calcResult.taxable_price_rounded) }}
                    </button>
                </div>
            </BCol>
        </BRow>
    </BModal>
</template>

<script>
import { usePricingCalculator } from "@/composables/usePricingCalculator.js";

export default {
    props: {
        show: { type: Boolean, default: false },
        pricingDestinations: { type: Array, default: () => [] },
        pricingConfig: { type: Object, default: null },
        initialData: { type: Object, default: () => ({}) },
        vatPercentage: { type: Number, default: 10 },
        cardFeesPercentage: { type: Number, default: 5 },
    },
    emits: ['update:show', 'apply'],
    data() {
        return {
            vehicleFillOptions: [
                { value: 'car', label: 'Car (2 pax)' },
                { value: 'van', label: 'Van (3-6 pax)' },
                { value: 'full', label: 'Full (7-8 pax)' },
            ],
            calcForm: {},
            calcResult: {
                taxable_transport: 0,
                taxable_experience: 0,
                surcharge_amount: 0,
                taxable_price: 0,
                taxable_price_rounded: 0,
                vat_amount: 0,
                cc_fees_amount: 0,
                final_price: 0,
                final_price_rounded: 0,
            },
        };
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.initForm();
            }
        },
    },
    methods: {
        initForm() {
            const d = this.initialData || {};
            this.calcForm = {
                pricing_destination_id: d.pricing_destination_id || null,
                destination_name: d.destination_name || '',
                service_type: d.service_type || 'TRF',
                mileage: d.mileage || 0,
                extra_km: d.extra_km || 0,
                duration_hours: d.duration_hours || 0,
                extension_hours: d.extension_hours || 0,
                extra_travel_hours: d.extra_travel_hours || 0,
                toll_cost: d.toll_cost || 0,
                pax_count: d.pax_count || 0,
                experience_per_pax: d.experience_per_pax || 0,
                seasonality: 'average',
                vehicle_fill: 'car',
                vat_percentage: this.vatPercentage || 10,
                card_fees_percentage: this.cardFeesPercentage || 5,
                surcharge_percentage: 0,
                travel_costs: 0,
            };
            this.recalculate();
        },
        onDestinationChange() {
            if (!this.calcForm.pricing_destination_id) return;
            const dest = this.pricingDestinations.find(d => d.id === this.calcForm.pricing_destination_id);
            if (!dest) return;
            this.calcForm.destination_name = dest.destination;
            this.calcForm.service_type = dest.service_type;
            this.calcForm.mileage = dest.mileage_km || 0;
            this.calcForm.duration_hours = dest.duration_hours || 0;
            this.calcForm.toll_cost = dest.toll_cost || 0;
            this.calcForm.experience_per_pax = dest.experience_a || 0;
            this.recalculate();
        },
        recalculate() {
            if (!this.pricingConfig) return;
            const { calculate } = usePricingCalculator();
            this.calcResult = calculate(this.calcForm, this.pricingConfig);
        },
        applyResult() {
            this.$emit('apply', {
                pricing_destination_id: this.calcForm.pricing_destination_id,
                destination_name: this.calcForm.destination_name,
                service_type: this.calcForm.service_type,
                mileage: this.calcForm.mileage,
                extra_km: this.calcForm.extra_km,
                duration_hours: this.calcForm.duration_hours,
                extension_hours: this.calcForm.extension_hours,
                extra_travel_hours: this.calcForm.extra_travel_hours,
                toll_cost: this.calcForm.toll_cost,
                pax_count: this.calcForm.pax_count,
                experience_per_pax: this.calcForm.experience_per_pax,
                taxable_price: this.calcResult.taxable_price_rounded,
            });
            this.$emit('update:show', false);
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '\u20AC 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
    },
};
</script>
