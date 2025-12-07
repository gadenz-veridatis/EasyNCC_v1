<template>
    <Head title="Esperienze" />

    <Layout>
        <PageHeader title="Esperienze" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Esperienze</h5>
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
                            <Link :href="route('easyncc.activities.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuova Esperienza
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="mb-3">
                                <BCol md="3" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutte le aziende</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 3 : 4">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Descrizione esperienza..."
                                        @input="loadActivities"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 3">
                                    <label class="form-label">Tipologia</label>
                                    <select v-model="filters.activity_type_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutte</option>
                                        <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 3">
                                    <label class="form-label">Fornitore</label>
                                    <select v-model="filters.supplier_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutti</option>
                                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                            {{ supplier.username }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 2">
                                    <label class="form-label">Pagamento</label>
                                    <select v-model="filters.payment_type" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutti</option>
                                        <option value="INCLUSO">Incluso</option>
                                        <option value="CLIENTE">Cliente</option>
                                        <option value="AGENZIA">Agenzia</option>
                                        <option value="NESSUNO">Nessuno</option>
                                    </select>
                                </BCol>
                            </BRow>
                            <BRow class="mb-3">
                                <BCol :md="isSuperAdmin ? 6 : 6">
                                    <label class="form-label">Servizio</label>
                                    <select v-model="filters.service_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutti</option>
                                        <option v-for="service in services" :key="service.id" :value="service.id">
                                            {{ service.reference_number }} - {{ service.client ? `${service.client.name} ${service.client.surname}` : 'N/A' }}
                                        </option>
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
                        <div v-else-if="activities.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" @click="sort('name')" style="cursor: pointer;">
                                            Descrizione
                                            <i v-if="sortBy === 'name'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Servizio</th>
                                        <th scope="col">Tipologia</th>
                                        <th scope="col">Fornitore</th>
                                        <th scope="col" @click="sort('start_time')" style="cursor: pointer;">
                                            Inizio
                                            <i v-if="sortBy === 'start_time'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Fine</th>
                                        <th scope="col" @click="sort('cost')" style="cursor: pointer;">
                                            Costo
                                            <i v-if="sortBy === 'cost'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Costo/Persona</th>
                                        <th scope="col">Pagamento</th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="activity in sortedActivities" :key="activity.id">
                                        <td class="fw-medium">{{ activity.name }}</td>
                                        <td>
                                            <span v-if="activity.service" class="text-muted small">
                                                {{ activity.service.reference_number }}<br>
                                                <span v-if="activity.service.client">
                                                    {{ activity.service.client.name }} {{ activity.service.client.surname }}
                                                </span>
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="activity.activity_type" class="badge bg-info-subtle text-info">
                                                {{ activity.activity_type.abbreviation }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>{{ activity.supplier?.username || '-' }}</td>
                                        <td>{{ formatDateTime(activity.start_time) }}</td>
                                        <td>{{ formatDateTime(activity.end_time) }}</td>
                                        <td>€ {{ parseFloat(activity.cost || 0).toFixed(2) }}</td>
                                        <td>€ {{ parseFloat(activity.cost_per_person || 0).toFixed(2) }}</td>
                                        <td>
                                            <span v-if="activity.payment_type" :class="getPaymentBadgeClass(activity.payment_type)">
                                                {{ activity.payment_type }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td v-if="isSuperAdmin">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ activity.company?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.activities.edit', activity.id)" class="btn btn-sm btn-soft-primary me-2">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteActivity(activity.id)"
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
                            <p>Nessuna attività trovata</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="activities.length > 0" class="d-flex justify-content-between align-items-center mt-3 px-3">
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
                                    <!-- First Page -->
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="changePage(1)">
                                            <i class="bx bx-chevrons-left"></i>
                                        </a>
                                    </li>
                                    <!-- Previous Page -->
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                            <i class="bx bx-chevron-left"></i>
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    <template v-if="totalPages <= 7">
                                        <li
                                            v-for="page in totalPages"
                                            :key="page"
                                            class="page-item"
                                            :class="{ active: currentPage === page }"
                                        >
                                            <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                {{ page }}
                                            </a>
                                        </li>
                                    </template>
                                    <template v-else>
                                        <li
                                            v-if="currentPage <= 4"
                                            v-for="page in 5"
                                            :key="page"
                                            class="page-item"
                                            :class="{ active: currentPage === page }"
                                        >
                                            <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                {{ page }}
                                            </a>
                                        </li>
                                        <template v-else-if="currentPage >= totalPages - 3">
                                            <li class="page-item">
                                                <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li
                                                v-for="page in 5"
                                                :key="page"
                                                class="page-item"
                                                :class="{ active: currentPage === totalPages - 5 + page }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(totalPages - 5 + page)">
                                                    {{ totalPages - 5 + page }}
                                                </a>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li class="page-item">
                                                <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li
                                                v-for="page in [currentPage - 1, currentPage, currentPage + 1]"
                                                :key="page"
                                                class="page-item"
                                                :class="{ active: currentPage === page }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                    {{ page }}
                                                </a>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                                    {{ totalPages }}
                                                </a>
                                            </li>
                                        </template>
                                    </template>

                                    <!-- Next Page -->
                                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                        <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                            <i class="bx bx-chevron-right"></i>
                                        </a>
                                    </li>
                                    <!-- Last Page -->
                                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                        <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                            <i class="bx bx-chevrons-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
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

const activities = ref([]);
const loading = ref(false);
const error = ref('');
const companies = ref([]);
const activityTypes = ref([]);
const suppliers = ref([]);
const services = ref([]);
const currentUser = ref(null);
const showFilters = ref(true);
const sortBy = ref('start_time');
const sortOrder = ref('desc');

const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);

const filters = ref({
    company_id: '',
    search: '',
    activity_type_id: '',
    supplier_id: '',
    payment_type: '',
    service_id: ''
});

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '' ||
           filters.value.activity_type_id !== '' ||
           filters.value.supplier_id !== '' ||
           filters.value.payment_type !== '' ||
           filters.value.service_id !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id) count++;
    if (filters.value.search) count++;
    if (filters.value.activity_type_id) count++;
    if (filters.value.supplier_id) count++;
    if (filters.value.payment_type) count++;
    if (filters.value.service_id) count++;
    return count;
});

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        activity_type_id: '',
        supplier_id: '',
        payment_type: '',
        service_id: ''
    };
    currentPage.value = 1;
    loadActivities();
};

