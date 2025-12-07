<template>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Allegati Conducente</h6>
            <button type="button" class="btn btn-sm btn-soft-primary" @click="showAddModal = true">
                <i class="ri-add-line me-1"></i> Aggiungi Allegato
            </button>
        </div>

        <!-- Info banner when no attachments -->
        <div v-if="attachments.length === 0" class="alert alert-info mb-3" role="alert">
            <i class="ri-information-line me-2"></i>
            <strong>Carica i documenti del conducente</strong><br>
            <small>Puoi caricare documenti come patente, certificati, assicurazione, ecc. Clicca su "Aggiungi Allegato" per iniziare.</small>
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
                                class="btn btn-sm btn-soft-info me-1"
                                @click="downloadAttachment(attachment)"
                                title="Scarica"
                            >
                                <i class="ri-download-line"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-warning me-1"
                                @click="editAttachment(attachment)"
                                title="Modifica"
                            >
                                <i class="ri-edit-line"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-danger"
                                @click="deleteAttachment(attachment)"
                                title="Elimina"
                            >
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal -->
        <BModal
            v-model="showAddModal"
            title="Aggiungi Allegato"
            size="lg"
            hide-footer
        >
            <form @submit.prevent="submitAttachment">
                <BRow>
                    <BCol cols="12" class="mb-3">
                        <label for="attachment_type" class="form-label">Tipo Allegato *</label>
                        <select
                            id="attachment_type"
                            v-model="formData.attachment_type"
                            class="form-select"
                            :class="{ 'is-invalid': formErrors.attachment_type }"
                            required
                        >
                            <option value="">Seleziona tipo allegato</option>
                            <option v-for="type in attachmentTypes" :key="type.id" :value="type.name">
                                {{ type.displayLabel }}
                            </option>
                        </select>
                        <small v-if="formErrors.attachment_type" class="text-danger">
                            {{ formErrors.attachment_type[0] }}
                        </small>
                    </BCol>

                    <BCol cols="12" class="mb-3">
                        <label for="file" class="form-label">File *</label>
                        <input
                            id="file"
                            ref="fileInput"
                            type="file"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.file }"
                            @change="handleFileChange"
                            required
                        />
                        <small class="text-muted">Dimensione massima: 10MB</small>
                        <small v-if="formErrors.file" class="text-danger d-block">
                            {{ formErrors.file[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="expiration_date" class="form-label">Scadenza</label>
                        <input
                            id="expiration_date"
                            v-model="formData.expiration_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.expiration_date }"
                        />
                        <small v-if="formErrors.expiration_date" class="text-danger">
                            {{ formErrors.expiration_date[0] }}
                        </small>
                    </BCol>

                    <BCol cols="12" class="mb-3">
                        <label for="notes" class="form-label">Note</label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.notes }"
                            rows="3"
                            placeholder="Note aggiuntive..."
                        ></textarea>
                        <small v-if="formErrors.notes" class="text-danger">
                            {{ formErrors.notes[0] }}
                        </small>
                    </BCol>
                </BRow>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-light" @click="closeModal">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="uploading">
                        <span v-if="uploading">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Caricamento...
                        </span>
                        <span v-else>
                            <i class="ri-save-line me-1"></i>
                            Salva
                        </span>
                    </button>
                </div>
            </form>
        </BModal>

        <!-- Edit Modal -->
        <BModal
            v-model="showEditModal"
            title="Modifica Allegato"
            size="lg"
            hide-footer
        >
            <form @submit.prevent="submitEdit">
                <BRow>
                    <BCol cols="12" class="mb-3">
                        <label for="edit_attachment_type" class="form-label">Tipo Allegato *</label>
                        <select
                            id="edit_attachment_type"
                            v-model="editFormData.attachment_type"
                            class="form-select"
                            :class="{ 'is-invalid': formErrors.attachment_type }"
                            required
                        >
                            <option value="">Seleziona tipo allegato</option>
                            <option v-for="type in attachmentTypes" :key="type.id" :value="type.name">
                                {{ type.displayLabel }}
                            </option>
                        </select>
                        <small v-if="formErrors.attachment_type" class="text-danger">
                            {{ formErrors.attachment_type[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="edit_expiration_date" class="form-label">Scadenza</label>
                        <input
                            id="edit_expiration_date"
                            v-model="editFormData.expiration_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.expiration_date }"
                        />
                        <small v-if="formErrors.expiration_date" class="text-danger">
                            {{ formErrors.expiration_date[0] }}
                        </small>
                    </BCol>

                    <BCol cols="12" class="mb-3">
                        <label for="edit_notes" class="form-label">Note</label>
                        <textarea
                            id="edit_notes"
                            v-model="editFormData.notes"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.notes }"
                            rows="3"
                        ></textarea>
                        <small v-if="formErrors.notes" class="text-danger">
                            {{ formErrors.notes[0] }}
                        </small>
                    </BCol>
                </BRow>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-light" @click="showEditModal = false">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="uploading">
                        <span v-if="uploading">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Salvataggio...
                        </span>
                        <span v-else>
                            <i class="ri-save-line me-1"></i>
                            Salva Modifiche
                        </span>
                    </button>
                </div>
            </form>
        </BModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    userId: {
        type: Number,
        required: true
    }
});

const attachments = ref([]);
const attachmentTypes = ref([]);
const showAddModal = ref(false);
const showEditModal = ref(false);
const uploading = ref(false);
const formErrors = ref({});
const selectedFile = ref(null);
const editingAttachment = ref(null);

const formData = ref({
    attachment_type: '',
    expiration_date: '',
    notes: '',
});

const editFormData = ref({
    attachment_type: '',
    expiration_date: '',
    notes: '',
});

const loadAttachments = async () => {
    try {
        const response = await axios.get(`/api/users/${props.userId}/attachments`);
        attachments.value = response.data;
    } catch (error) {
        console.error('Error loading attachments:', error);
    }
};

const loadAttachmentTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/driver-attachment-types');
        // response.data has structure { success: true, data: [...] }
        const items = response.data.data || response.data;

        // Map items to include formatted label with company name for super-admin
        attachmentTypes.value = items.map(item => ({
            ...item,
            displayLabel: item.company ? `${item.name} (${item.company.name})` : item.name
        }));
    } catch (error) {
        console.error('Error loading attachment types:', error);
        attachmentTypes.value = [];
    }
};

