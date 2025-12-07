<template>
    <Head :title="isEdit ? 'Modifica Esperienza' : 'Nuova Esperienza'" />

    <Layout>
        <PageHeader
            :title="isEdit ? 'Modifica Esperienza' : 'Nuova Esperienza'"
            pageTitle="EasyNCC"
        />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            {{ isEdit ? 'Modifica Esperienza' : 'Nuova Esperienza' }}
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <form @submit.prevent="submitForm">
                            <!-- Fieldset 1: Anagrafica -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-file-list-3-line me-1"></i>
                                    Anagrafica
                                </legend>
                                <BRow>
                                    <!-- Supplier -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Fornitore</label>
                                            <select v-model="form.supplier_id" class="form-select">
                                                <option value="">Nessuno</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.username }} - {{ supplier.email }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Activity Type -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipologia</label>
                                            <select v-model="form.activity_type_id" class="form-select">
                                                <option value="">Nessuna</option>
                                                <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                                                    {{ type.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Name -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Descrizione Esperienza <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                class="form-control"
                                                required
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Service -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Servizio</label>
                                            <select v-model="form.service_id" class="form-select">
                                                <option value="">Nessuno</option>
                                                <option v-for="service in services" :key="service.id" :value="service.id">
                                                    {{ service.reference_number }} - {{ service.client ? `${service.client.name} ${service.client.surname}` : 'N/A' }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Company (Super Admin only) -->
                                    <BCol md="6" v-if="isSuperAdmin">
                                        <div class="mb-3">
                                            <label class="form-label">Azienda <span class="text-danger">*</span></label>
                                            <select v-model="form.company_id" class="form-select" :disabled="isEdit" required>
                                                <option value="">Seleziona azienda</option>
                                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                                    {{ company.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 2: Orario -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-time-line me-1"></i>
                                    Orario
                                </legend>
                                <BRow>
                                    <!-- Start Time -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Data e Ora Inizio <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.start_time"
                                                type="datetime-local"
                                                class="form-control"
                                                required
                                            />
                                        </div>
                                    </BCol>

                                    <!-- End Time -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Data e Ora Fine <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.end_time"
                                                type="datetime-local"
                                                class="form-control"
                                                required
                                            />
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 3: Economics -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-money-euro-circle-line me-1"></i>
                                    Economics
                                </legend>
                                <BRow>
                                    <!-- Cost -->
                                    <BCol md="4">
                                        <div class="mb-3">
                                            <label class="form-label">Costo Totale</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    v-model="form.cost"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Cost Per Person -->
                                    <BCol md="4">
                                        <div class="mb-3">
                                            <label class="form-label">Costo per Persona</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    v-model="form.cost_per_person"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Payment Type -->
                                    <BCol md="4">
                                        <div class="mb-3">
                                            <label class="form-label">Tipologia Pagamento</label>
                                            <select v-model="form.payment_type" class="form-select">
                                                <option value="">Nessuna</option>
                                                <option value="NESSUNO">Nessuno</option>
                                                <option value="INCLUSO">Incluso</option>
                                                <option value="CLIENTE">Cliente</option>
                                                <option value="AGENZIA">Agenzia</option>
                                            </select>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 4: Note -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-file-text-line me-1"></i>
                                    Note
                                </legend>
                                <BRow>
                                    <BCol md="12">
                                        <div class="mb-3">
                                            <label class="form-label">Note</label>
                                            <textarea
                                                v-model="form.notes"
                                                class="form-control"
                                                rows="4"
                                            ></textarea>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Error Messages -->
                            <div v-if="errors.length > 0" class="alert alert-danger">
                                <ul class="mb-0">
                                    <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                </ul>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('easyncc.activities.index')" class="btn btn-soft-secondary">
                                    Annulla
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ isEdit ? 'Aggiorna' : 'Crea' }}
                                </button>
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    activity: {
        type: Object,
        default: null
    }
});

const isEdit = computed(() => !!props.activity);

const form = ref({
    company_id: '',
    service_id: '',
    activity_type_id: '',
    name: '',
    supplier_id: '',
    start_time: '',
    end_time: '',
    cost: null,
    cost_per_person: null,
    payment_type: '',
    notes: ''
});

const companies = ref([]);
const services = ref([]);
const activityTypes = ref([]);
const suppliers = ref([]);
const currentUser = ref(null);
const loading = ref(false);
const errors = ref([]);

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
});

const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
    } catch (err) {
        console.error('Error loading current user:', err);
    }
};

const loadCompanies = async () => {
    if (!isSuperAdmin.value) return;

    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading companies:', err);
    }
};

const loadActivityTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/activity-types');
        activityTypes.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading activity types:', err);
    }
};

const loadSuppliers = async () => {
    try {
        const response = await axios.get('/api/users', {
            params: { is_fornitore: 1 }
        });
        suppliers.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading suppliers:', err);
    }
};

const loadServices = async () => {
    try {
        const response = await axios.get('/api/services');
        services.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading services:', err);
    }
};

const submitForm = async () => {
    loading.value = true;
    errors.value = [];

    try {
        // Prepare data
        const data = {
            ...form.value,
            service_id: form.value.service_id || null,
            activity_type_id: form.value.activity_type_id || null,
            supplier_id: form.value.supplier_id || null,
            cost: form.value.cost || null,
            cost_per_person: form.value.cost_per_person || null,
            payment_type: form.value.payment_type || null
        };

        if (isEdit.value) {
            await axios.put(`/api/activities/${props.activity.id}`, data);
        } else {
            await axios.post('/api/activities', data);
        }

        router.visit(route('easyncc.activities.index'));
    } catch (err) {
        if (err.response && err.response.status === 422) {
            const validationErrors = err.response.data.errors;
            errors.value = Object.values(validationErrors).flat();
        } else {
            errors.value = ['Si è verificato un errore durante il salvataggio'];
        }
        console.error('Error saving activity:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadActivityTypes();
    await loadSuppliers();
    await loadServices();

    if (isEdit.value && props.activity) {
        form.value = {
            company_id: props.activity.company_id,
            service_id: props.activity.service_id || '',
            activity_type_id: props.activity.activity_type_id || '',
            name: props.activity.name,
            supplier_id: props.activity.supplier_id || '',
            start_time: props.activity.start_time ? moment(props.activity.start_time).format('YYYY-MM-DDTHH:mm') : '',
            end_time: props.activity.end_time ? moment(props.activity.end_time).format('YYYY-MM-DDTHH:mm') : '',
            cost: props.activity.cost,
            cost_per_person: props.activity.cost_per_person,
            payment_type: props.activity.payment_type || '',
            notes: props.activity.notes || ''
        };
    }
});
</script>
