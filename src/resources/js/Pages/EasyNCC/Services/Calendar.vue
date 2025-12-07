<template>
    <Head title="Calendario Servizi" />

    <Layout>
        <PageHeader title="Calendario Servizi" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Calendario Servizi</h5>
                        <Link :href="route('easyncc.services.create')" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus me-1"></i>
                            Nuovo Servizio
                        </Link>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div v-else id="calendar-container" style="min-height: 600px;">
                            <div id="calendar"></div>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Service Detail Modal -->
        <BModal
            v-model="showDetailModal"
            :title="selectedService?.reference_number || 'Dettagli Servizio'"
            size="lg"
        >
            <div v-if="selectedService" class="row">
                <div class="col-md-6 mb-3">
                    <strong>Cliente:</strong>
                    <p>{{ selectedService.client?.name || '-' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Stato:</strong>
                    <p>
                        <span :class="`badge bg-${getStatusColor(selectedService.status?.name)}`">
                            {{ selectedService.status?.name || 'N/A' }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Pickup:</strong>
                    <p>{{ selectedService.pickup_address }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Dropoff:</strong>
                    <p>{{ selectedService.dropoff_address }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Data/Ora Pickup:</strong>
                    <p>{{ formatDateTime(selectedService.pickup_datetime) }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Veicolo:</strong>
                    <p v-if="selectedService.vehicle">
                        {{ selectedService.vehicle.brand }} {{ selectedService.vehicle.model }}
                        <br>
                        <small class="text-muted">{{ selectedService.vehicle.license_plate }}</small>
                    </p>
                    <p v-else>-</p>
                </div>
                <div class="col-md-12 mb-3" v-if="selectedService.notes">
                    <strong>Note:</strong>
                    <p>{{ selectedService.notes }}</p>
                </div>
            </div>

            <template #footer>
                <Link
                    v-if="selectedService?.id"
                    :href="route('easyncc.services.show', selectedService.id)"
                    class="btn btn-primary btn-sm"
                >
                    Visualizza Dettagli
                </Link>
                <Link
                    v-if="selectedService?.id"
                    :href="route('easyncc.services.edit', selectedService.id)"
                    class="btn btn-info btn-sm ms-2"
                >
                    Modifica
                </Link>
                <button type="button" class="btn btn-secondary btn-sm ms-2" @click="showDetailModal = false">
                    Chiudi
                </button>
            </template>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

// FullCalendar instance
let calendarInstance;

const loading = ref(true);
const error = ref('');
const services = ref([]);
const showDetailModal = ref(false);
const selectedService = ref(null);

const loadServices = async (start = null, end = null) => {
    loading.value = true;
    error.value = '';

    try {
        // If no date range specified, load next 3 months
        if (!start || !end) {
            start = moment().startOf('month').format('YYYY-MM-DD');
            end = moment().add(3, 'months').endOf('month').format('YYYY-MM-DD');
        }

        const response = await axios.get('/api/services', {
            params: {
                pickup_date_from: start,
                pickup_date_to: end,
                per_page: 1000 // High limit for calendar view
            }
        });
        services.value = response.data.data || [];
        initializeCalendar();
    } catch (err) {
        error.value = 'Errore nel caricamento dei servizi';
        console.error('Error loading services:', err);
    } finally {
        loading.value = false;
    }
};

const initializeCalendar = async () => {
    try {
        // Dynamically import FullCalendar
        const { Calendar: CalendarCore } = await import('@fullcalendar/core');
        const dayGridPlugin = (await import('@fullcalendar/daygrid')).default;
        const timeGridPlugin = (await import('@fullcalendar/timegrid')).default;
        const interactionPlugin = (await import('@fullcalendar/interaction')).default;

        const events = services.value.map(service => ({
            id: service.id,
            title: service.reference_number || 'Servizio #' + service.id,
            start: service.pickup_datetime,
            end: service.dropoff_datetime,
            backgroundColor: getEventColor(service.status?.name),
            borderColor: getEventColor(service.status?.name),
            extendedProps: {
                service: service
            }
        }));

        // Initialize calendar
        const calendarEl = document.getElementById('calendar');
        if (calendarEl && !calendarInstance) {
            calendarInstance = new CalendarCore(calendarEl, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                initialView: 'dayGridMonth',
                locale: 'it',
                events: events,
                eventClick: handleEventClick,
                editable: false,
                displayEventTime: true
            });

            calendarInstance.render();
        }
    } catch (err) {
        console.error('Error initializing calendar:', err);
        error.value = 'Errore nell\'inizializzazione del calendario';
    }
};

const handleEventClick = (info) => {
    selectedService.value = info.event.extendedProps.service;
    showDetailModal.value = true;
};

const getEventColor = (status) => {
    const colors = {
        'preventivo': '#ffc107',
        'confermato': '#17a2b8',
        'in corso': '#007bff',
        'completato': '#28a745',
        'cancellato': '#dc3545',
        'no-show': '#6c757d'
    };
    return colors[status] || '#6c757d';
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
    loadServices();
});
</script>

<style scoped>
#calendar-container {
    padding: 20px 0;
}

:deep(.fc) {
    font-family: inherit;
}

:deep(.fc-button-primary) {
    background-color: #007bff;
    border-color: #007bff;
}

:deep(.fc-button-primary:not(:disabled).fc-button-active) {
    background-color: #0056b3;
    border-color: #0056b3;
}

:deep(.fc-button-primary:hover:not(:disabled)) {
    background-color: #0056b3;
    border-color: #0056b3;
}

:deep(.fc-daygrid-day.fc-day-today) {
    background-color: #f8f9fa;
}
</style>
