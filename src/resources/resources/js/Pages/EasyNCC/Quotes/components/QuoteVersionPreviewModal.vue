<template>
    <BModal :modelValue="show" @update:modelValue="$emit('update:show', $event)" title="Anteprima Versione Archiviata" size="xl" hide-footer>
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>
        <template v-else-if="versionData">
            <div class="mb-3">
                <h6>
                    Versione v{{ versionData.version }}
                    <span class="badge" :class="`bg-${getStatusColor(versionData.status)}`">
                        {{ getStatusLabel(versionData.status) }}
                    </span>
                    <small class="text-muted ms-2">Creata: {{ formatDateTime(versionData.created_at) }}</small>
                </h6>
            </div>

            <!-- Client Info -->
            <BCard no-body class="mb-3">
                <BCardHeader><h6 class="card-title mb-0"><i class="ri-user-line me-2"></i>Dati Cliente</h6></BCardHeader>
                <BCardBody>
                    <BRow>
                        <BCol md="4"><label class="form-label small text-muted">Cliente</label><div class="fw-semibold">{{ versionData.client_name || '-' }}</div></BCol>
                        <BCol md="4"><label class="form-label small text-muted">Email</label><div class="fw-semibold">{{ versionData.client_email || '-' }}</div></BCol>
                        <BCol md="4"><label class="form-label small text-muted">Data Servizio</label><div class="fw-semibold">{{ formatDate(versionData.service_date) }}</div></BCol>
                    </BRow>
                </BCardBody>
            </BCard>

            <!-- Services -->
            <BCard v-if="versionData.items && versionData.items.length > 0" no-body class="mb-3">
                <BCardHeader><h6 class="card-title mb-0"><i class="ri-route-line me-2"></i>Servizi</h6></BCardHeader>
                <BCardBody class="p-0">
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
                                <tr v-for="(item, idx) in versionData.items" :key="idx">
                                    <td>{{ idx + 1 }}</td>
                                    <td>{{ item.destination_name || '-' }}</td>
                                    <td><span class="badge" :class="serviceTypeBadgeClass(item.service_type, 'bg-info-subtle text-info')">{{ item.service_type || '-' }}</span></td>
                                    <td class="text-end">{{ item.mileage }}</td>
                                    <td class="text-end">{{ item.duration_hours }}</td>
                                    <td class="text-end">{{ item.pax_count }}</td>
                                    <td class="text-end">{{ formatCurrency(item.toll_cost) }}</td>
                                    <td class="text-end fw-bold">{{ formatCurrency(item.taxable_price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BCardBody>
            </BCard>

            <!-- Pricing -->
            <BCard no-body class="mb-3">
                <BCardHeader class="bg-light"><h6 class="card-title mb-0"><i class="ri-calculator-line me-2"></i>Risultato Prezzo</h6></BCardHeader>
                <BCardBody>
                    <BRow>
                        <BCol md="3"><label class="form-label small text-muted">Imponibile</label><div class="fs-5 fw-bold text-primary">{{ formatCurrency(versionData.taxable_price_rounded) }}</div></BCol>
                        <BCol md="3"><label class="form-label small text-muted">Prezzo Finale</label><div class="fs-5 fw-bold text-success">{{ formatCurrency(versionData.final_price_rounded) }}</div></BCol>
                        <BCol md="3"><label class="form-label small text-muted">Acconto</label><div class="fw-semibold">{{ formatCurrency(versionData.deposit_total) }}</div></BCol>
                    </BRow>
                </BCardBody>
            </BCard>

            <div class="d-flex justify-content-end">
                <button class="btn btn-warning" @click="$emit('restore', versionData)" :disabled="restoring">
                    <span v-if="restoring" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ri-restart-line me-1"></i>Ripristina questa versione
                </button>
            </div>
        </template>
    </BModal>
</template>

<script>
import moment from "moment";
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';

const { serviceTypeBadgeClass } = useServiceTypeColor();

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
    props: {
        show: { type: Boolean, default: false },
        versionData: { type: Object, default: null },
        loading: { type: Boolean, default: false },
        restoring: { type: Boolean, default: false },
    },
    emits: ['update:show', 'restore'],
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
