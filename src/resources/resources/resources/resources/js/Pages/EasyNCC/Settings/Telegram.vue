<template>
    <Layout>
        <PageHeader title="Impostazioni Telegram" pageTitle="Configurazione" />
        <BRow>
            <BCol lg="8">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-telegram-line me-2"></i>Configurazione Bot Telegram
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <form v-else @submit.prevent="saveConfig">
                            <!-- Company Selection (super-admin only) -->
                            <BRow v-if="isSuperAdmin" class="mb-4">
                                <BCol md="12">
                                    <div class="alert alert-info">
                                        <label class="form-label fw-bold">Seleziona Azienda</label>
                                        <select v-model="selectedCompanyId" class="form-select" @change="loadConfig">
                                            <option value="">Seleziona un'azienda</option>
                                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                                {{ company.name }}
                                            </option>
                                        </select>
                                    </div>
                                </BCol>
                            </BRow>

                            <div v-if="selectedCompanyId">
                                <!-- Sezione Credenziali Bot -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-robot-line me-2"></i>Credenziali Bot
                                    </legend>

                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">Bot Token <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input
                                                    v-model="form.bot_token"
                                                    :type="showToken ? 'text' : 'password'"
                                                    class="form-control"
                                                    placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
                                                    required
                                                />
                                                <button class="btn btn-outline-secondary" type="button" @click="showToken = !showToken">
                                                    <i :class="showToken ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Ottieni il token da @BotFather su Telegram</small>
                                        </BCol>
                                    </BRow>

                                    <BRow v-if="form.bot_username">
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">Username Bot</label>
                                            <div class="form-control bg-light">
                                                @{{ form.bot_username }}
                                            </div>
                                            <small class="text-muted">Recuperato automaticamente dalle API Telegram</small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Webhook -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-links-line me-2"></i>Webhook
                                    </legend>

                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">URL Webhook</label>
                                            <div class="input-group">
                                                <input
                                                    v-model="form.webhook_url"
                                                    type="url"
                                                    class="form-control"
                                                    :placeholder="suggestedWebhookUrl"
                                                />
                                                <button class="btn btn-outline-secondary" type="button" @click="form.webhook_url = suggestedWebhookUrl" title="Usa URL suggerito">
                                                    <i class="ri-magic-line"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">URL HTTPS dove Telegram invierà gli aggiornamenti</small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge fs-6" :class="form.webhook_active ? 'bg-success' : 'bg-secondary'">
                                                    <i :class="form.webhook_active ? 'ri-check-line' : 'ri-close-line'"></i>
                                                    {{ form.webhook_active ? 'Webhook Attivo' : 'Webhook Non Attivo' }}
                                                </span>
                                                <button
                                                    v-if="!form.webhook_active"
                                                    type="button"
                                                    class="btn btn-sm btn-success"
                                                    @click="activateWebhook"
                                                    :disabled="webhookLoading || !form.bot_token || !form.webhook_url"
                                                >
                                                    <span v-if="webhookLoading" class="spinner-border spinner-border-sm me-1"></span>
                                                    <i v-else class="ri-play-line me-1"></i>
                                                    Attiva Webhook
                                                </button>
                                                <button
                                                    v-else
                                                    type="button"
                                                    class="btn btn-sm btn-warning"
                                                    @click="deactivateWebhook"
                                                    :disabled="webhookLoading"
                                                >
                                                    <span v-if="webhookLoading" class="spinner-border spinner-border-sm me-1"></span>
                                                    <i v-else class="ri-stop-line me-1"></i>
                                                    Disattiva Webhook
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-info"
                                                    @click="loadWebhookInfo"
                                                    :disabled="webhookLoading || !form.bot_token"
                                                    title="Aggiorna stato webhook"
                                                >
                                                    <i class="ri-refresh-line"></i>
                                                </button>
                                            </div>
                                        </BCol>
                                    </BRow>

                                    <!-- Webhook Info -->
                                    <div v-if="webhookInfo" class="mt-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-bold" style="width: 200px;">URL</td>
                                                        <td>{{ webhookInfo.url || '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold">Pending Updates</td>
                                                        <td>{{ webhookInfo.pending_update_count || 0 }}</td>
                                                    </tr>
                                                    <tr v-if="webhookInfo.last_error_date">
                                                        <td class="fw-bold text-danger">Ultimo Errore</td>
                                                        <td class="text-danger">
                                                            {{ formatTimestamp(webhookInfo.last_error_date) }} - {{ webhookInfo.last_error_message }}
                                                        </td>
                                                    </tr>
                                                    <tr v-if="webhookInfo.max_connections">
                                                        <td class="fw-bold">Max Connections</td>
                                                        <td>{{ webhookInfo.max_connections }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Alert errori -->
                                <div v-if="errors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>

                                <!-- Alert successo -->
                                <div v-if="successMessage" class="alert alert-success">
                                    {{ successMessage }}
                                </div>

                                <!-- Pulsanti -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Configurazione
                                    </button>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                Seleziona un'azienda per configurare il bot Telegram
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>

            <!-- Colonna informativa -->
            <BCol lg="4">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-information-line me-2"></i>Guida
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <div class="mb-3">
                            <h6 class="fw-bold">1. Crea il Bot</h6>
                            <p class="small text-muted mb-1">
                                Cerca <strong>@BotFather</strong> su Telegram e invia il comando <code>/newbot</code>.
                                Segui le istruzioni per ottenere il token.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">2. Inserisci il Token</h6>
                            <p class="small text-muted mb-1">
                                Incolla il token ricevuto nel campo qui a fianco e salva.
                                Lo username del bot verrà recuperato automaticamente.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6 class="fw-bold">3. Configura il Webhook</h6>
                            <p class="small text-muted mb-1">
                                L'URL del webhook viene suggerito automaticamente.
                                Clicca su "Attiva Webhook" per iniziare a ricevere messaggi.
                            </p>
                        </div>
                        <div>
                            <h6 class="fw-bold">4. I driver si iscrivono</h6>
                            <p class="small text-muted mb-0">
                                I driver devono cercare il bot su Telegram e inviare <code>/start</code>.
                                Potrai poi associarli dalla pagina Utenti Telegram.
                            </p>
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

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
    },
    data() {
        return {
            loading: false,
            saving: false,
            webhookLoading: false,
            showToken: false,
            errors: [],
            successMessage: '',
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            webhookInfo: null,
            form: {
                bot_token: '',
                bot_username: null,
                webhook_url: null,
                webhook_active: false,
            },
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        suggestedWebhookUrl() {
            return window.location.origin + '/api/telegram/webhook/' + this.selectedCompanyId;
        },
    },
    async mounted() {
        await this.loadCurrentUser();
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadConfig();
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
        async loadConfig() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            this.errors = [];
            this.successMessage = '';
            this.webhookInfo = null;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/telegram/config', { params });

                const data = response.data.data;
                this.form = {
                    bot_token: data.bot_token || '',
                    bot_username: data.bot_username || null,
                    webhook_url: data.webhook_url || null,
                    webhook_active: data.webhook_active || false,
                };

                // Load webhook info if bot is configured
                if (this.form.bot_token) {
                    await this.loadWebhookInfo();
                }
            } catch (error) {
                console.error('Error loading telegram config:', error);
                this.errors = ['Errore nel caricamento della configurazione'];
            } finally {
                this.loading = false;
            }
        },
        async saveConfig() {
            this.saving = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const payload = {
                    bot_token: this.form.bot_token,
                    webhook_url: this.form.webhook_url,
                };

                if (this.isSuperAdmin) {
                    payload.company_id = this.selectedCompanyId;
                }

                const response = await axios.put('/api/telegram/config', payload);

                const data = response.data.data;
                this.form.bot_username = data.bot_username;
                this.form.webhook_active = data.webhook_active;

                this.successMessage = response.data.message;
            } catch (error) {
                if (error.response?.status === 422) {
                    const validationErrors = error.response.data.errors || {};
                    this.errors = Object.values(validationErrors).flat();
                    if (error.response.data.message) {
                        this.errors.unshift(error.response.data.message);
                    }
                } else {
                    this.errors = ['Errore nel salvataggio della configurazione'];
                }
            } finally {
                this.saving = false;
            }
        },
        async activateWebhook() {
            this.webhookLoading = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.post('/api/telegram/webhook/activate', params);

                this.form.webhook_active = true;
                this.successMessage = response.data.message;
                await this.loadWebhookInfo();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nell\'attivazione del webhook'];
            } finally {
                this.webhookLoading = false;
            }
        },
        async deactivateWebhook() {
            this.webhookLoading = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.post('/api/telegram/webhook/deactivate', params);

                this.form.webhook_active = false;
                this.successMessage = response.data.message;
                await this.loadWebhookInfo();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nella disattivazione del webhook'];
            } finally {
                this.webhookLoading = false;
            }
        },
        async loadWebhookInfo() {
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/telegram/webhook/info', { params });
                this.webhookInfo = response.data.data;
            } catch (error) {
                console.error('Error loading webhook info:', error);
            }
        },
        formatTimestamp(unixTimestamp) {
            if (!unixTimestamp) return '-';
            const date = new Date(unixTimestamp * 1000);
            return date.toLocaleString('it-IT');
        },
    },
};
</script>
