<template>
    <Head :title="isEdit ? 'Modifica Veicolo' : 'Nuovo Veicolo'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Veicolo' : 'Nuovo Veicolo'" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ isEdit ? 'Modifica Veicolo' : 'Nuovo Veicolo' }}</h5>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Form -->
                        <form v-else @submit.prevent="submitForm">
                            <BRow>
                                <!-- Company (only for super-admin) -->
                                <BCol v-if="isSuperAdmin" md="12" class="mb-3">
                                    <label for="company_id" class="form-label">Azienda *</label>
                                    <select
                                        id="company_id"
                                        v-model="form.company_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.company_id }"
                                    >
                                        <option value="">Seleziona un'azienda</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                    <small v-if="errors.company_id" class="text-danger d-block mt-1">
                                        {{ errors.company_id[0] }}
                                    </small>
                                </BCol>

                                <!-- License Plate -->
                                <BCol md="6" class="mb-3">
                                    <label for="license_plate" class="form-label">Targa *</label>
                                    <input
                                        id="license_plate"
                                        v-model="form.license_plate"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.license_plate }"
                                        placeholder="Es. AB123CD"
                                    />
                                    <small v-if="errors.license_plate" class="text-danger d-block mt-1">
                                        {{ errors.license_plate[0] }}
                                    </small>
                                </BCol>

                                <!-- Brand -->
                                <BCol md="6" class="mb-3">
                                    <label for="brand" class="form-label">Marca *</label>
                                    <input
                                        id="brand"
                                        v-model="form.brand"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.brand }"
                                        placeholder="Es. Mercedes"
                                    />
                                    <small v-if="errors.brand" class="text-danger d-block mt-1">
                                        {{ errors.brand[0] }}
                                    </small>
                                </BCol>

                                <!-- Model -->
                                <BCol md="6" class="mb-3">
                                    <label for="model" class="form-label">Modello *</label>
                                    <input
                                        id="model"
                                        v-model="form.model"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.model }"
                                        placeholder="Es. E-Class"
                                    />
                                    <small v-if="errors.model" class="text-danger d-block mt-1">
                                        {{ errors.model[0] }}
                                    </small>
                                </BCol>

                                <!-- Passenger Capacity -->
                                <BCol md="6" class="mb-3">
                                    <label for="passenger_capacity" class="form-label">Capacità Passeggeri *</label>
                                    <input
                                        id="passenger_capacity"
                                        v-model.number="form.passenger_capacity"
                                        type="number"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.passenger_capacity }"
                                        min="1"
                                    />
                                    <small v-if="errors.passenger_capacity" class="text-danger d-block mt-1">
                                        {{ errors.passenger_capacity[0] }}
                                    </small>
                                </BCol>

                                <!-- Purchase Date -->
                                <BCol md="6" class="mb-3">
                                    <label for="purchase_date" class="form-label">Data Acquisto</label>
                                    <input
                                        id="purchase_date"
                                        v-model="form.purchase_date"
                                        type="date"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.purchase_date }"
                                    />
                                    <small v-if="errors.purchase_date" class="text-danger d-block mt-1">
                                        {{ errors.purchase_date[0] }}
                                    </small>
                                </BCol>

                                <!-- Telepass License Number -->
                                <BCol md="6" class="mb-3">
                                    <label for="telepass_license_number" class="form-label">Numero Telepass</label>
                                    <input
                                        id="telepass_license_number"
                                        v-model="form.telepass_license_number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.telepass_license_number }"
                                    />
                                    <small v-if="errors.telepass_license_number" class="text-danger d-block mt-1">
                                        {{ errors.telepass_license_number[0] }}
                                    </small>
                                </BCol>

                                <!-- NCC License Number -->
                                <BCol md="6" class="mb-3">
                                    <label for="ncc_license_number" class="form-label">Numero Licenza NCC</label>
                                    <input
                                        id="ncc_license_number"
                                        v-model="form.ncc_license_number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.ncc_license_number }"
                                    />
                                    <small v-if="errors.ncc_license_number" class="text-danger d-block mt-1">
                                        {{ errors.ncc_license_number[0] }}
                                    </small>
                                </BCol>

                                <!-- License City -->
                                <BCol md="6" class="mb-3">
                                    <label for="license_city" class="form-label">Comune Licenza NCC</label>
                                    <input
                                        id="license_city"
                                        v-model="form.license_city"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.license_city }"
                                    />
                                    <small v-if="errors.license_city" class="text-danger d-block mt-1">
                                        {{ errors.license_city[0] }}
                                    </small>
                                </BCol>

                                <!-- Allow Overlapping -->
                                <BCol md="6" class="mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input
                                            id="allow_overlapping"
                                            v-model="form.allow_overlapping"
                                            type="checkbox"
                                            class="form-check-input"
                                        />
                                        <label for="allow_overlapping" class="form-check-label">
                                            Consenti Prenotazioni Sovrapposte
                                        </label>
                                    </div>
                                </BCol>

                                <!-- Notes -->
                                <BCol md="12" class="mb-3">
                                    <label for="notes" class="form-label">Note</label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.notes }"
                                        rows="4"
                                        placeholder="Note sul veicolo..."
                                    ></textarea>
                                    <small v-if="errors.notes" class="text-danger d-block mt-1">
                                        {{ errors.notes[0] }}
                                    </small>
                                </BCol>
                            </BRow>
                        </form>

                        <!-- ZTL Section -->
                        <div v-if="isEdit && props.vehicle?.id" class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">
                                <i class="ri-road-map-line me-2"></i>ZTL
                            </h6>
                            <div v-if="ztlLoading" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                            </div>
                            <div v-else-if="ztlItems.length === 0" class="text-muted small">
                                Nessuna ZTL configurata per questa azienda.
                            </div>
                            <div v-else class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="ztl in sortedZtlItems"
                                    :key="ztl.id"
                                    class="badge fs-6 ztl-tag"
                                    :class="isVehicleEnabledForZtl(ztl) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                                    @click="openZtlModal(ztl)"
                                >
                                    <i :class="isVehicleEnabledForZtl(ztl) ? 'ri-check-line me-1' : 'ri-close-line me-1'"></i>
                                    {{ ztl.city }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">
                                <i class="ri-road-map-line me-2"></i>ZTL
                            </h6>
                            <div class="alert alert-info mb-0">
                                <i class="ri-information-line me-2"></i>
                                La sezione ZTL sarà disponibile dopo aver creato il veicolo.
                            </div>
                        </div>

                        <!-- Vehicle Attachments -->
                        <div v-if="isEdit && props.vehicle?.id">
                            <VehicleAttachments :vehicle-id="props.vehicle.id" />
                        </div>
                        <div v-else class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">
                                <i class="ri-attachment-2 me-2"></i>Allegati Veicolo
                            </h6>
                            <div class="alert alert-info mb-0">
                                <i class="ri-information-line me-2"></i>
                                La sezione allegati sarà disponibile dopo aver creato il veicolo.
                            </div>
                        </div>

                        <!-- Vehicle Unavailabilities -->
                        <div v-if="isEdit && props.vehicle?.id">
                            <VehicleUnavailabilities :vehicle-id="props.vehicle.id" />
                        </div>
                        <div v-else class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">
                                <i class="ri-calendar-close-line me-2"></i>Periodi di Non Disponibilità
                            </h6>
                            <div class="alert alert-info mb-0">
                                <i class="ri-information-line me-2"></i>
                                La sezione periodi di non disponibilità sarà disponibile dopo aver creato il veicolo.
                            </div>
                        </div>

                        <!-- Audit Information -->
                        <div v-if="isEdit && props.vehicle" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Informazioni di Sistema</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Creato da</label>
                                <p class="text-muted mb-0">
                                    {{ props.vehicle.creator ? `${props.vehicle.creator.name} ${props.vehicle.creator.surname}` : '-' }}
                                    {{ props.vehicle.created_at ? `il ${formatDateTime(props.vehicle.created_at)}` : '' }}
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ultimo aggiornamento da</label>
                                <p class="text-muted mb-0">
                                    {{ props.vehicle.updater ? `${props.vehicle.updater.name} ${props.vehicle.updater.surname}` : '-' }}
                                    {{ props.vehicle.updated_at ? `il ${formatDateTime(props.vehicle.updated_at)}` : '' }}
                                </p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4">
                            <template v-if="isEdit">
                                <button
                                    @click="submitForm(false)"
                                    type="button"
                                    class="btn btn-primary"
                                    :disabled="submitting"
                                >
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    Salva
                                </button>
                            </template>
                            <template v-else>
                                <button
                                    @click="submitForm(true)"
                                    type="button"
                                    class="btn btn-primary"
                                    :disabled="submitting"
                                >
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    Crea
                                </button>
                                <button
                                    @click="submitForm(false)"
                                    type="button"
                                    class="btn btn-success ms-2"
                                    :disabled="submitting"
                                >
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    Crea ed Esci
                                </button>
                            </template>
                            <Link :href="route('easyncc.vehicles.index')" class="btn btn-secondary ms-2">
                                Esci
                            </Link>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
        <!-- ZTL Edit Modal -->
        <BModal v-model="showZtlModal" :title="ztlModalTitle" hide-footer size="lg">
            <form @submit.prevent="saveZtl">
                <BRow>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Comune</label>
                            <input
                                type="text"
                                class="form-control"
                                :value="ztlForm.city"
                                disabled
                            />
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Periodicità</label>
                            <input
                                type="text"
                                class="form-control"
                                :value="ztlForm.periodicity || '-'"
                                disabled
                            />
                        </div>
                    </BCol>
                </BRow>
                <BRow>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Scadenza</label>
                            <input
                                type="text"
                                class="form-control"
                                :value="ztlForm.expiration_date ? formatDate(ztlForm.expiration_date) : '-'"
                                disabled
                            />
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <div class="mt-1">
                                <span class="badge" :class="ztlForm.is_active ? 'bg-success' : 'bg-danger'">
                                    {{ ztlForm.is_active ? 'Attivo' : 'Inattivo' }}
                                </span>
                            </div>
                        </div>
                    </BCol>
                </BRow>
                <div v-if="ztlForm.notes" class="mb-3">
                    <label class="form-label">Note</label>
                    <p class="text-muted mb-0">{{ ztlForm.notes }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Veicoli Abilitati</label>
                    <div v-if="allVehicles.length === 0" class="text-muted">
                        Nessun veicolo disponibile
                    </div>
                    <div v-else class="border rounded p-3" style="max-height: 250px; overflow-y: auto;">
                        <div
                            v-for="v in allVehicles"
                            :key="v.id"
                            class="form-check mb-2"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                :id="'ztl-vehicle-' + v.id"
                                :value="v.id"
                                v-model="ztlForm.vehicle_ids"
                            />
                            <label class="form-check-label" :for="'ztl-vehicle-' + v.id">
                                <span class="targa-auto targa-sm me-2">
                                    <span class="codice-targa">{{ v.license_plate }}</span>
                                </span>
                                {{ v.brand }} {{ v.model }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-light me-2" @click="showZtlModal = false">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="ztlSaving">
                        <span v-if="ztlSaving" class="spinner-border spinner-border-sm me-1"></span>
                        Salva
                    </button>
                </div>
            </form>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import VehicleAttachments from '@/Components/ProfileFields/VehicleAttachments.vue';
import VehicleUnavailabilities from '@/Components/ProfileFields/VehicleUnavailabilities.vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicle: {
        type: Object,
        default: null
    }
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super-admin');

const isEdit = ref(!!props.vehicle);
const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const errors = ref({});
const companies = ref([]);

// ZTL state
const ztlItems = ref([]);
const ztlLoading = ref(false);
const showZtlModal = ref(false);
const ztlSaving = ref(false);
const ztlEditingId = ref(null);
const allVehicles = ref([]);
const ztlForm = ref({
    city: '',
    periodicity: '',
    expiration_date: '',
    notes: '',
    is_active: true,
    vehicle_ids: [],
});

const form = ref({
    company_id: props.vehicle?.company_id || '',
    license_plate: props.vehicle?.license_plate || '',
    brand: props.vehicle?.brand || '',
    model: props.vehicle?.model || '',
    passenger_capacity: props.vehicle?.passenger_capacity || '',
    purchase_date: props.vehicle?.purchase_date || '',
    ncc_license_number: props.vehicle?.ncc_license_number || '',
    telepass_license_number: props.vehicle?.telepass_license_number || '',
    license_city: props.vehicle?.license_city || '',
    allow_overlapping: props.vehicle?.allow_overlapping || false,
    status: props.vehicle?.status || 'in_service',
    notes: props.vehicle?.notes || ''
});

const submitForm = async (stayOnPage = false) => {
    submitting.value = true;
    error.value = '';
    errors.value = {};

    try {
        const url = isEdit.value ? `/api/vehicles/${props.vehicle.id}` : '/api/vehicles';
        const method = isEdit.value ? 'put' : 'post';

        const response = await axios[method](url, form.value);

        if (isEdit.value) {
            // After editing, return to the vehicles list
            router.visit(route('easyncc.vehicles.index'));
        } else {
            // For new creation
            if (stayOnPage && response.data?.id) {
                // Redirect to edit page
                router.visit(route('easyncc.vehicles.edit', response.data.id));
            } else {
                // Redirect to index
                router.visit(route('easyncc.vehicles.index'));
            }
        }
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            error.value = 'Errore nel salvataggio del veicolo';
        }
        console.error('Error submitting form:', err);
    } finally {
        submitting.value = false;
    }
};

const loadCompanies = async () => {
    if (!isSuperAdmin.value) return;

    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading companies:', err);
    }
};

