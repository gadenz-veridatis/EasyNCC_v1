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
                                    <!-- 1. Company (only for super-admin) - Full width -->
                                    <BCol md="12" class="mb-3" v-if="isSuperAdmin">
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

                                    <!-- 2. Username -->
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

                                    <!-- 3. Role -->
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

                                    <!-- 4. Password -->
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

                                    <!-- 5. Password Confirmation -->
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

                                    <!-- 6. Is Active -->
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
                                </BRow>
                            </fieldset>

                            <!-- Area dati anagrafici -->
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="float-none w-auto px-2 fs-6 text-muted">Dati Anagrafici</legend>
                                <BRow>
                                    <!-- Surname -->
                                    <BCol md="6" class="mb-3">
                                        <label for="surname" class="form-label">Cognome (o Nome Azienda) *</label>
                                        <input
                                            id="surname"
                                            v-model="form.surname"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.surname }"
                                            placeholder="Cognome o Nome Azienda"
                                            required
                                        />
                                        <small v-if="errors.surname" class="text-danger d-block mt-1">
                                            {{ errors.surname[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Name -->
                                    <BCol md="6" class="mb-3">
                                        <label for="name" class="form-label">Nome</label>
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

                                    <!-- Nickname -->
                                    <BCol md="6" class="mb-3">
                                        <label for="nickname" class="form-label">Nickname</label>
                                        <input
                                            id="nickname"
                                            v-model="form.nickname"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.nickname }"
                                            placeholder="Nickname o soprannome"
                                        />
                                        <small v-if="errors.nickname" class="text-danger d-block mt-1">
                                            {{ errors.nickname[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Address -->
                                    <BCol md="12" class="mb-3">
                                        <label for="address" class="form-label">Indirizzo</label>
                                        <textarea
                                            id="address"
                                            v-model="form.address"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.address }"
                                            rows="2"
                                            placeholder="Via/Piazza e numero civico"
                                        ></textarea>
                                        <small v-if="errors.address" class="text-danger d-block mt-1">
                                            {{ errors.address[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Postal Code -->
                                    <BCol md="3" class="mb-3">
                                        <label for="postal_code" class="form-label">CAP</label>
                                        <input
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.postal_code }"
                                            placeholder="00100"
                                        />
                                        <small v-if="errors.postal_code" class="text-danger d-block mt-1">
                                            {{ errors.postal_code[0] }}
                                        </small>
                                    </BCol>

                                    <!-- City -->
                                    <BCol md="4" class="mb-3">
                                        <label for="city" class="form-label">Comune</label>
                                        <input
                                            id="city"
                                            v-model="form.city"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.city }"
                                            placeholder="Roma"
                                        />
                                        <small v-if="errors.city" class="text-danger d-block mt-1">
                                            {{ errors.city[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Province -->
                                    <BCol md="2" class="mb-3">
                                        <label for="province" class="form-label">Provincia</label>
                                        <input
                                            id="province"
                                            v-model="form.province"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.province }"
                                            placeholder="RM"
                                            maxlength="2"
                                        />
                                        <small v-if="errors.province" class="text-danger d-block mt-1">
                                            {{ errors.province[0] }}
                                        </small>
                                    </BCol>

                                    <!-- Country -->
                                    <BCol md="3" class="mb-3">
                                        <label for="country" class="form-label">Nazione</label>
                                        <input
                                            id="country"
                                            v-model="form.country"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.country }"
                                            placeholder="Italia"
                                        />
                                        <small v-if="errors.country" class="text-danger d-block mt-1">
                                            {{ errors.country[0] }}
                                        </small>
                                    </BCol>

                                    <!-- User Photo -->
                                    <BCol md="12" class="mb-3">
                                        <label for="user_photo" class="form-label">Fotografia Profilo</label>
                                        <input
                                            id="user_photo"
                                            type="file"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.user_photo }"
                                            accept="image/*"
                                            @change="handleUserPhotoUpload"
                                        />
                                        <small class="text-muted d-block mt-1">
                                            Formati supportati: JPG, PNG, GIF. Dimensione massima: 2MB
                                        </small>
                                        <small v-if="errors.user_photo" class="text-danger d-block mt-1">
                                            {{ errors.user_photo[0] }}
                                        </small>
                                        <!-- Preview existing photo -->
                                        <div v-if="userPhotoPreview || (isEdit && props.user?.user_photo)" class="mt-2">
                                            <img
                                                :src="userPhotoPreview || `/storage/${props.user.user_photo}`"
                                                alt="Anteprima foto"
                                                class="img-thumbnail"
                                                style="max-width: 200px; max-height: 200px;"
                                            />
                                            <button
                                                v-if="userPhotoPreview"
                                                type="button"
                                                class="btn btn-sm btn-danger ms-2"
                                                @click="removeUserPhoto"
                                            >
                                                <i class="ri-delete-bin-line"></i> Rimuovi
                                            </button>
                                        </div>
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
                                    @logo-file-change="handleLogoFileChange"
                                />
                                <OperatorProfileFields
                                    v-if="form.role === 'operator'"
                                    v-model="profileData"
                                    :errors="profileErrors"
                                />
                            </div>

                            <!-- Driver Attachments (only in edit mode for driver role) -->
                            <div v-if="isEdit && form.role === 'driver' && props.user?.id">
                                <DriverAttachments :user-id="props.user.id" :company-id="form.company_id" />
                            </div>

                            <!-- Info for driver attachments in create mode -->
                            <div v-if="!isEdit && form.role === 'driver'" class="mt-4 pt-4 border-top">
                                <h6 class="text-muted mb-3">
                                    <i class="ri-attachment-2 me-2"></i>Allegati Conducente
                                </h6>
                                <div class="alert alert-info mb-0">
                                    <i class="ri-information-line me-2"></i>
                                    La sezione allegati sarà disponibile dopo aver creato l'utente.
                                </div>
                            </div>

                            <!-- Audit Information -->
                            <div v-if="isEdit && props.user" class="row mb-4 pt-3 border-top">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">Informazioni di Sistema</h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Creato da</label>
                                    <p class="text-muted mb-0">
                                        {{ props.user.creator ? `${props.user.creator.name} ${props.user.creator.surname}` : '-' }}
                                        {{ props.user.created_at ? `il ${formatDateTime(props.user.created_at)}` : '' }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Ultimo aggiornamento da</label>
                                    <p class="text-muted mb-0">
                                        {{ props.user.updater ? `${props.user.updater.name} ${props.user.updater.surname}` : '-' }}
                                        {{ props.user.updated_at ? `il ${formatDateTime(props.user.updated_at)}` : '' }}
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
                                <Link :href="route('easyncc.users.index')" class="btn btn-secondary ms-2">
                                    Esci
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import DriverProfileFields from '@/Components/ProfileFields/DriverProfileFields.vue';
import BusinessProfileFields from '@/Components/ProfileFields/BusinessProfileFields.vue';
import OperatorProfileFields from '@/Components/ProfileFields/OperatorProfileFields.vue';
import DriverAttachments from '@/Components/ProfileFields/DriverAttachments.vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    user: {
        type: Object,
        default: null
    }
});

// Get current user from Inertia page props
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');
const isAdmin = computed(() => currentUser.value?.role === 'admin');

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
const userPhotoPreview = ref(null);
const userPhotoFile = ref(null);
const logoFile = ref(null);

// In create mode (no props.user), all fields should be empty
// In edit mode (props.user exists), populate with existing data
const form = ref({
    name: isEdit.value ? (props.user?.name || '') : '',
    surname: isEdit.value ? (props.user?.surname || '') : '',
    email: isEdit.value ? (props.user?.email || '') : '',
    username: isEdit.value ? (props.user?.username || '') : '',
    role: isEdit.value ? (props.user?.role || '') : '',
    // For admin users in create mode, set company_id to their own company
    company_id: isEdit.value ? (props.user?.company_id || '') : (isAdmin.value ? currentUser.value?.company_id || '' : ''),
    is_active: isEdit.value ? (props.user?.is_active !== undefined ? props.user.is_active : true) : true,
    is_intermediario: isEdit.value ? (props.user?.is_intermediario || false) : false,
    percentuale_commissione: isEdit.value ? (props.user?.percentuale_commissione || null) : null,
    phone: isEdit.value ? (props.user?.phone || '') : '',
    nickname: isEdit.value ? (props.user?.nickname || '') : '',
    address: isEdit.value ? (props.user?.address || '') : '',
    postal_code: isEdit.value ? (props.user?.postal_code || '') : '',
    city: isEdit.value ? (props.user?.city || '') : '',
    province: isEdit.value ? (props.user?.province || '') : '',
    country: isEdit.value ? (props.user?.country || 'Italia') : 'Italia',
    notes: isEdit.value ? (props.user?.notes || '') : '',
    password: '',
    password_confirmation: ''
});

// Initialize profile data based on existing user profile
const getInitialProfileData = () => {
    // In edit mode, load profile data from user
    if (isEdit.value && props.user) {
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

        // Format birth_date for date input (YYYY-MM-DD format)
        if (cleanProfile.birth_date) {
            // Extract only the date part from ISO 8601 format (YYYY-MM-DDTHH:MM:SS.ssssssZ)
            cleanProfile.birth_date = cleanProfile.birth_date.split('T')[0];
        }

        // business_contacts are already cleaned by the backend (BusinessContact model has $hidden)
        // so we just need to pass them through as-is

        return cleanProfile;
    }

    // In create mode, return empty object (will be populated by watch when role changes)
    return {};
};

const profileData = ref(getInitialProfileData());

// Computed property to determine if profile fields should be shown
const showProfileFields = computed(() => {
    const rolesWithProfiles = ['driver', 'collaboratore', 'operator'];
    return rolesWithProfiles.includes(form.value.role);
});

// Watch role changes to reset profile data with proper defaults
watch(() => form.value.role, (newRole, oldRole) => {
    if (newRole !== oldRole) {
        // Reset profile data with role-specific defaults
        if (newRole === 'driver') {
            profileData.value = {
                birth_date: '',
                fiscal_code: '',
                vat_number: '',
                hourly_rate: null,
                bank_name: '',
                iban: '',
                assigned_vehicle_id: '',
                color: '#3788d8',
                allow_overlapping: false,
                notes: ''
            };
        } else {
            profileData.value = {};
        }
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

const handleUserPhotoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        userPhotoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            userPhotoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeUserPhoto = () => {
    userPhotoFile.value = null;
    userPhotoPreview.value = null;
    // Reset file input
    const fileInput = document.getElementById('user_photo');
    if (fileInput) {
        fileInput.value = '';
    }
};

const handleLogoFileChange = (file) => {
    logoFile.value = file;
};

const submitForm = async (stayOnPage = false) => {
    submitting.value = true;
    error.value = '';
    errors.value = {};
    profileErrors.value = {};

    try {
        const url = isEdit.value ? `/api/users/${props.user.id}` : '/api/users';
        const method = isEdit.value ? 'put' : 'post';

        // Use FormData if there's a file to upload
        let dataToSubmit;
        let headers = {};

        if (userPhotoFile.value || logoFile.value) {
            dataToSubmit = new FormData();

            // Add all form fields
            Object.keys(form.value).forEach(key => {
                if (form.value[key] !== null && form.value[key] !== '') {
                    // Skip password fields if empty in edit mode
                    if (isEdit.value && (key === 'password' || key === 'password_confirmation') && !form.value[key]) {
                        return;
                    }
                    dataToSubmit.append(key, form.value[key]);
                }
            });

            // Add user photo file if present
            if (userPhotoFile.value) {
                dataToSubmit.append('user_photo', userPhotoFile.value);
            }

            // Add profile data if present
            if (showProfileFields.value && Object.keys(profileData.value).length > 0) {
                dataToSubmit.append('profile', JSON.stringify(profileData.value));
            }

            // Add logo file if present (for collaboratore role)
            if (logoFile.value) {
                dataToSubmit.append('logo', logoFile.value);
            }

            // For PUT requests via FormData, we need to use POST with _method
            if (isEdit.value) {
                dataToSubmit.append('_method', 'PUT');
            }

            headers = { 'Content-Type': 'multipart/form-data' };
        } else {
            // No file upload, use regular JSON
            dataToSubmit = { ...form.value };

            // Remove empty password fields for edit mode
            if (isEdit.value && !dataToSubmit.password) {
                delete dataToSubmit.password;
                delete dataToSubmit.password_confirmation;
            }

            // Add profile data if present
            if (showProfileFields.value && Object.keys(profileData.value).length > 0) {
                dataToSubmit.profile = profileData.value;
            }
        }

        const response = await axios({
            method: (userPhotoFile.value || logoFile.value) && isEdit.value ? 'post' : method,
            url,
            data: dataToSubmit,
            headers
        });

        if (isEdit.value) {
            // After editing, return to the users list
            router.visit(route('easyncc.users.index'));
        } else {
            // For new creation
            if (stayOnPage && response.data?.id) {
                // Redirect to edit page
                router.visit(route('easyncc.users.edit', response.data.id));
            } else {
                // Redirect to index
                router.visit(route('easyncc.users.index'));
            }
        }
    } catch (err) {
        console.error('Error submitting form:', err);
        console.error('Error response:', err.response);
        console.error('Error data:', err.response?.data);

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

            // Show error message with details
            error.value = 'Errori di validazione: ' + Object.keys(responseErrors).join(', ');
        } else {
            error.value = 'Errore nel salvataggio dell\'utente: ' + (err.response?.data?.message || err.message);
        }
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    loading.value = true;
    loadCompanies().then(() => {
        loading.value = false;
    });

    // Initialize profile data for driver role in create mode
    if (!isEdit.value && form.value.role === 'driver' && Object.keys(profileData.value).length === 0) {
        profileData.value = {
            birth_date: '',
            fiscal_code: '',
            vat_number: '',
            hourly_rate: null,
            bank_name: '',
            iban: '',
            assigned_vehicle_id: '',
            color: '#3788d8',
            allow_overlapping: false,
            notes: ''
        };
    }
});

// Utility function for formatting datetime
const formatDateTime = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY HH:mm');
};
</script>
