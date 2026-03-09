<template>
    <Head :title="isEditing ? 'Modifica Preventivo' : 'Nuovo Preventivo'" />

    <Layout>
        <PageHeader
            :title="isEditing ? 'Modifica Preventivo' : 'Nuovo Preventivo'"
            pageTitle="Preventivi"
        />

        <BRow>
            <BCol lg="12">
                <form @submit.prevent="saveQuote">
                    <!-- Sezione 1: Dati Cliente e Servizio -->
                    <BCard no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                <i class="ri-user-line me-2"></i>Dati Cliente e Servizio
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label">Nome Cliente <span class="text-danger">*</span></label>
                                    <input v-model="form.client_name" type="text" class="form-control" placeholder="Nome del cliente" />
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Data Servizio</label>
                                    <input v-model="form.service_date" type="date" class="form-control" />
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="12" class="mb-3">
                                    <label class="form-label">Note</label>
                                    <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Note aggiuntive"></textarea>
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione 2: Dettagli Servizio -->
                    <BCard no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                <i class="ri-route-line me-2"></i>Dettagli Servizio
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label">Destinazione <span class="text-danger">*</span></label>
                                    <select v-model="selectedDestinationId" class="form-select" @change="onDestinationChange">
                                        <option :value="null">-- Personalizzato --</option>
                                        <option v-for="dest in destinations" :key="dest.id" :value="dest.id">
                                            {{ dest.destination }} - {{ dest.service_type }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Tipo Servizio <span class="text-danger">*</span></label>
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
                                    <label class="form-label">Kilometraggio <span class="text-danger">*</span></label>
                                    <input v-model.number="form.mileage" type="number" class="form-control" min="0" step="1" :placeholder="destDefaults.mileage_km" @input="recalculate" />
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Extra Km</label>
                                    <input v-model.number="form.extra_km" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Durata (ore) <span class="text-danger">*</span></label>
                                    <input v-model.number="form.duration_hours" type="number" class="form-control" min="0" step="0.5" :placeholder="destDefaults.duration_hours" @input="recalculate" />
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
                                    <input v-model.number="form.toll_cost" type="number" class="form-control" min="0" step="1" :placeholder="destDefaults.toll_cost" @input="recalculate" />
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Pax</label>
                                    <input v-model.number="form.pax_count" type="number" class="form-control" min="0" step="1" @input="recalculate" />
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Esperienza p.p. (€)</label>
                                    <input v-model.number="form.experience_per_pax" type="number" class="form-control" min="0" step="1" :placeholder="destDefaults.experience_a" @input="recalculate" />
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione 3: Politiche di Pricing -->
                    <BCard no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                <i class="ri-settings-3-line me-2"></i>Politiche di Pricing
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Stagionalità <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div v-for="opt in ['low', 'average', 'high']" :key="opt" class="form-check">
                                            <input
                                                :id="'season_' + opt"
                                                v-model="form.seasonality"
                                                type="radio"
                                                class="form-check-input"
                                                :value="opt"
                                                @change="recalculate"
                                            />
                                            <label :for="'season_' + opt" class="form-check-label text-capitalize">{{ opt }}</label>
                                        </div>
                                    </div>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label">Riempimento Veicolo <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div v-for="opt in vehicleFillOptions" :key="opt.value" class="form-check">
                                            <input
                                                :id="'fill_' + opt.value"
                                                v-model="form.vehicle_fill"
                                                type="radio"
                                                class="form-check-input"
                                                :value="opt.value"
                                                @change="recalculate"
                                            />
                                            <label :for="'fill_' + opt.value" class="form-check-label">{{ opt.label }}</label>
                                        </div>
                                    </div>
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">VAT (%) <span class="text-danger">*</span></label>
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

                    <!-- Sezione 4: Risultato Prezzo -->
                    <BCard no-body class="mb-3">
                        <BCardHeader class="bg-light">
                            <h6 class="card-title mb-0">
                                <i class="ri-calculator-line me-2"></i>Risultato Prezzo
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Imponibile Trasporto</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.taxable_transport) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Imponibile Esperienze</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.taxable_experience) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Maggiorazione</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.surcharge_amount) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Trasferta</label>
                                    <div class="fw-semibold">{{ formatCurrency(form.travel_costs || 0) }}</div>
                                </BCol>
                            </BRow>
                            <hr />
                            <BRow>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Imponibile Totale (arr. 5€)</label>
                                    <div class="fs-5 fw-bold text-primary">{{ formatCurrency(result.taxable_price_rounded) }}</div>
                                </BCol>
                                <BCol md="2" class="mb-2">
                                    <label class="form-label small text-muted">VAT</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.vat_amount) }}</div>
                                </BCol>
                                <BCol md="2" class="mb-2">
                                    <label class="form-label small text-muted">CC Fees</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.cc_fees_amount) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Prezzo Finale (arr. 5€)</label>
                                    <div class="fs-5 fw-bold text-success">{{ formatCurrency(result.final_price_rounded) }}</div>
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione 5: Sconto e Deposito -->
                    <BCard no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                <i class="ri-money-euro-circle-line me-2"></i>Prezzi di Vendita
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Prezzo Imponibile Totale (€)</label>
                                    <input v-model.number="form.override_taxable" type="number" class="form-control fw-bold text-primary" min="0" step="5" @input="onOverrideTaxableChange" />
                                    <small class="text-muted">Calcolato: {{ formatCurrency(result.taxable_price_rounded) }}</small>
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Sconto (%)</label>
                                    <input v-model.number="form.discount_percentage" type="number" class="form-control" min="0" max="100" step="1" @input="recalculate" />
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Promozione <span v-if="form.discount_percentage > 0" class="text-danger">*</span></label>
                                    <input v-model="form.discount_name" type="text" class="form-control" placeholder="Nome promozione" />
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Acconto (%)</label>
                                    <input v-model.number="form.deposit_percentage" type="number" class="form-control" min="0" max="100" step="5" @input="recalculate" />
                                </BCol>
                            </BRow>
                            <!-- Imponibile scontato -->
                            <BRow v-if="form.discount_percentage > 0">
                                <BCol md="3" class="mb-3">
                                    <label class="form-label small text-muted">Imponibile Scontato €</label>
                                    <div class="fs-5 fw-bold text-primary">{{ formatCurrency(result.discounted_taxable) }}</div>
                                    <small class="text-muted">Imponibile × (1 - Sconto%)</small>
                                </BCol>
                            </BRow>
                            <!-- Riga Acconto -->
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Imponibile €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_taxable) }}</div>
                                    <small class="text-muted">Imponibile × Acconto% / 100 (arr. 5€)</small>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Handling Fees €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_handling_fees) }}</div>
                                    <small class="text-muted">Acconto Imponibile × (1 + IVA%) (arr. 5€)</small>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Totale €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_total) }}</div>
                                    <small class="text-muted">Con IVA e Card Fees × Acconto% (arr. 5€)</small>
                                </BCol>
                            </BRow>
                            <!-- Riga Saldo -->
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Imponibile €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_taxable) }}</div>
                                    <small class="text-muted">Imponibile - Acconto Imponibile</small>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Handling Fees €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_handling_fees) }}</div>
                                    <small class="text-muted">Con IVA × (100 - Acconto%) (arr. 5€)</small>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Card Fees €</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_card_fees) }}</div>
                                    <small class="text-muted">Con IVA e Card Fees × (100 - Acconto%) (arr. 5€)</small>
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Errors & Actions -->
                    <div v-if="errors.length > 0" class="alert alert-danger">
                        <ul class="mb-0">
                            <li v-for="(error, idx) in errors" :key="idx">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between sticky-bottom bg-white py-3 px-3 border-top" style="z-index: 10; margin: 0 -1rem;">
                        <Link href="/easyncc/quotes" class="btn btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i>Torna alla Lista
                        </Link>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="ri-save-line me-1"></i>
                            {{ isEditing ? 'Aggiorna Preventivo' : 'Salva Preventivo' }}
                        </button>
                    </div>
                </form>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import { usePricingCalculator } from "@/composables/usePricingCalculator.js";

