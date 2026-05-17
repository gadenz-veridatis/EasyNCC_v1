<template>
    <Head title="Richieste" />

    <Layout>
        <PageHeader title="Richieste" pageTitle="Servizi" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-mail-line me-2"></i>Inbox Richieste
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
                                            placeholder="Cliente, pickup, dropoff..."
                                            @input="debouncedLoad"
                                        />
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Stato</label>
                                        <select v-model="filters.stato" class="form-select form-select-sm" @change="loadRichieste(1)">
                                            <option value="">Tutti</option>
                                            <option value="nuova">Nuova</option>
                                            <option value="in_lavorazione">In Lavorazione</option>
                                            <option value="preventivata">Preventivata</option>
                                            <option value="confermata">Confermata</option>
                                            <option value="annullata">Annullata</option>
                                            <option value="sospesa">Sospesa</option>
                                        </select>
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Fonte</label>
                                        <select v-model="filters.fonte" class="form-select form-select-sm" @change="loadRichieste(1)">
                                            <option value="">Tutte</option>
                                            <option value="email">Email</option>
                                            <option value="web_form">Web Form</option>
                                            <option value="telefono">Telefono</option>
                                            <option value="manuale">Manuale</option>
                                        </select>
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Data Da</label>
                                        <input v-model="filters.date_from" type="date" class="form-control form-control-sm" @change="loadRichieste(1)" />
                                    </BCol>
                                    <BCol md="2" class="mb-2">
                                        <label class="form-label small">Data A</label>
                                        <input v-model="filters.date_to" type="date" class="form-control form-control-sm" @change="loadRichieste(1)" />
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
                            <div class="d-flex gap-2">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    type="button"
                                    class="btn btn-sm"
                                    :class="activeTab === tab.key ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="setTab(tab.key)"
                                >
                                    {{ tab.label }}
                                    <span v-if="tab.count > 0" class="badge bg-white text-primary ms-1">{{ tab.count }}</span>
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-info" @click="fetchEmails" :disabled="fetchingEmails">
                                    <span v-if="fetchingEmails" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="ri-mail-download-line me-1"></i>Controlla Email
                                </button>
                                <button class="btn btn-sm btn-success" @click="showCreateModal = true">
                                    <i class="ri-add-line me-1"></i>Nuova Richiesta
                                </button>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="richieste.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th role="button" @click="sortBy('data_ricezione')" class="text-nowrap">
                                            Data <i :class="sortIcon('data_ricezione')"></i>
                                        </th>
                                        <th>Cliente</th>
                                        <th>Fonte</th>
                                        <th class="text-center">Righe</th>
                                        <th>Prima data servizio</th>
                                        <th>Stato</th>
                                        <th>Ultimo Messaggio</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in richieste" :key="r.id" style="cursor: pointer;" @click="goToDetail(r.id)"
                                        :class="{ 'fw-bold': r.has_unread }">
                                        <td>{{ formatDateTime(r.data_ricezione) }}</td>
                                        <td>
                                            <div :class="r.has_unread ? 'fw-bold' : 'fw-semibold'">
                                                {{ r.contact?.name || '-' }}
                                                <span v-if="r.has_unread" class="badge bg-danger rounded-pill ms-1" style="font-size: 0.65em;">nuovo</span>
                                            </div>
                                            <small class="text-muted fw-normal">{{ r.contact?.email || '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge" :class="fonteBadgeClass(r.fonte)">
                                                <i :class="fonteIcon(r.fonte)" class="me-1"></i>{{ fonteLabel(r.fonte) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ r.righe_count || 0 }}</td>
                                        <td>{{ firstServiceDate(r) }}</td>
                                        <td>
                                            <span class="badge" :class="statoBadgeClass(r.stato)">
                                                {{ statoLabel(r.stato) }}
                                            </span>
                                            <span v-if="r.stato === 'preventivata' && activeQuoteStatus(r)" class="badge bg-light text-dark ms-1" style="font-size: 0.7em;">
                                                {{ activeQuoteStatus(r) }}
                                            </span>
                                        </td>
                                        <td class="fw-normal">
                                            <small>{{ r.ultimo_messaggio_inbound_at ? formatDateTime(r.ultimo_messaggio_inbound_at) : '-' }}</small>
                                        </td>
                                        <td class="text-center" @click.stop>
                                            <Link :href="`/easyncc/richieste/${r.id}`" class="btn btn-sm btn-soft-primary me-1">
                                                <i class="ri-eye-line"></i>
                                            </Link>
                                            <button class="btn btn-sm btn-soft-danger" @click="confirmDelete(r)">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="ri-mail-open-line fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Nessuna richiesta trovata</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="!loading && pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                {{ pagination.total }} risultati — Pagina {{ pagination.current_page }} di {{ pagination.last_page }}
                            </small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">«</a>
                                    </li>
                                    <li v-for="p in visiblePages" :key="p" class="page-item" :class="{ active: p === pagination.current_page }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">»</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Create Modal -->
        <BModal v-model="showCreateModal" title="Nuova Richiesta" size="lg" hide-footer>
            <BRow>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Contatto <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input
                            v-model="contactSearch"
                            type="text"
                            class="form-control"
                            placeholder="Cerca contatto per nome o email..."
                            @input="onContactSearch"
                            @focus="showContactResults = contactSearchResults.length > 0"
                        />
                        <div v-if="newRichiesta.contact_id" class="mt-1">
                            <span class="badge bg-primary-subtle text-primary">
                                {{ selectedContactName }}
                                <i class="ri-close-line ms-1" role="button" @click="clearSelectedContact"></i>
                            </span>
                        </div>
                        <div v-if="showContactResults && contactSearchResults.length > 0" class="position-absolute bg-white border rounded shadow-sm w-100 mt-1" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                            <div
                                v-for="c in contactSearchResults"
                                :key="c.id"
                                class="px-3 py-2 border-bottom"
                                style="cursor: pointer;"
                                @mousedown.prevent="selectContact(c)"
                            >
                                <div class="fw-semibold">{{ c.name }}</div>
                                <small class="text-muted">{{ c.email || '' }}</small>
                            </div>
                        </div>
                    </div>
                </BCol>
                <BCol md="3" class="mb-3">
                    <label class="form-label">Fonte <span class="text-danger">*</span></label>
                    <select v-model="newRichiesta.fonte" class="form-select">
                        <option value="email">Email</option>
                        <option value="web_form">Web Form</option>
                        <option value="telefono">Telefono</option>
                        <option value="manuale">Manuale</option>
                    </select>
                </BCol>
                <BCol md="3" class="mb-3">
                    <label class="form-label">Data Ricezione</label>
                    <input v-model="newRichiesta.data_ricezione" type="datetime-local" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea v-model="newRichiesta.note" class="form-control" rows="3"></textarea>
                </BCol>
            </BRow>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-light" @click="showCreateModal = false">Annulla</button>
                <button class="btn btn-primary" :disabled="!newRichiesta.contact_id" @click="createRichiesta">
                    <i class="ri-add-line me-1"></i>Crea Richiesta
                </button>
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
import { useNotify } from '@/composables/useNotify.js';

const notify = useNotify();

const STATO_LABELS = {
    nuova: 'Nuova',
    in_lavorazione: 'In Lavorazione',
    preventivata: 'Preventivata',
    confermata: 'Confermata',
    annullata: 'Annullata',
    sospesa: 'Sospesa',
};

const STATO_BADGE = {
    nuova: 'bg-info-subtle text-info',
    in_lavorazione: 'bg-warning-subtle text-warning',
    preventivata: 'bg-primary-subtle text-primary',
    confermata: 'bg-success-subtle text-success',
    annullata: 'bg-danger-subtle text-danger',
    sospesa: 'bg-secondary-subtle text-secondary',
};

const FONTE_LABELS = { email: 'Email', web_form: 'Web Form', telefono: 'Telefono', manuale: 'Manuale' };
const FONTE_ICONS = { email: 'ri-mail-line', web_form: 'ri-global-line', telefono: 'ri-phone-line', manuale: 'ri-edit-line' };
const FONTE_BADGE = { email: 'bg-primary-subtle text-primary', web_form: 'bg-info-subtle text-info', telefono: 'bg-success-subtle text-success', manuale: 'bg-secondary-subtle text-secondary' };

export default {
    components: { Head, Link, Layout, PageHeader },
    data() {
        return {
            loading: true,
            richieste: [],
            showFilters: false,
            activeTab: 'da_gestire',
            pagination: { current_page: 1, last_page: 1, total: 0 },
            sortField: 'data_ricezione',
            sortDirection: 'desc',
            filters: { search: '', stato: '', fonte: '', date_from: '', date_to: '' },
            tabs: [
                { key: 'da_gestire', label: 'Da Gestire', count: 0 },
                { key: 'in_attesa', label: 'In Attesa', count: 0 },
                { key: 'confermate', label: 'Confermate', count: 0 },
                { key: 'tutte', label: 'Tutte', count: 0 },
            ],
            // Create modal
            showCreateModal: false,
            newRichiesta: { contact_id: null, fonte: 'manuale', data_ricezione: '', note: '' },
            contactSearch: '',
            contactSearchResults: [],
            showContactResults: false,
            selectedContactName: '',
            searchTimeout: null,
            fetchingEmails: false,
        };
    },
    computed: {
        activeFiltersCount() {
            return Object.values(this.filters).filter(v => v !== '' && v !== null).length;
        },
        visiblePages() {
            const pages = [];
            const c = this.pagination.current_page;
            const l = this.pagination.last_page;
            for (let i = Math.max(1, c - 2); i <= Math.min(l, c + 2); i++) {
                pages.push(i);
            }
            return pages;
        },
    },
    async mounted() {
        await this.loadRichieste(1);
    },
    methods: {
        async loadRichieste(page = 1) {
            this.loading = true;
            try {
                const params = {
                    page,
                    per_page: 20,
                    sort_by: this.sortField,
                    sort_order: this.sortDirection,
                    ...this.filters,
                };

                // Apply tab filters
                if (this.activeTab === 'da_gestire') {
                    params.stato = params.stato || '';
                    if (!params.stato) params.stato = 'nuova,in_lavorazione';
                } else if (this.activeTab === 'in_attesa') {
                    params.stato = 'preventivata';
                } else if (this.activeTab === 'confermate') {
                    params.stato = 'confermata';
                }

                const response = await axios.get('/api/richieste', { params });
                this.richieste = response.data.data || [];
                this.pagination = {
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    total: response.data.total || 0,
                };

                // Update tab counts
                await this.loadTabCounts();
            } catch (error) {
                console.error('Error loading richieste:', error);
            } finally {
                this.loading = false;
            }
        },
        async loadTabCounts() {
            try {
                const [daGestire, inAttesa, confermate, tutte] = await Promise.all([
                    axios.get('/api/richieste', { params: { stato: 'nuova,in_lavorazione', per_page: 1 } }),
                    axios.get('/api/richieste', { params: { stato: 'preventivata', per_page: 1 } }),
                    axios.get('/api/richieste', { params: { stato: 'confermata', per_page: 1 } }),
                    axios.get('/api/richieste', { params: { per_page: 1 } }),
                ]);
                this.tabs[0].count = daGestire.data.total || 0;
                this.tabs[1].count = inAttesa.data.total || 0;
                this.tabs[2].count = confermate.data.total || 0;
                this.tabs[3].count = tutte.data.total || 0;
            } catch (e) { /* silently fail */ }
        },
        setTab(key) {
            this.activeTab = key;
            this.filters.stato = '';
            this.loadRichieste(1);
        },
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }
            this.loadRichieste(1);
        },
        sortIcon(field) {
            if (this.sortField !== field) return 'ri-arrow-up-down-line text-muted';
            return this.sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line';
        },
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadRichieste(page);
            }
        },
        goToDetail(id) {
            this.$inertia.visit(`/easyncc/richieste/${id}`);
        },
        resetFilters() {
            this.filters = { search: '', stato: '', fonte: '', date_from: '', date_to: '' };
            this.loadRichieste(1);
        },
        debouncedLoad() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.loadRichieste(1), 300);
        },
        formatDateTime(date) { return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-'; },
        statoLabel(stato) { return STATO_LABELS[stato] || stato; },
        statoBadgeClass(stato) { return STATO_BADGE[stato] || 'bg-secondary'; },
        fonteLabel(fonte) { return FONTE_LABELS[fonte] || fonte; },
        fonteIcon(fonte) { return FONTE_ICONS[fonte] || 'ri-question-line'; },
        fonteBadgeClass(fonte) { return FONTE_BADGE[fonte] || 'bg-secondary-subtle text-secondary'; },
        firstServiceDate(r) {
            if (r.righe && r.righe.length > 0) {
                return moment(r.righe[0].data_servizio).format('DD/MM/YYYY');
            }
            return '-';
        },
        // Contact search
        async onContactSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(async () => {
                if (this.contactSearch.length < 2) {
                    this.contactSearchResults = [];
                    return;
                }
                try {
                    const res = await axios.get('/api/contacts/search', { params: { q: this.contactSearch } });
                    this.contactSearchResults = res.data.data || res.data || [];
                    this.showContactResults = true;
                } catch (e) { this.contactSearchResults = []; }
            }, 300);
        },
        selectContact(c) {
            this.newRichiesta.contact_id = c.id;
            this.selectedContactName = c.name;
            this.contactSearch = c.name;
            this.showContactResults = false;
        },
        clearSelectedContact() {
            this.newRichiesta.contact_id = null;
            this.selectedContactName = '';
            this.contactSearch = '';
        },
        async createRichiesta() {
            try {
                const res = await axios.post('/api/richieste', this.newRichiesta);
                if (res.data.success) {
                    this.showCreateModal = false;
                    this.$inertia.visit(`/easyncc/richieste/${res.data.data.id}`);
                }
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nella creazione');
            }
        },
        async fetchEmails() {
            this.fetchingEmails = true;
            try {
                const res = await axios.post('/api/gmail-accounts/fetch-all');
                const total = res.data.total_processed || 0;
                if (total > 0) {
                    notify.success(`${total} email trovate e processate.`);
                    await this.loadRichieste(1);
                } else {
                    notify.warning('Nessuna nuova email trovata con la label configurata.');
                }
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nel controllo email');
            } finally {
                this.fetchingEmails = false;
            }
        },
        activeQuoteStatus(r) {
            const quote = r.quotes?.find(q => q.is_active_version);
            if (!quote) return null;
            const labels = { draft: 'Bozza', in_approvazione: 'In Approv.', approved: 'Approvato', sent: 'Inviato', deposit_received: 'Pagato', scaduto: 'Scaduto' };
            return labels[quote.status] || quote.status;
        },
        async confirmDelete(r) {
            const confirmed = await notify.confirm('Eliminare Richiesta', `Eliminare la richiesta da ${r.contact?.name}?`);
            if (confirmed) {
                try {
                    await axios.delete(`/api/richieste/${r.id}`);
                    notify.success('Richiesta eliminata');
                    this.loadRichieste(this.pagination.current_page);
                } catch (error) {
                    notify.error(error.response?.data?.message || 'Errore nell\'eliminazione');
                }
            }
        },
    },
};
</script>
