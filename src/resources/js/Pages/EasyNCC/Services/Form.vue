<template>
    <Head :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" pageTitle="Servizi" />

        <BRow>
            <BCol lg="12">
                <form @submit.prevent="submitForm">
                    <BCard no-body>
                        <BCardHeader>
                            <h5 class="card-title mb-0">{{ isEdit ? 'Modifica Servizio' : 'Nuovo Servizio' }}</h5>
                        </BCardHeader>
                        <BCardBody>
                            <!-- Loading State -->
                            <div v-if="loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                            </div>

                            <div v-else>
                                <!-- Company Selection (only for super-admin) -->
                                <div v-if="isSuperAdmin" class="alert alert-info mb-4">
                                    <BRow>
                                        <BCol md="12">
                                            <label for="company_id" class="form-label fw-bold">Azienda *</label>
                                            <select
                                                id="company_id"
                                                v-model="form.company_id"
                                                class="form-select"
                                                required
                                                @change="onCompanyChange"
                                            >
                                                <option value="">Seleziona un'azienda</option>
                                                <option v-for="company in companies" :key="company.id" :value="company.id">
                                                    {{ company.name }}
                                                </option>
                                            </select>
                                        </BCol>
                                    </BRow>
                                </div>

                                <!-- FIELDSET 1: DATI IDENTIFICATIVI -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-list-3-line me-2"></i>Dati Identificativi
                                    </legend>
                                    <BRow>
                                        <BCol md="4" class="mb-3">
                                            <label for="reference_number" class="form-label">Identificativo Servizio *</label>
                                            <input
                                                id="reference_number"
                                                v-model="form.reference_number"
                                                type="text"
                                                class="form-control"
                                                required
                                                placeholder="Es. SRV001"
                                            />
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label for="service_type" class="form-label">Tipologia Servizio *</label>
                                            <select
                                                id="service_type"
                                                v-model="form.service_type"
                                                class="form-select"
                                                required
                                            >
                                                <option value="">Seleziona tipologia</option>
                                                <option v-for="serviceType in serviceTypes" :key="serviceType.id" :value="serviceType.name">
                                                    {{ serviceType.abbreviation }} - {{ serviceType.name }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label for="passenger_count" class="form-label fw-bold text-primary fs-5">
                                                <i class="ri-group-line me-1"></i>Numero Passeggeri *
                                            </label>
                                            <input
                                                id="passenger_count"
                                                v-model.number="form.passenger_count"
                                                type="number"
                                                class="form-control form-control-lg border-primary"
                                                required
                                                min="1"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label for="contact_name" class="form-label fw-bold text-primary fs-5">
                                                <i class="ri-user-star-line me-1"></i>Nominativo di Riferimento
                                            </label>
                                            <input
                                                id="contact_name"
                                                v-model="form.contact_name"
                                                type="text"
                                                class="form-control form-control-lg border-primary"
                                                placeholder="Nome del contatto"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label for="contact_phone" class="form-label">Contatto di Riferimento</label>
                                            <input
                                                id="contact_phone"
                                                v-model="form.contact_phone"
                                                type="text"
                                                class="form-control"
                                                placeholder="Telefono/Email del contatto"
                                            />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 2: PASSEGGERI -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-user-3-line me-2"></i>Passeggeri
                                    </legend>
                                    <div v-for="(passenger, index) in form.passengers" :key="index" class="border rounded p-3 mb-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Passeggero {{ index + 1 }}</h6>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-soft-danger"
                                                @click="removePassenger(index)"
                                                v-if="form.passengers.length > 1"
                                            >
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                        <BRow>
                                            <BCol md="3" class="mb-2">
                                                <label class="form-label">Cognome *</label>
                                                <input
                                                    v-model="passenger.surname"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-2">
                                                <label class="form-label">Nome *</label>
                                                <input
                                                    v-model="passenger.name"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-2">
                                                <label class="form-label">Telefono</label>
                                                <input
                                                    v-model="passenger.phone"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-2">
                                                <label class="form-label">Email</label>
                                                <input
                                                    v-model="passenger.email"
                                                    type="email"
                                                    class="form-control form-control-sm"
                                                />
                                            </BCol>
                                            <BCol md="4" class="mb-2">
                                                <label class="form-label">Nazionalità</label>
                                                <select
                                                    v-model="passenger.nationality"
                                                    class="form-select form-select-sm"
                                                >
                                                    <option value="">Seleziona nazionalità</option>
                                                    <option v-for="country in countries" :key="country.code" :value="country.name">
                                                        {{ country.flag }} {{ country.name }}
                                                    </option>
                                                </select>
                                            </BCol>
                                            <BCol md="4" class="mb-2">
                                                <label class="form-label">Provenienza</label>
                                                <input
                                                    v-model="passenger.origin"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                />
                                            </BCol>
                                            <BCol md="4" class="mb-2">
                                                <label class="form-label">Rif. Vettore Provenienza</label>
                                                <input
                                                    v-model="passenger.carrier_reference"
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                />
                                            </BCol>
                                        </BRow>
                                    </div>
                                    <button type="button" class="btn btn-soft-primary btn-sm" @click="addPassenger">
                                        <i class="ri-add-line me-1"></i>Aggiungi Passeggero
                                    </button>
                                </fieldset>

                                <!-- FIELDSET 3: COMMITTENTI/INTERMEDIARI -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-building-line me-2"></i>Committenti/Intermediari
                                    </legend>
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label for="client_id" class="form-label">Committente *</label>
                                            <select
                                                id="client_id"
                                                v-model="form.client_id"
                                                class="form-select"
                                                required
                                                @change="onClientChange"
                                            >
                                                <option value="">Seleziona committente</option>
                                                <option v-for="client in committenti" :key="client.id" :value="client.id">
                                                    {{ client.name }} {{ client.surname }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Referente Committente</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                :value="selectedClientContact"
                                                readonly
                                                disabled
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label for="intermediary_id" class="form-label">Intermediario</label>
                                            <select
                                                id="intermediary_id"
                                                v-model="form.intermediary_id"
                                                class="form-select"
                                                @change="onIntermediaryChange"
                                            >
                                                <option value="">Nessun intermediario</option>
                                                <option v-for="intermediary in intermediari" :key="intermediary.id" :value="intermediary.id">
                                                    {{ intermediary.name }} {{ intermediary.surname }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Referente Intermediario</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                :value="selectedIntermediaryContact"
                                                readonly
                                                disabled
                                            />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 4: VEICOLO -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-car-line me-2"></i>Veicolo
                                    </legend>
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label for="supplier_id" class="form-label">Fornitore</label>
                                            <select
                                                id="supplier_id"
                                                v-model="form.supplier_id"
                                                class="form-select"
                                                @change="onSupplierChange"
                                            >
                                                <option value="">Nessun fornitore esterno</option>
                                                <option v-for="supplier in fornitori" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.name }} {{ supplier.surname }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Referente Fornitore</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                :value="selectedSupplierContact"
                                                readonly
                                                disabled
                                            />
                                        </BCol>
                                        <BCol md="8" class="mb-3">
                                            <label for="vehicle_id" class="form-label">Veicolo *</label>
                                            <select
                                                id="vehicle_id"
                                                v-model="form.vehicle_id"
                                                class="form-select"
                                                required
                                                :disabled="form.vehicle_not_replaceable"
                                            >
                                                <option value="">Seleziona veicolo</option>
                                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                                    {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <div class="form-check">
                                                <input
                                                    id="vehicle_not_replaceable"
                                                    v-model="form.vehicle_not_replaceable"
                                                    type="checkbox"
                                                    class="form-check-input"
                                                />
                                                <label class="form-check-label" for="vehicle_not_replaceable">
                                                    Veicolo non sostituibile
                                                </label>
                                            </div>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 5: DRIVER -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-steering-2-line me-2"></i>Driver
                                    </legend>
                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="driver_select" class="form-label mb-0">Driver Assegnati *</label>
                                                <div class="form-check">
                                                    <input
                                                        id="driver_not_replaceable"
                                                        v-model="form.driver_not_replaceable"
                                                        type="checkbox"
                                                        class="form-check-input"
                                                    />
                                                    <label class="form-check-label" for="driver_not_replaceable">
                                                        Autista non sostituibile
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Select dropdown for adding drivers -->
                                            <select
                                                id="driver_select"
                                                class="form-select mb-3"
                                                @change="addDriver"
                                                :disabled="form.driver_not_replaceable"
                                            >
                                                <option value="">Seleziona un driver da aggiungere...</option>
                                                <option
                                                    v-for="driver in availableDrivers"
                                                    :key="driver.id"
                                                    :value="driver.id"
                                                >
                                                    {{ driver.name }} {{ driver.surname }}
                                                    {{ driver.driver_profile?.overlappable ? '(Sovrapponibile)' : '' }}
                                                </option>
                                            </select>

                                            <!-- Selected drivers as badges -->
                                            <div v-if="selectedDrivers.length > 0" class="border rounded p-3 bg-light">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <div
                                                        v-for="driver in selectedDrivers"
                                                        :key="driver.id"
                                                        class="badge bg-white border d-flex align-items-center gap-2 px-3 py-2"
                                                        style="font-size: 0.9rem;"
                                                    >
                                                        <span
                                                            class="rounded-circle"
                                                            :style="{
                                                                backgroundColor: driver.driver_profile?.color || '#6c757d',
                                                                width: '12px',
                                                                height: '12px',
                                                                display: 'inline-block'
                                                            }"
                                                        ></span>
                                                        <span class="text-dark">{{ driver.name }} {{ driver.surname }}</span>
                                                        <span v-if="driver.driver_profile?.overlappable" class="badge bg-info-subtle text-info" style="font-size: 0.7rem;">
                                                            Sovrapponibile
                                                        </span>
                                                        <button
                                                            type="button"
                                                            class="btn-close btn-sm"
                                                            style="font-size: 0.7rem;"
                                                            @click="removeDriver(driver.id)"
                                                            :title="`Rimuovi ${driver.name} ${driver.surname}`"
                                                            :disabled="form.driver_not_replaceable"
                                                        ></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="alert alert-warning mb-0">
                                                <i class="ri-alert-line me-1"></i>
                                                Nessun driver assegnato. Seleziona almeno un driver dalla lista.
                                            </div>

                                            <small class="text-muted d-block mt-2">
                                                Nota: Assegnando più driver allo stesso servizio, il sistema verificherà eventuali sovrapposizioni orarie per identificare potenziali conflitti.
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label for="external_driver_name" class="form-label">Nome Driver Esterno</label>
                                            <input
                                                id="external_driver_name"
                                                v-model="form.external_driver_name"
                                                type="text"
                                                class="form-control"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label for="external_driver_phone" class="form-label">Telefono Driver Esterno</label>
                                            <input
                                                id="external_driver_phone"
                                                v-model="form.external_driver_phone"
                                                type="text"
                                                class="form-control"
                                            />
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label for="dress_code_id" class="form-label">Dress Code</label>
                                            <select
                                                id="dress_code_id"
                                                v-model="form.dress_code_id"
                                                class="form-select"
                                            >
                                                <option value="">Nessuno</option>
                                                <option v-for="dressCode in dressCodes" :key="dressCode.id" :value="dressCode.id">
                                                    {{ dressCode.name }}
                                                </option>
                                            </select>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 6: BAGAGLI -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-luggage-cart-line me-2"></i>Bagagli
                                    </legend>
                                    <BRow>
                                        <BCol md="2" class="mb-3">
                                            <label for="large_luggage" class="form-label">Bagagli Grandi</label>
                                            <input
                                                id="large_luggage"
                                                v-model.number="form.large_luggage"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="medium_luggage" class="form-label">Bagagli Medi</label>
                                            <input
                                                id="medium_luggage"
                                                v-model.number="form.medium_luggage"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="small_luggage" class="form-label">Bagagli Piccoli</label>
                                            <input
                                                id="small_luggage"
                                                v-model.number="form.small_luggage"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="baby_seat_infant" class="form-label">Babyseat Ovetto</label>
                                            <input
                                                id="baby_seat_infant"
                                                v-model.number="form.baby_seat_infant"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="baby_seat_standard" class="form-label">Babyseat Standard</label>
                                            <input
                                                id="baby_seat_standard"
                                                v-model.number="form.baby_seat_standard"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="baby_seat_booster" class="form-label">Babyseat Booster</label>
                                            <input
                                                id="baby_seat_booster"
                                                v-model.number="form.baby_seat_booster"
                                                type="number"
                                                class="form-control"
                                                min="0"
                                            />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 7: PIANO DI SERVIZIO -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-map-pin-line me-2"></i>Piano di Servizio
                                    </legend>

                                    <!-- SUBFIELDSET: PICKUP -->
                                    <fieldset class="border rounded p-3 mb-3 bg-light">
                                        <legend class="float-none w-auto px-2 fs-6 fw-semibold text-success">
                                            <i class="ri-map-pin-user-line me-1"></i>Pickup
                                        </legend>
                                        <BRow>
                                            <BCol md="6" class="mb-3">
                                                <label for="pickup_datetime" class="form-label fw-bold text-success fs-5">
                                                    <i class="ri-calendar-event-line me-1"></i>Data/Ora Pickup *
                                                </label>
                                                <input
                                                    id="pickup_datetime"
                                                    v-model="form.pickup_datetime"
                                                    type="datetime-local"
                                                    class="form-control form-control-lg border-success"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="pickup_location" class="form-label fw-bold text-success fs-5">
                                                    <i class="ri-map-pin-line me-1"></i>Luogo Pickup *
                                                </label>
                                                <input
                                                    id="pickup_location"
                                                    v-model="form.pickup_location"
                                                    type="text"
                                                    class="form-control form-control-lg border-success"
                                                    placeholder="Es. Aeroporto Fiumicino, Hotel Excelsior..."
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="12" class="mb-3">
                                                <label for="pickup_address" class="form-label">Indirizzo Completo Pickup *</label>
                                                <input
                                                    id="pickup_address"
                                                    v-model="form.pickup_address"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Via, numero civico, città, CAP"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="vehicle_departure_datetime" class="form-label">Data/Ora Uscita Mezzo *</label>
                                                <input
                                                    id="vehicle_departure_datetime"
                                                    v-model="form.vehicle_departure_datetime"
                                                    type="datetime-local"
                                                    class="form-control"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-3">
                                                <label for="pickup_latitude" class="form-label">Latitudine</label>
                                                <input
                                                    id="pickup_latitude"
                                                    v-model="form.pickup_latitude"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Es. 41.9028"
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-3">
                                                <label for="pickup_longitude" class="form-label">Longitudine</label>
                                                <input
                                                    id="pickup_longitude"
                                                    v-model="form.pickup_longitude"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Es. 12.4964"
                                                />
                                            </BCol>
                                        </BRow>
                                    </fieldset>

                                    <!-- SUBFIELDSET: DROPOFF -->
                                    <fieldset class="border rounded p-3 mb-3 bg-light">
                                        <legend class="float-none w-auto px-2 fs-6 fw-semibold text-danger">
                                            <i class="ri-map-pin-range-line me-1"></i>Dropoff
                                        </legend>
                                        <BRow>
                                            <BCol md="6" class="mb-3">
                                                <label for="dropoff_datetime" class="form-label fw-bold text-danger fs-5">
                                                    <i class="ri-calendar-event-line me-1"></i>Data/Ora Dropoff *
                                                </label>
                                                <input
                                                    id="dropoff_datetime"
                                                    v-model="form.dropoff_datetime"
                                                    type="datetime-local"
                                                    class="form-control form-control-lg border-danger"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="dropoff_location" class="form-label fw-bold text-danger fs-5">
                                                    <i class="ri-map-pin-line me-1"></i>Luogo Dropoff *
                                                </label>
                                                <input
                                                    id="dropoff_location"
                                                    v-model="form.dropoff_location"
                                                    type="text"
                                                    class="form-control form-control-lg border-danger"
                                                    placeholder="Es. Stazione Termini, Ufficio Cliente..."
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="12" class="mb-3">
                                                <label for="dropoff_address" class="form-label">Indirizzo Completo Dropoff *</label>
                                                <input
                                                    id="dropoff_address"
                                                    v-model="form.dropoff_address"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Via, numero civico, città, CAP"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="vehicle_return_datetime" class="form-label">Data/Ora Rientro Mezzo *</label>
                                                <input
                                                    id="vehicle_return_datetime"
                                                    v-model="form.vehicle_return_datetime"
                                                    type="datetime-local"
                                                    class="form-control"
                                                    required
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-3">
                                                <label for="dropoff_latitude" class="form-label">Latitudine</label>
                                                <input
                                                    id="dropoff_latitude"
                                                    v-model="form.dropoff_latitude"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Es. 41.9028"
                                                />
                                            </BCol>
                                            <BCol md="3" class="mb-3">
                                                <label for="dropoff_longitude" class="form-label">Longitudine</label>
                                                <input
                                                    id="dropoff_longitude"
                                                    v-model="form.dropoff_longitude"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Es. 12.4964"
                                                />
                                            </BCol>
                                        </BRow>
                                    </fieldset>

                                    <!-- SUBFIELDSET: ESPERIENZE -->
                                    <fieldset class="border rounded p-3 mb-0 bg-light">
                                        <legend class="float-none w-auto px-2 fs-6 fw-semibold text-info">
                                            <i class="ri-calendar-check-line me-1"></i>Esperienze
                                        </legend>

                                        <!-- Show message if service not yet saved -->
                                        <div v-if="!isEdit" class="alert alert-info mb-0">
                                            <i class="ri-information-line me-1"></i>
                                            Le esperienze potranno essere aggiunte dopo aver salvato il servizio.
                                        </div>

                                        <!-- Activity List for Edit Mode -->
                                        <div v-else>
                                            <!-- Existing Activities Table -->
                                            <div v-if="form.activities && form.activities.length > 0" class="table-responsive mb-3">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Inizio</th>
                                                            <th>Fine</th>
                                                            <th>Descrizione Esperienza</th>
                                                            <th>Tipo</th>
                                                            <th>Fornitore</th>
                                                            <th>Costo</th>
                                                            <th>€/Persona</th>
                                                            <th>Pagamento</th>
                                                            <th class="text-center">Azioni</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="activity in form.activities" :key="activity.id">
                                                            <td>{{ formatDateTime(activity.start_time) }}</td>
                                                            <td>{{ formatDateTime(activity.end_time) }}</td>
                                                            <td>{{ activity.name }}</td>
                                                            <td>
                                                                <span v-if="getActivityTypeName(activity.activity_type_id)" class="badge bg-info-subtle text-info">
                                                                    {{ getActivityTypeName(activity.activity_type_id) }}
                                                                </span>
                                                                <span v-else class="text-muted">-</span>
                                                            </td>
                                                            <td>
                                                                <span v-if="activity.supplier">{{ activity.supplier.name }} {{ activity.supplier.surname }}</span>
                                                                <span v-else class="text-muted">-</span>
                                                            </td>
                                                            <td>€ {{ formatAmount(activity.cost) }}</td>
                                                            <td>€ {{ formatAmount(activity.cost_per_person) }}</td>
                                                            <td>
                                                                <span v-if="activity.payment_type" class="badge" :class="getPaymentTypeBadge(activity.payment_type)">
                                                                    {{ activity.payment_type }}
                                                                </span>
                                                                <span v-else class="text-muted">-</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-primary me-1"
                                                                    @click="openActivityModal(activity)"
                                                                    title="Modifica"
                                                                >
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-danger"
                                                                    @click="removeActivity(activity.id)"
                                                                    title="Elimina"
                                                                >
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Empty State -->
                                            <div v-else class="alert alert-warning mb-3">
                                                <i class="ri-alert-line me-1"></i>
                                                Nessuna esperienza collegata a questo servizio.
                                            </div>

                                            <!-- Add Activity Button -->
                                            <div class="text-end mb-3">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    @click="openActivityModal()"
                                                >
                                                    <i class="ri-add-line me-1"></i>Aggiungi Esperienza
                                                </button>
                                            </div>

                                            <!-- Conferma Prenotazioni Toggle -->
                                            <BRow>
                                                <BCol md="12">
                                                    <div class="form-check form-switch">
                                                        <input
                                                            id="activity_confirmation_enabled"
                                                            v-model="activityConfirmationEnabled"
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            @change="toggleActivityConfirmation"
                                                        />
                                                        <label class="form-check-label" for="activity_confirmation_enabled">
                                                            <strong>Conferma prenotazioni</strong>
                                                        </label>
                                                        <small class="d-block text-muted">
                                                            Quando attivo, crea automaticamente un task di conferma per ogni esperienza
                                                        </small>
                                                    </div>
                                                </BCol>
                                            </BRow>
                                        </div>
                                    </fieldset>
                                </fieldset>

                                <!-- FIELDSET 8: NOTE -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-text-line me-2"></i>Note
                                    </legend>
                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label for="notes" class="form-label">Note</label>
                                            <textarea
                                                id="notes"
                                                v-model="form.notes"
                                                class="form-control"
                                                rows="4"
                                            ></textarea>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 9: PREZZI -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-price-tag-3-line me-2"></i>Prezzi
                                    </legend>

                                    <!-- Riga 1: Prezzo base e parametri -->
                                    <BRow>
                                        <BCol md="4" class="mb-3">
                                            <label for="service_price" class="form-label fw-bold text-primary">
                                                <i class="ri-money-euro-circle-line me-1"></i>Prezzo Imponibile Totale
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    id="service_price"
                                                    v-model.number="form.service_price"
                                                    type="number"
                                                    step="0.01"
                                                    class="form-control border-primary"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label for="vat_rate" class="form-label">Aliquota IVA</label>
                                            <select
                                                id="vat_rate"
                                                v-model.number="form.vat_rate"
                                                class="form-select"
                                            >
                                                <option :value="10">10%</option>
                                                <option :value="22">22%</option>
                                            </select>
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label for="card_fees_percentage" class="form-label">Card Fees %</label>
                                            <div class="input-group">
                                                <input
                                                    id="card_fees_percentage"
                                                    v-model.number="form.card_fees_percentage"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    class="form-control"
                                                    placeholder="5"
                                                />
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label for="status_id" class="form-label">Stato Servizio *</label>
                                            <select
                                                id="status_id"
                                                v-model="form.status_id"
                                                class="form-select"
                                                required
                                            >
                                                <option value="">Seleziona stato</option>
                                                <option v-for="status in serviceStatuses" :key="status.id" :value="status.id">
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                        </BCol>
                                    </BRow>

                                    <!-- Riga 2: Acconto -->
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label for="deposit_percentage" class="form-label">Acconto %</label>
                                            <div class="input-group">
                                                <input
                                                    id="deposit_percentage"
                                                    v-model.number="form.deposit_percentage"
                                                    type="number"
                                                    step="1"
                                                    min="0"
                                                    max="100"
                                                    class="form-control"
                                                    placeholder="30"
                                                />
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </BCol>
                                        <BCol md="9" class="mb-3">
                                            <label for="deposit_amount" class="form-label">Acconto €</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    id="deposit_amount"
                                                    v-model.number="form.deposit_amount"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <small class="text-muted">Formula: (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (Acconto% / 100)</small>
                                        </BCol>
                                    </BRow>

                                    <!-- Riga 3: Saldi -->
                                    <BRow>
                                        <BCol md="4" class="mb-3">
                                            <label for="balance_taxable" class="form-label">Saldo Imponibile</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    id="balance_taxable"
                                                    v-model.number="form.balance_taxable"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <small class="text-muted">Formula: Imponibile × (100 - Acconto%) / 100</small>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label for="balance_handling_fees" class="form-label">Saldo Handling Fees</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    id="balance_handling_fees"
                                                    v-model.number="form.balance_handling_fees"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <small class="text-muted">Formula: (Imponibile × (100 + IVA%) / 100) × (100 - Acconto%) / 100</small>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label for="balance_card_fees" class="form-label">Saldo Card Fees</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input
                                                    id="balance_card_fees"
                                                    v-model.number="form.balance_card_fees"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <small class="text-muted">Formula: (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (100 - Acconto%) / 100</small>
                                        </BCol>
                                    </BRow>

                                    <!-- Bottone Calcola Corrispettivi -->
                                    <BRow class="mb-3">
                                        <BCol md="12" class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-soft-success"
                                                @click="calculateTotals"
                                            >
                                                <i class="ri-calculator-line me-1"></i>Calcola Corrispettivi
                                            </button>
                                        </BCol>
                                    </BRow>

                                    <!-- Interruttore Contabilizza il servizio -->
                                    <BRow>
                                        <BCol md="12">
                                            <div class="form-check form-switch">
                                                <input
                                                    id="accounting_enabled"
                                                    v-model="accountingEnabled"
                                                    type="checkbox"
                                                    class="form-check-input"
                                                />
                                                <label class="form-check-label" for="accounting_enabled">
                                                    <strong>Contabilizza il servizio</strong>
                                                </label>
                                                <small class="d-block text-muted">
                                                    Quando attivo, crea/aggiorna automaticamente i movimenti contabili di vendita (acconto e saldo) al salvataggio del servizio
                                                </small>
                                            </div>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 10: CONTABILITÀ -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-list-3-line me-2"></i>Contabilità
                                    </legend>

                                    <!-- Riepilogo Contabile -->
                                    <div v-if="isEdit && form.accounting_transactions && form.accounting_transactions.length > 0" class="border rounded p-3 mb-3 bg-light">
                                        <fieldset>
                                            <legend class="fs-6 fw-semibold text-primary mb-3">
                                                <i class="ri-pie-chart-line me-2"></i>Riepilogo Contabile
                                            </legend>
                                            <BRow>
                                                <!-- Vendite -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border border-success">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Vendite</p>
                                                                    <h5 class="mb-0 text-success">€ {{ formatAmount(accountingSummary.sales) }}</h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i class="ri-money-euro-circle-line fs-2 text-success"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                                <!-- Acquisti -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border border-danger">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Acquisti</p>
                                                                    <h5 class="mb-0 text-danger">€ {{ formatAmount(accountingSummary.purchases) }}</h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i class="ri-shopping-cart-line fs-2 text-danger"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                                <!-- Intermediazioni -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border border-warning">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Intermediazioni</p>
                                                                    <h5 class="mb-0 text-warning">€ {{ formatAmount(accountingSummary.intermediations) }}</h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i class="ri-team-line fs-2 text-warning"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                                <!-- Resi -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border border-info">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Resi</p>
                                                                    <h5 class="mb-0 text-info">€ {{ formatAmount(accountingSummary.supplierRefunds) }}</h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i class="ri-refund-line fs-2 text-info"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                                <!-- Rimborsi -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border border-secondary">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Rimborsi</p>
                                                                    <h5 class="mb-0 text-secondary">€ {{ formatAmount(accountingSummary.customerRefunds) }}</h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i class="ri-hand-coin-line fs-2 text-secondary"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                                <!-- Risultato Totale (moved to end) -->
                                                <BCol md="4" lg="2" class="mb-3">
                                                    <div class="card card-animate border" :class="accountingSummary.total >= 0 ? 'border-success' : 'border-danger'">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-1">Risultato</p>
                                                                    <h5 class="mb-0" :class="accountingSummary.total >= 0 ? 'text-success' : 'text-danger'">
                                                                        € {{ formatAmount(accountingSummary.total) }}
                                                                    </h5>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <i :class="['fs-2', accountingSummary.total >= 0 ? 'ri-arrow-up-circle-line text-success' : 'ri-arrow-down-circle-line text-danger']"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </BCol>
                                            </BRow>
                                        </fieldset>
                                    </div>

                                    <!-- Movimenti Contabili -->
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">Movimenti Contabili</h6>
                                            <div class="d-flex gap-2">
                                                <button
                                                    v-if="isEdit && selectedTransactions.length > 0"
                                                    type="button"
                                                    class="btn btn-sm btn-soft-danger"
                                                    @click="deleteSelectedTransactions"
                                                >
                                                    <i class="ri-delete-bin-line me-1"></i>Cancella Selezionati ({{ selectedTransactions.length }})
                                                </button>
                                                <button
                                                    v-if="isEdit"
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    @click="openTransactionModal()"
                                                >
                                                    <i class="ri-add-line me-1"></i>Nuovo Movimento
                                                </button>
                                            </div>
                                        </div>
                                        <div class="alert alert-info" v-if="!isEdit">
                                            I movimenti contabili potranno essere aggiunti dopo aver salvato il servizio.
                                        </div>
                                        <div v-else-if="form.accounting_transactions && form.accounting_transactions.length === 0" class="alert alert-warning">
                                            Nessun movimento contabile collegato.
                                        </div>
                                        <div v-else-if="form.accounting_transactions" class="table-responsive">
                                            <table class="table table-hover table-nowrap align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" style="width: 30px;">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input"
                                                                :checked="isAllTransactionsSelected"
                                                                @change="toggleSelectAllTransactions"
                                                                title="Seleziona tutti"
                                                            />
                                                        </th>
                                                        <th>Data</th>
                                                        <th>Tipo</th>
                                                        <th>Importo</th>
                                                        <th>Rata</th>
                                                        <th>Causali</th>
                                                        <th>Controparte</th>
                                                        <th>Documenti</th>
                                                        <th>Stato</th>
                                                        <th>Azioni</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="transaction in form.accounting_transactions" :key="transaction.id">
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input"
                                                                :value="transaction.id"
                                                                v-model="selectedTransactions"
                                                            />
                                                        </td>
                                                        <td>{{ formatDate(transaction.transaction_date) }}</td>
                                                        <td>
                                                            <span
                                                                :class="getTransactionTypeBadge(transaction.transaction_type)"
                                                                :title="getTransactionTypeLabel(transaction.transaction_type)"
                                                            >
                                                                {{ getTransactionTypeAbbr(transaction.transaction_type) }}
                                                            </span>
                                                        </td>
                                                        <td class="fw-medium">
                                                            <span
                                                                v-if="editingTransactionPrice !== transaction.id"
                                                                @click="startEditTransactionPrice(transaction)"
                                                                class="cursor-pointer text-primary text-decoration-underline"
                                                                title="Clicca per modificare"
                                                            >
                                                                € {{ parseFloat(transaction.amount).toFixed(2) }}
                                                            </span>
                                                            <div v-else>
                                                                <div class="input-group input-group-sm mb-1" style="max-width: 120px;">
                                                                    <span class="input-group-text">€</span>
                                                                    <input
                                                                        v-model="editingPriceValue"
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        class="form-control form-control-sm"
                                                                        @keyup.esc="cancelEditTransactionPrice"
                                                                        :ref="el => { if (el) priceInputRefs[transaction.id] = el }"
                                                                    />
                                                                </div>
                                                                <div class="d-flex gap-1">
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-success btn-sm"
                                                                        @click="saveTransactionPrice(transaction)"
                                                                        title="Salva"
                                                                    >
                                                                        <i class="ri-check-line"></i> Salva
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-secondary btn-sm"
                                                                        @click="cancelEditTransactionPrice"
                                                                        title="Annulla"
                                                                    >
                                                                        <i class="ri-close-line"></i> Annulla
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge bg-secondary-subtle text-secondary"
                                                                :title="getInstallmentLabel(transaction.installment)"
                                                            >
                                                                {{ getInstallmentAbbr(transaction.installment) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span v-if="transaction.payment_reason || transaction.accounting_entry">
                                                                <span v-if="transaction.payment_reason">{{ transaction.payment_reason }}</span>
                                                                <br v-if="transaction.payment_reason && transaction.accounting_entry">
                                                                <small v-if="transaction.accounting_entry" class="text-muted">
                                                                    {{ transaction.accounting_entry.abbreviation || transaction.accounting_entry.name }}
                                                                </small>
                                                            </span>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
                                                        <td>
                                                            <span v-if="transaction.counterpart">
                                                                {{ transaction.counterpart.name }} {{ transaction.counterpart.surname }}
                                                                <br>
                                                                <small class="text-muted">{{ transaction.counterpart.email }}</small>
                                                            </span>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
                                                        <td>
                                                            <div v-if="transaction.document_number || transaction.document_due_date">
                                                                <span v-if="transaction.document_number">{{ transaction.document_number }}</span>
                                                                <span v-else class="text-muted">-</span>
                                                                <br>
                                                                <small v-if="transaction.document_due_date" :class="getDueDateClass(transaction)">
                                                                    {{ formatDate(transaction.document_due_date) }}
                                                                </small>
                                                                <small v-else class="text-muted">-</small>
                                                            </div>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                v-if="editingTransactionStatus !== transaction.id"
                                                                @click="startEditTransactionStatus(transaction)"
                                                                :class="getStatusBadge(transaction.status)"
                                                                class="cursor-pointer"
                                                                :title="getStatusLabel(transaction.status) + ' - Clicca per modificare'"
                                                            >
                                                                {{ getStatusAbbr(transaction.status) }}
                                                            </span>
                                                            <div v-else>
                                                                <select
                                                                    v-model="editingStatusValue"
                                                                    class="form-select form-select-sm mb-1"
                                                                    @keyup.esc="cancelEditTransactionStatus"
                                                                    :ref="el => { if (el) statusInputRefs[transaction.id] = el }"
                                                                    style="max-width: 130px;"
                                                                >
                                                                    <template v-if="transaction.transaction_type === 'purchase' || transaction.transaction_type === 'intermediation'">
                                                                        <option value="to_pay">Da Pagare</option>
                                                                        <option value="paid">Pagato</option>
                                                                        <option value="suspended">Sospeso</option>
                                                                        <option value="cancelled">Annullato</option>
                                                                    </template>
                                                                    <template v-else-if="transaction.transaction_type === 'sale'">
                                                                        <option value="to_collect">Da Incassare</option>
                                                                        <option value="collected">Incassato</option>
                                                                        <option value="suspended">Sospeso</option>
                                                                        <option value="cancelled">Annullato</option>
                                                                    </template>
                                                                </select>
                                                                <div class="d-flex gap-1">
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-success btn-sm"
                                                                        @click="saveTransactionStatus(transaction)"
                                                                        title="Salva"
                                                                    >
                                                                        <i class="ri-check-line"></i> Salva
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-secondary btn-sm"
                                                                        @click="cancelEditTransactionStatus"
                                                                        title="Annulla"
                                                                    >
                                                                        <i class="ri-close-line"></i> Annulla
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-primary"
                                                                    @click="openTransactionModal(transaction)"
                                                                    title="Modifica"
                                                                >
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-danger"
                                                                    @click="removeTransaction(transaction.id)"
                                                                    title="Elimina"
                                                                >
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- FIELDSET 11: TASKS -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-task-line me-2"></i>Task
                                    </legend>
                                    <div v-if="isEdit" class="d-flex justify-content-end mb-3">
                                        <button type="button" @click="openTaskModal()" class="btn btn-primary btn-sm">
                                            <i class="bx bx-plus me-1"></i>
                                            Nuovo Task
                                        </button>
                                    </div>

                                    <!-- Info message when not in edit mode -->
                                    <div class="alert alert-info" v-if="!isEdit">
                                        I task potranno essere aggiunti dopo aver salvato il servizio.
                                    </div>

                                    <!-- Tasks Table -->
                                    <div v-else-if="serviceTasks.length > 0" class="table-responsive">
                                        <table class="table table-hover table-nowrap align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col">Nome Task</th>
                                                    <th scope="col">Scadenza</th>
                                                    <th scope="col">Assegnatario</th>
                                                    <th scope="col">Stato</th>
                                                    <th scope="col">Note</th>
                                                    <th scope="col">Azioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="task in sortedServiceTasks" :key="task.id">
                                                    <td class="fw-medium">{{ task.name }}</td>
                                                    <td>
                                                        <span v-if="task.due_date" :class="getTaskDueDateClass(task)">
                                                            {{ formatDate(task.due_date) }}
                                                        </span>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td>
                                                        <div v-if="task.assigned_users && task.assigned_users.length > 0">
                                                            <div v-for="(user, index) in task.assigned_users" :key="user.id" class="mb-1">
                                                                {{ user.name }} {{ user.surname }}
                                                                <br><small class="text-muted">{{ user.role }}</small>
                                                            </div>
                                                        </div>
                                                        <span v-else class="text-muted">Non assegnato</span>
                                                    </td>
                                                    <td>
                                                        <span :class="getTaskStatusBadgeClass(task.status)">
                                                            {{ getTaskStatusLabel(task.status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ task.notes ? (task.notes.substring(0, 50) + (task.notes.length > 50 ? '...' : '')) : '-' }}</small>
                                                    </td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-soft-primary me-2"
                                                            @click="openTaskModal(task)"
                                                        >
                                                            <i class="bx bx-edit"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-soft-danger"
                                                            @click="deleteTask(task.id)"
                                                        >
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- No Tasks in edit mode -->
                                    <div v-else class="text-center text-muted py-3">
                                        <p class="mb-0">Nessun task associato a questo servizio</p>
                                    </div>
                                </fieldset>
                            </div>
                        </BCardBody>
                        <BCardFooter>
                            <div class="d-flex justify-content-between">
                                <Link :href="route('easyncc.services.index')" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line me-1"></i>Annulla
                                </Link>
                                <div>
                                    <button type="button" class="btn btn-success me-2" :disabled="submitting" @click="saveAndStay">
                                        <span v-if="submitting && !exitAfterSave" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva
                                    </button>
                                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                                        <span v-if="submitting && exitAfterSave" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva ed Esci
                                    </button>
                                </div>
                            </div>
                        </BCardFooter>
                    </BCard>
                </form>
            </BCol>
        </BRow>

        <!-- Activity Modal -->
        <BModal
            v-model="showActivityModal"
            :title="activityForm.id ? 'Modifica Esperienza' : 'Nuova Esperienza'"
            size="lg"
            hide-footer
            @hidden="cancelActivityEdit"
        >
            <!-- Fieldset 1: Anagrafica -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-file-list-3-line me-1"></i>
                    Anagrafica
                </legend>
                <BRow>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Fornitore</label>
                            <select v-model="activityForm.supplier_id" class="form-select">
                                <option value="">Nessuno</option>
                                <option v-for="supplier in fornitori" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }} {{ supplier.surname }}
                                </option>
                            </select>
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Tipologia</label>
                            <select v-model="activityForm.activity_type_id" class="form-select">
                                <option value="">Nessuna</option>
                                <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </BCol>
                    <BCol md="12">
                        <div class="mb-3">
                            <label class="form-label">Descrizione Esperienza <span class="text-danger">*</span></label>
                            <input
                                v-model="activityForm.name"
                                type="text"
                                class="form-control"
                                required
                            />
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 2: Orario -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-time-line me-1"></i>
                    Orario
                </legend>
                <BRow>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Data e Ora Inizio <span class="text-danger">*</span></label>
                            <input
                                v-model="activityForm.start_time"
                                type="datetime-local"
                                class="form-control"
                                :min="form.pickup_datetime"
                                :max="form.dropoff_datetime"
                                required
                            />
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Data e Ora Fine <span class="text-danger">*</span></label>
                            <input
                                v-model="activityForm.end_time"
                                type="datetime-local"
                                class="form-control"
                                :min="activityForm.start_time || form.pickup_datetime"
                                :max="form.dropoff_datetime"
                                required
                            />
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 3: Economics -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-money-euro-circle-line me-1"></i>
                    Economics
                </legend>
                <BRow>
                    <BCol md="4">
                        <div class="mb-3">
                            <label class="form-label">Costo Totale</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input
                                    v-model="activityForm.cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                />
                            </div>
                        </div>
                    </BCol>
                    <BCol md="4">
                        <div class="mb-3">
                            <label class="form-label">Costo per Persona</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input
                                    v-model="activityForm.cost_per_person"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                />
                            </div>
                        </div>
                    </BCol>
                    <BCol md="4">
                        <div class="mb-3">
                            <label class="form-label">Pagamento</label>
                            <select v-model="activityForm.payment_type" class="form-select">
                                <option value="">Seleziona</option>
                                <option value="INCLUSO">Incluso</option>
                                <option value="CLIENTE">Cliente</option>
                                <option value="AGENZIA">Agenzia</option>
                                <option value="NESSUNO">Nessuno</option>
                            </select>
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 4: Note -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-file-text-line me-1"></i>
                    Note
                </legend>
                <BRow>
                    <BCol md="12">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea
                                v-model="activityForm.notes"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Action Buttons -->
            <div class="text-end">
                <button
                    type="button"
                    class="btn btn-secondary me-2"
                    @click="closeActivityModal"
                >
                    <i class="ri-close-line me-1"></i>Annulla
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveActivity"
                    :disabled="!activityForm.name || !activityForm.start_time || !activityForm.end_time"
                >
                    <i :class="activityForm.id ? 'ri-save-line' : 'ri-add-line'" class="me-1"></i>
                    {{ activityForm.id ? 'Aggiorna Esperienza' : 'Aggiungi Esperienza' }}
                </button>
            </div>
        </BModal>

        <!-- Transaction Modal -->
        <BModal
            v-model="showTransactionModal"
            :title="transactionForm.id ? 'Modifica Movimento Contabile' : 'Nuovo Movimento Contabile'"
            size="lg"
            hide-footer
            @hidden="cancelTransactionEdit"
        >
            <!-- Fieldset 1: Dati Principali -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-file-list-3-line me-1"></i>
                    Dati Principali
                </legend>
                <BRow>
                    <!-- Transaction Type -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Tipo Movimento <span class="text-danger">*</span></label>
                            <select v-model="transactionForm.transaction_type" class="form-select" @change="onTransactionTypeChange" required>
                                <option value="">Seleziona tipo</option>
                                <option value="purchase">Acquisto (Costi da Fornitore)</option>
                                <option value="sale">Vendita (Ricavi da Committente)</option>
                                <option value="intermediation">Intermediazione (Commissioni)</option>
                            </select>
                        </div>
                    </BCol>

                    <!-- Transaction Date -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Data Movimento <span class="text-danger">*</span></label>
                            <input
                                v-model="transactionForm.transaction_date"
                                type="date"
                                class="form-control"
                                required
                            />
                        </div>
                    </BCol>

                    <!-- Amount -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Importo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input
                                    v-model="transactionForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    required
                                />
                            </div>
                        </div>
                    </BCol>

                    <!-- Payment Reason (Causale) -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Causale Movimento</label>
                            <input
                                v-model="transactionForm.payment_reason"
                                type="text"
                                class="form-control"
                                placeholder="Es. Pagamento servizio, Acconto, Saldo finale..."
                            />
                        </div>
                    </BCol>

                    <!-- Installment -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Rata <span class="text-danger">*</span></label>
                            <select v-model="transactionForm.installment" class="form-select" required>
                                <option value="">Seleziona rata</option>
                                <option value="deposit">Acconto</option>
                                <option value="balance">Saldo</option>
                                <option value="supplier_refund">Reso Fornitore</option>
                                <option value="customer_refund">Rimborso Cliente</option>
                            </select>
                        </div>
                    </BCol>

                    <!-- Status -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Stato <span class="text-danger">*</span></label>
                            <select v-model="transactionForm.status" class="form-select" required>
                                <option value="">Seleziona stato</option>
                                <!-- Purchase and Intermediation are DARE (costs/expenses) - use to_pay/paid -->
                                <template v-if="transactionForm.transaction_type === 'purchase' || transactionForm.transaction_type === 'intermediation'">
                                    <option value="to_pay">Da Pagare</option>
                                    <option value="paid">Pagato</option>
                                    <option value="suspended">Sospeso</option>
                                    <option value="cancelled">Annullato</option>
                                </template>
                                <!-- Sale is AVERE (revenue/income) - use to_collect/collected -->
                                <template v-else-if="transactionForm.transaction_type === 'sale'">
                                    <option value="to_collect">Da Incassare</option>
                                    <option value="collected">Incassato</option>
                                    <option value="suspended">Sospeso</option>
                                    <option value="cancelled">Annullato</option>
                                </template>
                                <!-- No transaction type selected yet - show all -->
                                <template v-else>
                                    <option value="to_pay">Da Pagare</option>
                                    <option value="paid">Pagato</option>
                                    <option value="to_collect">Da Incassare</option>
                                    <option value="collected">Incassato</option>
                                    <option value="suspended">Sospeso</option>
                                    <option value="cancelled">Annullato</option>
                                </template>
                            </select>
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 2: Riferimenti -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-links-line me-1"></i>
                    Riferimenti
                </legend>
                <BRow>
                    <!-- Accounting Entry -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Causale Contabile</label>
                            <select v-model="transactionForm.accounting_entry_id" class="form-select">
                                <option value="">Nessuna</option>
                                <option v-for="entry in accountingEntries" :key="entry.id" :value="entry.id">
                                    {{ entry.name }} ({{ entry.abbreviation }})
                                </option>
                            </select>
                        </div>
                    </BCol>

                    <!-- Counterpart -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Controparte</label>
                            <select v-model="transactionForm.counterpart_id" class="form-select">
                                <option value="">Nessuna</option>
                                <option v-for="user in filteredCounterparts" :key="user.id" :value="user.id">
                                    {{ user.name }} {{ user.surname }} - {{ user.email }}
                                </option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <span v-if="transactionForm.transaction_type === 'purchase'">Seleziona un Fornitore</span>
                                <span v-else-if="transactionForm.transaction_type === 'sale'">Seleziona un Committente</span>
                                <span v-else-if="transactionForm.transaction_type === 'intermediation'">Seleziona un Intermediario</span>
                            </small>
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 3: Documenti e Pagamenti -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-file-text-line me-1"></i>
                    Documenti e Pagamenti
                </legend>
                <BRow>
                    <!-- Document Number -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Numero Documento</label>
                            <input
                                v-model="transactionForm.document_number"
                                type="text"
                                class="form-control"
                                placeholder="Es: FT-2024-001"
                            />
                        </div>
                    </BCol>

                    <!-- Document Due Date -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Scadenza Documento</label>
                            <input
                                v-model="transactionForm.document_due_date"
                                type="date"
                                class="form-control"
                            />
                        </div>
                    </BCol>

                    <!-- Payment Date -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Data Pagamento</label>
                            <input
                                v-model="transactionForm.payment_date"
                                type="date"
                                class="form-control"
                            />
                        </div>
                    </BCol>

                    <!-- Payment Type -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Modalità Pagamento</label>
                            <select v-model="transactionForm.payment_type" class="form-select">
                                <option value="">Nessuna</option>
                                <option v-for="type in paymentTypes" :key="type.id" :value="type.name">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                    </BCol>

                    <!-- IBAN -->
                    <BCol md="12">
                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input
                                v-model="transactionForm.iban"
                                type="text"
                                class="form-control"
                                placeholder="IT60X0542811101000000123456"
                            />
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Fieldset 4: Note -->
            <fieldset class="border rounded p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                    <i class="ri-sticky-note-line me-1"></i>
                    Note
                </legend>
                <BRow>
                    <BCol md="12">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea
                                v-model="transactionForm.notes"
                                class="form-control"
                                rows="4"
                                placeholder="Note aggiuntive..."
                            ></textarea>
                        </div>
                    </BCol>
                </BRow>
            </fieldset>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2">
                <button
                    type="button"
                    class="btn btn-soft-secondary"
                    @click="closeTransactionModal"
                >
                    <i class="ri-close-line me-1"></i>
                    Annulla
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveTransaction"
                    :disabled="!transactionForm.transaction_date || !transactionForm.amount || !transactionForm.transaction_type || !transactionForm.installment || !transactionForm.status"
                >
                    <i :class="transactionForm.id ? 'ri-save-line' : 'ri-add-line'" class="me-1"></i>
                    {{ transactionForm.id ? 'Aggiorna' : 'Crea' }} Movimento
                </button>
            </div>
        </BModal>

        <!-- Task Modal -->
        <BModal
            v-model="showTaskModal"
            :title="taskForm.id ? 'Modifica Task' : 'Nuovo Task'"
            size="lg"
            hide-footer
            @hidden="cancelTaskEdit"
        >
            <form @submit.prevent="saveTask">
                <!-- Task Fields -->
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                        <i class="ri-file-list-3-line me-1"></i>
                        Anagrafica
                    </legend>
                    <BRow>
                        <!-- Name -->
                        <BCol md="12">
                            <div class="mb-3">
                                <label class="form-label">Nome Task <span class="text-danger">*</span></label>
                                <input
                                    v-model="taskForm.name"
                                    type="text"
                                    class="form-control"
                                    required
                                />
                            </div>
                        </BCol>

                        <!-- Due Date -->
                        <BCol md="6">
                            <div class="mb-3">
                                <label class="form-label">Scadenza</label>
                                <input
                                    v-model="taskForm.due_date"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                        </BCol>

                        <!-- Assigned To (Multi-Select) -->
                        <BCol md="6">
                            <div class="mb-3">
                                <label class="form-label">Assegnatari</label>
                                <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                    <div v-for="user in taskAssignableUsers" :key="user.id" class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :value="user.id"
                                            v-model="taskForm.assigned_users"
                                            :id="`task-assignee-${user.id}`"
                                        />
                                        <label class="form-check-label" :for="`task-assignee-${user.id}`">
                                            {{ user.name }} {{ user.surname }} ({{ user.role }})
                                        </label>
                                    </div>
                                    <div v-if="taskAssignableUsers.length === 0" class="text-muted small">
                                        Nessun utente disponibile
                                    </div>
                                </div>
                            </div>
                        </BCol>

                        <!-- Status -->
                        <BCol md="12">
                            <div class="mb-3">
                                <label class="form-label">Stato <span class="text-danger">*</span></label>
                                <select v-model="taskForm.status" class="form-select" required>
                                    <option value="to_complete">Da Completare</option>
                                    <option value="completed">Completato</option>
                                    <option value="cancelled">Annullato</option>
                                </select>
                            </div>
                        </BCol>
                    </BRow>
                </fieldset>

                <!-- Note -->
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                        <i class="ri-file-text-line me-1"></i>
                        Note
                    </legend>
                    <BRow>
                        <BCol md="12">
                            <div class="mb-3">
                                <label class="form-label">Note</label>
                                <textarea
                                    v-model="taskForm.notes"
                                    class="form-control"
                                    rows="4"
                                ></textarea>
                            </div>
                        </BCol>
                    </BRow>
                </fieldset>

                <!-- Error Messages -->
                <div v-if="taskErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, index) in taskErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary" @click="cancelTaskEdit">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="taskSaving">
                        <span v-if="taskSaving" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else :class="taskForm.id ? 'ri-save-line' : 'ri-add-line'" class="me-1"></i>
                        {{ taskForm.id ? 'Aggiorna' : 'Crea' }}
                    </button>
                </div>
            </form>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    service: {
        type: Object,
        default: null
    }
});

const isEdit = computed(() => !!props.service);
const loading = ref(false);
const submitting = ref(false);
const exitAfterSave = ref(true); // Default: Salva ed Esci
const showActivityModal = ref(false);
const showTransactionModal = ref(false);
const showTaskModal = ref(false);

// Current user and companies
const currentUser = ref(null);
const companies = ref([]);

// Lists
const committenti = ref([]);
const intermediari = ref([]);
const fornitori = ref([]);
const vehicles = ref([]);
const drivers = ref([]);
const dressCodes = ref([]);
const serviceStatuses = ref([]);
const serviceTypes = ref([]);
const activityTypes = ref([]);

// Countries list with flags for nationality selector
const countries = ref([
    { code: 'IT', name: 'Italia', flag: '🇮🇹' },
    { code: 'US', name: 'Stati Uniti', flag: '🇺🇸' },
    { code: 'GB', name: 'Regno Unito', flag: '🇬🇧' },
    { code: 'DE', name: 'Germania', flag: '🇩🇪' },
    { code: 'FR', name: 'Francia', flag: '🇫🇷' },
    { code: 'ES', name: 'Spagna', flag: '🇪🇸' },
    { code: 'PT', name: 'Portogallo', flag: '🇵🇹' },
    { code: 'NL', name: 'Paesi Bassi', flag: '🇳🇱' },
    { code: 'BE', name: 'Belgio', flag: '🇧🇪' },
    { code: 'CH', name: 'Svizzera', flag: '🇨🇭' },
    { code: 'AT', name: 'Austria', flag: '🇦🇹' },
    { code: 'PL', name: 'Polonia', flag: '🇵🇱' },
    { code: 'SE', name: 'Svezia', flag: '🇸🇪' },
    { code: 'NO', name: 'Norvegia', flag: '🇳🇴' },
    { code: 'DK', name: 'Danimarca', flag: '🇩🇰' },
    { code: 'FI', name: 'Finlandia', flag: '🇫🇮' },
    { code: 'IE', name: 'Irlanda', flag: '🇮🇪' },
    { code: 'GR', name: 'Grecia', flag: '🇬🇷' },
    { code: 'CZ', name: 'Repubblica Ceca', flag: '🇨🇿' },
    { code: 'HU', name: 'Ungheria', flag: '🇭🇺' },
    { code: 'RO', name: 'Romania', flag: '🇷🇴' },
    { code: 'BG', name: 'Bulgaria', flag: '🇧🇬' },
    { code: 'HR', name: 'Croazia', flag: '🇭🇷' },
    { code: 'SK', name: 'Slovacchia', flag: '🇸🇰' },
    { code: 'SI', name: 'Slovenia', flag: '🇸🇮' },
    { code: 'LT', name: 'Lituania', flag: '🇱🇹' },
    { code: 'LV', name: 'Lettonia', flag: '🇱🇻' },
    { code: 'EE', name: 'Estonia', flag: '🇪🇪' },
    { code: 'JP', name: 'Giappone', flag: '🇯🇵' },
    { code: 'CN', name: 'Cina', flag: '🇨🇳' },
    { code: 'KR', name: 'Corea del Sud', flag: '🇰🇷' },
    { code: 'IN', name: 'India', flag: '🇮🇳' },
    { code: 'BR', name: 'Brasile', flag: '🇧🇷' },
    { code: 'AR', name: 'Argentina', flag: '🇦🇷' },
    { code: 'MX', name: 'Messico', flag: '🇲🇽' },
    { code: 'CA', name: 'Canada', flag: '🇨🇦' },
    { code: 'AU', name: 'Australia', flag: '🇦🇺' },
    { code: 'NZ', name: 'Nuova Zelanda', flag: '🇳🇿' },
    { code: 'ZA', name: 'Sudafrica', flag: '🇿🇦' },
    { code: 'RU', name: 'Russia', flag: '🇷🇺' },
    { code: 'TR', name: 'Turchia', flag: '🇹🇷' },
    { code: 'SA', name: 'Arabia Saudita', flag: '🇸🇦' },
    { code: 'AE', name: 'Emirati Arabi Uniti', flag: '🇦🇪' },
    { code: 'IL', name: 'Israele', flag: '🇮🇱' },
    { code: 'EG', name: 'Egitto', flag: '🇪🇬' },
    { code: 'MA', name: 'Marocco', flag: '🇲🇦' },
    { code: 'NG', name: 'Nigeria', flag: '🇳🇬' },
    { code: 'KE', name: 'Kenya', flag: '🇰🇪' },
    { code: 'TH', name: 'Tailandia', flag: '🇹🇭' },
    { code: 'SG', name: 'Singapore', flag: '🇸🇬' },
    { code: 'MY', name: 'Malesia', flag: '🇲🇾' },
    { code: 'ID', name: 'Indonesia', flag: '🇮🇩' },
    { code: 'PH', name: 'Filippine', flag: '🇵🇭' },
    { code: 'VN', name: 'Vietnam', flag: '🇻🇳' },
]);

// Tasks
const serviceTasks = ref([]);
const taskAssignableUsers = ref([]);
const taskSaving = ref(false);
const taskErrors = ref([]);
const activityConfirmationEnabled = ref(false);
const accountingEnabled = ref(false);

// Combined list for counterparts in transactions
const allCounterparts = computed(() => {
    return [...committenti.value, ...intermediari.value, ...fornitori.value];
});

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
});

// Computed properties for driver selection
const selectedDrivers = computed(() => {
    return drivers.value.filter(driver => form.value.driver_ids.includes(driver.id));
});

const availableDrivers = computed(() => {
    return drivers.value.filter(driver => !form.value.driver_ids.includes(driver.id));
});

// Methods for driver management
const addDriver = (event) => {
    const driverId = parseInt(event.target.value);
    if (driverId && !form.value.driver_ids.includes(driverId)) {
        form.value.driver_ids.push(driverId);
    }
    // Reset select
    event.target.value = '';
};

const removeDriver = (driverId) => {
    const index = form.value.driver_ids.indexOf(driverId);
    if (index > -1) {
        form.value.driver_ids.splice(index, 1);
    }
};

// Form
const form = ref({
    company_id: '',
    reference_number: '',
    service_type: '',
    passenger_count: 1,
    contact_name: '',
    contact_phone: '',
    passengers: [{
        surname: '',
        name: '',
        phone: '',
        email: '',
        nationality: '',
        origin: '',
        carrier_reference: ''
    }],
    client_id: '',
    intermediary_id: '',
    supplier_id: '',
    vehicle_id: '',
    vehicle_not_replaceable: false,
    driver_ids: [],
    external_driver_name: '',
    external_driver_phone: '',
    driver_not_replaceable: false,
    dress_code_id: '',
    large_luggage: 0,
    medium_luggage: 0,
    small_luggage: 0,
    baby_seat_infant: 0,
    baby_seat_standard: 0,
    baby_seat_booster: 0,
    pickup_datetime: '',
    pickup_location: '',
    pickup_address: '',
    pickup_latitude: '',
    pickup_longitude: '',
    vehicle_departure_datetime: '',
    dropoff_datetime: '',
    dropoff_location: '',
    dropoff_address: '',
    dropoff_latitude: '',
    dropoff_longitude: '',
    vehicle_return_datetime: '',
    activities: [],
    status_id: '',
    service_price: null,
    vat_rate: 10,
    card_fees_percentage: 5,
    deposit_percentage: 30,
    deposit_amount: null,
    balance_taxable: null,
    balance_handling_fees: null,
    balance_card_fees: null,
    accounting_transactions: [],
    notes: ''
});

// Activity Form
const activityForm = ref({
    id: null,
    name: '',
    activity_type_id: '',
    supplier_id: '',
    start_time: '',
    end_time: '',
    cost: 0,
    payment_type: '',
    description: ''
});

// Transaction Form
const transactionForm = ref({
    id: null,
    transaction_date: '',
    amount: 0,
    transaction_type: '',
    installment: '',
    accounting_entry_id: '',
    counterpart_id: '',
    document_number: '',
    document_due_date: '',
    payment_date: '',
    payment_type: '',
    payment_reason: '',
    iban: '',
    status: '',
    notes: ''
});

// Selection and inline editing
const selectedTransactions = ref([]);
const editingTransactionPrice = ref(null);
const editingPriceValue = ref(null);
const editingTransactionStatus = ref(null);
const editingStatusValue = ref('');
const priceInputRefs = ref({});
const statusInputRefs = ref({});

// Additional lists for transaction modal
const accountingEntries = ref([]);
const paymentTypes = ref([]);
const settings = ref(null);

// Task Form
const taskForm = ref({
    id: null,
    name: '',
    service_id: '',
    due_date: '',
    assigned_users: [],
    status: 'to_complete',
    notes: ''
});

// Computed
const selectedClientContact = computed(() => {
    if (!form.value.client_id) return '';
    const client = committenti.value.find(c => c.id === form.value.client_id);
    return client ? `${client.email || ''} ${client.phone || ''}`.trim() : '';
});

const selectedIntermediaryContact = computed(() => {
    if (!form.value.intermediary_id) return '';
    const intermediary = intermediari.value.find(i => i.id === form.value.intermediary_id);
    return intermediary ? `${intermediary.email || ''} ${intermediary.phone || ''}`.trim() : '';
});

const selectedSupplierContact = computed(() => {
    if (!form.value.supplier_id) return '';
    const supplier = fornitori.value.find(s => s.id === form.value.supplier_id);
    return supplier ? `${supplier.email || ''} ${supplier.phone || ''}`.trim() : '';
});

const accountingSummary = computed(() => {
    if (!form.value.accounting_transactions || form.value.accounting_transactions.length === 0) {
        return { total: 0, sales: 0, purchases: 0, intermediations: 0, supplierRefunds: 0, customerRefunds: 0 };
    }

    let sales = 0, purchases = 0, intermediations = 0, supplierRefunds = 0, customerRefunds = 0;

    form.value.accounting_transactions.forEach(t => {
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

const isAllTransactionsSelected = computed(() => {
    return form.value.accounting_transactions && form.value.accounting_transactions.length > 0 &&
           selectedTransactions.value.length === form.value.accounting_transactions.length;
});

const filteredCounterparts = computed(() => {
    if (!transactionForm.value.transaction_type) {
        return allCounterparts.value;
    }

    const typeMap = {
        purchase: 'fornitore',
        sale: 'committente',
        intermediation: 'intermediario',
    };

    const targetType = typeMap[transactionForm.value.transaction_type];

    // Filter based on user type flags
    if (targetType === 'fornitore') {
        return fornitori.value;
    } else if (targetType === 'committente') {
        return committenti.value;
    } else if (targetType === 'intermediario') {
        return intermediari.value;
    }

    return allCounterparts.value;
});

// Check if Contabilizza button should be enabled
const canContabilizza = computed(() => {
    // Button is enabled only if deposit_amount OR balance_taxable have non-zero/non-null values
    const hasDeposit = form.value.deposit_amount && form.value.deposit_amount > 0;
    const hasBalance = form.value.balance_taxable && form.value.balance_taxable > 0;
    return hasDeposit || hasBalance;
});

// Methods
const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;

        // Se non è super-admin, imposta company_id dal proprio utente
        if (!isSuperAdmin.value && !form.value.company_id) {
            form.value.company_id = currentUser.value.company_id;
        }
    } catch (error) {
        console.error('Error loading current user:', error);
    }
};

const loadCompanies = async () => {
    if (!isSuperAdmin.value) return;

    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading companies:', error);
    }
};

const loadCounterparts = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/users', {
            params: {
                role: 'collaboratore',
                per_page: 1000,
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        const users = response.data.data || [];

        // Filter by type
        committenti.value = users.filter(u => u.client_profile?.is_committente);
        intermediari.value = users.filter(u => u.is_intermediario);
        fornitori.value = users.filter(u => u.client_profile?.is_fornitore);
    } catch (error) {
        console.error('Error loading counterparts:', error);
    }
};

const loadVehicles = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/vehicles', {
            params: {
                per_page: 1000,
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        vehicles.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading vehicles:', error);
    }
};

