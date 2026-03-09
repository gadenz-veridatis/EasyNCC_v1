<template>
    <Head title="Dettaglio Preventivo" />

    <Layout>
        <PageHeader title="Dettaglio Preventivo" pageTitle="Preventivi" />

        <BRow>
            <BCol lg="12">
                <!-- Dati Cliente e Servizio -->
                <BCard no-body class="mb-3">
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ri-user-line me-2"></i>Preventivo #{{ quote.id }}
                        </h6>
                        <div class="d-flex gap-2">
                            <Link :href="`/easyncc/quotes/${quote.id}/edit`" class="btn btn-sm btn-outline-primary">
                                <i class="ri-pencil-line me-1"></i>Modifica
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Cliente</label>
                                <div class="fw-semibold">{{ quote.client_name || '-' }}</div>
                            </BCol>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Data Servizio</label>
                                <div class="fw-semibold">{{ formatDate(quote.service_date) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Destinazione</label>
                                <div class="fw-semibold">{{ quote.destination_name || '-' }}</div>
                            </BCol>
                            <BCol md="3" class="mb-3">
                                <label class="form-label small text-muted">Tipo Servizio</label>
                                <div>
                                    <span class="badge bg-info-subtle text-info">{{ quote.service_type || '-' }}</span>
                                </div>
                            </BCol>
                        </BRow>
                        <BRow v-if="quote.notes">
                            <BCol md="12" class="mb-3">
                                <label class="form-label small text-muted">Note</label>
                                <div>{{ quote.notes }}</div>
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
                                <div class="fw-semibold">{{ quote.mileage }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Extra Km</label>
                                <div class="fw-semibold">{{ quote.extra_km }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Durata (ore)</label>
                                <div class="fw-semibold">{{ quote.duration_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Estensione (ore)</label>
                                <div class="fw-semibold">{{ quote.extension_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Ore Spost. Extra</label>
                                <div class="fw-semibold">{{ quote.extra_travel_hours }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Pedaggio</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.toll_cost) }}</div>
                            </BCol>
                        </BRow>
                        <BRow class="mt-2">
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Pax</label>
                                <div class="fw-semibold">{{ quote.pax_count }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Esperienza p.p.</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.experience_per_pax) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Stagionalità</label>
                                <div class="fw-semibold text-capitalize">{{ quote.seasonality }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Riempimento</label>
                                <div class="fw-semibold text-capitalize">{{ quote.vehicle_fill }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">VAT</label>
                                <div class="fw-semibold">{{ quote.vat_percentage }}%</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Card Fees</label>
                                <div class="fw-semibold">{{ quote.card_fees_percentage }}%</div>
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
                                <div class="fw-semibold">{{ formatCurrency(quote.taxable_transport) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Esperienze</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.taxable_experience) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Maggiorazione</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.surcharge_amount) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Trasferta</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.travel_costs) }}</div>
                            </BCol>
                        </BRow>
                        <hr />
                        <BRow>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Totale</label>
                                <div class="fs-5 fw-bold text-primary">{{ formatCurrency(quote.taxable_price_rounded) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">VAT</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.vat_amount) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">CC Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.cc_fees_amount) }}</div>
                            </BCol>
                            <BCol md="3" class="mb-2">
                                <label class="form-label small text-muted">Prezzo Finale</label>
                                <div class="fs-5 fw-bold text-success">{{ formatCurrency(quote.final_price_rounded) }}</div>
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
                                <div class="fs-5 fw-bold text-primary">{{ formatCurrency(quote.override_taxable || quote.taxable_price_rounded) }}</div>
                            </BCol>
                            <BCol v-if="quote.discount_percentage > 0" md="2" class="mb-2">
                                <label class="form-label small text-muted">Sconto</label>
                                <div class="fw-semibold">{{ quote.discount_percentage }}%</div>
                            </BCol>
                            <BCol v-if="quote.discount_name" md="3" class="mb-2">
                                <label class="form-label small text-muted">Promozione</label>
                                <div class="fw-semibold">{{ quote.discount_name }}</div>
                            </BCol>
                            <BCol v-if="quote.discount_percentage > 0" md="2" class="mb-2">
                                <label class="form-label small text-muted">Imponibile Scontato</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.discounted_taxable) }}</div>
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Imponibile</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.deposit_taxable) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Handling Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.deposit_handling_fees) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Acconto Totale</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.deposit_total) }}</div>
                            </BCol>
                        </BRow>
                        <BRow>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Imponibile</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.balance_taxable) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Handling Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.balance_handling_fees) }}</div>
                            </BCol>
                            <BCol md="2" class="mb-2">
                                <label class="form-label small text-muted">Saldo Card Fees</label>
                                <div class="fw-semibold">{{ formatCurrency(quote.balance_card_fees) }}</div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Audit Info -->
                <BCard no-body class="mb-3">
                    <BCardBody>
                        <BRow>
                            <BCol md="4">
                                <small class="text-muted">
                                    Creato da: {{ quote.creator?.name }} {{ quote.creator?.surname }}
                                    il {{ formatDateTime(quote.created_at) }}
                                </small>
                            </BCol>
                            <BCol v-if="quote.updater" md="4">
                                <small class="text-muted">
                                    Modificato da: {{ quote.updater?.name }} {{ quote.updater?.surname }}
                                    il {{ formatDateTime(quote.updated_at) }}
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
                    <Link :href="`/easyncc/quotes/${quote.id}/edit`" class="btn btn-primary">
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

export default {
    components: { Head, Link, Layout, PageHeader },
    props: {
        quote: { type: Object, required: true },
    },
    methods: {
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
