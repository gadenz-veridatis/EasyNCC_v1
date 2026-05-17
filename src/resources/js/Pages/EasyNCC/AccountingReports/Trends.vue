<template>
    <Head title="Contabilità - Andamento" />

    <Layout>
        <PageHeader title="Andamento" pageTitle="Contabilità" />

        <!-- Year selector -->
        <BRow class="mb-3">
            <BCol lg="12">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-soft-primary btn-sm" @click="changeYear(-1)">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>
                        <span class="fw-semibold fs-5">{{ selectedYear }}</span>
                        <button class="btn btn-soft-primary btn-sm" @click="changeYear(1)">
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
            </BCol>
        </BRow>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <template v-else>
            <!-- KPI Cards -->
            <BRow class="mb-3">
                <BCol md="3" v-for="kpi in kpiCards" :key="kpi.label">
                    <BCard no-body class="card-animate">
                        <BCardBody>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-muted text-uppercase fw-medium mb-0 small">{{ kpi.label }}</p>
                                    <h4 class="mt-2 mb-0 ff-secondary fw-semibold">
                                        <span v-if="kpi.prefix">{{ kpi.prefix }}</span>{{ kpi.formatted }}
                                    </h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 48px; height: 48px; background-color: rgba(var(--bs-primary-rgb), 0.1);">
                                        <i :class="kpi.icon" style="font-size: 1.5rem; color: var(--bs-primary);"></i>
                                    </div>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Month and Quarter comparisons -->
            <BRow class="mb-3">
                <BCol lg="6">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                {{ monthComparison.current_label }}
                                <span class="text-muted fw-normal">vs</span>
                                {{ monthComparison.previous_label }}
                            </h6>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <table class="table table-sm table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th class="text-end">Corrente</th>
                                        <th class="text-end">Precedente</th>
                                        <th class="text-end">Var.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonRows(monthComparison)" :key="row.label">
                                        <td class="fw-medium">{{ row.label }}</td>
                                        <td class="text-end font-monospace">{{ row.current }}</td>
                                        <td class="text-end font-monospace text-muted">{{ row.previous }}</td>
                                        <td class="text-end fw-semibold" :class="row.variationClass">
                                            {{ row.variation }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="isPartialMonth" class="px-3 pb-2">
                                <small class="text-muted fst-italic">* Dati parziali al {{ todayFormatted }}</small>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
                <BCol lg="6">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                {{ quarterComparison.current_label }}
                                <span class="text-muted fw-normal">vs</span>
                                {{ quarterComparison.previous_label }}
                            </h6>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <table class="table table-sm table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th class="text-end">Corrente</th>
                                        <th class="text-end">Precedente</th>
                                        <th class="text-end">Var.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonRows(quarterComparison)" :key="row.label">
                                        <td class="fw-medium">{{ row.label }}</td>
                                        <td class="text-end font-monospace">{{ row.current }}</td>
                                        <td class="text-end font-monospace text-muted">{{ row.previous }}</td>
                                        <td class="text-end fw-semibold" :class="row.variationClass">
                                            {{ row.variation }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="isPartialQuarter" class="px-3 pb-2">
                                <small class="text-muted fst-italic">* Dati parziali al {{ todayFormatted }}</small>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Daily chart -->
            <BRow class="mb-3">
                <BCol lg="12">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0">Venduto e Incassato - Giornaliero</h6>
                        </BCardHeader>
                        <BCardBody>
                            <apexchart
                                v-if="dailyChartOptions"
                                type="area"
                                height="350"
                                :options="dailyChartOptions"
                                :series="dailyChartSeries"
                            />
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Cumulative chart -->
            <BRow>
                <BCol lg="12">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0">Venduto e Incassato - Cumulativo</h6>
                        </BCardHeader>
                        <BCardBody>
                            <apexchart
                                v-if="cumulativeChartOptions"
                                type="line"
                                height="250"
                                :options="cumulativeChartOptions"
                                :series="cumulativeChartSeries"
                            />
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>
        </template>
    </Layout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

export default {
    components: { Head, Layout, PageHeader },
    setup() {
        const loading = ref(false);
        const selectedYear = ref(new Date().getFullYear());
        const selectedCompanyId = ref(null);
        const companies = ref([]);
        const isSuperAdmin = ref(false);

        const kpis = ref({ total_revenue: 0, services_count: 0, collected: 0, to_collect: 0 });
        const monthComparison = ref({ current_label: '', previous_label: '', current: {}, previous: {} });
        const quarterComparison = ref({ current_label: '', previous_label: '', current: {}, previous: {} });
        const dailyData = ref([]);

        const todayFormatted = moment().format('DD/MM/YYYY');
        const isPartialMonth = computed(() => selectedYear.value === new Date().getFullYear());
        const isPartialQuarter = computed(() => selectedYear.value === new Date().getFullYear());

        // KPI cards
        const kpiCards = computed(() => [
            { label: 'Venduto', formatted: formatEur(kpis.value.total_revenue), prefix: '', icon: 'ri-money-euro-circle-line' },
            { label: 'N. Servizi', formatted: kpis.value.services_count.toLocaleString('it-IT'), prefix: '', icon: 'ri-car-line' },
            { label: 'Incassato', formatted: formatEur(kpis.value.collected), prefix: '', icon: 'ri-checkbox-circle-line' },
            { label: 'Da Incassare', formatted: formatEur(kpis.value.to_collect), prefix: '', icon: 'ri-time-line' },
        ]);

        // Comparison table rows
        const comparisonRows = (comp) => {
            if (!comp.current || !comp.previous) return [];
            const rows = [
                { key: 'revenue', label: 'Venduto' },
                { key: 'services_count', label: 'N. Servizi' },
                { key: 'collected', label: 'Incassato' },
                { key: 'ticket_medio', label: 'Ticket Medio' },
            ];
            return rows.map(r => {
                const curr = comp.current[r.key] || 0;
                const prev = comp.previous[r.key] || 0;
                const isCount = r.key === 'services_count';
                const variation = prev > 0 ? ((curr - prev) / prev * 100) : (curr > 0 ? 100 : 0);
                const sign = variation > 0 ? '+' : '';
                return {
                    label: r.label,
                    current: isCount ? curr.toLocaleString('it-IT') : formatEur(curr),
                    previous: isCount ? prev.toLocaleString('it-IT') : formatEur(prev),
                    variation: prev === 0 && curr === 0 ? '-' : sign + variation.toFixed(1) + '%',
                    variationClass: variation > 0 ? 'text-success' : variation < 0 ? 'text-danger' : 'text-muted',
                };
            });
        };

        // Chart options - shared
        const chartLocale = {
            name: 'it',
            options: {
                months: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                shortMonths: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                days: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
                shortDays: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
            }
        };

        // Daily chart
        const dailyChartSeries = computed(() => {
            if (!dailyData.value.length) return [];
            return [
                {
                    name: 'Venduto',
                    data: dailyData.value.map(d => ({ x: d.day, y: parseFloat(d.revenue) || 0 })),
                },
                {
                    name: 'Incassato',
                    data: dailyData.value.map(d => ({ x: d.day, y: parseFloat(d.collected) || 0 })),
                },
            ];
        });

        const dailyChartOptions = computed(() => {
            if (!dailyData.value.length) return null;
            return {
                chart: {
                    type: 'area',
                    height: 350,
                    zoom: { enabled: true },
                    toolbar: { show: true },
                    locales: [chartLocale],
                    defaultLocale: 'it',
                    id: 'daily-chart',
                    group: 'trends',
                },
                colors: ['#0ab39c', '#405189'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 90, 100] },
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'dd MMM',
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatEurShort(val),
                    },
                },
                tooltip: {
                    x: { format: 'dd/MM/yyyy' },
                    y: {
                        formatter: (val) => formatEur(val),
                    },
                },
                legend: { position: 'top' },
            };
        });

        // Cumulative chart
        const cumulativeChartSeries = computed(() => {
            if (!dailyData.value.length) return [];
            let cumRevenue = 0;
            let cumCollected = 0;
            const revData = [];
            const colData = [];
            for (const d of dailyData.value) {
                cumRevenue += parseFloat(d.revenue) || 0;
                cumCollected += parseFloat(d.collected) || 0;
                revData.push({ x: d.day, y: Math.round(cumRevenue * 100) / 100 });
                colData.push({ x: d.day, y: Math.round(cumCollected * 100) / 100 });
            }
            return [
                { name: 'Venduto Cumulativo', data: revData },
                { name: 'Incassato Cumulativo', data: colData },
            ];
        });

        const cumulativeChartOptions = computed(() => {
            if (!dailyData.value.length) return null;
            return {
                chart: {
                    type: 'line',
                    height: 250,
                    zoom: { enabled: true },
                    toolbar: { show: true },
                    locales: [chartLocale],
                    defaultLocale: 'it',
                    id: 'cumulative-chart',
                    group: 'trends',
                },
                colors: ['#0ab39c', '#405189'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: [3, 3] },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'dd MMM',
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatEurShort(val),
                    },
                },
                tooltip: {
                    x: { format: 'dd/MM/yyyy' },
                    y: {
                        formatter: (val) => formatEur(val),
                    },
                },
                legend: { position: 'top' },
            };
        });

        // Formatting helpers
        function formatEur(val) {
            const num = parseFloat(val) || 0;
            return num.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20AC';
        }

        function formatEurShort(val) {
            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
            if (val >= 1000) return (val / 1000).toFixed(1) + 'k';
            return val.toFixed(0) + ' \u20AC';
        }

        const changeYear = (delta) => {
            selectedYear.value += delta;
            loadData();
        };

        const loadData = async () => {
            loading.value = true;
            try {
                const params = { year: selectedYear.value };
                if (isSuperAdmin.value && selectedCompanyId.value) {
                    params.company_id = selectedCompanyId.value;
                }
                const response = await axios.get('/api/accounting-reports/trends', { params });
                kpis.value = response.data.kpis || {};
                monthComparison.value = response.data.month_comparison || {};
                quarterComparison.value = response.data.quarter_comparison || {};
                dailyData.value = response.data.daily || [];
            } catch (error) {
                console.error('Error loading trends:', error);
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
            loading, selectedYear, selectedCompanyId, companies, isSuperAdmin,
            kpis, kpiCards, monthComparison, quarterComparison,
            dailyData, todayFormatted, isPartialMonth, isPartialQuarter,
            comparisonRows, changeYear, loadData,
            dailyChartOptions, dailyChartSeries,
            cumulativeChartOptions, cumulativeChartSeries,
        };
    },
};
</script>