const loadDrivers = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/users', {
            params: {
                role: 'driver',
                per_page: 1000,
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        drivers.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading drivers:', error);
    }
};

const loadDressCodes = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/dress-codes', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        dressCodes.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading dress codes:', error);
    }
};

const loadServiceStatuses = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/service-statuses', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        serviceStatuses.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading service statuses:', error);
    }
};

const loadServiceTypes = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/service-types', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        serviceTypes.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading service types:', error);
    }
};

const loadAccountingEntries = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/accounting-entries', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        accountingEntries.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading accounting entries:', error);
    }
};

const loadSettings = async () => {
    if (!form.value.company_id) return;

    try {
        const params = isSuperAdmin.value ? { company_id: form.value.company_id } : {};
        const response = await axios.get('/api/settings', { params });
        settings.value = response.data.data;

        // Imposta i valori di default solo se stiamo creando un nuovo servizio (non in edit mode)
        if (!isEdit.value) {
            if (settings.value.deposit_percentage !== null && settings.value.deposit_percentage !== undefined) {
                form.value.deposit_percentage = settings.value.deposit_percentage;
            }
            if (settings.value.card_fees_percentage !== null && settings.value.card_fees_percentage !== undefined) {
                form.value.card_fees_percentage = settings.value.card_fees_percentage;
            }
        }
    } catch (error) {
        console.error('Error loading settings:', error);
    }
};

