<template>
    <Head title="Impostazioni" />

    <Layout>
        <PageHeader title="Impostazioni" pageTitle="Configurazione" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-settings-3-line me-2"></i>Impostazioni Azienda
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Settings Form -->
                        <form v-else @submit.prevent="saveSettings">
                            <!-- Company Selection (only for super-admin) -->
                            <BRow v-if="isSuperAdmin" class="mb-4">
                                <BCol md="12">
                                    <div class="alert alert-info">
                                        <label class="form-label fw-bold">Seleziona Azienda</label>
                                        <select
                                            v-model="selectedCompanyId"
                                            class="form-select"
                                            @change="loadSettings"
                                        >
                                            <option value="">Seleziona un'azienda</option>
                                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                                {{ company.name }}
                                            </option>
                                        </select>
                                    </div>
                                </BCol>
                            </BRow>

                            <div v-if="selectedCompanyId || !isSuperAdmin">
                                <!-- Sezione Vendite -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-money-dollar-circle-line me-2"></i>Vendite
                                    </legend>

                                    <BRow>
                                        <!-- Percentuale Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Percentuale Acconto di Default (%)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.deposit_percentage"
                                                type="number"
                                                class="form-control"
                                                required
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="Es. 30.00"
                                            />
                                            <small class="text-muted">
                                                Percentuale di default per calcolare l'acconto di vendita
                                            </small>
                                        </BCol>

                                        <!-- Percentuale Commissioni Carta -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Commissioni Carta di Credito (%)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.card_fees_percentage"
                                                type="number"
                                                class="form-control"
                                                required
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="Es. 5.00"
                                            />
                                            <small class="text-muted">
                                                Percentuale commissioni applicate sui pagamenti con carta
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Causale Contabile Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acconto Vendita</label>
                                            <select
                                                v-model="form.deposit_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acconto vendita
                                            </small>
                                        </BCol>

                                        <!-- Causale Movimento Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acconto Vendita</label>
                                            <input
                                                v-model="form.deposit_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acconto servizio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acconto
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Causale Contabile Saldo -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Saldo Vendita</label>
                                            <select
                                                v-model="form.balance_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di saldo vendita
                                            </small>
                                        </BCol>

                                        <!-- Causale Movimento Saldo -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Saldo Vendita</label>
                                            <input
                                                v-model="form.balance_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Saldo servizio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di saldo
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Alert per errori -->
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
                                        Salva Impostazioni
                                    </button>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                Seleziona un'azienda per modificare le impostazioni
                            </div>
                        </form>
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
            errors: [],
            successMessage: '',
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            accountingEntries: [],
            form: {
                deposit_percentage: 30.00,
                card_fees_percentage: 5.00,
                deposit_accounting_entry_id: null,
                deposit_reason: null,
                balance_accounting_entry_id: null,
                balance_reason: null,
            },
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
    },
    async mounted() {
        await this.loadCurrentUser();
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadSettings();
            await this.loadAccountingEntries();
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
        async loadSettings() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/settings', { params });

                const data = response.data.data;
                this.form = {
                    deposit_percentage: data.deposit_percentage || 30.00,
                    card_fees_percentage: data.card_fees_percentage || 5.00,
                    deposit_accounting_entry_id: data.deposit_accounting_entry_id || null,
                    deposit_reason: data.deposit_reason || null,
                    balance_accounting_entry_id: data.balance_accounting_entry_id || null,
                    balance_reason: data.balance_reason || null,
                };

                await this.loadAccountingEntries();
            } catch (error) {
                console.error('Error loading settings:', error);
                this.errors = ['Errore durante il caricamento delle impostazioni'];
            } finally {
                this.loading = false;
            }
        },
        async loadAccountingEntries() {
            if (!this.selectedCompanyId) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/settings/accounting-entries', { params });
                this.accountingEntries = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounting entries:', error);
            }
        },
        async saveSettings() {
            this.saving = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const data = { ...this.form };
                if (this.isSuperAdmin) {
                    data.company_id = this.selectedCompanyId;
                }

                const response = await axios.put('/api/settings', data);
                this.successMessage = 'Impostazioni salvate con successo!';

                // Auto-hide success message after 3 seconds
                setTimeout(() => {
                    this.successMessage = '';
                }, 3000);
            } catch (error) {
                console.error('Error saving settings:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = ['Errore durante il salvataggio delle impostazioni'];
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
