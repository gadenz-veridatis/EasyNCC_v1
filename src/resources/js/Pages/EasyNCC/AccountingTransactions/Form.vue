<template>
    <Head :title="isEdit ? 'Modifica Movimento' : 'Nuovo Movimento'" />

    <Layout>
        <PageHeader
            :title="isEdit ? 'Modifica Movimento Contabile' : 'Nuovo Movimento Contabile'"
            pageTitle="Contabilità"
        />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            {{ isEdit ? 'Modifica Movimento' : 'Nuovo Movimento' }}
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <form @submit.prevent="submitForm">
                            <!-- Fieldset 1: Dati Principali -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-file-list-3-line me-1"></i>
                                    Dati Principali
                                </legend>
                                <BRow>
                                    <!-- Transaction Type -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipo Movimento <span class="text-danger">*</span></label>
                                            <select v-model="form.transaction_type" class="form-select" @change="onTransactionTypeChange" required>
                                                <option value="">Seleziona tipo</option>
                                                <option value="purchase">Acquisto (Costi da Fornitore)</option>
                                                <option value="sale">Vendita (Ricavi da Committente)</option>
                                                <option value="intermediation">Intermediazione (Commissioni)</option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Transaction Date -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Data Movimento <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.transaction_date"
                                                type="date"
                                                class="form-control"
                                                required
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Amount -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Importo <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    v-model="form.amount"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Payment Reason (Causale) -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Causale Movimento</label>
                                            <input
                                                v-model="form.payment_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Pagamento servizio, Acconto, Saldo finale..."
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Installment -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Rata <span class="text-danger">*</span></label>
                                            <select v-model="form.installment" class="form-select" required>
                                                <option value="">Seleziona rata</option>
                                                <option value="deposit">Acconto</option>
                                                <option value="balance">Saldo</option>
                                                <option value="supplier_refund">Reso Fornitore</option>
                                                <option value="customer_refund">Rimborso Cliente</option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Status -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                                            <select v-model="form.status" class="form-select" required>
                                                <option value="">Seleziona stato</option>
                                                <!-- Purchase and Intermediation are DARE (costs/expenses) - use to_pay/paid -->
                                                <template v-if="form.transaction_type === 'purchase' || form.transaction_type === 'intermediation'">
                                                    <option value="to_pay">Da Pagare</option>
                                                    <option value="paid">Pagato</option>
                                                    <option value="suspended">Sospeso</option>
                                                    <option value="cancelled">Annullato</option>
                                                </template>
                                                <!-- Sale is AVERE (revenue/income) - use to_collect/collected -->
                                                <template v-else-if="form.transaction_type === 'sale'">
                                                    <option value="to_collect">Da Incassare</option>
                                                    <option value="collected">Incassato</option>
                                                    <option value="suspended">Sospeso</option>
                                                    <option value="cancelled">Annullato</option>
                                                </template>
                                                <!-- No transaction type selected yet - show all -->
                                                <template v-else>
                                                    <option value="to_pay">Da Pagare</option>
                                                    <option value="paid">Pagato</option>
                                                    <option value="to_collect">Da Incassare</option>
                                                    <option value="collected">Incassato</option>
                                                    <option value="suspended">Sospeso</option>
                                                    <option value="cancelled">Annullato</option>
                                                </template>
                                            </select>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 2: Riferimenti -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-links-line me-1"></i>
                                    Riferimenti
                                </legend>
                                <BRow>
                                    <!-- Company (Super Admin only) -->
                                    <BCol md="6" v-if="isSuperAdmin">
                                        <div class="mb-3">
                                            <label class="form-label">Azienda <span class="text-danger">*</span></label>
                                            <select v-model="form.company_id" class="form-select" :disabled="isEdit" required>
                                                <option value="">Seleziona azienda</option>
                                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                                    {{ company.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Service -->
                                    <BCol :md="isSuperAdmin ? 6 : 6">
                                        <div class="mb-3">
                                            <label class="form-label">Servizio</label>
                                            <select v-model="form.service_id" class="form-select">
                                                <option value="">Nessuno</option>
                                                <option v-for="service in services" :key="service.id" :value="service.id">
                                                    {{ service.reference_number }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Accounting Entry -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Causale Contabile</label>
                                            <select v-model="form.accounting_entry_id" class="form-select">
                                                <option value="">Nessuna</option>
                                                <option v-for="entry in accountingEntries" :key="entry.id" :value="entry.id">
                                                    {{ entry.name }} ({{ entry.abbreviation }})
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- Counterpart -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Controparte</label>
                                            <select v-model="form.counterpart_id" class="form-select">
                                                <option value="">Nessuna</option>
                                                <option v-for="user in filteredCounterparts" :key="user.id" :value="user.id">
                                                    {{ user.name }} {{ user.surname }} - {{ user.email }} ({{ getRoleLabel(user.counterpart_types) }})
                                                </option>
                                            </select>
                                            <small class="text-muted d-block mt-1">
                                                <span v-if="form.transaction_type === 'purchase'">Seleziona un Fornitore</span>
                                                <span v-else-if="form.transaction_type === 'sale'">Seleziona un Committente</span>
                                                <span v-else-if="form.transaction_type === 'intermediation'">Seleziona un Intermediario</span>
                                            </small>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 3: Documenti e Pagamenti -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-file-text-line me-1"></i>
                                    Documenti e Pagamenti
                                </legend>
                                <BRow>
                                    <!-- Document Number -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Numero Documento</label>
                                            <input
                                                v-model="form.document_number"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es: FT-2024-001"
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Document Due Date -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Scadenza Documento</label>
                                            <input
                                                v-model="form.document_due_date"
                                                type="date"
                                                class="form-control"
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Payment Date -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Data Pagamento</label>
                                            <input
                                                v-model="form.payment_date"
                                                type="date"
                                                class="form-control"
                                            />
                                        </div>
                                    </BCol>

                                    <!-- Payment Type -->
                                    <BCol md="6">
                                        <div class="mb-3">
                                            <label class="form-label">Modalità Pagamento</label>
                                            <select v-model="form.payment_type" class="form-select">
                                                <option value="">Nessuna</option>
                                                <option v-for="type in paymentTypes" :key="type.id" :value="type.name">
                                                    {{ type.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </BCol>

                                    <!-- IBAN -->
                                    <BCol md="12">
                                        <div class="mb-3">
                                            <label class="form-label">IBAN</label>
                                            <input
                                                v-model="form.iban"
                                                type="text"
                                                class="form-control"
                                                placeholder="IT60X0542811101000000123456"
                                            />
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Fieldset 4: Note -->
                            <fieldset class="border rounded p-3 mb-4">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                                    <i class="ri-sticky-note-line me-1"></i>
                                    Note
                                </legend>
                                <BRow>
                                    <BCol md="12">
                                        <div class="mb-3">
                                            <label class="form-label">Note</label>
                                            <textarea
                                                v-model="form.notes"
                                                class="form-control"
                                                rows="4"
                                                placeholder="Note aggiuntive..."
                                            ></textarea>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-end gap-2">
                                <Link
                                    :href="route('easyncc.accounting-transactions.index')"
                                    class="btn btn-soft-secondary"
                                >
                                    <i class="ri-close-line me-1"></i>
                                    Annulla
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    <i v-else class="ri-save-line me-1"></i>
                                    {{ isEdit ? 'Aggiorna' : 'Crea' }} Movimento
                                </button>
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
    },
    props: {
        transaction: {
            type: Object,
            default: null,
        },
    },
    setup(props) {
        const loading = ref(false);
        const companies = ref([]);
        const services = ref([]);
        const accountingEntries = ref([]);
        const paymentTypes = ref([]);
        const counterparts = ref([]);
        const user = ref(null);

        const form = ref({
            company_id: '',
            service_id: '',
            transaction_date: moment().format('YYYY-MM-DD'),
            amount: '',
            transaction_type: '',
            accounting_entry_id: '',
            installment: '',
            counterpart_id: '',
            document_number: '',
            document_due_date: '',
            payment_date: '',
            payment_type: '',
            payment_reason: '',
            iban: '',
            status: '',
            notes: '',
        });

        const isEdit = computed(() => !!props.transaction);
        const isSuperAdmin = computed(() => user.value?.role === 'super-admin');

        const filteredCounterparts = computed(() => {
            if (!form.value.transaction_type) {
                return counterparts.value;
            }

            const typeMap = {
                purchase: 'fornitore',
                sale: 'committente',
                intermediation: 'intermediario',
            };

            const targetType = typeMap[form.value.transaction_type];
            return counterparts.value.filter(u =>
                u.counterpart_types && u.counterpart_types.includes(targetType)
            );
        });

        const loadUser = async () => {
            try {
                const response = await axios.get('/api/user');
                user.value = response.data;

                if (!isEdit.value && !isSuperAdmin.value) {
                    form.value.company_id = user.value.company_id;
                }
            } catch (error) {
                console.error('Error loading user:', error);
            }
        };

        const loadCompanies = async () => {
            if (!isSuperAdmin.value) return;

            try {
                const response = await axios.get('/api/companies');
                companies.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        };

        const loadServices = async () => {
            try {
                const params = {};
                if (form.value.company_id) {
                    params.company_id = form.value.company_id;
                }
                const response = await axios.get('/api/services', { params });
                services.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading services:', error);
            }
        };

        const loadAccountingEntries = async () => {
            try {
                const params = {};
                if (form.value.company_id) {
                    params.company_id = form.value.company_id;
                }
                const response = await axios.get('/api/dictionaries/accounting-entries', { params });
                accountingEntries.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounting entries:', error);
            }
        };

        const loadPaymentTypes = async () => {
            try {
                const params = {};
                if (form.value.company_id) {
                    params.company_id = form.value.company_id;
                }
                const response = await axios.get('/api/dictionaries/payment-types', { params });
                paymentTypes.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading payment types:', error);
            }
        };

        const loadCounterparts = async () => {
            try {
                const params = {
                    per_page: 1000, // Load all counterparts
                };
                if (form.value.company_id) {
                    params.company_id = form.value.company_id;
                }
                const response = await axios.get('/api/users', { params });
                // Laravel pagination returns data in response.data.data
                const users = response.data.data || [];

                // Map users to include their counterpart type based on flags and role
                counterparts.value = users.map(u => {
                    const types = [];

                    // Check if user is intermediario
                    if (u.is_intermediario) {
                        types.push('intermediario');
                    }

                    // Check client profile flags
                    if (u.client_profile) {
                        if (u.client_profile.is_committente) {
                            types.push('committente');
                        }
                        if (u.client_profile.is_fornitore) {
                            types.push('fornitore');
                        }
                    }

                    return {
                        ...u,
                        counterpart_types: types,
                    };
                }).filter(u => u.counterpart_types.length > 0); // Only users with at least one type

            } catch (error) {
                console.error('Error loading counterparts:', error);
            }
        };

        const onTransactionTypeChange = () => {
            // Reset counterpart when transaction type changes
            form.value.counterpart_id = '';

            // Reset status when transaction type changes
            form.value.status = '';
        };

        const getRoleLabel = (types) => {
            if (!types || !Array.isArray(types)) return '';
            return types.map(type => {
                const labels = {
                    committente: 'Committente',
                    fornitore: 'Fornitore',
                    intermediario: 'Intermediario',
                };
                return labels[type] || type;
            }).join(', ');
        };

        const submitForm = async () => {
            loading.value = true;

            try {
                const payload = { ...form.value };

                // Convert empty strings to null for optional fields
                Object.keys(payload).forEach(key => {
                    if (payload[key] === '') {
                        payload[key] = null;
                    }
                });

                if (isEdit.value) {
                    await axios.put(`/api/accounting-transactions/${props.transaction.id}`, payload);
                } else {
                    await axios.post('/api/accounting-transactions', payload);
                }

                router.visit(route('easyncc.accounting-transactions.index'));
            } catch (error) {
                console.error('Error saving transaction:', error);

                if (error.response?.status === 422) {
                    const errors = error.response.data.errors;
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert('Errori di validazione:\n' + errorMessages);
                } else {
                    alert('Errore durante il salvataggio del movimento');
                }
            } finally {
                loading.value = false;
            }
        };

        // Watch company_id changes to reload dependent data
        watch(() => form.value.company_id, async (newVal) => {
            if (newVal) {
                await Promise.all([
                    loadServices(),
                    loadAccountingEntries(),
                    loadPaymentTypes(),
                    loadCounterparts(),
                ]);
            }
        });

        onMounted(async () => {
            await loadUser();

            if (isSuperAdmin.value) {
                await loadCompanies();
            }

            // Set company_id first (before populating form)
            if (isEdit.value && props.transaction) {
                form.value.company_id = props.transaction.company_id || '';
            } else if (!isSuperAdmin.value) {
                // For non-super-admin, set company_id from user
                form.value.company_id = user.value.company_id;
            }

            // Load dependent data BEFORE populating the rest of the form
            // This ensures counterparts are loaded when we set counterpart_id
            await Promise.all([
                loadServices(),
                loadAccountingEntries(),
                loadPaymentTypes(),
                loadCounterparts(),
            ]);

            // Now populate the rest of the form data
            if (isEdit.value && props.transaction) {
                form.value = {
                    company_id: props.transaction.company_id || '',
                    service_id: props.transaction.service_id || '',
                    transaction_date: props.transaction.transaction_date ? moment(props.transaction.transaction_date).format('YYYY-MM-DD') : '',
                    amount: props.transaction.amount || '',
                    transaction_type: props.transaction.transaction_type || '',
                    accounting_entry_id: props.transaction.accounting_entry_id || '',
                    installment: props.transaction.installment || '',
                    counterpart_id: props.transaction.counterpart_id || '',
                    document_number: props.transaction.document_number || '',
                    document_due_date: props.transaction.document_due_date ? moment(props.transaction.document_due_date).format('YYYY-MM-DD') : '',
                    payment_date: props.transaction.payment_date ? moment(props.transaction.payment_date).format('YYYY-MM-DD') : '',
                    payment_type: props.transaction.payment_type || '',
                    payment_reason: props.transaction.payment_reason || '',
                    iban: props.transaction.iban || '',
                    status: props.transaction.status || '',
                    notes: props.transaction.notes || '',
                };
            }
        });

        return {
            form,
            loading,
            companies,
            services,
            accountingEntries,
            paymentTypes,
            counterparts,
            filteredCounterparts,
            user,
            isEdit,
            isSuperAdmin,
            submitForm,
            onTransactionTypeChange,
            getRoleLabel,
            route: window.route,
        };
    },
};
</script>
