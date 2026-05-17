<template>
    <Head title="Servizi - Vista Compatta" />

    <Layout :collapsed-sidebar="true">
        <!-- Barra filtri compatta (sostituisce PageHeader + Card Header + Filtri) -->
        <div class="compact-toolbar bg-white border-bottom px-3 py-2 sticky-top" style="z-index: 100;">
            <!-- Riga 1: Titolo + Layout switcher + Azioni -->
            <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold">Servizi</h6>
                    <span v-if="totalItems > 0" class="badge bg-secondary-subtle text-secondary">{{ totalItems }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Layout switcher -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button
                            type="button"
                            class="btn"
                            :class="activeLayout === 'cards' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeLayout = 'cards'"
                            title="Vista Card"
                        >
                            <i class="ri-layout-grid-line"></i>
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="activeLayout === 'table' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeLayout = 'table'"
                            title="Vista Tabella Compatta"
                        >
                            <i class="ri-table-line"></i>
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="activeLayout === 'hybrid' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeLayout = 'hybrid'"
                            title="Vista Ibrida"
                        >
                            <i class="ri-layout-row-line"></i>
                        </button>
                    </div>
                    <!-- Ordinamento -->
                    <select v-model="sortField" @change="onSortChange" class="form-select form-select-sm" style="width: auto; font-size: 0.75rem;">
                        <option value="pickup_datetime">Data</option>
                        <option value="reference_number">Riferimento</option>
                        <option value="service_price">Prezzo</option>
                        <option value="client_id">Committente</option>
                        <option value="vehicle_id">Veicolo</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" @click="toggleSortDirection" :title="sortDirection === 'asc' ? 'Crescente' : 'Decrescente'">
                        <i :class="sortDirection === 'asc' ? 'ri-sort-asc' : 'ri-sort-desc'"></i>
                    </button>
                    <Link v-if="!isDriver" :href="withReturnUrl(route('easyncc.services.create'))" class="btn btn-primary btn-sm">
                        <i class="ri-add-line"></i> Nuovo
                    </Link>
                </div>
            </div>

            <!-- Riga 2: Preset rapidi + Filtri inline -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Navigazione giorni -->
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary btn-sm py-0 px-1" @click="shiftDates(-1)" title="Giorno precedente">
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-1" @click="shiftDates(1)" title="Giorno successivo">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>

                <!-- Preset buttons -->
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-sm py-0" :class="activePreset === 'tutti' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterAll">Tutti</button>
                    <button class="btn btn-sm py-0" :class="activePreset === 'da_oggi' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterFromToday">Da oggi</button>
                    <button class="btn btn-sm py-0" :class="activePreset === 'oggi' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterToday">Oggi</button>
                    <button class="btn btn-sm py-0" :class="activePreset === 'domani' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterTomorrow">Domani</button>
                    <button class="btn btn-sm py-0" :class="activePreset === 'settimana' ? 'btn-primary' : 'btn-outline-secondary'" @click="filterWeek">Settimana</button>
                </div>

                <input type="date" v-model="specificDate" @change="filterSpecificDate" class="form-control form-control-sm py-0" style="width: 130px; font-size: 0.75rem;" />

                <!-- Separatore -->
                <span class="text-muted" style="font-size: 0.75rem;">|</span>

                <!-- Intervallo date Da/A -->
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted" style="font-size: 0.7rem;">Da</span>
                    <input type="date" v-model="filters.date_from" @change="loadServicesFromFilter" class="form-control form-control-sm py-0" style="width: 130px; font-size: 0.75rem;" />
                    <span class="text-muted" style="font-size: 0.7rem;">A</span>
                    <input type="date" v-model="filters.date_to" @change="loadServicesFromFilter" class="form-control form-control-sm py-0" style="width: 130px; font-size: 0.75rem;" />
                </div>

                <!-- Cerca -->
                <input v-model="filters.reference_name" type="text" @input="debouncedLoadServices" class="form-control form-control-sm py-0" placeholder="Cerca..." style="width: 150px; font-size: 0.75rem;" />

                <!-- Filtro stato -->
                <select v-model="filters.status" @change="loadServicesFromFilter" class="form-select form-select-sm py-0" style="width: 120px; font-size: 0.75rem;">
                    <option value="">Stato...</option>
                    <option v-for="st in serviceStatuses" :key="st.id" :value="st.id">{{ st.name }}</option>
                </select>

                <!-- Filtri avanzati toggle -->
                <button class="btn btn-sm py-0" :class="showAdvancedFilters ? 'btn-info' : 'btn-outline-secondary'" @click="showAdvancedFilters = !showAdvancedFilters">
                    <i class="ri-filter-3-line"></i>
                    <span v-if="activeFiltersCount > 2" class="badge bg-primary ms-1" style="font-size: 0.6rem;">{{ activeFiltersCount - (filters.reference_name ? 1 : 0) - (filters.status ? 1 : 0) }}</span>
                </button>

                <!-- Reset -->
                <button v-if="hasActiveFilters" class="btn btn-sm btn-outline-danger py-0" @click="filterAll" title="Reset filtri">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Riga 3: Filtri avanzati (collassabili) -->
            <div v-show="showAdvancedFilters" class="mt-2 pt-2 border-top">
                <div class="d-flex gap-2 flex-wrap">
                    <div style="min-width: 140px;">
                        <select v-model="filters.service_type_id" @change="loadServicesFromFilter" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                            <option value="">Tipo servizio...</option>
                            <option v-for="type in serviceTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                    <div style="min-width: 140px;">
                        <select v-model="filters.driver_id" @change="loadServicesFromFilter" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                            <option value="">Autista...</option>
                            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ driverLabel(d) }}</option>
                        </select>
                    </div>
                    <div style="min-width: 140px;">
                        <select v-model="filters.vehicle_id" @change="loadServicesFromFilter" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                            <option value="">Veicolo...</option>
                            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.brand }} {{ v.model }} ({{ v.plate }})</option>
                        </select>
                    </div>
                    <div style="min-width: 140px;">
                        <Multiselect
                            v-model="filters.client_id"
                            :options="searchClients"
                            :searchable="true"
                            :min-chars="2"
                            :delay="300"
                            :resolve-on-load="false"
                            placeholder="Committente..."
                            label="label"
                            value-prop="id"
                            @change="() => nextTick(loadServicesFromFilter)"
                            class="multiselect-sm"
                        />
                    </div>
                    <div style="min-width: 140px;">
                        <Multiselect
                            v-model="filters.intermediary_id"
                            :options="searchIntermediaries"
                            :searchable="true"
                            :min-chars="2"
                            :delay="300"
                            :resolve-on-load="false"
                            placeholder="Intermediario..."
                            label="label"
                            value-prop="id"
                            @change="() => nextTick(loadServicesFromFilter)"
                            class="multiselect-sm"
                        />
                    </div>
                    <div v-if="isSuperAdmin" style="min-width: 140px;">
                        <select v-model="filters.company_id" @change="loadServicesFromFilter" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                            <option value="">Azienda...</option>
                            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenuto principale -->
        <div class="compact-content px-3 py-2">
            <!-- Loading -->
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2 text-muted small">Caricamento...</span>
            </div>

            <!-- Nessun risultato -->
            <div v-else-if="services.length === 0" class="text-center py-5 text-muted">
                <i class="ri-inbox-line" style="font-size: 2rem;"></i>
                <p class="mt-2">Nessun servizio trovato</p>
            </div>

            <!-- ==================== LAYOUT A: CARD COMPATTE ==================== -->
            <div v-else-if="activeLayout === 'cards'">
                <div class="d-flex flex-column gap-1">
                    <div
                        v-for="service in services"
                        :key="service.id"
                        class="card-service border rounded px-3 py-2"
                        :style="service.status?.bg_color ? { borderLeftColor: service.status.bg_color, borderLeftWidth: '4px' } : {}"
                    >
                        <!-- Riga 1 -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- Stato -->
                            <span class="badge" :style="{ backgroundColor: service.status?.color_code || '#6c757d', color: '#fff', fontSize: '0.65rem', padding: '2px 6px' }">
                                {{ service.status?.name || '—' }}
                            </span>
                            <!-- Tipo servizio -->
                            <span class="badge" :style="{ ...serviceTypeBadgeStyle(service.service_type || ''), fontSize: '0.65rem', padding: '2px 6px' }">
                                {{ getServiceTypeAbbreviation(service.service_type) || '—' }}
                            </span>
                            <!-- Data/ora -->
                            <span class="fw-bold small">{{ formatDateCompact(service.pickup_datetime) }}</span>
                            <span class="text-muted small">→</span>
                            <span class="small">{{ formatTimeOnly(service.dropoff_datetime) }}</span>
                            <!-- Passeggero -->
                            <span class="ms-2">
                                <span v-if="service.passengers?.length > 0">
                                    <span v-if="service.passengers[0].nationality" class="me-1">{{ getNationalityFlag(service.passengers[0].nationality) }}</span>
                                    <span class="fw-bold text-uppercase small">{{ service.passengers[0].surname }}</span>
                                    <span class="small"> {{ service.passengers[0].name }}</span>
                                    <span v-if="service.passengers.length > 1" class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.6rem;">+{{ service.passengers.length - 1 }}</span>
                                </span>
                                <span v-else class="text-muted small fst-italic">N/A</span>
                            </span>
                            <!-- Percorso -->
                            <span class="text-muted small ms-auto d-none d-lg-inline">
                                <i class="ri-map-pin-line text-success"></i> {{ truncate(service.pickup_address, 25) }}
                                <span class="mx-1">→</span>
                                <i class="ri-map-pin-line text-danger"></i> {{ truncate(service.dropoff_address, 25) }}
                            </span>
                        </div>
                        <!-- Riga 2 -->
                        <div class="d-flex align-items-center gap-3 mt-1 flex-wrap" style="font-size: 0.75rem;">
                            <!-- Ref -->
                            <span class="text-muted">#{{ service.reference_number || service.id }}</span>
                            <!-- Driver -->
                            <span v-if="service.drivers?.length > 0" class="d-inline-flex align-items-center gap-1">
                                <span
                                    v-for="driver in service.drivers"
                                    :key="driver.id"
                                    class="d-inline-flex align-items-center gap-1"
                                >
                                    <span class="driver-dot" :style="{ backgroundColor: driver.driver_profile?.color || '#6c757d' }"></span>
                                    <span>{{ driverLabel(driver) }}</span>
                                </span>
                            </span>
                            <span v-else class="text-muted">No driver</span>
                            <!-- Veicolo -->
                            <span v-if="service.vehicle" class="d-inline-flex align-items-center gap-1">
                                <span class="targa-mini">{{ service.vehicle.license_plate }}</span>
                                <span class="text-muted">{{ service.vehicle.brand }} {{ service.vehicle.model }}</span>
                            </span>
                            <!-- Committente -->
                            <span v-if="service.client && (!isDriver || isServiceAssignedOrAccepted(service))" class="text-muted">
                                <i class="ri-building-line"></i> {{ service.client.surname + ' ' + service.client.name }}
                            </span>
                            <!-- Importi -->
                            <span class="ms-auto d-flex gap-2">
                                <span v-if="!isDriver" class="text-success fw-medium">€{{ formatCurrency(service.service_price) }}</span>
                                <span v-if="!isDriver && hasAnyCost(service)" class="text-danger">€{{ formatCurrency(getTotalCosts(service)) }}</span>
                            </span>
                            <!-- Azioni -->
                            <span class="d-flex gap-1">
                                <Link v-if="!isDriver || isServiceAssignedOrAccepted(service)" :href="withReturnUrl(route('easyncc.services.show', service.id))" class="btn btn-sm btn-soft-primary py-0 px-1" style="font-size: 0.7rem;" title="Visualizza">
                                    <i class="ri-eye-line"></i>
                                </Link>
                                <Link v-if="!isDriver" :href="withReturnUrl(route('easyncc.services.edit', service.id))" class="btn btn-sm btn-soft-info py-0 px-1" style="font-size: 0.7rem;" title="Modifica">
                                    <i class="ri-edit-line"></i>
                                </Link>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== LAYOUT B: TABELLA ULTRA-COMPATTA ==================== -->
            <div v-else-if="activeLayout === 'table'">
                <div class="table-responsive">
                    <table class="table table-hover table-sm table-compact mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="th-compact" @click="sortBy('pickup_datetime')" style="cursor: pointer;">
                                    Stato
                                </th>
                                <th class="th-compact">Tipo</th>
                                <th class="th-compact" @click="sortBy('pickup_datetime')" style="cursor: pointer;">
                                    Data
                                    <i v-if="sortField === 'pickup_datetime'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th class="th-compact">Passeggero</th>
                                <th class="th-compact">Percorso</th>
                                <th class="th-compact">Driver</th>
                                <th class="th-compact" @click="sortBy('vehicle_id')" style="cursor: pointer;">
                                    Veicolo
                                    <i v-if="sortField === 'vehicle_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th class="th-compact" @click="sortBy('client_id')" style="cursor: pointer;">
                                    Comm.
                                    <i v-if="sortField === 'client_id'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th v-if="!isDriver" class="th-compact text-end" @click="sortBy('service_price')" style="cursor: pointer;">
                                    Ricavi
                                    <i v-if="sortField === 'service_price'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th v-if="!isDriver" class="th-compact text-end">Costi</th>
                                <th class="th-compact"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="service in services" :key="service.id" class="tr-compact" :style="service.status?.bg_color ? { borderLeft: `3px solid ${service.status.bg_color}` } : {}">
                                <td class="td-compact">
                                    <span class="badge" :style="{ backgroundColor: service.status?.color_code || '#6c757d', color: '#fff', fontSize: '0.6rem', padding: '1px 5px' }">
                                        {{ service.status?.name || '—' }}
                                    </span>
                                </td>
                                <td class="td-compact">
                                    <span class="badge" :style="{ ...serviceTypeBadgeStyle(service.service_type || ''), fontSize: '0.6rem', padding: '1px 5px' }">
                                        {{ getServiceTypeAbbreviation(service.service_type) || '—' }}
                                    </span>
                                </td>
                                <td class="td-compact text-nowrap">
                                    <span class="fw-bold">{{ formatDateShort(service.pickup_datetime) }}</span>
                                    <span class="text-muted ms-1">{{ formatTimeOnly(service.pickup_datetime) }}</span>
                                </td>
                                <td class="td-compact">
                                    <template v-if="service.passengers?.length > 0">
                                        <span v-if="service.passengers[0].nationality">{{ getNationalityFlag(service.passengers[0].nationality) }} </span>
                                        <span class="fw-medium text-uppercase">{{ service.passengers[0].surname }}</span>
                                        <span v-if="service.passengers.length > 1" class="text-muted ms-1">(+{{ service.passengers.length - 1 }})</span>
                                    </template>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="td-compact">
                                    <span class="text-truncate d-inline-block" style="max-width: 120px;" :title="`${service.pickup_address || ''} → ${service.dropoff_address || ''}`">
                                        {{ truncate(service.pickup_address, 15) }} → {{ truncate(service.dropoff_address, 15) }}
                                    </span>
                                </td>
                                <td class="td-compact">
                                    <span v-if="service.drivers?.length > 0" class="d-inline-flex align-items-center gap-1">
                                        <span v-for="driver in service.drivers" :key="driver.id" class="d-inline-flex align-items-center gap-1">
                                            <span class="driver-dot" :style="{ backgroundColor: driver.driver_profile?.color || '#6c757d' }"></span>
                                            <span>{{ driver.surname }}</span>
                                        </span>
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="td-compact">
                                    <span v-if="service.vehicle" :title="`${service.vehicle.brand} ${service.vehicle.model}`">
                                        <span class="targa-mini">{{ service.vehicle.license_plate }}</span>
                                        <span class="text-muted ms-1 d-none d-xl-inline">{{ service.vehicle.brand }}</span>
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="td-compact">
                                    <template v-if="!isDriver || isServiceAssignedOrAccepted(service)">
                                        <span v-if="service.client" :title="`${service.client.surname} ${service.client.name}`">
                                            {{ truncate(service.client.surname + ' ' + service.client.name, 15) }}
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </template>
                                </td>
                                <td v-if="!isDriver" class="td-compact text-end text-success fw-medium">
                                    {{ formatCurrency(service.service_price) }}
                                </td>
                                <td v-if="!isDriver" class="td-compact text-end text-danger">
                                    <span v-if="hasAnyCost(service)">{{ formatCurrency(getTotalCosts(service)) }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="td-compact">
                                    <div class="d-flex gap-1">
                                        <Link v-if="!isDriver || isServiceAssignedOrAccepted(service)" :href="withReturnUrl(route('easyncc.services.show', service.id))" class="btn btn-sm btn-soft-primary py-0 px-1 action-btn" title="Visualizza">
                                            <i class="ri-eye-line"></i>
                                        </Link>
                                        <Link v-if="!isDriver" :href="withReturnUrl(route('easyncc.services.edit', service.id))" class="btn btn-sm btn-soft-info py-0 px-1 action-btn" title="Modifica">
                                            <i class="ri-edit-line"></i>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================== LAYOUT C: IBRIDO (righe espandibili) ==================== -->
            <div v-else-if="activeLayout === 'hybrid'">
                <div class="table-responsive">
                    <table class="table table-hover table-sm table-compact mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="th-compact" style="width: 30px;"></th>
                                <th class="th-compact">Stato</th>
                                <th class="th-compact">Tipo</th>
                                <th class="th-compact" @click="sortBy('pickup_datetime')" style="cursor: pointer;">
                                    Data Pickup
                                    <i v-if="sortField === 'pickup_datetime'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th class="th-compact">Passeggero</th>
                                <th class="th-compact">Percorso</th>
                                <th class="th-compact">Driver</th>
                                <th class="th-compact">Veicolo</th>
                                <th v-if="!isDriver" class="th-compact text-end" @click="sortBy('service_price')" style="cursor: pointer;">
                                    €
                                    <i v-if="sortField === 'service_price'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" style="font-size: 0.65rem;"></i>
                                </th>
                                <th class="th-compact"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="service in services" :key="service.id">
                                <!-- Riga compatta -->
                                <tr class="tr-compact tr-hybrid-main" :style="service.status?.bg_color ? { borderLeft: `3px solid ${service.status.bg_color}` } : {}" @click="toggleExpand(service.id)">
                                    <td class="td-compact text-center">
                                        <i :class="expandedServices.includes(service.id) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" style="font-size: 0.75rem;"></i>
                                    </td>
                                    <td class="td-compact">
                                        <span class="badge" :style="{ backgroundColor: service.status?.color_code || '#6c757d', color: '#fff', fontSize: '0.6rem', padding: '1px 5px' }">
                                            {{ service.status?.name || '—' }}
                                        </span>
                                    </td>
                                    <td class="td-compact">
                                        <span class="badge" :style="{ ...serviceTypeBadgeStyle(service.service_type || ''), fontSize: '0.6rem', padding: '1px 5px' }">
                                            {{ getServiceTypeAbbreviation(service.service_type) || '—' }}
                                        </span>
                                    </td>
                                    <td class="td-compact text-nowrap">
                                        <span class="fw-bold">{{ formatDateShort(service.pickup_datetime) }}</span>
                                        <span class="text-muted ms-1">{{ formatTimeOnly(service.pickup_datetime) }}</span>
                                        <span class="text-muted">→{{ formatTimeOnly(service.dropoff_datetime) }}</span>
                                    </td>
                                    <td class="td-compact">
                                        <template v-if="service.passengers?.length > 0">
                                            <span v-if="service.passengers[0].nationality">{{ getNationalityFlag(service.passengers[0].nationality) }} </span>
                                            <span class="fw-medium text-uppercase">{{ service.passengers[0].surname }}</span>
                                            <span v-if="service.passengers.length > 1" class="text-muted">(+{{ service.passengers.length - 1 }})</span>
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td class="td-compact">
                                        <span :title="`${service.pickup_address || ''} → ${service.dropoff_address || ''}`">
                                            {{ truncate(service.pickup_address, 15) }} → {{ truncate(service.dropoff_address, 15) }}
                                        </span>
                                    </td>
                                    <td class="td-compact">
                                        <span v-if="service.drivers?.length > 0" class="d-inline-flex align-items-center gap-1">
                                            <span v-for="driver in service.drivers" :key="driver.id" class="d-inline-flex align-items-center gap-1">
                                                <span class="driver-dot" :style="{ backgroundColor: driver.driver_profile?.color || '#6c757d' }"></span>
                                                <span>{{ driver.surname }}</span>
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td class="td-compact">
                                        <span v-if="service.vehicle" :title="`${service.vehicle.brand} ${service.vehicle.model}`">
                                            <span class="targa-mini">{{ service.vehicle.license_plate }}</span>
                                            <span class="text-muted ms-1 d-none d-xl-inline">{{ service.vehicle.brand }}</span>
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td v-if="!isDriver" class="td-compact text-end text-success fw-medium">
                                        {{ formatCurrency(service.service_price) }}
                                    </td>
                                    <td class="td-compact" @click.stop>
                                        <div class="d-flex gap-1">
                                            <Link v-if="!isDriver || isServiceAssignedOrAccepted(service)" :href="withReturnUrl(route('easyncc.services.show', service.id))" class="btn btn-sm btn-soft-primary py-0 px-1 action-btn" title="Visualizza">
                                                <i class="ri-eye-line"></i>
                                            </Link>
                                            <Link v-if="!isDriver" :href="withReturnUrl(route('easyncc.services.edit', service.id))" class="btn btn-sm btn-soft-info py-0 px-1 action-btn" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Riga espansa con dettagli -->
                                <tr v-if="expandedServices.includes(service.id)" class="tr-expanded">
                                    <td :colspan="isDriver ? 9 : 10" class="p-0">
                                        <div class="expanded-details bg-light border-top px-4 py-2">
                                            <div class="row g-3" style="font-size: 0.78rem;">
                                                <!-- Colonna 1: Dettagli temporali -->
                                                <div class="col-md-3">
                                                    <h6 class="text-muted mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Orari & Percorso</h6>
                                                    <div class="mb-1">
                                                        <span class="text-success fw-bold">Pickup:</span> {{ formatDate(service.pickup_datetime) }}
                                                        <div class="text-muted small">{{ service.pickup_address }}</div>
                                                    </div>
                                                    <div class="mb-1">
                                                        <span class="text-danger fw-bold">Dropoff:</span> {{ formatDate(service.dropoff_datetime) }}
                                                        <div class="text-muted small">{{ service.dropoff_address }}</div>
                                                    </div>
                                                    <div v-if="service.vehicle_departure_datetime" class="text-muted small">
                                                        Uscita mezzo: {{ formatDate(service.vehicle_departure_datetime) }}
                                                    </div>
                                                    <div v-if="service.vehicle_return_datetime" class="text-muted small">
                                                        Rientro mezzo: {{ formatDate(service.vehicle_return_datetime) }}
                                                    </div>
                                                </div>
                                                <!-- Colonna 2: Passeggeri & Bagagli -->
                                                <div class="col-md-3">
                                                    <h6 class="text-muted mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Passeggeri & Bagagli</h6>
                                                    <div class="mb-1">
                                                        <i class="ri-user-line"></i> {{ service.passenger_count || 0 }} passeggeri
                                                    </div>
                                                    <div v-if="service.passengers?.length > 0">
                                                        <div v-for="(p, idx) in service.passengers" :key="idx" class="small">
                                                            <span v-if="p.nationality">{{ getNationalityFlag(p.nationality) }}</span>
                                                            {{ p.surname }} {{ p.name }}
                                                            <span v-if="p.phone" class="text-muted"> - {{ p.phone }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1 small text-muted">
                                                        Bagagli: <i class="ri-luggage-cart-line"></i>{{ service.large_luggage || 0 }}
                                                        <i class="ri-briefcase-line ms-1"></i>{{ service.medium_luggage || 0 }}
                                                        <i class="ri-handbag-line ms-1"></i>{{ service.small_luggage || 0 }}
                                                    </div>
                                                    <div v-if="(service.baby_seat_infant || 0) + (service.baby_seat_standard || 0) + (service.baby_seat_booster || 0) > 0" class="small text-muted">
                                                        Seggiolini: {{ service.baby_seat_infant || 0 }} ovetto, {{ service.baby_seat_standard || 0 }} std, {{ service.baby_seat_booster || 0 }} booster
                                                    </div>
                                                </div>
                                                <!-- Colonna 3: Soggetti -->
                                                <div class="col-md-3">
                                                    <h6 class="text-muted mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Soggetti</h6>
                                                    <template v-if="!isDriver || isServiceAssignedOrAccepted(service)">
                                                        <div v-if="service.client" class="mb-1">
                                                            <span class="badge bg-primary-subtle text-primary me-1" style="font-size: 0.6rem;">COMM</span>
                                                            {{ service.client.business_name || `${service.client.surname} ${service.client.name}` }}
                                                        </div>
                                                        <div v-if="service.intermediary" class="mb-1">
                                                            <span class="badge bg-info-subtle text-info me-1" style="font-size: 0.6rem;">INTERM</span>
                                                            {{ service.intermediary.surname + ' ' + service.intermediary.name }}
                                                        </div>
                                                        <div v-if="service.supplier" class="mb-1">
                                                            <span class="badge bg-warning-subtle text-warning me-1" style="font-size: 0.6rem;">FORN</span>
                                                            {{ service.supplier.surname + ' ' + service.supplier.name }}
                                                        </div>
                                                    </template>
                                                    <div v-if="service.dress_code" class="mt-1 small text-muted">
                                                        <i class="ri-shirt-line"></i> {{ service.dress_code }}
                                                    </div>
                                                    <div class="mt-1 small text-muted">
                                                        Rif: #{{ service.reference_number || service.id }}
                                                    </div>
                                                </div>
                                                <!-- Colonna 4: Economica -->
                                                <div v-if="!isDriver" class="col-md-3">
                                                    <h6 class="text-muted mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Economia</h6>
                                                    <div class="mb-1">
                                                        <span class="text-success fw-bold">Ricavi:</span> €{{ formatCurrency(service.service_price) }}
                                                    </div>
                                                    <div v-if="hasAnyCost(service)">
                                                        <span class="text-danger fw-bold">Costi:</span> €{{ formatCurrency(getTotalCosts(service)) }}
                                                        <div class="small text-muted">
                                                            <span v-if="parseFloat(service.driver_compensation)">Driver: €{{ formatCurrency(service.driver_compensation) }}</span>
                                                            <span v-if="parseFloat(service.colleague_cost)" class="ms-2">Collega: €{{ formatCurrency(service.colleague_cost) }}</span>
                                                            <span v-if="parseFloat(service.intermediary_commission)" class="ms-2">Interm: €{{ formatCurrency(service.intermediary_commission) }}</span>
                                                        </div>
                                                    </div>
                                                    <div v-if="service.overlaps_count > 0" class="mt-1">
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">
                                                            <i class="ri-error-warning-line"></i> {{ service.overlaps_count }} sovrapposizioni
                                                        </span>
                                                    </div>
                                                </div>
                                                <!-- Azioni nella riga espansa -->
                                                <div class="col-12 border-top pt-2 mt-1">
                                                    <div class="d-flex gap-2">
                                                        <Link v-if="!isDriver || isServiceAssignedOrAccepted(service)" :href="withReturnUrl(route('easyncc.services.show', service.id))" class="btn btn-sm btn-soft-primary">
                                                            <i class="ri-eye-line me-1"></i>Dettaglio
                                                        </Link>
                                                        <Link v-if="!isDriver" :href="withReturnUrl(route('easyncc.services.edit', service.id))" class="btn btn-sm btn-soft-info">
                                                            <i class="ri-edit-line me-1"></i>Modifica
                                                        </Link>
                                                        <button v-if="!isDriver" type="button" @click.stop="duplicateService(service.id)" class="btn btn-sm btn-soft-secondary">
                                                            <i class="ri-file-copy-line me-1"></i>Duplica
                                                        </button>
                                                        <button v-if="!isDriver" type="button" @click.stop="returnService(service.id)" class="btn btn-sm btn-soft-secondary">
                                                            <i class="ri-arrow-go-back-line me-1"></i>Ritorno
                                                        </button>
                                                        <button v-if="!isDriver" type="button" @click.stop="deleteService(service.id)" class="btn btn-sm btn-soft-danger ms-auto">
                                                            <i class="ri-delete-bin-line me-1"></i>Elimina
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginazione compatta -->
            <div v-if="services.length > 0" class="d-flex align-items-center justify-content-between mt-2 pb-2" style="font-size: 0.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <select v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm py-0" style="width: 60px; font-size: 0.7rem;">
                        <option :value="15">15</option>
                        <option :value="30">30</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-muted">{{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, totalItems) }} di {{ totalItems }}</span>
                </div>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" :disabled="currentPage <= 1" @click="goToPage(1)">
                        <i class="ri-skip-back-mini-line"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <span class="d-flex align-items-center px-2 text-muted">{{ currentPage }}/{{ totalPages }}</span>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" :disabled="currentPage >= totalPages" @click="goToPage(totalPages)">
                        <i class="ri-skip-forward-mini-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import axios from 'axios';
import moment from 'moment';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import { driverLabel } from '@/composables/useDriverLabel.js';
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';
import { useUrlFilters } from '@/composables/useUrlFilters.js';
import { useNotify } from '@/composables/useNotify.js';

const notify = useNotify();

// Layout state
const activeLayout = ref('hybrid');

// Data
const services = ref([]);
const loading = ref(false);
const currentUser = ref(null);

// Expanded rows (hybrid layout)
const expandedServices = ref([]);

// Dictionaries (lazy-loaded)
const serviceTypes = ref(null);
const drivers = ref(null);
const vehicles = ref(null);
const companies = ref(null);
const serviceStatuses = ref(null);

// Filters
const filters = ref({
    reference_name: '',
    date_from: moment().format('YYYY-MM-DD'),
    date_to: '',
    service_type_id: '',
    client_id: '',
    intermediary_id: '',
    driver_id: '',
    vehicle_id: '',
    supplier_id: '',
    status: '',
    company_id: ''
});

const activePreset = ref('da_oggi');
const specificDate = ref('');
const showAdvancedFilters = ref(false);

// Sorting
const sortField = ref('pickup_datetime');
const sortDirection = ref('asc');

// Pagination
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);
const perPage = ref(30);

// URL sync
const { readFromUrl, withReturnUrl } = useUrlFilters(filters, {
    page: currentPage,
    sortField,
    sortDirection,
});

// Computed
const isSuperAdmin = computed(() => currentUser.value?.role === 'super-admin');
const isDriver = computed(() => currentUser.value?.role === 'driver');

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '');
});

