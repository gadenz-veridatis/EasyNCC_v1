<template>
    <BCard no-body>
        <BCardHeader class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="ri-git-branch-line me-2"></i>Versioni ({{ versions.length }})
            </h6>
            <button class="btn btn-sm btn-primary" @click="$emit('create-version')" :disabled="creating">
                <span v-if="creating" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ri-add-line me-1"></i>Nuova Versione
            </button>
        </BCardHeader>
        <BCardBody class="p-0">
            <div class="list-group list-group-flush">
                <div
                    v-for="ver in versions"
                    :key="ver.id"
                    class="list-group-item"
                    :class="{ 'border-start border-primary border-3 bg-primary-subtle': ver.is_active_version }"
                >
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">
                                v{{ ver.version }}
                                <span v-if="ver.is_active_version" class="badge bg-primary ms-1">Attiva</span>
                                <span v-else class="badge bg-secondary ms-1">Archiviata</span>
                            </div>
                            <small class="text-muted d-block">
                                <span class="badge me-1" :class="`bg-${getStatusColor(ver.status)}-subtle text-${getStatusColor(ver.status)}`">
                                    {{ getStatusLabel(ver.status) }}
                                </span>
                            </small>
                            <small class="text-muted d-block mt-1">
                                Creata: {{ formatDateTime(ver.created_at) }}
                            </small>
                            <small v-if="ver.archived_at" class="text-muted d-block">
                                Archiviata: {{ formatDateTime(ver.archived_at) }}
                            </small>
                            <small v-if="ver.final_price_rounded" class="text-muted d-block">
                                Prezzo: {{ formatCurrency(ver.final_price_rounded) }}
                            </small>
                            <small class="text-muted d-block">
                                {{ ver.items_count || 0 }} servizio/i
                            </small>
                        </div>
                        <div v-if="!ver.is_active_version" class="d-flex flex-column gap-1">
                            <button class="btn btn-sm btn-outline-info" @click="$emit('preview-version', ver)" title="Anteprima">
                                <i class="ri-eye-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" @click="$emit('restore-version', ver)" title="Ripristina" :disabled="restoring">
                                <i class="ri-restart-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </BCardBody>
    </BCard>
</template>

<script>
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
    props: {
        versions: { type: Array, default: () => [] },
        creating: { type: Boolean, default: false },
        restoring: { type: Boolean, default: false },
    },
    emits: ['create-version', 'preview-version', 'restore-version'],
    methods: {
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
        formatDateTime(date) {
            return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
        },
    },
};
</script>
