<template>
    <Head :title="isEdit ? 'Modifica Veicolo' : 'Nuovo Veicolo'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Veicolo' : 'Nuovo Veicolo'" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="8" class="mx-auto">
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
                                    <label for="license_city" class="form-label">Città Licenza NCC</label>
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

                                <!-- Status -->
                                <BCol md="6" class="mb-3">
                                    <label for="status" class="form-label">Stato *</label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.status }"
                                    >
                                        <option value="">Seleziona uno stato</option>
                                        <option value="attivo">Attivo</option>
                                        <option value="manutenzione">Manutenzione</option>
                                        <option value="inattivo">Inattivo</option>
                                    </select>
                                    <small v-if="errors.status" class="text-danger d-block mt-1">
                                        {{ errors.status[0] }}
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
                                <Link :href="route('easyncc.vehicles.index')" class="btn btn-secondary ms-2">
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
    vehicle: {
        type: Object,
        default: null
    }
});

const isEdit = ref(!!props.vehicle);
const loading = ref(!!props.vehicle ? false : false);
const submitting = ref(false);
const error = ref('');
const errors = ref({});

const form = ref({
    license_plate: props.vehicle?.license_plate || '',
    brand: props.vehicle?.brand || '',
    model: props.vehicle?.model || '',
    passenger_capacity: props.vehicle?.passenger_capacity || '',
    purchase_date: props.vehicle?.purchase_date || '',
    ncc_license_number: props.vehicle?.ncc_license_number || '',
    license_city: props.vehicle?.license_city || '',
    allow_overlapping: props.vehicle?.allow_overlapping || false,
    status: props.vehicle?.status || '',
    notes: props.vehicle?.notes || ''
});

const submitForm = async () => {
    submitting.value = true;
    error.value = '';
    errors.value = {};

    try {
        const url = isEdit.value ? `/api/vehicles/${props.vehicle.id}` : '/api/vehicles';
        const method = isEdit.value ? 'put' : 'post';

        await axios[method](url, form.value);

        router.visit(route('easyncc.vehicles.index'));
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

onMounted(() => {
    loading.value = false;
});
</script>