const activeFiltersCount = computed(() => {
    return Object.values(filters.value).filter(value => value !== '').length;
});

// Service type colors
const { serviceTypes: serviceTypeColors, loadServiceTypes: loadServiceTypeColors, serviceTypeBadgeStyle } = useServiceTypeColor();

const getServiceTypeAbbreviation = (serviceTypeName) => {
    if (!serviceTypeName || !serviceTypeColors.value.length) return serviceTypeName;
    const found = serviceTypeColors.value.find(st => st.name?.toLowerCase() === serviceTypeName.toLowerCase());
    return found?.abbreviation || serviceTypeName;
};

// Driver status
const acceptedStatusId = ref(null);
const triggerStatusId = ref(null);

const isServiceAssignedOrAccepted = (service) => {
    if (!isDriver.value || !currentUser.value) return true;
    const driverIds = (service.drivers || []).map(d => d.id);
    if (driverIds.includes(currentUser.value.id)) return true;
    if (triggerStatusId.value && service.status_id === triggerStatusId.value) return true;
    if (acceptedStatusId.value && service.status_id === acceptedStatusId.value) return true;
    return false;
};

// Data loading
const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
    } catch (err) {
        console.error('Error loading current user:', err);
    }
};

const loadServices = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/services', {
            params: {
                ...filters.value,
                with_counts: true,
                per_page: perPage.value,
                page: currentPage.value,
                sort_by: sortField.value,
                sort_order: sortDirection.value,
            }
        });
        services.value = response.data.data || [];
        currentPage.value = response.data.current_page;
        totalPages.value = response.data.last_page;
        totalItems.value = response.data.total;
    } catch (err) {
        console.error('Error loading services:', err);
    } finally {
        loading.value = false;
    }
};