// ZTL computed
const sortedZtlItems = computed(() => {
    return [...ztlItems.value].sort((a, b) =>
        (a.city || '').localeCompare(b.city || '')
    );
});

const ztlModalTitle = computed(() => {
    return ztlForm.value.city ? `ZTL - ${ztlForm.value.city}` : 'Modifica ZTL';
});

const isVehicleEnabledForZtl = (ztl) => {
    if (!ztl.vehicles || !props.vehicle?.id) return false;
    return ztl.vehicles.some(v => v.id === props.vehicle.id);
};

// ZTL methods
const loadZtlItems = async () => {
    ztlLoading.value = true;
    try {
        const response = await axios.get('/api/dictionaries/ztl');
        ztlItems.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading ZTL items:', err);
        ztlItems.value = [];
    } finally {
        ztlLoading.value = false;
    }
};

const loadAllVehicles = async () => {
    try {
        const response = await axios.get('/api/vehicles', { params: { per_page: 200 } });
        allVehicles.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading vehicles for ZTL:', err);
        allVehicles.value = [];
    }
};

const openZtlModal = (ztl) => {
    ztlEditingId.value = ztl.id;
    ztlForm.value = {
        city: ztl.city || '',
        periodicity: ztl.periodicity || '',
        expiration_date: ztl.expiration_date || '',
        notes: ztl.notes || '',
        is_active: ztl.is_active,
        vehicle_ids: ztl.vehicles ? ztl.vehicles.map(v => v.id) : [],
    };
    showZtlModal.value = true;
};

