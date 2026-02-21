<template>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Allegati Veicolo</h6>
            <button type="button" class="btn btn-sm btn-soft-primary" @click="showAddModal = true">
                <i class="ri-add-line me-1"></i> Aggiungi Allegato
            </button>
        </div>

        <!-- Info banner when no attachments -->
        <div v-if="attachments.length === 0" class="alert alert-info mb-3" role="alert">
            <i class="ri-information-line me-2"></i>
            <strong>Carica i documenti del veicolo</strong><br>
            <small>Puoi caricare documenti come assicurazione, bollo, revisione, ecc. Clicca su "Aggiungi Allegato" per iniziare.</small>
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
                        <!-- Tipo Allegato - inline editable -->
                        <td @click="startInlineEdit(attachment, 'attachment_type')" class="inline-editable">
                            <template v-if="inlineEditing.id === attachment.id && inlineEditing.field === 'attachment_type'">
                                <select
                                    v-model="inlineEditing.value"
                                    class="form-select form-select-sm"
                                    @blur="saveInlineEdit(attachment)"
                                    @change="saveInlineEdit(attachment)"
                                    @keyup.escape="cancelInlineEdit"
                                    ref="inlineInput"
                                >
                                    <option v-for="type in attachmentTypes" :key="type.id" :value="type.name">
                                        {{ type.displayLabel }}
                                    </option>
                                </select>
                            </template>
                            <template v-else>{{ attachment.attachment_type }}</template>
                        </td>
                        <!-- Nome File - non editable -->
                        <td>
                            <i class="ri-file-line me-1"></i>
                            {{ attachment.file_name }}
                            <small class="text-muted d-block">
                                {{ formatFileSize(attachment.file_size) }}
                            </small>
                        </td>
                        <!-- Scadenza - inline editable -->
                        <td @click="startInlineEdit(attachment, 'expiration_date')" class="inline-editable">
                            <template v-if="inlineEditing.id === attachment.id && inlineEditing.field === 'expiration_date'">
                                <input
                                    type="date"
                                    v-model="inlineEditing.value"
                                    class="form-control form-control-sm"
                                    @blur="saveInlineEdit(attachment)"
                                    @keyup.enter="saveInlineEdit(attachment)"
                                    @keyup.escape="cancelInlineEdit"
                                    ref="inlineInput"
                                />
                            </template>
                            <template v-else>
                                <span v-if="attachment.expiration_date" :class="getExpirationClass(attachment.expiration_date)">
                                    {{ formatDate(attachment.expiration_date) }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </template>
                        </td>
                        <!-- Note - inline editable -->
                        <td @click="startInlineEdit(attachment, 'notes')" class="inline-editable">
                            <template v-if="inlineEditing.id === attachment.id && inlineEditing.field === 'notes'">
                                <input
                                    type="text"
                                    v-model="inlineEditing.value"
                                    class="form-control form-control-sm"
                                    @blur="saveInlineEdit(attachment)"
                                    @keyup.enter="saveInlineEdit(attachment)"
                                    @keyup.escape="cancelInlineEdit"
                                    ref="inlineInput"
                                />
                            </template>
                            <template v-else>
                                <small>{{ attachment.notes || '-' }}</small>
                            </template>
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

                    <BCol cols="12" class="mb-3">
                        <label class="form-label">File attuale</label>
                        <div class="d-flex align-items-center mb-2">
                            <i class="ri-file-line me-2"></i>
                            <span>{{ editingAttachment?.file_name }}</span>
                            <small class="text-muted ms-2">({{ formatFileSize(editingAttachment?.file_size) }})</small>
                        </div>
                        <label for="edit_file" class="form-label">Sostituisci file</label>
                        <input
                            id="edit_file"
                            ref="editFileInput"
                            type="file"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.file }"
                            @change="handleEditFileChange"
                        />
                        <small class="text-muted">Lascia vuoto per mantenere il file attuale. Max 10MB.</small>
                        <small v-if="formErrors.file" class="text-danger d-block">
                            {{ formErrors.file[0] }}
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
import { ref, nextTick, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicleId: {
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
const editSelectedFile = ref(null);
const editingAttachment = ref(null);
const inlineInput = ref(null);

const inlineEditing = ref({
    id: null,
    field: null,
    value: null,
});

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
        const response = await axios.get(`/api/vehicles/${props.vehicleId}/attachments`);
        attachments.value = response.data;
    } catch (error) {
        console.error('Error loading attachments:', error);
    }
};

const loadAttachmentTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/vehicle-attachment-types');
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
        await axios.post(`/api/vehicles/${props.vehicleId}/attachments`, formDataToSend, {
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
    editSelectedFile.value = null;
    showEditModal.value = true;
};

const handleEditFileChange = (event) => {
    editSelectedFile.value = event.target.files[0] || null;
};

const submitEdit = async () => {
    uploading.value = true;
    formErrors.value = {};

    try {
        const formDataToSend = new FormData();
        formDataToSend.append('_method', 'PUT');
        formDataToSend.append('attachment_type', editFormData.value.attachment_type);
        if (editFormData.value.expiration_date) {
            formDataToSend.append('expiration_date', editFormData.value.expiration_date);
        }
        if (editFormData.value.notes) {
            formDataToSend.append('notes', editFormData.value.notes);
        }
        if (editSelectedFile.value) {
            formDataToSend.append('file', editSelectedFile.value);
        }

        await axios.post(
            `/api/vehicles/${props.vehicleId}/attachments/${editingAttachment.value.id}`,
            formDataToSend,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        );

        await loadAttachments();
        showEditModal.value = false;
        editingAttachment.value = null;
        editSelectedFile.value = null;
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

const startInlineEdit = (attachment, field) => {
    if (inlineEditing.value.id === attachment.id && inlineEditing.value.field === field) return;
    inlineEditing.value = {
        id: attachment.id,
        field: field,
        value: attachment[field] || '',
    };
    nextTick(() => {
        if (inlineInput.value) {
            const el = Array.isArray(inlineInput.value) ? inlineInput.value[0] : inlineInput.value;
            if (el) el.focus();
        }
    });
};

const saveInlineEdit = async (attachment) => {
    if (inlineEditing.value.id !== attachment.id) return;
    const field = inlineEditing.value.field;
    const newValue = inlineEditing.value.value;

    // Skip if unchanged
    if ((attachment[field] || '') === (newValue || '')) {
        cancelInlineEdit();
        return;
    }

    try {
        const payload = {};
        payload[field] = newValue || null;
        await axios.put(
            `/api/vehicles/${props.vehicleId}/attachments/${attachment.id}`,
            payload
        );
        attachment[field] = newValue || null;
    } catch (error) {
        console.error('Error saving inline edit:', error);
    }
    cancelInlineEdit();
};

const cancelInlineEdit = () => {
    inlineEditing.value = { id: null, field: null, value: null };
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

const deleteAttachment = async (attachment) => {
    if (!confirm(`Sei sicuro di voler eliminare l'allegato "${attachment.file_name}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/vehicles/${props.vehicleId}/attachments/${attachment.id}`);
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

<style scoped>
.inline-editable {
    cursor: pointer;
    position: relative;
}
.inline-editable:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}
</style>