// Dictionary loaders
const ensureServiceStatuses = async () => {
    if (serviceStatuses.value === null) {
        try { const { data } = await axios.get('/api/dictionaries/service-statuses'); serviceStatuses.value = data.data || data || []; }
        catch (e) { serviceStatuses.value = []; }
    }
};
const ensureDrivers = async () => {
    if (drivers.value === null) {
        try { const { data } = await axios.get('/api/users', { params: { role: 'driver', per_page: 200, light: 1 } }); drivers.value = data.data || []; }
        catch (e) { drivers.value = []; }
    }
};
const ensureVehicles = async () => {
    if (vehicles.value === null) {
        try { const { data } = await axios.get('/api/vehicles', { params: { per_page: 200, light: 1 } }); vehicles.value = data.data || []; }
        catch (e) { vehicles.value = []; }
    }
};
const ensureServiceTypes = async () => {
    if (serviceTypes.value === null) {
        try { const { data } = await axios.get('/api/dictionaries/service-types'); serviceTypes.value = data.data || data || []; }
        catch (e) { serviceTypes.value = []; }
    }
};
const ensureCompanies = async () => {
    if (companies.value === null && isSuperAdmin.value) {
        try { const { data } = await axios.get('/api/companies', { params: { per_page: 200 } }); companies.value = data.data || []; }
        catch (e) { companies.value = []; }
    }
};