const sort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
};

const sortedActivities = computed(() => {
    if (!activities.value.length) return [];

    const sorted = [...activities.value].sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];

        if (aVal === null || aVal === undefined) return 1;
        if (bVal === null || bVal === undefined) return -1;

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

const loadActivities = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            ...filters.value,
            page: currentPage.value,
            per_page: perPage.value
        };

        const response = await axios.get('/api/activities', { params });
        activities.value = response.data.data || [];

        // Update pagination metadata
        if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento delle attività';
        console.error('Error loading activities:', err);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadActivities();
};

const changePerPage = () => {
    currentPage.value = 1; // Reset to first page when changing per page
    loadActivities();
};

const deleteActivity = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questa attività?')) {
        return;
    }

    try {
        await axios.delete(`/api/activities/${id}`);
        await loadActivities();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione dell\'attività';
        console.error('Error deleting activity:', err);
    }
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY HH:mm');
};

const getPaymentBadgeClass = (paymentType) => {
    const classes = {
        'INCLUSO': 'badge bg-success-subtle text-success',
        'CLIENTE': 'badge bg-primary-subtle text-primary',
        'AGENZIA': 'badge bg-warning-subtle text-warning',
        'NESSUNO': 'badge bg-secondary-subtle text-secondary'
    };
    return classes[paymentType] || 'badge bg-secondary';
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

const loadActivityTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/activity-types');
        activityTypes.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading activity types:', err);
    }
};

const loadSuppliers = async () => {
    try {
        const response = await axios.get('/api/users', {
            params: { is_fornitore: 1 }
        });
        suppliers.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading suppliers:', err);
    }
};

const loadServices = async () => {
    try {
        const response = await axios.get('/api/services');
        services.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading services:', err);
    }
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadActivityTypes();
    await loadSuppliers();
    await loadServices();
    await loadActivities();
});
</script>
