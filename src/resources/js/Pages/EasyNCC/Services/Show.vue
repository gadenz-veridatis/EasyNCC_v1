<template>
    <Head title="Dettaglio Servizio" />

    <Layout>
        <PageHeader title="Dettaglio Servizio" pageTitle="Servizi" />

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
        </div>

        <!-- Service Details -->
        <template v-else-if="service">
            <!-- Info Bar -->
            <div v-if="service.pickup_datetime" class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center flex-wrap gap-2">
                <span class="fw-bold text-primary" style="font-size: 0.9rem;">
                    <i class="ri-calendar-event-line me-1"></i>{{ formatDateTime(service.pickup_datetime) }}
                </span>
                <span v-if="service.pickup_location" class="text-dark">
                    <i class="ri-map-pin-line me-1"></i>{{ service.pickup_location }}
                </span>
                <span v-else-if="service.pickup_address" class="text-muted" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <i class="ri-map-pin-line me-1"></i>{{ service.pickup_address }}
                </span>
                <span v-if="service.reference_number" class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size: 0.75rem;">
                    {{ service.reference_number }}
                </span>
            </div>

            <BRow>
                <BCol lg="12">
                    <BCard no-body class="service-show-card">
                        <BCardHeader>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="card-title mb-0">Servizio #{{ service.reference_number || service.id }}</h5>
                                    <span class="badge" :style="statusBadgeStyle">
                                        {{ service.status?.name || 'N/A' }}
                                    </span>
                                </div>

                                <!-- Quick Navigation -->
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span class="text-muted small me-1" title="Navigazione Rapida">
                                        <i class="ri-navigation-line"></i>
                                    </span>
                                    <button type="button" @click="scrollToSection('section-intestazione')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Intestazione">
                                        <i class="ri-file-list-3-line"></i>
                                    </button>
                                    <button v-if="service.passengers?.length" type="button" @click="scrollToSection('section-passeggeri')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Passeggeri">
                                        <i class="ri-user-3-line"></i>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-equipaggio')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Equipaggio">
                                        <i class="ri-car-line"></i>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-piano')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Piano Servizio">
                                        <i class="ri-map-pin-line"></i>
                                    </button>
                                    <button v-if="service.notes" type="button" @click="scrollToSection('section-note')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Note">
                                        <i class="ri-file-text-line"></i>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-prezzi')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Economics">
                                        <i class="ri-money-euro-box-line"></i>
                                    </button>
                                    <button v-if="service.accounting_transactions?.length" type="button" @click="scrollToSection('section-contabilita')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Contabilità">
                                        <i class="ri-calculator-line"></i>
                                    </button>
                                    <button v-if="service.tasks?.length" type="button" @click="scrollToSection('section-tasks')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Tasks">
                                        <i class="ri-task-line"></i>
                                    </button>

                                    <!-- Action buttons -->
                                    <span class="border-start ms-2 ps-2 d-flex gap-1">
                                        <Link :href="route('easyncc.services.edit', service.id)" class="btn btn-sm btn-primary" title="Modifica">
                                            <i class="bx bx-edit me-1"></i><span class="d-none d-md-inline">Modifica</span>
                                        </Link>
                                        <Link :href="returnUrl || route('easyncc.services.index')" class="btn btn-sm btn-secondary" title="Torna alla lista">
                                            <i class="bx bx-arrow-back"></i>
                                        </Link>
                                    </span>
                                </div>
                            </div>
                        </BCardHeader>

                        <BCardBody>
                            <!-- ============================================================ -->
                            <!-- INTESTAZIONE: ID + Committente + Intermediario (merged)      -->
                            <!-- ============================================================ -->
                            <fieldset id="section-intestazione" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-file-list-3-line me-2"></i>Intestazione
                                </legend>
                                <!-- Row 1: Riferimenti -->
                                <div class="d-flex flex-wrap gap-4 mb-2">
                                    <div>
                                        <small class="text-muted">Rif. Servizio</small>
                                        <div class="fw-medium">{{ service.reference_number || '-' }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Rif. Esterno</small>
                                        <div class="fw-medium">{{ service.external_reference || '-' }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Tipo Servizio</small>
                                        <div>
                                            <span v-if="service.service_type" class="badge" :style="{ ...serviceTypeBadgeStyle(service.service_type), fontSize: '0.8rem' }">
                                                {{ service.service_type }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </div>
                                    </div>
                                    <div>
                                        <small class="text-muted">N. Passeggeri</small>
                                        <div class="fw-medium">{{ service.passenger_count }}</div>
                                    </div>
                                </div>
                                <!-- Row 2: Committente + Intermediario inline -->
                                <div class="d-flex flex-wrap gap-4 pt-2 border-top" v-if="service.client || service.intermediary">
                                    <!-- Committente -->
                                    <div v-if="service.client" class="d-flex flex-wrap gap-3 align-items-center">
                                        <div>
                                            <small class="text-muted">Committente</small>
                                            <div class="fw-medium">
                                                {{ service.client.name }} {{ service.client.surname }}
                                                <span v-if="service.client.client_profile?.business_name" class="text-muted">
                                                    ({{ service.client.client_profile.business_name }})
                                                </span>
                                            </div>
                                        </div>
                                        <small v-if="service.client.email" class="text-muted">
                                            <i class="ri-mail-line me-1"></i>{{ service.client.email }}
                                        </small>
                                        <small v-if="service.client.phone" class="text-muted">
                                            <i class="ri-phone-line me-1"></i>{{ service.client.phone }}
                                        </small>
                                    </div>
                                    <!-- Separator -->
                                    <div v-if="service.client && service.intermediary" class="border-start"></div>
                                    <!-- Intermediario -->
                                    <div v-if="service.intermediary" class="d-flex flex-wrap gap-3 align-items-center">
                                        <div>
                                            <small class="text-muted">Intermediario</small>
                                            <div class="fw-medium">
                                                {{ service.intermediary.name }} {{ service.intermediary.surname }}
                                                <span v-if="service.intermediary.client_profile?.business_name" class="text-muted">
                                                    ({{ service.intermediary.client_profile.business_name }})
                                                </span>
                                            </div>
                                        </div>
                                        <small v-if="service.intermediary.email" class="text-muted">
                                            <i class="ri-mail-line me-1"></i>{{ service.intermediary.email }}
                                        </small>
                                        <small v-if="service.intermediary.phone" class="text-muted">
                                            <i class="ri-phone-line me-1"></i>{{ service.intermediary.phone }}
                                        </small>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- PASSEGGERI                                                   -->
                            <!-- ============================================================ -->
                            <fieldset v-if="service.passengers?.length" id="section-passeggeri" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-user-3-line me-2"></i>Passeggeri
                                </legend>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <thead>
                                            <tr class="text-muted small">
                                                <th>Cognome</th>
                                                <th>Nome</th>
                                                <th>Telefono</th>
                                                <th>Email</th>
                                                <th>Nazionalità</th>
                                                <th>Provenienza</th>
                                                <th>Rif. Vettore</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="p in service.passengers" :key="p.id">
                                                <td class="fw-medium">{{ p.surname || '-' }}</td>
                                                <td>{{ p.name || '-' }}</td>
                                                <td>{{ p.phone || '-' }}</td>
                                                <td>{{ p.email || '-' }}</td>
                                                <td>{{ p.nationality || '-' }}</td>
                                                <td>{{ p.origin || '-' }}</td>
                                                <td>{{ p.carrier_reference || '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- EQUIPAGGIO: Veicolo + Driver + Bagagli (merged)              -->
                            <!-- ============================================================ -->
                            <fieldset id="section-equipaggio" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-car-line me-2"></i>Equipaggio
                                </legend>
                                <!-- Row 1: Veicolo -->
                                <div class="d-flex flex-wrap gap-4 align-items-center mb-2">
                                    <div v-if="service.supplier">
                                        <small class="text-muted">Fornitore</small>
                                        <div class="fw-medium">{{ service.supplier.name }} {{ service.supplier.surname }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Veicolo</small>
                                        <div class="fw-medium">
                                            <template v-if="service.vehicle">
                                                {{ service.vehicle.brand }} {{ service.vehicle.model }}
                                                <span class="text-muted">({{ service.vehicle.license_plate }})</span>
                                            </template>
                                            <span v-else>-</span>
                                        </div>
                                    </div>
                                    <div v-if="service.vehicle_type">
                                        <small class="text-muted">Tipo</small>
                                        <div>{{ service.vehicle_type }}</div>
                                    </div>
                                    <span v-if="service.vehicle_not_replaceable" class="badge bg-warning-subtle text-warning">
                                        <i class="ri-lock-line me-1"></i>Veicolo non sost.
                                    </span>
                                </div>
                                <!-- Row 2: Driver -->
                                <div class="d-flex flex-wrap gap-3 align-items-center pt-2 border-top mb-2">
                                    <small class="text-muted">Driver:</small>
                                    <template v-if="service.drivers?.length">
                                        <span
                                            v-for="driver in service.drivers"
                                            :key="driver.id"
                                            class="badge px-2 py-1"
                                            :style="{ backgroundColor: driver.driver_profile?.color || '#6c757d', color: '#fff', fontSize: '0.85rem' }"
                                        >
                                            {{ driverLabel(driver) }}
                                        </span>
                                    </template>
                                    <span v-else class="text-muted">Nessuno assegnato</span>
                                    <span v-if="service.driver_not_replaceable" class="badge bg-warning-subtle text-warning">
                                        <i class="ri-lock-line me-1"></i>Non sost.
                                    </span>
                                    <template v-if="service.external_driver_name">
                                        <span class="border-start ps-3">
                                            <small class="text-muted">Esterno:</small>
                                            {{ service.external_driver_name }}
                                            <small v-if="service.external_driver_phone" class="text-muted ms-1">{{ service.external_driver_phone }}</small>
                                        </span>
                                    </template>
                                    <template v-if="service.dress_code">
                                        <span class="border-start ps-3">
                                            <small class="text-muted">Dress:</small> {{ service.dress_code.name }}
                                        </span>
                                    </template>
                                </div>
                                <!-- Row 3: Bagagli (only if any > 0) -->
                                <div v-if="hasLuggage" class="d-flex flex-wrap gap-3 align-items-center pt-2 border-top">
                                    <small class="text-muted">Bagagli:</small>
                                    <span v-if="service.large_luggage" class="badge bg-light text-dark border">{{ service.large_luggage }}L</span>
                                    <span v-if="service.medium_luggage" class="badge bg-light text-dark border">{{ service.medium_luggage }}M</span>
                                    <span v-if="service.small_luggage" class="badge bg-light text-dark border">{{ service.small_luggage }}S</span>
                                    <template v-if="service.baby_seat_infant || service.baby_seat_standard || service.baby_seat_booster">
                                        <span class="border-start ps-3"><small class="text-muted">Seggiolini:</small></span>
                                        <span v-if="service.baby_seat_infant" class="badge bg-light text-dark border">{{ service.baby_seat_infant }} ovetto</span>
                                        <span v-if="service.baby_seat_standard" class="badge bg-light text-dark border">{{ service.baby_seat_standard }} standard</span>
                                        <span v-if="service.baby_seat_booster" class="badge bg-light text-dark border">{{ service.baby_seat_booster }} booster</span>
                                    </template>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- PIANO DI SERVIZIO: timeline compatta                        -->
                            <!-- ============================================================ -->
                            <fieldset id="section-piano" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-map-pin-line me-2"></i>Piano di Servizio
                                </legend>

                                <!-- Pickup row -->
                                <div class="d-flex flex-wrap gap-3 align-items-start p-2 rounded mb-2" style="background-color: rgba(10,179,156,0.08); border-left: 4px solid #0ab39c;">
                                    <div class="text-nowrap">
                                        <span class="fw-bold text-success">
                                            <i class="ri-map-pin-user-line me-1"></i>{{ formatDateTime(service.pickup_datetime) }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold text-success">{{ service.pickup_location || '-' }}</span>
                                        <small v-if="service.pickup_address" class="text-muted d-block">{{ service.pickup_address }}</small>
                                    </div>
                                    <div class="text-nowrap text-end">
                                        <small class="text-muted">Uscita mezzo:</small>
                                        <span class="ms-1">{{ formatDateTime(service.vehicle_departure_datetime) }}</span>
                                    </div>
                                </div>

                                <!-- Dropoff row -->
                                <div class="d-flex flex-wrap gap-3 align-items-start p-2 rounded mb-2" style="background-color: rgba(240,101,72,0.08); border-left: 4px solid #f06548;">
                                    <div class="text-nowrap">
                                        <span class="fw-bold text-danger">
                                            <i class="ri-map-pin-range-line me-1"></i>{{ formatDateTime(service.dropoff_datetime) }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-bold text-danger">{{ service.dropoff_location || '-' }}</span>
                                        <small v-if="service.dropoff_address" class="text-muted d-block">{{ service.dropoff_address }}</small>
                                    </div>
                                    <div class="text-nowrap text-end">
                                        <small class="text-muted">Rientro mezzo:</small>
                                        <span class="ms-1">{{ formatDateTime(service.vehicle_return_datetime) }}</span>
                                    </div>
                                </div>

                                <!-- Soste / Attività -->
                                <div v-if="service.activities?.length" class="p-2 rounded" style="background-color: rgba(41,156,219,0.08); border-left: 4px solid #299cdb;">
                                    <div class="d-flex align-items-center mb-1">
                                        <small class="fw-semibold text-info"><i class="ri-calendar-check-line me-1"></i>Soste / Attività</small>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr class="text-muted small">
                                                    <th>Orario</th>
                                                    <th>Descrizione</th>
                                                    <th>Tipo</th>
                                                    <th>Fornitore</th>
                                                    <th class="text-end">Costo</th>
                                                    <th class="text-end">Per pers.</th>
                                                    <th>Pag.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="a in service.activities" :key="a.id">
                                                    <td class="text-nowrap">{{ formatTime(a.start_time) }}-{{ formatTime(a.end_time) }}</td>
                                                    <td class="fw-medium">{{ a.name || '-' }}</td>
                                                    <td>{{ a.activity_type?.name || '-' }}</td>
                                                    <td>{{ a.supplier ? `${a.supplier.name} ${a.supplier.surname}` : '-' }}</td>
                                                    <td class="text-end">{{ formatCurrency(a.cost) }}</td>
                                                    <td class="text-end">{{ formatCurrency(a.cost_per_person) }}</td>
                                                    <td>
                                                        <span v-if="a.payment_type" class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">{{ a.payment_type }}</span>
                                                        <span v-else>-</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Legacy stops -->
                                <div v-if="service.stops?.length" class="p-2 rounded mt-2" style="background-color: rgba(41,156,219,0.08); border-left: 4px solid #299cdb;">
                                    <div class="d-flex align-items-center mb-1">
                                        <small class="fw-semibold text-info"><i class="ri-route-line me-1"></i>Fermate Intermedie</small>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr class="text-muted small">
                                                    <th>Nome</th><th>Indirizzo</th><th>Inizio</th><th>Fine</th><th class="text-end">Costo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="stop in service.stops" :key="stop.id">
                                                    <td class="fw-medium">{{ stop.name }}</td>
                                                    <td>{{ stop.address }}</td>
                                                    <td>{{ formatDateTime(stop.start_time) }}</td>
                                                    <td>{{ formatDateTime(stop.end_time) }}</td>
                                                    <td class="text-end">{{ formatCurrency(stop.total_cost) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- NOTE                                                        -->
                            <!-- ============================================================ -->
                            <fieldset v-if="service.notes" id="section-note" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-file-text-line me-2"></i>Note
                                </legend>
                                <div style="white-space: pre-wrap;">{{ service.notes }}</div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- ECONOMICS: layout piatto a 2 colonne                        -->
                            <!-- ============================================================ -->
                            <fieldset id="section-prezzi" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-money-euro-box-line me-2"></i>Economics
                                </legend>

                                <BRow>
                                    <!-- Left column: COSTI -->
                                    <BCol md="5" class="mb-3 mb-md-0">
                                        <div class="bg-light rounded p-3 h-100">
                                            <h6 class="text-secondary mb-2"><i class="ri-money-euro-circle-line me-1"></i>Costi</h6>
                                            <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-muted py-1">Commissione</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.intermediary_commission) }}</td>
                                                    </tr>
                                                    <tr class="border-top">
                                                        <td colspan="2" class="text-muted py-1 small fw-semibold"><i class="ri-car-line me-1"></i>Veicolo</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Carburante</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.fuel_cost) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Pedaggio</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.toll_cost) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Parcheggio</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.parking_cost) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Altri costi</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.other_vehicle_costs) }}</td>
                                                    </tr>
                                                    <tr class="border-top">
                                                        <td colspan="2" class="text-muted py-1 small fw-semibold"><i class="ri-user-line me-1"></i>Driver</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Costo Driver</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.driver_compensation) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted py-1 ps-3">Costo Fornitore</td>
                                                        <td class="text-end fw-medium py-1">{{ formatCurrency(service.colleague_cost) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </BCol>

                                    <!-- Right column: VENDITA -->
                                    <BCol md="7">
                                        <div class="bg-light rounded p-3 h-100">
                                            <h6 class="text-secondary mb-2"><i class="ri-price-tag-3-line me-1"></i>Vendita</h6>
                                            <!-- Prezzo principale -->
                                            <div class="d-flex flex-wrap gap-4 align-items-baseline mb-2">
                                                <div>
                                                    <small class="text-muted">Prezzo Imponibile</small>
                                                    <div class="fw-bold text-primary fs-4">{{ formatCurrency(service.service_price) }}</div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">IVA</small>
                                                    <div>{{ service.vat_rate || 0 }}%</div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Card Fees</small>
                                                    <div>{{ service.card_fees_percentage || 0 }}%</div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Acconto</small>
                                                    <div>{{ service.deposit_percentage || 0 }}%</div>
                                                </div>
                                            </div>
                                            <!-- Acconto / Saldo grid -->
                                            <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                                                <thead>
                                                    <tr class="text-muted small">
                                                        <th></th>
                                                        <th class="text-end">Imponibile</th>
                                                        <th class="text-end">Handling Fees</th>
                                                        <th class="text-end">Card Fees</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-muted fw-semibold py-1">Acconto</td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.deposit_sale_type === 'deposit_taxable' }">
                                                            {{ formatCurrency(service.deposit_taxable) }}
                                                        </td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.deposit_sale_type === 'deposit_handling_fees' }">
                                                            {{ formatCurrency(service.deposit_handling_fees) }}
                                                        </td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.deposit_sale_type === 'deposit_card_fees' }">
                                                            {{ formatCurrency(service.deposit_amount) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted fw-semibold py-1">Saldo</td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.balance_sale_type === 'balance_taxable' }">
                                                            {{ formatCurrency(service.balance_taxable) }}
                                                        </td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.balance_sale_type === 'balance_handling_fees' }">
                                                            {{ formatCurrency(service.balance_handling_fees) }}
                                                        </td>
                                                        <td class="text-end py-1" :class="{ 'text-primary fw-bold': service.balance_sale_type === 'balance_card_fees' }">
                                                            {{ formatCurrency(service.balance_card_fees) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!-- Gestione inline -->
                                            <div class="d-flex flex-wrap gap-4 align-items-center pt-2 mt-2 border-top">
                                                <div>
                                                    <small class="text-muted">Stato:</small>
                                                    <span class="badge ms-1" :style="statusBadgeStyle">{{ service.status?.name || 'N/A' }}</span>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Contabilità:</small>
                                                    <i class="ms-1" :class="hasAccountingTransactions ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-muted'"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Driver incassa:</small>
                                                    <i class="ms-1" :class="service.driver_must_collect ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-muted'"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- CONTABILITÀ: badge inline + tabella                          -->
                            <!-- ============================================================ -->
                            <fieldset v-if="service.accounting_transactions?.length" id="section-contabilita" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-calculator-line me-2"></i>Contabilità
                                </legend>

                                <!-- Riepilogo inline badges -->
                                <div class="d-flex flex-wrap gap-3 align-items-center mb-3 p-2 bg-light rounded">
                                    <span class="badge bg-success-subtle text-success px-3 py-2" style="font-size: 0.85rem;">
                                        <i class="ri-money-euro-circle-line me-1"></i>Vendite € {{ formatAmount(accountingSummary.sales) }}
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger px-3 py-2" style="font-size: 0.85rem;">
                                        <i class="ri-shopping-cart-line me-1"></i>Acquisti € {{ formatAmount(accountingSummary.purchases) }}
                                    </span>
                                    <span class="badge bg-warning-subtle text-warning px-3 py-2" style="font-size: 0.85rem;">
                                        <i class="ri-team-line me-1"></i>Interm. € {{ formatAmount(accountingSummary.intermediations) }}
                                    </span>
                                    <span v-if="accountingSummary.supplierRefunds" class="badge bg-info-subtle text-info px-3 py-2" style="font-size: 0.85rem;">
                                        <i class="ri-refund-line me-1"></i>Resi € {{ formatAmount(accountingSummary.supplierRefunds) }}
                                    </span>
                                    <span v-if="accountingSummary.customerRefunds" class="badge bg-secondary-subtle text-secondary px-3 py-2" style="font-size: 0.85rem;">
                                        <i class="ri-hand-coin-line me-1"></i>Rimb. € {{ formatAmount(accountingSummary.customerRefunds) }}
                                    </span>
                                    <span class="badge px-3 py-2 fw-bold" style="font-size: 0.9rem;"
                                          :class="accountingSummary.total >= 0 ? 'bg-success text-white' : 'bg-danger text-white'">
                                        = € {{ formatAmount(accountingSummary.total) }}
                                    </span>
                                </div>

                                <!-- Movimenti Contabili -->
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover table-nowrap align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data</th>
                                                <th>Tipo</th>
                                                <th>Importo</th>
                                                <th>Rata</th>
                                                <th style="max-width: 200px;">Causali</th>
                                                <th style="max-width: 180px;">Controparte</th>
                                                <th>Documenti</th>
                                                <th>Stato</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="t in sortedAccountingTransactions" :key="t.id">
                                                <td>{{ formatDate(t.transaction_date) }}</td>
                                                <td>
                                                    <span :class="getTransactionTypeBadge(t.transaction_type)" style="font-size: 0.7rem;">
                                                        {{ getTransactionTypeAbbr(t.transaction_type) }}
                                                    </span>
                                                    <span v-if="t.is_automatic" class="badge bg-info-subtle text-info ms-1" style="font-size: 0.65rem;" title="Automatico">A</span>
                                                    <span v-else class="badge bg-warning-subtle text-warning ms-1" style="font-size: 0.65rem;" title="Manuale">M</span>
                                                </td>
                                                <td class="fw-medium">€ {{ parseFloat(t.amount).toFixed(2) }}</td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">
                                                        {{ getInstallmentAbbr(t.installment) }}
                                                    </span>
                                                </td>
                                                <td style="max-width: 200px; word-wrap: break-word; white-space: normal;">
                                                    <span v-if="t.payment_reason || t.accounting_entry">
                                                        <span v-if="t.payment_reason">{{ t.payment_reason }}</span>
                                                        <br v-if="t.payment_reason && t.accounting_entry">
                                                        <small v-if="t.accounting_entry" class="text-muted">
                                                            {{ t.accounting_entry.abbreviation || t.accounting_entry.name }}
                                                        </small>
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td style="max-width: 180px; word-wrap: break-word; white-space: normal;">
                                                    <span v-if="t.counterpart">
                                                        {{ t.counterpart.name }} {{ t.counterpart.surname }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <div v-if="t.document_number || t.document_due_date">
                                                        <span v-if="t.document_number">{{ t.document_number }}</span>
                                                        <small v-if="t.document_due_date" :class="getDueDateClass(t)" class="ms-1">
                                                            {{ formatDate(t.document_due_date) }}
                                                        </small>
                                                    </div>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <span class="badge" :style="getStatusBadgeStyle(t.status)" style="font-size: 0.7rem;">
                                                        {{ getStatusLabel(t.status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- TASKS                                                       -->
                            <!-- ============================================================ -->
                            <fieldset v-if="service.tasks?.length" id="section-tasks" class="border rounded p-3 mb-3">
                                <legend class="fs-5 fw-semibold text-primary mb-2">
                                    <i class="ri-task-line me-2"></i>Task
                                </legend>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover table-nowrap align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="max-width: 300px;">Nome</th>
                                                <th>Scadenza</th>
                                                <th>Assegnatario</th>
                                                <th>Stato</th>
                                                <th style="max-width: 250px;">Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="task in sortedTasks" :key="task.id">
                                                <td class="fw-medium" style="max-width: 300px; word-wrap: break-word; white-space: normal;">{{ task.name }}</td>
                                                <td>
                                                    <span v-if="task.due_date" :class="getTaskDueDateClass(task)">{{ formatDate(task.due_date) }}</span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <template v-if="task.assigned_users?.length">
                                                        <span v-for="(user, i) in task.assigned_users" :key="user.id">
                                                            {{ user.name }} {{ user.surname }}<span v-if="i < task.assigned_users.length - 1">, </span>
                                                        </span>
                                                    </template>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <span :class="getTaskStatusBadgeClass(task.status)" style="font-size: 0.7rem;">
                                                        {{ getTaskStatusLabel(task.status) }}
                                                    </span>
                                                </td>
                                                <td style="max-width: 250px; word-wrap: break-word; white-space: normal;">
                                                    <small class="text-muted">{{ task.notes || '-' }}</small>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>

                            <!-- ============================================================ -->
                            <!-- AUDIT                                                       -->
                            <!-- ============================================================ -->
                            <div v-if="service.creator || service.updater" class="d-flex flex-wrap gap-4 pt-2 border-top text-muted small">
                                <div v-if="service.creator">
                                    <i class="ri-user-add-line me-1"></i>Creato da {{ service.creator.name }} {{ service.creator.surname }}
                                    <span class="ms-1">{{ formatDateTime(service.created_at) }}</span>
                                </div>
                                <div v-if="service.updater">
                                    <i class="ri-edit-line me-1"></i>Aggiornato da {{ service.updater.name }} {{ service.updater.surname }}
                                    <span class="ms-1">{{ formatDateTime(service.updated_at) }}</span>
                                </div>
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
import { driverLabel } from '@/composables/useDriverLabel.js';
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';

const props = defineProps({
    serviceId: {
        type: [String, Number],
        required: true
    }
});

const service = ref(null);
const loading = ref(false);
const error = ref('');
const transactionStatuses = ref([]);
const { loadServiceTypes, serviceTypeBadgeStyle } = useServiceTypeColor();
const returnUrl = new URLSearchParams(window.location.search).get('returnUrl') || '';

// --- Data loading ---

const loadService = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get(`/api/services/${props.serviceId}`);
        service.value = response.data;
    } catch (err) {
        error.value = 'Errore nel caricamento del servizio';
        console.error('Error loading service:', err);
    } finally {
        loading.value = false;
    }
};

const loadFormData = async () => {
    try {
        const response = await axios.get('/api/services/form-data');
        const data = response.data.data;
        transactionStatuses.value = data.transaction_statuses || [];
    } catch (err) {
        console.error('Error loading form data:', err);
    }
};

// --- Computed ---

const statusBadgeStyle = computed(() => {
    const color = service.value?.status?.color_code || '#6c757d';
    return { backgroundColor: color, color: '#fff' };
});

const hasLuggage = computed(() => {
    const s = service.value;
    if (!s) return false;
    return (s.large_luggage || 0) + (s.medium_luggage || 0) + (s.small_luggage || 0)
         + (s.baby_seat_infant || 0) + (s.baby_seat_standard || 0) + (s.baby_seat_booster || 0) > 0;
});

const hasAccountingTransactions = computed(() => {
    const txns = service.value?.accounting_transactions;
    if (!txns || !txns.length) return false;
    return txns.some(t =>
        t.transaction_type === 'sale' || t.transaction_type === 'intermediation'
    );
});

const accountingSummary = computed(() => {
    const txns = service.value?.accounting_transactions;
    if (!txns || !txns.length) {
        return { total: 0, sales: 0, purchases: 0, intermediations: 0, supplierRefunds: 0, customerRefunds: 0 };
    }

    let sales = 0, purchases = 0, intermediations = 0, supplierRefunds = 0, customerRefunds = 0;

    txns.forEach(t => {
        if (t.status === 'cancelled') return;
        const amount = parseFloat(t.amount);
        if (t.transaction_type === 'sale') {
            if (t.installment === 'customer_refund') {
                customerRefunds += amount;
            } else {
                sales += amount;
            }
        } else if (t.transaction_type === 'purchase') {
            if (t.installment === 'supplier_refund') {
                supplierRefunds += amount;
            } else {
                purchases += amount;
            }
        } else if (t.transaction_type === 'intermediation') {
            intermediations += amount;
        }
    });

    const total = sales + supplierRefunds - purchases - intermediations - customerRefunds;
    return { total, sales, purchases, intermediations, supplierRefunds, customerRefunds };
});

const sortedAccountingTransactions = computed(() => {
    const txns = service.value?.accounting_transactions;
    if (!txns || !txns.length) return [];
    const installmentOrder = { deposit: 0, extra: 1, balance: 2, supplier_refund: 3, customer_refund: 4 };
    const typeOrder = { sale: 0, intermediation: 1, purchase: 2 };
    return [...txns].sort((a, b) => {
        const instA = installmentOrder[a.installment] ?? 9;
        const instB = installmentOrder[b.installment] ?? 9;
        if (instA !== instB) return instA - instB;
        const typeA = typeOrder[a.transaction_type] ?? 9;
        const typeB = typeOrder[b.transaction_type] ?? 9;
        return typeA - typeB;
    });
});

const sortedTasks = computed(() => {
    const tasks = service.value?.tasks;
    if (!tasks || !tasks.length) return [];
    return [...tasks].sort((a, b) => {
        if (!a.due_date && !b.due_date) return 0;
        if (!a.due_date) return 1;
        if (!b.due_date) return -1;
        return new Date(a.due_date) - new Date(b.due_date);
    });
});

// --- Formatters ---

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return moment.utc(datetime).format('DD/MM/YYYY HH:mm');
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment.utc(date).format('DD/MM/YYYY');
};

const formatTime = (time) => {
    if (!time) return '-';
    return moment.utc(time).format('HH:mm');
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '-';
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('it-IT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Math.abs(amount || 0));
};

// --- Transaction helpers ---

const bootstrapColorMap = {
    primary: '#405189', secondary: '#6c757d', success: '#0ab39c',
    danger: '#f06548', warning: '#f7b84b', info: '#299cdb',
};

const getTransactionTypeBadge = (type) => {
    const classes = {
        purchase: 'badge bg-danger-subtle text-danger',
        sale: 'badge bg-success-subtle text-success',
        intermediation: 'badge bg-warning-subtle text-warning'
    };
    return classes[type] || 'badge bg-secondary-subtle text-secondary';
};

const getTransactionTypeAbbr = (type) => {
    const abbrs = { purchase: 'ACQ', sale: 'VEN', intermediation: 'INT' };
    return abbrs[type] || type;
};

const getInstallmentAbbr = (installment) => {
    const abbrs = { deposit: 'ACC', extra: 'EXT', balance: 'SAL', supplier_refund: 'RES', customer_refund: 'RIM' };
    return abbrs[installment] || installment;
};

const getStatusLabel = (status) => {
    const found = transactionStatuses.value.find(s => s.code === status);
    return found ? found.name : status;
};

const getStatusBadgeStyle = (status) => {
    const fallback = { backgroundColor: '#6c757d20', color: '#6c757d', fontWeight: '500' };
    const found = transactionStatuses.value.find(s => s.code === status);
    if (!found || !found.color) return fallback;
    const hex = found.color.startsWith('#') ? found.color : (bootstrapColorMap[found.color] || '#6c757d');
    return { backgroundColor: hex + '20', color: hex, fontWeight: '500' };
};

const isStatusFinal = (statusCode) => {
    const found = transactionStatuses.value.find(s => s.code === statusCode);
    return found ? found.is_final : false;
};

const getDueDateClass = (transaction) => {
    if (!transaction.document_due_date) return 'text-muted';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(transaction.document_due_date);
    dueDate.setHours(0, 0, 0, 0);
    if (isStatusFinal(transaction.status)) return 'text-success';
    if (dueDate < today) return 'text-danger fw-bold';
    const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 7) return 'text-warning';
    return '';
};

// --- Task helpers ---

const getTaskDueDateClass = (task) => {
    if (!task.due_date) return '';
    if (task.status === 'completed' || task.status === 'cancelled') return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(task.due_date);
    dueDate.setHours(0, 0, 0, 0);
    if (dueDate < today) return 'text-danger fw-bold';
    const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 3) return 'text-warning fw-bold';
    return '';
};

const getTaskStatusBadgeClass = (status) => {
    const classes = {
        'to_complete': 'badge bg-warning-subtle text-warning',
        'completed': 'badge bg-success-subtle text-success',
        'cancelled': 'badge bg-secondary-subtle text-secondary'
    };
    return classes[status] || 'badge bg-secondary';
};

const getTaskStatusLabel = (status) => {
    const labels = { 'to_complete': 'Da Completare', 'completed': 'Completato', 'cancelled': 'Annullato' };
    return labels[status] || status;
};

// --- Navigation ---

const scrollToSection = (sectionId) => {
    const el = document.getElementById(sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// --- Init ---

onMounted(() => {
    loadService();
    loadServiceTypes();
    loadFormData();
});
</script>
