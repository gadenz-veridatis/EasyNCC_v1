<template>
    <Layout>
        <PageHeader title="Utenti Telegram" pageTitle="Telegram" />
        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="ri-telegram-line me-2"></i>Utenti Registrati al Bot
                            </h5>
                            <div class="d-flex gap-2">
                                <Link href="/easyncc/settings/telegram" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-settings-3-line me-1"></i>Impostazioni Bot
                                </Link>
                            </div>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Company Selection (super-admin only) -->
                        <BRow v-if="isSuperAdmin" class="mb-4">
                            <BCol md="12">
                                <div class="alert alert-info">
                                    <label class="form-label fw-bold">Seleziona Azienda</label>
                                    <select v-model="selectedCompanyId" class="form-select" @change="loadUsers">
                                        <option value="">Seleziona un'azienda</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </div>
                            </BCol>
                        </BRow>

                        <div v-if="selectedCompanyId">
                            <!-- Filters & Search -->
                            <BRow class="mb-3">
                                <BCol md="4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input
                                            v-model="search"
                                            type="text"
                                            class="form-control"
                                            placeholder="Cerca per nome o username..."
                                            @input="debounceSearch"
                                        />
                                    </div>
                                </BCol>
                                <BCol md="3">
                                    <select v-model="filter" class="form-select" @change="loadUsers">
                                        <option value="">Tutti</option>
                                        <option value="associated">Associati</option>
                                        <option value="not_associated">Non associati</option>
                                    </select>
                                </BCol>
                                <BCol md="5" class="text-end">
                                    <span class="badge bg-success me-2" v-if="stats.associated > 0">
                                        {{ stats.associated }} associati
                                    </span>
                                    <span class="badge bg-warning text-dark" v-if="stats.notAssociated > 0">
                                        {{ stats.notAssociated }} non associati
                                    </span>
                                </BCol>
                            </BRow>

                            <!-- Loading -->
                            <div v-if="loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                            </div>

                            <!-- Table -->
                            <div v-else-if="telegramUsers.length > 0" class="table-responsive">
                                <table class="table table-hover table-bordered align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 50px;"></th>
                                            <th>Nome Telegram</th>
                                            <th>Username</th>
                                            <th>Chat ID</th>
                                            <th>Registrazione</th>
                                            <th>Driver Associato</th>
                                            <th style="width: 100px;">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="tgUser in telegramUsers" :key="tgUser.id">
                                            <!-- Avatar -->
                                            <td class="text-center">
                                                <div
                                                    class="rounded-circle d-flex align-items-center justify-content-center"
                                                    :style="{
                                                        width: '36px',
                                                        height: '36px',
                                                        backgroundColor: tgUser.user_id ? '#198754' : '#6c757d',
                                                        color: '#fff',
                                                        fontSize: '0.9rem',
                                                        fontWeight: 'bold'
                                                    }"
                                                >
                                                    {{ getInitials(tgUser) }}
                                                </div>
                                            </td>
                                            <!-- Nome -->
                                            <td>
                                                <div class="fw-bold">
                                                    {{ tgUser.first_name || '' }} {{ tgUser.last_name || '' }}
                                                </div>
                                            </td>
                                            <!-- Username -->
                                            <td>
                                                <span v-if="tgUser.username" class="text-primary">
                                                    {{ '@' + tgUser.username }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <!-- Chat ID -->
                                            <td>
                                                <code>{{ tgUser.telegram_chat_id }}</code>
                                            </td>
                                            <!-- Data registrazione -->
                                            <td>
                                                <div class="small">{{ formatDate(tgUser.created_at) }}</div>
                                            </td>
                                            <!-- Driver Associato -->
                                            <td>
                                                <div v-if="tgUser.driver" class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-success">
                                                        <i class="ri-check-line me-1"></i>
                                                        {{ tgUser.driver.surname }} {{ tgUser.driver.name }}
                                                    </span>
                                                    <button
                                                        class="btn btn-sm btn-outline-danger p-0 px-1"
                                                        @click="removeAssociation(tgUser)"
                                                        title="Rimuovi associazione"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                                <div v-else>
                                                    <button
                                                        class="btn btn-sm btn-outline-warning"
                                                        @click="openAssociateModal(tgUser)"
                                                    >
                                                        <i class="ri-link me-1"></i>Associa Driver
                                                    </button>
                                                </div>
                                            </td>
                                            <!-- Azioni -->
                                            <td>
                                                <Link
                                                    :href="`/easyncc/telegram/chat?chat_id=${tgUser.telegram_chat_id}`"
                                                    class="btn btn-sm btn-soft-primary"
                                                    title="Apri Chat"
                                                >
                                                    <i class="ri-chat-3-line"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="text-center py-5 text-muted">
                                <i class="ri-user-unfollow-line" style="font-size: 3rem;"></i>
                                <p class="mt-2">Nessun utente Telegram registrato.</p>
                                <p class="small">I driver devono inviare <code>/start</code> al bot per registrarsi.</p>
                            </div>
                        </div>

                        <div v-else class="alert alert-warning">
                            Seleziona un'azienda per visualizzare gli utenti Telegram
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Modal Associazione Driver (Bootstrap CSS, no BS JS) -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" @click.self="closeModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ri-link me-2"></i>Associa Driver
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedTgUser" class="mb-3 p-3 bg-light rounded">
                            <div class="fw-bold">Utente Telegram:</div>
                            <div>
                                {{ selectedTgUser.first_name }} {{ selectedTgUser.last_name }}
                                <span v-if="selectedTgUser.username" class="text-primary ms-1">{{ '@' + selectedTgUser.username }}</span>
                            </div>
                        </div>

                        <div v-if="loadingDrivers" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            Caricamento driver...
                        </div>

                        <div v-else-if="availableDrivers.length > 0">
                            <label class="form-label fw-bold">Seleziona Driver</label>
                            <div class="list-group">
                                <button
                                    v-for="driver in availableDrivers"
                                    :key="driver.id"
                                    type="button"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    :class="{ 'active': selectedDriverId === driver.id }"
                                    @click="selectedDriverId = driver.id"
                                >
                                    <div>
                                        <div class="fw-bold">{{ driver.surname }} {{ driver.name }}</div>
                                        <small class="text-muted">{{ driver.email }}</small>
                                    </div>
                                    <i v-if="selectedDriverId === driver.id" class="ri-check-line fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <div v-else class="alert alert-info mb-0">
                            Tutti i driver sono già associati a utenti Telegram.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">Annulla</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!selectedDriverId || saving"
                            @click="saveAssociation"
                        >
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="ri-check-line me-1"></i>
                            Associa
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showModal" class="modal-backdrop fade show"></div>
    </Layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";

export default {
    components: {
        Link,
        Layout,
        PageHeader,
    },
    data() {
        return {
            loading: false,
            saving: false,
            loadingDrivers: false,
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            telegramUsers: [],
            search: '',
            filter: '',
            searchTimeout: null,
            // Modal
            showModal: false,
            selectedTgUser: null,
            selectedDriverId: null,
            availableDrivers: [],
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        stats() {
            const associated = this.telegramUsers.filter(u => u.user_id).length;
            return {
                associated,
                notAssociated: this.telegramUsers.length - associated,
            };
        },
    },
    async mounted() {
        await this.loadCurrentUser();
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else if (this.currentUser) {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadUsers();
        }
    },
    methods: {
        async loadCurrentUser() {
            try {
                const response = await axios.get('/api/user');
                this.currentUser = response.data;
            } catch (error) {
                console.error('Error loading current user:', error);
            }
        },
        async loadCompanies() {
            try {
                const response = await axios.get('/api/companies');
                this.companies = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },
        async loadUsers() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            try {
                const params = {
                    company_id: this.selectedCompanyId,
                };
                if (this.filter) params.filter = this.filter;
                if (this.search) params.search = this.search;

                const response = await axios.get('/api/telegram/users', { params });
                this.telegramUsers = response.data.data || [];
            } catch (error) {
                console.error('Error loading telegram users:', error);
            } finally {
                this.loading = false;
            }
        },
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadUsers();
            }, 400);
        },
        async openAssociateModal(tgUser) {
            this.selectedTgUser = tgUser;
            this.selectedDriverId = null;
            this.availableDrivers = [];
            this.showModal = true;
            document.body.classList.add('modal-open');

            this.loadingDrivers = true;
            try {
                const params = { company_id: this.selectedCompanyId };
                const response = await axios.get('/api/telegram/users/available-drivers', { params });
                this.availableDrivers = response.data.data || [];
            } catch (error) {
                console.error('Error loading available drivers:', error);
            } finally {
                this.loadingDrivers = false;
            }
        },
        closeModal() {
            this.showModal = false;
            document.body.classList.remove('modal-open');
        },
        async saveAssociation() {
            if (!this.selectedTgUser || !this.selectedDriverId) return;

            this.saving = true;
            try {
                await axios.put(
                    `/api/telegram/users/${this.selectedTgUser.id}/associate`,
                    {
                        user_id: this.selectedDriverId,
                        company_id: this.selectedCompanyId,
                    }
                );
                this.showModal = false;
                await this.loadUsers();
            } catch (error) {
                console.error('Error associating driver:', error);
                alert('Errore nell\'associazione del driver');
            } finally {
                this.saving = false;
            }
        },
        async removeAssociation(tgUser) {
            if (!confirm(`Rimuovere l'associazione di ${tgUser.first_name || ''} ${tgUser.last_name || ''} con il driver ${tgUser.driver?.surname || ''} ${tgUser.driver?.name || ''}?`)) {
                return;
            }

            try {
                await axios.put(
                    `/api/telegram/users/${tgUser.id}/associate`,
                    {
                        user_id: null,
                        company_id: this.selectedCompanyId,
                    }
                );
                await this.loadUsers();
            } catch (error) {
                console.error('Error removing association:', error);
                alert('Errore nella rimozione dell\'associazione');
            }
        },
        getInitials(tgUser) {
            const first = (tgUser.first_name || '')[0] || '';
            const last = (tgUser.last_name || '')[0] || '';
            return (first + last).toUpperCase() || '?';
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('it-IT', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    },
};
</script>

