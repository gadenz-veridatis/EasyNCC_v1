<template>
    <Head title="Task" />

    <Layout>
        <PageHeader title="Task" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Task</h5>
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
                            <Link v-if="canCreate" :href="route('easyncc.tasks.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Task
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="mb-3">
                                <BCol md="3" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadTasks">
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
                                        placeholder="Nome task o note..."
                                        @input="loadTasks"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 3 : 4">
                                    <label class="form-label">Stato</label>
                                    <select v-model="filters.status" class="form-select form-select-sm" @change="loadTasks">
                                        <option value="">Tutti</option>
                                        <option value="to_complete">Da Completare</option>
                                        <option value="completed">Completato</option>
                                        <option value="cancelled">Annullato</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 3 : 4" v-if="canViewAll">
                                    <label class="form-label">Assegnatario</label>
                                    <select v-model="filters.assigned_to" class="form-select form-select-sm" @change="loadTasks">
                                        <option value="">Tutti</option>
                                        <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                                            {{ user.name }} {{ user.surname }} ({{ user.role }})
                                        </option>
                                    </select>
                                </BCol>
                            </BRow>
                            <BRow class="mb-3">
                                <BCol :md="isSuperAdmin ? 6 : 6">
                                    <label class="form-label">Servizio</label>
                                    <select v-model="filters.service_id" class="form-select form-select-sm" @change="loadTasks">
                                        <option value="">Tutti</option>
                                        <option v-for="service in services" :key="service.id" :value="service.id">
                                            {{ service.reference_number }}
                                        </option>
                                    </select>
                                </BCol>
                            </BRow>
                            <BRow class="mb-3">
                                <BCol :md="isSuperAdmin ? 6 : 6">
                                    <label class="form-label">Data Scadenza (Da)</label>
                                    <input
                                        v-model="filters.start_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadTasks"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 6 : 6">
                                    <label class="form-label">Data Scadenza (A)</label>
                                    <input
                                        v-model="filters.end_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadTasks"
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
                        <div v-else-if="tasks.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" @click="sort('name')" style="cursor: pointer;">
                                            Nome Task
                                            <i v-if="sortBy === 'name'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Servizio</th>
                                        <th scope="col" @click="sort('due_date')" style="cursor: pointer;">
                                            Scadenza
                                            <i v-if="sortBy === 'due_date'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col" v-if="canViewAll">Assegnatario</th>
                                        <th scope="col" @click="sort('status')" style="cursor: pointer;">
                                            Stato
                                            <i v-if="sortBy === 'status'" :class="sortOrder === 'asc' ? 'bx bx-up-arrow-alt' : 'bx bx-down-arrow-alt'"></i>
                                        </th>
                                        <th scope="col">Note</th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="task in sortedTasks" :key="task.id">
                                        <td class="fw-medium">{{ task.name }}</td>
                                        <td>
                                            <span v-if="task.service" class="badge bg-info-subtle text-info">
                                                {{ task.service.reference_number }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="task.due_date" :class="getDueDateClass(task)">
                                                {{ formatDate(task.due_date) }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td v-if="canViewAll">
                                            <div v-if="task.assigned_users && task.assigned_users.length > 0">
                                                <div v-for="(user, index) in task.assigned_users" :key="user.id" class="mb-1">
                                                    {{ user.name }} {{ user.surname }}
                                                    <br><small class="text-muted">{{ user.role }}</small>
                                                </div>
                                            </div>
                                            <span v-else class="text-muted">Non assegnato</span>
                                        </td>
                                        <td>
                                            <span :class="getStatusBadgeClass(task.status)">
                                                {{ getStatusLabel(task.status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ task.notes ? (task.notes.substring(0, 50) + (task.notes.length > 50 ? '...' : '')) : '-' }}</small>
                                        </td>
                                        <td v-if="isSuperAdmin">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ task.company?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.tasks.edit', task.id)" class="btn btn-sm btn-soft-primary me-2">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                v-if="canDelete"
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteTask(task.id)"
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
                            <p>Nessun task trovato</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="tasks.length > 0" class="d-flex justify-content-between align-items-center mt-3 px-3">
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

const tasks = ref([]);
const loading = ref(false);
const error = ref('');
const companies = ref([]);
const assignableUsers = ref([]);
const services = ref([]);
const currentUser = ref(null);
const showFilters = ref(true);
const sortBy = ref('due_date');
const sortOrder = ref('asc');

const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);

const filters = ref({
    company_id: '',
    search: '',
    status: '',
    assigned_to: '',
    service_id: '',
    start_date: '',
    end_date: ''
});

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '' ||
           filters.value.status !== '' ||
           filters.value.assigned_to !== '' ||
           filters.value.service_id !== '' ||
           filters.value.start_date !== '' ||
           filters.value.end_date !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id) count++;
    if (filters.value.search) count++;
    if (filters.value.status) count++;
    if (filters.value.assigned_to) count++;
    if (filters.value.service_id) count++;
    if (filters.value.start_date) count++;
    if (filters.value.end_date) count++;
    return count;
});

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        status: '',
        assigned_to: '',
        service_id: '',
        start_date: '',
        end_date: ''
    };
    currentPage.value = 1;
    loadTasks();
};

const sort = (column) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
};

const sortedTasks = computed(() => {
    if (!tasks.value.length) return [];

    const sorted = [...tasks.value].sort((a, b) => {
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

const loadTasks = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            ...filters.value,
            page: currentPage.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value
        };

        const response = await axios.get('/api/tasks', { params });
        tasks.value = response.data.data || [];

        // Update pagination metadata
        if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento dei task';
        console.error('Error loading tasks:', err);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadTasks();
};

const changePerPage = () => {
    currentPage.value = 1; // Reset to first page when changing per page
    loadTasks();
};

const deleteTask = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo task?')) {
        return;
    }

    try {
        await axios.delete(`/api/tasks/${id}`);
        await loadTasks();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione del task';
        console.error('Error deleting task:', err);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const getDueDateClass = (task) => {
    if (!task.due_date) return '';
    if (task.status === 'completed' || task.status === 'cancelled') return '';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(task.due_date);
    dueDate.setHours(0, 0, 0, 0);

    if (dueDate < today) {
        return 'text-danger fw-bold';
    }
    const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 3) {
        return 'text-warning fw-bold';
    }
    return '';
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'to_complete': 'badge bg-warning-subtle text-warning',
        'completed': 'badge bg-success-subtle text-success',
        'cancelled': 'badge bg-secondary-subtle text-secondary'
    };
    return classes[status] || 'badge bg-secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        'to_complete': 'Da Completare',
        'completed': 'Completato',
        'cancelled': 'Annullato'
    };
    return labels[status] || status;
};

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
});

const canViewAll = computed(() => {
    const role = currentUser.value?.role;
    return role === 'super-admin' || role === 'admin' || role === 'operator';
});

const canCreate = computed(() => {
    const role = currentUser.value?.role;
    return role === 'super-admin' || role === 'admin' || role === 'operator';
});

const canDelete = computed(() => {
    const role = currentUser.value?.role;
    return role === 'super-admin' || role === 'admin';
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

const loadAssignableUsers = async () => {
    if (!canViewAll.value) return;

    try {
        const response = await axios.get('/api/users', {
            params: {
                role: 'admin,operator,driver,accountant'
            }
        });
        assignableUsers.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading assignable users:', err);
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
    await loadAssignableUsers();
    await loadServices();
    await loadTasks();
});
</script>
