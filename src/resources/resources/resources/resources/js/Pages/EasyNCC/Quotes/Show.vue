<template>
    <Head title="Dettaglio Preventivo" />

    <Layout>
        <PageHeader title="Dettaglio Preventivo" pageTitle="Preventivi" />

        <BRow>
            <BCol lg="12">
                <!-- Workflow Stepper -->
                <QuoteWorkflowStepper :status="quoteData.status" />

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

                <!-- Dati Cliente -->
                <BCard no-body class="mb-3">
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ri-user-line me-2"></i>Preventivo #{{ quoteData.id }}
                            <span class="badge bg-primary-subtle text-primary ms-2">v{{ quoteData.version }}</span>
                            <span class="badge ms-1" :class="`bg-${getStatusColor(quoteData.status)}`">
                                {{ getStatusLabel(quoteData.status) }}
                            </span>
                        </h6>
                        <Link :href="`/easyncc/quotes/${quoteData.id}/edit`" class="btn btn-sm btn-outline-primary">
                            <i class="ri-pencil-line me-1"></i>Modifica
                        </Link>
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
                                <label class="form-label small text-muted">Servizi</label>
                                <div class="fw-semibold">{{ (quoteData.items || []).length || 1 }} servizio/i</div>
                            </BCol>
                        </BRow>
                        <BRow v-if="quoteData.notes">
                            <BCol md="12">
                                <label class="form-label small text-muted">Note</label>
                                <div>{{ quoteData.notes }}</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Servizi -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="card-title mb-0"><i class="ri-route-line me-2"></i>Servizi</h6>
                    </BCardHeader>
                    <BCardBody v-if="quoteData.items && quoteData.items.length > 0" class="p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Destinazione</th>
                                        <th>Tipo</th>
                                        <th class="text-end">Km</th>
                                        <th class="text-end">Ore</th>
                                        <th class="text-end">Pax</th>
                                        <th class="text-end">Pedaggio</th>
                                        <th class="text-end">Imponibile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in quoteData.items" :key="idx">
                                        <td>{{ idx + 1 }}</td>
                                        <td>{{ item.destination_name || '-' }}</td>
                                        <td><span class="badge" :class="serviceTypeBadgeClass(item.service_type, 'bg-info-subtle text-info')">{{ item.service_type || '-' }}</span></td>
                                        <td class="text-end">{{ item.mileage }}</td>
                                        <td class="text-end">{{ item.duration_hours }}</td>
                                        <td class="text-end">{{ item.pax_count }}</td>
                                        <td class="text-end">{{ formatCurrency(item.toll_cost) }}</td>
                                        <td class="text-end fw-bold">{{ formatCurrency(item.taxable_price) }}</td>
                                    </tr>
                                    <tr class="table-light fw-bold">
                                        <td colspan="7" class="text-end">Totale</td>
                                        <td class="text-end">{{ formatCurrency(itemsTotalTaxable) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </BCardBody>
                    <BCardBody v-else>
                        <p class="text-muted mb-0">Nessun servizio associato.</p>
                    </BCardBody>
                </BCard>

                <!-- Risultato Prezzo -->
                <BCard no-body class="mb-3">
                    <BCardHeader class="bg-light">
                        <h6 class="card-title mb-0"><i class="ri-calculator-line me-2"></i>Risultato Prezzo</h6>
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
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">VAT</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.vat_amount) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Prezzo Finale</label>
                                <div class="fs-5 fw-bold text-success">{{ formatCurrency(quoteData.final_price_rounded) }}</div>
                            </BCol>
                        </BRow>
                        <hr />
                        <BRow>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Acconto Totale</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.deposit_total) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Saldo</label>
                                <div class="fw-semibold">{{ formatCurrency(quoteData.balance_taxable) }}</div>
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
                                        <td><span class="badge bg-light text-dark">{{ t.transition_source }}</span></td>
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
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';

const { loadServiceTypes, serviceTypeBadgeClass } = useServiceTypeColor();
import QuoteWorkflowStepper from './components/QuoteWorkflowStepper.vue';

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
    components: { Head, Link, Layout, PageHeader, QuoteWorkflowStepper },
    props: {
        quote: { type: Object, required: true },
        versions: { type: Array, default: () => [] },
    },
    data() {
        return {
            quoteData: { ...this.quote },
            showTransitions: false,
        };
    },
    computed: {
        itemsTotalTaxable() {
            if (!this.quoteData.items || !this.quoteData.items.length) return 0;
            return this.quoteData.items.reduce((sum, item) => sum + (parseFloat(item.taxable_price) || 0), 0);
        },
    },
    mounted() {
        loadServiceTypes();
    },
    methods: {
        serviceTypeBadgeClass,
        getStatusLabel(status) {
            return STATUS_LABELS[status] || status;
        },
        getStatusColor(status) {
            return STATUS_COLORS[status] || 'secondary';
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '\u20AC 0,00';
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
