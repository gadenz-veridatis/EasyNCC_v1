<template>
    <Head title="Calendario Servizi" />

    <Layout :collapsed-sidebar="true">
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

        <!-- Hover Popover (Quick Info) -->
        <div
            v-if="showHoverPopover && hoverService"
            class="hover-popover"
            :style="hoverPopoverStyle"
        >
            <div class="hover-popover-content">
                <div class="fw-bold">{{ getHoverEventTitle(hoverService) }}</div>
            </div>
        </div>

        <!-- Service Detail Popover (Click) -->
        <div
            v-if="showDetailPopover && selectedService"
            ref="popoverEl"
            class="service-detail-popover"
            :style="popoverStyle"
        >
            <div class="popover-header d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <div class="fw-bold">#{{ selectedService.reference_number || selectedService.id }}</div>
                    <div class="small mt-1">{{ formatTime(selectedService.pickup_datetime) }} - {{ selectedService.contact_name }} - {{ selectedService.passenger_count }} pax</div>
                    <div class="small" v-if="selectedService.client">{{ selectedService.client.name }} {{ selectedService.client.surname }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" @click="closePopover"></button>
            </div>
            <div class="popover-body">
                <!-- Pickup -->
                <div class="mb-2">
                    <div class="text-muted small">Pickup</div>
                    <div class="fw-bold small">{{ formatDateTime(selectedService.pickup_datetime) }}</div>
                    <div class="small text-truncate">{{ selectedService.pickup_address }}</div>
                </div>

                <!-- Dropoff -->
                <div class="mb-2">
                    <div class="text-muted small">Dropoff</div>
                    <div class="fw-bold small">{{ formatDateTime(selectedService.dropoff_datetime) }}</div>
                    <div class="small text-truncate">{{ selectedService.dropoff_address }}</div>
                </div>

                <!-- Driver -->
                <div class="mb-2" v-if="selectedService.drivers && selectedService.drivers.length > 0">
                    <div class="text-muted small">Autista</div>
                    <div v-for="driver in selectedService.drivers" :key="driver.id" class="small">
                        {{ driver.name }} {{ driver.surname }}
                    </div>
                </div>

                <!-- Veicolo -->
                <div class="mb-2" v-if="selectedService.vehicle">
                    <div class="text-muted small">Veicolo</div>
                    <div class="small">{{ selectedService.vehicle.brand }} {{ selectedService.vehicle.model }} - {{ selectedService.vehicle.license_plate }}</div>
                </div>

                <!-- Dress Code -->
                <div class="mb-2" v-if="selectedService.dress_code">
                    <div class="text-muted small">Dress Code</div>
                    <div class="small">{{ selectedService.dress_code.name }}</div>
                </div>

                <!-- Tipologia Servizio -->
                <div class="mb-2" v-if="selectedService.service_type">
                    <div class="text-muted small">Tipologia</div>
                    <div class="small">{{ selectedService.service_type }}</div>
                </div>

                <!-- Esperienze -->
                <div class="mb-2" v-if="selectedService.activities && selectedService.activities.length > 0">
                    <div class="text-muted small">Esperienze</div>
                    <div v-for="activity in selectedService.activities" :key="activity.id" class="small">
                        {{ activity.activity_type?.name || '-' }} - {{ activity.supplier?.name || '-' }}
                    </div>
                </div>

                <!-- Prezzo -->
                <div class="mb-2">
                    <div class="text-muted small">Prezzo</div>
                    <div class="fw-bold small">Tot: €{{ formatCurrency(selectedService.price) }}</div>
                    <div class="small">Saldo: €{{ formatCurrency(selectedService.balance_taxable) }}</div>
                </div>

                <!-- Note -->
                <div v-if="selectedService.notes" class="mb-2">
                    <div class="text-muted small">Note</div>
                    <div class="small text-truncate">{{ selectedService.notes }}</div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2 mt-3">
                    <Link
                        :href="route('easyncc.services.show', selectedService.id)"
                        class="btn btn-primary btn-sm flex-fill"
                    >
                        Visualizza
                    </Link>
                    <Link
                        :href="route('easyncc.services.edit', selectedService.id)"
                        class="btn btn-info btn-sm flex-fill"
                    >
                        Modifica
                    </Link>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
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
const showDetailPopover = ref(false);
const selectedService = ref(null);
const popoverEl = ref(null);
const popoverStyle = ref({});
const selectedEvent = ref(null);

// Hover popover
const showHoverPopover = ref(false);
const hoverService = ref(null);
const hoverPopoverStyle = ref({});
let hoverTimeout = null;

const loadServices = async (start = null, end = null) => {
    loading.value = true;
    error.value = '';

    try {
        // Load all services without date filter to show everything
        const response = await axios.get('/api/services', {
            params: {
                per_page: 1000,
                with_counts: true
            }
        });
        services.value = response.data.data || [];
        console.log('Loaded services for calendar:', services.value.length);
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

        const events = services.value.map(service => {
            const drivers = service.drivers || [];
            const pickupTime = moment(service.pickup_datetime).format('HH:mm');
            const contactName = service.contact_name || '';
            const passengerCount = service.passenger_count || 0;
            const clientName = service.client ? `${service.client.name || ''} ${service.client.surname || ''}`.trim() : '';
            const serviceType = service.service_type || '';

            // Capitalizza la prima parola del nome di riferimento
            const capitalizedContactName = contactName ?
                contactName.split(' ').map((word, index) =>
                    index === 0 ? word.toUpperCase() : word
                ).join(' ') : '';

            // Costruisci il titolo dell'evento: ORA | TIPO | PAX | NOME | COMMITTENTE
            const titleParts = [
                pickupTime,
                serviceType,
                `${passengerCount} pax`,
                capitalizedContactName,
                clientName
            ].filter(part => part && part.trim());

            const title = titleParts.join(' | ');

            // Gestisci i colori per i driver
            let backgroundColor, borderColor;
            const colors = drivers.length > 0
                ? drivers.map(d => d.driver_profile?.color || '#6c757d')
                : ['#6c757d'];

            backgroundColor = colors[0];
            borderColor = colors[0];

            return {
                id: service.id,
                title: title,
                start: service.pickup_datetime,
                end: service.dropoff_datetime,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                extendedProps: {
                    service: service,
                    driverColors: colors
                }
            };
        });

        // Initialize calendar
        const calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            if (calendarInstance) {
                calendarInstance.destroy();
            }

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
                displayEventTime: false,
                eventDisplay: 'block',
                eventDidMount: (info) => {
                    // Applica pattern a strisce oblique ripetute per tutti gli eventi
                    if (info.event.extendedProps.driverColors && info.event.extendedProps.driverColors.length > 0) {
                        const colors = info.event.extendedProps.driverColors;
                        info.el.style.background = createStripedGradient(colors);
                        info.el.style.color = '#fff';
                    }

                    // Aggiungi event listeners per hover
                    info.el.addEventListener('mouseenter', (e) => handleEventHover(e, info));
                    info.el.addEventListener('mouseleave', handleEventLeave);
                },
                eventMouseEnter: (info) => {
                    // Backup per eventMouseEnter di FullCalendar
                },
                eventMouseLeave: () => {
                    // Backup per eventMouseLeave di FullCalendar
                }
            });

            calendarInstance.render();
        }
    } catch (err) {
        console.error('Error initializing calendar:', err);
        error.value = 'Errore nell\'inizializzazione del calendario';
    }
};