const onCompanyChange = async () => {
    // Reset form fields che dipendono dall'azienda
    form.value.client_id = '';
    form.value.intermediary_id = '';
    form.value.supplier_id = '';
    form.value.vehicle_id = '';
    form.value.driver_ids = [];
    form.value.dress_code_id = '';
    form.value.status_id = '';
    form.value.service_type = '';

    // Reset task assignable users
    taskAssignableUsers.value = [];

    // Ricarica tutti i dati dipendenti dall'azienda
    await Promise.all([
        loadCounterparts(),
        loadVehicles(),
        loadDrivers(),
        loadDressCodes(),
        loadServiceStatuses(),
        loadServiceTypes(),
        loadActivityTypes(),
        loadAccountingEntries(),
        loadPaymentTypes(),
        loadSettings(),
        loadTaskAssignableUsers()
    ]);
};

const loadPaymentTypes = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/payment-types', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        paymentTypes.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading payment types:', error);
    }
};

const addPassenger = () => {
    form.value.passengers.push({
        surname: '',
        name: '',
        phone: '',
        email: '',
        nationality: '',
        origin: '',
        carrier_reference: ''
    });
};

const removePassenger = (index) => {
    form.value.passengers.splice(index, 1);
};

const removeActivity = async (activityId) => {
    if (!confirm('Sei sicuro di voler rimuovere questa esperienza?')) {
        return;
    }

    try {
        // Delete from backend if it has an ID
        if (activityId) {
            await axios.delete(`/api/activities/${activityId}`);
        }
        // Remove from local array
        form.value.activities = form.value.activities.filter(a => a.id !== activityId);
        alert('Esperienza rimossa con successo');
    } catch (error) {
        console.error('Error removing activity:', error);
        alert('Errore durante la rimozione dell\'esperienza');
    }
};

