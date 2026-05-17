<template>
    <Head title="Soste" />

    <Layout>
        <PageHeader title="Soste" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Soste</h5>
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
                            <button class="btn btn-primary btn-sm" @click="openEditModal()">
                                <i class="bx bx-plus me-1"></i>
                                Nuova Sosta
                            </button>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <!-- Riga 1: Ricerca, Data da, Data a, Tipologia -->
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Descrizione, note, rif. servizio..."
                                        @input="debouncedLoad"
                                    />
                                </BCol>
                                <BCol md="2">
                                    <label class="form-label">Data da</label>
                                    <input
                                        v-model="filters.start_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadActivities"
                                    />
                                </BCol>
                                <BCol md="2">
                                    <label class="form-label">Data a</label>
                                    <input
                                        v-model="filters.end_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadActivities"
                                    />
                                </BCol>
                                <BCol md="2">
                                    <label class="form-label">Tipologia</label>
                                    <select v-model="filters.activity_type_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutte</option>
                                        <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutte le aziende</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </BCol>
                            </BRow>

                            <!-- Riga 2: Committente, Driver, Fornitore, Pagamento -->
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Committente</label>
                                    <Multiselect
                                        v-model="filters.client_id"
                                        :options="searchClients"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                        @change="() => nextTick(loadActivities)"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Driver</label>
                                    <select v-model="filters.driver_id" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutti i driver</option>
                                        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                            {{ driver.surname }} {{ driver.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Fornitore</label>
                                    <Multiselect
                                        v-model="filters.supplier_id"
                                        :options="searchSuppliers"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                        @change="() => nextTick(loadActivities)"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Pagamento</label>
                                    <select v-model="filters.payment_type" class="form-select form-select-sm" @change="loadActivities">
                                        <option value="">Tutti</option>
                                        <option v-for="pt in activityPaymentTypes" :key="pt.id" :value="pt.code">
                                            {{ pt.name }}
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

                        <!-- Quick date filters -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button
                                v-for="preset in datePresets"
                                :key="preset.key"
                                class="btn btn-sm"
                                :class="activePreset === preset.key ? 'btn-primary' : 'btn-soft-secondary'"
                                @click="applyPreset(preset)"
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="activities.length > 0" class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 80px;">Azioni</th>
                                        <th scope="col" @click="sort('start_time')" style="cursor: pointer;">
                                            Data
                                            <i v-if="sortBy === 'start_time'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" @click="sort('name')" style="cursor: pointer; max-width: 220px;">
                                            Descrizione
                                            <i v-if="sortBy === 'name'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Tipo</th>
                                        <th scope="col">Fornitore</th>
                                        <th scope="col" @click="sort('cost')" style="cursor: pointer;">
                                            Costo
                                            <i v-if="sortBy === 'cost'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" @click="sort('payment_type')" style="cursor: pointer;">
                                            Pagamento
                                            <i v-if="sortBy === 'payment_type'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Servizio</th>
                                        <th scope="col">Driver</th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="activity in activities" :key="activity.id">
                                        <td>
                                            <button class="btn btn-sm btn-soft-primary me-1" @click="openEditModal(activity)">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteActivity(activity.id)"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                        <!-- Data -->
                                        <td class="text-nowrap">
                                            <div>{{ formatDate(activity.start_time) }}</div>
                                            <div class="small text-muted">
                                                {{ formatTime(activity.start_time) }}
                                                <template v-if="activity.end_time"> - {{ formatTime(activity.end_time) }}</template>
                                            </div>
                                        </td>
                                        <!-- Descrizione -->
                                        <td style="max-width: 220px; word-wrap: break-word; white-space: normal;">{{ activity.name }}</td>
                                        <!-- Tipo -->
                                        <td>
                                            <span v-if="activity.activity_type" class="badge bg-info-subtle text-info">
                                                {{ activity.activity_type.name }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <!-- Fornitore -->
                                        <td>
                                            <span v-if="activity.supplier">{{ activity.supplier.surname }} {{ activity.supplier.name }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <!-- Costo (unificato) -->
                                        <td class="text-nowrap">
                                            <div>&euro; {{ parseFloat(activity.cost || 0).toFixed(2) }}</div>
                                            <div v-if="activity.cost_per_person > 0" class="small text-muted">
                                                &euro; {{ parseFloat(activity.cost_per_person).toFixed(2) }}/pax
                                            </div>
                                        </td>
                                        <!-- Pagamento -->
                                        <td>
                                            <span v-if="activity.payment_type" class="badge" :class="getPaymentBadgeClass(activity.payment_type)" :style="getPaymentBadgeStyle(activity.payment_type)">
                                                {{ activity.payment_type }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                            <i v-if="activity.should_account" class="ri-calculator-line ms-1 text-success" title="Contabilizzato"></i>
                                        </td>
                                        <!-- Servizio (arricchito) -->
                                        <td>
                                            <template v-if="activity.service">
                                                <Link
                                                    :href="withReturnUrl(route('easyncc.services.edit', activity.service.id))"
                                                    class="text-primary text-decoration-underline small fw-semibold"
                                                >
                                                    {{ activity.service.reference_number }}
                                                </Link>
                                                <div class="small text-muted">
                                                    {{ formatDate(activity.service.pickup_datetime) }}
                                                </div>
                                                <div v-if="activity.service.client" class="small text-muted">
                                                    {{ activity.service.client.surname }} {{ activity.service.client.name }}
                                                </div>
                                            </template>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <!-- Driver -->
                                        <td>
                                            <template v-if="activity.service && activity.service.drivers && activity.service.drivers.length">
                                                <div v-for="driver in activity.service.drivers" :key="driver.id" class="mb-1">
                                                    <span
                                                        class="badge"
                                                        :style="`background-color: ${driver.driver_profile?.color || '#6c757d'}; font-size: 0.75rem;`"
                                                    >
                                                        {{ driver.surname }} {{ driver.name }}
                                                    </span>
                                                </div>
                                            </template>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td v-if="isSuperAdmin">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ activity.company?.name || 'N/A' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No Data -->
                        <div v-else class="text-center text-muted py-5">
                            <p>Nessuna sosta trovata</p>
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
                                            :class="{ active: currentPage === page }"
                                        >
                                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                                        </li>
                                    </template>
                                    <template v-else>
                                        <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: currentPage === page }">
                                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
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
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Edit/Create Modal -->
        <BModal v-model="showModal" :title="activityForm.id ? 'Modifica Sosta' : 'Nuova Sosta'" size="lg" hide-footer>
            <form @submit.prevent="saveActivity">
                <div v-if="modalErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(err, i) in modalErrors" :key="i">{{ err }}</li>
                    </ul>
                </div>

                <BRow>
                    <BCol md="12" class="mb-3">
                        <label class="form-label">Descrizione Sosta <span class="text-danger">*</span></label>
                        <input v-model="activityForm.name" type="text" class="form-control" required />
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Tipologia</label>
                        <select v-model="activityForm.activity_type_id" class="form-select">
                            <option value="">Nessuna</option>
                            <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                    </BCol>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Fornitore</label>
                        <select v-model="activityForm.supplier_id" class="form-select">
                            <option value="">Nessuno</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.surname }} {{ supplier.name }}
                            </option>
                        </select>
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Inizio</label>
                        <input v-model="activityForm.start_time" type="datetime-local" class="form-control" />
                    </BCol>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Fine</label>
                        <input v-model="activityForm.end_time" type="datetime-local" class="form-control" />
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="4" class="mb-3">
                        <label class="form-label">Costo</label>
                        <input v-model.number="activityForm.cost" type="number" step="0.01" min="0" class="form-control" />
                    </BCol>
                    <BCol md="4" class="mb-3">
                        <label class="form-label">Costo per Persona</label>
                        <input v-model.number="activityForm.cost_per_person" type="number" step="0.01" min="0" class="form-control" />
                    </BCol>
                    <BCol md="4" class="mb-3">
                        <label class="form-label">Pagamento</label>
                        <select v-model="activityForm.payment_type" class="form-select">
                            <option value="">Seleziona</option>
                            <option v-for="pt in activityPaymentTypes" :key="pt.id" :value="pt.code">
                                {{ pt.name }}
                            </option>
                        </select>
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="6" class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="modal_should_account" v-model="activityForm.should_account" />
                            <label class="form-check-label" for="modal_should_account">Contabilizzata</label>
                        </div>
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="12" class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea v-model="activityForm.notes" class="form-control" rows="3"></textarea>
                    </BCol>
                </BRow>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" @click="showModal = false">Annulla</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        {{ activityForm.id ? 'Aggiorna' : 'Crea' }}
                    </button>
                </div>
            </form>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import axios from 'axios';
import { useUrlFilters } from '@/composables/useUrlFilters.js';
import moment from 'moment';

const activities = ref([]);
const loading = ref(false);
const error = ref('');
const companies = ref([]);
const activityTypes = ref([]);
const activityPaymentTypes = ref([]);
const suppliers = ref([]);
const drivers = ref([]);
const currentUser = ref(null);
const showFilters = ref(true);
const sortBy = ref('start_time');
const sortOrder = ref('asc');
const activePreset = ref('from_today');

const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);

// Debounce timer for text search
let searchTimer = null;

// Modal
const showModal = ref(false);
const saving = ref(false);
const modalErrors = ref([]);
const activityForm = ref({
    id: null,
    name: '',
    activity_type_id: '',
    supplier_id: '',
    start_time: '',
    end_time: '',
    cost: 0,
    cost_per_person: 0,
    payment_type: '',
    should_account: false,
    notes: '',
    service_id: null,
    company_id: null,
});

const filters = ref({
    company_id: '',
    search: '',
    activity_type_id: '',
    supplier_id: '',
    payment_type: '',
    client_id: '',
    driver_id: '',
    start_date: moment().format('YYYY-MM-DD'),
    end_date: '',
});

const { readFromUrl, withReturnUrl } = useUrlFilters(filters, {
    page: currentPage,
    sortField: sortBy,
    sortDirection: sortOrder,
});

// Date presets
const datePresets = [
    { key: 'all', label: 'Tutte', start: '', end: '' },
    { key: 'today', label: 'Oggi', start: () => moment().format('YYYY-MM-DD'), end: () => moment().format('YYYY-MM-DD') },
    { key: 'tomorrow', label: 'Domani', start: () => moment().add(1, 'day').format('YYYY-MM-DD'), end: () => moment().add(1, 'day').format('YYYY-MM-DD') },
    { key: 'this_week', label: 'Questa settimana', start: () => moment().startOf('isoWeek').format('YYYY-MM-DD'), end: () => moment().endOf('isoWeek').format('YYYY-MM-DD') },
    { key: 'next_week', label: 'Prossima settimana', start: () => moment().add(1, 'week').startOf('isoWeek').format('YYYY-MM-DD'), end: () => moment().add(1, 'week').endOf('isoWeek').format('YYYY-MM-DD') },
    { key: 'from_today', label: 'Da oggi', start: () => moment().format('YYYY-MM-DD'), end: '' },
];

const applyPreset = (preset) => {
    activePreset.value = preset.key;
    filters.value.start_date = typeof preset.start === 'function' ? preset.start() : preset.start;
    filters.value.end_date = typeof preset.end === 'function' ? preset.end() : preset.end;
    currentPage.value = 1;
    loadActivities();
};

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '' ||
           filters.value.activity_type_id !== '' ||
           filters.value.supplier_id !== '' ||
           filters.value.payment_type !== '' ||
           filters.value.client_id !== '' ||
           filters.value.driver_id !== '' ||
           filters.value.start_date !== '' ||
           filters.value.end_date !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id) count++;
    if (filters.value.search) count++;
    if (filters.value.activity_type_id) count++;
    if (filters.value.supplier_id) count++;
    if (filters.value.payment_type) count++;
    if (filters.value.client_id) count++;
    if (filters.value.driver_id) count++;
    if (filters.value.start_date) count++;
    if (filters.value.end_date) count++;
    return count;
});

