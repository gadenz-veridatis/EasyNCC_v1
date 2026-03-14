<template>
    <Head title="Dettaglio Preventivo" />

    <Layout>
        <PageHeader title="Dettaglio Preventivo" pageTitle="Preventivi" />

        <BRow>
            <BCol lg="12">
                <!-- Workflow Stepper -->
                <BCard no-body class="mb-3">
                    <BCardBody>
                        <div class="d-flex justify-content-between align-items-center position-relative" style="padding: 0 2rem;">
                            <!-- Line behind steps -->
                            <div class="position-absolute" style="top: 50%; left: 2rem; right: 2rem; height: 2px; background: #e9ecef; z-index: 0;"></div>
                            <div v-for="(step, idx) in steps" :key="step.key" class="text-center position-relative" style="z-index: 1;">
                                <div
                                    class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                                    :class="stepClass(idx)"
                                    style="width: 40px; height: 40px;"
                                >
                                    <i v-if="idx < currentStepIndex" class="ri-check-line"></i>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>
                                <small :class="idx <= currentStepIndex ? 'fw-semibold' : 'text-muted'">{{ step.label }}</small>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>

                <!-- Status Info Banners -->
                <BCard v-if="quoteData.status === 'sent'" no-body class="mb-3 border-info">
                    <BCardBody class="text-center">
                        <div class="alert alert-info mb-0">
                            <i class="ri-time-line me-2"></i>
                            <strong>In attesa di pagamento</strong>
                            <div class="mt-1">
                                <small>Email inviata il {{ formatDateTime(quoteData.sent_at) }}</small>
                                <br v-if="quoteData.sumup_checkout_url" />
                                <small v-if="quoteData.sumup_checkout_url">
                                    Link pagamento:
                                    <a :href="quoteData.sumup_checkout_url" target="_blank">{{ quoteData.sumup_checkout_url }}</a>
                                </small>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>

                <BCard v-if="quoteData.status === 'deposit_received'" no-body class="mb-3 border-success">
                    <BCardBody class="text-center">
                        <div class="alert alert-success mb-0">
                            <i class="ri-checkbox-circle-line me-2 fs-4"></i>
                            <strong>Acconto ricevuto il {{ formatDateTime(quoteData.deposit_received_at) }}</strong>
                            <div v-if="quoteData.service_id" class="mt-2">
                                <Link :href="`/easyncc/services/${quoteData.service_id}`" class="btn btn-success">
                                    <i class="ri-arrow-right-line me-1"></i>Vai al Servizio #{{ quoteData.service_id }}
                                </Link>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>

                <!-- Dati Cliente e Servizio -->
                <BCard no-body class="mb-3">
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ri-user-line me-2"></i>Preventivo #{{ quoteData.id }}
                            <span class="badge ms-2" :class="`bg-${getStatusColor(quoteData.status)}`">
                                {{ getStatusLabel(quoteData.status) }}
                            </span>
                        </h6>
                        <div class="d-flex gap-2">
                            <Link :href="`/easyncc/quotes/${quoteData.id}/edit`" class="btn btn-sm btn-outline-primary">
                                <i class="ri-pencil-line me-1"></i>Modifica
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Cliente</label>
                                <div class="fw-semibold">{{ quoteData.client_name || '-' }}</div>
                            </BCol>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Email Cliente</label>
                                <div class="fw-semibold">{{ quoteData.client_email || '-' }}</div>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label small text-muted">Data Servizio</label>
                                <div class="fw-semibold">{{ formatDate(quoteData.service_date) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label small text-muted">Destinazione</label>
                                <div class="fw-semibold">{{ quoteData.destination_name || '-' }}</div>
                            </BCol>
                            <BCol md="2" class="mb-3">
                                <label class="form-label small text-muted">Tipo Servizio</label>
                                <div>
                                    <span class="badge bg-info-subtle text-info">{{ quoteData.service_type || '-' }}</span>
                                </div>
                            </BCol>
                        </BRow>
                        <BRow v-if="quoteData.notes">
                            <BCol md="12" class="mb-3">
                                <label class="form-label small text-muted">Note</label>
                                <div>{{ quoteData.notes }}</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Dettagli Servizio -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="card-title mb-0">
                            <i class="ri-route-line me-2"></i>Dettagli Servizio
                        </h6>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Km</label>
                                <div class="fw-semibold">{{ quoteData.mileage }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Extra Km</label>
                                <div class="fw-semibold">{{ quoteData.extra_km }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Durata (ore)</label>
                                <div class="fw-semibold">{{ quoteData.duration_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Estensione (ore)</label>
                                <div class="fw-semibold">{{ quoteData.extension_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Ore Spost. Extra</label>
                                <div class="fw-semibold">{{ quoteData.extra_travel_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Pedaggio</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.toll_cost) }}</div>
                            </BCol>
                        </BRow>
                        <BRow class="mt-2">
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Pax</label>
                                <div class="fw-semibold">{{ quoteData.pax_count }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Esperienza p.p.</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.experience_per_pax) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Stagionalità</label>
                                <div class="fw-semibold text-capitalize">{{ quoteData.seasonality }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Riempimento</label>
                                <div class="fw-semibold text-capitalize">{{ quoteData.vehicle_fill }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">VAT</label>
                                <div class="fw-semibold">{{ quoteData.vat_percentage }}%</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Card Fees</label>
                                <div class="fw-semibold">{{ quoteData.card_fees_percentage }}%</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Risultato Prezzo -->
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
                                <div class="fw-semibold">{{ formatCurrency(quoteData.taxable_transport) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Esperienze</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.taxable_experience) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Maggiorazione</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.surcharge_amount) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Trasferta</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.travel_costs) }}</div>
                            </BCol>
                        </BRow>
                        <hr />
                        <BRow>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Totale</label>
                                <div class="fs-5 fw-bold text-primary">{{ formatCurrency(quoteData.taxable_price_rounded) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">VAT</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.vat_amount) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">CC Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.cc_fees_amount) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Prezzo Finale</label>
                                <div class="fs-5 fw-bold text-success">{{ formatCurrency(quoteData.final_price_rounded) }}</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Sconto e Deposito -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="card-title mb-0">
                            <i class="ri-money-euro-circle-line me-2"></i>Sconto e Deposito
                        </h6>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Totale</label>
                                <div class="fs-5 fw-bold text-primary">{{ formatCurrency(quoteData.override_taxable || quoteData.taxable_price_rounded) }}</div>
                            </BCol>
                            <BCol v-if="quoteData.discount_percentage > 0" md="2" class="mb-2">
                                <label class="form-label small text-muted">Sconto</label>
                                <div class="fw-semibold">{{ quoteData.discount_percentage }}%</div>
                            </BCol>
                            <BCol v-if="quoteData.discount_name" md="3" class="mb-2">
                                <label class="form-label small text-muted">Promozione</label>
                                <div class="fw-semibold">{{ quoteData.discount_name }}</div>
                            </BCol>
                            <BCol v-if="quoteData.discount_percentage > 0" md="2" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Scontato</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.discounted_taxable) }}</div>
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Imponibile</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.deposit_taxable) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Handling Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.deposit_handling_fees) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Totale</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.deposit_total) }}</div>
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Imponibile</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.balance_taxable) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Handling Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.balance_handling_fees) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Card Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.balance_card_fees) }}</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Storico Transizioni -->
                <BCard v-if="quoteData.state_transitions && quoteData.state_transitions.length > 0" no-body class="mb-3">
                    <BCardHeader role="button" @click="showTransitions = !showTransitions">
                        <h6 class="card-title mb-0 d-flex justify-content-between align-items-center">
                            <span><i class="ri-history-line me-2"></i>Storico Transizioni</span>
                            <i :class="showTransitions ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                        </h6>
                    </BCardHeader>
                    <BCardBody v-show="showTransitions">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Da</th>
                                        <th>A</th>
                                        <th>Utente</th>
                                        <th>Sorgente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in quoteData.state_transitions" :key="t.id">
                                        <td>{{ formatDateTime(t.created_at) }}</td>
                                        <td>
                                            <span v-if="t.from_state" class="badge" :class="`bg-${getStatusColor(t.from_state)}-subtle text-${getStatusColor(t.from_state)}`">
                                                {{ getStatusLabel(t.from_state) }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="`bg-${getStatusColor(t.to_state)}-subtle text-${getStatusColor(t.to_state)}`">
                                                {{ getStatusLabel(t.to_state) }}
                                            </span>
                                        </td>
                                        <td>{{ t.actor ? `${t.actor.name} ${t.actor.surname}` : '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ t.transition_source }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </BCardBody>
                </BCard>

                <!-- Audit Info -->
                <BCard no-body class="mb-3">
                    <BCardBody>
                        <BRow>
                            <BCol md="4">
                                <small class="text-muted">
                                    Creato da: {{ quoteData.creator?.name }} {{ quoteData.creator?.surname }}
                                    il {{ formatDateTime(quoteData.created_at) }}
                                </small>
                            </BCol>
                            <BCol v-if="quoteData.updater" md="4">
                                <small class="text-muted">
                                    Modificato da: {{ quoteData.updater?.name }} {{ quoteData.updater?.surname }}
                                    il {{ formatDateTime(quoteData.updated_at) }}
                                </small>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Actions -->
                <div class="d-flex justify-content-between mb-4">
                    <Link href="/easyncc/quotes" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i>Torna alla Lista
                    </Link>
                    <Link :href="`/easyncc/quotes/${quoteData.id}/edit`" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i>Modifica
                    </Link>
                </div>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import moment from "moment";

const STATUS_LABELS = {
    draft: 'Bozza',
    approved: 'Approvato',
    sent: 'Inviato',
    deposit_received: 'Acconto Ricevuto',
};

const STATUS_COLORS = {
    draft: 'secondary',
    approved: 'primary',
    sent: 'info',
    deposit_received: 'success',
};

export default {
    components: { Head, Link, Layout, PageHeader },
    props: {
        quote: { type: Object, required: true },
    },
    data() {
        return {
            quoteData: { ...this.quote },
            showTransitions: false,
        };
    },
    computed: {
        steps() {
            return [
                { key: 'draft', label: 'Bozza' },
                { key: 'approved', label: 'Approvato' },
                { key: 'sent', label: 'Inviato' },
                { key: 'deposit_received', label: 'Acconto Ricevuto' },
            ];
        },
        currentStepIndex() {
            const idx = this.steps.findIndex(s => s.key === this.quoteData.status);
            return idx >= 0 ? idx : 0;
        },
    },
    methods: {
        stepClass(idx) {
            if (idx < this.currentStepIndex) return 'bg-success text-white';
            if (idx === this.currentStepIndex) return 'bg-primary text-white';
            return 'bg-light text-muted border';
        },
        getStatusLabel(status) {
            return STATUS_LABELS[status] || status;
        },
        getStatusColor(status) {
            return STATUS_COLORS[status] || 'secondary';
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '€ 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
        formatDate(date) {
            return date ? moment(date).format('DD/MM/YYYY') : '-';
        },
        formatDateTime(date) {
            return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
        },
    },
};
</script>
