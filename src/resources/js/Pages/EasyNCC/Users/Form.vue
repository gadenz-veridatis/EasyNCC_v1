<template>
    <Head :title="isEdit ? 'Modifica Utente' : 'Nuovo Utente'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Utente' : 'Nuovo Utente'" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="8" class="mx-auto">
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
                            <BRow>
                                <!-- First Name -->
                                <BCol md="6" class="mb-3">
                                    <label for="first_name" class="form-label">Nome *</label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.first_name }"
                                        placeholder="Nome"
                                    />
                                    <small v-if="errors.first_name" class="text-danger d-block mt-1">
                                        {{ errors.first_name[0] }}
                                    </small>
                                </BCol>

                                <!-- Last Name -->
                                <BCol md="6" class="mb-3">
                                    <label for="last_name" class="form-label">Cognome *</label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.last_name }"
                                        placeholder="Cognome"
                                    />
                                    <small v-if="errors.last_name" class="text-danger d-block mt-1">
                                        {{ errors.last_name[0] }}
                                    </small>
                                </BCol>

                                <!-- Email -->
                                <BCol md="12" class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.email }"
                                        placeholder="email@example.com"
                                    />
                                    <small v-if="errors.email" class="text-danger d-block mt-1">
                                        {{ errors.email[0] }}
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
                                        <option value="admin">Amministratore</option>
                                        <option value="operatore">Operatore</option>
                                        <option value="driver">Driver</option>
                                        <option value="cliente">Cliente</option>
                                    </select>
                                    <small v-if="errors.role" class="text-danger d-block mt-1">
                                        {{ errors.role[0] }}
                                    </small>
                                </BCol>

                                <!-- Company -->
                                <BCol md="6" class="mb-3">
                                    <label for="company_id" class="form-label">Azienda</label>
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

                                <!-- Password (only for new users) -->
                                <BCol md="12" class="mb-3" v-if="!isEdit">
                                    <label for="password" class="form-label">Password *</label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.password }"
                                        placeholder="Inserisci una password"
                                    />
                                    <small v-if="errors.password" class="text-danger d-block mt-1">
                                        {{ errors.password[0] }}
                                    </small>
                                </BCol>

                                <!-- Password Confirmation (only for new users) -->
                                <BCol md="12" class="mb-3" v-if="!isEdit">
                                    <label for="password_confirmation" class="form-label">Conferma Password *</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.password_confirmation }"
                                        placeholder="Conferma la password"
                                    />
                                    <small v-if="errors.password_confirmation" class="text-danger d-block mt-1">
                                        {{ errors.password_confirmation[0] }}
                                    </small>
                                </BCol>

                                <!-- Is Active -->
                                <BCol md="12" class="mb-3">
                                    <div class="form-check form-switch">
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

                                <!-- Notes -->
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
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';

const props = defineProps({
    user: {
        type: Object,
        default: null
    }
});

const isEdit = ref(!!props.user);
const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const errors = ref({});
const companies = ref([]);

const form = ref({
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    email: props.user?.email || '',
    role: props.user?.role || '',
    company_id: props.user?.company_id || '',
    is_active: props.user?.is_active || true,
    phone: props.user?.phone || '',
    notes: props.user?.notes || '',
    password: '',
    password_confirmation: ''
});

const loadCompanies = async () => {
    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data;
    } catch (err) {
        console.error('Error loading companies:', err);
    }
};

const submitForm = async () => {
    submitting.value = true;
    error.value = '';
    errors.value = {};

    try {
        const url = isEdit.value ? `/api/users/${props.user.id}` : '/api/users';
        const method = isEdit.value ? 'put' : 'post';

        // Remove empty password fields for edit mode
        const dataToSubmit = { ...form.value };
        if (isEdit.value && !dataToSubmit.password) {
            delete dataToSubmit.password;
            delete dataToSubmit.password_confirmation;
        }

        await axios[method](url, dataToSubmit);

        router.visit(route('easyncc.users.index'));
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
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
