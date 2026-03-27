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
                                        <label class="form-label small">Stato</label>
                                        <select v-model="filters.status" class="form-select form-select-sm" @change="loadQuotes(1)">
                                            <option value="">Tutti</option>
                                            <option value="draft">Bozza</option>
                                            <option value="approved">Approvato</option>
                                            <option value="sent">Inviato</option>
                                            <option value="deposit_received">Acconto Ricevuto</option>
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
                                    <BCol md="3" class="mb-2">
                                        <label class="form-label small">Contatto</label>
                                        <div class="position-relative">
                                            <input
                                                v-model="contactSearch"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="Cerca contatto..."
                                                @input="onContactSearch"
                                                @focus="showContactResults = contactSearchResults.length > 0"
                                            />
                                            <div v-if="filters.contact_id" class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ contactFilterName }}
                                                    <i class="ri-close-line ms-1" role="button" @click="clearContactFilter"></i>
                                                </span>
                                            </div>
                                            <div v-if="showContactResults && contactSearchResults.length > 0" class="position-absolute bg-white border rounded shadow-sm w-100 mt-1" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                                <div
                                                    v-for="c in contactSearchResults"
                                                    :key="c.id"
                                                    class="px-3 py-2 border-bottom cursor-pointer"
                                                    style="cursor: pointer;"
                                                    @mousedown.prevent="selectContactFilter(c)"
                                                >
                                                    <div class="fw-semibold">{{ c.name }}</div>
                                                    <small class="text-muted">{{ c.email || '' }} {{ c.phone || '' }}</small>
                                                </div>
                                            </div>
                                        </div>
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
                                        <th class="text-center">Ver.</th>
                                        <th role="button" @click="sortBy('service_date')" class="text-nowrap">
                                            Data <i :class="sortIcon('service_date')"></i>
                                        </th>
                                        <th role="button" @click="sortBy('client_name')" class="text-nowrap">
                                            Cliente <i :class="sortIcon('client_name')"></i>
                                        </th>
                                        <th>Destinazione</th>
                                        <th>Tipo</th>
                                        <th>Stato</th>
                                        <th class="text-end text-nowrap" role="button" @click="sortBy('taxable_price_rounded')">
                                            Imponibile <i :class="sortIcon('taxable_price_rounded')"></i>
                                        </th>
                                        <th class="text-end">Prezzo Cliente</th>
                                        <th class="text-end">Deposito</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Creato</th>
                                        <th style="width: 160px">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="quote in quotes" :key="quote.id">
                                        <td>{{ quote.id }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary">v{{ quote.version || 1 }}</span>
                                        </td>
                                        <td>{{ formatDate(quote.service_date) }}</td>
                                        <td>{{ quote.client_name || '-' }}</td>
                                        <td>
                                            <template v-if="quote.items && quote.items.length > 0">
                                                {{ quote.items[0]?.destination_name || '-' }}
                                                <small v-if="quote.items.length > 1" class="text-muted d-block">(+{{ quote.items.length - 1 }} servizi)</small>
                                            </template>
                                            <template v-else>{{ quote.destination_name || '-' }}</template>
                                        </td>
                                        <td>
                                            <template v-if="quote.items && quote.items.length > 0">
                                                <span v-for="(item, idx) in quote.items" :key="idx" class="badge me-1" :class="serviceTypeBadgeClass(item.service_type, 'bg-info-subtle text-info')">{{ item.service_type || '-' }}</span>
                                            </template>
                                            <span v-else class="badge" :class="serviceTypeBadgeClass(quote.service_type, 'bg-info-subtle text-info')">{{ quote.service_type || '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="`bg-${getStatusColor(quote.status)}-subtle text-${getStatusColor(quote.status)}`">
                                                {{ getStatusLabel(quote.status) }}
                                            </span>
                                            <div v-if="getStatusDate(quote)" class="mt-1">
                                                <small class="text-muted">{{ getStatusDate(quote) }}</small>
                                            </div>
                                            <button
                                                v-if="quote.rendered_body_html && (quote.status === 'approved' || quote.status === 'sent' || quote.status === 'deposit_received')"
                                                class="btn btn-link btn-sm p-0 mt-1 d-block"
                                                @click="openEmailPreview(quote)"
                                                title="Vedi email"
                                            >
                                                <i class="ri-mail-line me-1"></i><small>Vedi email</small>
                                            </button>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(quote.taxable_price_rounded) }}</td>
                                        <td class="text-end">
                                            <span :class="quote.client_price < quote.final_price_rounded ? 'text-success' : ''">
                                                {{ formatCurrency(quote.client_price) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(quote.deposit_total) }}</td>
                                        <td class="text-end">
                                            <div>{{ formatCurrency(quote.balance_taxable) }}</div>
                                            <div v-if="quote.balance_handling_fees > 0">
                                                <small class="text-muted">HF: {{ formatCurrency(quote.balance_handling_fees) }}</small>
                                            </div>
                                            <div v-if="quote.balance_card_fees > 0">
                                                <small class="text-muted">CF: {{ formatCurrency(quote.balance_card_fees) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ quote.creator?.name }} {{ quote.creator?.surname }}</small>
                                            <div><small class="text-muted">{{ formatDateTime(quote.created_at) }}</small></div>
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
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="Duplica"
                                                    @click="duplicateQuote(quote.id)"
                                                    :disabled="duplicatingId === quote.id"
                                                >
                                                    <span v-if="duplicatingId === quote.id" class="spinner-border spinner-border-sm"></span>
                                                    <i v-else class="ri-file-copy-line"></i>
                                                </button>
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
        <!-- Email Preview Modal -->
        <BModal v-model="showEmailModal" :title="emailPreviewTitle" hide-footer size="xl">
            <div v-if="emailPreviewQuote">
                <div class="mb-3">
                    <label class="form-label fw-bold">Oggetto</label>
                    <div class="form-control bg-light">{{ emailPreviewQuote.rendered_subject || '-' }}</div>
                </div>
                <div>
                    <label class="form-label fw-bold">Corpo Email</label>
                    <div class="border rounded p-3 bg-white" v-html="emailPreviewQuote.rendered_body_html"></div>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import moment from "moment";
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';

const { loadServiceTypes, serviceTypeBadgeClass } = useServiceTypeColor();

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
            duplicatingId: null,
            showEmailModal: false,
            emailPreviewQuote: null,
            filters: {
                search: '',
                service_type: '',
                status: '',
                seasonality: '',
                date_from: '',
                date_to: '',
                contact_id: '',
            },
            // Contact filter
            contactSearch: '',
            contactSearchResults: [],
            contactSearchTimeout: null,
            showContactResults: false,
            contactFilterName: '',
        };
    },
    computed: {
        emailPreviewTitle() {
            if (!this.emailPreviewQuote) return 'Email';
            const s = this.emailPreviewQuote.status;
            if (s === 'sent' || s === 'deposit_received') return 'Email Inviata';
            return 'Bozza Email';
        },
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
        // Check URL for contact_id filter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('contact_id')) {
            this.filters.contact_id = urlParams.get('contact_id');
            this.contactFilterName = urlParams.get('contact_name') || 'Contatto #' + urlParams.get('contact_id');
            this.showFilters = true;
        }
        await this.loadQuotes();
        loadServiceTypes();
    },
    methods: {
        serviceTypeBadgeClass,
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
            this.filters = { search: '', service_type: '', status: '', seasonality: '', date_from: '', date_to: '', contact_id: '' };
            this.contactSearch = '';
            this.contactFilterName = '';
            this.contactSearchResults = [];
            this.loadQuotes(1);
        },
        onContactSearch() {
            clearTimeout(this.contactSearchTimeout);
            if (!this.contactSearch || this.contactSearch.length < 2) {
                this.contactSearchResults = [];
                this.showContactResults = false;
                return;
            }
            this.contactSearchTimeout = setTimeout(async () => {
                try {
                    const { data } = await axios.get('/api/contacts/search', { params: { q: this.contactSearch } });
                    this.contactSearchResults = data.data || [];
                    this.showContactResults = true;
                } catch (e) {
                    console.error('Error searching contacts:', e);
                }
            }, 300);
        },
        selectContactFilter(contact) {
            this.filters.contact_id = contact.id;
            this.contactFilterName = contact.name;
            this.contactSearch = '';
            this.contactSearchResults = [];
            this.showContactResults = false;
            this.loadQuotes(1);
        },
        clearContactFilter() {
            this.filters.contact_id = '';
            this.contactFilterName = '';
            this.loadQuotes(1);
        },
        async duplicateQuote(id) {
            this.duplicatingId = id;
            try {
                const response = await axios.post(`/api/quotes/${id}/duplicate`);
                if (response.data.success) {
                    this.$inertia.visit(`/easyncc/quotes/${response.data.data.id}/edit`);
                }
            } catch (error) {
                console.error('Error duplicating quote:', error);
                alert(error.response?.data?.message || 'Errore durante la duplicazione del preventivo');
            } finally {
                this.duplicatingId = null;
            }
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
        openEmailPreview(quote) {
            this.emailPreviewQuote = quote;
            this.showEmailModal = true;
        },
        getStatusDate(quote) {
            if (quote.status === 'deposit_received' && quote.deposit_received_at) return moment(quote.deposit_received_at).format('DD/MM/YYYY HH:mm');
            if (quote.status === 'sent' && quote.sent_at) return moment(quote.sent_at).format('DD/MM/YYYY HH:mm');
            if (quote.status === 'approved' && quote.approved_at) return moment(quote.approved_at).format('DD/MM/YYYY HH:mm');
            return null;
        },
        formatDate(date) {
            return date ? moment(date).format('DD/MM/YYYY') : '-';
        },
        formatDateTime(date) {
            return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '-';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
        getStatusLabel(status) {
            const labels = { draft: 'Bozza', approved: 'Approvato', sent: 'Inviato', deposit_received: 'Acconto Ricevuto' };
            return labels[status] || status || 'Bozza';
        },
        getStatusColor(status) {
            const colors = { draft: 'secondary', approved: 'warning', sent: 'info', deposit_received: 'success' };
            return colors[status] || 'secondary';
        },
    },
};
</script>
