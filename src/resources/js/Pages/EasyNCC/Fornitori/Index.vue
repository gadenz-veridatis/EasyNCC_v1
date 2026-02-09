<template>
    <Head title="Fornitori" />

    <Layout>
        <PageHeader title="Fornitori" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Fornitori</h5>
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
                                Nuovo Fornitore
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
                                        placeholder="Nome, cognome, ragione sociale, email..."
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
                                    <label class="form-label">Intermediario</label>
                                    <select v-model="filters.is_intermediario" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Sì</option>
                                        <option value="0">No</option>
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
                        <div v-else-if="fornitori.length > 0" class="table-responsive">
                            <table class="table table-hover table-bordered table-nowrap align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" class="sortable" @click="sortBy('surname')">
                                            Utente
                                            <i v-if="sortField === 'surname'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" class="sortable" @click="sortBy('email')">
                                            Email
                                            <i v-if="sortField === 'email'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col">Telefono</th>
                                        <th scope="col">Ragione Sociale</th>
                                        <th scope="col">Partita IVA</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="fornitore in fornitori" :key="fornitore.id">
                                        <td class="fw-medium">
                                            <span class="text-uppercase">{{ fornitore.surname || '-' }}</span>
                                            {{ fornitore.name || '' }}
                                        </td>
                                        <td>{{ fornitore.email || '-' }}</td>
                                        <td>{{ fornitore.phone || fornitore.client_profile?.phone || '-' }}</td>
                                        <td>{{ fornitore.client_profile?.business_name || '-' }}</td>
                                        <td>{{ fornitore.client_profile?.vat_number || '-' }}</td>
                                        <td>
                                            <Link :href="route('easyncc.users.show', fornitore.id)" class="btn btn-sm btn-soft-info me-1" title="Visualizza Dettagli">
                                                <i class="bx bx-show"></i>
                                            </Link>
                                            <Link :href="route('easyncc.users.edit', fornitore.id)" class="btn btn-sm btn-soft-primary me-1" title="Modifica">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteFornitore(fornitore.id)"
                                                title="Elimina"
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
                                            <li v-if="currentPage > 3" class="page-item">
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
                                            <li v-if="currentPage < totalPages - 2" class="page-item">
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

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="bx bx-store display-1 text-muted"></i>
                            <h5 class="mt-3">Nessun fornitore trovato</h5>
                            <p class="text-muted">Non ci sono fornitori che corrispondono ai criteri di ricerca.</p>
                        </div>

                        <!-- Error Message -->
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
import Swal from 'sweetalert2';

const fornitori = ref([]);
const companies = ref([]);
const loading = ref(false);
const error = ref('');
const showFilters = ref(false);

// User info
const currentUser = ref(null);

// Pagination
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);

// Filters
const filters = ref({
    company_id: '',
    search: '',
    is_active: '',
    is_intermediario: ''
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

const loadFornitori = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            role: 'collaboratore',
            is_fornitore: 1,
            page: currentPage.value,
            per_page: perPage.value,
            ...filters.value,
            sort_by: sortField.value,
            sort_direction: sortDirection.value
        };

        const response = await axios.get('/api/users', { params });
        fornitori.value = response.data.data || [];

        // Handle pagination metadata
        if (response.data.last_page !== undefined) {
            totalPages.value = response.data.last_page || 1;
            totalRecords.value = response.data.total || 0;
            currentPage.value = response.data.current_page || 1;
        } else if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        } else {
            totalPages.value = 1;
            totalRecords.value = fornitori.value.length;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento dei fornitori';
        console.error('Error loading fornitori:', err);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    currentPage.value = 1;
    loadFornitori();
};

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        is_active: '',
        is_intermediario: ''
    };
    currentPage.value = 1;
    loadFornitori();
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    loadFornitori();
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadFornitori();
};

const changePerPage = () => {
    currentPage.value = 1;
    loadFornitori();
};

const deleteFornitore = async (id) => {
    const result = await Swal.fire({
        title: 'Sei sicuro?',
        text: 'Vuoi eliminare questo fornitore?',
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
            Swal.fire('Eliminato!', 'Il fornitore è stato eliminato.', 'success');
            loadFornitori();
        } catch (err) {
            Swal.fire('Errore!', 'Si è verificato un errore durante l\'eliminazione.', 'error');
            console.error('Error deleting fornitore:', err);
        }
    }
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadFornitori();
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