const visiblePages = computed(() => {
    const pages = [];
    let start = Math.max(1, currentPage.value - 2);
    let end = Math.min(totalPages.value, start + 4);
    start = Math.max(1, end - 4);
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        activity_type_id: '',
        supplier_id: '',
        payment_type: '',
        client_id: '',
        driver_id: '',
        start_date: '',
        end_date: '',
    };
    activePreset.value = '';
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
    currentPage.value = 1;
    loadActivities();
};

// Async search for clients (lazy loading via API)
const searchClients = async (query) => {
    const params = { type: 'client' };
    if (isSuperAdmin.value && filters.value.company_id) params.company_id = filters.value.company_id;
    if (query && query.length >= 2) params.search = query;
    else return [];

    try {
        const response = await axios.get('/api/services/filter-users', { params });
        return response.data.data || [];
    } catch (err) {
        return [];
    }
};

const searchSuppliers = async (query) => {
    if (!query || query.length < 2) return [];
    const params = { is_fornitore: 1, per_page: 50, search: query };
    if (isSuperAdmin.value && filters.value.company_id) params.company_id = filters.value.company_id;

    try {
        const response = await axios.get('/api/users', { params });
        return (response.data.data || []).map(u => ({
            value: u.id,
            label: `${u.surname || ''} ${u.name || ''}`.trim(),
        }));
    } catch (err) {
        return [];
    }
};

