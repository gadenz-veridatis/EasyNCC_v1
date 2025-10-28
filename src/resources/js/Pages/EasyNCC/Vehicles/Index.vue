<template>
    <Head title="Veicoli" />

    <Layout>
        <PageHeader title="Veicoli" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Veicoli</h5>
                        <Link :href="route('easyncc.vehicles.create')" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus me-1"></i>
                            Nuovo Veicolo
                        </Link>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Filters -->
                        <BRow class="mb-4">
                            <BCol md="6">
                                <label class="form-label">Ricerca</label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Targa, marca, modello..."
                                    @input="loadVehicles"
                                />
                            </BCol>
                            <BCol md="6">
                                <label class="form-label">Stato</label>
                                <select v-model="filters.status" class="form-select form-select-sm" @change="loadVehicles">
                                    <option value="">Tutti gli stati</option>
                                    <option value="attivo">Attivo</option>
                                    <option value="manutenzione">Manutenzione</option>
                                    <option value="inattivo">Inattivo</option>
                                </select>
                            </BCol>
                        </BRow>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="vehicles.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Targa</th>
                                        <th scope="col">Marca</th>
                                        <th scope="col">Modello</th>
                                        <th scope="col">Passeggeri</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="vehicle in vehicles" :key="vehicle.id">
                                        <td class="fw-medium">{{ vehicle.license_plate }}</td>
                                        <td>{{ vehicle.brand }}</td>
                                        <td>{{ vehicle.model }}</td>
                                        <td>{{ vehicle.passenger_capacity }}</td>
                                        <td>
                                            <span :class="`badge bg-${getStatusBadge(vehicle.status)}`">
                                                {{ vehicle.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.vehicles.edit', vehicle.id)" class="btn btn-sm btn-soft-primary me-2">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteVehicle(vehicle.id)"
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
                            <p>Nessun veicolo trovato</p>
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

const vehicles = ref([]);
const loading = ref(false);
const error = ref('');
const filters = ref({
    search: '',
    status: ''
});

const loadVehicles = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get('/api/vehicles', {
            params: filters.value
        });
        vehicles.value = response.data;
    } catch (err) {
        error.value = 'Errore nel caricamento dei veicoli';
        console.error('Error loading vehicles:', err);
    } finally {
        loading.value = false;
    }
};

const deleteVehicle = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo veicolo?')) {
        return;
    }

    try {
        await axios.delete(`/api/vehicles/${id}`);
        await loadVehicles();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione del veicolo';
        console.error('Error deleting vehicle:', err);
    }
};

const getStatusBadge = (status) => {
    const badges = {
        'attivo': 'success',
        'manutenzione': 'warning',
        'inattivo': 'danger'
    };
    return badges[status] || 'secondary';
};

onMounted(() => {
    loadVehicles();
});
</script>
