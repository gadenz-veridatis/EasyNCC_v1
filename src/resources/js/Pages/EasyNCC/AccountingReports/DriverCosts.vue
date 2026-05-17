<template>
    <Head title="Contabilità - Costi Driver" />

    <Layout>
        <PageHeader title="Costi Driver" pageTitle="Contabilità" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Sinottica Costi Driver</h5>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Month navigation -->
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-soft-primary btn-sm" @click="changeMonth(-1)" title="Mese precedente">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <input
                                    type="month"
                                    class="form-control form-control-sm"
                                    v-model="selectedMonth"
                                    @change="loadData"
                                    style="width: 170px;"
                                />
                                <button class="btn btn-soft-primary btn-sm" @click="changeMonth(1)" title="Mese successivo">
                                    <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                            <!-- Company selector for super-admin -->
                            <div v-if="isSuperAdmin" class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 text-nowrap small">Azienda:</label>
                                <select v-model="selectedCompanyId" class="form-select form-select-sm" @change="loadData" style="width: 200px;">
                                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- No data -->
                        <div v-else-if="rows.length === 0" class="text-center py-5 text-muted">
                            <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                            Nessun dato per il periodo selezionato
                        </div>

                        <!-- Data table -->
                        <div v-else class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead>
                                    <!-- Group header row -->
                                    <tr>
                                        <th rowspan="2" class="text-start align-middle table-light" style="min-width: 200px;">Driver</th>
                                        <th :colspan="costColumns.length" class="text-center bg-cost border-separator">
                                            COSTI
                                        </th>
                                        <th :colspan="revenueColumns.length" class="text-center bg-revenue">
                                            RICAVI
                                        </th>
                                    </tr>
                                    <!-- Column header row -->
                                    <tr>
                                        <th v-for="(col, idx) in costColumns" :key="col.key"
                                            class="text-end bg-cost-light"
                                            :class="{ 'border-separator': idx === costColumns.length - 1 }"
                                            style="min-width: 130px;"
                                            :title="col.description">
                                            {{ col.label }}
                                            <sup class="text-muted">{{ getColumnIndex(col.key) }}</sup>
                                        </th>
                                        <th v-for="col in revenueColumns" :key="col.key"
                                            class="text-end bg-revenue-light"
                                            style="min-width: 130px;"
                                            :title="col.description">
                                            {{ col.label }}
                                            <sup class="text-muted">{{ getColumnIndex(col.key) }}</sup>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="row in rows" :key="row.driver_id">
                                        <!-- Driver summary row -->
                                        <tr class="cursor-pointer" :class="{ 'table-active': isExpanded(row.driver_id) }"
                                            @click="toggleDetail(row.driver_id)">
                                            <td class="fw-medium">
                                                <i :class="isExpanded(row.driver_id) ? 'ri-arrow-down-s-fill' : 'ri-arrow-right-s-fill'"
                                                   class="me-1 text-muted"></i>
                                                {{ row.surname }} {{ row.name }}
                                            </td>
                                            <td v-for="(col, idx) in columnDefinitions" :key="col.key"
                                                class="text-end font-monospace"
                                                :class="{ 'border-separator': idx === costColumns.length - 1 }">
                                                {{ formatAmount(row[col.key]) }}
                                            </td>
                                        </tr>
                                        <!-- Expanded detail rows -->
                                        <tr v-if="isExpanded(row.driver_id) && detailLoading[row.driver_id]">
                                            <td :colspan="1 + columnDefinitions.length" class="text-center py-3 bg-light">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                Caricamento dettaglio...
                                            </td>
                                        </tr>
                                        <template v-if="isExpanded(row.driver_id) && !detailLoading[row.driver_id] && detailData[row.driver_id]">
                                            <tr v-if="detailData[row.driver_id].length === 0">
                                                <td :colspan="1 + columnDefinitions.length" class="text-center text-muted py-2 bg-light">
                                                    Nessun servizio trovato
                                                </td>
                                            </tr>
                                            <tr v-for="svc in detailData[row.driver_id]" :key="svc.service_id"
                                                class="bg-light">
                                                <td class="ps-4 small">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="ri-arrow-right-line text-muted" style="font-size: 0.7rem;"></i>
                                                        <div>
                                                            <a :href="'/easyncc/services/' + svc.service_id + '/edit'"
                                                               class="text-primary fw-medium text-decoration-none"
                                                               @click.stop>
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
                                                    :class="{ 'border-separator': idx === costColumns.length - 1 }">
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
                                            :class="{ 'border-separator': idx === costColumns.length - 1 }">
                                            {{ formatAmount(totals[col.key]) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Column legend -->
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
    components: {
        Head,
        Layout,
        PageHeader,
    },
    setup() {
        const loading = ref(false);
        const selectedMonth = ref(moment().format('YYYY-MM'));
        const selectedCompanyId = ref(null);
        const companies = ref([]);
        const rows = ref([]);
        const totals = ref({});
        const columnDefinitions = ref([]);
        const isSuperAdmin = ref(false);

        // Expandable row state
        const expandedDrivers = ref(new Set());
        const detailData = reactive({});
        const detailLoading = reactive({});

        const costColumnKeys = new Set(['compenso', 'carburante']);
        const costColumns = computed(() => columnDefinitions.value.filter(c => costColumnKeys.has(c.key)));
        const revenueColumns = computed(() => columnDefinitions.value.filter(c => !costColumnKeys.has(c.key)));

        const isExpanded = (driverId) => expandedDrivers.value.has(driverId);

        const toggleDetail = async (driverId) => {
            if (expandedDrivers.value.has(driverId)) {
                expandedDrivers.value.delete(driverId);
                // Force reactivity
                expandedDrivers.value = new Set(expandedDrivers.value);
                return;
            }

            expandedDrivers.value.add(driverId);
            expandedDrivers.value = new Set(expandedDrivers.value);

            // Load detail data if not already loaded
            if (!detailData[driverId]) {
                detailLoading[driverId] = true;
                try {
                    const params = { month: selectedMonth.value };
                    if (isSuperAdmin.value && selectedCompanyId.value) {
                        params.company_id = selectedCompanyId.value;
                    }
                    const response = await axios.get(`/api/accounting-reports/driver-costs/${driverId}`, { params });
                    detailData[driverId] = response.data.services || [];
                } catch (error) {
                    console.error('Error loading driver detail:', error);
                    detailData[driverId] = [];
                } finally {
                    detailLoading[driverId] = false;
                }
            }
        };

        const getColumnIndex = (key) => {
            const idx = columnDefinitions.value.findIndex(c => c.key === key);
            return idx >= 0 ? idx + 1 : '';
        };

        const formatAmount = (value) => {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('it-IT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        };

        const formatDate = (datetime) => {
            if (!datetime) return '';
            return moment(datetime).format('DD/MM/YYYY HH:mm');
        };

        const truncate = (str, maxLen) => {
            if (!str) return '';
            return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
        };

        const changeMonth = (delta) => {
            const m = moment(selectedMonth.value + '-01').add(delta, 'months');
            selectedMonth.value = m.format('YYYY-MM');
            loadData();
        };

        const clearDetails = () => {
            expandedDrivers.value = new Set();
            Object.keys(detailData).forEach(k => delete detailData[k]);
            Object.keys(detailLoading).forEach(k => delete detailLoading[k]);
        };

        const loadData = async () => {
            loading.value = true;
            clearDetails();
            try {
                const params = { month: selectedMonth.value };
                if (isSuperAdmin.value && selectedCompanyId.value) {
                    params.company_id = selectedCompanyId.value;
                }
                const response = await axios.get('/api/accounting-reports/driver-costs', { params });
                rows.value = response.data.rows || [];
                totals.value = response.data.totals || {};
                columnDefinitions.value = response.data.column_definitions || [];
            } catch (error) {
                console.error('Error loading driver costs:', error);
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

        onMounted(() => {
            loadInitialData();
        });

        return {
            loading,
            selectedMonth,
            selectedCompanyId,
            companies,
            rows,
            totals,
            columnDefinitions,
            isSuperAdmin,
            costColumns,
            revenueColumns,
            expandedDrivers,
            detailData,
            detailLoading,
            isExpanded,
            toggleDetail,
            getColumnIndex,
            formatAmount,
            formatDate,
            truncate,
            changeMonth,
            loadData,
        };
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.cursor-pointer:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}
/* Cost group header */
.bg-cost {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #842029;
    font-weight: 600;
}
.bg-cost-light {
    background-color: rgba(220, 53, 69, 0.06) !important;
}
/* Revenue group header */
.bg-revenue {
    background-color: rgba(25, 135, 84, 0.15) !important;
    color: #0f5132;
    font-weight: 600;
}
.bg-revenue-light {
    background-color: rgba(25, 135, 84, 0.06) !important;
}
/* Thick border separating cost and revenue groups */
.border-separator {
    border-right: 3px solid #6c757d !important;
}
</style>
