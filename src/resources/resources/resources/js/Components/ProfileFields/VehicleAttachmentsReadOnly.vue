<template>
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Allegati Veicolo</h6>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <div v-else-if="attachments.length === 0" class="text-muted text-center py-3">
            <small>Nessun allegato disponibile.</small>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Tipo Allegato</th>
                        <th>Nome File</th>
                        <th>Scadenza</th>
                        <th>Note</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="attachment in attachments" :key="attachment.id">
                        <td>{{ attachment.attachment_type }}</td>
                        <td>
                            <i class="ri-file-line me-1"></i>
                            {{ attachment.file_name }}
                            <small class="text-muted d-block">
                                {{ formatFileSize(attachment.file_size) }}
                            </small>
                        </td>
                        <td>
                            <span v-if="attachment.expiration_date" :class="getExpirationClass(attachment.expiration_date)">
                                {{ formatDate(attachment.expiration_date) }}
                            </span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td>
                            <small>{{ attachment.notes || '-' }}</small>
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-info"
                                @click="downloadAttachment(attachment)"
                                title="Scarica"
                            >
                                <i class="ri-download-line"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicleId: {
        type: Number,
        required: true
    }
});

const attachments = ref([]);
const loading = ref(false);

const loadAttachments = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${props.vehicleId}/attachments`);
        attachments.value = response.data;
    } catch (error) {
        console.error('Error loading attachments:', error);
    } finally {
        loading.value = false;
    }
};

const downloadAttachment = async (attachment) => {
    try {
        const response = await axios.get(
            `/api/vehicles/${props.vehicleId}/attachments/${attachment.id}/download`,
            { responseType: 'blob' }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', attachment.file_name);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading attachment:', error);
        alert('Errore durante il download del file');
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const formatFileSize = (bytes) => {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const getExpirationClass = (date) => {
    if (!date) return '';
    const exp = moment(date);
    const now = moment();
    const daysUntilExpiration = exp.diff(now, 'days');

    if (daysUntilExpiration < 0) {
        return 'badge bg-danger';
    } else if (daysUntilExpiration < 30) {
        return 'badge bg-warning';
    }
    return 'badge bg-success';
};

onMounted(() => {
    loadAttachments();
});
</script>