// Search functions for Multiselect
const searchClients = async (query) => {
    const params = { type: 'client' };
    if (isSuperAdmin.value && filters.value.company_id) params.company_id = filters.value.company_id;
    if (query && query.length >= 2) params.search = query;
    else return [];
    try {
        const response = await axios.get('/api/services/filter-users', { params });
        return response.data.data || [];
    } catch (error) { return []; }
};

const searchIntermediaries = async (query) => {
    const params = { type: 'intermediary' };
    if (isSuperAdmin.value && filters.value.company_id) params.company_id = filters.value.company_id;
    if (query && query.length >= 2) params.search = query;
    else return [];
    try {
        const response = await axios.get('/api/services/filter-users', { params });
        return response.data.data || [];
    } catch (error) { return []; }
};

// Filter functions
const loadServicesFromFilter = () => {
    activePreset.value = null;
    currentPage.value = 1;
    loadServices();
};

const shiftDates = (days) => {
    const hasFrom = !!filters.value.date_from;
    const hasTo = !!filters.value.date_to;
    if (hasFrom) filters.value.date_from = moment(filters.value.date_from).add(days, 'days').format('YYYY-MM-DD');
    if (hasTo) filters.value.date_to = moment(filters.value.date_to).add(days, 'days').format('YYYY-MM-DD');
    if (!hasFrom && !hasTo) filters.value.date_from = moment().add(days, 'days').format('YYYY-MM-DD');
    loadServicesFromFilter();
};

