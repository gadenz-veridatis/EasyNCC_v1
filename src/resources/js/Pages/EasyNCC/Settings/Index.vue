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
                                <!-- Sezione Ricavi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-money-dollar-circle-line me-2"></i>Ricavi
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

                                <!-- Sezione Costi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-shopping-cart-line me-2"></i>Costi
                                    </legend>

                                    <!-- A: Commissioni -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Commissioni</label>
                                            <select
                                                v-model="form.commission_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i movimenti di commissioni
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Commissioni</label>
                                            <input
                                                v-model="form.commission_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Commissioni intermediario"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di commissioni
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- B: Acquisto Carburanti -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Carburanti</label>
                                            <select
                                                v-model="form.fuel_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i movimenti di acquisto carburanti
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Carburanti</label>
                                            <input
                                                v-model="form.fuel_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto carburante"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto carburanti
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- C: Acquisto Pedaggio -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Pedaggio</label>
                                            <select
                                                v-model="form.toll_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i movimenti di acquisto pedaggio
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Pedaggio</label>
                                            <input
                                                v-model="form.toll_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Pedaggio autostradale"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto pedaggio
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- D: Acquisto Parcheggio -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Parcheggio</label>
                                            <select
                                                v-model="form.parking_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i movimenti di acquisto parcheggio
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Parcheggio</label>
                                            <input
                                                v-model="form.parking_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Parcheggio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto parcheggio
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- E: Altri Costi del Veicolo -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Altri Costi del Veicolo</label>
                                            <select
                                                v-model="form.other_vehicle_accounting_entry_id"
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
                                                Causale contabile da utilizzare per altri costi veicolo
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Altri Costi del Veicolo</label>
                                            <input
                                                v-model="form.other_vehicle_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Altri costi veicolo"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di altri costi veicolo
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- F: Costi Driver -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Costi Driver</label>
                                            <select
                                                v-model="form.driver_cost_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i costi driver
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Costi Driver</label>
                                            <input
                                                v-model="form.driver_cost_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Compenso driver"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di costi driver
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- G: Costi Collega -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Costi Collega</label>
                                            <select
                                                v-model="form.colleague_cost_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i costi collega
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto da Collega</label>
                                            <input
                                                v-model="form.colleague_cost_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto da collega"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto da collega
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- H: Acquisto Esperienze -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Acquisto Esperienze</label>
                                            <select
                                                v-model="form.experience_accounting_entry_id"
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
                                                Causale contabile da utilizzare per i movimenti di acquisto esperienze
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Esperienze</label>
                                            <input
                                                v-model="form.experience_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto esperienza"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto esperienze
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Esperienze -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-map-pin-line me-2"></i>Esperienze
                                    </legend>

                                    <BRow>
                                        <!-- Testo Conferma Prenotazione Esperienza -->
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">
                                                Testo Conferma Prenotazione Esperienza
                                            </label>
                                            <textarea
                                                v-model="form.activity_confirmation_text"
                                                class="form-control"
                                                rows="3"
                                                placeholder="Es. Confermare prenotazione {$fornitore$} per servizio {$servizio$}"
                                            ></textarea>
                                            <small class="text-muted">
                                                Testo utilizzato nel Nome del Task. Segnaposto disponibili: <code>{$fornitore$}</code> e <code>{$servizio$}</code>
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Ruolo Assegnatari -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Assegna a Ruolo</label>
                                            <select
                                                v-model="form.activity_confirmation_role"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona ruolo</option>
                                                <option value="super-admin">Super Admin</option>
                                                <option value="admin">Admin</option>
                                                <option value="operator">Operatore</option>
                                                <option value="driver">Driver</option>
                                                <option value="collaboratore">Collaboratore</option>
                                                <option value="contabilita">Contabilità</option>
                                            </select>
                                            <small class="text-muted">
                                                Ruolo degli utenti a cui saranno assegnati i task di conferma prenotazione esperienza
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Veicoli -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-car-line me-2"></i>Veicoli
                                    </legend>

                                    <BRow>
                                        <!-- Fornitore Default -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Fornitore Default</label>
                                            <select
                                                v-model="form.default_supplier_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Nessun fornitore di default</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.name }} {{ supplier.surname }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Fornitore che verrà selezionato automaticamente nei nuovi servizi
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
            suppliers: [],
            form: {
                deposit_percentage: 30.00,
                card_fees_percentage: 5.00,
                deposit_accounting_entry_id: null,
                deposit_reason: null,
                balance_accounting_entry_id: null,
                balance_reason: null,
                commission_accounting_entry_id: null,
                commission_reason: null,
                fuel_accounting_entry_id: null,
                fuel_reason: null,
                toll_accounting_entry_id: null,
                toll_reason: null,
                parking_accounting_entry_id: null,
                parking_reason: null,
                other_vehicle_accounting_entry_id: null,
                other_vehicle_reason: null,
                driver_cost_accounting_entry_id: null,
                driver_cost_reason: null,
                colleague_cost_accounting_entry_id: null,
                colleague_cost_reason: null,
                experience_accounting_entry_id: null,
                experience_reason: null,
                activity_confirmation_text: null,
                activity_confirmation_role: null,
                default_supplier_id: null,
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
            await this.loadSuppliers();
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
                    commission_accounting_entry_id: data.commission_accounting_entry_id || null,
                    commission_reason: data.commission_reason || null,
                    fuel_accounting_entry_id: data.fuel_accounting_entry_id || null,
                    fuel_reason: data.fuel_reason || null,
                    toll_accounting_entry_id: data.toll_accounting_entry_id || null,
                    toll_reason: data.toll_reason || null,
                    parking_accounting_entry_id: data.parking_accounting_entry_id || null,
                    parking_reason: data.parking_reason || null,
                    other_vehicle_accounting_entry_id: data.other_vehicle_accounting_entry_id || null,
                    other_vehicle_reason: data.other_vehicle_reason || null,
                    driver_cost_accounting_entry_id: data.driver_cost_accounting_entry_id || null,
                    driver_cost_reason: data.driver_cost_reason || null,
                    colleague_cost_accounting_entry_id: data.colleague_cost_accounting_entry_id || null,
                    colleague_cost_reason: data.colleague_cost_reason || null,
                    experience_accounting_entry_id: data.experience_accounting_entry_id || null,
                    experience_reason: data.experience_reason || null,
                    activity_confirmation_text: data.activity_confirmation_text || null,
                    activity_confirmation_role: data.activity_confirmation_role || null,
                    default_supplier_id: data.default_supplier_id || null,
                };

                await this.loadAccountingEntries();
                await this.loadSuppliers();
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
        async loadSuppliers() {
            if (!this.selectedCompanyId) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                params.role = 'collaboratore';
                params.is_fornitore = 1;
                params.per_page = 1000;
                const response = await axios.get('/api/users', { params });
                this.suppliers = response.data.data || [];
            } catch (error) {
                console.error('Error loading suppliers:', error);
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
