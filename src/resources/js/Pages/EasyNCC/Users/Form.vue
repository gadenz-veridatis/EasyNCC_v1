<template>
    <Head :title="isEdit ? 'Modifica Utente' : 'Nuovo Utente'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Utente' : 'Nuovo Utente'" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ isEdit ? 'Modifica Utente' : 'Nuovo Utente' }}</h5>
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
                            <h6 class="card-subtitle mb-3 text-muted">Dati Generali</h6>

                            <!-- Area dati identificativi -->
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="float-none w-auto px-2 fs-6 text-muted">Dati Identificativi</legend>
                                <BRow>
                                    <!-- Username -->
                                    <BCol md="6" class="mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input
                                            id="username"
                                            v-model="form.username"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.username }"
                                            placeholder="username"
                                        />
                                        <small v-if="errors.username" class="text-danger d-block mt-1">
                                            {{ errors.username[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Role -->
                                    <BCol md="6" class="mb-3">
                                        <label for="role" class="form-label">Ruolo *</label>
                                        <select
                                            id="role"
                                            v-model="form.role"
                                            class="form-select"
                                            :class="{ 'is-invalid': errors.role }"
                                        >
                                            <option value="">Seleziona un ruolo</option>
                                            <option value="super-admin">Super Admin</option>
                                            <option value="admin">Amministratore</option>
                                            <option value="operator">Operatore</option>
                                            <option value="driver">Driver</option>
                                            <option value="collaboratore">Collaboratore</option>
                                            <option value="contabilita">Contabilità</option>
                                        </select>
                                        <small v-if="errors.role" class="text-danger d-block mt-1">
                                            {{ errors.role[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Company -->
                                    <BCol md="6" class="mb-3">
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

                                    <!-- Is Active -->
                                    <BCol md="6" class="mb-3">
                                        <div class="form-check form-switch" style="padding-top: 2.2rem;">
                                            <input
                                                id="is_active"
                                                v-model="form.is_active"
                                                type="checkbox"
                                                class="form-check-input"
                                            />
                                            <label for="is_active" class="form-check-label">
                                                Utente Attivo
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Se attivo, l'utente può accedere all'applicazione
                                        </small>
                                    </BCol>

                                    <!-- Password -->
                                    <BCol md="6" class="mb-3">
                                        <label for="password" class="form-label">
                                            Password {{ !isEdit ? '*' : '' }}
                                        </label>
                                        <div class="input-group">
                                            <input
                                                id="password"
                                                v-model="form.password"
                                                :type="showPassword ? 'text' : 'password'"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.password }"
                                                :placeholder="isEdit ? 'Lascia vuoto per non modificare' : 'Inserisci una password'"
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="showPassword = !showPassword"
                                            >
                                                <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                            </button>
                                        </div>
                                        <small v-if="errors.password" class="text-danger d-block mt-1">
                                            {{ errors.password[0] }}
                                        </small>
                                        <small v-else-if="isEdit" class="text-muted d-block mt-1">
                                            Lascia vuoto per mantenere la password attuale
                                        </small>
                                    </BCol>

                                    <!-- Password Confirmation -->
                                    <BCol md="6" class="mb-3">
                                        <label for="password_confirmation" class="form-label">
                                            Conferma Password {{ !isEdit ? '*' : '' }}
                                        </label>
                                        <div class="input-group">
                                            <input
                                                id="password_confirmation"
                                                v-model="form.password_confirmation"
                                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.password_confirmation }"
                                                :placeholder="isEdit ? 'Lascia vuoto per non modificare' : 'Conferma la password'"
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                            >
                                                <i :class="showPasswordConfirmation ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                            </button>
                                        </div>
                                        <small v-if="errors.password_confirmation" class="text-danger d-block mt-1">
                                            {{ errors.password_confirmation[0] }}
                                        </small>
                                        <small v-else-if="isEdit" class="text-muted d-block mt-1">
                                            Conferma la nuova password se vuoi modificarla
                                        </small>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Area dati anagrafici -->
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="float-none w-auto px-2 fs-6 text-muted">Dati Anagrafici</legend>
                                <BRow>
                                    <!-- Name -->
                                    <BCol md="6" class="mb-3">
                                        <label for="name" class="form-label">Nome *</label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.name }"
                                            placeholder="Nome"
                                        />
                                        <small v-if="errors.name" class="text-danger d-block mt-1">
                                            {{ errors.name[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Surname -->
                                    <BCol md="6" class="mb-3">
                                        <label for="surname" class="form-label">Cognome *</label>
                                        <input
                                            id="surname"
                                            v-model="form.surname"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.surname }"
                                            placeholder="Cognome"
                                        />
                                        <small v-if="errors.surname" class="text-danger d-block mt-1">
                                            {{ errors.surname[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Email -->
                                    <BCol md="6" class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.email }"
                                            placeholder="email@example.com"
                                            autocomplete="off"
                                        />
                                        <small v-if="errors.email" class="text-danger d-block mt-1">
                                            {{ errors.email[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Phone -->
                                    <BCol md="6" class="mb-3">
                                        <label for="phone" class="form-label">Telefono</label>
                                        <input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.phone }"
                                            placeholder="+39 123 4567890"
                                        />
                                        <small v-if="errors.phone" class="text-danger d-block mt-1">
                                            {{ errors.phone[0] }}
                                        </small>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Area intermediazione -->
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="float-none w-auto px-2 fs-6 text-muted">Intermediazione</legend>
                                <BRow>
                                    <!-- Is Intermediario -->
                                    <BCol md="6" class="mb-3">
                                        <div class="form-check form-switch">
                                            <input
                                                id="is_intermediario"
                                                v-model="form.is_intermediario"
                                                type="checkbox"
                                                class="form-check-input"
                                            />
                                            <label for="is_intermediario" class="form-check-label">
                                                È Intermediario
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            L'utente può intermediare servizi per conto di terzi
                                        </small>
                                    </BCol>

                                    <!-- Percentuale Commissione -->
                                    <BCol md="6" class="mb-3">
                                        <label for="percentuale_commissione" class="form-label">Percentuale Commissione (%)</label>
                                        <input
                                            id="percentuale_commissione"
                                            v-model.number="form.percentuale_commissione"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.percentuale_commissione }"
                                            placeholder="0.00"
                                        />
                                        <small v-if="errors.percentuale_commissione" class="text-danger d-block mt-1">
                                            {{ errors.percentuale_commissione[0] }}
                                        </small>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Note -->
                            <BRow>
                                <BCol md="12" class="mb-3">
                                    <label for="notes" class="form-label">Note</label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.notes }"
                                        rows="4"
                                        placeholder="Note sull'utente..."
                                    ></textarea>
                                    <small v-if="errors.notes" class="text-danger d-block mt-1">
                                        {{ errors.notes[0] }}
                                    </small>
                                </BCol>
                            </BRow>

                            <!-- Role-specific Profile Fields -->
                            <div v-if="showProfileFields" class="mt-4 pt-3 border-top">
                                <DriverProfileFields
                                    v-if="form.role === 'driver'"
                                    v-model="profileData"
                                    :errors="profileErrors"
                                />
                                <BusinessProfileFields
                                    v-if="form.role === 'collaboratore'"
                                    v-model="profileData"
                                    :role="form.role"
                                    :errors="profileErrors"
                                />
                                <OperatorProfileFields
                                    v-if="form.role === 'operator'"
                                    v-model="profileData"
                                    :errors="profileErrors"
                                />
                            </div>

                            <!-- Driver Attachments (only in edit mode for driver role) -->
                            <div v-if="isEdit && form.role === 'driver' && props.user?.id">
                                <DriverAttachments :user-id="props.user.id" />
                            </div>

                            <!-- Buttons -->
                            <div class="mt-4">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="submitting"
                                >
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ isEdit ? 'Aggiorna' : 'Crea' }}
                                </button>
                                <Link :href="route('easyncc.users.index')" class="btn btn-secondary ms-2">
                                    Annulla
                                </Link>
                            </div>

                            <!-- Error Message -->
                            <div v-if="error" class="alert alert-danger mt-3" role="alert">
                                {{ error }}
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import DriverProfileFields from '@/Components/ProfileFields/DriverProfileFields.vue';
import BusinessProfileFields from '@/Components/ProfileFields/BusinessProfileFields.vue';
import OperatorProfileFields from '@/Components/ProfileFields/OperatorProfileFields.vue';
import DriverAttachments from '@/Components/ProfileFields/DriverAttachments.vue';
import axios from 'axios';

const props = defineProps({
    user: {
        type: Object,
        default: null
    }
});

// Check if we're in edit mode - props.user must exist AND have an id
const isEdit = ref(!!(props.user && props.user.id));

const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const errors = ref({});
const profileErrors = ref({});
const companies = ref([]);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// In create mode (no props.user), all fields should be empty
// In edit mode (props.user exists), populate with existing data
const form = ref({
    name: isEdit.value ? (props.user?.name || '') : '',
    surname: isEdit.value ? (props.user?.surname || '') : '',
    email: isEdit.value ? (props.user?.email || '') : '',
    username: isEdit.value ? (props.user?.username || '') : '',
    role: isEdit.value ? (props.user?.role || '') : '',
    company_id: isEdit.value ? (props.user?.company_id || '') : '',
    is_active: isEdit.value ? (props.user?.is_active !== undefined ? props.user.is_active : true) : true,
    is_intermediario: isEdit.value ? (props.user?.is_intermediario || false) : false,
    percentuale_commissione: isEdit.value ? (props.user?.percentuale_commissione || null) : null,
    phone: isEdit.value ? (props.user?.phone || '') : '',
    notes: isEdit.value ? (props.user?.notes || '') : '',
    password: '',
    password_confirmation: ''
});

// Initialize profile data based on existing user profile
const getInitialProfileData = () => {
    // Only load profile data in edit mode
    if (!isEdit.value || !props.user) return {};

    let profile = null;
    // Check both camelCase and snake_case due to Laravel serialization
    if (props.user.driverProfile || props.user.driver_profile) {
        profile = props.user.driverProfile || props.user.driver_profile;
    } else if (props.user.clientProfile || props.user.client_profile) {
        profile = props.user.clientProfile || props.user.client_profile;
    } else if (props.user.operatorProfile || props.user.operator_profile) {
        profile = props.user.operatorProfile || props.user.operator_profile;
    }

    if (!profile) return {};

    // Filter out system fields (id, user_id, created_at, updated_at, etc.)
    const { id, user_id, created_at, updated_at, ...cleanProfile } = profile;

    // business_contacts are already cleaned by the backend (BusinessContact model has $hidden)
    // so we just need to pass them through as-is

    return cleanProfile;
};

const profileData = ref(getInitialProfileData());

// Computed property to determine if profile fields should be shown
const showProfileFields = computed(() => {
    const rolesWithProfiles = ['driver', 'collaboratore', 'operator'];
    return rolesWithProfiles.includes(form.value.role);
});

// Watch role changes to reset profile data
watch(() => form.value.role, (newRole, oldRole) => {
    if (newRole !== oldRole) {
        profileData.value = {};
    }
});

const loadCompanies = async () => {
    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading companies:', err);
        companies.value = [];
    }
};

const submitForm = async () => {
    submitting.value = true;
    error.value = '';
    errors.value = {};
    profileErrors.value = {};

    try {
        const url = isEdit.value ? `/api/users/${props.user.id}` : '/api/users';
        const method = isEdit.value ? 'put' : 'post';

        // Remove empty password fields for edit mode
        const dataToSubmit = { ...form.value };
        if (isEdit.value && !dataToSubmit.password) {
            delete dataToSubmit.password;
            delete dataToSubmit.password_confirmation;
        }

        // Add profile data if present
        if (showProfileFields.value && Object.keys(profileData.value).length > 0) {
            dataToSubmit.profile = profileData.value;
        }

        const response = await axios[method](url, dataToSubmit);

        // If creating a new driver, redirect to edit page to allow uploading attachments
        if (!isEdit.value && form.value.role === 'driver' && response.data?.id) {
            router.visit(route('easyncc.users.edit', response.data.id));
        } else {
            router.visit(route('easyncc.users.index'));
        }
    } catch (err) {
        if (err.response?.status === 422) {
            const responseErrors = err.response.data.errors || {};

            // Separate user errors from profile errors
            errors.value = {};
            profileErrors.value = {};

            Object.keys(responseErrors).forEach(key => {
                if (key.startsWith('profile.')) {
                    const profileKey = key.replace('profile.', '');
                    profileErrors.value[profileKey] = responseErrors[key];
                } else {
                    errors.value[key] = responseErrors[key];
                }
            });
        } else {
            error.value = 'Errore nel salvataggio dell\'utente';
        }
        console.error('Error submitting form:', err);
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    loading.value = true;
    loadCompanies().then(() => {
        loading.value = false;
    });
});
</script>