const filterFromToday = () => {
    filters.value.date_from = moment().format('YYYY-MM-DD');
    filters.value.date_to = '';
    activePreset.value = 'da_oggi';
    currentPage.value = 1;
    loadServices();
};

const filterToday = () => {
    const today = moment().format('YYYY-MM-DD');
    filters.value.date_from = today;
    filters.value.date_to = today;
    activePreset.value = 'oggi';
    currentPage.value = 1;
    loadServices();
};

const filterTomorrow = () => {
    const tomorrow = moment().add(1, 'days').format('YYYY-MM-DD');
    filters.value.date_from = tomorrow;
    filters.value.date_to = tomorrow;
    activePreset.value = 'domani';
    currentPage.value = 1;
    loadServices();
};

const filterWeek = () => {
    filters.value.date_from = moment().startOf('isoWeek').format('YYYY-MM-DD');
    filters.value.date_to = moment().endOf('isoWeek').format('YYYY-MM-DD');
    activePreset.value = 'settimana';
    currentPage.value = 1;
    loadServices();
};

const filterSpecificDate = () => {
    if (specificDate.value) {
        filters.value.date_from = specificDate.value;
        filters.value.date_to = specificDate.value;
        activePreset.value = 'specific';
        currentPage.value = 1;
        loadServices();
    }
};

