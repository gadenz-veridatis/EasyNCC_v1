<template>
    <Head title="Preventivi" />

    <Layout>
        <PageHeader title="Preventivi" pageTitle="Servizi" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-list-3-line me-2"></i>Lista Preventivi
                        </h5>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="showFilters ? 'btn-primary' : 'btn-outline-primary'"
                                @click="showFilters = !showFilters"
                            >
                                <i class="ri-filter-3-line me-1"></i>Filtri
                                <span v-if="activeFiltersCount > 0" class="badge bg-white text-primary ms-1">{{ activeFiltersCount }}</span>
                            </button>
                        </div>
                    </BCardHeader>

                    <BCardBody>
                        <!-- Filters -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <fieldset>
                                <legend class="fs-6 fw-semibold">Filtri di Ricerca</legend>
                                <BRow>
                                    <BCol md="3" class="mb-2">
                                        <label class="form-label small">Cerca</label>
                                        <input
                                            v-model="filters.search"
                                            type="text"
                                            class="form-control form-control-sm"
                                            placeholder="Cliente, destinazione..."
                                            @input="loadQuotes(1)"
                                        />
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Tipo Servizio</label>
                                        <select v-model="filters.service_type" class="form-select form-select-sm" @change="loadQuotes(1)">
                                            <option value="">Tutti</option>
                                            <option value="TRF">TRF</option>
                                            <option value="TOUR HD">TOUR HD</option>
                                            <option value="TOUR FD">TOUR FD</option>
                                            <option value="TOUR FD+">TOUR FD+</option>
                                        </select>
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Stagionalità</label>
                                        <select v-model="filters.seasonality" class="form-select form-select-sm" @change="loadQuotes(1)">
                                            <option value="">Tutte</option>
                                            <option value="low">Low</option>
                                            <option value="average">Average</option>
                                            <option value="high">High</option>
                                        </select>
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Data Da</label>
                                        <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="loadQuotes(1)" />
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Data A</label>
                                        <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="loadQuotes(1)" />
                                    </BCol>
                                    <BCol md="1" class="mb-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                    </BCol>
                                </BRow>
                            </fieldset>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div></div>
                            <Link href="/easyncc/quotes/create" class="btn btn-sm btn-success">
                                <i class="ri-add-line me-1"></i>Nuovo Preventivo
                            </Link>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="quotes.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th role="button" @click="sortBy('id')" class="text-nowrap">
                                            # <i :class="sortIcon('id')"></i>
                                        </th>
                                        <th role="button" @click="sortBy('service_date')" class="text-nowrap">
                                            Data <i :class="sortIcon('service_date')"></i>
                                        </th>
                                        <th role="button" @click="sortBy('client_name')" class="text-nowrap">
                                            Cliente <i :class="sortIcon('client_name')"></i>
                                        </th>
                                        <th>Destinazione</th>
                                        <th>Tipo</th>
                                        <th class="text-end text-nowrap" role="button" @click="sortBy('taxable_price_rounded')">
                                            Imponibile <i :class="sortIcon('taxable_price_rounded')"></i>
                                        </th>
                                        <th class="text-end text-nowrap" role="button" @click="sortBy('final_price_rounded')">
                                            Prezzo Finale <i :class="sortIcon('final_price_rounded')"></i>
                                        </th>
                                        <th class="text-end">Prezzo Cliente</th>
                                        <th>Creato da</th>
                                        <th style="width: 120px">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="quote in quotes" :key="quote.id">
                                        <td>{{ quote.id }}</td>
                                        <td>{{ formatDate(quote.service_date) }}</td>
                                        <td>{{ quote.client_name || '-' }}</td>
                                        <td>{{ quote.destination_name || '-' }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ quote.service_type || '-' }}</span>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(quote.taxable_price_rounded) }}</td>
                                        <td class="text-end fw-semibold">{{ formatCurrency(quote.final_price_rounded) }}</td>
                                        <td class="text-end">
                                            <span :class="quote.client_price < quote.final_price_rounded ? 'text-success' : ''">
                                                {{ formatCurrency(quote.client_price) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ quote.creator?.name }} {{ quote.creator?.surname }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <Link :href="`/easyncc/quotes/${quote.id}`" class="btn btn-sm btn-outline-info" title="Visualizza">
                                                    <i class="ri-eye-line"></i>
                                                </Link>
                                                <Link :href="`/easyncc/quotes/${quote.id}/edit`" class="btn btn-sm btn-outline-primary" title="Modifica">
                                                    <i class="ri-pencil-line"></i>
                                                </Link>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Elimina"
                                                    @click="deleteQuote(quote.id)"
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
                            <p class="text-muted mt-2">Nessun preventivo trovato</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="pagination.total > 0" class="row align-items-center mt-3">
                            <div class="col-sm-4">
                                <small class="text-muted">
                                    {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} -
                                    {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
                                    di {{ pagination.total }} risultati
                                </small>
                            </div>
                            <div class="col-sm-4 text-center">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                                        <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">
                                                <i class="ri-arrow-left-s-line"></i>
                                            </a>
                                        </li>
                                        <li
                                            v-for="page in visiblePages"
                                            :key="page"
                                            class="page-item"
                                            :class="{ active: page === pagination.current_page }"
                                        >
                                            <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">
                                                <i class="ri-arrow-right-s-line"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            <div class="col-sm-4 text-end">
                                <select v-model="perPage" class="form-select form-select-sm d-inline-block" style="width: auto" @change="loadQuotes(1)">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import moment from "moment";

export default {
    components: { Head, Link, Layout, PageHeader },
    data() {
        return {
            loading: true,
            quotes: [],
            showFilters: false,
            perPage: 10,
            pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
            sortField: 'created_at',
            sortDirection: 'desc',
            filters: {
                search: '',
                service_type: '',
                seasonality: '',
                date_from: '',
                date_to: '',
            },
        };
    },
    computed: {
        activeFiltersCount() {
            return Object.values(this.filters).filter(v => v !== '').length;
        },
        visiblePages() {
            const pages = [];
            const total = this.pagination.last_page;
            const current = this.pagination.current_page;
            let start = Math.max(1, current - 2);
            let end = Math.min(total, start + 4);
            start = Math.max(1, end - 4);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
    },
    async mounted() {
        await this.loadQuotes();
    },
    methods: {
        async loadQuotes(page = 1) {
            this.loading = true;
            try {
                const params = {
                    page,
                    per_page: this.perPage,
                    sort_by: this.sortField,
                    sort_order: this.sortDirection,
                    ...this.filters,
                };
                const response = await axios.get('/api/quotes', { params });
                if (response.data.success) {
                    this.quotes = response.data.data;
                    this.pagination = response.data.meta;
                }
            } catch (error) {
                console.error('Error loading quotes:', error);
            } finally {
                this.loading = false;
            }
        },
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.loadQuotes(1);
        },
        sortIcon(field) {
            if (this.sortField !== field) return 'ri-arrow-up-down-line text-muted';
            return this.sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line';
        },
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadQuotes(page);
            }
        },
        resetFilters() {
            this.filters = { search: '', service_type: '', seasonality: '', date_from: '', date_to: '' };
            this.loadQuotes(1);
        },
        async deleteQuote(id) {
            if (!confirm('Eliminare questo preventivo?')) return;
            try {
                await axios.delete(`/api/quotes/${id}`);
                this.loadQuotes(this.pagination.current_page);
            } catch (error) {
                console.error('Error deleting quote:', error);
                alert('Errore durante l\'eliminazione del preventivo');
            }
        },
        formatDate(date) {
            return date ? moment(date).format('DD/MM/YYYY') : '-';
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '-';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
    },
};
</script>