const createStripedPattern = (colors) => {
    // Ritorna il primo colore come fallback (sarà sostituito dal gradient nel eventDidMount)
    return colors[0];
};

const createStripedGradient = (colors) => {
    // Crea un gradient con bande oblique ripetute a 135° con larghezza 40px per banda
    const bandWidth = 40; // larghezza di una singola banda in pixel
    const gradientStops = [];

    colors.forEach((color, index) => {
        const start = index * bandWidth;
        const end = (index + 1) * bandWidth;
        gradientStops.push(`${color} ${start}px`);
        gradientStops.push(`${color} ${end}px`);
    });

    return `repeating-linear-gradient(
        135deg,
        ${gradientStops.join(', ')}
    )`;
};

const handleEventClick = (info) => {
    // Chiudi l'hover popover se aperto
    closeHoverPopover();

    selectedService.value = info.event.extendedProps.service;
    selectedEvent.value = info.el;
    showDetailPopover.value = true;

    // Posiziona il popover vicino all'elemento cliccato
    setTimeout(() => {
        positionPopover(info.jsEvent);
    }, 0);
};

const handleEventHover = (event, info) => {
    // Cancella timeout precedente se esiste
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
    }

    // Imposta timeout di 1 secondo prima di mostrare il popover
    hoverTimeout = setTimeout(() => {
        hoverService.value = info.event.extendedProps.service;
        showHoverPopover.value = true;
        positionHoverPopover(event);
    }, 1000);
};

const handleEventLeave = () => {
    closeHoverPopover();
};

const closeHoverPopover = () => {
    if (hoverTimeout) {
        clearTimeout(hoverTimeout);
        hoverTimeout = null;
    }
    showHoverPopover.value = false;
    hoverService.value = null;
};

