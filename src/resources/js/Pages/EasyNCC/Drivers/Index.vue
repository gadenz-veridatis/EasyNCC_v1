<template>
    <Head title="Driver" />

    <Layout>
        <PageHeader title="Driver" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Driver</h5>
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
                            <Link :href="route('easyncc.users.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Driver
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="mb-3">
                                <BCol md="3" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutte</option>
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
                                        placeholder="Nome, cognome, username, email..."
                                        @input="applyFilters"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 3 : 4">
                                    <label class="form-label">Stato</label>
                                    <select v-model="filters.is_active" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Attivi</option>
                                        <option value="0">Inattivi</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 3 : 4">
                                    <label class="form-label">Scadenze</label>
                                    <select v-model="filters.expiring" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="30">Scaduti o in scadenza (30gg)</option>
                                        <option value="60">In scadenza (60gg)</option>
                                        <option value="90">In scadenza (90gg)</option>
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
                        <div v-else-if="drivers.length > 0" class="table-responsive">
                            <table class="table table-hover table-bordered table-nowrap align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" class="sortable" @click="sortBy('username')">
                                            Username
                                            <i v-if="sortField === 'username'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col">Nickname</th>
                                        <th scope="col" class="sortable" @click="sortBy('surname')">
                                            Nome
                                            <i v-if="sortField === 'surname'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col">Contatti</th>
                                        <th scope="col">CF / P.IVA</th>
                                        <th scope="col">Scadenza Documenti</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="driver in drivers" :key="driver.id">
                                        <td class="fw-medium">{{ driver.username }}</td>
                                        <td>
                                            <span
                                                v-if="driver.nickname"
                                                class="badge"
                                                :style="`background-color: ${driver.driver_profile?.color || '#6c757d'}; color: white; font-size: 0.875rem; padding: 0.35rem 0.65rem;`"
                                            >
                                                {{ driver.nickname }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-uppercase">{{ driver.surname || '-' }}</div>
                                            <div class="small text-muted">{{ driver.name || '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="small">{{ driver.email }}</div>
                                            <div class="small text-muted" v-if="driver.phone">
                                                <i class="ri-phone-line"></i> {{ driver.phone }}
                                            </div>
                                        </td>
                                        <td>
                                            <div v-if="driver.driver_profile?.fiscal_code" class="small">
                                                CF: {{ driver.driver_profile.fiscal_code }}
                                            </div>
                                            <div v-if="driver.driver_profile?.vat_number" class="small">
                                                P.IVA: {{ driver.driver_profile.vat_number }}
                                            </div>
                                            <span v-if="!driver.driver_profile?.fiscal_code && !driver.driver_profile?.vat_number" class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <div
                                                v-if="driver.nearest_expiry"
                                                @click="showDocumentsModal(driver)"
                                                class="cursor-pointer"
                                                style="cursor: pointer;"
                                            >
                                                <div class="small fw-bold">{{ driver.nearest_expiry.document_type }}</div>
                                                <div
                                                    class="small"
                                                    :class="getExpiryColorClass(driver.nearest_expiry.expiry_date)"
                                                >
                                                    <i class="ri-calendar-line"></i>
                                                    {{ formatDate(driver.nearest_expiry.expiry_date) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ getDaysUntilExpiry(driver.nearest_expiry.expiry_date) }}
                                                </div>
                                            </div>
                                            <span v-else class="text-muted small">Nessuna scadenza</span>
                                        </td>
                                        <td>
                                            <span v-if="driver.is_active" class="badge bg-success">
                                                Attivo
                                            </span>
                                            <span v-else class="badge bg-danger">
                                                Inattivo
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.users.show', driver.id)" class="btn btn-sm btn-soft-info me-1" title="Visualizza Dettagli">
                                                <i class="bx bx-show"></i>
                                            </Link>
                                            <Link :href="route('easyncc.users.edit', driver.id)" class="btn btn-sm btn-soft-primary me-1" title="Modifica">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteDriver(driver.id)"
                                                title="Elimina"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="bx bx-user-circle display-1 text-muted"></i>
                            <h5 class="mt-3">Nessun driver trovato</h5>
                            <p class="text-muted">Non ci sono driver che corrispondono ai criteri di ricerca.</p>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Documents Modal -->
        <BModal
            v-model="showDocumentsModalFlag"
            title="Documenti Driver"
            size="lg"
            hide-footer
            centered
        >
            <div v-if="selectedDriver">
                <h6 class="mb-3">
                    Driver: <strong>{{ selectedDriver.surname }} {{ selectedDriver.name }}</strong>
                    <span
                        v-if="selectedDriver.nickname"
                        class="badge ms-2"
                        :style="`background-color: ${selectedDriver.driver_profile?.color || '#6c757d'}; color: white;`"
                    >
                        {{ selectedDriver.nickname }}
                    </span>
                </h6>

                <div v-if="selectedDriver.driver_attachments && selectedDriver.driver_attachments.length > 0" class="table-responsive">
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
                            <tr v-for="doc in sortedDocuments" :key="doc.id">
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
                    <p class="text-muted mt-2">Nessun documento caricato</p>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';

const drivers = ref([]);
const companies = ref([]);
const loading = ref(false);
const error = ref('');
const showFilters = ref(false);

// Documents modal
const showDocumentsModalFlag = ref(false);
const selectedDriver = ref(null);

// User info
const currentUser = ref(null);

// Filters
const filters = ref({
    company_id: '',
    search: '',
    is_active: '',
    expiring: ''
});

// Sorting
const sortField = ref('surname');
const sortDirection = ref('asc');

// Computed
const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(value => value !== '').length;
});

const sortedDocuments = computed(() => {
    if (!selectedDriver.value?.driver_attachments) return [];

    return [...selectedDriver.value.driver_attachments].sort((a, b) => {
        // Prima i documenti senza scadenza vanno alla fine
        if (!a.expiration_date && !b.expiration_date) return 0;
        if (!a.expiration_date) return 1;
        if (!b.expiration_date) return -1;

        // Ordina per data di scadenza (più vicina prima)
        return moment(a.expiration_date).diff(moment(b.expiration_date));
    });
});

// Methods
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

const loadDrivers = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            role: 'driver',
            ...filters.value,
            sort_field: sortField.value,
            sort_direction: sortDirection.value
        };

        const response = await axios.get('/api/users', { params });
        drivers.value = response.data.data || [];
    } catch (err) {
        error.value = 'Errore nel caricamento dei driver';
        console.error('Error loading drivers:', err);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    loadDrivers();
};

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        is_active: '',
        expiring: ''
    };
    loadDrivers();
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    loadDrivers();
};

