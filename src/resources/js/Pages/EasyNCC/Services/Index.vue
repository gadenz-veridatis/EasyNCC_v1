<template>
    <Head title="Servizi" />

    <Layout :collapsed-sidebar="true">
        <PageHeader title="Servizi" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Servizi</h5>
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
                        <BRow class="mb-4">
                            <BCol md="2">
                                <label class="form-label">Nominativo Riferimento</label>
                                <input
                                    v-model="filters.reference_name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Nome riferimento..."
                                    @input="debouncedLoadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Pickup Da</label>
                                <input
                                    v-model="filters.pickup_date_from"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Pickup A</label>
                                <input
                                    v-model="filters.pickup_date_to"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Dropoff Da</label>
                                <input
                                    v-model="filters.dropoff_date_from"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Data Dropoff A</label>
                                <input
                                    v-model="filters.dropoff_date_to"
                                    type="date"
                                    class="form-control form-control-sm"
                                    @change="loadServices"
                                />
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Tipo Servizio</label>
                                <select v-model="filters.service_type_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i tipi</option>
                                    <option v-for="type in serviceTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                            </BCol>
                        </BRow>

                        <BRow class="mb-4">
                            <BCol md="2">
                                <label class="form-label">Committente</label>
                                <select v-model="filters.client_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i committenti</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }} {{ client.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Intermediario</label>
                                <select v-model="filters.intermediary_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli intermediari</option>
                                    <option v-for="intermediary in intermediaries" :key="intermediary.id" :value="intermediary.id">
                                        {{ intermediary.name }} {{ intermediary.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Autista</label>
                                <select v-model="filters.driver_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli autisti</option>
                                    <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                        {{ driver.name }} {{ driver.surname }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Veicolo</label>
                                <select v-model="filters.vehicle_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti i veicoli</option>
                                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                        {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                                    </option>
                                </select>
                            </BCol>
                            <BCol md="2">
                                <label class="form-label">Stato</label>
                                <select v-model="filters.status" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutti gli stati</option>
                                    <option value="preventivo">Preventivo</option>
                                    <option value="confermato">Confermato</option>
                                    <option value="in corso">In Corso</option>
                                    <option value="completato">Completato</option>
                                    <option value="cancellato">Cancellato</option>
                                    <option value="no-show">No Show</option>
                                </select>
                            </BCol>
                            <BCol md="2" v-if="isSuperAdmin">
                                <label class="form-label">Azienda</label>
                                <select v-model="filters.company_id" class="form-select form-select-sm" @change="loadServices">
                                    <option value="">Tutte le aziende</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                            </BCol>
                        </BRow>

                        <!-- Reset Filters Button -->
                        <BRow v-if="hasActiveFilters">
                            <BCol cols="12" class="d-flex justify-content-end">
                                <button
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

                        <!-- Bulk Actions Area (Collapsible) -->
                        <div v-show="selectedServices.length > 0" class="border rounded p-3 mb-3 bg-warning bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2">{{ selectedServices.length }} servizi selezionati</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-danger" @click="deleteSelected">
                                        <i class="ri-delete-bin-line me-1"></i>Elimina Selezionati
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="services.length > 0" class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="form-check-input">
                                        </th>
                                        <th scope="col" @click="sortBy('reference_number')" style="cursor: pointer;">
                                            Dati Identificativi
                                            <i v-if="sortField === 'reference_number'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col" @click="sortBy('pickup_datetime')" style="cursor: pointer;">
                                            Data
                                            <i v-if="sortField === 'pickup_datetime'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">
                                            Passeggeri<br/>Bagagli
                                        </th>
                                        <th scope="col" @click="sortBy('client_id')" style="cursor: pointer;">
                                            Committente<br/>Intermediario
                                            <i v-if="sortField === 'client_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">Autista</th>
                                        <th scope="col" @click="sortBy('vehicle_id')" style="cursor: pointer;">
                                            Veicolo
                                            <i v-if="sortField === 'vehicle_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">Esperienze</th>
                                        <th scope="col" @click="sortBy('price')" style="cursor: pointer;">
                                            Economics
                                            <i v-if="sortField === 'price'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                        <th scope="col">Notifiche</th>
                                        <th scope="col">Azioni</th>
                                        <th scope="col" v-if="isSuperAdmin" @click="sortBy('company_id')" style="cursor: pointer;">
                                            Azienda
                                            <i v-if="sortField === 'company_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in sortedServices" :key="service.id" :class="{ 'table-active': selectedServices.includes(service.id) }">
                                        <td>
                                            <input type="checkbox" v-model="selectedServices" :value="service.id" class="form-check-input">
                                        </td>
                                        <!-- Dati Identificativi -->
                                        <td>
                                            <!-- Tipologia Servizio come Tag, più visibile -->
                                            <div class="mb-2" v-if="service.service_type">
                                                <span class="badge bg-primary text-white" style="font-size: 0.9rem; padding: 0.45rem 0.85rem;">
                                                    {{ service.service_type }}
                                                </span>
                                            </div>

                                            <!-- Primo Passeggero o Messaggio -->
                                            <div class="mb-1">
                                                <div v-if="service.passengers && service.passengers.length > 0" class="d-flex align-items-center gap-1">
                                                    <span v-if="service.passengers[0].nationality">{{ getNationalityFlag(service.passengers[0].nationality) }} </span>
                                                    <span class="fw-bold text-uppercase">{{ service.passengers[0].surname }}</span>
                                                    <span>{{ service.passengers[0].name }}</span>
                                                    <!-- Icona per aprire modale se ci sono più passeggeri -->
                                                    <button
                                                        v-if="service.passengers.length > 1"
                                                        type="button"
                                                        @click="showPassengersModal(service)"
                                                        class="btn btn-link btn-sm p-0 text-primary"
                                                        :title="`Vedi tutti i ${service.passengers.length} passeggeri`"
                                                        style="line-height: 1;"
                                                    >
                                                        <i class="ri-group-line" style="font-size: 1rem;"></i>
                                                        <span class="badge bg-primary rounded-pill ms-1" style="font-size: 0.7rem;">{{ service.passengers.length }}</span>
                                                    </button>
                                                </div>
                                                <div v-else class="text-muted small fst-italic">
                                                    Nessun passeggero inserito
                                                </div>
                                            </div>

                                            <!-- Identificativo Servizio in fondo, visibilità ridotta -->
                                            <div class="small text-muted">
                                                #{{ service.reference_number || service.id }}
                                            </div>
                                        </td>
                                        <!-- Data -->
                                        <td>
                                            <div class="d-flex align-items-start gap-2 mb-2">
                                                <i class="ri-map-pin-add-line text-success" style="font-size: 1.1rem;" title="Pickup"></i>
                                                <div>
                                                    <div class="fw-bold">{{ formatDate(service.pickup_datetime) }}</div>
                                                    <div class="small text-primary">{{ service.pickup_address }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="ri-map-pin-line text-danger" style="font-size: 1.1rem;" title="Dropoff"></i>
                                                <div>
                                                    <div class="small text-muted">{{ formatDate(service.dropoff_datetime) }}</div>
                                                    <div class="small text-muted">{{ service.dropoff_address }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Passeggeri -->
                                        <td>
                                            <!-- Passenger Count Inline Edit -->
                                            <div class="mb-1">
                                                <!-- Display mode -->
                                                <div
                                                    v-if="editingPassengerCount !== service.id"
                                                    class="d-flex align-items-center gap-1"
                                                >
                                                    <div class="fw-bold">
                                                        <i class="ri-user-line"></i> {{ service.passenger_count || 0 }}
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="startEditPassengerCount(service)"
                                                        class="btn btn-link btn-sm p-0 text-muted"
                                                        title="Modifica numero passeggeri"
                                                        style="line-height: 1;"
                                                    >
                                                        <i class="ri-edit-line" style="font-size: 0.9rem;"></i>
                                                    </button>
                                                </div>

                                                <!-- Edit mode -->
                                                <div v-else class="d-flex gap-2 align-items-center">
                                                    <input
                                                        v-model.number="editingPassengerCountValue"
                                                        :ref="el => passengerCountInputRefs[service.id] = el"
                                                        @keydown.enter="savePassengerCount(service)"
                                                        @keydown.escape="cancelEditPassengerCount"
                                                        type="number"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="N. passeggeri"
                                                        style="font-size: 0.85rem; width: 80px;"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="savePassengerCount(service)"
                                                        class="btn btn-sm btn-success"
                                                        title="Salva"
                                                    >
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="cancelEditPassengerCount"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Annulla"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Bagagli -->
                                            <div class="small mb-1">
                                                <span class="text-muted me-2" style="font-size: 0.7rem;">Bagagli:</span>
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <span :title="`Bagaglio grande: ${service.big_luggage || 0}`" style="cursor: help;">
                                                        <i class="ri-luggage-cart-line"></i>{{ service.big_luggage || 0 }}
                                                    </span>
                                                    <span :title="`Bagaglio medio: ${service.medium_luggage || 0}`" style="cursor: help;">
                                                        <i class="ri-briefcase-line"></i>{{ service.medium_luggage || 0 }}
                                                    </span>
                                                    <span :title="`Bagaglio piccolo: ${service.small_luggage || 0}`" style="cursor: help;">
                                                        <i class="ri-handbag-line"></i>{{ service.small_luggage || 0 }}
                                                    </span>
                                                </span>
                                            </div>
                                            <!-- Babyseat -->
                                            <div class="small mb-1">
                                                <span class="text-muted me-2" style="font-size: 0.7rem;">Baby Seat:</span>
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <span :title="`Ovetto: ${service.babyseat_egg || 0}`" style="cursor: help;">
                                                        <i class="ri-bear-smile-line"></i>{{ service.babyseat_egg || 0 }}
                                                    </span>
                                                    <span :title="`Seggiolino standard: ${service.babyseat_standard || 0}`" style="cursor: help;">
                                                        <i class="ri-parent-line"></i>{{ service.babyseat_standard || 0 }}
                                                    </span>
                                                    <span :title="`Booster: ${service.babyseat_booster || 0}`" style="cursor: help;">
                                                        <i class="ri-user-smile-line"></i>{{ service.babyseat_booster || 0 }}
                                                    </span>
                                                </span>
                                            </div>
                                        </td>
                                        <!-- Committente/Intermediario -->
                                        <td>
                                            <!-- Committente -->
                                            <div v-if="service.client">
                                                <div class="small text-muted mb-1">
                                                    <span class="badge bg-soft-primary text-primary" style="font-size: 0.7rem;">Committente</span>
                                                </div>
                                                <div class="fw-bold">
                                                    {{ service.client.name }} {{ service.client.surname }}
                                                </div>
                                                <div class="small" v-if="service.client.phone">
                                                    <i class="ri-phone-line"></i> {{ service.client.phone }}
                                                </div>
                                            </div>

                                            <!-- Intermediario -->
                                            <div v-if="service.intermediary" class="mt-2 pt-2 border-top">
                                                <div class="small text-muted mb-1">
                                                    <span class="badge bg-soft-secondary text-secondary" style="font-size: 0.7rem;">Intermediario</span>
                                                </div>
                                                <div class="small">
                                                    {{ service.intermediary.name }} {{ service.intermediary.surname }}
                                                </div>
                                                <div class="small" v-if="service.intermediary.phone">
                                                    <i class="ri-phone-line"></i> {{ service.intermediary.phone }}
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Autista -->
                                        <td>
                                            <!-- Display mode -->
                                            <div v-if="editingDrivers !== service.id">
                                                <div v-if="service.drivers && service.drivers.length > 0">
                                                    <div v-for="driver in service.drivers" :key="driver.id" class="mb-2">
                                                        <span class="badge text-start" :style="`background-color: ${driver.driver_profile?.color || '#6c757d'}; padding: 0.5rem 0.75rem;`">
                                                            <div class="fw-bold text-uppercase" style="font-size: 0.9rem;">{{ driver.surname }}</div>
                                                            <div style="font-size: 0.85rem;">{{ driver.name }}</div>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div v-else class="text-muted small">
                                                    Nessun autista
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="startEditDrivers(service)"
                                                    class="btn btn-link btn-sm p-0 text-muted mt-2"
                                                    title="Modifica autisti"
                                                    style="line-height: 1;"
                                                >
                                                    <i class="ri-edit-line" style="font-size: 0.9rem;"></i>
                                                </button>
                                            </div>

                                            <!-- Edit mode -->
                                            <div v-else class="mb-2">
                                                <select
                                                    v-model="editingDriversValue"
                                                    :ref="el => driversInputRefs[service.id] = el"
                                                    class="form-select form-select-sm"
                                                    multiple
                                                    size="5"
                                                    style="min-width: 200px;"
                                                >
                                                    <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                                        {{ driver.surname }} {{ driver.name }}
                                                    </option>
                                                </select>
                                                <div class="d-flex gap-2 mt-2">
                                                    <button
                                                        type="button"
                                                        @click="saveDrivers(service)"
                                                        class="btn btn-sm btn-success"
                                                        title="Salva"
                                                    >
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="cancelEditDrivers"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Annulla"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-2">
                                                <!-- Display mode -->
                                                <div
                                                    v-if="editingDressCode !== service.id"
                                                    class="d-flex align-items-center gap-1"
                                                >
                                                        <div style="font-size: 0.75rem;">
                                                            <i class="ri-shirt-line me-1"></i>
                                                            {{ service.dress_code ? service.dress_code.name : 'Nessun dress code' }}
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="startEditDressCode(service)"
                                                            class="btn btn-link btn-sm p-0 text-muted"
                                                            title="Modifica dress code"
                                                            style="line-height: 1;"
                                                        >
                                                            <i class="ri-edit-line" style="font-size: 0.9rem;"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Edit mode -->
                                                    <div v-else class="d-flex gap-2 align-items-center">
                                                        <select
                                                            v-model="editingDressCodeValue"
                                                            :ref="el => dressCodeInputRefs[service.id] = el"
                                                            @keydown.enter="saveDressCode(service)"
                                                            @keydown.escape="cancelEditDressCode"
                                                            class="form-select form-select-sm"
                                                            style="font-size: 0.75rem; min-width: 150px;"
                                                        >
                                                            <option :value="null">Nessun dress code</option>
                                                            <option
                                                                v-for="dressCode in dressCodes"
                                                                :key="dressCode.id"
                                                                :value="dressCode.id"
                                                            >
                                                                {{ dressCode.name }}
                                                            </option>
                                                        </select>
                                                        <button
                                                            type="button"
                                                            @click="saveDressCode(service)"
                                                            class="btn btn-sm btn-success"
                                                            title="Salva"
                                                        >
                                                            <i class="ri-check-line"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="cancelEditDressCode"
                                                            class="btn btn-sm btn-secondary"
                                                            title="Annulla"
                                                        >
                                                            <i class="ri-close-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                        </td>
                                        <!-- Veicolo -->
                                        <td>
                                            <div v-if="service.vehicle">
                                                <!-- Display mode -->
                                                <div
                                                    v-if="editingVehicle !== service.id"
                                                    class="d-flex align-items-center gap-1"
                                                >
                                                    <div
                                                        class="targa-auto"
                                                        :title="`${service.vehicle.brand} ${service.vehicle.model} - ${service.vehicle.passenger_capacity} posti`"
                                                        style="cursor: help;"
                                                    >
                                                        <span class="codice-targa">{{ service.vehicle.license_plate }}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="startEditVehicle(service)"
                                                        class="btn btn-link btn-sm p-0 text-muted"
                                                        title="Modifica veicolo"
                                                        style="line-height: 1;"
                                                    >
                                                        <i class="ri-edit-line" style="font-size: 0.9rem;"></i>
                                                    </button>
                                                </div>

                                                <!-- Edit mode -->
                                                <div v-else class="d-flex gap-2 align-items-center">
                                                    <select
                                                        v-model="editingVehicleValue"
                                                        :ref="el => vehicleInputRefs[service.id] = el"
                                                        class="form-select form-select-sm"
                                                        style="min-width: 150px;"
                                                    >
                                                        <option value="">Seleziona veicolo</option>
                                                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                                            {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                                                        </option>
                                                    </select>
                                                    <button
                                                        type="button"
                                                        @click="saveVehicle(service)"
                                                        class="btn btn-sm btn-success"
                                                        title="Salva"
                                                    >
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="cancelEditVehicle"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Annulla"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div v-else class="text-muted">
                                                <button
                                                    type="button"
                                                    @click="startEditVehicle(service)"
                                                    class="btn btn-link btn-sm p-0 text-muted"
                                                    title="Assegna veicolo"
                                                >
                                                    <i class="ri-add-line"></i> Assegna
                                                </button>
                                            </div>

                                            <!-- Fornitore trasporto -->
                                            <div class="small mt-2" v-if="service.supplier">
                                                <span class="badge bg-soft-info text-info" style="font-size: 0.7rem;">Fornitore</span>
                                                <div class="text-muted mt-1">
                                                    <i class="ri-building-line"></i> {{ service.supplier.name }} {{ service.supplier.surname }}
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Esperienze -->
                                        <td>
                                            <div v-if="service.activities && service.activities.length > 0">
                                                <div v-for="activity in service.activities" :key="activity.id" class="small mb-1">
                                                    <!-- Ora | Tipologia -->
                                                    <div class="fw-medium">
                                                        {{ formatTime(activity.start_time) }} | {{ activity.activity_type?.name || '-' }}
                                                    </div>
                                                    <!-- Fornitore | Pagamento -->
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <span v-if="activity.supplier?.name || activity.supplier?.surname">
                                                            {{ activity.supplier.name }} {{ activity.supplier.surname }}
                                                        </span>
                                                        <span v-if="(activity.supplier?.name || activity.supplier?.surname) && activity.payment_type"> | </span>
                                                        <span v-if="activity.payment_type" class="text-secondary">
                                                            Pagamento: {{ activity.payment_type }}
                                                        </span>
                                                        <span v-if="!activity.supplier?.name && !activity.supplier?.surname && !activity.payment_type">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Economics -->
                                        <td>
                                            <div
                                                class="cursor-pointer"
                                                style="cursor: pointer;"
                                                @click="showEconomicsModal(service)"
                                                title="Clicca per vedere i dettagli economici"
                                            >
                                                <span class="badge bg-success fs-6 px-3 py-2">€{{ formatCurrency(service.service_price) }}</span>
                                            </div>
                                        </td>
                                        <!-- Notifiche -->
                                        <td>
                                            <!-- Status di Conferma -->
                                            <div class="mb-2" v-if="service.status">
                                                <span class="badge" :class="getStatusBadgeClass(service.status.name)">{{ service.status.name }}</span>
                                            </div>

                                            <div class="small">
                                                <!-- Movimenti Contabili -->
                                                <div class="mb-1">
                                                    <i
                                                        class="ri-file-list-line"
                                                        :class="service.accounting_transactions_count > 0 ? 'text-success' : 'text-muted'"
                                                        style="cursor: pointer;"
                                                        @click="showTransactionsPopup(service)"
                                                        title="Clicca per vedere i movimenti"
                                                    ></i> {{ service.accounting_transactions_count || 0 }}
                                                </div>
                                                <!-- Task -->
                                                <div>
                                                    <i
                                                        class="ri-task-line"
                                                        :class="service.tasks_count > 0 ? getTaskIconClass(service) : 'text-muted'"
                                                        style="cursor: pointer;"
                                                        @click="showTasksPopup(service)"
                                                        title="Clicca per vedere i task"
                                                    ></i>
                                                    {{ getCompletedTasksCount(service) }}/{{ service.tasks_count || 0 }}
                                                </div>
                                                <!-- Sovrapposizioni -->
                                                <div v-if="getTotalOverlapsCount(service) > 0" class="mt-1">
                                                    <i
                                                        class="ri-error-warning-line text-warning"
                                                        style="cursor: pointer;"
                                                        @click="showOverlapsPopup(service)"
                                                        title="Clicca per vedere le sovrapposizioni"
                                                    ></i>
                                                    <span class="badge bg-warning text-dark ms-1">{{ getTotalOverlapsCount(service) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Azioni -->
                                        <td>
                                            <Link :href="route('easyncc.services.show', service.id)" class="btn btn-sm btn-soft-primary me-1" title="Visualizza">
                                                <i class="ri-eye-line"></i>
                                            </Link>
                                            <Link :href="route('easyncc.services.edit', service.id)" class="btn btn-sm btn-soft-info me-1" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </Link>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteService(service.id)" title="Elimina">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                        <!-- Azienda (solo per super-admin) -->
                                        <td v-if="isSuperAdmin">
                                            <div class="small">{{ service.company?.name || '-' }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Popup Movimenti Contabili -->
                        <div v-if="showTransactionsModal" class="popup-overlay" @click="closeTransactionsPopup">
                            <div class="popup-content popup-content-large" @click.stop>
                                <div class="popup-header">
                                    <h6 class="mb-0">Movimenti Contabili - Servizio #{{ selectedServiceForPopup?.reference_number || selectedServiceForPopup?.id }}</h6>
                                    <button type="button" class="btn-close" @click="closeTransactionsPopup"></button>
                                </div>
                                <div class="popup-body">
                                    <div v-if="selectedServiceForPopup?.accounting_transactions && selectedServiceForPopup.accounting_transactions.length > 0" class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Importo</th>
                                                    <th>Tipo</th>
                                                    <th>Rata</th>
                                                    <th>Causale</th>
                                                    <th>Stato</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="trans in selectedServiceForPopup.accounting_transactions" :key="trans.id">
                                                    <td>{{ formatDate(trans.date) }}</td>
                                                    <td class="fw-bold">€{{ formatCurrency(trans.amount) }}</td>
                                                    <td>{{ trans.type || '-' }}</td>
                                                    <td>{{ trans.installment || '-' }}</td>
                                                    <td>{{ trans.payment_reason || '-' }}</td>
                                                    <td>
                                                        <span class="badge" :class="getTransactionStatusBadgeClass(trans.status)">
                                                            {{ trans.status || '-' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-center text-muted py-3">
                                        <p class="mb-0">Nessun movimento contabile presente</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Popup Task -->
                        <div v-if="showTasksModal" class="popup-overlay" @click="closeTasksPopup">
                            <div class="popup-content" @click.stop>
                                <div class="popup-header">
                                    <h6 class="mb-0">Task - Servizio #{{ selectedServiceForPopup?.reference_number || selectedServiceForPopup?.id }}</h6>
                                    <button type="button" class="btn-close" @click="closeTasksPopup"></button>
                                </div>
                                <div class="popup-body">
                                    <div v-if="selectedServiceForPopup?.tasks && selectedServiceForPopup.tasks.length > 0" class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Scadenza</th>
                                                    <th>Stato</th>
                                                    <th>Assegnato a</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="task in selectedServiceForPopup.tasks" :key="task.id">
                                                    <td>{{ task.name }}</td>
                                                    <td>{{ task.due_date ? formatDate(task.due_date) : '-' }}</td>
                                                    <td>
                                                        <span class="badge" :class="getTaskStatusBadgeClass(task.status)">
                                                            {{ getTaskStatusLabel(task.status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div v-if="task.assigned_users && task.assigned_users.length > 0">
                                                            <span v-for="(user, idx) in task.assigned_users" :key="user.id">
                                                                {{ user.name }}<span v-if="idx < task.assigned_users.length - 1">, </span>
                                                            </span>
                                                        </div>
                                                        <div v-else>-</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-center text-muted py-3">
                                        <p class="mb-0">Nessun task presente</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Popup Sovrapposizioni -->
                        <div v-if="showOverlapsModal" class="popup-overlay" @click="closeOverlapsPopup">
                            <div class="popup-content popup-content-large" @click.stop>
                                <div class="popup-header">
                                    <h6 class="mb-0">
                                        <i class="ri-error-warning-line text-warning me-2"></i>
                                        Sovrapposizioni - Servizio #{{ selectedServiceForOverlaps?.reference_number || selectedServiceForOverlaps?.id }}
                                    </h6>
                                    <button type="button" class="btn-close" @click="closeOverlapsPopup"></button>
                                </div>
                                <div class="popup-body">
                                    <!-- Informazioni sul servizio corrente -->
                                    <div class="alert alert-light border mb-3">
                                        <h6 class="mb-2"><i class="ri-car-line me-1"></i> Servizio Corrente</h6>
                                        <div class="row small">
                                            <div class="col-md-4">
                                                <strong>Veicolo:</strong> {{ selectedServiceForOverlaps?.vehicle ? `${selectedServiceForOverlaps.vehicle.license_plate} - ${selectedServiceForOverlaps.vehicle.brand} ${selectedServiceForOverlaps.vehicle.model}` : '-' }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Uscita:</strong> {{ formatDate(selectedServiceForOverlaps?.vehicle_departure_datetime) }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Rientro:</strong> {{ formatDate(selectedServiceForOverlaps?.vehicle_return_datetime) }}
                                            </div>
                                        </div>
                                        <div class="row small mt-1" v-if="selectedServiceForOverlaps?.drivers?.length">
                                            <div class="col-12">
                                                <strong>Driver:</strong>
                                                <span v-for="(driver, idx) in selectedServiceForOverlaps.drivers" :key="driver.id">
                                                    {{ driver.surname }} {{ driver.name }}<span v-if="idx < selectedServiceForOverlaps.drivers.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabella sovrapposizioni -->
                                    <div v-if="allOverlaps.length > 0" class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Servizio</th>
                                                    <th>Tipo</th>
                                                    <th>Risorsa Sovrapposta</th>
                                                    <th>Periodo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="overlap in allOverlaps" :key="overlap.id">
                                                    <td>
                                                        <Link
                                                            :href="route('easyncc.services.edit', overlap.related_service_id)"
                                                            class="text-primary"
                                                        >
                                                            #{{ overlap.related_service_reference || overlap.related_service_id }}
                                                        </Link>
                                                    </td>
                                                    <td>
                                                        <span class="badge" :class="getOverlapTypeBadgeClass(overlap.overlap_type)">
                                                            {{ getOverlapTypeLabel(overlap.overlap_type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div v-if="overlap.overlap_type === 'vehicle' || overlap.overlap_type === 'both'">
                                                            <i class="ri-car-line me-1"></i>
                                                            {{ overlap.vehicle ? `${overlap.vehicle.license_plate} - ${overlap.vehicle.brand} ${overlap.vehicle.model}` : '-' }}
                                                        </div>
                                                        <div v-if="overlap.overlap_type === 'driver' || overlap.overlap_type === 'both'">
                                                            <i class="ri-user-line me-1"></i>
                                                            {{ overlap.driver ? `${overlap.driver.surname} ${overlap.driver.name}` : '-' }}
                                                        </div>
                                                    </td>
                                                    <td class="small">
                                                        {{ formatDate(overlap.service_departure) }} -
                                                        {{ formatDate(overlap.service_return) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-center text-muted py-3">
                                        <p class="mb-0">Nessuna sovrapposizione presente</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Data -->
                        <div v-else-if="!loading && services.length === 0" class="text-center text-muted py-5">
                            <p>Nessun servizio trovato</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Passengers Modal -->
        <BModal
            v-model="showPassengersModalFlag"
            title="Lista Passeggeri"
            size="lg"
            hide-footer
            centered
        >
            <div v-if="selectedService">
                <h6 class="mb-3">
                    Servizio: <strong>#{{ selectedService.reference_number || selectedService.id }}</strong>
                </h6>

                <div v-if="selectedService.passengers && selectedService.passengers.length > 0" class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Passeggero</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Nazionalità</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(passenger, idx) in selectedService.passengers" :key="idx">
                                <td>{{ idx + 1 }}</td>
                                <td>
                                    <span v-if="passenger.nationality">{{ getNationalityFlag(passenger.nationality) }} </span>
                                    <span class="fw-bold text-uppercase">{{ passenger.surname }}</span> {{ passenger.name }}
                                </td>
                                <td>
                                    <span v-if="passenger.phone">
                                        <i class="ri-phone-line"></i> {{ passenger.phone }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span v-if="passenger.email">
                                        <i class="ri-mail-line"></i> {{ passenger.email }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span v-if="passenger.nationality">{{ passenger.nationality }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-4">
                    <i class="ri-user-line display-4 text-muted"></i>
                    <p class="text-muted mt-2">Nessun passeggero registrato</p>
                </div>
            </div>
        </BModal>

        <!-- Economics Modal -->
        <BModal
            v-model="showEconomicsModalFlag"
            title="Dettagli Economici"
            size="lg"
            hide-footer
            centered
        >
            <div v-if="selectedService">
                <h6 class="mb-3">
                    Servizio: <strong>#{{ selectedService.reference_number || selectedService.id }}</strong>
                </h6>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td class="fw-bold">Prezzo Totale</td>
                                <td class="text-end">
                                    <span class="badge bg-success fs-6 px-3 py-2">€{{ formatCurrency(selectedService.service_price) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="ri-wallet-3-line me-1 text-primary"></i>
                                    Acconto
                                </td>
                                <td class="text-end">€{{ formatCurrency(selectedService.deposit_amount) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="ri-money-euro-circle-line me-1 text-success"></i>
                                    Saldo Imponibile
                                </td>
                                <td class="text-end">€{{ formatCurrency(selectedService.balance_taxable) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="ri-percent-line me-1 text-warning"></i>
                                    Diritti di Agenzia
                                </td>
                                <td class="text-end">€{{ formatCurrency(selectedService.balance_handling_fees) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="ri-bank-card-line me-1 text-info"></i>
                                    Commissioni Carta
                                </td>
                                <td class="text-end">€{{ formatCurrency(selectedService.balance_card_fees) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const services = ref([]);
const loading = ref(false);
const error = ref('');
const selectedServices = ref([]);
const selectAll = ref(false);

// Passengers Modal
const showPassengersModalFlag = ref(false);
const selectedService = ref(null);

// Economics Modal
const showEconomicsModalFlag = ref(false);

// Inline editing
const editingDressCode = ref(null);
const editingDressCodeValue = ref(null);
const dressCodeInputRefs = ref({});

const editingContactName = ref(null);
const editingContactNameValue = ref(null);
const contactNameInputRefs = ref({});

const editingPassengerCount = ref(null);
const editingPassengerCountValue = ref(null);
const passengerCountInputRefs = ref({});

const editingVehicle = ref(null);
const editingVehicleValue = ref(null);
const vehicleInputRefs = ref({});

const editingDrivers = ref(null);
const editingDriversValue = ref([]);
const driversInputRefs = ref({});

// Dictionaries
const serviceTypes = ref([]);
const clients = ref([]);
const intermediaries = ref([]);
const drivers = ref([]);
const vehicles = ref([]);
const companies = ref([]);
const dressCodes = ref([]);
const currentUser = ref(null);

// Filters
const filters = ref({
    reference_name: '',
    pickup_date_from: '',
    pickup_date_to: '',
    dropoff_date_from: '',
    dropoff_date_to: '',
    service_type_id: '',
    client_id: '',
    intermediary_id: '',
    driver_id: '',
    vehicle_id: '',
    status: '',
    company_id: ''
});

// Show/Hide filters
const showFilters = ref(true);

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(value => value !== '').length;
});

// Sorting
const sortField = ref('pickup_datetime');
const sortDirection = ref('asc');

const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');

const sortedServices = computed(() => {
    const sorted = [...services.value];
    sorted.sort((a, b) => {
        let aVal = a[sortField.value];
        let bVal = b[sortField.value];

        // Handle nested objects
        if (sortField.value === 'client_id') {
            aVal = a.client?.name || '';
            bVal = b.client?.name || '';
        } else if (sortField.value === 'vehicle_id') {
            aVal = a.vehicle?.license_plate || '';
            bVal = b.vehicle?.license_plate || '';
        } else if (sortField.value === 'company_id') {
            aVal = a.company?.name || '';
            bVal = b.company?.name || '';
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
    return sorted;
});

const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
    } catch (err) {
        console.error('Error loading current user:', err);
    }
};

const loadDictionaries = async () => {
    try {
        console.log('Loading dictionaries...');
        const requests = [
            axios.get('/api/dictionaries/service-types'),
            axios.get('/api/users', { params: { role: 'collaboratore', is_client: true, per_page: 1000 } }),
            axios.get('/api/users', { params: { is_intermediary: true, per_page: 1000 } }),
            axios.get('/api/users', { params: { role: 'driver', per_page: 1000 } }),
            axios.get('/api/vehicles', { params: { per_page: 1000 } }),
            axios.get('/api/dictionaries/dress-codes')
        ];

        // Se l'utente è super-admin, carica anche le aziende
        if (isSuperAdmin.value) {
            requests.push(axios.get('/api/companies', { params: { per_page: 1000 } }));
        }

        const responses = await Promise.all(requests);

        console.log('Dictionaries loaded:', {
            types: responses[0].data,
            clients: responses[1].data,
            intermediaries: responses[2].data,
            drivers: responses[3].data,
            vehicles: responses[4].data,
            dressCodes: responses[5].data,
            companies: responses[6]?.data
        });

        serviceTypes.value = responses[0].data.data || [];
        clients.value = responses[1].data.data || [];
        intermediaries.value = responses[2].data.data || [];
        drivers.value = responses[3].data.data || [];
        vehicles.value = responses[4].data.data || [];
        dressCodes.value = responses[5].data.data || [];

        if (isSuperAdmin.value && responses[6]) {
            companies.value = responses[6].data.data || [];
        }
    } catch (err) {
        console.error('Error loading dictionaries:', err);
        console.error('Error details:', err.response?.data);
    }
};

let debounceTimer = null;
const debouncedLoadServices = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadServices();
    }, 500);
};

const showPassengersModal = (service) => {
    selectedService.value = service;
    showPassengersModalFlag.value = true;
};

const showEconomicsModal = (service) => {
    selectedService.value = service;
    showEconomicsModalFlag.value = true;
};

const loadServices = async () => {
    loading.value = true;
    error.value = '';

    try {
        console.log('Loading services with filters:', filters.value);
        const response = await axios.get('/api/services', {
            params: {
                ...filters.value,
                with_counts: true,
                per_page: 1000
            }
        });
        console.log('Services API response:', response.data);
        services.value = response.data.data || [];
        console.log('Loaded services:', services.value.length);
    } catch (err) {
        error.value = 'Errore nel caricamento dei servizi';
        console.error('Error loading services:', err);
        console.error('Error response:', err.response?.data);
    } finally {
        loading.value = false;
    }
};

const deleteService = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo servizio?')) {
        return;
    }

    try {
        await axios.delete(`/api/services/${id}`);
        await loadServices();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione del servizio';
        console.error('Error deleting service:', err);
    }
};

const deleteSelected = async () => {
    if (!confirm(`Sei sicuro di voler eliminare ${selectedServices.value.length} servizi?`)) {
        return;
    }

    try {
        await Promise.all(selectedServices.value.map(id => axios.delete(`/api/services/${id}`)));
        selectedServices.value = [];
        selectAll.value = false;
        await loadServices();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione dei servizi';
        console.error('Error deleting services:', err);
    }
};

// Inline dress code editing functions
const startEditDressCode = (service) => {
    editingDressCode.value = service.id;
    editingDressCodeValue.value = service.dress_code_id;
    // Focus select in next tick using dynamic ref
    nextTick(() => {
        const select = dressCodeInputRefs.value[service.id];
        if (select) {
            select.focus();
        }
    });
};

const saveDressCode = async (service) => {
    // Prevent duplicate saves
    if (!editingDressCode.value || editingDressCode.value !== service.id) {
        return;
    }

    try {
        const payload = {
            dress_code_id: editingDressCodeValue.value
        };

        await axios.put(`/api/services/${service.id}`, payload);

        // Update local service data
        const serviceIndex = services.value.findIndex(s => s.id === service.id);
        if (serviceIndex !== -1) {
            services.value[serviceIndex].dress_code_id = editingDressCodeValue.value;
            if (editingDressCodeValue.value) {
                const dressCode = dressCodes.value.find(dc => dc.id === editingDressCodeValue.value);
                services.value[serviceIndex].dress_code = dressCode;
            } else {
                services.value[serviceIndex].dress_code = null;
            }
        }

        // Clear editing state
        editingDressCode.value = null;
        editingDressCodeValue.value = null;
    } catch (err) {
        error.value = 'Errore nell\'aggiornamento del dress code';
        console.error('Error updating dress code:', err);
        // Reload services to revert changes in case of error
        await loadServices();
        // Clear editing state even on error
        editingDressCode.value = null;
        editingDressCodeValue.value = null;
    }
};

const cancelEditDressCode = () => {
    editingDressCode.value = null;
    editingDressCodeValue.value = null;
};

// Inline vehicle editing functions
const startEditVehicle = (service) => {
    editingVehicle.value = service.id;
    editingVehicleValue.value = service.vehicle_id;
    // Focus select in next tick using dynamic ref
    nextTick(() => {
        const select = vehicleInputRefs.value[service.id];
        if (select) {
            select.focus();
        }
    });
};

const saveVehicle = async (service) => {
    // Prevent duplicate saves
    if (!editingVehicle.value || editingVehicle.value !== service.id) {
        return;
    }

    try {
        const payload = {
            vehicle_id: editingVehicleValue.value
        };

        await axios.put(`/api/services/${service.id}`, payload);

        // Update local service data
        const serviceIndex = services.value.findIndex(s => s.id === service.id);
        if (serviceIndex !== -1) {
            services.value[serviceIndex].vehicle_id = editingVehicleValue.value;
            if (editingVehicleValue.value) {
                const vehicle = vehicles.value.find(v => v.id === editingVehicleValue.value);
                services.value[serviceIndex].vehicle = vehicle;
            } else {
                services.value[serviceIndex].vehicle = null;
            }
        }

        // Clear editing state
        editingVehicle.value = null;
        editingVehicleValue.value = null;
    } catch (err) {
        error.value = 'Errore nell\'aggiornamento del veicolo';
        console.error('Error updating vehicle:', err);
        // Reload services to revert changes in case of error
        await loadServices();
        // Clear editing state even on error
        editingVehicle.value = null;
        editingVehicleValue.value = null;
    }
};

const cancelEditVehicle = () => {
    editingVehicle.value = null;
    editingVehicleValue.value = null;
};

// Inline drivers editing functions
const startEditDrivers = (service) => {
    editingDrivers.value = service.id;
    editingDriversValue.value = service.drivers ? service.drivers.map(d => d.id) : [];
    nextTick(() => {
        const select = driversInputRefs.value[service.id];
        if (select) {
            select.focus();
        }
    });
};

const saveDrivers = async (service) => {
    if (!editingDrivers.value || editingDrivers.value !== service.id) {
        return;
    }

    try {
        const payload = {
            driver_ids: editingDriversValue.value
        };

        await axios.put(`/api/services/${service.id}`, payload);

        // Reload services to get updated driver data with colors
        await loadServices();

        editingDrivers.value = null;
        editingDriversValue.value = [];
    } catch (err) {
        error.value = 'Errore nell\'aggiornamento degli autisti';
        console.error('Error updating drivers:', err);
        await loadServices();
        editingDrivers.value = null;
        editingDriversValue.value = [];
    }
};

const cancelEditDrivers = () => {
    editingDrivers.value = null;
    editingDriversValue.value = [];
};

// Inline contact name editing functions
const startEditContactName = (service) => {
    editingContactName.value = service.id;
    editingContactNameValue.value = service.contact_name || '';
    // Focus input in next tick using dynamic ref
    nextTick(() => {
        const input = contactNameInputRefs.value[service.id];
        if (input) {
            input.focus();
        }
    });
};

const saveContactName = async (service) => {
    // Prevent duplicate saves
    if (!editingContactName.value || editingContactName.value !== service.id) {
        return;
    }

    try {
        const payload = {
            contact_name: editingContactNameValue.value || null
        };

        await axios.put(`/api/services/${service.id}`, payload);

        // Update local service data
        const serviceIndex = services.value.findIndex(s => s.id === service.id);
        if (serviceIndex !== -1) {
            services.value[serviceIndex].contact_name = editingContactNameValue.value || null;
        }

        // Clear editing state
        editingContactName.value = null;
        editingContactNameValue.value = null;
    } catch (err) {
        error.value = 'Errore nell\'aggiornamento del nominativo';
        console.error('Error updating contact name:', err);
        // Reload services to revert changes in case of error
        await loadServices();
        // Clear editing state even on error
        editingContactName.value = null;
        editingContactNameValue.value = null;
    }
};

const cancelEditContactName = () => {
    editingContactName.value = null;
    editingContactNameValue.value = null;
};

// Passenger Count inline editing methods
const startEditPassengerCount = (service) => {
    editingPassengerCount.value = service.id;
    editingPassengerCountValue.value = service.passenger_count || 0;
    nextTick(() => {
        const input = passengerCountInputRefs.value[service.id];
        if (input) {
            input.focus();
            input.select();
        }
    });
};

const savePassengerCount = async (service) => {
    if (!editingPassengerCount.value || editingPassengerCount.value !== service.id) {
        return;
    }

    // Validate: passenger_count must be >= 0
    if (editingPassengerCountValue.value < 0) {
        error.value = 'Il numero di passeggeri non può essere negativo';
        return;
    }

    try {
        const payload = {
            passenger_count: editingPassengerCountValue.value || 0
        };

        await axios.put(`/api/services/${service.id}`, payload);

        const serviceIndex = services.value.findIndex(s => s.id === service.id);
        if (serviceIndex !== -1) {
            services.value[serviceIndex].passenger_count = editingPassengerCountValue.value || 0;
        }

        editingPassengerCount.value = null;
        editingPassengerCountValue.value = null;
    } catch (err) {
        error.value = 'Errore nell\'aggiornamento del numero passeggeri';
        console.error('Error updating passenger count:', err);
        await loadServices();
        editingPassengerCount.value = null;
        editingPassengerCountValue.value = null;
    }
};

const cancelEditPassengerCount = () => {
    editingPassengerCount.value = null;
    editingPassengerCountValue.value = null;
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedServices.value = services.value.map(s => s.id);
    } else {
        selectedServices.value = [];
    }
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const resetFilters = () => {
    filters.value = {
        reference_name: '',
        pickup_date_from: '',
        pickup_date_to: '',
        dropoff_date_from: '',
        dropoff_date_to: '',
        service_type_id: '',
        client_id: '',
        intermediary_id: '',
        driver_id: '',
        vehicle_id: '',
        status: ''
    };
    loadServices();
};

const formatDate = (datetime) => {
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const formatTime = (datetime) => {
    return datetime ? moment(datetime).format('HH:mm') : '-';
};

const formatCurrency = (value) => {
    return value ? parseFloat(value).toFixed(2) : '0.00';
};

// Get nationality flag emoji
const getNationalityFlag = (nationality) => {
    const countryFlags = {
        'Italia': '🇮🇹',
        'Stati Uniti': '🇺🇸',
        'Regno Unito': '🇬🇧',
        'Germania': '🇩🇪',
        'Francia': '🇫🇷',
        'Spagna': '🇪🇸',
        'Portogallo': '🇵🇹',
        'Paesi Bassi': '🇳🇱',
        'Belgio': '🇧🇪',
        'Svizzera': '🇨🇭',
        'Austria': '🇦🇹',
        'Polonia': '🇵🇱',
        'Svezia': '🇸🇪',
        'Norvegia': '🇳🇴',
        'Danimarca': '🇩🇰',
        'Finlandia': '🇫🇮',
        'Irlanda': '🇮🇪',
        'Grecia': '🇬🇷',
        'Repubblica Ceca': '🇨🇿',
        'Ungheria': '🇭🇺',
        'Romania': '🇷🇴',
        'Bulgaria': '🇧🇬',
        'Croazia': '🇭🇷',
        'Slovacchia': '🇸🇰',
        'Slovenia': '🇸🇮',
        'Lituania': '🇱🇹',
        'Lettonia': '🇱🇻',
        'Estonia': '🇪🇪',
        'Giappone': '🇯🇵',
        'Cina': '🇨🇳',
        'Corea del Sud': '🇰🇷',
        'India': '🇮🇳',
        'Brasile': '🇧🇷',
        'Argentina': '🇦🇷',
        'Messico': '🇲🇽',
        'Canada': '🇨🇦',
        'Australia': '🇦🇺',
        'Nuova Zelanda': '🇳🇿',
        'Sudafrica': '🇿🇦',
        'Russia': '🇷🇺',
        'Turchia': '🇹🇷',
        'Arabia Saudita': '🇸🇦',
        'Emirati Arabi Uniti': '🇦🇪',
        'Israele': '🇮🇱',
        'Egitto': '🇪🇬',
        'Marocco': '🇲🇦',
        'Nigeria': '🇳🇬',
        'Kenya': '🇰🇪',
        'Tailandia': '🇹🇭',
        'Singapore': '🇸🇬',
        'Malesia': '🇲🇾',
        'Indonesia': '🇮🇩',
        'Filippine': '🇵🇭',
        'Vietnam': '🇻🇳'
    };

    return countryFlags[nationality] || '🌍';
};

// Popup variables
const showTransactionsModal = ref(false);
const showTasksModal = ref(false);
const showOverlapsModal = ref(false);
const selectedServiceForPopup = ref(null);
const selectedServiceForOverlaps = ref(null);
const allOverlaps = ref([]);

// Status badge classes
const getStatusBadgeClass = (status) => {
    const statusMap = {
        'preventivo': 'bg-secondary',
        'confermato': 'bg-success',
        'in corso': 'bg-primary',
        'completato': 'bg-info',
        'cancellato': 'bg-danger',
        'no-show': 'bg-warning'
    };
    return statusMap[status?.toLowerCase()] || 'bg-secondary';
};

// Task functions
const getCompletedTasksCount = (service) => {
    return service.tasks_count - (service.incomplete_tasks_count || 0);
};

const getTaskIconClass = (service) => {
    const completedCount = getCompletedTasksCount(service);
    const totalCount = service.tasks_count;

    if (completedCount === totalCount) {
        return 'text-success';
    } else if (completedCount === 0) {
        return 'text-danger';
    } else {
        return 'text-warning';
    }
};

const getTaskStatusBadgeClass = (status) => {
    const statusMap = {
        'to_complete': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return statusMap[status] || 'bg-secondary';
};

const getTaskStatusLabel = (status) => {
    const labelMap = {
        'to_complete': 'Da completare',
        'completed': 'Completato',
        'cancelled': 'Cancellato'
    };
    return labelMap[status] || status;
};

// Transaction status badge classes
const getTransactionStatusBadgeClass = (status) => {
    const statusMap = {
        'pending': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger',
        'in_attesa': 'bg-warning',
        'completato': 'bg-success',
        'cancellato': 'bg-danger'
    };
    return statusMap[status?.toLowerCase()] || 'bg-secondary';
};

// Popup functions
const showTransactionsPopup = async (service) => {
    try {
        // Load full service data with accounting transactions
        const response = await axios.get(`/api/services/${service.id}`);
        // The API returns the service directly without a 'data' wrapper
        selectedServiceForPopup.value = response.data;
        showTransactionsModal.value = true;
    } catch (err) {
        console.error('Error loading transactions:', err);
        error.value = 'Errore nel caricamento dei movimenti contabili';
    }
};

const closeTransactionsPopup = () => {
    showTransactionsModal.value = false;
    selectedServiceForPopup.value = null;
};

const showTasksPopup = async (service) => {
    try {
        // Load full service data with tasks
        const response = await axios.get(`/api/services/${service.id}`);
        // The API returns the service directly without a 'data' wrapper
        selectedServiceForPopup.value = response.data;
        showTasksModal.value = true;
    } catch (err) {
        console.error('Error loading tasks:', err);
        error.value = 'Errore nel caricamento dei task';
    }
};

const closeTasksPopup = () => {
    showTasksModal.value = false;
    selectedServiceForPopup.value = null;
};

// Overlaps functions
const getTotalOverlapsCount = (service) => {
    return (service.overlaps_count || 0) + (service.overlapped_by_count || 0);
};

const showOverlapsPopup = async (service) => {
    try {
        // Load full service data with overlaps
        const response = await axios.get(`/api/services/${service.id}`);
        selectedServiceForOverlaps.value = response.data;

        // Combine overlaps and overlappedBy into a single array
        const overlaps = [];

        // Add overlaps (where this service overlaps others)
        if (service.overlaps && service.overlaps.length > 0) {
            service.overlaps.forEach(o => {
                overlaps.push({
                    id: o.id,
                    overlap_type: o.overlap_type,
                    related_service_id: o.overlapping_service?.id || o.overlapping_service_id,
                    related_service_reference: o.overlapping_service?.reference_number,
                    service_departure: o.overlapping_service?.vehicle_departure_datetime,
                    service_return: o.overlapping_service?.vehicle_return_datetime,
                    vehicle: o.vehicle,
                    driver: o.driver
                });
            });
        }

        // Add overlappedBy (where other services overlap this one)
        if (service.overlapped_by && service.overlapped_by.length > 0) {
            service.overlapped_by.forEach(o => {
                overlaps.push({
                    id: `by_${o.id}`,
                    overlap_type: o.overlap_type,
                    related_service_id: o.service?.id || o.service_id,
                    related_service_reference: o.service?.reference_number,
                    service_departure: o.service?.vehicle_departure_datetime,
                    service_return: o.service?.vehicle_return_datetime,
                    vehicle: o.vehicle,
                    driver: o.driver
                });
            });
        }

        allOverlaps.value = overlaps;
        showOverlapsModal.value = true;
    } catch (err) {
        console.error('Error loading overlaps:', err);
        error.value = 'Errore nel caricamento delle sovrapposizioni';
    }
};

const closeOverlapsPopup = () => {
    showOverlapsModal.value = false;
    selectedServiceForOverlaps.value = null;
    allOverlaps.value = [];
};

const getOverlapTypeBadgeClass = (type) => {
    const typeMap = {
        'vehicle': 'bg-info',
        'driver': 'bg-warning text-dark',
        'both': 'bg-danger'
    };
    return typeMap[type] || 'bg-secondary';
};

const getOverlapTypeLabel = (type) => {
    const typeMap = {
        'vehicle': 'Veicolo',
        'driver': 'Driver',
        'both': 'Veicolo + Driver'
    };
    return typeMap[type] || type;
};

onMounted(async () => {
    await loadCurrentUser();
    await loadDictionaries();
    await loadServices();
});
</script>

<style scoped>
/* Cursor pointer for clickable elements */
.cursor-pointer {
    cursor: pointer;
}

/* Bordi leggeri per la tabella */
.table-bordered {
    border-color: #e9ecef !important;
}

.table-bordered > :not(caption) > * {
    border-width: 1px;
    border-color: #e9ecef !important;
}

.table-bordered > :not(caption) > * > * {
    border-color: #e9ecef !important;
}

.targa-auto {
    /* Stile targa italiana con bande blu laterali */
    background: linear-gradient(to right, #003399 0%, #003399 8%, #ffffff 8%, #ffffff 92%, #003399 92%, #003399 100%);
    border: 1px solid #000;
    border-radius: 3px;
    padding: 3px 8px;
    display: inline-block;
    font-family: 'Arial', sans-serif;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    min-width: 90px;
}

.codice-targa {
    /* Stile per il testo della targa */
    font-size: 14px;
    font-weight: bold;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table-responsive {
    font-size: 0.875rem;
}

.table td {
    vertical-align: top;
    padding: 0.75rem 0.5rem;
}

.table th {
    white-space: nowrap;
}

/* Popup Styles */
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.popup-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.popup-content-large {
    max-width: 900px;
}

.popup-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 0.5rem 0.5rem 0 0;
}

.popup-header h6 {
    margin: 0;
    font-weight: 600;
}

.popup-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.popup-body .table {
    margin-bottom: 0;
}
</style>