// Calcola Corrispettivi
const calculateTotals = () => {
    // Validazione campi richiesti
    if (!form.value.service_price || form.value.service_price <= 0) {
        alert('Inserisci un Prezzo Imponibile Totale valido');
        return;
    }

    if (!form.value.vat_rate) {
        alert('Seleziona l\'Aliquota IVA');
        return;
    }

    if (!form.value.card_fees_percentage && form.value.card_fees_percentage !== 0) {
        alert('Inserisci il Card Fees %');
        return;
    }

    if (!form.value.deposit_percentage && form.value.deposit_percentage !== 0) {
        alert('Inserisci l\'Acconto %');
        return;
    }

    const imponibile = parseFloat(form.value.service_price);
    const vatRate = parseFloat(form.value.vat_rate);
    const cardFeesPerc = parseFloat(form.value.card_fees_percentage);
    const depositPerc = parseFloat(form.value.deposit_percentage);

    // Calcolo del prezzo con IVA
    const prezzoConIva = imponibile * (100 + vatRate) / 100;

    // Calcolo del prezzo con IVA e Card Fees
    const prezzoConIvaECardFees = prezzoConIva * (100 + cardFeesPerc) / 100;

    // 1. Acconto € = (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (Acconto% / 100)
    form.value.deposit_amount = parseFloat((prezzoConIvaECardFees * (depositPerc / 100)).toFixed(2));

    // 2. Saldo Imponibile = Imponibile × (100 - Acconto%) / 100
    form.value.balance_taxable = parseFloat((imponibile * (100 - depositPerc) / 100).toFixed(2));

    // 3. Saldo Handling Fees = (Imponibile × (100 + IVA%) / 100) × (100 - Acconto%) / 100
    form.value.balance_handling_fees = parseFloat((prezzoConIva * (100 - depositPerc) / 100).toFixed(2));

    // 4. Saldo Card Fees = (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (100 - Acconto%) / 100
    form.value.balance_card_fees = parseFloat((prezzoConIvaECardFees * (100 - depositPerc) / 100).toFixed(2));
};

