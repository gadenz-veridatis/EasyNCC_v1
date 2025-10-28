<template>
    <Head title="Servizi" />

    <Layout>
        <PageHeader title="Servizi" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Servizi</h5>
                        <Link :href="route('easyncc.services.create')" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus me-1"></i>
                            Nuovo Servizio
                        </Link>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Filters -->
                        <BRow class="mb-4">
                            <BCol md="3">
                                <label class="form-label">Ricerca</label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Riferimento, cliente..."
                                    @input="loadServices"
                                />
                            </BCol>
                            <BCol md="3">
                                <label class="form-label">Stato</label>
                                <select v-model="filters.status" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli stati</option>
                                    <option value="preventivo">Preventivo</option>
                                    <option value="confermato">Confermato</option>
                                    <option value="in corso">In Corso</option>
                                    <option value="completato">Completato</option>
                                    <option value="cancellato">Cancellato</option>
                                    <option value="no-show">No Show</option>
                                </select>
                            </BCol>
                            <BCol md="3">
                                <label class="form-label">Da</label>
                                <input
                                    v-model="filters.date_from"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @input="loadServices"
                                />
                            </BCol>
                            <BCol md="3">
                                <label class="form-label">A</label>
                                <input
                                    v-model="filters.date_to"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @input="loadServices"
                                />
                            </BCol>
                        </BRow>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="services.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Riferimento</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Pickup</th>
                                        <th scope="col">Dropoff</th>
                                        <th scope="col">Data/Ora</th>
                                        <th scope="col">Veicolo</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in services" :key="service.id">
                                        <td class="fw-medium">{{ service.reference_number || '#' + service.id }}</td>
                                        <td>{{ service.client?.name || '-' }}</td>
                                        <td class="text-truncate" style="max-width: 150px;" :title="service.pickup_address">
                                            {{ service.pickup_address }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 150px;" :title="service.dropoff_address">
                                            {{ service.dropoff_address }}
                                        </td>
                                        <td>{{ formatDateTime(service.pickup_datetime) }}</td>
                                        <td>
                                            <span v-if="service.vehicle">
                                                {{ service.vehicle.brand }} {{ service.vehicle.model }}
                                                <br>
                                                <small class="text-muted">{{ service.vehicle.license_plate }}</small>
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span :class="`badge bg-${getStatusColor(service.status?.name)}`">
                                                {{ service.status?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                                                                        <Link :href="route('easyncc.services.show', service.id)" class="btn btn-sm btn-soft-primary me-2">
                                                Visualizza
                                            </Link>
                                            <Link :href="route('easyncc.services.edit', service.id)" class="btn btn-sm btn-soft-info me-2">
                                                Modifica
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteService(service.id)"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No Data -->
                        <div v-else class="text-center text-muted py-5">
                            <p>Nessun servizio trovato</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const services = ref([]);
const loading = ref(false);
const error = ref('');
const filters = ref({
    search: '',
    status: '',
    date_from: '',
    date_to: ''
});

const loadServices = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get('/api/services', {
            params: filters.value
        });
        services.value = response.data;
    } catch (err) {
        error.value = 'Errore nel caricamento dei servizi';
        console.error('Error loading services:', err);
    } finally {
        loading.value = false;
    }
};

const deleteService = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo servizio?')) {
        return;
    }

    try {
        await axios.delete(`/api/services/${id}`);
        await loadServices();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione del servizio';
        console.error('Error deleting service:', err);
    }
};

const formatDateTime = (datetime) => {
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const getStatusColor = (status) => {
    const colors = {
        'preventivo': 'warning',
        'confermato': 'info',
        'in corso': 'primary',
        'completato': 'success',
        'cancellato': 'danger',
        'no-show': 'secondary'
    };
    return colors[status] || 'secondary';
};

onMounted(() => {
    loadServices();
});
</script>
