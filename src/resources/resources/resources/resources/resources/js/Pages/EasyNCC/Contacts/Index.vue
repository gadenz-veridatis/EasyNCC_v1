<template>
    <Layout>
        <PageHeader title="Contatti" pageTitle="Contatti" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-contacts-line me-2"></i>Anagrafica Contatti
                        </h5>
                        <button class="btn btn-sm btn-primary" @click="openCreateModal">
                            <i class="ri-add-line me-1"></i>Nuovo Contatto
                        </button>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Search -->
                        <BRow class="mb-3">
                            <BCol md="4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                                    <input v-model="searchQuery" type="text" class="form-control" placeholder="Cerca per nome, email, telefono..." @input="debouncedLoad" />
                                </div>
                            </BCol>
                            <BCol md="2">
                                <select v-model="perPage" class="form-select" @change="loadContacts">
                                    <option :value="10">10 per pagina</option>
                                    <option :value="20">20 per pagina</option>
                                    <option :value="50">50 per pagina</option>
                                </select>
                            </BCol>
                        </BRow>

                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>

                        <!-- Success/Error messages -->
                        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
                            {{ successMessage }}
                            <button type="button" class="btn-close" @click="successMessage = ''"></button>
                        </div>

                        <!-- Table -->
                        <div v-if="!loading" class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="cursor-pointer" @click="sort('name')">
                                            Nome
                                            <i v-if="sortBy === 'name'" :class="sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                                        </th>
                                        <th class="cursor-pointer" @click="sort('email')">
                                            Email
                                            <i v-if="sortBy === 'email'" :class="sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                                        </th>
                                        <th>Telefono</th>
                                        <th class="text-center">Preventivi</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="contacts.length === 0">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Nessun contatto trovato.
                                        </td>
                                    </tr>
                                    <tr v-for="contact in contacts" :key="contact.id">
                                        <td class="fw-medium">{{ contact.name }}</td>
                                        <td>
                                            <a v-if="contact.email" :href="'mailto:' + contact.email" class="text-muted">
                                                {{ contact.email }}
                                            </a>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="contact.phone">{{ contact.phone }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="text-center">
                                            <a
                                                v-if="contact.quotes_count > 0"
                                                :href="`/easyncc/quotes?contact_id=${contact.id}&contact_name=${encodeURIComponent(contact.name)}`"
                                                class="badge bg-primary-subtle text-primary text-decoration-none"
                                                title="Vedi preventivi"
                                            >
                                                {{ contact.quotes_count }}
                                            </a>
                                            <span v-else class="badge bg-secondary-subtle text-secondary">0</span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-soft-primary me-1" @click="openEditModal(contact)" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteContact(contact)" title="Elimina">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="meta.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">{{ meta.total }} contatti totali</small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(meta.current_page - 1)">
                                            <i class="ri-arrow-left-s-line"></i>
                                        </a>
                                    </li>
                                    <li v-for="page in paginationPages" :key="page" class="page-item" :class="{ active: page === meta.current_page }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
                                        <a class="page-link" href="#" @click.prevent="goToPage(meta.current_page + 1)">
                                            <i class="ri-arrow-right-s-line"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Create/Edit Modal -->
        <BModal v-model="showModal" :title="editingContact ? 'Modifica Contatto' : 'Nuovo Contatto'" hide-footer size="md">
            <div v-if="modalErrors.length > 0" class="alert alert-danger">
                <ul class="mb-0">
                    <li v-for="(err, i) in modalErrors" :key="i">{{ err }}</li>
                </ul>
            </div>
            <div class="mb-3">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input v-model="modalForm.name" type="text" class="form-control" placeholder="Nome completo" />
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input v-model="modalForm.email" type="email" class="form-control" placeholder="email@esempio.it" />
            </div>
            <div class="mb-3">
                <label class="form-label">Telefono</label>
                <input v-model="modalForm.phone" type="text" class="form-control" placeholder="+39 333..." />
            </div>
            <div class="mb-3">
                <label class="form-label">Note</label>
                <textarea v-model="modalForm.notes" class="form-control" rows="2" placeholder="Note aggiuntive"></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <BButton variant="light" @click="showModal = false">Annulla</BButton>
                <BButton variant="primary" @click="saveContact" :disabled="modalSaving">
                    <span v-if="modalSaving" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ri-save-line me-1"></i>
                    {{ editingContact ? 'Aggiorna' : 'Crea' }}
                </BButton>
            </div>
        </BModal>
    </Layout>
</template>

<script>
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";

export default {
    components: { Layout, PageHeader },
    data() {
        return {
            loading: false,
            contacts: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            searchQuery: '',
            sortBy: 'name',
            sortDir: 'asc',
            perPage: 20,
            searchTimeout: null,
            successMessage: '',
            // Modal
            showModal: false,
            modalSaving: false,
            modalErrors: [],
            editingContact: null,
            modalForm: { name: '', email: '', phone: '', notes: '' },
        };
    },
    computed: {
        paginationPages() {
            const pages = [];
            const start = Math.max(1, this.meta.current_page - 2);
            const end = Math.min(this.meta.last_page, this.meta.current_page + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
    },
    mounted() {
        this.loadContacts();
    },
    methods: {
        async loadContacts(page = 1) {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/contacts', {
                    params: {
                        search: this.searchQuery || undefined,
                        sort_by: this.sortBy,
                        sort_order: this.sortDir,
                        per_page: this.perPage,
                        page,
                    },
                });
                this.contacts = data.data || [];
                this.meta = data.meta || { current_page: 1, last_page: 1, total: 0 };
            } catch (e) {
                console.error('Error loading contacts:', e);
            } finally {
                this.loading = false;
            }
        },
        debouncedLoad() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.loadContacts(), 300);
        },
        sort(field) {
            if (this.sortBy === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortDir = 'asc';
            }
            this.loadContacts();
        },
        goToPage(page) {
            if (page >= 1 && page <= this.meta.last_page) {
                this.loadContacts(page);
            }
        },
        openCreateModal() {
            this.editingContact = null;
            this.modalForm = { name: '', email: '', phone: '', notes: '' };
            this.modalErrors = [];
            this.showModal = true;
        },
        openEditModal(contact) {
            this.editingContact = contact;
            this.modalForm = {
                name: contact.name,
                email: contact.email || '',
                phone: contact.phone || '',
                notes: contact.notes || '',
            };
            this.modalErrors = [];
            this.showModal = true;
        },
        async saveContact() {
            this.modalSaving = true;
            this.modalErrors = [];
            try {
                if (this.editingContact) {
                    await axios.put(`/api/contacts/${this.editingContact.id}`, this.modalForm);
                    this.successMessage = 'Contatto aggiornato con successo';
                } else {
                    await axios.post('/api/contacts', this.modalForm);
                    this.successMessage = 'Contatto creato con successo';
                }
                this.showModal = false;
                await this.loadContacts(this.meta.current_page);
            } catch (error) {
                if (error.response?.status === 422) {
                    this.modalErrors = Object.values(error.response.data.errors || {}).flat();
                } else {
                    this.modalErrors = ['Errore nel salvataggio'];
                }
            } finally {
                this.modalSaving = false;
            }
        },
        async deleteContact(contact) {
            if (!confirm(`Eliminare il contatto "${contact.name}"?`)) return;
            try {
                await axios.delete(`/api/contacts/${contact.id}`);
                this.successMessage = 'Contatto eliminato con successo';
                await this.loadContacts(this.meta.current_page);
            } catch (e) {
                alert(e.response?.data?.message || 'Errore nella cancellazione');
            }
        },
    },
};
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: rgba(var(--bs-primary-rgb), 0.05); }
</style>
