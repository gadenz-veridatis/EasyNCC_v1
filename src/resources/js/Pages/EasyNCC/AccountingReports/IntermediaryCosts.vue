<template>
    <Head title="Contabilità - Costi Intermediari" />

    <Layout>
        <PageHeader title="Costi Intermediari" pageTitle="Contabilità" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Sinottica Costi Intermediari</h5>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-soft-primary btn-sm" @click="changeMonth(-1)" title="Mese precedente">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <input type="month" class="form-control form-control-sm" v-model="selectedMonth" @change="loadData" style="width: 170px;" />
                                <button class="btn btn-soft-primary btn-sm" @click="changeMonth(1)" title="Mese successivo">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                            <div v-if="isSuperAdmin" class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 text-nowrap small">Azienda:</label>
                                <select v-model="selectedCompanyId" class="form-select form-select-sm" @change="loadData" style="width: 200px;">
                                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <div v-else-if="rows.length === 0" class="text-center py-5 text-muted">
                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                            Nessun dato per il periodo selezionato
                        </div>

                        <div v-else class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="text-start align-middle table-light" style="min-width: 220px;">Intermediario</th>
                                        <th :colspan="commissionColumns.length" class="text-center bg-commission border-separator">
                                            COMMISSIONI
                                        </th>
                                        <th :colspan="paymentColumns.length" class="text-center bg-payment">
                                            PAGAMENTI
                                        </th>
                                    </tr>
                                    <tr>
                                        <th v-for="(col, idx) in commissionColumns" :key="col.key"
                                            class="text-end bg-commission-light"
                                            :class="{ 'border-separator': idx === commissionColumns.length - 1 }"
                                            style="min-width: 130px;" :title="col.description">
                                            {{ col.label }}
                                            <sup class="text-muted">{{ getColumnIndex(col.key) }}</sup>
                                        </th>
                                        <th v-for="col in paymentColumns" :key="col.key"
                                            class="text-end bg-payment-light"
                                            style="min-width: 130px;" :title="col.description">
                                            {{ col.label }}
                                            <sup class="text-muted">{{ getColumnIndex(col.key) }}</sup>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="row in rows" :key="row.intermediary_id">
                                        <tr class="cursor-pointer" :class="{ 'table-active': isExpanded(row.intermediary_id) }"
                                            @click="toggleDetail(row.intermediary_id)">
                                            <td class="fw-medium">
                                                <i :class="isExpanded(row.intermediary_id) ? 'ri-arrow-down-s-fill' : 'ri-arrow-right-s-fill'"
                                                   class="me-1 text-muted"></i>
                                                {{ row.business_name || (row.surname + ' ' + row.name) }}
                                            </td>
                                            <td v-for="(col, idx) in columnDefinitions" :key="col.key"
                                                class="text-end font-monospace"
                                                :class="{ 'border-separator': idx === commissionColumns.length - 1 }">
                                                {{ formatAmount(row[col.key]) }}
                                            </td>
                                        </tr>
                                        <tr v-if="isExpanded(row.intermediary_id) && detailLoading[row.intermediary_id]">
                                            <td :colspan="1 + columnDefinitions.length" class="text-center py-3 bg-light">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                Caricamento dettaglio...
                                            </td>
                                        </tr>
                                        <template v-if="isExpanded(row.intermediary_id) && !detailLoading[row.intermediary_id] && detailData[row.intermediary_id]">
                                            <tr v-if="detailData[row.intermediary_id].length === 0">
                                                <td :colspan="1 + columnDefinitions.length" class="text-center text-muted py-2 bg-light">
                                                    Nessun servizio trovato
                                                </td>
                                            </tr>
                                            <tr v-for="svc in detailData[row.intermediary_id]" :key="svc.service_id" class="bg-light">
                                                <td class="ps-4 small">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="ri-arrow-right-line text-muted" style="font-size: 0.7rem;"></i>
                                                        <div>
                                                            <a :href="'/easyncc/services/' + svc.service_id + '/edit'"
                                                               class="text-primary fw-medium text-decoration-none" @click.stop>
                                                                {{ svc.reference_number || '#' + svc.service_id }}
                                                            </a>
                                                            <span class="text-muted ms-2">{{ formatDate(svc.pickup_datetime) }}</span>
                                                            <br>
                                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                                {{ svc.client_surname }} {{ svc.client_name }}
                                                                <span v-if="svc.pickup_address"> &middot; {{ truncate(svc.pickup_address, 30) }}</span>
                                                                <span v-if="svc.dropoff_address"> &rarr; {{ truncate(svc.dropoff_address, 30) }}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td v-for="(col, idx) in columnDefinitions" :key="col.key"
                                                    class="text-end font-monospace small"
                                                    :class="{ 'border-separator': idx === commissionColumns.length - 1 }">
                                                    {{ formatAmount(svc[col.key]) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>TOTALI</td>
                                        <td v-for="(col, idx) in columnDefinitions" :key="col.key"
                                            class="text-end font-monospace"
                                            :class="{ 'border-separator': idx === commissionColumns.length - 1 }">
                                            {{ formatAmount(totals[col.key]) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div v-if="rows.length > 0" class="mt-3 border-top pt-3">
                            <p class="text-muted small mb-1 fw-semibold">Legenda colonne:</p>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li v-for="col in columnDefinitions" :key="col.key" class="mb-1">
                                    <sup class="fw-bold">{{ getColumnIndex(col.key) }}</sup>
                                    <strong>{{ col.label }}</strong>: {{ col.description }}
                                </li>
                            </ul>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

export default {
    components: { Head, Layout, PageHeader },
    setup() {
        const loading = ref(false);
        const selectedMonth = ref(moment().format('YYYY-MM'));
        const selectedCompanyId = ref(null);
        const companies = ref([]);
        const rows = ref([]);
        const totals = ref({});
        const columnDefinitions = ref([]);
        const isSuperAdmin = ref(false);
        const expandedItems = ref(new Set());
        const detailData = reactive({});
        const detailLoading = reactive({});

        const commissionColumnKeys = new Set(['totale_commissioni']);
        const commissionColumns = computed(() => columnDefinitions.value.filter(c => commissionColumnKeys.has(c.key)));
        const paymentColumns = computed(() => columnDefinitions.value.filter(c => !commissionColumnKeys.has(c.key)));

        const isExpanded = (id) => expandedItems.value.has(id);

        const toggleDetail = async (id) => {
            if (expandedItems.value.has(id)) {
                expandedItems.value.delete(id);
                expandedItems.value = new Set(expandedItems.value);
                return;
            }
            expandedItems.value.add(id);
            expandedItems.value = new Set(expandedItems.value);

            if (!detailData[id]) {
                detailLoading[id] = true;
                try {
                    const params = { month: selectedMonth.value };
                    if (isSuperAdmin.value && selectedCompanyId.value) params.company_id = selectedCompanyId.value;
                    const response = await axios.get(`/api/accounting-reports/intermediary-costs/${id}`, { params });
                    detailData[id] = response.data.services || [];
                } catch (error) {
                    console.error('Error loading detail:', error);
                    detailData[id] = [];
                } finally {
                    detailLoading[id] = false;
                }
            }
        };

        const getColumnIndex = (key) => { const idx = columnDefinitions.value.findIndex(c => c.key === key); return idx >= 0 ? idx + 1 : ''; };
        const formatAmount = (value) => { const num = parseFloat(value) || 0; return num.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        const formatDate = (datetime) => { if (!datetime) return ''; return moment(datetime).format('DD/MM/YYYY HH:mm'); };
        const truncate = (str, maxLen) => { if (!str) return ''; return str.length > maxLen ? str.substring(0, maxLen) + '...' : str; };

        const changeMonth = (delta) => {
            const m = moment(selectedMonth.value + '-01').add(delta, 'months');
            selectedMonth.value = m.format('YYYY-MM');
            loadData();
        };

        const clearDetails = () => {
            expandedItems.value = new Set();
            Object.keys(detailData).forEach(k => delete detailData[k]);
            Object.keys(detailLoading).forEach(k => delete detailLoading[k]);
        };

        const loadData = async () => {
            loading.value = true;
            clearDetails();
            try {
                const params = { month: selectedMonth.value };
                if (isSuperAdmin.value && selectedCompanyId.value) params.company_id = selectedCompanyId.value;
                const response = await axios.get('/api/accounting-reports/intermediary-costs', { params });
                rows.value = response.data.rows || [];
                totals.value = response.data.totals || {};
                columnDefinitions.value = response.data.column_definitions || [];
            } catch (error) {
                console.error('Error loading data:', error);
                rows.value = [];
                totals.value = {};
            } finally {
                loading.value = false;
            }
        };

        const loadInitialData = async () => {
            try {
                const userResponse = await axios.get('/api/user');
                const user = userResponse.data;
                isSuperAdmin.value = user.role === 'super-admin';
                selectedCompanyId.value = user.company_id;
                if (isSuperAdmin.value) {
                    const companiesResponse = await axios.get('/api/companies');
                    companies.value = companiesResponse.data.data || companiesResponse.data || [];
                }
                await loadData();
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
        };

        onMounted(() => { loadInitialData(); });

        return {
            loading, selectedMonth, selectedCompanyId, companies, rows, totals, columnDefinitions, isSuperAdmin,
            commissionColumns, paymentColumns, expandedItems, detailData, detailLoading,
            isExpanded, toggleDetail, getColumnIndex, formatAmount, formatDate, truncate, changeMonth, loadData,
        };
    },
};
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: rgba(var(--bs-primary-rgb), 0.05) !important; }
.bg-commission { background-color: rgba(255, 193, 7, 0.20) !important; color: #664d03; font-weight: 600; }
.bg-commission-light { background-color: rgba(255, 193, 7, 0.06) !important; }
.bg-payment { background-color: rgba(13, 110, 253, 0.15) !important; color: #084298; font-weight: 600; }
.bg-payment-light { background-color: rgba(13, 110, 253, 0.06) !important; }
.border-separator { border-right: 3px solid #6c757d !important; }
</style>
