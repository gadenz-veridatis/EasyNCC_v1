<template>
    <Head title="Calendario Servizi" />

    <Layout :collapsed-sidebar="true">
        <PageHeader title="Calendario Servizi" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Calendario Servizi</h5>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-soft-primary btn-sm"
                                @click="showFilters = !showFilters"
                            >
                                <i :class="showFilters ? 'bx bx-chevron-up' : 'bx bx-chevron-down'"></i>
                                {{ showFilters ? 'Nascondi Filtri' : 'Mostra Filtri' }}
                                <span v-if="hasActiveFilters" class="badge bg-primary ms-2">{{ activeFiltersCount }}</span>
                            </button>
                            <Link :href="route('easyncc.services.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Servizio
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="align-items-end">
                                <BCol md="3">
                                    <label class="form-label">Autista</label>
                                    <select v-model="filters.driver_id" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti gli autisti</option>
                                        <option v-for="driver in allDrivers" :key="driver.id" :value="driver.id">
                                            {{ driver.name }} {{ driver.surname }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Veicolo</label>
                                    <select v-model="filters.vehicle_id" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti i veicoli</option>
                                        <option v-for="vehicle in allVehicles" :key="vehicle.id" :value="vehicle.id">
                                            {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Nome Passeggero</label>
                                    <input
                                        v-model="filters.passenger_name"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Cerca passeggero..."
                                        @input="debouncedApplyFilters"
                                    />
                                </BCol>
                                <BCol md="3" class="d-flex align-items-end">
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        class="btn btn-soft-secondary btn-sm"
                                        @click="resetFilters"
                                    >
                                        <i class="bx bx-refresh me-1"></i>
                                        Reset Filtri
                                    </button>
                                </BCol>
                            </BRow>
                        </div>

                        <!-- Calendar (always in DOM so FullCalendar can initialize) -->
                        <div id="calendar-container" style="min-height: 600px; position: relative;">
                            <!-- Loading Overlay -->
                            <div v-if="loading" class="d-flex justify-content-center align-items-center" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 10; background: rgba(255,255,255,0.8);">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                            </div>
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
            <div class="popover-header d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <!-- Tipo di Tour -->
                    <div v-if="selectedService.service_type" class="mb-1">
                        <span class="badge bg-light text-dark" style="font-size: 0.8rem;">{{ selectedService.service_type }}</span>
                    </div>
                    <!-- Nome primo passeggero -->
                    <div class="fw-bold" v-if="selectedService.passengers && selectedService.passengers.length > 0">
                        {{ selectedService.passengers[0].surname ? selectedService.passengers[0].surname.toUpperCase() : '' }} {{ selectedService.passengers[0].name || '' }}
                    </div>
                    <div class="fw-bold" v-else-if="selectedService.contact_name">
                        {{ selectedService.contact_name }}
                    </div>
                    <!-- Numero PAX -->
                    <div class="small mt-1">
                        <i class="ri-user-line me-1"></i>{{ selectedService.passenger_count || 0 }} pax
                    </div>
                    <!-- ID piccolo -->
                    <div class="small" style="opacity: 0.7; font-size: 0.7rem;">#{{ selectedService.reference_number || selectedService.id }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-2" @click="closePopover"></button>
            </div>
            <div class="popover-body">
                <!-- Pickup -->
                <div class="mb-2">
                    <div class="fw-bold text-success" style="font-size: 0.75rem;">Partenza:</div>
                    <div class="fw-bold small">{{ formatDateTime(selectedService.pickup_datetime) }}</div>
                    <div class="small text-truncate">{{ selectedService.pickup_address }}</div>
                </div>

                <!-- Dropoff -->
                <div class="mb-2">
                    <div class="fw-bold text-danger" style="font-size: 0.75rem;">Arrivo:</div>
                    <div class="fw-bold small">{{ formatDateTime(selectedService.dropoff_datetime) }}</div>
                    <div class="small text-truncate">{{ selectedService.dropoff_address }}</div>
                </div>

                <!-- Driver - inline editable -->
                <div class="mb-2" v-if="selectedService.drivers && selectedService.drivers.length > 0">
                    <div class="text-muted small">Autista</div>
                    <div v-if="!popoverEditingDrivers">
                        <div
                            v-for="driver in selectedService.drivers"
                            :key="driver.id"
                            class="small popover-inline-editable"
                            @click.stop="startPopoverEditDrivers"
                            title="Clicca per modificare"
                        >
                            <span class="badge" :style="`background-color: ${driver.driver_profile?.color || '#6c757d'};`">
                                {{ driver.surname }} {{ driver.name }}
                            </span>
                        </div>
                    </div>
                    <div v-else @click.stop>
                        <select
                            v-model="popoverEditingDriversValue"
                            class="form-select form-select-sm"
                            multiple
                            size="4"
                            style="font-size: 0.75rem;"
                        >
                            <option v-for="driver in allDrivers" :key="driver.id" :value="driver.id">
                                {{ driver.surname }} {{ driver.name }}
                            </option>
                        </select>
                        <div class="d-flex gap-1 mt-1">
                            <button class="btn btn-sm btn-success py-0 px-1" @click.stop="savePopoverDrivers" style="font-size: 0.7rem;">
                                <i class="ri-check-line"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary py-0 px-1" @click.stop="cancelPopoverEditDrivers" style="font-size: 0.7rem;">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Veicolo - inline editable -->
                <div class="mb-2">
                    <div class="text-muted small">Veicolo</div>
                    <div v-if="!popoverEditingVehicle && selectedService.vehicle" class="small popover-inline-editable" @click.stop="startPopoverEditVehicle" title="Clicca per modificare">
                        {{ selectedService.vehicle.brand }} {{ selectedService.vehicle.model }} - {{ selectedService.vehicle.license_plate }}
                    </div>
                    <div v-else-if="!popoverEditingVehicle" class="small text-muted popover-inline-editable" @click.stop="startPopoverEditVehicle" title="Clicca per assegnare">
                        <i class="ri-add-line"></i> Assegna veicolo
                    </div>
                    <div v-else @click.stop>
                        <select
                            v-model="popoverEditingVehicleValue"
                            class="form-select form-select-sm"
                            style="font-size: 0.75rem;"
                            @change="savePopoverVehicle"
                        >
                            <option value="">Seleziona veicolo</option>
                            <option v-for="vehicle in allVehicles" :key="vehicle.id" :value="vehicle.id">
                                {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Dress Code - inline editable -->
                <div class="mb-2">
                    <div class="text-muted small">Dress Code</div>
                    <div v-if="!popoverEditingDressCode" class="small popover-inline-editable" @click.stop="startPopoverEditDressCode" title="Clicca per modificare">
                        <i class="ri-shirt-line me-1"></i>{{ selectedService.dress_code ? selectedService.dress_code.name : 'Nessun dress code' }}
                    </div>
                    <div v-else @click.stop>
                        <select
                            v-model="popoverEditingDressCodeValue"
                            class="form-select form-select-sm"
                            style="font-size: 0.75rem;"
                            @change="savePopoverDressCode"
                        >
                            <option :value="null">Nessun dress code</option>
                            <option v-for="dc in allDressCodes" :key="dc.id" :value="dc.id">
                                {{ dc.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Prezzo Totale -->
                <div class="mb-2">
                    <div class="text-muted small">Prezzo Totale</div>
                    <div class="fw-bold">
                        <span class="badge bg-success fs-6 px-2 py-1">€{{ formatCurrency(selectedService.service_price || selectedService.price) }}</span>
                    </div>
                </div>

                <!-- Sovrapposizioni -->
                <div v-if="hasSelectedServiceOverlaps" class="mb-2">
                    <div class="text-warning small fw-bold">
                        <i class="ri-alert-fill me-1"></i>Sovrapposizioni
                    </div>
                    <div v-for="overlap in selectedServiceOverlaps" :key="overlap.id" class="small border-start border-warning ps-2 mt-1">
                        <div class="fw-bold">
                            #{{ overlap.related_service_reference || overlap.related_service_id }}
                        </div>
                        <div class="text-muted">
                            <span v-if="overlap.overlap_type === 'vehicle'" class="badge bg-info me-1">Veicolo</span>
                            <span v-else-if="overlap.overlap_type === 'driver'" class="badge bg-warning text-dark me-1">Autista</span>
                            <span v-else-if="overlap.overlap_type === 'both'" class="badge bg-danger me-1">Entrambi</span>
                        </div>
                        <div v-if="overlap.vehicle" class="text-info">
                            <i class="ri-car-line me-1"></i>{{ overlap.vehicle.license_plate }}
                        </div>
                        <div v-if="overlap.driver" class="text-warning">
                            <i class="ri-user-line me-1"></i>{{ overlap.driver.surname }} {{ overlap.driver.name }}
                        </div>
                    </div>
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

// FullCalendar instance
let calendarInstance;
let debounceTimer = null;

const loading = ref(true);
const error = ref('');
const services = ref([]);
const showFilters = ref(false);

// Filter state
const filters = ref({
    driver_id: '',
    vehicle_id: '',
    passenger_name: '',
});

const hasActiveFilters = computed(() => {
    return filters.value.driver_id !== '' ||
           filters.value.vehicle_id !== '' ||
           filters.value.passenger_name !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.driver_id !== '') count++;
    if (filters.value.vehicle_id !== '') count++;
    if (filters.value.passenger_name !== '') count++;
    return count;
});

const applyFilters = () => {
    if (calendarInstance) {
        const view = calendarInstance.view;
        loadServices(view.activeStart, view.activeEnd);
    }
};

const debouncedApplyFilters = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
};

const resetFilters = () => {
    filters.value.driver_id = '';
    filters.value.vehicle_id = '';
    filters.value.passenger_name = '';
    applyFilters();
};
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

// Inline editing in popover
const popoverEditingDrivers = ref(false);
const popoverEditingDriversValue = ref([]);
const popoverEditingVehicle = ref(false);
const popoverEditingVehicleValue = ref('');
const popoverEditingDressCode = ref(false);
const popoverEditingDressCodeValue = ref(null);

// Dictionaries for inline editing
const allDrivers = ref([]);
const allVehicles = ref([]);
const allDressCodes = ref([]);

// Track if we're currently loading to prevent duplicate requests
let isLoadingServices = false;

const mapServicesToEvents = (servicesList) => {
    return servicesList.map(service => {
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

        // Check for overlaps
        const hasOverlaps = (service.overlaps_count || 0) + (service.overlapped_by_count || 0) > 0;

        return {
            id: service.id,
            title: title,
            start: service.pickup_datetime,
            end: service.dropoff_datetime,
            backgroundColor: backgroundColor,
            borderColor: borderColor,
            classNames: hasOverlaps ? ['fc-event-overlap'] : [],
            extendedProps: {
                service: service,
                driverColors: colors,
                hasOverlaps: hasOverlaps
            }
        };
    });
};

const loadServices = async (start = null, end = null) => {
    if (isLoadingServices) return;
    isLoadingServices = true;
    error.value = '';

    try {
        const params = {
            per_page: 1000,
            with_counts: true
        };

        // Filter by date range if provided (with margin for multi-day events)
        if (start && end) {
            params.pickup_date_from = moment(start).subtract(7, 'days').format('YYYY-MM-DD');
            params.pickup_date_to = moment(end).add(7, 'days').format('YYYY-MM-DD');
        }

        // Apply user filters
        if (filters.value.driver_id) {
            params.driver_id = filters.value.driver_id;
        }
        if (filters.value.vehicle_id) {
            params.vehicle_id = filters.value.vehicle_id;
        }
        if (filters.value.passenger_name) {
            params.passenger_name = filters.value.passenger_name;
        }

        const response = await axios.get('/api/services', { params });
        services.value = response.data.data || [];

        // Update calendar events if calendar exists
        if (calendarInstance) {
            // Remove all existing events
            calendarInstance.removeAllEvents();
            // Add new events
            const events = mapServicesToEvents(services.value);
            events.forEach(event => calendarInstance.addEvent(event));
        }
    } catch (err) {
        error.value = 'Errore nel caricamento dei servizi';
        console.error('Error loading services:', err);
    } finally {
        isLoadingServices = false;
    }
};

const initializeCalendar = async () => {
    try {
        // Dynamically import FullCalendar
        const { Calendar: CalendarCore } = await import('@fullcalendar/core');
        const dayGridPlugin = (await import('@fullcalendar/daygrid')).default;
        const timeGridPlugin = (await import('@fullcalendar/timegrid')).default;
        const interactionPlugin = (await import('@fullcalendar/interaction')).default;

        // Initialize calendar (empty, events loaded via datesSet)
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
                events: [],
                eventClick: handleEventClick,
                editable: false,
                displayEventTime: false,
                eventDisplay: 'block',
                datesSet: (dateInfo) => {
                    // Load services for the visible date range
                    loadServices(dateInfo.start, dateInfo.end);
                },
                eventDidMount: (info) => {
                    // Applica pattern a strisce oblique ripetute per tutti gli eventi
                    if (info.event.extendedProps.driverColors && info.event.extendedProps.driverColors.length > 0) {
                        const colors = info.event.extendedProps.driverColors;
                        info.el.style.background = createStripedGradient(colors);
                        info.el.style.color = '#fff';
                    }

                    // Aggiungi icona triangolo giallo per eventi sovrapposti
                    if (info.event.extendedProps.hasOverlaps) {
                        const titleEl = info.el.querySelector('.fc-event-title') || info.el.querySelector('.fc-event-title-container');
                        if (titleEl) {
                            const warningIcon = document.createElement('span');
                            warningIcon.innerHTML = '\u26A0';
                            warningIcon.style.cssText = 'color: #ffc107; font-size: 0.85rem; margin-right: 3px; text-shadow: 0 0 2px rgba(0,0,0,0.5);';
                            titleEl.prepend(warningIcon);
                        }
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
    resetPopoverEditing();
};

const resetPopoverEditing = () => {
    popoverEditingDrivers.value = false;
    popoverEditingDriversValue.value = [];
    popoverEditingVehicle.value = false;
    popoverEditingVehicleValue.value = '';
    popoverEditingDressCode.value = false;
    popoverEditingDressCodeValue.value = null;
};

const loadDictionaries = async () => {
    try {
        const [driversRes, vehiclesRes, dressCodesRes] = await Promise.all([
            axios.get('/api/users', { params: { role: 'driver', per_page: 200 } }),
            axios.get('/api/vehicles', { params: { per_page: 200 } }),
            axios.get('/api/dictionaries/dress-codes')
        ]);
        allDrivers.value = driversRes.data.data || [];
        allVehicles.value = vehiclesRes.data.data || [];
        allDressCodes.value = dressCodesRes.data.data || [];
    } catch (err) {
        console.error('Error loading dictionaries:', err);
    }
};

// Inline editing: Drivers
const startPopoverEditDrivers = () => {
    popoverEditingDrivers.value = true;
    popoverEditingDriversValue.value = selectedService.value.drivers
        ? selectedService.value.drivers.map(d => d.id)
        : [];
};

const savePopoverDrivers = async () => {
    try {
        await axios.put(`/api/services/${selectedService.value.id}`, {
            driver_ids: popoverEditingDriversValue.value
        });
        // Reload services to update calendar
        if (calendarInstance) {
            const view = calendarInstance.view;
            await loadServices(view.activeStart, view.activeEnd);
        }
        // Update selected service data
        const updated = services.value.find(s => s.id === selectedService.value.id);
        if (updated) selectedService.value = updated;
        popoverEditingDrivers.value = false;
    } catch (err) {
        console.error('Error updating drivers:', err);
    }
};

const cancelPopoverEditDrivers = () => {
    popoverEditingDrivers.value = false;
    popoverEditingDriversValue.value = [];
};

// Inline editing: Vehicle
const startPopoverEditVehicle = () => {
    popoverEditingVehicle.value = true;
    popoverEditingVehicleValue.value = selectedService.value.vehicle_id || '';
};

const savePopoverVehicle = async () => {
    try {
        await axios.put(`/api/services/${selectedService.value.id}`, {
            vehicle_id: popoverEditingVehicleValue.value || null
        });
        // Update local data
        if (popoverEditingVehicleValue.value) {
            const vehicle = allVehicles.value.find(v => v.id === popoverEditingVehicleValue.value);
            selectedService.value.vehicle = vehicle;
            selectedService.value.vehicle_id = popoverEditingVehicleValue.value;
        } else {
            selectedService.value.vehicle = null;
            selectedService.value.vehicle_id = null;
        }
        popoverEditingVehicle.value = false;
        // Reload calendar events
        if (calendarInstance) {
            const view = calendarInstance.view;
            await loadServices(view.activeStart, view.activeEnd);
        }
    } catch (err) {
        console.error('Error updating vehicle:', err);
    }
};

// Inline editing: Dress Code
const startPopoverEditDressCode = () => {
    popoverEditingDressCode.value = true;
    popoverEditingDressCodeValue.value = selectedService.value.dress_code_id || null;
};

const savePopoverDressCode = async () => {
    try {
        await axios.put(`/api/services/${selectedService.value.id}`, {
            dress_code_id: popoverEditingDressCodeValue.value
        });
        // Update local data
        if (popoverEditingDressCodeValue.value) {
            const dc = allDressCodes.value.find(d => d.id === popoverEditingDressCodeValue.value);
            selectedService.value.dress_code = dc;
            selectedService.value.dress_code_id = popoverEditingDressCodeValue.value;
        } else {
            selectedService.value.dress_code = null;
            selectedService.value.dress_code_id = null;
        }
        popoverEditingDressCode.value = false;
    } catch (err) {
        console.error('Error updating dress code:', err);
    }
};

// Computed properties for overlaps
const hasSelectedServiceOverlaps = computed(() => {
    if (!selectedService.value) return false;
    const overlapsCount = (selectedService.value.overlaps_count || 0) + (selectedService.value.overlapped_by_count || 0);
    return overlapsCount > 0 ||
           (selectedService.value.overlaps && selectedService.value.overlaps.length > 0) ||
           (selectedService.value.overlapped_by && selectedService.value.overlapped_by.length > 0);
});

const selectedServiceOverlaps = computed(() => {
    if (!selectedService.value) return [];

    const overlaps = [];

    // Add overlaps (where this service overlaps others)
    if (selectedService.value.overlaps && selectedService.value.overlaps.length > 0) {
        selectedService.value.overlaps.forEach(o => {
            overlaps.push({
                id: o.id,
                overlap_type: o.overlap_type,
                related_service_id: o.overlapping_service?.id || o.overlapping_service_id,
                related_service_reference: o.overlapping_service?.reference_number,
                vehicle: o.vehicle,
                driver: o.driver
            });
        });
    }

    // Add overlappedBy (where other services overlap this one)
    if (selectedService.value.overlapped_by && selectedService.value.overlapped_by.length > 0) {
        selectedService.value.overlapped_by.forEach(o => {
            overlaps.push({
                id: `by_${o.id}`,
                overlap_type: o.overlap_type,
                related_service_id: o.service?.id || o.service_id,
                related_service_reference: o.service?.reference_number,
                vehicle: o.vehicle,
                driver: o.driver
            });
        });
    }

    return overlaps;
});

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

onMounted(async () => {
    loading.value = true;
    try {
        await Promise.all([initializeCalendar(), loadDictionaries()]);
    } finally {
        loading.value = false;
        // Update calendar size after overlay is removed
        await nextTick();
        if (calendarInstance) {
            calendarInstance.updateSize();
        }
    }
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

:deep(.fc-event.fc-event-overlap) {
    /* No special border for overlapping events - uses warning icon instead */
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

/* Inline editable fields in popover */
.popover-inline-editable {
    cursor: pointer;
    border-radius: 3px;
    padding: 1px 3px;
    transition: background-color 0.15s;
}

.popover-inline-editable:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.08);
}
</style>
