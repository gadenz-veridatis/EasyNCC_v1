<template>
    <Layout>
        <PageHeader title="Configurazione SumUp" pageTitle="Impostazioni" />
        <BRow>
            <BCol lg="8">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-bank-card-line me-2"></i>Merchant SumUp
                        </h5>
                        <button class="btn btn-sm btn-primary" @click="openCreateModal">
                            <i class="ri-add-line me-1"></i>Nuovo Merchant
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
                                    <select v-model="selectedCompanyId" class="form-select" @change="loadConfigs">
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

                        <!-- Configs Table -->
                        <div v-if="!loading && selectedCompanyId" class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome Merchant</th>
                                        <th>Merchant Code</th>
                                        <th class="text-center">Attivo</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="configs.length === 0">
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Nessun merchant SumUp configurato. Aggiungi il primo merchant.
                                        </td>
                                    </tr>
                                    <tr v-for="config in configs" :key="config.id">
                                        <td class="fw-medium">{{ config.merchant_name }}</td>
                                        <td><code>{{ config.merchant_code }}</code></td>
                                        <td class="text-center">
                                            <span class="badge" :class="config.is_active ? 'bg-success' : 'bg-secondary'">
                                                {{ config.is_active ? 'Attivo' : 'Disattivo' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-soft-primary me-1" @click="openEditModal(config)" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteConfig(config)" title="Elimina" :disabled="actionLoading">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="!loading && !selectedCompanyId && isSuperAdmin" class="alert alert-warning">
                            Seleziona un'azienda per gestire i merchant SumUp
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
                            <h6 class="fw-bold">1. Crea un account SumUp</h6>
                            <p class="small text-muted mb-1">
                                Registrati su <strong>sumup.com</strong> e attiva il tuo profilo merchant.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">2. Genera una API Key</h6>
                            <p class="small text-muted mb-1">
                                Nella dashboard SumUp, vai su Developers &gt; API Keys e genera una chiave con permesso <code>payments</code>.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">3. Inserisci i dati</h6>
                            <p class="small text-muted mb-1">
                                Inserisci nome merchant, API Key e Merchant Code (email merchant).
                            </p>
                        </div>
                        <div>
                            <h6 class="fw-bold">4. Webhook</h6>
                            <p class="small text-muted mb-0">
                                Configura il webhook nella dashboard SumUp con URL:<br/>
                                <code class="text-primary">{{ webhookUrl }}</code>
                            </p>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Create/Edit Modal -->
        <BModal v-model="showModal" :title="editingConfig ? 'Modifica Merchant' : 'Nuovo Merchant'" hide-footer>
            <form @submit.prevent="saveConfig">
                <div v-if="modalErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, index) in modalErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome Merchant <span class="text-danger">*</span></label>
                    <input v-model="modalForm.merchant_name" type="text" class="form-control" placeholder="es. NCC Italia SRL" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">API Key <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input
                            v-model="modalForm.api_key"
                            :type="showApiKey ? 'text' : 'password'"
                            class="form-control"
                            placeholder="sup_sk_..."
                            required
                        />
                        <button class="btn btn-outline-secondary" type="button" @click="showApiKey = !showApiKey">
                            <i :class="showApiKey ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Merchant Code <span class="text-danger">*</span></label>
                    <input v-model="modalForm.merchant_code" type="text" class="form-control" placeholder="email@merchant.com" required />
                    <small class="text-muted">Email associata all'account SumUp (pay_to_email)</small>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input v-model="modalForm.is_active" type="checkbox" class="form-check-input" id="isActiveCheck" />
                        <label class="form-check-label" for="isActiveCheck">Attivo</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <BButton variant="light" @click="showModal = false">Annulla</BButton>
                    <BButton type="submit" variant="primary" :disabled="modalSaving">
                        <span v-if="modalSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-save-line me-1"></i>
                        {{ editingConfig ? 'Aggiorna' : 'Crea' }}
                    </BButton>
                </div>
            </form>
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
            showApiKey: false,
            errors: [],
            modalErrors: [],
            successMessage: '',
            companies: [],
            selectedCompanyId: '',
            configs: [],
            editingConfig: null,
            modalForm: {
                merchant_name: '',
                api_key: '',
                merchant_code: '',
                is_active: true,
            },
            showModal: false,
        };
    },
    computed: {
        currentUser() {
            return this.$page?.props?.auth?.user;
        },
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        webhookUrl() {
            return window.location.origin + '/api/sumup/webhook/' + (this.selectedCompanyId || '{companyId}');
        },
    },
    async mounted() {
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else if (this.currentUser?.company_id) {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadConfigs();
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
        async loadConfigs() {
            if (!this.selectedCompanyId) return;
            this.loading = true;
            this.errors = [];
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/sumup-configs', { params });
                this.configs = response.data.data || [];
            } catch (error) {
                this.errors = ['Errore nel caricamento delle configurazioni'];
            } finally {
                this.loading = false;
            }
        },
        openCreateModal() {
            this.editingConfig = null;
            this.modalForm = { merchant_name: '', api_key: '', merchant_code: '', is_active: true };
            this.modalErrors = [];
            this.showApiKey = false;
            this.showModal = true;
        },
        openEditModal(config) {
            this.editingConfig = config;
            this.modalForm = {
                merchant_name: config.merchant_name,
                api_key: config.api_key,
                merchant_code: config.merchant_code,
                is_active: config.is_active,
            };
            this.modalErrors = [];
            this.showApiKey = false;
            this.showModal = true;
        },
        async saveConfig() {
            this.modalSaving = true;
            this.modalErrors = [];
            try {
                const payload = { ...this.modalForm };
                if (this.isSuperAdmin) payload.company_id = this.selectedCompanyId;

                if (this.editingConfig) {
                    await axios.put(`/api/sumup-configs/${this.editingConfig.id}`, payload);
                    this.successMessage = 'Merchant aggiornato con successo';
                } else {
                    await axios.post('/api/sumup-configs', payload);
                    this.successMessage = 'Merchant creato con successo';
                }
                this.showModal = false;
                await this.loadConfigs();
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
        async deleteConfig(config) {
            if (!confirm(`Eliminare il merchant "${config.merchant_name}"?`)) return;
            this.actionLoading = true;
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                await axios.delete(`/api/sumup-configs/${config.id}`, { params });
                this.successMessage = 'Merchant eliminato';
                await this.loadConfigs();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nell\'eliminazione'];
            } finally {
                this.actionLoading = false;
            }
        },
    },
};
</script>
