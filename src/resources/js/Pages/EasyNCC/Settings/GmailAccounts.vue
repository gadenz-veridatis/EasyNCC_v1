<template>
    <Layout>
        <PageHeader title="Account Gmail" pageTitle="Impostazioni" />
        <BRow>
            <BCol lg="8">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-mail-line me-2"></i>Account Gmail
                        </h5>
                        <button class="btn btn-sm btn-primary" @click="openCreateModal">
                            <i class="ri-add-line me-1"></i>Nuovo Account
                        </button>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Company Selection (super-admin only) -->
                        <BRow v-if="isSuperAdmin && !loading" class="mb-4">
                            <BCol md="12">
                                <div class="alert alert-info">
                                    <label class="form-label fw-bold">Seleziona Azienda</label>
                                    <select v-model="selectedCompanyId" class="form-select" @change="loadAccounts">
                                        <option value="">Seleziona un'azienda</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </div>
                            </BCol>
                        </BRow>

                        <!-- Alerts -->
                        <div v-if="errors.length > 0" class="alert alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
                            {{ successMessage }}
                            <button type="button" class="btn-close" @click="successMessage = ''"></button>
                        </div>

                        <!-- Accounts Table -->
                        <div v-if="!loading && selectedCompanyId" class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Etichetta</th>
                                        <th>Indirizzo Email</th>
                                        <th class="text-center">Attivo</th>
                                        <th class="text-center">Token</th>
                                        <th class="text-center">Ingestion</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="accounts.length === 0">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Nessun account Gmail configurato. Aggiungi il primo account.
                                        </td>
                                    </tr>
                                    <tr v-for="account in accounts" :key="account.id">
                                        <td class="fw-medium">{{ account.account_label }}</td>
                                        <td>{{ account.email_address }}</td>
                                        <td class="text-center">
                                            <span class="badge" :class="account.is_active ? 'bg-success' : 'bg-secondary'">
                                                {{ account.is_active ? 'Attivo' : 'Disattivo' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="account.token_expires_at" class="badge" :class="isTokenExpired(account.token_expires_at) ? 'bg-danger' : 'bg-success'">
                                                {{ isTokenExpired(account.token_expires_at) ? 'Scaduto' : 'Valido' }}
                                            </span>
                                            <span v-else class="badge bg-warning">Non impostato</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="account.ingestion_attiva" class="badge bg-success">
                                                <i class="ri-mail-download-line me-1"></i>Attiva
                                            </span>
                                            <span v-else-if="account.label_richieste_id" class="badge bg-warning-subtle text-warning">
                                                Label OK
                                            </span>
                                            <span v-else class="badge bg-secondary-subtle text-secondary">
                                                Non configurata
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="testConnection(account)" title="Testa Connessione" :disabled="testingId === account.id">
                                                <span v-if="testingId === account.id" class="spinner-border spinner-border-sm"></span>
                                                <i v-else class="ri-link-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-primary me-1" @click="openEditModal(account)" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-warning me-1" @click="openIngestionModal(account)" title="Configura Ingestion">
                                                <i class="ri-mail-settings-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteAccount(account)" title="Elimina" :disabled="actionLoading">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="!loading && !selectedCompanyId && isSuperAdmin" class="alert alert-warning">
                            Seleziona un'azienda per gestire gli account Gmail
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <!-- Info Column -->
            <BCol lg="4">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-information-line me-2"></i>Guida
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <div class="mb-3">
                            <h6 class="fw-bold">1. Crea progetto Google Cloud</h6>
                            <p class="small text-muted mb-1">
                                Vai su <strong>console.cloud.google.com</strong>, crea un progetto e abilita la Gmail API.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">2. Crea credenziali OAuth 2.0</h6>
                            <p class="small text-muted mb-1">
                                Crea credenziali OAuth client ID (tipo "Web application"). Annota Client ID e Client Secret.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">3. Ottieni il Refresh Token</h6>
                            <p class="small text-muted mb-1">
                                Usa l'OAuth Playground di Google o uno script per ottenere il refresh token con scope <code>gmail.compose</code> e <code>gmail.modify</code>.
                            </p>
                        </div>
                        <div>
                            <h6 class="fw-bold">4. Inserisci i dati</h6>
                            <p class="small text-muted mb-0">
                                Inserisci Client ID, Client Secret, Refresh Token e l'indirizzo email dell'account Gmail autorizzato.
                            </p>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Create/Edit Modal -->
        <BModal v-model="showModal" :title="editingAccount ? 'Modifica Account' : 'Nuovo Account Gmail'" hide-footer size="lg">
            <form @submit.prevent="saveAccount">
                <div v-if="modalErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, index) in modalErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <BRow>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Etichetta Account <span class="text-danger">*</span></label>
                        <input v-model="modalForm.account_label" type="text" class="form-control" placeholder="es. Account Principale" required />
                    </BCol>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Indirizzo Email <span class="text-danger">*</span></label>
                        <input v-model="modalForm.email_address" type="email" class="form-control" placeholder="email@gmail.com" required />
                    </BCol>
                </BRow>

                <BRow>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Client ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                v-model="modalForm.client_id"
                                :type="showSecrets ? 'text' : 'password'"
                                class="form-control"
                                placeholder="xxxx.apps.googleusercontent.com"
                                required
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="showSecrets = !showSecrets">
                                <i :class="showSecrets ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </BCol>
                    <BCol md="6" class="mb-3">
                        <label class="form-label">Client Secret <span class="text-danger">*</span></label>
                        <input v-model="modalForm.client_secret" :type="showSecrets ? 'text' : 'password'" class="form-control" placeholder="GOCSPX-..." required />
                    </BCol>
                </BRow>

                <div class="mb-3">
                    <label class="form-label">Refresh Token <span class="text-danger">*</span></label>
                    <textarea v-model="modalForm.refresh_token" :class="showSecrets ? '' : 'text-security'" class="form-control font-monospace" rows="3" placeholder="1//..." required></textarea>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input v-model="modalForm.is_active" type="checkbox" class="form-check-input" id="isActiveGmailCheck" />
                        <label class="form-check-label" for="isActiveGmailCheck">Attivo</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <BButton variant="light" @click="showModal = false">Annulla</BButton>
                    <BButton type="submit" variant="primary" :disabled="modalSaving">
                        <span v-if="modalSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-save-line me-1"></i>
                        {{ editingAccount ? 'Aggiorna' : 'Crea' }}
                    </BButton>
                </div>
            </form>
        </BModal>
        <!-- Ingestion Config Modal -->
        <BModal v-model="showIngestionModal" title="Configurazione Ingestion Email" hide-footer size="md">
            <div v-if="ingestionAccount">
                <div class="mb-3">
                    <label class="form-label">Account</label>
                    <div class="fw-semibold">{{ ingestionAccount.account_label }} ({{ ingestionAccount.email_address }})</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome Label Gmail <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input
                            v-model="ingestionForm.label_richieste"
                            type="text"
                            class="form-control"
                            placeholder="es. NCC/Richiesta"
                        />
                        <button class="btn btn-outline-primary" type="button" @click="resolveLabel" :disabled="resolvingLabel || !ingestionForm.label_richieste">
                            <span v-if="resolvingLabel" class="spinner-border spinner-border-sm"></span>
                            <span v-else><i class="ri-check-double-line me-1"></i>Verifica</span>
                        </button>
                    </div>
                    <div v-if="ingestionAccount.label_richieste_id" class="mt-1">
                        <small class="text-success"><i class="ri-check-line me-1"></i>Label verificata: {{ ingestionAccount.label_richieste_id }}</small>
                    </div>
                    <small class="form-text text-muted">La label deve esistere nella casella Gmail. Creala manualmente in Gmail se non esiste.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subject Tag (opzionale)</label>
                    <input v-model="ingestionForm.subject_tag" type="text" class="form-control" placeholder="es. [NCC]" />
                    <small class="form-text text-muted">Tag nel subject come segnale secondario</small>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input
                            v-model="ingestionForm.ingestion_attiva"
                            type="checkbox"
                            class="form-check-input"
                            id="ingestionAttivaCheck"
                            :disabled="!ingestionAccount.label_richieste_id"
                        />
                        <label class="form-check-label" for="ingestionAttivaCheck">
                            Attiva Ingestion
                        </label>
                    </div>
                    <small v-if="!ingestionAccount.label_richieste_id" class="text-warning">
                        <i class="ri-alert-line me-1"></i>Verifica la label prima di attivare l'ingestion
                    </small>
                </div>

                <div class="d-flex justify-content-between">
                    <button
                        class="btn btn-sm btn-outline-info"
                        @click="fetchNow"
                        :disabled="fetchingNow || !ingestionAccount.ingestion_attiva"
                    >
                        <span v-if="fetchingNow" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-refresh-line me-1"></i>Controlla Ora
                    </button>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light" @click="showIngestionModal = false">Chiudi</button>
                        <button class="btn btn-primary" @click="saveIngestion" :disabled="savingIngestion">
                            <span v-if="savingIngestion" class="spinner-border spinner-border-sm me-1"></span>
                            Salva
                        </button>
                    </div>
                </div>

                <div v-if="fetchResult" class="mt-3 alert" :class="fetchResult.success ? 'alert-success' : 'alert-danger'">
                    {{ fetchResult.message }}
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

export default {
    components: { Head, Link, Layout, PageHeader },
    data() {
        return {
            loading: false,
            actionLoading: false,
            modalSaving: false,
            showSecrets: false,
            errors: [],
            modalErrors: [],
            successMessage: '',
            companies: [],
            selectedCompanyId: '',
            accounts: [],
            editingAccount: null,
            modalForm: {
                account_label: '',
                email_address: '',
                client_id: '',
                client_secret: '',
                refresh_token: '',
                is_active: true,
            },
            showModal: false,
            testingId: null,
            // Ingestion modal
            showIngestionModal: false,
            ingestionAccount: null,
            ingestionForm: { label_richieste: '', subject_tag: '', ingestion_attiva: false },
            resolvingLabel: false,
            savingIngestion: false,
            fetchingNow: false,
            fetchResult: null,
        };
    },
    computed: {
        currentUser() {
            return this.$page?.props?.auth?.user;
        },
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
    },
    async mounted() {
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else if (this.currentUser?.company_id) {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadAccounts();
        }
    },
    methods: {
        async loadCompanies() {
            try {
                const response = await axios.get('/api/companies');
                this.companies = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },
        async loadAccounts() {
            if (!this.selectedCompanyId) return;
            this.loading = true;
            this.errors = [];
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/gmail-accounts', { params });
                this.accounts = response.data.data || [];
            } catch (error) {
                this.errors = ['Errore nel caricamento degli account'];
            } finally {
                this.loading = false;
            }
        },
        openCreateModal() {
            this.editingAccount = null;
            this.modalForm = {
                account_label: '', email_address: '', client_id: '',
                client_secret: '', refresh_token: '', is_active: true,
            };
            this.modalErrors = [];
            this.showSecrets = false;
            this.showModal = true;
        },
        openEditModal(account) {
            this.editingAccount = account;
            this.modalForm = {
                account_label: account.account_label,
                email_address: account.email_address,
                client_id: account.client_id,
                client_secret: account.client_secret,
                refresh_token: account.refresh_token,
                is_active: account.is_active,
            };
            this.modalErrors = [];
            this.showSecrets = false;
            this.showModal = true;
        },
        async saveAccount() {
            this.modalSaving = true;
            this.modalErrors = [];
            try {
                const payload = { ...this.modalForm };
                if (this.isSuperAdmin) payload.company_id = this.selectedCompanyId;

                if (this.editingAccount) {
                    await axios.put(`/api/gmail-accounts/${this.editingAccount.id}`, payload);
                    this.successMessage = 'Account aggiornato con successo';
                } else {
                    await axios.post('/api/gmail-accounts', payload);
                    this.successMessage = 'Account creato con successo';
                }
                this.showModal = false;
                await this.loadAccounts();
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
        async deleteAccount(account) {
            if (!confirm(`Eliminare l'account "${account.account_label}"?`)) return;
            this.actionLoading = true;
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                await axios.delete(`/api/gmail-accounts/${account.id}`, { params });
                this.successMessage = 'Account eliminato';
                await this.loadAccounts();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nell\'eliminazione'];
            } finally {
                this.actionLoading = false;
            }
        },
        async testConnection(account) {
            this.testingId = account.id;
            this.errors = [];
            this.successMessage = '';
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.post(`/api/gmail-accounts/${account.id}/test-connection`, params);
                this.successMessage = response.data.message || 'Connessione riuscita!';
                // Update account in list with fresh data (token updated)
                if (response.data.data) {
                    const idx = this.accounts.findIndex(a => a.id === account.id);
                    if (idx >= 0) this.accounts.splice(idx, 1, response.data.data);
                }
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore durante il test della connessione'];
            } finally {
                this.testingId = null;
            }
        },
        isTokenExpired(expiresAt) {
            if (!expiresAt) return true;
            return new Date(expiresAt) < new Date();
        },
        // Ingestion methods
        openIngestionModal(account) {
            this.ingestionAccount = { ...account };
            this.ingestionForm = {
                label_richieste: account.label_richieste || '',
                subject_tag: account.subject_tag || '',
                ingestion_attiva: account.ingestion_attiva || false,
            };
            this.fetchResult = null;
            this.showIngestionModal = true;
        },
        async resolveLabel() {
            this.resolvingLabel = true;
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const res = await axios.post(
                    `/api/gmail-accounts/${this.ingestionAccount.id}/resolve-label`,
                    { label_name: this.ingestionForm.label_richieste, ...params }
                );
                if (res.data.success) {
                    this.successMessage = res.data.message;
                    this.ingestionAccount = res.data.data;
                    await this.loadAccounts();
                }
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nella verifica della label'];
            } finally {
                this.resolvingLabel = false;
            }
        },
        async saveIngestion() {
            this.savingIngestion = true;
            try {
                const payload = {
                    label_richieste: this.ingestionForm.label_richieste,
                    subject_tag: this.ingestionForm.subject_tag,
                    ingestion_attiva: this.ingestionForm.ingestion_attiva,
                };
                if (this.isSuperAdmin) payload.company_id = this.selectedCompanyId;
                await axios.put(`/api/gmail-accounts/${this.ingestionAccount.id}`, payload);
                this.successMessage = 'Configurazione ingestion salvata';
                this.showIngestionModal = false;
                await this.loadAccounts();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nel salvataggio'];
            } finally {
                this.savingIngestion = false;
            }
        },
        async fetchNow() {
            this.fetchingNow = true;
            this.fetchResult = null;
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const res = await axios.post(
                    `/api/gmail-accounts/${this.ingestionAccount.id}/fetch-now`,
                    params
                );
                this.fetchResult = { success: true, message: res.data.message };
                // Refresh account data
                this.ingestionAccount = res.data.data;
                await this.loadAccounts();
            } catch (error) {
                this.fetchResult = { success: false, message: error.response?.data?.message || 'Errore nel fetch' };
            } finally {
                this.fetchingNow = false;
            }
        },
    },
};
</script>

<style scoped>
.text-security {
    -webkit-text-security: disc;
}
</style>
