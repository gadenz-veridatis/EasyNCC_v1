<template>
    <Layout>
        <PageHeader :title="pageTitle" :items="items" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ isEdit ? 'Modifica' : 'Nuova' }} Azienda</h5>
                    </BCardHeader>
                    <BCardBody>
                        <form @submit.prevent="submitForm">
                            <!-- Nome Azienda -->
                            <BRow>
                                <BCol md="6" class="mb-3">
                                    <label for="name" class="form-label">Nome Azienda <span class="text-danger">*</span></label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.name }"
                                        placeholder="Es: NCC Roma Service"
                                        required
                                    />
                                    <small v-if="errors.name" class="text-danger d-block mt-1">
                                        {{ errors.name[0] }}
                                    </small>
                                </BCol>

                                <!-- Email -->
                                <BCol md="6" class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.email }"
                                        placeholder="info@azienda.it"
                                        required
                                    />
                                    <small v-if="errors.email" class="text-danger d-block mt-1">
                                        {{ errors.email[0] }}
                                    </small>
                                </BCol>

                                <!-- Telefono -->
                                <BCol md="6" class="mb-3">
                                    <label for="phone" class="form-label">Telefono</label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.phone }"
                                        placeholder="+39 06 12345678"
                                    />
                                    <small v-if="errors.phone" class="text-danger d-block mt-1">
                                        {{ errors.phone[0] }}
                                    </small>
                                </BCol>

                                <!-- Partita IVA -->
                                <BCol md="6" class="mb-3">
                                    <label for="vat_number" class="form-label">Partita IVA</label>
                                    <input
                                        id="vat_number"
                                        v-model="form.vat_number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.vat_number }"
                                        placeholder="12345678901"
                                        maxlength="11"
                                    />
                                    <small v-if="errors.vat_number" class="text-danger d-block mt-1">
                                        {{ errors.vat_number[0] }}
                                    </small>
                                </BCol>

                                <!-- SDI -->
                                <BCol md="6" class="mb-3">
                                    <label for="sdi" class="form-label">Codice SDI</label>
                                    <input
                                        id="sdi"
                                        v-model="form.sdi"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.sdi }"
                                        placeholder="ABCDEFG"
                                        maxlength="7"
                                    />
                                    <small v-if="errors.sdi" class="text-danger d-block mt-1">
                                        {{ errors.sdi[0] }}
                                    </small>
                                </BCol>

                                <!-- PEC -->
                                <BCol md="6" class="mb-3">
                                    <label for="pec" class="form-label">PEC</label>
                                    <input
                                        id="pec"
                                        v-model="form.pec"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.pec }"
                                        placeholder="azienda@pec.it"
                                    />
                                    <small v-if="errors.pec" class="text-danger d-block mt-1">
                                        {{ errors.pec[0] }}
                                    </small>
                                </BCol>

                                <!-- Indirizzo -->
                                <BCol md="12" class="mb-3">
                                    <label for="address" class="form-label">Indirizzo</label>
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.address }"
                                        rows="2"
                                        placeholder="Via, numero civico, città, CAP"
                                    ></textarea>
                                    <small v-if="errors.address" class="text-danger d-block mt-1">
                                        {{ errors.address[0] }}
                                    </small>
                                </BCol>

                                <!-- Sito Web -->
                                <BCol md="6" class="mb-3">
                                    <label for="website" class="form-label">Sito Web</label>
                                    <input
                                        id="website"
                                        v-model="form.website"
                                        type="url"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.website }"
                                        placeholder="https://www.azienda.it"
                                    />
                                    <small v-if="errors.website" class="text-danger d-block mt-1">
                                        {{ errors.website[0] }}
                                    </small>
                                </BCol>

                                <!-- Stato Attivo -->
                                <BCol md="6" class="mb-3">
                                    <label for="is_active" class="form-label">Stato</label>
                                    <div class="form-check form-switch mt-2">
                                        <input
                                            id="is_active"
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="form-check-input"
                                            role="switch"
                                        />
                                        <label for="is_active" class="form-check-label">
                                            {{ form.is_active ? 'Attiva' : 'Inattiva' }}
                                        </label>
                                    </div>
                                    <small v-if="errors.is_active" class="text-danger d-block mt-1">
                                        {{ errors.is_active[0] }}
                                    </small>
                                </BCol>
                            </BRow>

                            <!-- Buttons -->
                            <div class="mt-4 d-flex gap-2">
                                <BButton type="submit" variant="primary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ isEdit ? 'Aggiorna' : 'Crea' }} Azienda
                                </BButton>
                                <BButton variant="secondary" @click="$inertia.visit('/easyncc/companies')">
                                    Annulla
                                </BButton>
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';

const props = defineProps({
    company: {
        type: Object,
        default: null
    }
});

const page = usePage();
const isEdit = computed(() => !!props.company);

const pageTitle = computed(() => isEdit.value ? 'Modifica Azienda' : 'Nuova Azienda');
const items = ref([
    { text: 'EasyNCC', href: '/' },
    { text: 'Aziende', href: '/easyncc/companies' },
    { text: isEdit.value ? 'Modifica' : 'Nuova', active: true }
]);

const form = ref({
    name: isEdit.value ? (props.company?.name || '') : '',
    email: isEdit.value ? (props.company?.email || '') : '',
    phone: isEdit.value ? (props.company?.phone || '') : '',
    vat_number: isEdit.value ? (props.company?.vat_number || '') : '',
    sdi: isEdit.value ? (props.company?.sdi || '') : '',
    pec: isEdit.value ? (props.company?.pec || '') : '',
    address: isEdit.value ? (props.company?.address || '') : '',
    website: isEdit.value ? (props.company?.website || '') : '',
    is_active: isEdit.value ? (props.company?.is_active !== undefined ? props.company.is_active : true) : true
});

const errors = ref({});
const loading = ref(false);

const submitForm = async () => {
    loading.value = true;
    errors.value = {};

    try {
        const url = isEdit.value
            ? `/api/companies/${props.company.id}`
            : '/api/companies';
        const method = isEdit.value ? 'put' : 'post';

        const response = await axios({
            method,
            url,
            data: form.value
        });

        // Success - redirect to companies list
        window.location.href = '/easyncc/companies';
    } catch (error) {
        loading.value = false;

        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            console.error('Error saving company:', error);
            alert('Si è verificato un errore durante il salvataggio');
        }
    }
};
</script>