const deleteDriver = async (id) => {
    const result = await Swal.fire({
        title: 'Sei sicuro?',
        text: 'Vuoi eliminare questo driver?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sì, elimina!',
        cancelButtonText: 'Annulla'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/users/${id}`);
            Swal.fire('Eliminato!', 'Il driver è stato eliminato.', 'success');
            loadDrivers();
        } catch (err) {
            Swal.fire('Errore!', 'Si è verificato un errore durante l\'eliminazione.', 'error');
            console.error('Error deleting driver:', err);
        }
    }
};

const showDocumentsModal = async (driver) => {
    try {
        // Load full driver data with attachments
        const response = await axios.get(`/api/users/${driver.id}`);
        selectedDriver.value = response.data;
        showDocumentsModalFlag.value = true;
    } catch (err) {
        console.error('Error loading driver documents:', err);
        Swal.fire('Errore!', 'Errore nel caricamento dei documenti.', 'error');
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const getDaysUntilExpiry = (expiryDate) => {
    if (!expiryDate) return '';

    const days = moment(expiryDate).diff(moment(), 'days');

    if (days < 0) {
        return `scaduto da ${Math.abs(days)} giorni`;
    } else if (days === 0) {
        return 'scade oggi';
    } else if (days === 1) {
        return 'scade domani';
    } else {
        return `${days} giorni`;
    }
};

const getExpiryColorClass = (expiryDate) => {
    if (!expiryDate) return '';

    const days = moment(expiryDate).diff(moment(), 'days');

    if (days < 0) {
        return 'text-danger';
    } else if (days <= 30) {
        return 'text-danger';
    } else if (days <= 60) {
        return 'text-warning';
    } else {
        return 'text-success';
    }
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadDrivers();
});
</script>

<style scoped>
.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>