const positionHoverPopover = (event) => {
    const rect = event.target.getBoundingClientRect();
    const popoverWidth = 250; // Larghezza del hover popover

    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 5;

    // Se il popover esce dallo schermo a destra, posizionalo a sinistra
    if (left + popoverWidth > window.innerWidth) {
        left = rect.right - popoverWidth + window.scrollX;
    }

    hoverPopoverStyle.value = {
        position: 'absolute',
        left: `${left}px`,
        top: `${top}px`,
        zIndex: 9998
    };
};

const positionPopover = (event) => {
    if (!popoverEl.value) return;

    const rect = event.target.getBoundingClientRect();
    const popoverWidth = 320; // Larghezza fissa del popover
    const popoverHeight = popoverEl.value.offsetHeight;

    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 5;

    // Se il popover esce dallo schermo a destra, posizionalo a sinistra
    if (left + popoverWidth > window.innerWidth) {
        left = rect.right - popoverWidth + window.scrollX;
    }

    // Se il popover esce dallo schermo in basso, posizionalo sopra
    if (top + popoverHeight > window.innerHeight + window.scrollY) {
        top = rect.top + window.scrollY - popoverHeight - 5;
    }

    popoverStyle.value = {
        position: 'absolute',
        left: `${left}px`,
        top: `${top}px`,
        zIndex: 9999
    };
};

const closePopover = () => {
    showDetailPopover.value = false;
    selectedService.value = null;
    selectedEvent.value = null;
};

const formatDateTime = (datetime) => {
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const formatTime = (datetime) => {
    return moment(datetime).format('HH:mm');
};

const formatCurrency = (value) => {
    return value ? parseFloat(value).toFixed(2) : '0.00';
};

const getHoverEventTitle = (service) => {
    const pickupTime = moment(service.pickup_datetime).format('HH:mm');
    const contactName = service.contact_name || '';
    const passengerCount = service.passenger_count || 0;
    const clientName = service.client ? `${service.client.name || ''} ${service.client.surname || ''}`.trim() : '';
    const serviceType = service.service_type || '';

    // Capitalizza la prima parola del nome di riferimento
    const capitalizedContactName = contactName ?
        contactName.split(' ').map((word, index) =>
            index === 0 ? word.toUpperCase() : word
        ).join(' ') : '';

    // Costruisci il titolo: ORA | TIPO | PAX | NOME | COMMITTENTE
    const titleParts = [
        pickupTime,
        serviceType,
        `${passengerCount} pax`,
        capitalizedContactName,
        clientName
    ].filter(part => part && part.trim());

    return titleParts.join(' | ');
};

// Chiudi il popover quando si clicca fuori
const handleClickOutside = (event) => {
    if (showDetailPopover.value && popoverEl.value && !popoverEl.value.contains(event.target)) {
        if (!selectedEvent.value || !selectedEvent.value.contains(event.target)) {
            closePopover();
        }
    }
};

onMounted(() => {
    loadServices();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (calendarInstance) {
        calendarInstance.destroy();
    }
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
#calendar-container {
    padding: 20px 0;
}

:deep(.fc) {
    font-family: inherit;
}

/* Assicura che ci sia una sola toolbar */
:deep(.fc-header-toolbar) {
    margin-bottom: 1.5rem;
}

:deep(.fc-button-primary) {
    background-color: #007bff;
    border-color: #007bff;
}

:deep(.fc-button-primary:hover) {
    background-color: #0056b3;
    border-color: #0056b3;
}

:deep(.fc-button-primary:disabled) {
    opacity: 0.65;
}

:deep(.fc-event) {
    cursor: pointer;
    padding: 2px 4px;
    font-size: 0.7rem;
    line-height: 1.3;
}

:deep(.fc-event-title) {
    font-weight: 500;
}

.service-detail-popover {
    width: 320px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.service-detail-popover .popover-header {
    background-color: #405189;
    color: #fff;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.375rem 0.375rem 0 0;
}

.service-detail-popover .popover-body {
    padding: 1rem;
    max-height: 500px;
    overflow-y: auto;
}

.service-detail-popover .btn-close-white {
    filter: brightness(0) invert(1);
}

.text-truncate {
    max-width: 100%;
}

/* Hover Popover */
.hover-popover {
    width: 250px;
    background: rgba(220, 220, 220, 0.95);
    color: #333;
    border-radius: 0.375rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    pointer-events: none;
}

.hover-popover-content {
    padding: 0.75rem;
}
</style>
