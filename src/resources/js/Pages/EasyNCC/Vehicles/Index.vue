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
                                <BCol md="4" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadVehicles">
                                        <option value="">Tutte le aziende</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 4 : 6">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Targa, marca, modello..."
                                        @input="loadVehicles"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 4 : 6">
                                    <label class="form-label">Scadenze Documenti</label>
                                    <select v-model="filters.expiring" class="form-select form-select-sm" @change="loadVehicles">
                                        <option value="">Tutte</option>
                                        <option value="7">Prossimi 7 giorni</option>
                                        <option value="15">Prossimi 15 giorni</option>
                                        <option value="30">Prossimi 30 giorni</option>
                                        <option value="60">Prossimi 60 giorni</option>
                                        <option value="90">Prossimi 90 giorni</option>
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
                        <div v-else-if="vehicles.length > 0" class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="border: 1px solid #dee2e6;">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" class="sortable" @click="sort('license_plate')">
                                            Targa
                                            <i v-if="sortBy === 'license_plate'" :class="`bx bx-${sortOrder === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" class="sortable" @click="sort('model')">
                                            Modello
                                            <i v-if="sortBy === 'model'" :class="`bx bx-${sortOrder === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" class="sortable" @click="sort('passenger_capacity')">
                                            Passeggeri
                                            <i v-if="sortBy === 'passenger_capacity'" :class="`bx bx-${sortOrder === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                        <th scope="col">Scadenza Bollo</th>
                                        <th scope="col">Scadenza Assicurazione</th>
                                        <th scope="col">Scadenza Revisione</th>
                                        <th scope="col">Altre Scadenze</th>
                                        <th scope="col">Prossima Inattività</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="vehicle in sortedVehicles" :key="vehicle.id">
                                        <td>
                                            <div class="targa-auto mb-1">
                                                <span class="codice-targa">{{ vehicle.license_plate }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-uppercase">{{ vehicle.model || '-' }}</div>
                                            <div class="small text-muted">{{ vehicle.brand || '-' }}</div>
                                        </td>
                                        <td class="text-center">{{ vehicle.passenger_capacity }}</td>
                                        <td v-if="isSuperAdmin">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ vehicle.company?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <!-- Scadenza Bollo -->
                                        <td>
                                            <div v-if="getAttachmentByType(vehicle, 'Bollo')" @click="showAttachmentsModal(vehicle)" class="cursor-pointer" style="cursor: pointer;">
                                                <div class="small" :class="getExpiryColorClass(getAttachmentByType(vehicle, 'Bollo').expiration_date)">
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(getAttachmentByType(vehicle, 'Bollo').expiration_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ getDaysUntilExpiry(getAttachmentByType(vehicle, 'Bollo').expiration_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-warning" title="Bollo mancante">
                                                <i class="ri-error-warning-line fs-5"></i>
                                            </span>
                                        </td>
                                        <!-- Scadenza Assicurazione -->
                                        <td>
                                            <div v-if="getAttachmentByType(vehicle, 'Assicurazione')" @click="showAttachmentsModal(vehicle)" class="cursor-pointer" style="cursor: pointer;">
                                                <div class="small" :class="getExpiryColorClass(getAttachmentByType(vehicle, 'Assicurazione').expiration_date)">
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(getAttachmentByType(vehicle, 'Assicurazione').expiration_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ getDaysUntilExpiry(getAttachmentByType(vehicle, 'Assicurazione').expiration_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-warning" title="Assicurazione mancante">
                                                <i class="ri-error-warning-line fs-5"></i>
                                            </span>
                                        </td>
                                        <!-- Scadenza Revisione -->
                                        <td>
                                            <div v-if="getAttachmentByType(vehicle, 'Revisione')" @click="showAttachmentsModal(vehicle)" class="cursor-pointer" style="cursor: pointer;">
                                                <div class="small" :class="getExpiryColorClass(getAttachmentByType(vehicle, 'Revisione').expiration_date)">
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(getAttachmentByType(vehicle, 'Revisione').expiration_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ getDaysUntilExpiry(getAttachmentByType(vehicle, 'Revisione').expiration_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-warning" title="Revisione mancante">
                                                <i class="ri-error-warning-line fs-5"></i>
                                            </span>
                                        </td>
                                        <!-- Altre Scadenze -->
                                        <td>
                                            <div
                                                v-if="getNextExpiringAttachmentOther(vehicle)"
                                                @click="showAttachmentsModal(vehicle)"
                                                class="cursor-pointer"
                                                style="cursor: pointer;"
                                            >
                                                <div class="small fw-bold">{{ getNextExpiringAttachmentOther(vehicle).attachment_type }}</div>
                                                <div
                                                    class="small"
                                                    :class="getExpiryColorClass(getNextExpiringAttachmentOther(vehicle).expiration_date)"
                                                >
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(getNextExpiringAttachmentOther(vehicle).expiration_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ getDaysUntilExpiry(getNextExpiringAttachmentOther(vehicle).expiration_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-muted small">-</span>
                                        </td>
                                        <td>
                                            <div
                                                v-if="getNextUnavailability(vehicle)"
                                                @click="showUnavailabilitiesModal(vehicle)"
                                                class="cursor-pointer"
                                                style="cursor: pointer;"
                                            >
                                                <div class="small fw-bold">{{ getNextUnavailability(vehicle).type || 'N/D' }}</div>
                                                <div class="small text-warning">
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(getNextUnavailability(vehicle).start_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    Fino al {{ formatDate(getNextUnavailability(vehicle).end_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-muted small">Nessuna inattività</span>
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

        <!-- Attachments Modal -->
        <BModal
            v-model="showAttachmentsModalFlag"
            title="Allegati Veicolo"
            size="lg"
            hide-footer
            centered
        >
            <div v-if="selectedVehicle">
                <h6 class="mb-3">
                    Veicolo: <strong>{{ selectedVehicle.license_plate }}</strong> - {{ selectedVehicle.brand }} {{ selectedVehicle.model }}
                </h6>

                <div v-if="selectedVehicle.vehicle_attachments && selectedVehicle.vehicle_attachments.length > 0" class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo Documento</th>
                                <th>Data Scadenza</th>
                                <th>Stato</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in sortedAttachments" :key="doc.id">
                                <td>
                                    <i class="ri-file-text-line me-1"></i>
                                    {{ doc.attachment_type || 'N/D' }}
                                </td>
                                <td>
                                    <span v-if="doc.expiration_date" :class="getExpiryColorClass(doc.expiration_date)">
                                        {{ formatDate(doc.expiration_date) }}
                                    </span>
                                    <span v-else class="text-muted">Non specificata</span>
                                </td>
                                <td>
                                    <span v-if="doc.expiration_date" class="small" :class="getExpiryColorClass(doc.expiration_date)">
                                        {{ getDaysUntilExpiry(doc.expiration_date) }}
                                    </span>
                                </td>
                                <td>
                                    <a
                                        v-if="doc.file_path"
                                        :href="doc.file_path"
                                        target="_blank"
                                        class="btn btn-sm btn-soft-primary"
                                    >
                                        <i class="ri-download-line"></i>
                                    </a>
                                    <span v-else class="text-muted small">Nessun file</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-4">
                    <i class="ri-file-list-line display-4 text-muted"></i>
                    <p class="text-muted mt-2">Nessun allegato caricato</p>
                </div>
            </div>
        </BModal>

        <!-- Unavailabilities Modal -->
        <BModal
            v-model="showUnavailabilitiesModalFlag"
            title="Periodi di Inattività"
            size="lg"
            hide-footer
            centered
        >
            <div v-if="selectedVehicle">
                <h6 class="mb-3">
                    Veicolo: <strong>{{ selectedVehicle.license_plate }}</strong> - {{ selectedVehicle.brand }} {{ selectedVehicle.model }}
                </h6>

                <div v-if="selectedVehicle.unavailabilities && selectedVehicle.unavailabilities.length > 0" class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tipologia</th>
                                <th>Data Inizio</th>
                                <th>Data Fine</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="unav in sortedUnavailabilities" :key="unav.id">
                                <td>
                                    <i class="ri-alert-line me-1"></i>
                                    {{ unav.type || 'N/D' }}
                                </td>
                                <td>
                                    {{ formatDate(unav.start_date) }}
                                </td>
                                <td>
                                    {{ formatDate(unav.end_date) }}
                                </td>
                                <td>
                                    <span v-if="isUnavailabilityActive(unav)" class="badge bg-warning">
                                        In corso
                                    </span>
                                    <span v-else-if="isUnavailabilityFuture(unav)" class="badge bg-info">
                                        Pianificata
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        Passata
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-4">
                    <i class="ri-calendar-line display-4 text-muted"></i>
                    <p class="text-muted mt-2">Nessun periodo di inattività registrato</p>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';

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

// Modals
const showAttachmentsModalFlag = ref(false);
const showUnavailabilitiesModalFlag = ref(false);
const selectedVehicle = ref(null);

const filters = ref({
    company_id: '',
    search: '',
    expiring: ''
});

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '' ||
           filters.value.expiring !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id) count++;
    if (filters.value.search) count++;
    if (filters.value.expiring) count++;
    return count;
});

const sortedAttachments = computed(() => {
    if (!selectedVehicle.value?.vehicle_attachments) return [];

    return [...selectedVehicle.value.vehicle_attachments].sort((a, b) => {
        // Prima i documenti senza scadenza vanno alla fine
        if (!a.expiration_date && !b.expiration_date) return 0;
        if (!a.expiration_date) return 1;
        if (!b.expiration_date) return -1;

        // Ordina per data di scadenza (più vicina prima)
        return moment(a.expiration_date).diff(moment(b.expiration_date));
    });
});

const sortedUnavailabilities = computed(() => {
    if (!selectedVehicle.value?.unavailabilities) return [];

    return [...selectedVehicle.value.unavailabilities].sort((a, b) => {
        // Ordina per data di inizio (più recente prima)
        return moment(a.start_date).diff(moment(b.start_date));
    });
});

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        expiring: ''
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
    const result = await Swal.fire({
        title: 'Sei sicuro?',
        text: 'Vuoi eliminare questo veicolo?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sì, elimina',
        cancelButtonText: 'Annulla'
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/api/vehicles/${id}`);
        Swal.fire('Eliminato!', 'Il veicolo è stato eliminato.', 'success');
        await loadVehicles();
    } catch (err) {
        Swal.fire('Errore!', 'Si è verificato un errore durante l\'eliminazione.', 'error');
        console.error('Error deleting vehicle:', err);
    }
};

const showAttachmentsModal = async (vehicle) => {
    try {
        // Load full vehicle data with attachments
        const response = await axios.get(`/api/vehicles/${vehicle.id}`);
        selectedVehicle.value = response.data;
        showAttachmentsModalFlag.value = true;
    } catch (err) {
        console.error('Error loading vehicle attachments:', err);
        Swal.fire('Errore!', 'Errore nel caricamento degli allegati.', 'error');
    }
};

const showUnavailabilitiesModal = async (vehicle) => {
    try {
        // Load full vehicle data with unavailabilities
        const response = await axios.get(`/api/vehicles/${vehicle.id}`);
        selectedVehicle.value = response.data;
        showUnavailabilitiesModalFlag.value = true;
    } catch (err) {
        console.error('Error loading vehicle unavailabilities:', err);
        Swal.fire('Errore!', 'Errore nel caricamento delle inattività.', 'error');
    }
};

const excludedTypes = ['Bollo', 'Assicurazione', 'Revisione'];

const getAttachmentByType = (vehicle, typeName) => {
    if (!vehicle.vehicle_attachments || vehicle.vehicle_attachments.length === 0) {
        return null;
    }

    const attachments = vehicle.vehicle_attachments
        .filter(att => att.attachment_type === typeName && att.expiration_date)
        .sort((a, b) => moment(a.expiration_date).diff(moment(b.expiration_date)));

    // Return the one with the closest expiration date
    return attachments.length > 0 ? attachments[0] : null;
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

const getNextExpiringAttachmentOther = (vehicle) => {
    if (!vehicle.vehicle_attachments || vehicle.vehicle_attachments.length === 0) {
        return null;
    }

    const today = moment();
    const otherAttachments = vehicle.vehicle_attachments
        .filter(att => !excludedTypes.includes(att.attachment_type));

    if (otherAttachments.length === 0) return null;

    const futureAttachments = otherAttachments
        .filter(att => att.expiration_date && moment(att.expiration_date).isAfter(today))
        .sort((a, b) => moment(a.expiration_date).diff(moment(b.expiration_date)));

    return futureAttachments.length > 0 ? futureAttachments[0] :
           otherAttachments
               .filter(att => att.expiration_date)
               .sort((a, b) => moment(a.expiration_date).diff(moment(b.expiration_date)))[0] || null;
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

const getDaysUntilExpiry = (expiryDate) => {
    if (!expiryDate) return '';

    const now = moment();
    const expiry = moment(expiryDate);
    const days = expiry.diff(now, 'days');

    if (days < 0) {
        return `Scaduto da ${Math.abs(days)} giorni`;
    } else if (days === 0) {
        return 'Scade oggi';
    } else if (days === 1) {
        return 'Scade domani';
    } else if (days < 30) {
        return `Scade tra ${days} giorni`;
    } else if (days < 60) {
        return 'Scade tra 1 mese';
    } else if (days < 90) {
        return 'Scade tra 2 mesi';
    } else {
        return 'Scade tra più di 3 mesi';
    }
};

const getExpiryColorClass = (expiryDate) => {
    if (!expiryDate) return '';

    const now = moment();
    const expiry = moment(expiryDate);
    const days = expiry.diff(now, 'days');

    if (days < 0) {
        return 'text-danger';
    } else if (days < 30) {
        return 'text-warning';
    } else if (days < 90) {
        return 'text-info';
    } else {
        return 'text-success';
    }
};

const isUnavailabilityActive = (unavailability) => {
    const now = moment();
    const start = moment(unavailability.start_date);
    const end = moment(unavailability.end_date);
    return now.isBetween(start, end, 'day', '[]');
};

const isUnavailabilityFuture = (unavailability) => {
    const now = moment();
    const start = moment(unavailability.start_date);
    return start.isAfter(now);
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

.table {
    border: 1px solid #dee2e6;
}

.table td,
.table th {
    border: 1px solid #dee2e6;
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.cursor-pointer {
    cursor: pointer;
}

.cursor-pointer:hover {
    opacity: 0.8;
}
</style>
