<template>
    <Layout>
        <PageHeader :title="pageTitle" :items="items" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Dettagli Azienda</h5>
                            <div class="d-flex gap-2">
                                <BButton
                                    variant="primary"
                                    size="sm"
                                    @click="$inertia.visit(`/easyncc/companies/${company.id}/edit`)"
                                >
                                    <i class="bx bx-edit me-1"></i> Modifica
                                </BButton>
                                <BButton
                                    variant="secondary"
                                    size="sm"
                                    @click="$inertia.visit('/easyncc/companies')"
                                >
                                    <i class="bx bx-arrow-back me-1"></i> Torna alla Lista
                                </BButton>
                            </div>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Informazioni Base -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted mb-3 border-bottom pb-2">Informazioni Base</h6>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Nome Azienda</label>
                                <p class="text-muted mb-0">{{ company.name }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <p class="text-muted mb-0">
                                    <a :href="`mailto:${company.email}`">{{ company.email }}</a>
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Telefono</label>
                                <p class="text-muted mb-0">
                                    <a v-if="company.phone" :href="`tel:${company.phone}`">{{ company.phone }}</a>
                                    <span v-else>-</span>
                                </p>
                            </div>
                        </div>

                        <!-- Dati Fiscali -->
                        <div class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Dati Fiscali</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Partita IVA</label>
                                <p class="text-muted mb-0">{{ company.vat_number || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Codice SDI</label>
                                <p class="text-muted mb-0">{{ company.sdi || '-' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">PEC</label>
                                <p class="text-muted mb-0">
                                    <a v-if="company.pec" :href="`mailto:${company.pec}`">{{ company.pec }}</a>
                                    <span v-else>-</span>
                                </p>
                            </div>
                        </div>

                        <!-- Indirizzo e Contatti -->
                        <div class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Indirizzo e Contatti</h6>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Indirizzo</label>
                                <p class="text-muted mb-0">{{ company.address || '-' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Sito Web</label>
                                <p class="text-muted mb-0">
                                    <a v-if="company.website" :href="company.website" target="_blank">
                                        {{ company.website }}
                                    </a>
                                    <span v-else>-</span>
                                </p>
                            </div>
                        </div>

                        <!-- Stato -->
                        <div class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Stato</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Stato Azienda</label>
                                <p class="mb-0">
                                    <BBadge :variant="company.is_active ? 'success' : 'danger'">
                                        {{ company.is_active ? 'Attiva' : 'Inattiva' }}
                                    </BBadge>
                                </p>
                            </div>
                        </div>

                        <!-- Statistiche -->
                        <div v-if="company.users || company.vehicles || company.services" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Statistiche</h6>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Utenti</label>
                                <p class="text-muted mb-0">
                                    {{ company.users?.length || 0 }} utenti registrati
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Veicoli</label>
                                <p class="text-muted mb-0">
                                    {{ company.vehicles?.length || 0 }} veicoli in flotta
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Servizi</label>
                                <p class="text-muted mb-0">
                                    {{ company.services?.length || 0 }} servizi totali
                                </p>
                            </div>
                        </div>

                        <!-- Date di Sistema -->
                        <div class="row pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Date di Sistema</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Data Creazione</label>
                                <p class="text-muted mb-0">{{ formatDate(company.created_at) }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ultimo Aggiornamento</label>
                                <p class="text-muted mb-0">{{ formatDate(company.updated_at) }}</p>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { computed } from 'vue';
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import moment from 'moment';

const props = defineProps({
    company: {
        type: Object,
        required: true
    }
});

const pageTitle = computed(() => props.company.name);
const items = [
    { text: 'EasyNCC', href: '/' },
    { text: 'Aziende', href: '/easyncc/companies' },
    { text: props.company.name, active: true }
];

const formatDate = (date) => {
    return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
};
</script>