const handleFileChange = (event) => {
    selectedFile.value = event.target.files[0];
};

const submitAttachment = async () => {
    if (!selectedFile.value) {
        formErrors.value.file = ['Seleziona un file da caricare'];
        return;
    }

    uploading.value = true;
    formErrors.value = {};

    const formDataToSend = new FormData();
    formDataToSend.append('file', selectedFile.value);
    formDataToSend.append('attachment_type', formData.value.attachment_type);
    if (formData.value.expiration_date) {
        formDataToSend.append('expiration_date', formData.value.expiration_date);
    }
    if (formData.value.notes) {
        formDataToSend.append('notes', formData.value.notes);
    }

    try {
        await axios.post(`/api/users/${props.userId}/attachments`, formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        await loadAttachments();
        closeModal();
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error uploading attachment:', error);
            alert('Errore durante il caricamento del file');
        }
    } finally {
        uploading.value = false;
    }
};

const editAttachment = (attachment) => {
    editingAttachment.value = attachment;
    editFormData.value = {
        attachment_type: attachment.attachment_type,
        expiration_date: attachment.expiration_date || '',
        notes: attachment.notes || '',
    };
    showEditModal.value = true;
};

const submitEdit = async () => {
    uploading.value = true;
    formErrors.value = {};

    try {
        await axios.put(
            `/api/users/${props.userId}/attachments/${editingAttachment.value.id}`,
            editFormData.value
        );

        await loadAttachments();
        showEditModal.value = false;
        editingAttachment.value = null;
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error updating attachment:', error);
            alert('Errore durante l\'aggiornamento dell\'allegato');
        }
    } finally {
        uploading.value = false;
    }
};

const downloadAttachment = async (attachment) => {
    try {
        const response = await axios.get(
            `/api/users/${props.userId}/attachments/${attachment.id}/download`,
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

const deleteAttachment = async (attachment) => {
    if (!confirm(`Sei sicuro di voler eliminare l'allegato "${attachment.file_name}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/users/${props.userId}/attachments/${attachment.id}`);
        await loadAttachments();
    } catch (error) {
        console.error('Error deleting attachment:', error);
        alert('Errore durante l\'eliminazione dell\'allegato');
    }
};

const closeModal = () => {
    showAddModal.value = false;
    formData.value = {
        attachment_type: '',
        expiration_date: '',
        notes: '',
    };
    selectedFile.value = null;
    formErrors.value = {};
    if (props.$refs && props.$refs.fileInput) {
        props.$refs.fileInput.value = '';
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
    loadAttachmentTypes();
});
</script>
