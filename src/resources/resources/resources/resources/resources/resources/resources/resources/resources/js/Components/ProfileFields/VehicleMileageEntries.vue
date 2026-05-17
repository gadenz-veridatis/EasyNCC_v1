<template>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Registro Chilometraggio</h6>
            <button type="button" class="btn btn-sm btn-soft-primary" @click="openAddModal">
                <i class="ri-add-line me-1"></i> Aggiungi Lettura
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <!-- Info banner when no entries -->
        <div v-else-if="entries.length === 0" class="alert alert-info mb-3" role="alert">
            <i class="ri-information-line me-2"></i>
            <strong>Nessuna lettura registrata</strong><br>
            <small>Aggiungi le letture del contachilometri per tracciare il chilometraggio del veicolo.</small>
        </div>

        <template v-else>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Data Lettura</th>
                            <th class="text-end">Km</th>
                            <th>Tipo</th>
                            <th>Utente</th>
                            <th>Note</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries" :key="entry.id">
                            <td>{{ formatDate(entry.entry_date) }}</td>
                            <td class="text-end fw-semibold">{{ formatNumber(entry.mileage) }}</td>
                            <td>
                                <span :class="getTypeClass(entry.update_type)">
                                    {{ getTypeLabel(entry.update_type) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ entry.creator ? `${entry.creator.name} ${entry.creator.surname}` : '-' }}</small>
                            </td>
                            <td>
                                <small>{{ entry.notes || '-' }}</small>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-soft-warning me-1"
                                    @click="editEntry(entry)"
                                    title="Modifica"
                                >
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-soft-danger"
                                    @click="deleteEntry(entry)"
                                    title="Elimina"
                                >
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    {{ pagination.from }}-{{ pagination.to }} di {{ pagination.total }} registrazioni
                </small>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                        <a class="page-link" href="#" @click.prevent="loadEntries(pagination.current_page - 1)">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    </li>
                    <li
                        v-for="page in visiblePages"
                        :key="page"
                        class="page-item"
                        :class="{ active: page === pagination.current_page }"
                    >
                        <a class="page-link" href="#" @click.prevent="loadEntries(page)">{{ page }}</a>
                    </li>
                    <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                        <a class="page-link" href="#" @click.prevent="loadEntries(pagination.current_page + 1)">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </template>

        <!-- Add/Edit Modal -->
        <BModal
            v-model="showModal"
            :title="editingEntry ? 'Modifica Lettura' : 'Nuova Lettura Chilometraggio'"
            size="lg"
            hide-footer
        >
            <form @submit.prevent="submitForm">
                <BRow>
                    <BCol md="6" class="mb-3">
                        <label for="entry_date" class="form-label">Data Lettura *</label>
                        <input
                            id="entry_date"
                            v-model="formData.entry_date"
                            type="date"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.entry_date }"
                            required
                        />
                        <small v-if="formErrors.entry_date" class="text-danger">
                            {{ formErrors.entry_date[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="mileage" class="form-label">Chilometraggio (km) *</label>
                        <input
                            id="mileage"
                            v-model.number="formData.mileage"
                            type="number"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.mileage }"
                            min="0"
                            required
                            placeholder="Es. 125000"
                        />
                        <small v-if="formErrors.mileage" class="text-danger">
                            {{ formErrors.mileage[0] }}
                        </small>
                    </BCol>

                    <BCol md="6" class="mb-3">
                        <label for="update_type" class="form-label">Tipo Aggiornamento *</label>
                        <select
                            id="update_type"
                            v-model="formData.update_type"
                            class="form-select"
                            :class="{ 'is-invalid': formErrors.update_type }"
                            required
                        >
                            <option value="">Seleziona tipo</option>
                            <option value="manual">Manuale</option>
                            <option value="correction">Rettifica</option>
                            <option value="service">Servizio</option>
                        </select>
                        <small v-if="formErrors.update_type" class="text-danger">
                            {{ formErrors.update_type[0] }}
                        </small>
                    </BCol>

                    <BCol cols="12" class="mb-3">
                        <label for="notes" class="form-label">Note</label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            class="form-control"
                            :class="{ 'is-invalid': formErrors.notes }"
                            rows="2"
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
                            {{ editingEntry ? 'Salva Modifiche' : 'Salva' }}
                        </span>
                    </button>
                </div>
            </form>
        </BModal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicleId: {
        type: Number,
        required: true
    }
});

const entries = ref([]);
const loading = ref(false);
const showModal = ref(false);
const submitting = ref(false);
const formErrors = ref({});
const editingEntry = ref(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
});

const today = () => new Date().toISOString().split('T')[0];

const formData = ref({
    entry_date: today(),
    mileage: null,
    update_type: 'manual',
    notes: '',
});

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const loadEntries = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${props.vehicleId}/mileage-entries`, {
            params: { page, per_page: 15 }
        });
        entries.value = response.data.data || [];
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            from: response.data.from || 0,
            to: response.data.to || 0,
            total: response.data.total || 0,
        };
    } catch (error) {
        console.error('Error loading mileage entries:', error);
    } finally {
        loading.value = false;
    }
};

const openAddModal = () => {
    editingEntry.value = null;
    formData.value = {
        entry_date: today(),
        mileage: null,
        update_type: 'manual',
        notes: '',
    };
    formErrors.value = {};
    showModal.value = true;
};

const editEntry = (entry) => {
    editingEntry.value = entry;
    formData.value = {
        entry_date: entry.entry_date ? entry.entry_date.substring(0, 10) : '',
        mileage: entry.mileage,
        update_type: entry.update_type,
        notes: entry.notes || '',
    };
    formErrors.value = {};
    showModal.value = true;
};

const submitForm = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingEntry.value) {
            await axios.put(
                `/api/vehicles/${props.vehicleId}/mileage-entries/${editingEntry.value.id}`,
                formData.value
            );
        } else {
            await axios.post(
                `/api/vehicles/${props.vehicleId}/mileage-entries`,
                formData.value
            );
        }

        await loadEntries(pagination.value.current_page);
        closeModal();
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error saving mileage entry:', error);
            alert('Errore durante il salvataggio della lettura');
        }
    } finally {
        submitting.value = false;
    }
};

const deleteEntry = async (entry) => {
    if (!confirm('Sei sicuro di voler eliminare questa registrazione?')) {
        return;
    }

    try {
        await axios.delete(`/api/vehicles/${props.vehicleId}/mileage-entries/${entry.id}`);
        await loadEntries(pagination.value.current_page);
    } catch (error) {
        console.error('Error deleting mileage entry:', error);
        alert('Errore durante l\'eliminazione della registrazione');
    }
};

const closeModal = () => {
    showModal.value = false;
    editingEntry.value = null;
    formErrors.value = {};
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '-';
    return num.toLocaleString('it-IT');
};

const getTypeLabel = (type) => {
    const labels = {
        'manual': 'Manuale',
        'correction': 'Rettifica',
        'service': 'Servizio',
    };
    return labels[type] || type;
};

const getTypeClass = (type) => {
    const classes = {
        'manual': 'badge bg-info',
        'correction': 'badge bg-warning',
        'service': 'badge bg-success',
    };
    return classes[type] || 'badge bg-secondary';
};

onMounted(() => {
    loadEntries();
});
</script>