const filterAll = () => {
    filters.value = {
        reference_name: '', date_from: '', date_to: '', service_type_id: '',
        client_id: '', intermediary_id: '', driver_id: '', vehicle_id: '',
        supplier_id: '', status: '', company_id: ''
    };
    specificDate.value = '';
    activePreset.value = 'tutti';
    currentPage.value = 1;
    loadServices();
};

let debounceTimer = null;
const debouncedLoadServices = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { currentPage.value = 1; loadServices(); }, 500);
};

// Sorting
const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    currentPage.value = 1;
    loadServices();
};

const onSortChange = () => {
    currentPage.value = 1;
    loadServices();
};

const toggleSortDirection = () => {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    currentPage.value = 1;
    loadServices();
};

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        loadServices();
    }
};

const changePerPage = (newPerPage) => {
    perPage.value = newPerPage;
    currentPage.value = 1;
    loadServices();
};

// Expand/collapse (hybrid)
const toggleExpand = (serviceId) => {
    const idx = expandedServices.value.indexOf(serviceId);
    if (idx >= 0) {
        expandedServices.value.splice(idx, 1);
    } else {
        expandedServices.value.push(serviceId);
    }
};

// Service actions
const duplicateService = async (id) => {
    try {
        const { data } = await axios.post(`/api/services/${id}/duplicate`);
        window.location.href = `/easyncc/services/${data.data.id}/edit`;
    } catch (err) {
        console.error('Error duplicating service:', err);
    }
};

