<template>
    <Head title="Calcolatore Prezzi" />

    <Layout>
        <PageHeader title="Calcolatore Prezzi" pageTitle="Preventivi" />

        <BRow>
            <BCol lg="8">
                <!-- Dettagli Servizio -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="card-title mb-0">
                            <i class="ri-route-line me-2"></i>Dettagli Servizio
                        </h6>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="4" class="mb-3">
                                <label class="form-label">Destinazione</label>
                                <select v-model="selectedDestinationId" class="form-select" @change="onDestinationChange">
                                    <option :value="null">-- Personalizzato --</option>
                                    <option v-for="dest in pricingDestinations" :key="dest.id" :value="dest.id">
                                        {{ dest.destination }} - {{ dest.service_type }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Tipo Servizio</label>
                                <select v-model="form.service_type" class="form-select" @change="recalculate">
                                    <option value="TRF">TRF</option>
                                    <option value="TOUR HD">TOUR HD</option>
                                    <option value="TOUR FD">TOUR FD</option>
                                    <option value="TOUR FD+">TOUR FD+</option>
                                </select>
                            </BCol>
                            <BCol md="3" class="mb-3">
                                <label class="form-label">Nome Destinazione</label>
                                <input v-model="form.destination_name" type="text" class="form-control" placeholder="Es. Chianti Classico" />
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Kilometraggio</label>
                                <input v-model.number="form.mileage" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Extra Km</label>
                                <input v-model.number="form.extra_km" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Durata (ore)</label>
                                <input v-model.number="form.duration_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Estensione (ore)</label>
                                <input v-model.number="form.extension_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Ore Spostam. Extra</label>
                                <input v-model.number="form.extra_travel_hours" type="number" class="form-control" min="0" step="0.5" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Pedaggio (€)</label>
                                <input v-model.number="form.toll_cost" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Pax</label>
                                <input v-model.number="form.pax_count" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Esperienza p.p. (€)</label>
                                <input v-model.number="form.experience_per_pax" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Politiche di Pricing -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="card-title mb-0">
                            <i class="ri-settings-3-line me-2"></i>Politiche di Pricing
                        </h6>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="3" class="mb-3">
                                <label class="form-label">Stagionalità</label>
                                <div class="d-flex gap-2">
                                    <div v-for="opt in ['low', 'average', 'high']" :key="opt" class="form-check">
                                        <input :id="'season_' + opt" v-model="form.seasonality" type="radio" class="form-check-input" :value="opt" @change="recalculate" />
                                        <label :for="'season_' + opt" class="form-check-label text-capitalize">{{ opt }}</label>
                                    </div>
                                </div>
                            </BCol>
                            <BCol md="4" class="mb-3">
                                <label class="form-label">Riempimento Veicolo</label>
                                <div class="d-flex gap-2">
                                    <div v-for="opt in vehicleFillOptions" :key="opt.value" class="form-check">
                                        <input :id="'fill_' + opt.value" v-model="form.vehicle_fill" type="radio" class="form-check-input" :value="opt.value" @change="recalculate" />
                                        <label :for="'fill_' + opt.value" class="form-check-label">{{ opt.label }}</label>
                                    </div>
                                </div>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">VAT (%)</label>
                                <select v-model.number="form.vat_percentage" class="form-select" @change="recalculate">
                                    <option :value="10">10%</option>
                                    <option :value="22">22%</option>
                                </select>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Card Fees (%)</label>
                                <input v-model.number="form.card_fees_percentage" type="number" class="form-control" min="0" max="100" step="0.5" @input="recalculate" />
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Maggiorazioni (%)</label>
                                <input v-model.number="form.surcharge_percentage" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label">Trasferta (€)</label>
                                <input v-model.number="form.travel_costs" type="number" class="form-control" min="0" step="5" @input="recalculate" />
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>
            </BCol>

            <BCol lg="4">
                <!-- Risultato Prezzo -->
                <BCard no-body class="mb-3 sticky-top" style="top: 1rem; z-index: 5;">
                    <BCardHeader class="bg-light">
                        <h6 class="card-title mb-0">
                            <i class="ri-calculator-line me-2"></i>Risultato Prezzo
                        </h6>
                    </BCardHeader>
                    <BCardBody>
                        <div class="mb-2">
                            <small class="text-muted">Imponibile Trasporto</small>
                            <div class="fw-semibold">{{ formatCurrency(result.taxable_transport) }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Imponibile Esperienze</small>
                            <div class="fw-semibold">{{ formatCurrency(result.taxable_experience) }}</div>
                        </div>
                        <div v-if="result.surcharge_amount > 0" class="mb-2">
                            <small class="text-muted">Maggiorazione</small>
                            <div class="fw-semibold">{{ formatCurrency(result.surcharge_amount) }}</div>
                        </div>
                        <div v-if="form.travel_costs > 0" class="mb-2">
                            <small class="text-muted">Trasferta</small>
                            <div class="fw-semibold">{{ formatCurrency(form.travel_costs) }}</div>
                        </div>
                        <hr />
                        <div class="mb-2">
                            <small class="text-muted">Imponibile Totale (arr. 5€)</small>
                            <div class="fs-4 fw-bold text-primary">{{ formatCurrency(result.taxable_price_rounded) }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">VAT</small>
                            <span class="fw-semibold ms-2">{{ formatCurrency(result.vat_amount) }}</span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">CC Fees</small>
                            <span class="fw-semibold ms-2">{{ formatCurrency(result.cc_fees_amount) }}</span>
                        </div>
                        <hr />
                        <div>
                            <small class="text-muted">Prezzo Finale (arr. 5€)</small>
                            <div class="fs-4 fw-bold text-success">{{ formatCurrency(result.final_price_rounded) }}</div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { usePricingCalculator } from "@/composables/usePricingCalculator.js";

export default {
    components: { Head, Layout, PageHeader },
    props: {
        pricingDestinations: { type: Array, default: () => [] },
        pricingConfig: { type: Object, default: null },
    },
    data() {
        return {
            selectedDestinationId: null,
            vehicleFillOptions: [
                { value: 'car', label: 'Car (2 pax)' },
                { value: 'van', label: 'Van (3-6 pax)' },
                { value: 'full', label: 'Full (7-8 pax)' },
            ],
            form: {
                destination_name: '',
                service_type: 'TRF',
                mileage: 0,
                extra_km: 0,
                duration_hours: 0,
                extension_hours: 0,
                extra_travel_hours: 0,
                toll_cost: 0,
                pax_count: 0,
                experience_per_pax: 0,
                seasonality: 'average',
                vehicle_fill: 'car',
                vat_percentage: 10,
                card_fees_percentage: 5,
                surcharge_percentage: 0,
                travel_costs: 0,
            },
            result: {
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
    mounted() {
        this.recalculate();
    },
    methods: {
        onDestinationChange() {
            if (!this.selectedDestinationId) return;
            const dest = this.pricingDestinations.find(d => d.id === this.selectedDestinationId);
            if (!dest) return;
            this.form.destination_name = dest.destination;
            this.form.service_type = dest.service_type;
            this.form.mileage = dest.mileage_km || 0;
            this.form.duration_hours = dest.duration_hours || 0;
            this.form.toll_cost = dest.toll_cost || 0;
            this.form.experience_per_pax = dest.experience_a || 0;
            this.recalculate();
        },
        recalculate() {
            if (!this.pricingConfig) return;
            const { calculate } = usePricingCalculator();
            this.result = calculate(this.form, this.pricingConfig);
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '€ 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
    },
};
</script>
