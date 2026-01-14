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
                                        <th scope="col">Passeggeri</th>
                                        <th scope="col" @click="sortBy('client_id')" style="cursor: pointer;">
                                            Committente
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
                                            <div class="fw-bold">#{{ service.reference_number || service.id }}</div>
                                            <div class="text-muted small" v-if="service.service_type">{{ service.service_type }}</div>

                                            <!-- Contact Name Inline Edit -->
                                            <div class="mt-1">
                                                <!-- Display mode -->
                                                <div
                                                    v-if="editingContactName !== service.id"
                                                    class="d-flex align-items-center gap-1"
                                                >
                                                    <div class="fw-bold text-primary">
                                                        {{ service.contact_name || 'Nessun nominativo' }}
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="startEditContactName(service)"
                                                        class="btn btn-link btn-sm p-0 text-muted"
                                                        title="Modifica nominativo"
                                                        style="line-height: 1;"
                                                    >
                                                        <i class="ri-edit-line" style="font-size: 0.9rem;"></i>
                                                    </button>
                                                </div>

                                                <!-- Edit mode -->
                                                <div v-else class="d-flex gap-2 align-items-center">
                                                    <input
                                                        v-model="editingContactNameValue"
                                                        :ref="el => contactNameInputRefs[service.id] = el"
                                                        @keydown.enter="saveContactName(service)"
                                                        @keydown.escape="cancelEditContactName"
                                                        type="text"
                                                        class="form-control form-control-sm"
                                                        placeholder="Inserisci nominativo"
                                                        style="font-size: 0.85rem; min-width: 150px;"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="saveContactName(service)"
                                                        class="btn btn-sm btn-success"
                                                        title="Salva"
                                                    >
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="cancelEditContactName"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Annulla"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-1" v-if="service.status">
                                                <span class="badge" :class="getStatusBadgeClass(service.status.name)">{{ service.status.name }}</span>
                                            </div>
                                        </td>
                                        <!-- Data -->
                                        <td>
                                            <div class="fw-bold">{{ formatDate(service.pickup_datetime) }}</div>
                                            <div class="small text-primary">{{ service.pickup_address }}</div>
                                            <div class="small text-muted mt-1">{{ formatDate(service.dropoff_datetime) }}</div>
                                            <div class="small text-muted">{{ service.dropoff_address }}</div>
                                        </td>
                                        <!-- Passeggeri -->
                                        <td>
                                            <div class="fw-bold mb-1">
                                                <i class="ri-user-line"></i> {{ service.passenger_count || 0 }}
                                            </div>
                                            <!-- Bagagli -->
                                            <div class="small mb-1">
                                                <i class="ri-luggage-cart-line me-1" title="Bagaglio grande"></i>{{ service.big_luggage || 0 }}
                                                <i class="ri-briefcase-line ms-2 me-1" title="Bagaglio medio"></i>{{ service.medium_luggage || 0 }}
                                                <i class="ri-handbag-line ms-2 me-1" title="Bagaglio piccolo"></i>{{ service.small_luggage || 0 }}
                                            </div>
                                            <!-- Babyseat -->
                                            <div class="small mb-1">
                                                <i class="ri-bear-smile-line me-1" title="Ovetto"></i>{{ service.babyseat_egg || 0 }}
                                                <i class="ri-parent-line ms-2 me-1" title="Seggiolino standard"></i>{{ service.babyseat_standard || 0 }}
                                                <i class="ri-user-smile-line ms-2 me-1" title="Booster"></i>{{ service.babyseat_booster || 0 }}
                                            </div>
                                            <!-- Lista passeggeri -->
                                            <div class="small" v-if="service.passengers && service.passengers.length > 0">
                                                <div v-for="(passenger, idx) in service.passengers" :key="idx" class="text-muted mb-1" style="font-size: 0.75rem;">
                                                    <span v-if="passenger.nationality">{{ getNationalityFlag(passenger.nationality) }} </span>
                                                    <span class="text-uppercase fw-bold">{{ passenger.surname }}</span> {{ passenger.name }}
                                                    <span v-if="passenger.phone"><br><i class="ri-phone-line"></i> {{ passenger.phone }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Committente -->
                                        <td>
                                            <div class="fw-bold" v-if="service.client">
                                                {{ service.client.name }} {{ service.client.surname }}
                                            </div>
                                            <div class="small" v-if="service.client && service.client.phone">
                                                <i class="ri-phone-line"></i> {{ service.client.phone }}
                                            </div>
                                            <div class="small text-muted mt-1" v-if="service.intermediary">
                                                Int: {{ service.intermediary.name }} {{ service.intermediary.surname }}
                                            </div>
                                            <div class="small" v-if="service.intermediary && service.intermediary.phone">
                                                <i class="ri-phone-line"></i> {{ service.intermediary.phone }}
                                            </div>
                                        </td>
                                        <!-- Autista -->
                                        <td>
                                            <div v-if="service.drivers && service.drivers.length > 0">
                                                <div v-for="driver in service.drivers" :key="driver.id" class="mb-1">
                                                    <span class="badge" :style="`background-color: ${driver.driver_profile?.color || '#6c757d'}`">
                                                        {{ driver.name }} {{ driver.surname }}
                                                    </span>
                                                </div>
                                                <div class="mt-2">
                                                    <!-- Display mode -->
                                                    <div
                                                        v-if="editingDressCode !== service.id"
                                                        class="d-flex align-items-center gap-1"
                                                    >
                                                        <div style="font-size: 0.85rem;">
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
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Veicolo -->
                                        <td>
                                            <div v-if="service.vehicle">
                                                <div class="targa-auto mb-1">
                                                    <span class="codice-targa">{{ service.vehicle.license_plate }}</span>
                                                </div>
                                                <div class="small mt-2">{{ service.vehicle.brand }} {{ service.vehicle.model }}</div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                            <!-- Fornitore -->
                                            <div class="small text-muted mt-2" v-if="service.supplier">
                                                <i class="ri-building-line"></i> {{ service.supplier.name }} {{ service.supplier.surname }}
                                            </div>
                                        </td>
                                        <!-- Esperienze -->
                                        <td>
                                            <div v-if="service.activities && service.activities.length > 0">
                                                <div v-for="activity in service.activities" :key="activity.id" class="small mb-1">
                                                    <div class="fw-medium">
                                                        <i class="ri-time-line"></i> {{ formatTime(activity.start_time) }}
                                                    </div>
                                                    <div>{{ activity.activity_type?.name || '-' }}</div>
                                                    <div class="text-muted">{{ activity.supplier?.name || '-' }}</div>
                                                </div>
                                            </div>
                                            <div v-else class="text-muted">-</div>
                                        </td>
                                        <!-- Economics -->
                                        <td>
                                            <div class="small">
                                                <div class="mb-2">
                                                    <span class="badge bg-success fs-6 px-3 py-2">€{{ formatCurrency(service.service_price) }}</span>
                                                </div>
                                                <div><i class="ri-wallet-3-line me-1" title="Acconto"></i>€{{ formatCurrency(service.deposit_amount) }}</div>
                                                <div><i class="ri-money-euro-circle-line me-1" title="Saldo imponibile"></i>€{{ formatCurrency(service.balance_taxable) }}</div>
                                                <div><i class="ri-percent-line me-1" title="Diritti di agenzia"></i>€{{ formatCurrency(service.balance_handling_fees) }}</div>
                                                <div><i class="ri-bank-card-line me-1" title="Commissioni carta"></i>€{{ formatCurrency(service.balance_card_fees) }}</div>
                                            </div>
                                        </td>
                                        <!-- Notifiche -->
                                        <td>
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

                        <!-- No Data -->
                        <div v-else class="text-center text-muted py-5">
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

// Inline editing
const editingDressCode = ref(null);
const editingDressCodeValue = ref(null);
const dressCodeInputRefs = ref({});

const editingContactName = ref(null);
const editingContactNameValue = ref(null);
const contactNameInputRefs = ref({});

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
const selectedServiceForPopup = ref(null);

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