const debouncedLoad = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage.value = 1;
        loadActivities();
    }, 350);
};

const loadActivities = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = {};
        // Only send non-empty filter values
        if (filters.value.company_id) params.company_id = filters.value.company_id;
        if (filters.value.search) params.search = filters.value.search;
        if (filters.value.activity_type_id) params.activity_type_id = filters.value.activity_type_id;
        if (filters.value.supplier_id) params.supplier_id = filters.value.supplier_id;
        if (filters.value.payment_type) params.payment_type = filters.value.payment_type;
        if (filters.value.client_id) params.client_id = filters.value.client_id;
        if (filters.value.driver_id) params.driver_id = filters.value.driver_id;
        if (filters.value.start_date) params.start_date = filters.value.start_date;
        if (filters.value.end_date) params.end_date = filters.value.end_date;

        params.page = currentPage.value;
        params.per_page = perPage.value;
        params.sort_by = sortBy.value;
        params.sort_order = sortOrder.value;

        const response = await axios.get('/api/activities', { params });
        activities.value = response.data.data || [];
        if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento delle soste';
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
    currentPage.value = 1;
    loadActivities();
};

const openEditModal = (activity = null) => {
    if (activity) {
        activityForm.value = {
            id: activity.id,
            name: activity.name,
            activity_type_id: activity.activity_type_id || '',
            supplier_id: activity.supplier_id || '',
            start_time: activity.start_time ? moment.utc(activity.start_time).format('YYYY-MM-DDTHH:mm') : '',
            end_time: activity.end_time ? moment.utc(activity.end_time).format('YYYY-MM-DDTHH:mm') : '',
            cost: activity.cost || 0,
            cost_per_person: activity.cost_per_person || 0,
            payment_type: activity.payment_type || '',
            should_account: activity.should_account || false,
            notes: activity.notes || '',
            service_id: activity.service_id || null,
            company_id: activity.company_id || null,
        };
    } else {
        activityForm.value = {
            id: null,
            name: '',
            activity_type_id: '',
            supplier_id: '',
            start_time: '',
            end_time: '',
            cost: 0,
            cost_per_person: 0,
            payment_type: '',
            should_account: false,
            notes: '',
            service_id: null,
            company_id: isSuperAdmin.value && filters.value.company_id ? filters.value.company_id : null,
        };
    }
    modalErrors.value = [];
    showModal.value = true;
};

