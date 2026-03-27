<template>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">
                <i class="ri-attachment-2 me-1"></i>Allegati Servizio
            </h6>
            <button type="button" class="btn btn-sm btn-soft-primary" @click="showUploadForm = !showUploadForm">
                <i class="ri-add-line me-1"></i>Aggiungi Allegato
            </button>
        </div>

        <!-- Upload form -->
        <div v-if="showUploadForm" class="border rounded p-3 mb-3 bg-light">
            <form @submit.prevent="uploadFile">
                <div class="mb-2">
                    <label class="form-label">File *</label>
                    <input
                        ref="fileInput"
                        type="file"
                        class="form-control form-control-sm"
                        @change="onFileChange"
                        required
                    />
                </div>
                <div class="mb-2">
                    <label class="form-label">Note</label>
                    <input
                        v-model="uploadNotes"
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Note opzionali..."
                    />
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary" :disabled="uploading || !selectedFile">
                        <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-upload-2-line me-1"></i>Carica
                    </button>
                    <button type="button" class="btn btn-sm btn-light" @click="cancelUpload">Annulla</button>
                </div>
            </form>
        </div>

        <!-- Attachments list -->
        <div v-if="attachments.length === 0 && !showUploadForm" class="text-muted small text-center py-2">
            Nessun allegato.
        </div>
        <div v-else-if="attachments.length > 0" class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Dimensione</th>
                        <th>Note</th>
                        <th>Data</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="att in attachments" :key="att.id">
                        <td>
                            <i class="ri-file-line me-1"></i>
                            <a :href="`/api/services/${serviceId}/attachments/${att.id}/download`" target="_blank" class="text-decoration-none">
                                {{ att.file_name }}
                            </a>
                        </td>
                        <td class="small text-muted">{{ formatSize(att.file_size) }}</td>
                        <td class="small">{{ att.notes || '-' }}</td>
                        <td class="small text-muted">{{ formatDate(att.created_at) }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-danger"
                                @click="deleteAttachment(att)"
                                title="Elimina"
                            >
                                <i class="ri-delete-bin-line"></i>
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
    serviceId: { type: Number, required: true }
});

const attachments = ref([]);
const showUploadForm = ref(false);
const uploading = ref(false);
const selectedFile = ref(null);
const uploadNotes = ref('');
const fileInput = ref(null);

const loadAttachments = async () => {
    try {
        const { data } = await axios.get(`/api/services/${props.serviceId}/attachments`);
        attachments.value = data;
    } catch (e) {
        console.error('Error loading attachments:', e);
    }
};

const onFileChange = (event) => {
    selectedFile.value = event.target.files[0] || null;
};

const uploadFile = async () => {
    if (!selectedFile.value) return;
    uploading.value = true;
    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        if (uploadNotes.value) formData.append('notes', uploadNotes.value);

        await axios.post(`/api/services/${props.serviceId}/attachments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        cancelUpload();
        await loadAttachments();
    } catch (e) {
        console.error('Error uploading:', e);
        alert('Errore durante il caricamento del file');
    } finally {
        uploading.value = false;
    }
};

const cancelUpload = () => {
    showUploadForm.value = false;
    selectedFile.value = null;
    uploadNotes.value = '';
    if (fileInput.value) fileInput.value.value = '';
};

const deleteAttachment = async (att) => {
    if (!confirm(`Eliminare l'allegato "${att.file_name}"?`)) return;
    try {
        await axios.delete(`/api/services/${props.serviceId}/attachments/${att.id}`);
        await loadAttachments();
    } catch (e) {
        console.error('Error deleting:', e);
        alert('Errore durante l\'eliminazione');
    }
};

const formatSize = (bytes) => {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};

const formatDate = (date) => {
    return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
};

onMounted(() => {
    loadAttachments();
});
</script>
