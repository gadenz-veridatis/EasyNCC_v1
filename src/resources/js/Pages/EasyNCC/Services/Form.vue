<template>
    <Head :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="8" class="mx-auto">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">{{ isEdit ? 'Modifica Servizio' : 'Nuovo Servizio' }}</h5>
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
                                <!-- Reference Number -->
                                <BCol md="6" class="mb-3">
                                    <label for="reference_number" class="form-label">Riferimento *</label>
                                    <input
                                        id="reference_number"
                                        v-model="form.reference_number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.reference_number }"
                                        placeholder="Es. SRV001"
                                    />
                                    <small v-if="errors.reference_number" class="text-danger d-block mt-1">
                                        {{ errors.reference_number[0] }}
                                    </small>
                                </BCol>

                                <!-- Status -->
                                <BCol md="6" class="mb-3">
                                    <label for="status" class="form-label">Stato *</label>
                                    <select
                                        id="status"
                                        v-model="form.status_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.status_id }"
                                    >
                                        <option value="">Seleziona uno stato</option>
                                        <option value="1">Preventivo</option>
                                        <option value="2">Confermato</option>
                                        <option value="3">In Corso</option>
                                        <option value="4">Completato</option>
                                        <option value="5">Cancellato</option>
                                        <option value="6">No Show</option>
                                    </select>
                                    <small v-if="errors.status_id" class="text-danger d-block mt-1">
                                        {{ errors.status_id[0] }}
                                    </small>
                                </BCol>

                                <!-- Client ID -->
                                <BCol md="6" class="mb-3">
                                    <label for="client_id" class="form-label">Cliente *</label>
                                    <select
                                        id="client_id"
                                        v-model="form.client_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.client_id }"
                                    >
                                        <option value="">Seleziona un cliente</option>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                    <small v-if="errors.client_id" class="text-danger d-block mt-1">
                                        {{ errors.client_id[0] }}
                                    </small>
                                </BCol>

                                <!-- Vehicle ID -->
                                <BCol md="6" class="mb-3">
                                    <label for="vehicle_id" class="form-label">Veicolo</label>
                                    <select
                                        id="vehicle_id"
                                        v-model="form.vehicle_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': errors.vehicle_id }"
                                    >
                                        <option value="">Seleziona un veicolo</option>
                                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                            {{ vehicle.brand }} {{ vehicle.model }} ({{ vehicle.license_plate }})
                                        </option>
                                    </select>
                                    <small v-if="errors.vehicle_id" class="text-danger d-block mt-1">
                                        {{ errors.vehicle_id[0] }}
                                    </small>
                                </BCol>

                                <!-- Pickup Address -->
                                <BCol md="12" class="mb-3">
                                    <label for="pickup_address" class="form-label">Indirizzo Pickup *</label>
                                    <input
                                        id="pickup_address"
                                        v-model="form.pickup_address"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.pickup_address }"
                                        placeholder="Indirizzo di partenza..."
                                    />
                                    <small v-if="errors.pickup_address" class="text-danger d-block mt-1">
                                        {{ errors.pickup_address[0] }}
                                    </small>
                                </BCol>

                                <!-- Dropoff Address -->
                                <BCol md="12" class="mb-3">
                                    <label for="dropoff_address" class="form-label">Indirizzo Dropoff *</label>
                                    <input
                                        id="dropoff_address"
                                        v-model="form.dropoff_address"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.dropoff_address }"
                                        placeholder="Indirizzo di destinazione..."
                                    />
                                    <small v-if="errors.dropoff_address" class="text-danger d-block mt-1">
                                        {{ errors.dropoff_address[0] }}
                                    </small>
                                </BCol>

                                <!-- Pickup DateTime -->
                                <BCol md="6" class="mb-3">
                                    <label for="pickup_datetime" class="form-label">Data/Ora Pickup *</label>
                                    <input
                                        id="pickup_datetime"
                                        v-model="form.pickup_datetime"
                                        type="datetime-local"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.pickup_datetime }"
                                    />
                                    <small v-if="errors.pickup_datetime" class="text-danger d-block mt-1">
                                        {{ errors.pickup_datetime[0] }}
                                    </small>
                                </BCol>

                                <!-- Dropoff DateTime -->
                                <BCol md="6" class="mb-3">
                                    <label for="dropoff_datetime" class="form-label">Data/Ora Dropoff</label>
                                    <input
                                        id="dropoff_datetime"
                                        v-model="form.dropoff_datetime"
                                        type="datetime-local"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.dropoff_datetime }"
                                    />
                                    <small v-if="errors.dropoff_datetime" class="text-danger d-block mt-1">
                                        {{ errors.dropoff_datetime[0] }}
                                    </small>
                                </BCol>

                                <!-- Number of Passengers -->
                                <BCol md="6" class="mb-3">
                                    <label for="number_of_passengers" class="form-label">Numero Passeggeri *</label>
                                    <input
                                        id="number_of_passengers"
                                        v-model.number="form.number_of_passengers"
                                        type="number"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.number_of_passengers }"
                                        min="1"
                                    />
                                    <small v-if="errors.number_of_passengers" class="text-danger d-block mt-1">
                                        {{ errors.number_of_passengers[0] }}
                                    </small>
                                </BCol>

                                <!-- Price -->
                                <BCol md="6" class="mb-3">
                                    <label for="price" class="form-label">Prezzo</label>
                                    <input
                                        id="price"
                                        v-model.number="form.price"
                                        type="number"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.price }"
                                        step="0.01"
                                        min="0"
                                    />
                                    <small v-if="errors.price" class="text-danger d-block mt-1">
                                        {{ errors.price[0] }}
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
                                        placeholder="Note sul servizio..."
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
                                <Link :href="route('easyncc.services.index')" class="btn btn-secondary ms-2">
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
    service: {
        type: Object,
        default: null
    }
});

const isEdit = ref(!!props.service);
const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const errors = ref({});
const clients = ref([]);
const vehicles = ref([]);

const form = ref({
    reference_number: props.service?.reference_number || '',
    status_id: props.service?.status_id || '',
    client_id: props.service?.client_id || '',
    vehicle_id: props.service?.vehicle_id || '',
    pickup_address: props.service?.pickup_address || '',
    dropoff_address: props.service?.dropoff_address || '',
    pickup_datetime: props.service?.pickup_datetime || '',
    dropoff_datetime: props.service?.dropoff_datetime || '',
    number_of_passengers: props.service?.number_of_passengers || '',
    price: props.service?.price || '',
    notes: props.service?.notes || ''
});

const loadClients = async () => {
    try {
        const response = await axios.get('/api/clients');
        clients.value = response.data;
    } catch (err) {
        console.error('Error loading clients:', err);
    }
};

const loadVehicles = async () => {
    try {
        const response = await axios.get('/api/vehicles?status=attivo');
        vehicles.value = response.data;
    } catch (err) {
        console.error('Error loading vehicles:', err);
    }
};

const submitForm = async () => {
    submitting.value = true;
    error.value = '';
    errors.value = {};

    try {
        const url = isEdit.value ? `/api/services/${props.service.id}` : '/api/services';
        const method = isEdit.value ? 'put' : 'post';

        await axios[method](url, form.value);

        router.visit(route('easyncc.services.index'));
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            error.value = 'Errore nel salvataggio del servizio';
        }
        console.error('Error submitting form:', err);
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    loading.value = true;
    Promise.all([loadClients(), loadVehicles()]).then(() => {
        loading.value = false;
    });
});
</script>