const saveActivity = async () => {
    saving.value = true;
    modalErrors.value = [];
    try {
        const data = {
            name: activityForm.value.name,
            activity_type_id: activityForm.value.activity_type_id || null,
            supplier_id: activityForm.value.supplier_id || null,
            start_time: activityForm.value.start_time || null,
            end_time: activityForm.value.end_time || null,
            cost: activityForm.value.cost || 0,
            cost_per_person: activityForm.value.cost_per_person || 0,
            payment_type: activityForm.value.payment_type || null,
            should_account: activityForm.value.should_account || false,
            notes: activityForm.value.notes || null,
            service_id: activityForm.value.service_id || null,
        };
        if (activityForm.value.company_id) data.company_id = activityForm.value.company_id;

        if (activityForm.value.id) {
            await axios.put(`/api/activities/${activityForm.value.id}`, data);
        } else {
            await axios.post('/api/activities', data);
        }
        showModal.value = false;
        await loadActivities();
    } catch (err) {
        if (err.response && err.response.status === 422) {
            modalErrors.value = Object.values(err.response.data.errors).flat();
        } else {
            modalErrors.value = ['Errore durante il salvataggio della sosta'];
        }
    } finally {
        saving.value = false;
    }
};

const deleteActivity = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questa sosta?')) return;
    try {
        await axios.delete(`/api/activities/${id}`);
        await loadActivities();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione della sosta';
        console.error('Error deleting activity:', err);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment.utc(date).format('DD/MM/YYYY');
};

