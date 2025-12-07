<template>
    <Head :title="isEdit ? 'Modifica Task' : 'Nuovo Task'" />

    <Layout>
        <PageHeader
            :title="isEdit ? 'Modifica Task' : 'Nuovo Task'"
            pageTitle="EasyNCC"
        />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            {{ isEdit ? 'Modifica Task' : 'Nuovo Task' }}
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
                                    <!-- Company (Super Admin only) -->
                                    <BCol md="6" v-if="isSuperAdmin">
                                        <div class="mb-3">
                                            <label class="form-label">Azienda <span class="text-danger">*</span></label>
                                            <select v-model="form.company_id" class="form-select" :disabled="isEdit || isRestrictedUser" required>
                                                <option value="">Seleziona azienda</option>
                                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                                    {{ company.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Name -->
                                    <BCol :md="isSuperAdmin ? 6 : 12">
                                        <div class="mb-3">
                                            <label class="form-label">Nome Task <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                class="form-control"
                                                :disabled="isRestrictedUser"
                                                required
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Service -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Servizio</label>
                                            <select v-model="form.service_id" class="form-select" :disabled="isRestrictedUser">
                                                <option value="">Nessuno</option>
                                                <option v-for="service in services" :key="service.id" :value="service.id">
                                                    {{ service.reference_number }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Due Date -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Scadenza</label>
                                            <input
                                                v-model="form.due_date"
                                                type="date"
                                                class="form-control"
                                                :disabled="isRestrictedUser"
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Assigned To (Multi-Select) -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Assegnatari</label>
                                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                                <div v-for="user in assignableUsers" :key="user.id" class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :value="user.id"
                                                        v-model="form.assigned_users"
                                                        :id="`assignee-${user.id}`"
                                                        :disabled="isRestrictedUser"
                                                    />
                                                    <label class="form-check-label" :for="`assignee-${user.id}`">
                                                        {{ user.name }} {{ user.surname }} ({{ user.role }})
                                                    </label>
                                                </div>
                                                <div v-if="assignableUsers.length === 0" class="text-muted small">
                                                    Nessun utente disponibile
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Status -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                                            <select v-model="form.status" class="form-select" required>
                                                <option value="to_complete">Da Completare</option>
                                                <option value="completed">Completato</option>
                                                <option value="cancelled">Annullato</option>
                                            </select>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 2: Note -->
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
                                <Link :href="route('easyncc.tasks.index')" class="btn btn-soft-secondary">
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
    task: {
        type: Object,
        default: null
    }
});

const isEdit = computed(() => !!props.task);

const form = ref({
    company_id: '',
    service_id: '',
    name: '',
    due_date: '',
    assigned_users: [],
    status: 'to_complete',
    notes: ''
});

const companies = ref([]);
const assignableUsers = ref([]);
const services = ref([]);
const currentUser = ref(null);
const loading = ref(false);
const errors = ref([]);

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
});

const isRestrictedUser = computed(() => {
    // Driver and accountant can only edit status and notes
    const role = currentUser.value?.role;
    return role === 'driver' || role === 'accountant';
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

const loadAssignableUsers = async () => {
    try {
        // Load users with roles that can be assigned tasks
        const roles = ['admin', 'operator', 'driver', 'contabilita'];
        const allUsers = [];

        for (const role of roles) {
            const response = await axios.get('/api/users', {
                params: {
                    role: role,
                    per_page: 100
                }
            });
            if (response.data.data) {
                allUsers.push(...response.data.data);
            }
        }

        // Remove duplicates by id
        const uniqueUsers = allUsers.filter((user, index, self) =>
            index === self.findIndex((u) => u.id === user.id)
        );

        assignableUsers.value = uniqueUsers;
    } catch (err) {
        console.error('Error loading assignable users:', err);
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
        let data = {};

        if (isRestrictedUser.value) {
            // Driver and accountant can only update status and notes
            data = {
                status: form.value.status,
                notes: form.value.notes
            };
        } else {
            // Admin, operator, super-admin can update all fields
            data = {
                ...form.value,
                service_id: form.value.service_id || null,
                assigned_users: form.value.assigned_users.length > 0 ? form.value.assigned_users : [],
                due_date: form.value.due_date || null
            };
        }

        if (isEdit.value) {
            await axios.put(`/api/tasks/${props.task.id}`, data);
        } else {
            await axios.post('/api/tasks', data);
        }

        router.visit(route('easyncc.tasks.index'));
    } catch (err) {
        if (err.response && err.response.status === 422) {
            const validationErrors = err.response.data.errors;
            errors.value = Object.values(validationErrors).flat();
        } else {
            errors.value = ['Si è verificato un errore durante il salvataggio'];
        }
        console.error('Error saving task:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadAssignableUsers();
    await loadServices();

    if (isEdit.value && props.task) {
        form.value = {
            company_id: props.task.company_id,
            service_id: props.task.service_id || '',
            name: props.task.name,
            due_date: props.task.due_date ? moment(props.task.due_date).format('YYYY-MM-DD') : '',
            assigned_users: props.task.assigned_users ? props.task.assigned_users.map(u => u.id) : [],
            status: props.task.status || 'to_complete',
            notes: props.task.notes || ''
        };
    } else {
        // Check for service_id in query parameters
        const urlParams = new URLSearchParams(window.location.search);
        const serviceId = urlParams.get('service_id');
        if (serviceId) {
            form.value.service_id = parseInt(serviceId);
        }
    }
});
</script>
