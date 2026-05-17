<template>
    <Head title="Dashboard" />

    <Layout :collapsed-sidebar="true">
        <PageHeader title="Dashboard" pageTitle="EasyNCC" />

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <template v-else>
            <!-- KPI Cards -->
            <BRow>
                <BCol xl="3" md="6" class="mb-3">
                    <BCard no-body class="card-animate">
                        <BCardBody>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Servizi Oggi</p>
                                    <h3 class="mt-2 mb-0">{{ data.kpi?.services_today || 0 }}</h3>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle rounded fs-3">
                                        <i class="ri-car-line text-primary"></i>
                                    </span>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
                <BCol xl="3" md="6" class="mb-3">
                    <BCard no-body class="card-animate">
                        <BCardBody>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Task Da Completare</p>
                                    <h3 class="mt-2 mb-0">{{ data.kpi?.tasks_pending || 0 }}</h3>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle rounded fs-3">
                                        <i class="ri-task-line text-warning"></i>
                                    </span>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
                <BCol xl="3" md="6" class="mb-3">
                    <BCard no-body class="card-animate">
                        <BCardBody>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Da Incassare</p>
                                    <h3 class="mt-2 mb-0">{{ data.kpi?.payments_pending || 0 }}</h3>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle rounded fs-3">
                                        <i class="ri-money-euro-circle-line text-info"></i>
                                    </span>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
                <BCol xl="3" md="6" class="mb-3">
                    <BCard no-body class="card-animate">
                        <BCardBody>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Documenti in Scadenza</p>
                                    <h3 class="mt-2 mb-0">{{ data.kpi?.expiring_documents || 0 }}</h3>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle rounded fs-3">
                                        <i class="ri-alarm-warning-line text-danger"></i>
                                    </span>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Row 2: Services + Tasks -->
            <BRow>
                <!-- Services today/tomorrow -->
                <BCol xl="8" class="mb-3">
                    <BCard no-body>
                        <BCardHeader class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="ri-calendar-line me-2"></i>Servizi Oggi e Domani</h6>
                            <Link href="/easyncc/services" class="btn btn-sm btn-soft-primary">Vedi tutti</Link>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <div v-if="data.services_today_tomorrow && data.services_today_tomorrow.length > 0" class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ora</th>
                                            <th>Tipo</th>
                                            <th>Passeggero</th>
                                            <th>Driver</th>
                                            <th>Veicolo</th>
                                            <th>Stato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(service, idx) in data.services_today_tomorrow" :key="service.id">
                                            <!-- Day separator -->
                                            <tr v-if="idx === 0 || service.is_today !== data.services_today_tomorrow[idx - 1].is_today" class="table-secondary">
                                                <td colspan="6" class="fw-bold small py-1 px-3">
                                                    {{ service.is_today ? 'Oggi' : 'Domani' }}
                                                </td>
                                            </tr>
                                            <tr style="cursor: pointer;" @click="goToService(service.id)">
                                                <td class="fw-bold">{{ formatTime(service.pickup_datetime) }}</td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary">{{ service.service_type || '-' }}</span>
                                                </td>
                                                <td>{{ service.passenger_name || '-' }}</td>
                                                <td>
                                                    <span
                                                        v-for="driver in service.drivers"
                                                        :key="driver.id"
                                                        class="badge me-1"
                                                        :style="{ backgroundColor: driver.color, color: '#fff' }"
                                                    >{{ driver.name }}</span>
                                                    <span v-if="!service.drivers?.length" class="text-muted small">-</span>
                                                </td>
                                                <td>
                                                    <span v-if="service.vehicle" class="small">{{ service.vehicle.license_plate }}</span>
                                                    <span v-else class="text-muted small">-</span>
                                                </td>
                                                <td>
                                                    <span v-if="service.status" class="badge" :style="{ backgroundColor: service.status.color_code || '#6c757d', color: '#fff' }">
                                                        {{ service.status.name }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center text-muted py-4">
                                <i class="ri-calendar-check-line fs-2 d-block mb-2"></i>
                                Nessun servizio programmato per oggi e domani
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>

                <!-- Tasks due soon -->
                <BCol xl="4" class="mb-3">
                    <BCard no-body>
                        <BCardHeader class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="ri-task-line me-2"></i>Task in Scadenza</h6>
                            <Link href="/easyncc/tasks" class="btn btn-sm btn-soft-warning">Vedi tutti</Link>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <div v-if="data.tasks_due_soon && data.tasks_due_soon.length > 0">
                                <div
                                    v-for="task in data.tasks_due_soon"
                                    :key="task.id"
                                    class="d-flex align-items-start gap-2 px-3 py-2 border-bottom"
                                    style="cursor: pointer;"
                                    @click="goToTask(task.id)"
                                >
                                    <div class="flex-grow-1">
                                        <div class="fw-medium small" style="line-height: 1.3;">{{ truncate(task.name, 60) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            {{ task.assigned_users?.join(', ') || 'Non assegnato' }}
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <span v-if="task.due_date" :class="getDueDateClass(task.due_date)" style="font-size: 0.75rem;">
                                            {{ formatDate(task.due_date) }}
                                        </span>
                                        <span v-else class="text-muted" style="font-size: 0.75rem;">Nessuna</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-muted py-4">
                                <i class="ri-check-double-line fs-2 d-block mb-2"></i>
                                Nessun task in scadenza
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Row 3: Accounting + Unavailabilities -->
            <BRow>
                <!-- Accounting summary -->
                <BCol xl="6" class="mb-3">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0"><i class="ri-money-euro-box-line me-2"></i>Riepilogo Contabile</h6>
                        </BCardHeader>
                        <BCardBody v-if="data.accounting_summary">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th class="text-end">Mese Corrente</th>
                                        <th class="text-end">Mese Precedente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-medium">Ricavi</td>
                                        <td class="text-end text-success fw-bold">&euro; {{ formatCurrency(data.accounting_summary.current_month.revenue) }}</td>
                                        <td class="text-end text-muted">&euro; {{ formatCurrency(data.accounting_summary.previous_month.revenue) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">Costi</td>
                                        <td class="text-end text-danger fw-bold">&euro; {{ formatCurrency(data.accounting_summary.current_month.costs) }}</td>
                                        <td class="text-end text-muted">&euro; {{ formatCurrency(data.accounting_summary.previous_month.costs) }}</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td class="fw-bold">Margine</td>
                                        <td class="text-end fw-bold" :class="data.accounting_summary.current_month.margin >= 0 ? 'text-success' : 'text-danger'">
                                            &euro; {{ formatCurrency(data.accounting_summary.current_month.margin) }}
                                        </td>
                                        <td class="text-end fw-bold text-muted">
                                            &euro; {{ formatCurrency(data.accounting_summary.previous_month.margin) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </BCardBody>
                    </BCard>
                </BCol>

                <!-- Unavailabilities -->
                <BCol xl="6" class="mb-3">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0"><i class="ri-user-unfollow-line me-2"></i>Non Disponibili Oggi</h6>
                        </BCardHeader>
                        <BCardBody>
                            <div v-if="hasUnavailabilities">
                                <!-- Driver -->
                                <div v-if="data.unavailabilities_today?.drivers?.length > 0" class="mb-3">
                                    <div class="text-muted small fw-bold mb-2">Driver</div>
                                    <div v-for="(item, idx) in data.unavailabilities_today.drivers" :key="'d' + idx" class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="ri-user-line me-1 text-danger"></i>
                                            <span class="fw-medium">{{ item.driver_name }}</span>
                                            <span class="text-muted small ms-1">{{ item.reason }}</span>
                                        </div>
                                        <span class="text-muted small">{{ item.start_date }} - {{ item.end_date }}</span>
                                    </div>
                                </div>
                                <!-- Vehicles -->
                                <div v-if="data.unavailabilities_today?.vehicles?.length > 0">
                                    <div class="text-muted small fw-bold mb-2">Veicoli</div>
                                    <div v-for="(item, idx) in data.unavailabilities_today.vehicles" :key="'v' + idx" class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="ri-car-line me-1 text-secondary"></i>
                                            <span class="fw-medium">{{ item.vehicle_plate }}</span>
                                            <span class="text-muted small ms-1">{{ item.vehicle_label }} - {{ item.reason }}</span>
                                        </div>
                                        <span class="text-muted small">{{ item.start_date }} - {{ item.end_date }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-3">
                                <i class="ri-check-line text-success fs-2 d-block mb-2"></i>
                                <span class="text-success">Tutte le risorse disponibili</span>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>

            <!-- Row 4: Document expirations (driver + vehicle attachments) -->
            <BRow v-if="data.document_expirations && data.document_expirations.length > 0">
                <BCol lg="12" class="mb-3">
                    <BCard no-body>
                        <BCardHeader>
                            <h6 class="card-title mb-0"><i class="ri-alarm-warning-line me-2"></i>Scadenze Documenti Driver e Veicoli</h6>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Scadenza</th>
                                            <th>Tipo</th>
                                            <th>Documento</th>
                                            <th>Intestatario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(doc, idx) in data.document_expirations" :key="idx" :class="doc.is_expired ? 'table-danger' : getDeadlineRowClass(doc.expiration_date)">
                                            <td class="fw-bold" :class="doc.is_expired ? 'text-danger' : ''">
                                                {{ formatDate(doc.expiration_date) }}
                                                <span v-if="doc.is_expired" class="badge bg-danger ms-1" style="font-size: 0.6rem;">SCADUTO</span>
                                            </td>
                                            <td>
                                                <span v-if="doc.type === 'driver'" class="badge bg-primary-subtle text-primary">
                                                    <i class="ri-user-line me-1"></i>Driver
                                                </span>
                                                <span v-else class="badge bg-secondary-subtle text-secondary">
                                                    <i class="ri-car-line me-1"></i>Veicolo
                                                </span>
                                            </td>
                                            <td>{{ doc.document_type }}</td>
                                            <td>
                                                <span class="fw-medium">{{ doc.entity_name }}</span>
                                                <span v-if="doc.entity_label" class="text-muted small ms-1">{{ doc.entity_label }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>
        </template>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const loading = ref(true);
const data = ref({});

const hasUnavailabilities = computed(() => {
    return (data.value.unavailabilities_today?.drivers?.length > 0) ||
           (data.value.unavailabilities_today?.vehicles?.length > 0);
});

const loadDashboard = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/dashboard');
        data.value = response.data;
    } catch (err) {
        console.error('Error loading dashboard:', err);
    } finally {
        loading.value = false;
    }
};

const formatTime = (datetime) => {
    if (!datetime) return '-';
    return moment.utc(datetime).format('HH:mm');
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment.utc(date).format('DD/MM/YYYY');
};

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2).replace('.', ',');
};

const truncate = (text, maxLength) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getDueDateClass = (dueDate) => {
    if (!dueDate) return '';
    const today = moment().startOf('day');
    const due = moment.utc(dueDate).startOf('day');
    const diff = due.diff(today, 'days');
    if (diff < 0) return 'text-danger fw-bold';
    if (diff <= 3) return 'text-warning fw-bold';
    return '';
};

const getDeadlineRowClass = (dueDate) => {
    if (!dueDate) return '';
    const today = moment().startOf('day');
    const due = moment.utc(dueDate).startOf('day');
    const diff = due.diff(today, 'days');
    if (diff <= 3) return 'table-warning';
    return '';
};

const goToService = (id) => {
    window.location.href = `/easyncc/services/${id}/edit`;
};

const goToTask = (id) => {
    window.location.href = `/easyncc/tasks/${id}/edit`;
};

onMounted(() => {
    loadDashboard();
});
</script>

<style scoped>
.card-animate {
    transition: transform 0.15s;
}
.card-animate:hover {
    transform: translateY(-3px);
}
</style>