const contabilizza = async () => {
    if (!props.service || !props.service.id) {
        alert('Salva il servizio prima di contabilizzare');
        return;
    }

    if (!form.value.client_id) {
        alert('Seleziona un committente prima di contabilizzare');
        return;
    }

    if (!settings.value) {
        alert('Impossibile recuperare le impostazioni aziendali');
        return;
    }

    try {
        const today = moment().format('YYYY-MM-DD');
        const clientId = form.value.client_id;

        // A & B: Handle Acconto (deposit)
        if (form.value.deposit_amount && form.value.deposit_amount > 0) {
            // Check if acconto transaction exists
            const existingAcconto = form.value.accounting_transactions.find(
                t => t.transaction_type === 'sale' && t.installment === 'deposit'
            );

            const accontoPayload = {
                service_id: props.service.id,
                transaction_date: today,
                amount: form.value.deposit_amount,
                transaction_type: 'sale',
                installment: 'deposit',
                accounting_entry_id: settings.value.deposit_accounting_entry_id,
                counterpart_id: clientId,
                document_number: null,
                document_due_date: null,
                payment_date: null,
                payment_type: 'carta_di_credito',
                payment_reason: settings.value.deposit_reason,
                iban: null,
                status: 'to_collect',
                notes: null
            };

            if (existingAcconto) {
                // Update existing acconto
                const response = await axios.put(`/api/accounting-transactions/${existingAcconto.id}`, accontoPayload);
                const index = form.value.accounting_transactions.findIndex(t => t.id === existingAcconto.id);
                if (index !== -1) {
                    form.value.accounting_transactions[index] = response.data.data;
                }
            } else {
                // Create new acconto
                const response = await axios.post('/api/accounting-transactions', accontoPayload);
                form.value.accounting_transactions.push(response.data.data);
            }
        }

        // C & D: Handle Saldo (balance)
        if (form.value.balance_taxable && form.value.balance_taxable > 0) {
            // Check if saldo transaction exists
            const existingSaldo = form.value.accounting_transactions.find(
                t => t.transaction_type === 'sale' && t.installment === 'balance'
            );

            const saldoPayload = {
                service_id: props.service.id,
                transaction_date: today,
                amount: form.value.balance_taxable,
                transaction_type: 'sale',
                installment: 'balance',
                accounting_entry_id: settings.value.balance_accounting_entry_id,
                counterpart_id: clientId,
                document_number: null,
                document_due_date: null,
                payment_date: null,
                payment_type: 'contanti',
                payment_reason: settings.value.balance_reason,
                iban: null,
                status: 'to_collect',
                notes: null
            };

            if (existingSaldo) {
                // Update existing saldo
                const response = await axios.put(`/api/accounting-transactions/${existingSaldo.id}`, saldoPayload);
                const index = form.value.accounting_transactions.findIndex(t => t.id === existingSaldo.id);
                if (index !== -1) {
                    form.value.accounting_transactions[index] = response.data.data;
                }
            } else {
                // Create new saldo
                const response = await axios.post('/api/accounting-transactions', saldoPayload);
                form.value.accounting_transactions.push(response.data.data);
            }
        }

        alert('Contabilizzazione completata con successo');
    } catch (error) {
        console.error('Error during contabilizza:', error);
        alert('Errore durante la contabilizzazione');
    }
};