export default {
    components: { Head, Link, Layout, PageHeader },
    props: {
        quote: { type: Object, default: null },
    },
    data() {
        return {
            loading: true,
            saving: false,
            errors: [],
            destinations: [],
            selectedDestinationId: null,
            destDefaults: {},
            pricingConfig: null,
            vehicleFillOptions: [
                { value: 'car', label: 'Car (2 pax)' },
                { value: 'van', label: 'Van (3-6 pax)' },
                { value: 'full', label: 'Full (7-8 pax)' },
            ],
            form: {
                client_name: '',
                service_date: '',
                notes: '',
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
                override_taxable: null,
                discount_percentage: 0,
                discount_name: '',
                deposit_percentage: 30,
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
                discounted_taxable: 0,
                deposit_percentage: 30,
                deposit_taxable: 0,
                deposit_handling_fees: 0,
                deposit_total: 0,
                balance_taxable: 0,
                balance_handling_fees: 0,
                balance_card_fees: 0,
            },
        };
    },
    computed: {
        isEditing() {
            return !!this.quote;
        },
    },
    async mounted() {
        await Promise.all([
            this.loadSettings(),
            this.loadDestinations(),
        ]);

        if (this.quote) {
            this.populateForm(this.quote);
        }

        this.recalculate();
        this.loading = false;
    },
    methods: {
        async loadSettings() {
            try {
                const response = await axios.get('/api/settings/public');
                const data = response.data;
                this.pricingConfig = {
                    pricing_markups: data.pricing_markups || {},
                    pricing_vehicle_costs: data.pricing_vehicle_costs || {},
                    pricing_vehicle_assumptions: data.pricing_vehicle_assumptions || {},
                    pricing_annual_expenses: data.pricing_annual_expenses || {},
                    pricing_season_service: data.pricing_season_service || {},
                    pricing_vehicle_service: data.pricing_vehicle_service || {},
                    pricing_season_experience: data.pricing_season_experience || {},
                    pricing_vehicle_experience: data.pricing_vehicle_experience || {},
                    pricing_attenuation_transport: data.pricing_attenuation_transport || {},
                    pricing_attenuation_driver: data.pricing_attenuation_driver || {},
                    pricing_extension: data.pricing_extension || {},
                    pricing_depreciation: data.pricing_depreciation || [0.22, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2],
                    pricing_toll: data.pricing_toll || {},
                };

                // Load defaults for deposit and card fees
                if (data.deposit_percentage) this.form.deposit_percentage = data.deposit_percentage;
                if (data.card_fees_percentage) this.form.card_fees_percentage = data.card_fees_percentage;
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        },
        async loadDestinations() {
            try {
                const response = await axios.get('/api/pricing-destinations');
                this.destinations = response.data.data || [];
            } catch (error) {
                console.error('Error loading destinations:', error);
            }
        },
        populateForm(quote) {
            const fields = [
                'client_name', 'service_date', 'notes',
                'destination_name', 'service_type', 'mileage', 'extra_km',
                'duration_hours', 'extension_hours', 'extra_travel_hours', 'toll_cost',
                'pax_count', 'experience_per_pax', 'seasonality', 'vehicle_fill',
                'vat_percentage', 'card_fees_percentage', 'surcharge_percentage',
                'travel_costs', 'override_taxable', 'discount_percentage', 'discount_name',
                'deposit_percentage',
            ];
            fields.forEach(f => {
                if (quote[f] !== null && quote[f] !== undefined) {
                    this.form[f] = quote[f];
                }
            });

            // Ensure vat_percentage is a number for the select to match
            if (this.form.vat_percentage) {
                this.form.vat_percentage = parseFloat(this.form.vat_percentage);
            }

            // Match destination by name and service_type
            if (quote.destination_name) {
                const match = this.destinations.find(d =>
                    d.destination === quote.destination_name && d.service_type === quote.service_type
                );
                if (match) {
                    this.selectedDestinationId = match.id;
                    this.destDefaults = { ...match };
                }
            }
        },
        onDestinationChange() {
            if (!this.selectedDestinationId) {
                this.destDefaults = {};
                return;
            }
            const dest = this.destinations.find(d => d.id === this.selectedDestinationId);
            if (!dest) return;

            this.destDefaults = { ...dest };
            this.form.destination_name = dest.destination;
            this.form.service_type = dest.service_type;
            this.form.mileage = dest.mileage_km || 0;
            this.form.duration_hours = dest.duration_hours || 0;
            this.form.toll_cost = dest.toll_cost || 0;
            this.form.experience_per_pax = dest.experience_a || 0;
            this.recalculate();
        },
        onOverrideTaxableChange() {
            this.recalculate();
        },
        recalculate() {
            if (!this.pricingConfig) return;

            const { calculate } = usePricingCalculator();
            this.result = calculate(this.form, this.pricingConfig);
        },
        async saveQuote() {
            this.saving = true;
            this.errors = [];

            // Client-side validation
            if (this.form.discount_percentage > 0 && !this.form.discount_name) {
                this.errors = ['Il nome della promozione è obbligatorio quando lo sconto è maggiore di zero.'];
                this.saving = false;
                return;
            }

            try {
                const payload = { ...this.form };

                if (this.isEditing) {
                    await axios.put(`/api/quotes/${this.quote.id}`, payload);
                } else {
                    await axios.post('/api/quotes', payload);
                }

                window.location.href = '/easyncc/quotes';
            } catch (error) {
                console.error('Error saving quote:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = [error.response?.data?.message || 'Errore durante il salvataggio'];
                }
            } finally {
                this.saving = false;
            }
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '€ 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
    },
};
</script>
