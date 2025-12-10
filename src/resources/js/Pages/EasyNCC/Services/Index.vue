<template>
    <Head title="Servizi" />

    <Layout :collapsed-sidebar="true">
        <PageHeader title="Servizi" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Servizi</h5>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-soft-primary btn-sm"
                                @click="showFilters = !showFilters"
                            >
                                <i :class="showFilters ? 'bx bx-chevron-up' : 'bx bx-chevron-down'"></i>
                                {{ showFilters ? 'Nascondi Filtri' : 'Mostra Filtri' }}
                                <span v-if="hasActiveFilters" class="badge bg-primary ms-2">{{ activeFiltersCount }}</span>
                            </button>
                            <Link :href="route('easyncc.services.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Servizio
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                        <BRow class="mb-4">
                            <BCol md="2">
                                <label class="form-label">Nominativo Riferimento</label>
                                <input
                                    v-model="filters.reference_name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Nome riferimento..."
                                    @input="debouncedLoadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Pickup Da</label>
                                <input
                                    v-model="filters.pickup_date_from"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Pickup A</label>
                                <input
                                    v-model="filters.pickup_date_to"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Dropoff Da</label>
                                <input
                                    v-model="filters.dropoff_date_from"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Dropoff A</label>
                                <input
                                    v-model="filters.dropoff_date_to"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Tipo Servizio</label>
                                <select v-model="filters.service_type_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i tipi</option>
                                    <option v-for="type in serviceTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                            </BCol>
                        </BRow>

                        <BRow class="mb-4">
                            <BCol md="2">
                                <label class="form-label">Committente</label>
                                <select v-model="filters.client_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i committenti</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }} {{ client.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Intermediario</label>
                                <select v-model="filters.intermediary_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli intermediari</option>
                                    <option v-for="intermediary in intermediaries" :key="intermediary.id" :value="intermediary.id">
                                        {{ intermediary.name }} {{ intermediary.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Autista</label>
                                <select v-model="filters.driver_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli autisti</option>
                                    <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                        {{ driver.name }} {{ driver.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Veicolo</label>
                                <select v-model="filters.vehicle_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i veicoli</option>
                                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                        {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
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
                        </BRow>

                        <!-- Reset Filters Button -->
                        <BRow v-if="hasActiveFilters">
                            <BCol cols="12" class="d-flex justify-content-end">
                                <button
                                    type="button"
                                    class="btn btn-soft-secondary btn-sm"
                                    @click="resetFilters"
                                >
                                    <i class="bx bx-refresh me-1"></i>
                                    Reset Filtri
                                </button>
                            </BCol>
                        </BRow>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="services.length > 0" class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="form-check-input">
                                        </th>
                                        <th scope="col" @click="sortBy('reference_number')" style="cursor: pointer;">
                                            Dati Identificativi
                                            <i v-if="sortField === 'reference_number'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col" v-if="isSuperAdmin" @click="sortBy('company_id')" style="cursor: pointer;">
                                            Azienda
                                            <i v-if="sortField === 'company_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col" @click="sortBy('pickup_datetime')" style="cursor: pointer;">
                                            Data
                                            <i v-if="sortField === 'pickup_datetime'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col" @click="sortBy('client_id')" style="cursor: pointer;">
                                            Committente
                                            <i v-if="sortField === 'client_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col" @click="sortBy('vehicle_id')" style="cursor: pointer;">
                                            Veicolo
                                            <i v-if="sortField === 'vehicle_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">Autista</th>
                                        <th scope="col">Passeggeri</th>
                                        <th scope="col">Esperienze</th>
                                        <th scope="col" @click="sortBy('price')" style="cursor: pointer;">
                                            Economics
                                            <i v-if="sortField === 'price'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">Notifiche</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in sortedServices" :key="service.id" :class="{ 'table-active': selectedServices.includes(service.id) }">
                                        <td>
                                            <input type="checkbox" v-model="selectedServices" :value="service.id" class="form-check-input">
                                        </td>
                                        <!-- Dati Identificativi -->
                                        <td>
                                            <div class="fw-bold">#{{ service.reference_number || service.id }}</div>
                                            <div class="text-muted small" v-if="service.service_type">{{ service.service_type }}</div>
                                            <div class="fw-bold text-primary mt-1" v-if="service.contact_name">{{ service.contact_name }}</div>
                                        </td>
                                        <!-- Azienda (solo per super-admin) -->
                                        <td v-if="isSuperAdmin">
                                            <div class="small">{{ service.company?.name || '-' }}</div>
                                        </td>
                                        <!-- Data -->
                                        <td>
                                            <div class="fw-bold">{{ formatDate(service.pickup_datetime) }}</div>
                                            <div class="small text-primary">{{ service.pickup_address }}</div>
                                            <div class="small text-muted mt-1">{{ formatDate(service.dropoff_datetime) }}</div>
                                            <div class="small text-muted">{{ service.dropoff_address }}</div>
                                        </td>
                                        <!-- Committente -->
                                        <td>
                                            <div class="fw-bold" v-if="service.client">{{ service.client.name }} {{ service.client.surname }}</div>
                                            <div class="small text-muted" v-if="service.intermediary">Int: {{ service.intermediary.name }} {{ service.intermediary.surname }}</div>
                                        </td>
                                        <!-- Veicolo -->
                                        <td>
                                            <div v-if="service.vehicle">
                                                <div class="targa-auto mb-1">
                                                    <span class="codice-targa">{{ service.vehicle.license_plate }}</span>
                                                </div>
                                                <div class="small mt-2">{{ service.vehicle.brand }} {{ service.vehicle.model }}</div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Autista -->
                                        <td>
                                            <div v-if="service.drivers && service.drivers.length > 0">
                                                <div v-for="driver in service.drivers" :key="driver.id" class="mb-1">
                                                    <span class="badge" :style="`background-color: ${driver.driver_profile?.color || '#6c757d'}`">
                                                        {{ driver.name }} {{ driver.surname }}
                                                    </span>
                                                </div>
                                                <div class="small text-muted" v-if="service.dress_code">Dress: {{ service.dress_code.name }}</div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Passeggeri -->
                                        <td>
                                            <div class="fw-bold">{{ service.passenger_count || 0 }} pax</div>
                                            <div class="small" v-if="service.big_luggage || service.medium_luggage || service.small_luggage">
                                                Bagagli: {{ service.big_luggage || 0 }}L / {{ service.medium_luggage || 0 }}M / {{ service.small_luggage || 0 }}S
                                            </div>
                                        </td>
                                        <!-- Esperienze -->
                                        <td>
                                            <div v-if="service.activities && service.activities.length > 0">
                                                <div v-for="activity in service.activities" :key="activity.id" class="small mb-1">
                                                    <span class="fw-medium">{{ activity.activity_type?.name || '-' }}</span>
                                                    <br>
                                                    <span class="text-muted">{{ activity.supplier?.name || '-' }}</span>
                                                </div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Economics -->
                                        <td>
                                            <div class="small">
                                                <div class="fw-bold">Tot: €{{ formatCurrency(service.service_price) }}</div>
                                                <div>Acc: €{{ formatCurrency(service.deposit_amount) }}</div>
                                                <div>Imp: €{{ formatCurrency(service.balance_taxable) }}</div>
                                                <div>Han: €{{ formatCurrency(service.balance_handling_fees) }}</div>
                                                <div>Card: €{{ formatCurrency(service.balance_card_fees) }}</div>
                                            </div>
                                        </td>
                                        <!-- Notifiche -->
                                        <td>
                                            <div class="small">
                                                <div v-if="service.accounting_transactions_count">
                                                    <i class="ri-file-list-line text-success"></i> {{ service.accounting_transactions_count }}
                                                </div>
                                                <div v-if="service.tasks_count">
                                                    <i class="ri-task-line" :class="service.incomplete_tasks_count ? 'text-warning' : 'text-success'"></i>
                                                    {{ service.incomplete_tasks_count }}/{{ service.tasks_count }}
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Azioni -->
                                        <td>
                                            <Link :href="route('easyncc.services.show', service.id)" class="btn btn-sm btn-soft-primary me-1" title="Visualizza">
                                                <i class="ri-eye-line"></i>
                                            </Link>
                                            <Link :href="route('easyncc.services.edit', service.id)" class="btn btn-sm btn-soft-info me-1" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </Link>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteService(service.id)" title="Elimina">
                                                <i class="ri-delete-bin-line"></i>
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

                        <!-- Selected Actions -->
                        <div v-if="selectedServices.length > 0" class="mt-3">
                            <span class="badge bg-primary me-2">{{ selectedServices.length }} servizi selezionati</span>
                            <button class="btn btn-sm btn-danger" @click="deleteSelected">
                                <i class="ri-delete-bin-line me-1"></i>Elimina Selezionati
                            </button>
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
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const services = ref([]);
const loading = ref(false);
const error = ref('');
const selectedServices = ref([]);
const selectAll = ref(false);

// Dictionaries
const serviceTypes = ref([]);
const clients = ref([]);
const intermediaries = ref([]);
const drivers = ref([]);
const vehicles = ref([]);
const currentUser = ref(null);

// Filters
const filters = ref({
    reference_name: '',
    pickup_date_from: '',
    pickup_date_to: '',
    dropoff_date_from: '',
    dropoff_date_to: '',
    service_type_id: '',
    client_id: '',
    intermediary_id: '',
    driver_id: '',
    vehicle_id: '',
    status: ''
});

// Show/Hide filters
const showFilters = ref(true);

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(value => value !== '').length;
});

// Sorting
const sortField = ref('pickup_datetime');
const sortDirection = ref('asc');

const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');

const sortedServices = computed(() => {
    const sorted = [...services.value];
    sorted.sort((a, b) => {
        let aVal = a[sortField.value];
        let bVal = b[sortField.value];

        // Handle nested objects
        if (sortField.value === 'client_id') {
            aVal = a.client?.name || '';
            bVal = b.client?.name || '';
        } else if (sortField.value === 'vehicle_id') {
            aVal = a.vehicle?.license_plate || '';
            bVal = b.vehicle?.license_plate || '';
        } else if (sortField.value === 'company_id') {
            aVal = a.company?.name || '';
            bVal = b.company?.name || '';
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
    return sorted;
});

const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
    } catch (err) {
        console.error('Error loading current user:', err);
    }
};

const loadDictionaries = async () => {
    try {
        console.log('Loading dictionaries...');
        const [typesRes, clientsRes, intermediariesRes, driversRes, vehiclesRes] = await Promise.all([
            axios.get('/api/dictionaries/service-types'),
            axios.get('/api/users', { params: { role: 'collaboratore', is_client: true, per_page: 1000 } }),
            axios.get('/api/users', { params: { is_intermediary: true, per_page: 1000 } }),
            axios.get('/api/users', { params: { role: 'driver', per_page: 1000 } }),
            axios.get('/api/vehicles', { params: { per_page: 1000 } })
        ]);

        console.log('Dictionaries loaded:', {
            types: typesRes.data,
            clients: clientsRes.data,
            intermediaries: intermediariesRes.data,
            drivers: driversRes.data,
            vehicles: vehiclesRes.data
        });

        serviceTypes.value = typesRes.data.data || [];
        clients.value = clientsRes.data.data || [];
        intermediaries.value = intermediariesRes.data.data || [];
        drivers.value = driversRes.data.data || [];
        vehicles.value = vehiclesRes.data.data || [];
    } catch (err) {
        console.error('Error loading dictionaries:', err);
        console.error('Error details:', err.response?.data);
    }
};

let debounceTimer = null;
const debouncedLoadServices = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadServices();
    }, 500);
};

const loadServices = async () => {
    loading.value = true;
    error.value = '';

    try {
        console.log('Loading services with filters:', filters.value);
        const response = await axios.get('/api/services', {
            params: {
                ...filters.value,
                with_counts: true,
                per_page: 1000
            }
        });
        console.log('Services API response:', response.data);
        services.value = response.data.data || [];
        console.log('Loaded services:', services.value.length);
    } catch (err) {
        error.value = 'Errore nel caricamento dei servizi';
        console.error('Error loading services:', err);
        console.error('Error response:', err.response?.data);
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

const deleteSelected = async () => {
    if (!confirm(`Sei sicuro di voler eliminare ${selectedServices.value.length} servizi?`)) {
        return;
    }

    try {
        await Promise.all(selectedServices.value.map(id => axios.delete(`/api/services/${id}`)));
        selectedServices.value = [];
        selectAll.value = false;
        await loadServices();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione dei servizi';
        console.error('Error deleting services:', err);
    }
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedServices.value = services.value.map(s => s.id);
    } else {
        selectedServices.value = [];
    }
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const resetFilters = () => {
    filters.value = {
        reference_name: '',
        pickup_date_from: '',
        pickup_date_to: '',
        dropoff_date_from: '',
        dropoff_date_to: '',
        service_type_id: '',
        client_id: '',
        intermediary_id: '',
        driver_id: '',
        vehicle_id: '',
        status: ''
    };
    loadServices();
};

const formatDate = (datetime) => {
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const formatCurrency = (value) => {
    return value ? parseFloat(value).toFixed(2) : '0.00';
};

onMounted(async () => {
    await loadCurrentUser();
    await loadDictionaries();
    await loadServices();
});
</script>

<style scoped>
.targa-auto {
    /* Stile targa italiana con bande blu laterali */
    background: linear-gradient(to right, #003399 0%, #003399 8%, #ffffff 8%, #ffffff 92%, #003399 92%, #003399 100%);
    border: 1px solid #000;
    border-radius: 3px;
    padding: 3px 8px;
    display: inline-block;
    font-family: 'Arial', sans-serif;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    min-width: 90px;
}

.codice-targa {
    /* Stile per il testo della targa */
    font-size: 14px;
    font-weight: bold;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table-responsive {
    font-size: 0.875rem;
}

.table td {
    vertical-align: top;
    padding: 0.75rem 0.5rem;
}

.table th {
    white-space: nowrap;
}
</style>