const openActivityModal = (activity = null) => {
    if (activity) {
        // Edit mode
        activityForm.value = {
            id: activity.id,
            name: activity.name,
            activity_type_id: activity.activity_type_id || '',
            supplier_id: activity.supplier_id || '',
            start_time: activity.start_time ? moment(activity.start_time).format('YYYY-MM-DDTHH:mm') : '',
            end_time: activity.end_time ? moment(activity.end_time).format('YYYY-MM-DDTHH:mm') : '',
            cost: activity.cost || 0,
            cost_per_person: activity.cost_per_person || 0,
            payment_type: activity.payment_type || '',
            notes: activity.notes || ''
        };
    } else {
        // Create mode - reset form with default dates from service
        activityForm.value = {
            id: null,
            name: '',
            activity_type_id: '',
            supplier_id: '',
            start_time: form.value.pickup_datetime || '',
            end_time: form.value.pickup_datetime || '',
            cost: 0,
            cost_per_person: 0,
            payment_type: '',
            notes: ''
        };
    }
    showActivityModal.value = true;
};

const closeActivityModal = () => {
    showActivityModal.value = false;
};

const cancelActivityEdit = () => {
    activityForm.value = {
        id: null,
        name: '',
        activity_type_id: '',
        supplier_id: '',
        start_time: '',
        end_time: '',
        cost: 0,
        cost_per_person: 0,
        payment_type: '',
        notes: ''
    };
    showActivityModal.value = false;
};

const saveActivity = async () => {
    if (!activityForm.value.name || !activityForm.value.start_time || !activityForm.value.end_time) {
        alert('Compila tutti i campi obbligatori');
        return;
    }

    try {
        const payload = {
            company_id: form.value.company_id,
            service_id: props.service.id,
            name: activityForm.value.name,
            activity_type_id: activityForm.value.activity_type_id || null,
            supplier_id: activityForm.value.supplier_id || null,
            start_time: activityForm.value.start_time,
            end_time: activityForm.value.end_time,
            cost: activityForm.value.cost || 0,
            cost_per_person: activityForm.value.cost_per_person || 0,
            payment_type: activityForm.value.payment_type || null,
            notes: activityForm.value.notes || null
        };

        let response;
        if (activityForm.value.id) {
            // Update existing activity
            response = await axios.put(`/api/activities/${activityForm.value.id}`, payload);
            // Update in local array
            const index = form.value.activities.findIndex(a => a.id === activityForm.value.id);
            if (index !== -1) {
                form.value.activities[index] = response.data.data;
            }
        } else {
            // Create new activity
            response = await axios.post('/api/activities', payload);
            // Add to local array
            form.value.activities.push(response.data.data);
        }

        alert(activityForm.value.id ? 'Esperienza aggiornata con successo' : 'Esperienza aggiunta con successo');
        closeActivityModal();
        cancelActivityEdit();
    } catch (error) {
        console.error('Error saving activity:', error);
        alert('Errore durante il salvataggio dell\'esperienza');
    }
};

const loadActivityTypes = async () => {
    if (!form.value.company_id) return;

    try {
        const response = await axios.get('/api/dictionaries/activity-types', {
            params: {
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            }
        });
        activityTypes.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading activity types:', error);
    }
};

// Transaction functions
const openTransactionModal = (transaction = null) => {
    if (transaction) {
        // Edit mode
        transactionForm.value = {
            id: transaction.id,
            transaction_date: transaction.transaction_date ? moment(transaction.transaction_date).format('YYYY-MM-DD') : '',
            amount: transaction.amount || 0,
            transaction_type: transaction.transaction_type || '',
            installment: transaction.installment || '',
            accounting_entry_id: transaction.accounting_entry_id || '',
            counterpart_id: transaction.counterpart_id || '',
            document_number: transaction.document_number || '',
            document_due_date: transaction.document_due_date ? moment(transaction.document_due_date).format('YYYY-MM-DD') : '',
            payment_date: transaction.payment_date ? moment(transaction.payment_date).format('YYYY-MM-DD') : '',
            payment_type: transaction.payment_type || '',
            payment_reason: transaction.payment_reason || '',
            iban: transaction.iban || '',
            status: transaction.status || '',
            notes: transaction.notes || ''
        };
    } else {
        // Create mode - reset form
        transactionForm.value = {
            id: null,
            transaction_date: moment().format('YYYY-MM-DD'),
            amount: 0,
            transaction_type: '',
            installment: '',
            accounting_entry_id: '',
            counterpart_id: '',
            document_number: '',
            document_due_date: '',
            payment_date: '',
            payment_type: '',
            payment_reason: '',
            iban: '',
            status: '',
            notes: ''
        };
    }
    showTransactionModal.value = true;
};

const closeTransactionModal = () => {
    showTransactionModal.value = false;
};

const cancelTransactionEdit = () => {
    transactionForm.value = {
        id: null,
        transaction_date: '',
        amount: 0,
        transaction_type: '',
        installment: '',
        accounting_entry_id: '',
        counterpart_id: '',
        document_number: '',
        document_due_date: '',
        payment_date: '',
        payment_type: '',
        payment_reason: '',
        iban: '',
        status: '',
        notes: ''
    };
    showTransactionModal.value = false;
};

const onTransactionTypeChange = () => {
    // Reset counterpart when transaction type changes
    transactionForm.value.counterpart_id = '';
    // Reset status when transaction type changes
    transactionForm.value.status = '';
};

const saveTransaction = async () => {
    if (!transactionForm.value.transaction_date || !transactionForm.value.amount || !transactionForm.value.transaction_type || !transactionForm.value.installment || !transactionForm.value.status) {
        alert('Compila tutti i campi obbligatori');
        return;
    }

    try {
        const payload = {
            service_id: props.service.id,
            transaction_date: transactionForm.value.transaction_date,
            amount: transactionForm.value.amount,
            transaction_type: transactionForm.value.transaction_type,
            installment: transactionForm.value.installment,
            accounting_entry_id: transactionForm.value.accounting_entry_id || null,
            counterpart_id: transactionForm.value.counterpart_id || null,
            document_number: transactionForm.value.document_number || null,
            document_due_date: transactionForm.value.document_due_date || null,
            payment_date: transactionForm.value.payment_date || null,
            payment_type: transactionForm.value.payment_type || null,
            payment_reason: transactionForm.value.payment_reason || null,
            iban: transactionForm.value.iban || null,
            status: transactionForm.value.status,
            notes: transactionForm.value.notes || null
        };

        let response;
        if (transactionForm.value.id) {
            // Update existing transaction
            response = await axios.put(`/api/accounting-transactions/${transactionForm.value.id}`, payload);
            // Update in local array
            const index = form.value.accounting_transactions.findIndex(t => t.id === transactionForm.value.id);
            if (index !== -1) {
                form.value.accounting_transactions[index] = response.data.data;
            }
        } else {
            // Create new transaction
            response = await axios.post('/api/accounting-transactions', payload);
            // Add to local array
            form.value.accounting_transactions.push(response.data.data);
        }

        alert(transactionForm.value.id ? 'Movimento aggiornato con successo' : 'Movimento aggiunto con successo');
        closeTransactionModal();
        cancelTransactionEdit();
    } catch (error) {
        console.error('Error saving transaction:', error);
        alert('Errore durante il salvataggio del movimento');
    }
};