const formatTime = (date) => {
    if (!date) return '';
    return moment.utc(date).format('HH:mm');
};

const getPaymentBadgeClass = (paymentType) => {
    const found = activityPaymentTypes.value.find(pt => pt.code === paymentType);
    if (found && found.color) return '';
    const classes = {
        'INCLUSO': 'badge bg-success-subtle text-success',
        'CLIENTE': 'badge bg-primary-subtle text-primary',
        'AGENZIA': 'badge bg-warning-subtle text-warning',
        'NESSUNO': 'badge bg-secondary-subtle text-secondary'
    };
    return classes[paymentType] || 'badge bg-secondary';
};

const getPaymentBadgeStyle = (paymentType) => {
    const found = activityPaymentTypes.value.find(pt => pt.code === paymentType);
    if (found && found.color) {
        return { backgroundColor: found.color + '99', color: found.color, fontWeight: '500' };
    }
    return {};
};

const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');

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

const loadActivityPaymentTypes = async () => {
    try {
        const response = await axios.get('/api/dictionaries/activity-payment-types');
        activityPaymentTypes.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading activity payment types:', err);
    }
};

const loadSuppliers = async () => {
    try {
        const response = await axios.get('/api/users', {
            params: { is_fornitore: 1, per_page: 200 }
        });
        suppliers.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading suppliers:', err);
    }
};

const loadDrivers = async () => {
    try {
        const response = await axios.get('/api/users', {
            params: { role: 'driver', per_page: 200, light: 1 }
        });
        drivers.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading drivers:', err);
        drivers.value = [];
    }
};

onMounted(async () => {
    await loadCurrentUser();
    readFromUrl();
    await Promise.all([
        loadCompanies(),
        loadActivityTypes(),
        loadActivityPaymentTypes(),
        loadSuppliers(),
        loadDrivers(),
        loadActivities(),
    ]);
});
</script>
