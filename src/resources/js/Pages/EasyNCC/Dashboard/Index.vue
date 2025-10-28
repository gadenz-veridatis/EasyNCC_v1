<template>
    <Head title="Dashboard" />

    <Layout>
        <PageHeader title="Dashboard" pageTitle="EasyNCC" />

        <BRow>
            <!-- Statistics Cards -->
            <BCol xl="3" md="6">
                <BCard no-body class="card-animate">
                    <BCardBody>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">Totale Servizi</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    {{ stats.total_services || 0 }}
                                </h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded fs-3">
                                    <i class="bx bx-car text-primary"></i>
                                </span>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <BCol xl="3" md="6">
                <BCard no-body class="card-animate">
                    <BCardBody>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">Servizi Oggi</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    {{ stats.services_today || 0 }}
                                </h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded fs-3">
                                    <i class="bx bx-calendar-check text-success"></i>
                                </span>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <BCol xl="3" md="6">
                <BCard no-body class="card-animate">
                    <BCardBody>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">Veicoli Disponibili</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    {{ stats.vehicles_available || 0 }}
                                </h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle rounded fs-3">
                                    <i class="bx bxs-car text-info"></i>
                                </span>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <BCol xl="3" md="6">
                <BCard no-body class="card-animate">
                    <BCardBody>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">Driver Disponibili</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    {{ stats.drivers_available || 0 }}
                                </h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded fs-3">
                                    <i class="bx bx-user text-warning"></i>
                                </span>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Upcoming Services -->
        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">Prossimi Servizi</h5>
                    </BCardHeader>
                    <BCardBody>
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Riferimento</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Pickup</th>
                                        <th scope="col">Destinazione</th>
                                        <th scope="col">Data/Ora</th>
                                        <th scope="col">Veicolo</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in upcomingServices" :key="service.id">
                                        <td>{{ service.reference_number || '#' + service.id }}</td>
                                        <td>{{ service.client?.name || '-' }}</td>
                                        <td class="text-truncate" style="max-width: 200px;">
                                            {{ service.pickup_address }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 200px;">
                                            {{ service.dropoff_address }}
                                        </td>
                                        <td>{{ formatDateTime(service.pickup_datetime) }}</td>
                                        <td>
                                            <span v-if="service.vehicle">
                                                {{ service.vehicle.brand }} {{ service.vehicle.model }}
                                                <br>
                                                <small class="text-muted">{{ service.vehicle.license_plate }}</small>
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span :class="`badge bg-${getStatusColor(service.status?.name)}`">
                                                {{ service.status?.name || 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.services.show', service.id)" class="btn btn-sm btn-soft-primary">
                                                Dettagli
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="upcomingServices.length === 0">
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Nessun servizio in programma
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const stats = ref({});
const upcomingServices = ref([]);

const loadDashboardData = async () => {
    try {
        const [statsRes, servicesRes] = await Promise.all([
            axios.get('/api/dashboard/stats'),
            axios.get('/api/dashboard/upcoming-services')
        ]);

        stats.value = statsRes.data;
        upcomingServices.value = servicesRes.data;
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
};

const formatDateTime = (datetime) => {
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const getStatusColor = (status) => {
    const colors = {
        'preventivo': 'warning',
        'confermato': 'info',
        'in corso': 'primary',
        'completato': 'success',
        'cancellato': 'danger',
        'no-show': 'secondary'
    };
    return colors[status] || 'secondary';
};

onMounted(() => {
    loadDashboardData();
});
</script>