const removeTransaction = async (transactionId) => {
    if (!confirm('Sei sicuro di voler rimuovere questo movimento contabile?')) {
        return;
    }

    try {
        // Delete from backend if it has an ID
        if (transactionId) {
            await axios.delete(`/api/accounting-transactions/${transactionId}`);
        }
        // Remove from local array
        form.value.accounting_transactions = form.value.accounting_transactions.filter(t => t.id !== transactionId);
        alert('Movimento rimosso con successo');
    } catch (error) {
        console.error('Error removing transaction:', error);
        alert('Errore durante la rimozione del movimento');
    }
};

// Selection functions
const toggleSelectAllTransactions = () => {
    if (isAllTransactionsSelected.value) {
        selectedTransactions.value = [];
    } else {
        selectedTransactions.value = form.value.accounting_transactions.map(t => t.id);
    }
};

const deleteSelectedTransactions = async () => {
    if (selectedTransactions.value.length === 0) return;

    const count = selectedTransactions.value.length;
    if (confirm(`Sei sicuro di voler eliminare ${count} movimenti selezionati?`)) {
        try {
            // Delete all selected transactions
            await Promise.all(
                selectedTransactions.value.map(id =>
                    axios.delete(`/api/accounting-transactions/${id}`)
                )
            );

            // Remove from local array
            form.value.accounting_transactions = form.value.accounting_transactions.filter(
                t => !selectedTransactions.value.includes(t.id)
            );

            selectedTransactions.value = [];
            alert(`${count} movimenti eliminati con successo`);
        } catch (error) {
            console.error('Error deleting transactions:', error);
            alert('Errore durante l\'eliminazione dei movimenti selezionati');
        }
    }
};

// Inline price editing functions
const startEditTransactionPrice = (transaction) => {
    editingTransactionPrice.value = transaction.id;
    editingPriceValue.value = parseFloat(transaction.amount);
    // Focus input in next tick using dynamic ref
    nextTick(() => {
        const input = priceInputRefs.value[transaction.id];
        if (input) {
            input.focus();
            input.select();
        }
    });
};

const saveTransactionPrice = async (transaction) => {
    // Prevent duplicate saves
    if (!editingTransactionPrice.value || editingTransactionPrice.value !== transaction.id) {
        return;
    }

    if (editingPriceValue.value === null || editingPriceValue.value < 0) {
        alert('Inserisci un importo valido');
        return;
    }

    try {
        const payload = {
            service_id: props.service.id,
            transaction_date: transaction.transaction_date,
            amount: editingPriceValue.value,
            transaction_type: transaction.transaction_type,
            installment: transaction.installment,
            accounting_entry_id: transaction.accounting_entry_id || null,
            counterpart_id: transaction.counterpart_id || null,
            document_number: transaction.document_number || null,
            document_due_date: transaction.document_due_date || null,
            payment_date: transaction.payment_date || null,
            payment_type: transaction.payment_type || null,
            payment_reason: transaction.payment_reason || null,
            iban: transaction.iban || null,
            status: transaction.status,
            notes: transaction.notes || null
        };

        const response = await axios.put(`/api/accounting-transactions/${transaction.id}`, payload);

        // Update in local array with the full response data
        const index = form.value.accounting_transactions.findIndex(t => t.id === transaction.id);
        if (index !== -1) {
            form.value.accounting_transactions[index] = response.data.data;
        }

        // Clear editing state
        editingTransactionPrice.value = null;
        editingPriceValue.value = null;
    } catch (error) {
        console.error('Error updating transaction price:', error);
        if (error.response && error.response.status === 422) {
            if (error.response.data.errors) {
                const errorMessages = Object.entries(error.response.data.errors)
                    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                    .join('\n');
                alert('Errori di validazione:\n\n' + errorMessages);
            } else {
                alert('Errore di validazione durante l\'aggiornamento del prezzo');
            }
        } else {
            alert('Errore durante l\'aggiornamento del prezzo');
        }
    }
};

const cancelEditTransactionPrice = () => {
    editingTransactionPrice.value = null;
    editingPriceValue.value = null;
};

// Inline status editing functions
const startEditTransactionStatus = (transaction) => {
    editingTransactionStatus.value = transaction.id;
    editingStatusValue.value = transaction.status;
    // Focus select in next tick using dynamic ref
    nextTick(() => {
        const select = statusInputRefs.value[transaction.id];
        if (select) {
            select.focus();
        }
    });
};

const saveTransactionStatus = async (transaction) => {
    if (!editingStatusValue.value) {
        alert('Seleziona uno stato valido');
        return;
    }

    try {
        const payload = {
            service_id: props.service.id,
            transaction_date: transaction.transaction_date,
            amount: transaction.amount,
            transaction_type: transaction.transaction_type,
            installment: transaction.installment,
            accounting_entry_id: transaction.accounting_entry_id || null,
            counterpart_id: transaction.counterpart_id || null,
            document_number: transaction.document_number || null,
            document_due_date: transaction.document_due_date || null,
            payment_date: transaction.payment_date || null,
            payment_type: transaction.payment_type || null,
            payment_reason: transaction.payment_reason || null,
            iban: transaction.iban || null,
            status: editingStatusValue.value,
            notes: transaction.notes || null
        };

        const response = await axios.put(`/api/accounting-transactions/${transaction.id}`, payload);

        // Update in local array
        const index = form.value.accounting_transactions.findIndex(t => t.id === transaction.id);
        if (index !== -1) {
            form.value.accounting_transactions[index] = response.data.data;
        }

        editingTransactionStatus.value = null;
        editingStatusValue.value = '';
    } catch (error) {
        console.error('Error updating transaction status:', error);
        alert('Errore durante l\'aggiornamento dello stato');
    }
};

const cancelEditTransactionStatus = () => {
    editingTransactionStatus.value = null;
    editingStatusValue.value = '';
};

const getInstallmentLabel = (installment) => {
    const labels = {
        deposit: 'Acconto',
        balance: 'Saldo',
        supplier_refund: 'Reso Fornitore',
        customer_refund: 'Rimborso Cliente'
    };
    return labels[installment] || installment;
};

const formatDateTime = (datetime) => {
    return datetime ? moment(datetime).format('DD/MM/YYYY HH:mm') : '-';
};

const getPaymentTypeBadge = (type) => {
    const classes = {
        'INCLUSO': 'bg-success-subtle text-success',
        'CLIENTE': 'bg-primary-subtle text-primary',
        'AGENZIA': 'bg-warning-subtle text-warning',
        'NESSUNO': 'bg-secondary-subtle text-secondary'
    };
    return classes[type] || 'bg-secondary-subtle text-secondary';
};

const onClientChange = () => {
    // Auto-fill based on client selection if needed
};

const onIntermediaryChange = () => {
    // Auto-fill based on intermediary selection if needed
};

const onSupplierChange = () => {
    // Auto-fill based on supplier selection if needed
};

const formatDate = (date) => {
    return date ? moment(date).format('DD/MM/YYYY') : '-';
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('it-IT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Math.abs(amount || 0));
};

const getActivityTypeName = (activityTypeId) => {
    if (!activityTypeId) return null;
    const activityType = activityTypes.value.find(t => t.id === activityTypeId);
    return activityType ? activityType.name : null;
};

const getTransactionTypeLabel = (type) => {
    const labels = { purchase: 'Acquisto', sale: 'Vendita', intermediation: 'Intermediazione' };
    return labels[type] || type;
};

const getTransactionTypeBadge = (type) => {
    const classes = {
        purchase: 'badge bg-danger-subtle text-danger',
        sale: 'badge bg-success-subtle text-success',
        intermediation: 'badge bg-warning-subtle text-warning'
    };
    return classes[type] || 'badge bg-secondary-subtle text-secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        to_pay: 'Da Pagare',
        paid: 'Pagato',
        to_collect: 'Da Incassare',
        collected: 'Incassato',
        suspended: 'Sospeso',
        cancelled: 'Annullato'
    };
    return labels[status] || status;
};

const getStatusBadge = (status) => {
    const classes = {
        to_pay: 'badge bg-warning-subtle text-warning',
        paid: 'badge bg-success-subtle text-success',
        to_collect: 'badge bg-info-subtle text-info',
        collected: 'badge bg-success-subtle text-success',
        suspended: 'badge bg-secondary-subtle text-secondary',
        cancelled: 'badge bg-danger-subtle text-danger'
    };
    return classes[status] || 'badge bg-secondary-subtle text-secondary';
};

const getTransactionTypeAbbr = (type) => {
    const abbrs = {
        purchase: 'ACQ',
        sale: 'VEN',
        intermediation: 'INT'
    };
    return abbrs[type] || type;
};

const getInstallmentAbbr = (installment) => {
    const abbrs = {
        deposit: 'ACC',
        balance: 'SAL',
        supplier_refund: 'RES',
        customer_refund: 'RIM'
    };
    return abbrs[installment] || installment;
};

const getStatusAbbr = (status) => {
    const abbrs = {
        to_pay: 'DP',
        paid: 'PAG',
        to_collect: 'DI',
        collected: 'INC',
        suspended: 'SOS',
        cancelled: 'ANN'
    };
    return abbrs[status] || status;
};

const getDueDateClass = (transaction) => {
    if (!transaction.document_due_date) return 'text-muted';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(transaction.document_due_date);
    dueDate.setHours(0, 0, 0, 0);

    // Check if paid or collected
    if (transaction.status === 'paid' || transaction.status === 'collected') {
        return 'text-success';
    }

    // Check if overdue
    if (dueDate < today) {
        return 'text-danger fw-bold';
    }

    // Check if due soon (within 7 days)
    const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 7) {
        return 'text-warning fw-bold';
    }

    return 'text-muted';
};

// Tasks Functions
const sortedServiceTasks = computed(() => {
    if (!serviceTasks.value || !serviceTasks.value.length) return [];

    return [...serviceTasks.value].sort((a, b) => {
        // Sort by due_date ascending (tasks with no date go to end)
        if (!a.due_date && !b.due_date) return 0;
        if (!a.due_date) return 1;
        if (!b.due_date) return -1;
        return new Date(a.due_date) - new Date(b.due_date);
    });
});

const getTaskDueDateClass = (task) => {
    if (!task.due_date) return '';
    if (task.status === 'completed' || task.status === 'cancelled') return '';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(task.due_date);
    dueDate.setHours(0, 0, 0, 0);

    if (dueDate < today) {
        return 'text-danger fw-bold';
    }
    const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 3) {
        return 'text-warning fw-bold';
    }
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
    const labels = {
        'to_complete': 'Da Completare',
        'completed': 'Completato',
        'cancelled': 'Annullato'
    };
    return labels[status] || status;
};

const loadTaskAssignableUsers = async () => {
    if (!form.value.company_id) return;

    try {
        // Load users with roles that can be assigned tasks
        const roles = ['admin', 'operator', 'driver', 'contabilita'];
        const allUsers = [];

        for (const role of roles) {
            const response = await axios.get('/api/users', {
                params: {
                    role: role,
                    per_page: 100,
                    company_id: isSuperAdmin.value ? form.value.company_id : undefined
                }
            });
            if (response.data.data) {
                allUsers.push(...response.data.data);
            }
        }

        // Remove duplicates by id
        const uniqueUsers = allUsers.filter((user, index, self) =>
            index === self.findIndex((u) => u.id === user.id)
        );

        taskAssignableUsers.value = uniqueUsers;
    } catch (error) {
        console.error('Error loading task assignable users:', error);
    }
};

const openTaskModal = async (task = null) => {
    // Load assignable users if not already loaded
    if (taskAssignableUsers.value.length === 0) {
        await loadTaskAssignableUsers();
    }

    if (task) {
        // Edit mode
        taskForm.value = {
            id: task.id,
            name: task.name,
            service_id: task.service_id || '',
            due_date: task.due_date ? moment(task.due_date).format('YYYY-MM-DD') : '',
            assigned_users: task.assigned_users ? task.assigned_users.map(u => u.id) : [],
            status: task.status || 'to_complete',
            notes: task.notes || ''
        };
    } else {
        // Create mode - pre-fill service_id
        taskForm.value = {
            id: null,
            name: '',
            service_id: isEdit.value && props.service ? props.service.id : '',
            due_date: '',
            assigned_users: [],
            status: 'to_complete',
            notes: ''
        };
    }

    taskErrors.value = [];
    showTaskModal.value = true;
};

const saveTask = async () => {
    taskSaving.value = true;
    taskErrors.value = [];

    try {
        const data = {
            name: taskForm.value.name,
            service_id: taskForm.value.service_id || null,
            due_date: taskForm.value.due_date || null,
            assigned_users: taskForm.value.assigned_users.length > 0 ? taskForm.value.assigned_users : [],
            status: taskForm.value.status,
            notes: taskForm.value.notes || null
        };

        if (taskForm.value.id) {
            // Update
            await axios.put(`/api/tasks/${taskForm.value.id}`, data);
        } else {
            // Create
            await axios.post('/api/tasks', data);
        }

        // Reload tasks
        await reloadServiceTasks();

        // Close modal
        showTaskModal.value = false;
    } catch (error) {
        console.error('Error saving task:', error);
        if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            taskErrors.value = Object.values(validationErrors).flat();
        } else {
            taskErrors.value = ['Si è verificato un errore durante il salvataggio'];
        }
    } finally {
        taskSaving.value = false;
    }
};

