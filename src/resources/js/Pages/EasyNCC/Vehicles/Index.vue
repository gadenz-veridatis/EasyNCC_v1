<template>
    <Head title="Veicoli" />

    <Layout>
        <PageHeader title="Veicoli" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Veicoli</h5>
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
                            <Link :href="route('easyncc.vehicles.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Veicolo
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="mb-3">
                                <BCol md="6" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadVehicles">
                                        <option value="">Tutte le aziende</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 6 : 12">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Targa, marca, modello..."
                                        @input="loadVehicles"
                                    />
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
                                        Resetta Filtri
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
                        <div v-else-if="vehicles.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" @click="sort('license_plate')" style="cursor: pointer;">
                                            Targa
                                            <i v-if="sortBy === 'license_plate'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" @click="sort('brand')" style="cursor: pointer;">
                                            Marca
                                            <i v-if="sortBy === 'brand'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" @click="sort('model')" style="cursor: pointer;">
                                            Modello
                                            <i v-if="sortBy === 'model'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" @click="sort('passenger_capacity')" style="cursor: pointer;">
                                            Passeggeri
                                            <i v-if="sortBy === 'passenger_capacity'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                        <th scope="col">Allegati</th>
                                        <th scope="col">Prossima Inattività</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="vehicle in sortedVehicles" :key="vehicle.id">
                                        <td class="fw-medium">{{ vehicle.license_plate }}</td>
                                        <td>{{ vehicle.brand }}</td>
                                        <td>{{ vehicle.model }}</td>
                                        <td>{{ vehicle.passenger_capacity }}</td>
                                        <td v-if="isSuperAdmin">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ vehicle.company?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="getNextExpiringAttachment(vehicle)" :class="getExpirationBadgeClass(getNextExpiringAttachment(vehicle).expiration_date)">
                                                {{ formatDate(getNextExpiringAttachment(vehicle).expiration_date) }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="getNextUnavailability(vehicle)" class="badge bg-warning-subtle text-warning">
                                                {{ formatDate(getNextUnavailability(vehicle).start_date) }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.vehicles.show', vehicle.id)" class="btn btn-sm btn-soft-info me-2">
                                                <i class="bx bx-show"></i>
                                            </Link>
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

                            <!-- Pagination Controls -->
                            <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="perPageSelect" class="form-label mb-0 text-nowrap">
                                        Record per pagina:
                                    </label>
                                    <select
                                        id="perPageSelect"
                                        v-model="perPage"
                                        class="form-select form-select-sm"
                                        style="width: auto;"
                                        @change="changePerPage"
                                    >
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                    </select>
                                    <span class="text-muted small">
                                        Totale: {{ totalRecords }} record
                                    </span>
                                </div>

                                <nav aria-label="Navigazione pagine">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                            <a class="page-link" href="#" @click.prevent="changePage(1)">
                                                <i class="bx bx-chevrons-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                                <i class="bx bx-chevron-left"></i>
                                            </a>
                                        </li>

                                        <template v-if="totalPages <= 7">
                                            <li
                                                v-for="page in totalPages"
                                                :key="page"
                                                class="page-item"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                    {{ page }}
                                                </a>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li
                                                v-if="currentPage > 3"
                                                class="page-item"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
                                            </li>
                                            <li v-if="currentPage > 4" class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>

                                            <li
                                                v-for="page in [currentPage - 1, currentPage, currentPage + 1]"
                                                :key="page"
                                                v-show="page > 0 && page <= totalPages"
                                                class="page-item"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                    {{ page }}
                                                </a>
                                            </li>

                                            <li v-if="currentPage < totalPages - 3" class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li
                                                v-if="currentPage < totalPages - 2"
                                                class="page-item"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                                    {{ totalPages }}
                                                </a>
                                            </li>
                                        </template>

                                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                                <i class="bx bx-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                            <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                                <i class="bx bx-chevrons-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
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
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const vehicles = ref([]);
const loading = ref(false);
const error = ref('');
const companies = ref([]);
const currentUser = ref(null);
const showFilters = ref(true);
const sortBy = ref('license_plate');
const sortOrder = ref('asc');
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);

const filters = ref({
    company_id: '',
    search: ''
});

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id) count++;
    if (filters.value.search) count++;
    return count;
});

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: ''
    };
    currentPage.value = 1;
    loadVehicles();
};

const sort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
};

const sortedVehicles = computed(() => {
    if (!vehicles.value.length) return [];

    const sorted = [...vehicles.value].sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];

        // Handle null/undefined values
        if (aVal === null || aVal === undefined) return 1;
        if (bVal === null || bVal === undefined) return -1;

        // String comparison
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (sortOrder.value === 'asc') {
            return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
    });

    return sorted;
});

const loadVehicles = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            ...filters.value,
            page: currentPage.value,
            per_page: perPage.value
        };

        const response = await axios.get('/api/vehicles', { params });
        vehicles.value = response.data.data || [];

        // Update pagination metadata
        if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento dei veicoli';
        console.error('Error loading vehicles:', err);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadVehicles();
};

const changePerPage = () => {
    currentPage.value = 1; // Reset to first page when changing per page
    loadVehicles();
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

const getNextExpiringAttachment = (vehicle) => {
    if (!vehicle.vehicle_attachments || vehicle.vehicle_attachments.length === 0) {
        return null;
    }

    const today = moment();
    const futureAttachments = vehicle.vehicle_attachments
        .filter(att => att.expiration_date && moment(att.expiration_date).isAfter(today))
        .sort((a, b) => moment(a.expiration_date).diff(moment(b.expiration_date)));

    return futureAttachments.length > 0 ? futureAttachments[0] :
           vehicle.vehicle_attachments.sort((a, b) => {
               if (!a.expiration_date) return 1;
               if (!b.expiration_date) return -1;
               return moment(a.expiration_date).diff(moment(b.expiration_date));
           })[0];
};

const getNextUnavailability = (vehicle) => {
    if (!vehicle.unavailabilities || vehicle.unavailabilities.length === 0) {
        return null;
    }

    const today = moment();
    const futureUnavailabilities = vehicle.unavailabilities
        .filter(unav => moment(unav.start_date).isAfter(today) || moment(unav.end_date).isAfter(today))
        .sort((a, b) => moment(a.start_date).diff(moment(b.start_date)));

    return futureUnavailabilities.length > 0 ? futureUnavailabilities[0] : null;
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const getExpirationBadgeClass = (date) => {
    if (!date) return 'text-muted';

    const exp = moment(date);
    const now = moment();
    const daysUntilExpiration = exp.diff(now, 'days');

    if (daysUntilExpiration < 0) {
        return 'badge bg-danger';
    } else if (daysUntilExpiration < 30) {
        return 'badge bg-warning';
    }
    return 'badge bg-success';
};

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
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

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadVehicles();
});
</script>
