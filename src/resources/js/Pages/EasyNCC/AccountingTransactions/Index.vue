<template>
    <Head title="Contabilità - Movimenti" />

    <Layout>
        <PageHeader title="Movimenti Contabili" pageTitle="Contabilità" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Movimenti</h5>
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
                            <button
                                type="button"
                                class="btn btn-soft-primary btn-sm"
                                @click="showSummary = !showSummary"
                            >
                                <i :class="showSummary ? 'bx bx-chevron-up' : 'bx bx-chevron-down'"></i>
                                {{ showSummary ? 'Nascondi Riepilogo' : 'Mostra Riepilogo' }}
                            </button>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <fieldset>
                                <legend class="fs-6 fw-semibold text-primary mb-3">
                                    <i class="ri-filter-3-line me-2"></i>Filtri di Ricerca
                                </legend>
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Numero documento, causale, note..."
                                        @input="loadTransactions"
                                    />
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Tipo Movimento</label>
                                    <select v-model="filters.transaction_type" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutti</option>
                                        <option value="purchase">Acquisto</option>
                                        <option value="sale">Vendita</option>
                                        <option value="intermediation">Intermediazione</option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Stato</label>
                                    <select v-model="filters.status" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutti</option>
                                        <option value="to_pay">Da Pagare</option>
                                        <option value="paid">Pagato</option>
                                        <option value="to_collect">Da Incassare</option>
                                        <option value="collected">Incassato</option>
                                        <option value="suspended">Sospeso</option>
                                        <option value="cancelled">Annullato</option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Servizio</label>
                                    <select v-model="filters.service_id" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutti</option>
                                        <option v-for="service in services" :key="service.id" :value="service.id">
                                            {{ service.reference_number }}
                                        </option>
                                    </select>
                                </BCol>
                            </BRow>
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Causale Contabile</label>
                                    <select v-model="filters.accounting_entry_id" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutte</option>
                                        <option v-for="entry in accountingEntries" :key="entry.id" :value="entry.id">
                                            {{ entry.name }} ({{ entry.abbreviation }})
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Controparte</label>
                                    <select v-model="filters.counterpart_id" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutte</option>
                                        <option v-for="user in counterparts" :key="user.id" :value="user.id">
                                            {{ user.name }} {{ user.surname }} ({{ user.role }})
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Data Da</label>
                                    <input
                                        v-model="filters.start_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadTransactions"
                                    />
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Data A</label>
                                    <input
                                        v-model="filters.end_date"
                                        type="date"
                                        class="form-control form-control-sm"
                                        @change="loadTransactions"
                                    />
                                </BCol>
                            </BRow>
                            <BRow class="mb-3" v-if="isSuperAdmin">
                                <BCol md="12">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadTransactions">
                                        <option value="">Tutte le aziende</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
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
                            </fieldset>
                        </div>

                        <!-- Summary Statistics (Collapsible) -->
                        <div v-if="showSummary" class="border rounded p-3 mb-3 bg-light">
                            <fieldset>
                                <legend class="fs-6 fw-semibold text-primary mb-3">
                                    <i class="ri-pie-chart-line me-2"></i>Riepilogo Contabile
                                </legend>
                            <BRow>
                                    <!-- Vendite -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-success">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Vendite
                                                        </p>
                                                        <h5 class="mb-0 text-success">
                                                            € {{ formatAmount(summary.sales) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-money-euro-circle-line fs-2 text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Acquisti -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-danger">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Acquisti
                                                        </p>
                                                        <h5 class="mb-0 text-danger">
                                                            € {{ formatAmount(summary.purchases) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-shopping-cart-line fs-2 text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Intermediazioni -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-warning">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Intermediazioni
                                                        </p>
                                                        <h5 class="mb-0 text-warning">
                                                            € {{ formatAmount(summary.intermediations) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-team-line fs-2 text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Resi Fornitore -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-info">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Resi
                                                        </p>
                                                        <h5 class="mb-0 text-info">
                                                            € {{ formatAmount(summary.supplierRefunds) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-refund-line fs-2 text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Rimborsi Cliente -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-secondary">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Rimborsi
                                                        </p>
                                                        <h5 class="mb-0 text-secondary">
                                                            € {{ formatAmount(summary.customerRefunds) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-hand-coin-line fs-2 text-secondary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Risultato Totale (moved to the end) -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border" :class="summary.total >= 0 ? 'border-success' : 'border-danger'">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Risultato
                                                        </p>
                                                        <h5 class="mb-0" :class="summary.total >= 0 ? 'text-success' : 'text-danger'">
                                                            € {{ formatAmount(summary.total) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i :class="['fs-2', summary.total >= 0 ? 'ri-arrow-up-circle-line text-success' : 'ri-arrow-down-circle-line text-danger']"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-2">
                                <button
                                    v-if="selectedTransactions.length > 0 && canDelete"
                                    type="button"
                                    class="btn btn-soft-danger btn-sm"
                                    @click="deleteSelectedTransactions"
                                >
                                    <i class="ri-delete-bin-line me-1"></i>
                                    Cancella Selezionati ({{ selectedTransactions.length }})
                                </button>
                            </div>
                            <Link :href="route('easyncc.accounting-transactions.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Movimento
                            </Link>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="transactions.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 30px;">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                :checked="isAllSelected"
                                                @change="toggleSelectAll"
                                                title="Seleziona tutti"
                                            />
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('transaction_date')">
                                            Data
                                            <i v-if="sortField === 'transaction_date'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('transaction_type')">
                                            Tipo
                                            <i v-if="sortField === 'transaction_type'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('amount')">
                                            Importo
                                            <i v-if="sortField === 'amount'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('installment')">
                                            Rata
                                            <i v-if="sortField === 'installment'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col">Causali</th>
                                        <th scope="col">Controparte</th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('document_number')">
                                            Documenti
                                            <i v-if="sortField === 'document_number'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('status')">
                                            Stato
                                            <i v-if="sortField === 'status'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col">Servizio</th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="transaction in transactions" :key="transaction.id">
                                        <td>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                :value="transaction.id"
                                                v-model="selectedTransactions"
                                            />
                                        </td>
                                        <td>{{ formatDate(transaction.transaction_date) }}</td>
                                        <td>
                                            <span
                                                :class="getTransactionTypeBadgeClass(transaction.transaction_type)"
                                                :title="getTransactionTypeLabel(transaction.transaction_type)"
                                            >
                                                {{ getTransactionTypeAbbr(transaction.transaction_type) }}
                                            </span>
                                        </td>
                                        <td class="fw-medium">€ {{ parseFloat(transaction.amount).toFixed(2) }}</td>
                                        <td>
                                            <span
                                                class="badge bg-secondary-subtle text-secondary"
                                                :title="getInstallmentLabel(transaction.installment)"
                                            >
                                                {{ getInstallmentAbbr(transaction.installment) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="transaction.payment_reason || transaction.accounting_entry">
                                                <span v-if="transaction.payment_reason">{{ transaction.payment_reason }}</span>
                                                <br v-if="transaction.payment_reason && transaction.accounting_entry">
                                                <small v-if="transaction.accounting_entry" class="text-muted">
                                                    {{ transaction.accounting_entry.abbreviation || transaction.accounting_entry.name }}
                                                </small>
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="transaction.counterpart">
                                                {{ transaction.counterpart.name }} {{ transaction.counterpart.surname }}
                                                <br>
                                                <small class="text-muted">{{ transaction.counterpart.email }}</small>
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <div v-if="transaction.document_number || transaction.document_due_date">
                                                <span v-if="transaction.document_number">{{ transaction.document_number }}</span>
                                                <span v-else class="text-muted">-</span>
                                                <br>
                                                <small v-if="transaction.document_due_date" :class="getDueDateClass(transaction)">
                                                    {{ formatDate(transaction.document_due_date) }}
                                                </small>
                                                <small v-else class="text-muted">-</small>
                                            </div>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span
                                                :class="getStatusBadgeClass(transaction.status)"
                                                :title="getStatusLabel(transaction.status, transaction.transaction_type)"
                                            >
                                                {{ getStatusAbbr(transaction.status, transaction.transaction_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link v-if="transaction.service"
                                                :href="route('easyncc.services.edit', transaction.service.id)"
                                                class="text-primary text-decoration-underline"
                                                :title="'Vai al servizio ' + transaction.service.reference_number"
                                            >
                                                {{ transaction.service.reference_number }}
                                            </Link>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td v-if="isSuperAdmin">
                                            <span v-if="transaction.company">{{ transaction.company.name }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <Link
                                                    :href="route('easyncc.accounting-transactions.edit', transaction.id)"
                                                    class="btn btn-soft-primary btn-sm"
                                                    title="Modifica"
                                                >
                                                    <i class="ri-pencil-line"></i>
                                                </Link>
                                                <button
                                                    @click="deleteTransaction(transaction.id)"
                                                    class="btn btn-soft-danger btn-sm"
                                                    title="Elimina"
                                                    v-if="canDelete"
                                                >
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="ri-file-list-3-line display-4 text-muted"></i>
                            <p class="text-muted mt-3">Nessun movimento trovato</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="pagination.total > 0" class="row align-items-center mt-3">
                            <div class="col-sm-6">
                                <div class="text-muted">
                                    Visualizzazione <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</strong> -
                                    <strong>{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong>
                                    di <strong>{{ pagination.total }}</strong> risultati
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <nav aria-label="Paginazione movimenti">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                            <button class="page-link" @click="goToPage(1)" :disabled="pagination.current_page === 1">
                                                <i class="bx bx-chevrons-left"></i>
                                            </button>
                                        </li>
                                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                            <button class="page-link" @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">
                                                <i class="bx bx-chevron-left"></i>
                                            </button>
                                        </li>

                                        <li
                                            v-for="page in visiblePages"
                                            :key="page"
                                            class="page-item"
                                            :class="{ active: pagination.current_page === page }"
                                        >
                                            <button class="page-link" @click="goToPage(page)">
                                                {{ page }}
                                            </button>
                                        </li>

                                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                            <button class="page-link" @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">
                                                <i class="bx bx-chevron-right"></i>
                                            </button>
                                        </li>
                                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                            <button class="page-link" @click="goToPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page">
                                                <i class="bx bx-chevrons-right"></i>
                                            </button>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- Per Page Selector -->
                        <div v-if="pagination.total > 0" class="row mt-3">
                            <div class="col-sm-12 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0">Risultati per pagina:</label>
                                    <select v-model="perPage" @change="changePerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
    },
    setup() {
        const transactions = ref([]);
        const companies = ref([]);
        const services = ref([]);
        const counterparts = ref([]);
        const accountingEntries = ref([]);
        const loading = ref(false);
        const showFilters = ref(false);
        const showSummary = ref(true);
        const perPage = ref(10);
        const sortField = ref('transaction_date');
        const sortDirection = ref('desc');
        const selectedTransactions = ref([]);

        const filters = ref({
            company_id: '',
            search: '',
            transaction_type: '',
            status: '',
            service_id: '',
            accounting_entry_id: '',
            counterpart_id: '',
            start_date: '',
            end_date: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        });

        const summary = ref({
            total: 0,
            sales: 0,
            purchases: 0,
            intermediations: 0,
            supplierRefunds: 0,
            customerRefunds: 0,
        });

        const user = ref(null);

        const isSuperAdmin = computed(() => user.value?.role === 'super-admin');
        const canDelete = computed(() => user.value?.role === 'super-admin' || user.value?.role === 'admin');

        const isAllSelected = computed(() => {
            return transactions.value.length > 0 &&
                   selectedTransactions.value.length === transactions.value.length;
        });

        const hasActiveFilters = computed(() => {
            return filters.value.company_id !== '' ||
                   filters.value.search !== '' ||
                   filters.value.transaction_type !== '' ||
                   filters.value.status !== '' ||
                   filters.value.service_id !== '' ||
                   filters.value.accounting_entry_id !== '' ||
                   filters.value.counterpart_id !== '' ||
                   filters.value.start_date !== '' ||
                   filters.value.end_date !== '';
        });

        const activeFiltersCount = computed(() => {
            let count = 0;
            if (filters.value.company_id !== '') count++;
            if (filters.value.search !== '') count++;
            if (filters.value.transaction_type !== '') count++;
            if (filters.value.status !== '') count++;
            if (filters.value.service_id !== '') count++;
            if (filters.value.accounting_entry_id !== '') count++;
            if (filters.value.counterpart_id !== '') count++;
            if (filters.value.start_date !== '') count++;
            if (filters.value.end_date !== '') count++;
            return count;
        });

        const visiblePages = computed(() => {
            const pages = [];
            const maxVisiblePages = 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);

            let startPage = Math.max(1, pagination.value.current_page - halfVisible);
            let endPage = Math.min(pagination.value.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                pages.push(i);
            }

            return pages;
        });

        const loadUser = async () => {
            try {
                const response = await axios.get('/api/user');
                user.value = response.data;
            } catch (error) {
                console.error('Error loading user:', error);
            }
        };

        const loadCompanies = async () => {
            try {
                const response = await axios.get('/api/companies');
                companies.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        };

        const loadServices = async () => {
            try {
                const params = {};
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/services', { params });
                services.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading services:', error);
            }
        };

        const loadCounterparts = async () => {
            try {
                const params = {
                    per_page: 1000, // Load all counterparts
                };
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/users', { params });
                // Laravel pagination returns data in response.data.data
                const users = response.data.data || [];

                // Map users to include their counterpart type based on flags and role
                counterparts.value = users.map(u => {
                    const types = [];

                    // Check if user is intermediario
                    if (u.is_intermediario) {
                        types.push('intermediario');
                    }

                    // Check client profile flags
                    if (u.client_profile) {
                        if (u.client_profile.is_committente) {
                            types.push('committente');
                        }
                        if (u.client_profile.is_fornitore) {
                            types.push('fornitore');
                        }
                    }

                    return {
                        ...u,
                        counterpart_types: types,
                    };
                }).filter(u => u.counterpart_types.length > 0); // Only users with at least one type

            } catch (error) {
                console.error('Error loading counterparts:', error);
            }
        };

        const loadAccountingEntries = async () => {
            try {
                const params = {
                    per_page: 1000, // Load all entries
                };
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/dictionaries/accounting-entries', { params });
                accountingEntries.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounting entries:', error);
            }
        };

        const loadTransactions = async (page = 1) => {
            loading.value = true;
            selectedTransactions.value = []; // Reset selection on page change
            try {
                const params = {
                    page,
                    per_page: perPage.value,
                    sort_by: sortField.value,
                    sort_order: sortDirection.value,
                    ...filters.value,
                };

                const response = await axios.get('/api/accounting-transactions', { params });

                if (response.data.success) {
                    transactions.value = response.data.data;
                    pagination.value = response.data.meta;

                    // Calculate summary from all transactions (not just current page)
                    await calculateSummary();
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            } finally {
                loading.value = false;
            }
        };

        const calculateSummary = async () => {
            try {
                // Load all transactions matching filters to calculate totals
                const params = {
                    per_page: 999999, // Get all records
                    ...filters.value,
                };

                const response = await axios.get('/api/accounting-transactions', { params });

                if (response.data.success) {
                    const allTransactions = response.data.data;

                    let sales = 0;
                    let purchases = 0;
                    let intermediations = 0;
                    let supplierRefunds = 0;
                    let customerRefunds = 0;

                    allTransactions.forEach(t => {
                        const amount = parseFloat(t.amount);

                        if (t.transaction_type === 'sale') {
                            if (t.installment === 'customer_refund') {
                                customerRefunds += amount;
                            } else {
                                sales += amount;
                            }
                        } else if (t.transaction_type === 'purchase') {
                            if (t.installment === 'supplier_refund') {
                                supplierRefunds += amount;
                            } else {
                                purchases += amount;
                            }
                        } else if (t.transaction_type === 'intermediation') {
                            intermediations += amount;
                        }
                    });

                    // Risultato = Vendite + Resi Fornitore - Acquisti - Intermediazioni - Rimborsi Cliente
                    const total = sales + supplierRefunds - purchases - intermediations - customerRefunds;

                    summary.value = {
                        total,
                        sales,
                        purchases,
                        intermediations,
                        supplierRefunds,
                        customerRefunds,
                    };
                }
            } catch (error) {
                console.error('Error calculating summary:', error);
            }
        };

        const sortBy = (field) => {
            if (sortField.value === field) {
                // Toggle direction
                sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
            } else {
                sortField.value = field;
                sortDirection.value = 'asc';
            }
            loadTransactions(1);
        };

        const goToPage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                loadTransactions(page);
            }
        };

        const changePerPage = () => {
            loadTransactions(1);
        };

        const resetFilters = () => {
            filters.value = {
                company_id: '',
                search: '',
                transaction_type: '',
                status: '',
                service_id: '',
                accounting_entry_id: '',
                counterpart_id: '',
                start_date: '',
                end_date: '',
            };
            loadTransactions(1);
        };

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedTransactions.value = [];
            } else {
                selectedTransactions.value = transactions.value.map(t => t.id);
            }
        };

        const deleteTransaction = async (id) => {
            if (confirm('Sei sicuro di voler eliminare questo movimento?')) {
                try {
                    await axios.delete(`/api/accounting-transactions/${id}`);
                    selectedTransactions.value = [];
                    loadTransactions(pagination.value.current_page);
                } catch (error) {
                    console.error('Error deleting transaction:', error);
                    alert('Errore durante l\'eliminazione del movimento');
                }
            }
        };

        const deleteSelectedTransactions = async () => {
            if (selectedTransactions.value.length === 0) return;

            const count = selectedTransactions.value.length;
            if (confirm(`Sei sicuro di voler eliminare ${count} movimenti selezionati?`)) {
                try {
                    // Delete all selected transactions
                    await Promise.all(
                        selectedTransactions.value.map(id =>
                            axios.delete(`/api/accounting-transactions/${id}`)
                        )
                    );

                    selectedTransactions.value = [];
                    loadTransactions(pagination.value.current_page);
                } catch (error) {
                    console.error('Error deleting transactions:', error);
                    alert('Errore durante l\'eliminazione dei movimenti selezionati');
                }
            }
        };

        const formatDate = (date) => {
            return date ? moment(date).format('DD/MM/YYYY') : '-';
        };

        const getTransactionTypeLabel = (type) => {
            const labels = {
                purchase: 'Acquisto',
                sale: 'Vendita',
                intermediation: 'Intermediazione',
            };
            return labels[type] || type;
        };

        const getTransactionTypeAbbr = (type) => {
            const abbrs = {
                purchase: 'ACQ',
                sale: 'VEN',
                intermediation: 'INT',
            };
            return abbrs[type] || type;
        };

        const getTransactionTypeBadgeClass = (type) => {
            const classes = {
                purchase: 'badge bg-danger-subtle text-danger',
                sale: 'badge bg-success-subtle text-success',
                intermediation: 'badge bg-warning-subtle text-warning',
            };
            return classes[type] || 'badge bg-secondary-subtle text-secondary';
        };

        const getInstallmentLabel = (installment) => {
            const labels = {
                deposit: 'Acconto',
                balance: 'Saldo',
                supplier_refund: 'Reso Fornitore',
                customer_refund: 'Rimborso Cliente',
            };
            return labels[installment] || installment;
        };

        const getInstallmentAbbr = (installment) => {
            const abbrs = {
                deposit: 'ACC',
                balance: 'SAL',
                supplier_refund: 'RES',
                customer_refund: 'RIM',
            };
            return abbrs[installment] || installment;
        };

        const getStatusLabel = (status, transactionType) => {
            // For purchase and intermediation (dare/costi), show only to_pay/paid states
            // For sale (avere/ricavi), show only to_collect/collected states
            const labels = {
                to_pay: 'Da Pagare',
                paid: 'Pagato',
                to_collect: 'Da Incassare',
                collected: 'Incassato',
                suspended: 'Sospeso',
                cancelled: 'Annullato',
            };
            return labels[status] || status;
        };

        const getStatusAbbr = (status, transactionType) => {
            // For purchase and intermediation (dare/costi), show only to_pay/paid states
            // For sale (avere/ricavi), show only to_collect/collected states
            const abbrs = {
                to_pay: 'DP',
                paid: 'PAG',
                to_collect: 'DI',
                collected: 'INC',
                suspended: 'SOS',
                cancelled: 'ANN',
            };
            return abbrs[status] || status;
        };

        const getStatusBadgeClass = (status) => {
            const classes = {
                to_pay: 'badge bg-warning-subtle text-warning',
                paid: 'badge bg-success-subtle text-success',
                to_collect: 'badge bg-info-subtle text-info',
                collected: 'badge bg-success-subtle text-success',
                suspended: 'badge bg-secondary-subtle text-secondary',
                cancelled: 'badge bg-danger-subtle text-danger',
            };
            return classes[status] || 'badge bg-secondary-subtle text-secondary';
        };

        const getDueDateClass = (transaction) => {
            if (!transaction.document_due_date) return '';

            const dueDate = moment(transaction.document_due_date);
            const today = moment();

            // Se già pagato/incassato, non evidenziare
            if (['paid', 'collected', 'cancelled'].includes(transaction.status)) {
                return '';
            }

            // Se scaduto
            if (dueDate.isBefore(today, 'day')) {
                return 'text-danger fw-bold';
            }

            // Se scade entro 7 giorni
            if (dueDate.diff(today, 'days') <= 7) {
                return 'text-warning fw-bold';
            }

            return '';
        };

        const formatAmount = (amount) => {
            return new Intl.NumberFormat('it-IT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Math.abs(amount));
        };

        onMounted(async () => {
            await loadUser();
            if (isSuperAdmin.value) {
                await loadCompanies();
            }
            await loadServices();
            await loadAccountingEntries();
            await loadCounterparts();
            await loadTransactions();
        });

        return {
            transactions,
            companies,
            services,
            counterparts,
            accountingEntries,
            loading,
            showFilters,
            showSummary,
            filters,
            pagination,
            perPage,
            sortField,
            sortDirection,
            summary,
            user,
            isSuperAdmin,
            canDelete,
            hasActiveFilters,
            activeFiltersCount,
            visiblePages,
            selectedTransactions,
            isAllSelected,
            loadTransactions,
            sortBy,
            goToPage,
            changePerPage,
            resetFilters,
            toggleSelectAll,
            deleteTransaction,
            deleteSelectedTransactions,
            formatDate,
            formatAmount,
            getTransactionTypeLabel,
            getTransactionTypeAbbr,
            getTransactionTypeBadgeClass,
            getInstallmentLabel,
            getInstallmentAbbr,
            getStatusLabel,
            getStatusAbbr,
            getStatusBadgeClass,
            getDueDateClass,
            route: window.route,
        };
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
    user-select: none;
}

.cursor-pointer:hover {
    background-color: rgba(0, 0, 0, 0.05);
}
</style>