const cancelTaskEdit = () => {
    showTaskModal.value = false;
    taskForm.value = {
        id: null,
        name: '',
        service_id: '',
        due_date: '',
        assigned_to: '',
        status: 'to_complete',
        notes: ''
    };
    taskErrors.value = [];
};

const reloadServiceTasks = async () => {
    if (isEdit.value && props.service) {
        try {
            const response = await axios.get(`/api/services/${props.service.id}`);
            serviceTasks.value = response.data.tasks || [];
        } catch (error) {
            console.error('Error reloading tasks:', error);
        }
    }
};

const deleteTask = async (taskId) => {
    if (!confirm('Sei sicuro di voler eliminare questo task?')) {
        return;
    }

    try {
        await axios.delete(`/api/tasks/${taskId}`);
        // Reload tasks
        await reloadServiceTasks();
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('Errore durante l\'eliminazione del task');
    }
};

// Activity Confirmation Tasks Management
// Toggle only changes the state, actual creation/deletion happens on save
const toggleActivityConfirmation = () => {
    // Nothing happens here - changes will be applied on save
};

const createActivityConfirmationTasks = async () => {
    // Validate settings
    if (!settings.value || !settings.value.activity_confirmation_text || !settings.value.activity_confirmation_role) {
        console.warn('Activity confirmation settings not configured');
        return false;
    }

    // Validate activities exist
    if (!form.value.activities || form.value.activities.length === 0) {
        console.warn('No activities to create confirmation tasks for');
        return false;
    }

    try {
        // Get users with the specified role for this company
        const response = await axios.get('/api/users', {
            params: {
                role: settings.value.activity_confirmation_role,
                company_id: form.value.company_id,
                per_page: 100
            }
        });

        const assignableUsers = response.data.data || [];
        if (assignableUsers.length === 0) {
            console.warn(`No users with role "${settings.value.activity_confirmation_role}" found`);
            return false;
        }

        const userIds = assignableUsers.map(u => u.id);

        // Calculate due date (day before pickup)
        const pickupDate = moment(form.value.pickup_datetime);
        const dueDate = pickupDate.subtract(1, 'days').format('YYYY-MM-DD');

        // Create a task for each activity
        for (const activity of form.value.activities) {
            // Get supplier name
            const supplier = activity.supplier
                ? `${activity.supplier.name} ${activity.supplier.surname || ''}`.trim()
                : 'Fornitore non specificato';

            // Get service reference
            const serviceRef = form.value.reference_number || `Servizio #${props.service.id}`;

            // Replace placeholders in confirmation text
            let taskName = settings.value.activity_confirmation_text
                .replace('{$fornitore$}', supplier)
                .replace('{$servizio$}', serviceRef);

            // Create task
            await axios.post('/api/tasks', {
                company_id: form.value.company_id,
                name: taskName,
                service_id: props.service.id,
                due_date: dueDate,
                assigned_users: userIds,
                status: 'to_complete',
                notes: `Task di conferma automatico per l'esperienza: ${activity.name}`
            });
        }

        return true;
    } catch (error) {
        console.error('Error creating activity confirmation tasks:', error);
        return false;
    }
};

const removeActivityConfirmationTasks = async () => {
    try {
        // Find all tasks that are activity confirmation tasks
        // They contain the note "Task di conferma automatico per l'esperienza:"
        const confirmationTasks = serviceTasks.value.filter(task =>
            task.notes && task.notes.includes('Task di conferma automatico per l\'esperienza:')
        );

        if (confirmationTasks.length === 0) {
            console.warn('No confirmation tasks to remove');
            return false;
        }

        // Delete all confirmation tasks
        for (const task of confirmationTasks) {
            await axios.delete(`/api/tasks/${task.id}`);
        }

        return true;
    } catch (error) {
        console.error('Error removing activity confirmation tasks:', error);
        return false;
    }
};

// Process accounting transactions (create or update)
const processAccountingTransactions = async () => {
    if (!form.value.client_id) {
        console.warn('No client selected for accounting');
        return false;
    }

    if (!settings.value) {
        console.warn('Settings not available for accounting');
        return false;
    }

    try {
        const today = moment().format('YYYY-MM-DD');
        const clientId = form.value.client_id;
        const serviceId = props.service.id;

        // Handle Acconto (deposit)
        if (form.value.deposit_amount && form.value.deposit_amount > 0) {
            const existingAcconto = form.value.accounting_transactions.find(
                t => t.transaction_type === 'sale' && t.installment === 'deposit'
            );

            const accontoPayload = {
                service_id: serviceId,
                transaction_date: today,
                amount: form.value.deposit_amount,
                transaction_type: 'sale',
                installment: 'deposit',
                accounting_entry_id: settings.value.deposit_accounting_entry_id,
                counterpart_id: clientId,
                document_number: null,
                document_due_date: null,
                payment_date: null,
                payment_type: 'carta_di_credito',
                payment_reason: settings.value.deposit_reason,
                iban: null,
                status: 'to_collect',
                notes: null
            };

            if (existingAcconto) {
                // Update existing acconto
                await axios.put(`/api/accounting-transactions/${existingAcconto.id}`, accontoPayload);
            } else {
                // Create new acconto
                await axios.post('/api/accounting-transactions', accontoPayload);
            }
        }

        // Handle Saldo (balance)
        if (form.value.balance_taxable && form.value.balance_taxable > 0) {
            const existingSaldo = form.value.accounting_transactions.find(
                t => t.transaction_type === 'sale' && t.installment === 'balance'
            );

            const saldoPayload = {
                service_id: serviceId,
                transaction_date: today,
                amount: form.value.balance_taxable,
                transaction_type: 'sale',
                installment: 'balance',
                accounting_entry_id: settings.value.balance_accounting_entry_id,
                counterpart_id: clientId,
                document_number: null,
                document_due_date: null,
                payment_date: null,
                payment_type: 'contanti',
                payment_reason: settings.value.balance_reason,
                iban: null,
                status: 'to_collect',
                notes: null
            };

            if (existingSaldo) {
                // Update existing saldo
                await axios.put(`/api/accounting-transactions/${existingSaldo.id}`, saldoPayload);
            } else {
                // Create new saldo
                await axios.post('/api/accounting-transactions', saldoPayload);
            }
        }

        return true;
    } catch (error) {
        console.error('Error processing accounting transactions:', error);
        return false;
    }
};

// Remove sale accounting transactions (deposit and balance)
const removeAccountingTransactions = async () => {
    try {
        // Find sale transactions (deposit and balance)
        const saleTransactions = form.value.accounting_transactions.filter(
            t => t.transaction_type === 'sale' && (t.installment === 'deposit' || t.installment === 'balance')
        );

        if (saleTransactions.length === 0) {
            console.warn('No sale transactions to remove');
            return false;
        }

        // Delete all sale transactions
        for (const transaction of saleTransactions) {
            await axios.delete(`/api/accounting-transactions/${transaction.id}`);
        }

        return true;
    } catch (error) {
        console.error('Error removing accounting transactions:', error);
        return false;
    }
};

// Function to handle "Salva" button (save and stay)
const saveAndStay = async () => {
    exitAfterSave.value = false;
    await submitForm();
};

const submitForm = async () => {
    submitting.value = true;
    try {
        const payload = { ...form.value };
        const url = isEdit.value ? `/api/services/${props.service.id}` : '/api/services';
        const method = isEdit.value ? 'put' : 'post';

        const response = await axios[method](url, payload);
        const savedServiceId = response.data.data?.id || props.service?.id;

        // Handle activity confirmation tasks after successful save (only for edit mode)
        if (isEdit.value && props.service) {
            // Check if confirmation tasks already exist
            const hasConfirmationTasks = serviceTasks.value.some(task =>
                task.notes && task.notes.includes('Task di conferma automatico per l\'esperienza:')
            );

            if (activityConfirmationEnabled.value && !hasConfirmationTasks) {
                // Toggle is ON and no tasks exist - create them
                await createActivityConfirmationTasks();
            } else if (!activityConfirmationEnabled.value && hasConfirmationTasks) {
                // Toggle is OFF and tasks exist - delete them
                await removeActivityConfirmationTasks();
            }

            // Handle accounting transactions after successful save
            const hasSaleTransactions = form.value.accounting_transactions.some(
                t => t.transaction_type === 'sale' && (t.installment === 'deposit' || t.installment === 'balance')
            );

            if (accountingEnabled.value && !hasSaleTransactions) {
                // Toggle is ON and no sale transactions exist - create them
                await processAccountingTransactions();
            } else if (accountingEnabled.value && hasSaleTransactions) {
                // Toggle is ON and sale transactions exist - update them
                await processAccountingTransactions();
            } else if (!accountingEnabled.value && hasSaleTransactions) {
                // Toggle is OFF and sale transactions exist - delete them
                await removeAccountingTransactions();
            }
        }

        // Decide where to go based on exitAfterSave flag
        if (exitAfterSave.value) {
            // Salva ed Esci: go to services list
            router.visit(route('easyncc.services.index'));
        } else {
            // Salva: reload current page
            if (isEdit.value) {
                router.visit(route('easyncc.services.edit', savedServiceId));
            } else {
                // If creating new service, redirect to edit page
                router.visit(route('easyncc.services.edit', savedServiceId));
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);

        // Log detailed validation errors for 422 responses
        if (error.response && error.response.status === 422) {
            console.error('Validation failed!');
            console.error('Response data:', error.response.data);
            console.error('Validation errors:', error.response.data.errors);

            // Show specific validation errors
            if (error.response.data.errors) {
                const errorMessages = Object.entries(error.response.data.errors)
                    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                    .join('\n');
                alert('Errori di validazione:\n\n' + errorMessages);
            } else {
                alert('Errore durante il salvataggio del servizio (422 - Validation Error)');
            }
        } else {
            alert('Errore durante il salvataggio del servizio');
        }
    } finally {
        submitting.value = false;
        exitAfterSave.value = true; // Reset to default for next save
    }
};

onMounted(async () => {
    loading.value = true;

    // Load current user first
    await loadCurrentUser();

    // Load companies if super-admin
    if (isSuperAdmin.value) {
        await loadCompanies();
    }

    // Set company_id BEFORE loading dictionaries
    if (isEdit.value && props.service) {
        // If editing, use the service's company_id
        form.value.company_id = props.service.company_id;
    } else if (!isSuperAdmin.value && currentUser.value) {
        // If creating and not super-admin, use current user's company_id
        form.value.company_id = currentUser.value.company_id;
    }

    // Load all data (now company_id is set)
    await Promise.all([
        loadCounterparts(),
        loadVehicles(),
        loadDrivers(),
        loadDressCodes(),
        loadServiceStatuses(),
        loadServiceTypes(),
        loadActivityTypes(),
        loadAccountingEntries(),
        loadPaymentTypes(),
        loadSettings()
    ]);

    if (isEdit.value && props.service) {
        // Populate form with service data
        Object.keys(form.value).forEach(key => {
            if (props.service[key] !== undefined) {
                // Format datetime fields for datetime-local input
                if (key.includes('datetime') && props.service[key]) {
                    form.value[key] = moment(props.service[key]).format('YYYY-MM-DDTHH:mm');
                } else if (key === 'vat_rate' || key === 'card_fees_percentage' || key === 'deposit_percentage') {
                    // Convert decimal values to numbers for proper select binding
                    form.value[key] = parseFloat(props.service[key]) || 0;
                } else {
                    form.value[key] = props.service[key];
                }
            }
        });

        // Load related data
        if (props.service.passengers && props.service.passengers.length > 0) {
            form.value.passengers = props.service.passengers;
        } else {
            // Ensure passengers is always an array with at least one item
            form.value.passengers = [{
                surname: '',
                name: '',
                phone: '',
                email: '',
                nationality: '',
                origin: '',
                carrier_reference: ''
            }];
        }
        if (props.service.activities) {
            form.value.activities = props.service.activities;
        }
        if (props.service.accounting_transactions) {
            form.value.accounting_transactions = props.service.accounting_transactions;
        }
        if (props.service.drivers) {
            form.value.driver_ids = props.service.drivers.map(d => d.id);
        }
        if (props.service.tasks) {
            serviceTasks.value = props.service.tasks;
        }

        // Set default values if not present
        if (!form.value.vat_rate) {
            form.value.vat_rate = 10;
        }
        if (!form.value.card_fees_percentage) {
            form.value.card_fees_percentage = 5;
        }
        if (!form.value.deposit_percentage) {
            form.value.deposit_percentage = 30;
        }
    } else {
        // Set defaults for new service
        form.value.vat_rate = 10;
        form.value.card_fees_percentage = 5;
        form.value.deposit_percentage = 30;
    }

    loading.value = false;
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
