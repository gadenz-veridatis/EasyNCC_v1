<template>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Periodi di Non Disponibilit&agrave;</h6>
            <button type="button" class="btn btn-sm btn-soft-primary" @click="showAddModal = true">
                <i class="ri-add-line me-1"></i> Aggiungi Periodo
            </button>
        </div>

        <div v-if="unavailabilities.length === 0" class="alert alert-info mb-3" role="alert">
            <i class="ri-information-line me-2"></i>
            <strong>Nessun periodo di non disponibilit&agrave;</strong><br>
            <small>Aggiungi periodi in cui il driver non sar&agrave; disponibile (ferie, malattia, permesso, altro).</small>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Note</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="unavailability in unavailabilities" :key="unavailability.id">
                        <td>
                            <span class="badge bg-info">
                                {{ unavailability.leave_type?.name || '-' }}
                            </span>
                        </td>
                        <td>{{ formatDate(unavailability.start_date) }}</td>
                        <td>{{ formatDate(unavailability.end_date) }}</td>
                        <td>
                            <small>{{ unavailability.notes || '-' }}</small>
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-warning me-1"
                                @click="editUnavailability(unavailability)"
                                title="Modifica"
                            >
                                <i class="ri-edit-line"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-danger"
                                @click="deleteUnavailability(unavailability)"
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
            :title="editingUnavailability ? 'Modifica Periodo' : 'Aggiungi Periodo'"
            size="lg"
            hide-footer
        >
            <form @submit.prevent="submitForm">
                <BRow>
                    <BCol cols="12" class="mb-3">
                        <label for="leave_type_id" class="form-label">Tipo *</label>
                        <select
                            id="leave_type_id"
                            v-model="formData.leave_type_id"
                            class="form-select"
                            :class="{ 'is-invalid': formErrors.leave_type_id }"
                            required
                        >
                            <option value="">Seleziona tipo</option>
                            <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">
                                {{ lt.name }}
                            </option>
                        </select>
                        <small v-if="formErrors.leave_type_id" class="text-danger">
                            {{ formErrors.leave_type_id[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="start_date" class="form-label">Data Inizio *</label>
                        <input
                            id="start_date"
                            v-model="formData.start_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.start_date }"
                            required
                        />
                        <small v-if="formErrors.start_date" class="text-danger">
                            {{ formErrors.start_date[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="end_date" class="form-label">Data Fine *</label>
                        <input
                            id="end_date"
                            v-model="formData.end_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.end_date }"
                            required
                        />
                        <small v-if="formErrors.end_date" class="text-danger">
                            {{ formErrors.end_date[0] }}
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
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Salvataggio...
                        </span>
                        <span v-else>
                            <i class="ri-save-line me-1"></i>
                            {{ editingUnavailability ? 'Salva Modifiche' : 'Salva' }}
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

const unavailabilities = ref([]);
const leaveTypes = ref([]);
const showAddModal = ref(false);
const submitting = ref(false);
const formErrors = ref({});
const editingUnavailability = ref(null);

const formData = ref({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    notes: '',
});

const loadUnavailabilities = async () => {
    try {
        const response = await axios.get(`/api/users/${props.userId}/unavailabilities`);
        unavailabilities.value = response.data;
    } catch (error) {
        console.error('Error loading unavailabilities:', error);
    }
};

const loadLeaveTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/leave-types');
        leaveTypes.value = (response.data.data || response.data).filter(t => t.is_active !== false);
    } catch (error) {
        console.error('Error loading leave types:', error);
    }
};

const submitForm = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingUnavailability.value) {
            await axios.put(
                `/api/users/${props.userId}/unavailabilities/${editingUnavailability.value.id}`,
                formData.value
            );
        } else {
            await axios.post(
                `/api/users/${props.userId}/unavailabilities`,
                formData.value
            );
        }

        await loadUnavailabilities();
        closeModal();
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error saving unavailability:', error);
            alert('Errore durante il salvataggio del periodo');
        }
    } finally {
        submitting.value = false;
    }
};

const editUnavailability = (unavailability) => {
    editingUnavailability.value = unavailability;
    formData.value = {
        leave_type_id: unavailability.leave_type_id || '',
        start_date: unavailability.start_date,
        end_date: unavailability.end_date,
        notes: unavailability.notes || '',
    };
    showAddModal.value = true;
};

const deleteUnavailability = async (unavailability) => {
    if (!confirm('Sei sicuro di voler eliminare questo periodo di non disponibilit\u00E0?')) {
        return;
    }

    try {
        await axios.delete(`/api/users/${props.userId}/unavailabilities/${unavailability.id}`);
        await loadUnavailabilities();
    } catch (error) {
        console.error('Error deleting unavailability:', error);
        alert('Errore durante l\'eliminazione del periodo');
    }
};

const closeModal = () => {
    showAddModal.value = false;
    editingUnavailability.value = null;
    formData.value = {
        leave_type_id: '',
        start_date: '',
        end_date: '',
        notes: '',
    };
    formErrors.value = {};
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

onMounted(() => {
    loadUnavailabilities();
    loadLeaveTypes();
});
</script>