const returnService = async (id) => {
    try {
        const { data } = await axios.post(`/api/services/${id}/return`);
        window.location.href = `/easyncc/services/${data.data.id}/edit`;
    } catch (err) {
        console.error('Error creating return service:', err);
    }
};

const deleteService = async (id) => {
    const confirmed = await notify.confirm(
        'Conferma eliminazione',
        'Eliminando il servizio verranno rimossi anche:<ul class="text-start mt-2">'
            + '<li>Esperienze collegate</li><li>Task collegati</li><li>Movimenti contabili</li>'
            + '<li>Allegati</li><li>Passeggeri</li></ul>Vuoi procedere?',
        { confirmText: 'Elimina tutto' }
    );
    if (!confirmed) return;
    try {
        await axios.delete(`/api/services/${id}`);
        await loadServices();
    } catch (err) {
        console.error('Error deleting service:', err);
    }
};

// Formatting helpers
const formatDate = (datetime) => moment.utc(datetime).format('DD/MM/YYYY HH:mm');
const formatDateCompact = (datetime) => moment.utc(datetime).format('DD/MM HH:mm');
const formatDateShort = (datetime) => moment.utc(datetime).format('DD/MM');
const formatTimeOnly = (datetime) => datetime ? moment.utc(datetime).format('HH:mm') : '-';
const formatCurrency = (value) => value ? parseFloat(value).toFixed(2) : '0.00';