const saveZtl = async () => {
    ztlSaving.value = true;
    try {
        await axios.put(`/api/dictionaries/ztl/${ztlEditingId.value}`, {
            city: ztlForm.value.city,
            periodicity: ztlForm.value.periodicity,
            expiration_date: ztlForm.value.expiration_date || null,
            notes: ztlForm.value.notes,
            is_active: ztlForm.value.is_active,
            vehicle_ids: ztlForm.value.vehicle_ids,
        });
        showZtlModal.value = false;
        await loadZtlItems();
    } catch (err) {
        console.error('Error saving ZTL:', err);
        alert('Errore durante il salvataggio della ZTL');
    } finally {
        ztlSaving.value = false;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return moment(dateStr).format('DD/MM/YYYY');
};

onMounted(() => {
    loading.value = false;
    loadCompanies();
    if (isEdit.value && props.vehicle?.id) {
        loadZtlItems();
        loadAllVehicles();
    }
});

// Utility function for formatting datetime
const formatDateTime = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY HH:mm');
};
</script>

<style scoped>
.ztl-tag {
    cursor: pointer;
    transition: opacity 0.2s;
}

.ztl-tag:hover {
    opacity: 0.75;
}

.targa-auto {
    background: linear-gradient(to right, #003399 0%, #003399 8%, #ffffff 8%, #ffffff 92%, #003399 92%, #003399 100%);
    border: 1px solid #000;
    border-radius: 3px;
    padding: 3px 8px;
    display: inline-block;
    font-family: 'Arial', sans-serif;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    min-width: 90px;
}

.targa-sm {
    padding: 2px 6px;
    min-width: 70px;
}

.targa-sm .codice-targa {
    font-size: 12px;
}

.codice-targa {
    font-size: 14px;
    font-weight: bold;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
}
</style>