const truncate = (str, maxLen) => {
    if (!str) return '—';
    return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
};

const getTotalCosts = (service) => {
    return (parseFloat(service.driver_compensation) || 0)
        + (parseFloat(service.colleague_cost) || 0)
        + (parseFloat(service.intermediary_commission) || 0)
        + (parseFloat(service.fuel_cost) || 0)
        + (parseFloat(service.toll_cost) || 0)
        + (parseFloat(service.parking_cost) || 0)
        + (parseFloat(service.other_vehicle_costs) || 0);
};

const hasAnyCost = (service) => getTotalCosts(service) > 0;

const getNationalityFlag = (nationality) => {
    const countryFlags = {
        'Italia': '\u{1F1EE}\u{1F1F9}', 'Stati Uniti': '\u{1F1FA}\u{1F1F8}', 'Regno Unito': '\u{1F1EC}\u{1F1E7}',
        'Germania': '\u{1F1E9}\u{1F1EA}', 'Francia': '\u{1F1EB}\u{1F1F7}', 'Spagna': '\u{1F1EA}\u{1F1F8}',
        'Portogallo': '\u{1F1F5}\u{1F1F9}', 'Paesi Bassi': '\u{1F1F3}\u{1F1F1}', 'Belgio': '\u{1F1E7}\u{1F1EA}',
        'Svizzera': '\u{1F1E8}\u{1F1ED}', 'Austria': '\u{1F1E6}\u{1F1F9}', 'Giappone': '\u{1F1EF}\u{1F1F5}',
        'Cina': '\u{1F1E8}\u{1F1F3}', 'Brasile': '\u{1F1E7}\u{1F1F7}', 'Canada': '\u{1F1E8}\u{1F1E6}',
        'Australia': '\u{1F1E6}\u{1F1FA}', 'Russia': '\u{1F1F7}\u{1F1FA}', 'India': '\u{1F1EE}\u{1F1F3}',
    };
    return countryFlags[nationality] || '\u{1F30D}';
};

// Lifecycle
onMounted(async () => {
    await loadCurrentUser();
    if (isDriver.value) {
        try {
            const res = await axios.get('/api/settings/public');
            acceptedStatusId.value = res.data.telegram_accepted_status_id || null;
            triggerStatusId.value = res.data.telegram_trigger_status_id || null;
        } catch (e) { console.error('Error loading public settings:', e); }
    }
    const hadUrlParams = readFromUrl();
    if (hadUrlParams) activePreset.value = null;
    await loadServices();

    // Background loading
    ensureServiceTypes();
    loadServiceTypeColors();
    ensureDrivers();
    ensureVehicles();
    ensureServiceStatuses();
    if (isSuperAdmin.value) ensureCompanies();
});
</script>

<style scoped>
/* Toolbar sticky */
.compact-toolbar {
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* Table compatta */
.table-compact {
    font-size: 0.78rem;
}

.th-compact {
    padding: 4px 6px !important;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6c757d;
}

.td-compact {
    padding: 4px 6px !important;
    vertical-align: middle;
    white-space: nowrap;
}

.tr-compact {
    line-height: 1.3;
}

.tr-compact:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.04) !important;
}

/* Hybrid layout */
.tr-hybrid-main {
    cursor: pointer;
}

.tr-expanded {
    background-color: #f8f9fa;
}

.expanded-details {
    animation: slideDown 0.15s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; max-height: 0; }
    to { opacity: 1; max-height: 500px; }
}

/* Card layout */
.card-service {
    transition: box-shadow 0.15s;
    background: white;
}

.card-service:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Driver dot */
.driver-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Mini license plate */
.targa-mini {
    font-family: 'Arial', sans-serif;
    font-size: 0.7rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: linear-gradient(to right, #003399 0%, #003399 8%, #f0f0f0 8%, #f0f0f0 92%, #003399 92%, #003399 100%);
    border: 1px solid #999;
    border-radius: 2px;
    padding: 0px 5px;
    display: inline-block;
}

/* Action buttons */
.action-btn {
    font-size: 0.65rem !important;
    line-height: 1.2;
}

/* Multiselect compact */
.multiselect-sm {
    --ms-font-size: 0.75rem;
    --ms-line-height: 1.2;
    --ms-py: 0.15rem;
    --ms-px: 0.5rem;
    --ms-tag-font-size: 0.7rem;
    --ms-option-font-size: 0.75rem;
    min-height: 0;
    font-size: 0.75rem;
}
</style>
