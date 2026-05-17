<template>
    <Head :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" />

    <Layout>
        <PageHeader :title="isEdit ? 'Modifica Servizio' : 'Nuovo Servizio'" pageTitle="Servizi" />

        <!-- Service Info Bar (edit mode only) -->
        <div v-if="isEdit && form.pickup_datetime" class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center flex-wrap gap-2">
            <span class="fw-bold text-primary" style="font-size: 0.9rem;">
                <i class="ri-calendar-event-line me-1"></i>{{ formatInfoBarDate(form.pickup_datetime) }}
            </span>
            <span v-if="form.pickup_location" class="text-dark">
                <i class="ri-map-pin-line me-1"></i>{{ form.pickup_location }}
            </span>
            <span v-else-if="form.pickup_address" class="text-muted" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <i class="ri-map-pin-line me-1"></i>{{ form.pickup_address }}
            </span>
            <span v-if="form.reference_number" class="badge bg-secondary-subtle text-secondary ms-auto" style="font-size: 0.75rem;">
                {{ form.reference_number }}
            </span>
        </div>

        <BRow>
            <BCol lg="12">
                <form @submit.prevent>
                    <BCard no-body class="service-form-card">
                        <BCardHeader>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ isEdit ? 'Modifica Servizio' : 'Nuovo Servizio' }}</h5>

                                <!-- Quick Navigation - Compact Icon-only Style -->
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small me-2" title="Navigazione Rapida">
                                        <i class="ri-navigation-line"></i>
                                    </span>
                                    <button type="button" @click="scrollToSection('section-identificativi')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Identificativi">
                                        <i class="ri-file-list-3-line"></i><span class="d-none d-lg-inline ms-1">Identificativi</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-passeggeri')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Passeggeri">
                                        <i class="ri-user-3-line"></i><span class="d-none d-lg-inline ms-1">Passeggeri</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-committenti')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Committenti">
                                        <i class="ri-building-line"></i><span class="d-none d-lg-inline ms-1">Committenti</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-intermediari')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Intermediari">
                                        <i class="ri-links-line"></i><span class="d-none d-lg-inline ms-1">Intermediari</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-veicolo')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Veicolo">
                                        <i class="ri-car-line"></i><span class="d-none d-lg-inline ms-1">Veicolo</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-driver')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Driver">
                                        <i class="ri-user-settings-line"></i><span class="d-none d-lg-inline ms-1">Driver</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-bagagli')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Bagagli">
                                        <i class="ri-suitcase-line"></i><span class="d-none d-lg-inline ms-1">Bagagli</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-piano')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Piano Servizio">
                                        <i class="ri-map-pin-line"></i><span class="d-none d-lg-inline ms-1">Piano</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-note')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Note">
                                        <i class="ri-file-text-line"></i><span class="d-none d-lg-inline ms-1">Note</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-prezzi')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Economics">
                                        <i class="ri-money-euro-box-line"></i><span class="d-none d-lg-inline ms-1">Economics</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-contabilita')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Contabilità">
                                        <i class="ri-calculator-line"></i><span class="d-none d-lg-inline ms-1">Contabilità</span>
                                    </button>
                                    <button type="button" @click="scrollToSection('section-tasks')" class="btn btn-sm btn-soft-primary px-2 py-1" title="Tasks">
                                        <i class="ri-task-line"></i><span class="d-none d-lg-inline ms-1">Tasks</span>
                                    </button>
                                </div>
                            </div>
                        </BCardHeader>
                        <BCardBody class="service-form-body">
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
                                <fieldset id="section-identificativi" class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-list-3-line me-2"></i>Dati Identificativi
                                    </legend>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
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
                                        <BCol md="3" class="mb-3">
                                            <label for="external_reference" class="form-label">Riferimento Esterno</label>
                                            <input
                                                id="external_reference"
                                                v-model="form.external_reference"
                                                type="text"
                                                class="form-control"
                                                placeholder="Codice prenotazione..."
                                            />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
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
                                            <label for="passenger_count" class="form-label">Numero Passeggeri *</label>
                                            <input
                                                id="passenger_count"
                                                v-model.number="form.passenger_count"
                                                type="number"
                                                class="form-control"
                                                required
                                                min="0"
                                            />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 2: PASSEGGERI -->
                                <fieldset id="section-passeggeri" class="border rounded p-3 mb-4">
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
                                                <VueTelInput
                                                    :modelValue="passenger.phone || ''"
                                                    @update:modelValue="val => passenger.phone = val"
                                                    @country-changed="country => onPhoneCountryChanged(passenger, country)"
                                                    :defaultCountry="getCountryCode(passenger.nationality) || 'IT'"
                                                    :autoDefaultCountry="false"
                                                    mode="international"
                                                    :preferredCountries="['IT', 'DE', 'FR', 'GB', 'US', 'CH', 'AT']"
                                                    :inputOptions="{ placeholder: 'Telefono...', styleClasses: 'form-control form-control-sm' }"
                                                    :dropdownOptions="{ showSearchBox: true, searchBoxPlaceholder: 'Cerca paese...', showDialCodeInSelection: true, showFlags: true }"
                                                    styleClasses="vue-tel-input-sm"
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
                                                <Multiselect
                                                    v-model="passenger.nationality"
                                                    :options="countryOptions"
                                                    :searchable="true"
                                                    :can-clear="true"
                                                    :can-deselect="true"
                                                    placeholder="Cerca nazionalità..."
                                                    no-results-text="Nessun risultato"
                                                    class="multiselect-sm"
                                                />
                                            </BCol>
                                            <BCol md="4" class="mb-2">
                                                <label class="form-label">Rif. Vettore</label>
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        v-model="passenger.carrier_reference"
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="Es. AZ1234"
                                                    />
                                                    <button
                                                        v-if="settings?.flight_tracking_enabled && passenger.carrier_reference"
                                                        type="button"
                                                        class="btn btn-outline-info"
                                                        @click="trackFlight(passenger.carrier_reference)"
                                                        :disabled="flightTracking"
                                                        title="Cerca informazioni volo"
                                                    >
                                                        <span v-if="flightTracking" class="spinner-border spinner-border-sm"></span>
                                                        <i v-else class="ri-flight-takeoff-line"></i>
                                                    </button>
                                                </div>
                                            </BCol>
                                            <BCol md="4" class="mb-2">
                                                <label class="form-label">Provenienza</label>
                                                <input
                                                    v-model="passenger.origin"
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

                                <!-- FIELDSET 3: COMMITTENTI -->
                                <fieldset id="section-committenti" class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-building-line me-2"></i>Committenti
                                    </legend>
                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label for="client_id" class="form-label">Committente *</label>
                                            <div class="d-flex gap-2">
                                                <Multiselect
                                                    :key="committentiKey"
                                                    id="client_id"
                                                    v-model="form.client_id"
                                                    :options="searchCommittenti"
                                                    :searchable="true"
                                                    :loading="committentiLoading"
                                                    :filter-results="false"
                                                    :min-chars="0"
                                                    :delay="300"
                                                    :resolve-on-load="true"
                                                    placeholder="Cerca committente..."
                                                    no-options-text="Nessun committente trovato"
                                                    no-results-text="Nessun risultato"
                                                    @change="onClientChange"
                                                    class="flex-grow-1"
                                                    :required="true"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-primary"
                                                    @click="openNewCommittenteModal"
                                                    title="Aggiungi nuovo committente"
                                                >
                                                    <i class="ri-add-line"></i>
                                                </button>
                                                <button
                                                    v-if="hasPassengerData"
                                                    type="button"
                                                    class="btn btn-soft-success"
                                                    @click="createCommittenteFromPassenger"
                                                    :disabled="creatingCommittenteFromPassenger"
                                                    title="Crea committente usando i dati del primo passeggero"
                                                >
                                                    <span v-if="creatingCommittenteFromPassenger" class="spinner-border spinner-border-sm"></span>
                                                    <span v-else><i class="ri-user-3-line"></i><i class="ri-arrow-right-line"></i></span>
                                                </button>
                                            </div>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 3b: INTERMEDIARI -->
                                <fieldset id="section-intermediari" class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-links-line me-2"></i>Intermediari
                                    </legend>
                                    <BRow>
                                        <BCol md="8" class="mb-3">
                                            <label for="intermediary_id" class="form-label">Intermediario</label>
                                            <div class="d-flex gap-2">
                                                <Multiselect
                                                    :key="intermediariKey"
                                                    id="intermediary_id"
                                                    v-model="form.intermediary_id"
                                                    :options="searchIntermediari"
                                                    :searchable="true"
                                                    :loading="intermediariLoading"
                                                    :filter-results="false"
                                                    :min-chars="0"
                                                    :delay="300"
                                                    :resolve-on-load="true"
                                                    placeholder="Cerca intermediario..."
                                                    no-options-text="Nessun intermediario trovato"
                                                    no-results-text="Nessun risultato"
                                                    @change="onIntermediaryChange"
                                                    class="flex-grow-1"
                                                    :canClear="true"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-primary"
                                                    @click="openNewIntermediarioModal"
                                                    title="Aggiungi nuovo intermediario"
                                                >
                                                    <i class="ri-add-line"></i>
                                                </button>
                                            </div>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- FIELDSET 4: VEICOLO -->
                                <fieldset id="section-veicolo" class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-car-line me-2"></i>Veicolo
                                    </legend>
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label for="supplier_id" class="form-label">Collega</label>
                                            <div class="d-flex gap-2">
                                                <Multiselect
                                                    :key="fornitoriKey"
                                                    id="supplier_id"
                                                    v-model="form.supplier_id"
                                                    :options="searchFornitori"
                                                    :searchable="true"
                                                    :loading="fornitoriLoading"
                                                    :filter-results="false"
                                                    :min-chars="0"
                                                    :delay="300"
                                                    :resolve-on-load="true"
                                                    placeholder="Cerca collega..."
                                                    no-options-text="Nessun collega trovato"
                                                    no-results-text="Nessun risultato"
                                                    @change="onSupplierChange"
                                                    class="flex-grow-1"
                                                    :canClear="true"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-primary"
                                                    @click="openNewFornitoreModal"
                                                    title="Aggiungi nuovo collega"
                                                >
                                                    <i class="ri-add-line"></i>
                                                </button>
                                            </div>
                                        </BCol>
                                        <BCol md="8" class="mb-3">
                                            <label for="vehicle_id" class="form-label">Veicolo *</label>
                                            <Multiselect
                                                id="vehicle_id"
                                                v-model="form.vehicle_id"
                                                :options="searchVehicles"
                                                :searchable="true"
                                                :loading="vehiclesLoading"
                                                :filter-results="false"
                                                :min-chars="0"
                                                :delay="300"
                                                :resolve-on-load="true"
                                                placeholder="Cerca veicolo..."
                                                no-options-text="Nessun veicolo trovato"
                                                no-results-text="Nessun risultato"
                                                class="flex-grow-1"
                                                :required="true"
                                                :disabled="form.vehicle_not_replaceable"
                                            />
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
                                <fieldset id="section-driver" class="border rounded p-3 mb-4">
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

                                            <!-- Searchable dropdown for adding drivers -->
                                            <Multiselect
                                                id="driver_select"
                                                :model-value="null"
                                                :options="searchDrivers"
                                                :searchable="true"
                                                :loading="driversLoading"
                                                :filter-results="false"
                                                :min-chars="0"
                                                :delay="300"
                                                :resolve-on-load="true"
                                                placeholder="Cerca un driver da aggiungere..."
                                                no-options-text="Nessun driver trovato"
                                                no-results-text="Nessun risultato"
                                                class="mb-3"
                                                :disabled="form.driver_not_replaceable"
                                                @change="addDriverFromMultiselect"
                                            />

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
                                                        <span class="text-dark">{{ driverLabel(driver) }}</span>
                                                        <span v-if="driver.driver_profile?.allow_overlapping" class="badge bg-info-subtle text-info" style="font-size: 0.7rem;">
                                                            Sovrapponibile
                                                        </span>
                                                        <button
                                                            type="button"
                                                            class="btn-close btn-sm"
                                                            style="font-size: 0.7rem;"
                                                            @click="removeDriver(driver.id)"
                                                            :title="`Rimuovi ${driverLabel(driver)}`"
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
                                <fieldset id="section-bagagli" class="border rounded p-3 mb-4">
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
                                <fieldset id="section-piano" class="border rounded p-3 mb-4">
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
                                                <AddressMapInput
                                                    id="pickup_address"
                                                    v-model:address="form.pickup_address"
                                                    v-model:latitude="form.pickup_latitude"
                                                    v-model:longitude="form.pickup_longitude"
                                                    label="Indirizzo Completo Pickup"
                                                    placeholder="Via, numero civico, città, CAP"
                                                    color="success"
                                                    required
                                                />
                                                <button
                                                    v-if="canSendTelegramLocation('pickup')"
                                                    type="button"
                                                    class="btn btn-sm btn-outline-info mt-1"
                                                    :disabled="sendingTelegramLocation === 'pickup'"
                                                    @click="sendTelegramLocation('pickup')"
                                                    title="Invia posizione pickup al driver via Telegram"
                                                >
                                                    <span v-if="sendingTelegramLocation === 'pickup'" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                    <i v-else class="ri-telegram-line me-1"></i>
                                                    Invia posizione al driver
                                                </button>
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="vehicle_departure_datetime" class="form-label">Data/Ora Uscita Mezzo *</label>
                                                <div class="input-group">
                                                    <input
                                                        id="vehicle_departure_datetime"
                                                        v-model="form.vehicle_departure_datetime"
                                                        type="datetime-local"
                                                        class="form-control"
                                                        required
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="form.vehicle_departure_datetime = form.pickup_datetime" title="Copia da Pickup">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
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
                                                <div class="input-group">
                                                    <input
                                                        id="dropoff_datetime"
                                                        v-model="form.dropoff_datetime"
                                                        type="datetime-local"
                                                        class="form-control form-control-lg border-danger"
                                                        required
                                                    />
                                                    <button type="button" class="btn btn-outline-danger" @click="form.dropoff_datetime = form.pickup_datetime" title="Copia da Pickup">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="dropoff_location" class="form-label fw-bold text-danger fs-5">
                                                    <i class="ri-map-pin-line me-1"></i>Luogo Dropoff *
                                                </label>
                                                <div class="input-group">
                                                    <input
                                                        id="dropoff_location"
                                                        v-model="form.dropoff_location"
                                                        type="text"
                                                        class="form-control form-control-lg border-danger"
                                                        placeholder="Es. Stazione Termini, Ufficio Cliente..."
                                                        required
                                                    />
                                                    <button type="button" class="btn btn-outline-danger" @click="form.dropoff_location = form.pickup_location" title="Copia da Pickup">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                            </BCol>
                                            <BCol md="12" class="mb-3">
                                                <div class="d-flex align-items-end gap-2">
                                                    <div class="flex-grow-1">
                                                        <AddressMapInput
                                                            id="dropoff_address"
                                                            v-model:address="form.dropoff_address"
                                                            v-model:latitude="form.dropoff_latitude"
                                                            v-model:longitude="form.dropoff_longitude"
                                                            label="Indirizzo Completo Dropoff"
                                                            placeholder="Via, numero civico, città, CAP"
                                                            color="danger"
                                                            required
                                                        />
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary mb-1" @click="copyPickupToDropoff" title="Copia indirizzo e coordinate da Pickup" style="white-space: nowrap;">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                                <button
                                                    v-if="canSendTelegramLocation('dropoff')"
                                                    type="button"
                                                    class="btn btn-sm btn-outline-info mt-1"
                                                    :disabled="sendingTelegramLocation === 'dropoff'"
                                                    @click="sendTelegramLocation('dropoff')"
                                                    title="Invia posizione dropoff al driver via Telegram"
                                                >
                                                    <span v-if="sendingTelegramLocation === 'dropoff'" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                    <i v-else class="ri-telegram-line me-1"></i>
                                                    Invia posizione al driver
                                                </button>
                                            </BCol>
                                            <BCol md="6" class="mb-3">
                                                <label for="vehicle_return_datetime" class="form-label">Data/Ora Rientro Mezzo *</label>
                                                <div class="input-group">
                                                    <input
                                                        id="vehicle_return_datetime"
                                                        v-model="form.vehicle_return_datetime"
                                                        type="datetime-local"
                                                        class="form-control"
                                                        required
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="form.vehicle_return_datetime = form.dropoff_datetime" title="Copia da Dropoff">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                            </BCol>
                                        </BRow>
                                    </fieldset>

                                    <!-- SUBFIELDSET: SOSTE -->
                                    <fieldset class="border rounded p-3 mb-0 bg-light">
                                        <legend class="float-none w-auto px-2 fs-6 fw-semibold text-info">
                                            <i class="ri-calendar-check-line me-1"></i>Soste
                                        </legend>

                                        <!-- Show message if service not yet saved -->
                                        <div v-if="!isEdit" class="alert alert-info mb-0">
                                            <i class="ri-information-line me-1"></i>
                                            Le soste potranno essere aggiunte dopo aver salvato il servizio.
                                        </div>

                                        <!-- Activity List for Edit Mode -->
                                        <div v-else>
                                            <!-- Existing Activities Table -->
                                            <div v-if="form.activities && form.activities.length > 0" class="table-responsive mb-3">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 40px"></th>
                                                            <th>Inizio</th>
                                                            <th>Fine</th>
                                                            <th>Descrizione Sosta</th>
                                                            <th>Tipo</th>
                                                            <th>Fornitore</th>
                                                            <th>Costo</th>
                                                            <th>€/Pax</th>
                                                            <th>Pagamento</th>
                                                            <th class="text-center" style="width: 50px" title="Contabilizza"><i class="ri-calculator-line"></i></th>
                                                            <th class="text-center" style="width: 50px" title="Conferma"><i class="ri-checkbox-circle-line"></i></th>
                                                            <th style="min-width: 140px">Assegna a</th>
                                                            <th class="text-center">Azioni</th>
                                                        </tr>
                                                    </thead>
                                                    <VueDraggableNext
                                                        v-model="form.activities"
                                                        tag="tbody"
                                                        handle=".drag-handle"
                                                        @end="onActivityDragEnd"
                                                    >
                                                        <tr v-for="(activity, index) in form.activities" :key="activity.id">
                                                            <!-- Handle -->
                                                            <td class="text-center">
                                                                <i class="ri-draggable drag-handle" style="cursor: grab; font-size: 1.2rem; color: #adb5bd;" title="Trascina per riordinare"></i>
                                                            </td>
                                                            <!-- Inizio -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'start_time')" class="cursor-pointer fw-bold" @click="startEditActivityField(activity, 'start_time')" :title="activity.start_time ? formatDateTime(activity.start_time) + ' - Clicca per modificare' : 'Clicca per impostare'">
                                                                    {{ formatTimeHighlight(activity.start_time).time }}
                                                                </div>
                                                                <div v-else>
                                                                    <input type="time" v-model="editingActivityTimeValue" class="form-control form-control-sm mb-1" style="font-size: 0.75rem; width: 90px;" @keyup.escape="cancelEditActivityField" />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityTimeField(activity, 'start_time')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Fine -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'end_time')" class="cursor-pointer fw-bold" @click="startEditActivityField(activity, 'end_time')" :title="activity.end_time ? formatDateTime(activity.end_time) + ' - Clicca per modificare' : 'Clicca per impostare'">
                                                                    {{ formatTimeHighlight(activity.end_time).time }}
                                                                </div>
                                                                <div v-else>
                                                                    <input type="time" v-model="editingActivityTimeValue" class="form-control form-control-sm mb-1" style="font-size: 0.75rem; width: 90px;" @keyup.escape="cancelEditActivityField" />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityTimeField(activity, 'end_time')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Descrizione -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'name')" class="cursor-pointer" @click="startEditActivityField(activity, 'name')" title="Clicca per modificare">
                                                                    {{ activity.name }}
                                                                </div>
                                                                <div v-else>
                                                                    <input type="text" v-model="editingActivityValue" class="form-control form-control-sm mb-1" style="font-size: 0.75rem; min-width: 120px;" @keyup.escape="cancelEditActivityField" />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'name')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Tipo -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'activity_type_id')" class="cursor-pointer" @click="startEditActivityField(activity, 'activity_type_id')" title="Clicca per modificare">
                                                                    <span v-if="getActivityTypeName(activity.activity_type_id)" class="badge bg-info-subtle text-info">{{ getActivityTypeName(activity.activity_type_id) }}</span>
                                                                    <span v-else class="text-muted">-</span>
                                                                </div>
                                                                <div v-else>
                                                                    <select v-model="editingActivityValue" class="form-select form-select-sm mb-1" style="font-size: 0.75rem; min-width: 100px;" @keyup.escape="cancelEditActivityField">
                                                                        <option value="">-</option>
                                                                        <option v-for="type in activityTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                                                    </select>
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'activity_type_id')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Fornitore -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'supplier_id')" class="cursor-pointer" @click="startEditActivityField(activity, 'supplier_id')" title="Clicca per modificare">
                                                                    <span v-if="activity.supplier">{{ activity.supplier.name }} {{ activity.supplier.surname }}</span>
                                                                    <span v-else class="text-muted">-</span>
                                                                </div>
                                                                <div v-else>
                                                                    <select v-model="editingActivityValue" class="form-select form-select-sm mb-1" style="font-size: 0.75rem; min-width: 120px;" @keyup.escape="cancelEditActivityField">
                                                                        <option value="">-</option>
                                                                        <option v-for="s in fornitori" :key="s.value" :value="s.value">{{ s.label }}</option>
                                                                    </select>
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'supplier_id')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Costo -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'cost')" class="cursor-pointer" @click="startEditActivityField(activity, 'cost')" title="Clicca per modificare">
                                                                    € {{ formatAmount(activity.cost) }}
                                                                </div>
                                                                <div v-else>
                                                                    <input type="number" v-model.number="editingActivityValue" step="0.01" min="0" class="form-control form-control-sm mb-1" style="font-size: 0.75rem; width: 80px;" @keyup.escape="cancelEditActivityField" />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'cost')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- €/Pax -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'cost_per_person')" class="cursor-pointer" @click="startEditActivityField(activity, 'cost_per_person')" title="Clicca per modificare">
                                                                    € {{ formatAmount(activity.cost_per_person) }}
                                                                </div>
                                                                <div v-else>
                                                                    <input type="number" v-model.number="editingActivityValue" step="0.01" min="0" class="form-control form-control-sm mb-1" style="font-size: 0.75rem; width: 80px;" @keyup.escape="cancelEditActivityField" />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'cost_per_person')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Pagamento -->
                                                            <td>
                                                                <div v-if="!isEditingActivity(activity, 'payment_type')" class="cursor-pointer" @click="startEditActivityField(activity, 'payment_type')" title="Clicca per modificare">
                                                                    <span v-if="activity.payment_type" class="badge" :class="getPaymentTypeBadge(activity.payment_type)" :style="getPaymentTypeBadgeStyle(activity.payment_type)">{{ activity.payment_type }}</span>
                                                                    <span v-else class="text-muted">-</span>
                                                                </div>
                                                                <div v-else>
                                                                    <select v-model="editingActivityValue" class="form-select form-select-sm mb-1" style="font-size: 0.75rem; min-width: 100px;" @keyup.escape="cancelEditActivityField">
                                                                        <option value="">-</option>
                                                                        <option v-for="pt in activityPaymentTypes" :key="pt.id" :value="pt.code">{{ pt.name }}</option>
                                                                    </select>
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveActivityField(activity, 'payment_type')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditActivityField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <!-- Contabilizza -->
                                                            <td class="text-center">
                                                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="form-check-input"
                                                                        :checked="activity.should_account"
                                                                        @change="saveActivityShouldAccount(activity, $event.target.checked)"
                                                                    />
                                                                </div>
                                                            </td>
                                                            <!-- Conferma -->
                                                            <td class="text-center">
                                                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="form-check-input"
                                                                        :checked="activity.confirmation_enabled"
                                                                        @change="toggleActivityRowConfirmation(index, $event.target.checked)"
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <select
                                                                    v-if="activity.confirmation_enabled"
                                                                    class="form-select form-select-sm"
                                                                    :value="activity.confirmation_assignee_id || ''"
                                                                    @change="setActivityAssignee(index, $event.target.value)"
                                                                >
                                                                    <option value="">Tutti (ruolo default)</option>
                                                                    <option v-for="user in confirmationRoleUsers" :key="user.id" :value="user.id">
                                                                        {{ user.surname }} {{ user.name }}
                                                                    </option>
                                                                </select>
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
                                                    </VueDraggableNext>
                                                </table>
                                            </div>

                                            <!-- Empty State -->
                                            <div v-else class="alert alert-warning mb-3">
                                                <i class="ri-alert-line me-1"></i>
                                                Nessuna sosta collegata a questo servizio.
                                            </div>

                                            <!-- Add Activity Button -->
                                            <div class="text-end mb-3">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    @click="openActivityModal()"
                                                >
                                                    <i class="ri-add-line me-1"></i>Aggiungi Sosta
                                                </button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </fieldset>

                                <!-- FIELDSET 8: NOTE -->
                                <fieldset id="section-note" class="border rounded p-3 mb-4">
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

                                <!-- FIELDSET: ALLEGATI SERVIZIO -->
                                <div v-if="isEdit && props.service?.id">
                                    <ServiceAttachments :service-id="props.service.id" />
                                </div>

                                <!-- FIELDSET: ECONOMICS -->
                                <fieldset id="section-prezzi" class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-money-euro-box-line me-2"></i>Economics
                                    </legend>

                                    <!-- Sub-fieldset: Costi del Servizio -->
                                    <fieldset class="border rounded p-3 mb-3 bg-light">
                                        <legend class="fs-6 fw-semibold text-secondary mb-2">
                                            <i class="ri-money-euro-circle-line me-1"></i>Costi del Servizio
                                        </legend>

                                        <!-- Commissione -->
                                        <BRow>
                                            <BCol md="4" class="mb-3">
                                                <label for="intermediary_commission" class="form-label">Commissione (&euro;)</label>
                                                <input
                                                    id="intermediary_commission"
                                                    v-model.number="form.intermediary_commission"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control form-control-sm"
                                                    placeholder="0.00"
                                                />
                                            </BCol>
                                        </BRow>

                                        <!-- Spese del Veicolo -->
                                        <fieldset class="border rounded p-3 mt-2 bg-white">
                                            <legend class="fs-6 fw-semibold text-secondary mb-2">
                                                <i class="ri-car-line me-1"></i>Spese del Veicolo
                                            </legend>
                                            <BRow>
                                                <BCol md="3" class="mb-3">
                                                    <label for="fuel_cost" class="form-label">Costo Carburanti (&euro;)</label>
                                                    <input
                                                        id="fuel_cost"
                                                        v-model.number="form.fuel_cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                                <BCol md="3" class="mb-3">
                                                    <label for="toll_cost" class="form-label">Costo Pedaggio (&euro;)</label>
                                                    <input
                                                        id="toll_cost"
                                                        v-model.number="form.toll_cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                                <BCol md="3" class="mb-3">
                                                    <label for="parking_cost" class="form-label">Costo Parcheggio (&euro;)</label>
                                                    <input
                                                        id="parking_cost"
                                                        v-model.number="form.parking_cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                                <BCol md="3" class="mb-3">
                                                    <label for="other_vehicle_costs" class="form-label">Altri costi veicolo (&euro;)</label>
                                                    <input
                                                        id="other_vehicle_costs"
                                                        v-model.number="form.other_vehicle_costs"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                            </BRow>
                                        </fieldset>

                                        <!-- Spese del Driver -->
                                        <fieldset class="border rounded p-3 mt-2 bg-white">
                                            <legend class="fs-6 fw-semibold text-secondary mb-2">
                                                <i class="ri-user-line me-1"></i>Spese del Driver
                                            </legend>
                                            <BRow>
                                                <BCol md="4" class="mb-3">
                                                    <label for="driver_compensation" class="form-label">Costo Driver (&euro;)</label>
                                                    <input
                                                        id="driver_compensation"
                                                        v-model.number="form.driver_compensation"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                                <BCol md="4" class="mb-3">
                                                    <label for="colleague_cost" class="form-label">Costo Fornitore (&euro;)</label>
                                                    <input
                                                        id="colleague_cost"
                                                        v-model.number="form.colleague_cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        placeholder="0.00"
                                                    />
                                                </BCol>
                                            </BRow>
                                        </fieldset>
                                    </fieldset>

                                    <!-- Sub-fieldset: Ricavi -->
                                    <fieldset class="border rounded p-3 mb-3 bg-light">
                                        <legend class="fs-6 fw-semibold text-secondary mb-2">
                                            <i class="ri-money-euro-circle-line me-1"></i>Ricavi
                                        </legend>

                                        <!-- Ricavi del Servizio -->
                                        <fieldset class="border rounded p-3 mb-3 bg-white">
                                        <legend class="fs-6 fw-semibold text-info mb-2" style="font-size: 0.85rem !important;">
                                            <i class="ri-price-tag-3-line me-1"></i>Ricavi del Servizio
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
                                            <BCol md="2" class="mb-3">
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
                                            <BCol md="3" class="mb-3">
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
                                        </BRow>

                                        <!-- Riga 2: Acconto (radio integrato nella label) -->
                                        <BRow>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.deposit_sale_type" value="deposit_taxable" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.deposit_sale_type === 'deposit_taxable' }">Acconto Imponibile €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="deposit_taxable"
                                                        v-model.number="form.deposit_taxable"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.deposit_sale_type === 'deposit_taxable' }"
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                                <small class="text-muted">Imponibile × Acconto% / 100 (arr. 5€)</small>
                                            </BCol>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.deposit_sale_type" value="deposit_handling_fees" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.deposit_sale_type === 'deposit_handling_fees' }">Acconto Bonifico Bancario €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="deposit_handling_fees"
                                                        v-model.number="form.deposit_handling_fees"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.deposit_sale_type === 'deposit_handling_fees' }"
                                                        placeholder="0.00"
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="calcDepositHandlingFees" title="Calcola">
                                                        <i class="ri-calculator-line"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Acconto Imponibile × (1 + IVA%) (arr. 5€)</small>
                                            </BCol>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.deposit_sale_type" value="deposit_card_fees" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.deposit_sale_type === 'deposit_card_fees' }">Acconto Carta Credito €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="deposit_amount"
                                                        v-model.number="form.deposit_amount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.deposit_sale_type === 'deposit_card_fees' }"
                                                        placeholder="0.00"
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="calcDepositAmount" title="Calcola">
                                                        <i class="ri-calculator-line"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Con IVA e Card Fees × Acconto% (arr. 5€)</small>
                                            </BCol>
                                        </BRow>

                                        <!-- Riga 3: Saldi (radio integrato nella label) -->
                                        <BRow>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.balance_sale_type" value="balance_taxable" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.balance_sale_type === 'balance_taxable' }">Saldo Imponibile €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="balance_taxable"
                                                        v-model.number="form.balance_taxable"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.balance_sale_type === 'balance_taxable' }"
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                                <small class="text-muted">Imponibile - Acconto Imponibile</small>
                                            </BCol>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.balance_sale_type" value="balance_handling_fees" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.balance_sale_type === 'balance_handling_fees' }">Saldo Bonifico Bancario €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="balance_handling_fees"
                                                        v-model.number="form.balance_handling_fees"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.balance_sale_type === 'balance_handling_fees' }"
                                                        placeholder="0.00"
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="calcBalanceHandlingFees" title="Calcola">
                                                        <i class="ri-calculator-line"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Con IVA × (100 - Acconto%) (arr. 5€)</small>
                                            </BCol>
                                            <BCol md="4" class="mb-3">
                                                <label class="form-label d-flex align-items-center gap-2">
                                                    <input type="radio" v-model="form.balance_sale_type" value="balance_card_fees" class="form-check-input mt-0" />
                                                    <span :class="{ 'fw-semibold text-primary': form.balance_sale_type === 'balance_card_fees' }">Saldo Carta Credito €</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">€</span>
                                                    <input
                                                        id="balance_card_fees"
                                                        v-model.number="form.balance_card_fees"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control"
                                                        :class="{ 'border-primary': form.balance_sale_type === 'balance_card_fees' }"
                                                        placeholder="0.00"
                                                    />
                                                    <button type="button" class="btn btn-outline-secondary" @click="calcBalanceCardFees" title="Calcola">
                                                        <i class="ri-calculator-line"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Con IVA e Card Fees × (100 - Acconto%) (arr. 5€)</small>
                                            </BCol>
                                        </BRow>

                                        <!-- Bottoni Calcola Corrispettivi -->
                                        <BRow class="mb-3">
                                            <BCol md="12" class="text-end d-flex justify-content-end gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-info"
                                                    @click="openReverseCalcModal"
                                                >
                                                    <i class="ri-arrow-go-back-line me-1"></i>Calcola Imponibile
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-success"
                                                    @click="calculateTotals"
                                                >
                                                    <i class="ri-calculator-line me-1"></i>Calcola Corrispettivi di Vendita
                                                </button>
                                            </BCol>
                                        </BRow>
                                        </fieldset>

                                        <!-- Ricavi Extra -->
                                        <fieldset class="border rounded p-3 mb-0 bg-white">
                                        <legend class="fs-6 fw-semibold text-info mb-2" style="font-size: 0.85rem !important;">
                                            <i class="ri-add-circle-line me-1"></i>Ricavi Extra
                                        </legend>

                                        <div v-if="!isEdit" class="alert alert-info mb-0 small">
                                            <i class="ri-information-line me-1"></i>
                                            I ricavi extra potranno essere aggiunti dopo aver salvato il servizio.
                                        </div>

                                        <div v-else>
                                            <div v-if="form.extra_revenues && form.extra_revenues.length > 0" class="table-responsive mb-3">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Descrizione</th>
                                                            <th>Importo</th>
                                                            <th class="text-center">Azioni</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(extra, index) in form.extra_revenues" :key="extra.id || index">
                                                            <td>{{ extra.description }}</td>
                                                            <td>€ {{ getExtraRevenueSelectedAmount(extra).toFixed(2) }} <small class="text-muted">({{ getExtraRevenueSaleLabel(extra.sale_type) }})</small></td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-soft-primary me-1" @click="openExtraRevenueModal(extra, index)" title="Modifica">
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeExtraRevenue(index)" title="Elimina">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-light">
                                                            <td class="fw-bold">Totale Ricavi Extra</td>
                                                            <td class="fw-bold">€ {{ extraRevenuesTotalFormatted }}</td>
                                                            <td></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <div v-else class="alert alert-light mb-3 small">
                                                Nessun ricavo extra aggiunto.
                                            </div>

                                            <div class="text-end">
                                                <button type="button" class="btn btn-sm btn-primary" @click="openExtraRevenueModal()">
                                                    <i class="ri-add-line me-1"></i>Aggiungi Ricavo Extra
                                                </button>
                                            </div>
                                        </div>
                                        </fieldset>

                                    </fieldset>

                                    <!-- Sub-fieldset: Gestione -->
                                    <fieldset class="border rounded p-3 mb-3 bg-light">
                                        <legend class="fs-6 fw-semibold text-secondary mb-2">
                                            <i class="ri-settings-4-line me-1"></i>Gestione
                                        </legend>
                                        <BRow>
                                            <BCol md="4" class="mb-3">
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
                                            <BCol md="4" class="mb-3 d-flex align-items-center">
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
                                            <BCol md="4" class="mb-3 d-flex align-items-center">
                                                <div class="form-check form-switch">
                                                    <input
                                                        id="driver_must_collect"
                                                        v-model="form.driver_must_collect"
                                                        type="checkbox"
                                                        class="form-check-input"
                                                    />
                                                    <label class="form-check-label" for="driver_must_collect">
                                                        <strong>Driver deve incassare</strong>
                                                    </label>
                                                    <small class="d-block text-muted">
                                                        Se attivo, il driver deve incassare il corrispettivo del servizio
                                                    </small>
                                                </div>
                                            </BCol>
                                        </BRow>
                                    </fieldset>
                                </fieldset>

                                <!-- FIELDSET 10: CONTABILITÀ -->
                                <fieldset id="section-contabilita" class="border rounded p-3 mb-4">
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
                                                                @click.prevent="toggleSelectAllTransactions"
                                                                title="Seleziona tutti"
                                                            />
                                                        </th>
                                                        <th style="width: 70px;">Azioni</th>
                                                        <th>Tipo</th>
                                                        <th>Data</th>
                                                        <th>Importo</th>
                                                        <th style="max-width: 250px;">Causali</th>
                                                        <th style="max-width: 200px;">Controparte</th>
                                                        <th>Documenti</th>
                                                        <th>Pagamento</th>
                                                        <th>Stato</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="transaction in sortedAccountingTransactions" :key="transaction.id">
                                                        <!-- CHECKBOX -->
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input"
                                                                :value="transaction.id"
                                                                v-model="selectedTransactions"
                                                            />
                                                        </td>
                                                        <!-- AZIONI -->
                                                        <td>
                                                            <div class="d-flex gap-1">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-primary"
                                                                    @click="openTransactionModal(transaction)"
                                                                    title="Modifica"
                                                                >
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <button
                                                                    v-if="!transaction.is_automatic"
                                                                    type="button"
                                                                    class="btn btn-sm btn-soft-danger"
                                                                    @click="removeTransaction(transaction.id)"
                                                                    title="Elimina"
                                                                >
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <!-- TIPO + RATA (merged) -->
                                                        <td>
                                                            <span
                                                                :class="getTransactionTypeBadge(transaction.transaction_type)"
                                                                :title="getTransactionTypeLabel(transaction.transaction_type)"
                                                            >
                                                                {{ getTransactionTypeAbbr(transaction.transaction_type) }}
                                                            </span>
                                                            <span v-if="transaction.is_automatic" class="badge bg-info-subtle text-info ms-1" title="Automatico">A</span>
                                                            <span v-else class="badge bg-warning-subtle text-warning ms-1" title="Manuale">M</span>
                                                            <span
                                                                v-if="editingInstallment !== transaction.id"
                                                                class="badge bg-secondary-subtle text-secondary cursor-pointer ms-1"
                                                                :title="getInstallmentLabel(transaction.installment) + ' - Clicca per modificare'"
                                                                @click="startEditInstallment(transaction)"
                                                            >
                                                                {{ getInstallmentAbbr(transaction.installment) }}
                                                            </span>
                                                            <div v-else class="mt-1">
                                                                <select
                                                                    v-model="editingInstallmentValue"
                                                                    class="form-select form-select-sm"
                                                                    @change="saveInstallment(transaction)"
                                                                    @keyup.esc="cancelEditInstallment"
                                                                    @blur="cancelEditInstallment"
                                                                    style="max-width: 130px; font-size: 0.75rem;"
                                                                >
                                                                    <option value="deposit">Acconto</option>
                                                                    <option value="balance">Saldo</option>
                                                                    <option value="supplier_refund">Reso Fornitore</option>
                                                                    <option value="customer_refund">Rimborso Cliente</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                        <!-- DATA -->
                                                        <td>{{ formatDate(transaction.transaction_date) }}</td>
                                                        <!-- IMPORTO -->
                                                        <td class="fw-medium">
                                                            € {{ parseFloat(transaction.amount).toFixed(2) }}
                                                        </td>
                                                        <!-- CAUSALI -->
                                                        <td style="max-width: 250px; word-wrap: break-word; white-space: normal;">
                                                            <span v-if="transaction.payment_reason || transaction.accounting_entry">
                                                                <span v-if="transaction.payment_reason">{{ transaction.payment_reason }}</span>
                                                                <br v-if="transaction.payment_reason && transaction.accounting_entry">
                                                                <small v-if="transaction.accounting_entry" class="text-muted">
                                                                    {{ transaction.accounting_entry.abbreviation || transaction.accounting_entry.name }}
                                                                </small>
                                                            </span>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
                                                        <!-- CONTROPARTE con link -->
                                                        <td style="max-width: 200px; word-wrap: break-word; white-space: normal;">
                                                            <span v-if="transaction.counterpart">
                                                                <a :href="'/easyncc/users/' + transaction.counterpart.id + '/edit'" class="text-primary text-decoration-none" :title="'Apri scheda ' + transaction.counterpart.name + ' ' + transaction.counterpart.surname">
                                                                    {{ transaction.counterpart.name }} {{ transaction.counterpart.surname }}
                                                                </a>
                                                            </span>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
                                                        <!-- DOCUMENTI con inline editing -->
                                                        <td>
                                                            <div>
                                                                <!-- N. documento inline -->
                                                                <span
                                                                    v-if="editingTransactionField !== transaction.id + '_document_number'"
                                                                    class="cursor-pointer"
                                                                    :class="transaction.document_number ? '' : 'text-muted'"
                                                                    @click="startEditTransactionField(transaction, 'document_number')"
                                                                    title="Clicca per modificare n. documento"
                                                                >
                                                                    {{ transaction.document_number || '-' }}
                                                                </span>
                                                                <div v-else>
                                                                    <input
                                                                        type="text"
                                                                        v-model="editingTransactionFieldValue"
                                                                        class="form-control form-control-sm mb-1"
                                                                        style="max-width: 120px; font-size: 0.75rem;"
                                                                        placeholder="N. doc"
                                                                        @keyup.escape="cancelEditTransactionField"
                                                                    />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveTransactionField(transaction, 'document_number')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditTransactionField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                                <br>
                                                                <!-- Scadenza documento inline -->
                                                                <small
                                                                    v-if="editingTransactionField !== transaction.id + '_document_due_date'"
                                                                    class="cursor-pointer"
                                                                    :class="transaction.document_due_date ? getDueDateClass(transaction) : 'text-muted'"
                                                                    @click="startEditTransactionField(transaction, 'document_due_date')"
                                                                    title="Clicca per modificare scadenza"
                                                                >
                                                                    {{ transaction.document_due_date ? formatDate(transaction.document_due_date) : '-' }}
                                                                </small>
                                                                <div v-else>
                                                                    <input
                                                                        type="date"
                                                                        v-model="editingTransactionFieldValue"
                                                                        class="form-control form-control-sm mb-1"
                                                                        style="max-width: 140px; font-size: 0.75rem;"
                                                                        @keyup.escape="cancelEditTransactionField"
                                                                    />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveTransactionField(transaction, 'document_due_date')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditTransactionField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <!-- PAGAMENTO (nuova colonna) con inline editing -->
                                                        <td>
                                                            <div>
                                                                <!-- Data pagamento inline -->
                                                                <small
                                                                    v-if="editingTransactionField !== transaction.id + '_payment_date'"
                                                                    class="cursor-pointer"
                                                                    :class="transaction.payment_date ? '' : 'text-muted'"
                                                                    @click="startEditTransactionField(transaction, 'payment_date')"
                                                                    title="Clicca per modificare data pagamento"
                                                                >
                                                                    {{ transaction.payment_date ? formatDate(transaction.payment_date) : '-' }}
                                                                </small>
                                                                <div v-else>
                                                                    <input
                                                                        type="date"
                                                                        v-model="editingTransactionFieldValue"
                                                                        class="form-control form-control-sm mb-1"
                                                                        style="max-width: 140px; font-size: 0.75rem;"
                                                                        @keyup.escape="cancelEditTransactionField"
                                                                    />
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveTransactionField(transaction, 'payment_date')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditTransactionField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                                <br>
                                                                <!-- Metodo pagamento inline -->
                                                                <small
                                                                    v-if="editingTransactionField !== transaction.id + '_payment_type'"
                                                                    class="cursor-pointer"
                                                                    :class="transaction.payment_type ? '' : 'text-muted'"
                                                                    @click="startEditTransactionField(transaction, 'payment_type')"
                                                                    title="Clicca per modificare metodo pagamento"
                                                                >
                                                                    {{ transaction.payment_type || '-' }}
                                                                </small>
                                                                <div v-else>
                                                                    <select
                                                                        v-model="editingTransactionFieldValue"
                                                                        class="form-select form-select-sm mb-1"
                                                                        style="max-width: 140px; font-size: 0.75rem;"
                                                                        @keyup.escape="cancelEditTransactionField"
                                                                    >
                                                                        <option value="">Nessuno</option>
                                                                        <option v-for="type in paymentTypes" :key="type.id" :value="type.name">
                                                                            {{ type.name }}
                                                                        </option>
                                                                    </select>
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-success btn-sm py-0 px-1" @click="saveTransactionField(transaction, 'payment_type')" style="font-size: 0.7rem;"><i class="ri-check-line"></i></button>
                                                                        <button type="button" class="btn btn-secondary btn-sm py-0 px-1" @click="cancelEditTransactionField" style="font-size: 0.7rem;"><i class="ri-close-line"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <!-- STATO -->
                                                        <td>
                                                            <span
                                                                v-if="editingTransactionStatus !== transaction.id"
                                                                @click="startEditTransactionStatus(transaction)"
                                                                class="badge cursor-pointer"
                                                                :style="getStatusBadgeStyle(transaction.status)"
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
                                                                    <option
                                                                        v-for="ts in filteredTransactionStatuses(transaction.transaction_type)"
                                                                        :key="ts.code"
                                                                        :value="ts.code"
                                                                    >{{ ts.name }}</option>
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
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Bulk action bar -->
                                        <Transition name="tx-bulk-bar">
                                            <div v-if="selectedTransactions.length > 0" class="tx-bulk-action-bar mt-2">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                    <!-- Left: counter + deselect -->
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-white text-dark fs-6">{{ selectedTransactions.length }}</span>
                                                        <span class="text-white small">selezionati</span>
                                                        <button class="btn btn-sm btn-outline-light" @click="selectedTransactions = []" title="Deseleziona tutti">
                                                            <i class="ri-close-line"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Right: actions -->
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <!-- Bulk Status -->
                                                        <div class="tx-bulk-action-group">
                                                            <select
                                                                v-model="bulkTransactionStatus"
                                                                class="form-select form-select-sm tx-bulk-select"
                                                                :disabled="bulkAvailableStatuses.length === 0"
                                                                :title="bulkAvailableStatuses.length === 0 ? 'Seleziona movimenti dello stesso tipo per cambiare lo stato' : 'Stato'"
                                                            >
                                                                <option value="">Stato...</option>
                                                                <option v-for="ts in bulkAvailableStatuses" :key="ts.code" :value="ts.code">
                                                                    {{ ts.name }}
                                                                </option>
                                                            </select>
                                                            <button
                                                                v-if="bulkTransactionStatus"
                                                                class="btn btn-sm btn-light"
                                                                @click="applyBulkStatus"
                                                                :disabled="bulkApplyingTransactions"
                                                                title="Applica stato"
                                                            >
                                                                <i class="ri-check-line"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Bulk Payment Date -->
                                                        <div class="tx-bulk-action-group">
                                                            <input
                                                                type="date"
                                                                v-model="bulkPaymentDate"
                                                                class="form-control form-control-sm tx-bulk-select"
                                                                title="Data pagamento"
                                                            />
                                                            <button
                                                                v-if="bulkPaymentDate"
                                                                class="btn btn-sm btn-light"
                                                                @click="applyBulkField('payment_date', bulkPaymentDate)"
                                                                :disabled="bulkApplyingTransactions"
                                                                title="Applica data pagamento"
                                                            >
                                                                <i class="ri-check-line"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Bulk Payment Type -->
                                                        <div class="tx-bulk-action-group">
                                                            <select
                                                                v-model="bulkPaymentType"
                                                                class="form-select form-select-sm tx-bulk-select"
                                                            >
                                                                <option value="">Metodo...</option>
                                                                <option v-for="type in paymentTypes" :key="type.id" :value="type.name">
                                                                    {{ type.name }}
                                                                </option>
                                                            </select>
                                                            <button
                                                                v-if="bulkPaymentType"
                                                                class="btn btn-sm btn-light"
                                                                @click="applyBulkField('payment_type', bulkPaymentType)"
                                                                :disabled="bulkApplyingTransactions"
                                                                title="Applica metodo pagamento"
                                                            >
                                                                <i class="ri-check-line"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Divider -->
                                                        <span class="tx-bulk-divider">|</span>

                                                        <!-- Bulk Delete -->
                                                        <button
                                                            class="btn btn-sm btn-danger"
                                                            @click="deleteSelectedTransactions"
                                                            :disabled="bulkApplyingTransactions"
                                                        >
                                                            <i class="ri-delete-bin-line me-1"></i><span class="d-none d-sm-inline">Elimina</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Progress indicator -->
                                                <div v-if="bulkApplyingTransactions" class="mt-2">
                                                    <div class="progress" style="height: 3px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </fieldset>

                                <!-- FIELDSET 11: TASKS -->
                                <fieldset id="section-tasks" class="border rounded p-3 mb-4">
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
                                                    <th scope="col" style="max-width: 300px;">Nome Task</th>
                                                    <th scope="col">Scadenza</th>
                                                    <th scope="col">Assegnatario</th>
                                                    <th scope="col">Stato</th>
                                                    <th scope="col" style="max-width: 250px;">Note</th>
                                                    <th scope="col">Azioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="task in sortedServiceTasks" :key="task.id">
                                                    <td class="fw-medium" style="max-width: 300px; word-wrap: break-word; white-space: normal;">{{ task.name }}</td>
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
                                                    <td style="max-width: 250px; word-wrap: break-word; white-space: normal;">
                                                        <small class="text-muted">{{ task.notes || '-' }}</small>
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

                                <!-- Audit Section -->
                                <div v-if="isEdit && props.service" class="row mb-4 pt-3 border-top">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">Informazioni di Sistema</h6>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Creato da</label>
                                        <p class="text-muted mb-0">
                                            {{ props.service.creator ? `${props.service.creator.name || ''} ${props.service.creator.surname || ''}`.trim() : '-' }}
                                            {{ props.service.created_at ? `il ${formatDateTime(props.service.created_at)}` : '' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Ultimo aggiornamento da</label>
                                        <p class="text-muted mb-0">
                                            {{ props.service.updater ? `${props.service.updater.name || ''} ${props.service.updater.surname || ''}`.trim() : '-' }}
                                            {{ props.service.updated_at ? `il ${formatDateTime(props.service.updated_at)}` : '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </BCardBody>
                        <BCardFooter class="service-form-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <Link :href="returnUrl || route('easyncc.services.index')" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line me-1"></i>Annulla
                                </Link>
                                <div>
                                    <button type="button" class="btn btn-success me-2" :disabled="submitting" @click="saveAndStay">
                                        <span v-if="submitting && !exitAfterSave" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva
                                    </button>
                                    <button type="button" class="btn btn-primary" :disabled="submitting" @click="saveAndExit">
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
            :title="activityForm.id ? 'Modifica Sosta' : 'Nuova Sosta'"
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
                            <Multiselect
                                v-model="activityForm.supplier_id"
                                :options="searchActivitySuppliers"
                                :searchable="true"
                                :filter-results="false"
                                :min-chars="0"
                                :delay="100"
                                :resolve-on-load="true"
                                placeholder="Cerca fornitore..."
                                no-options-text="Nessun fornitore trovato"
                                no-results-text="Nessun risultato"
                                :canClear="true"
                            />
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
                            <label class="form-label">Descrizione Sosta <span class="text-danger">*</span></label>
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
                            <label class="form-label">Data e Ora Inizio</label>
                            <input
                                v-model="activityForm.start_time"
                                type="datetime-local"
                                class="form-control"
                                :min="form.pickup_datetime"
                                :max="form.dropoff_datetime"
                            />
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Data e Ora Fine</label>
                            <input
                                v-model="activityForm.end_time"
                                type="datetime-local"
                                class="form-control"
                                :min="activityForm.start_time || form.pickup_datetime"
                                :max="form.dropoff_datetime"
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
                                <option v-for="pt in activityPaymentTypes" :key="pt.id" :value="pt.code">
                                    {{ pt.name }}
                                </option>
                            </select>
                        </div>
                    </BCol>
                    <BCol md="6">
                        <div class="mb-3 d-flex align-items-center" style="margin-top: 2rem;">
                            <div class="form-check form-switch">
                                <input
                                    id="should_account"
                                    v-model="activityForm.should_account"
                                    type="checkbox"
                                    class="form-check-input"
                                />
                                <label class="form-check-label" for="should_account">Contabilizza</label>
                            </div>
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
                    :disabled="!activityForm.name"
                >
                    <i :class="activityForm.id ? 'ri-save-line' : 'ri-add-line'" class="me-1"></i>
                    {{ activityForm.id ? 'Aggiorna Sosta' : 'Aggiungi Sosta' }}
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
                            <select v-model="transactionForm.transaction_type" class="form-select" @change="onTransactionTypeChange" required :disabled="transactionForm.is_automatic">
                                <option value="">Seleziona tipo</option>
                                <option value="purchase">Acquisto (Costi da Fornitore)</option>
                                <option value="sale">Vendita (Ricavi da Committente)</option>
                                <option value="intermediation">Intermediazione (Commissioni)</option>
                            </select>
                            <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
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
                                :disabled="transactionForm.is_automatic"
                            />
                            <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
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
                                    :disabled="transactionForm.is_automatic"
                                />
                            </div>
                            <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
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
                                <option
                                    v-for="ts in filteredTransactionStatuses(transactionForm.transaction_type)"
                                    :key="ts.code"
                                    :value="ts.code"
                                >{{ ts.name }}</option>
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
                            <select v-model="transactionForm.accounting_entry_id" class="form-select" :disabled="transactionForm.is_automatic">
                                <option value="">Nessuna</option>
                                <option v-for="entry in accountingEntries" :key="entry.id" :value="entry.id">
                                    {{ entry.name }} ({{ entry.abbreviation }})
                                </option>
                            </select>
                            <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
                        </div>
                    </BCol>

                    <!-- Counterpart -->
                    <BCol md="6">
                        <div class="mb-3">
                            <label class="form-label">Controparte</label>
                            <select v-model="transactionForm.counterpart_id" class="form-select" :disabled="transactionForm.is_automatic">
                                <option value="">Nessuna</option>
                                <option v-for="user in filteredCounterparts" :key="user.id" :value="user.id">
                                    {{ user.name }} {{ user.surname }} - {{ user.email }}
                                </option>
                            </select>
                            <small v-if="transactionForm.is_automatic" class="text-muted d-block mt-1">Campo gestito automaticamente</small>
                            <small v-else class="text-muted d-block mt-1">
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

        <!-- Modal: Conferma Sovrapposizioni -->
        <BModal
            v-model="showOverlapModal"
            title="Sovrapposizioni Rilevate"
            size="lg"
            hide-footer
            no-close-on-backdrop
        >
            <div class="alert alert-warning mb-3">
                <i class="ri-alert-line me-2"></i>
                Sono state rilevate sovrapposizioni temporali per questo servizio. Confermare per procedere con il salvataggio.
            </div>

            <!-- Current service info -->
            <div class="card bg-light mb-3">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">Servizio corrente</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div v-if="currentServiceVehicle">
                            <i class="ri-car-line me-1 text-primary"></i>
                            <strong>{{ currentServiceVehicle }}</strong>
                        </div>
                        <div v-if="currentServiceDrivers">
                            <i class="ri-user-line me-1 text-success"></i>
                            <strong>{{ currentServiceDrivers }}</strong>
                        </div>
                        <div>
                            <i class="ri-time-line me-1 text-info"></i>
                            <small>{{ formatDateTime(form.vehicle_departure_datetime) }} - {{ formatDateTime(form.vehicle_return_datetime) }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sovrapposizioni con servizi -->
            <div v-if="serviceOverlaps.length > 0">
                <h6 class="mb-2">Servizi in sovrapposizione:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Servizio</th>
                                <th>Tipo Sovrapposizione</th>
                                <th>Risorsa Sovrapposta</th>
                                <th>Periodo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(overlap, index) in serviceOverlaps" :key="'svc-' + index">
                                <td>
                                    <strong>{{ overlap.overlapping_service_reference || '#' + overlap.overlapping_service_id }}</strong>
                                </td>
                                <td>
                                    <span v-if="overlap.overlap_type === 'vehicle'" class="badge bg-info">Veicolo</span>
                                    <span v-else-if="overlap.overlap_type === 'driver'" class="badge bg-warning">Autista</span>
                                    <span v-else-if="overlap.overlap_type === 'both'" class="badge bg-danger">Veicolo + Autista</span>
                                </td>
                                <td>
                                    <div v-if="overlap.vehicle_plate" class="text-info mb-1">
                                        <i class="ri-car-line me-1"></i>
                                        <strong>{{ overlap.vehicle_plate }}</strong>
                                        <small class="text-muted ms-1">{{ overlap.vehicle_brand }} {{ overlap.vehicle_model }}</small>
                                    </div>
                                    <div v-if="overlap.driver_name" class="text-warning">
                                        <i class="ri-user-line me-1"></i>
                                        <strong>{{ overlap.driver_name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        {{ formatDateTime(overlap.service_departure) }} -
                                        {{ formatDateTime(overlap.service_return) }}
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Conflitti con indisponibilità -->
            <div v-if="unavailabilityConflicts.length > 0">
                <h6 class="mb-2 text-danger">
                    <i class="ri-error-warning-line me-1"></i>Conflitti con periodi di indisponibilità:
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Risorsa</th>
                                <th>Motivo</th>
                                <th>Periodo Indisponibilità</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(conflict, index) in unavailabilityConflicts" :key="'unavail-' + index" class="table-danger">
                                <td>
                                    <span v-if="conflict.overlap_type === 'vehicle_unavailability'" class="badge bg-secondary">
                                        <i class="ri-car-line me-1"></i>Veicolo
                                    </span>
                                    <span v-else-if="conflict.overlap_type === 'driver_unavailability'" class="badge bg-danger">
                                        <i class="ri-user-line me-1"></i>Autista
                                    </span>
                                </td>
                                <td>
                                    <div v-if="conflict.vehicle_plate">
                                        <strong>{{ conflict.vehicle_plate }}</strong>
                                        <small class="text-muted ms-1">{{ conflict.vehicle_brand }} {{ conflict.vehicle_model }}</small>
                                    </div>
                                    <div v-if="conflict.driver_name">
                                        <strong>{{ conflict.driver_name }}</strong>
                                    </div>
                                </td>
                                <td>{{ conflict.unavailability_reason }}</td>
                                <td>
                                    <small>
                                        {{ conflict.unavailability_start }} - {{ conflict.unavailability_end }}
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-soft-secondary" @click="cancelOverlapConfirmation">
                    Annulla
                </button>
                <button type="button" class="btn btn-warning" @click="confirmOverlapsAndSave">
                    <i class="ri-check-line me-1"></i>
                    Conferma e Salva
                </button>
            </div>
        </BModal>

        <!-- Modal: Nuovo Committente -->
        <BModal
            v-model="showNewCommittenteModal"
            title="Nuovo Committente"
            size="lg"
            hide-footer
            @hidden="resetCommittenteForm"
        >
            <form @submit.prevent="saveNewCommittente">
                <BRow>
                    <!-- Cognome (o Nome Azienda) -->
                    <BCol md="6" class="mb-3">
                        <label for="new_committente_surname" class="form-label">Cognome (o Nome Azienda) *</label>
                        <input
                            id="new_committente_surname"
                            v-model="newCommittenteForm.surname"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newCommittenteErrors.surname }"
                            placeholder="Cognome o Nome Azienda"
                            required
                        />
                        <small v-if="newCommittenteErrors.surname" class="text-danger d-block mt-1">
                            {{ newCommittenteErrors.surname[0] }}
                        </small>
                    </BCol>

                    <!-- Nome -->
                    <BCol md="6" class="mb-3">
                        <label for="new_committente_name" class="form-label">Nome</label>
                        <input
                            id="new_committente_name"
                            v-model="newCommittenteForm.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newCommittenteErrors.name }"
                            placeholder="Nome"
                        />
                        <small v-if="newCommittenteErrors.name" class="text-danger d-block mt-1">
                            {{ newCommittenteErrors.name[0] }}
                        </small>
                    </BCol>

                    <!-- Telefono -->
                    <BCol md="6" class="mb-3">
                        <label for="new_committente_phone" class="form-label">Telefono</label>
                        <input
                            id="new_committente_phone"
                            v-model="newCommittenteForm.phone"
                            type="tel"
                            class="form-control"
                            :class="{ 'is-invalid': newCommittenteErrors.phone }"
                            placeholder="+39 123 4567890"
                        />
                        <small v-if="newCommittenteErrors.phone" class="text-danger d-block mt-1">
                            {{ newCommittenteErrors.phone[0] }}
                        </small>
                    </BCol>
                </BRow>

                <!-- Dati di sistema (collapsible) -->
                <div class="border-top pt-3 mt-3">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                        style="cursor: pointer;"
                        @click="showSystemDataSection = !showSystemDataSection"
                    >
                        <h6 class="text-muted mb-0">
                            <i class="ri-settings-3-line me-2"></i>Dati di sistema
                        </h6>
                        <i :class="showSystemDataSection ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-muted"></i>
                    </div>

                    <div v-show="showSystemDataSection">
                        <BRow>
                            <!-- Username -->
                            <BCol md="6" class="mb-3">
                                <label for="new_committente_username" class="form-label">Username *</label>
                                <input
                                    id="new_committente_username"
                                    v-model="newCommittenteForm.username"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': newCommittenteErrors.username }"
                                    placeholder="username"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newCommittenteErrors.username" class="text-danger d-block mt-1">
                                    {{ newCommittenteErrors.username[0] }}
                                </small>
                            </BCol>

                            <!-- Email -->
                            <BCol md="6" class="mb-3">
                                <label for="new_committente_email" class="form-label">Email *</label>
                                <input
                                    id="new_committente_email"
                                    v-model="newCommittenteForm.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': newCommittenteErrors.email }"
                                    placeholder="email@example.com"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newCommittenteErrors.email" class="text-danger d-block mt-1">
                                    {{ newCommittenteErrors.email[0] }}
                                </small>
                            </BCol>

                            <!-- Password -->
                            <BCol md="6" class="mb-3">
                                <label for="new_committente_password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_committente_password"
                                        v-model="newCommittenteForm.password"
                                        :type="showNewCommittentePassword ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newCommittenteErrors.password }"
                                        placeholder="Inserisci una password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewCommittentePassword = !showNewCommittentePassword"
                                    >
                                        <i :class="showNewCommittentePassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">Password di default</small>
                                <small v-if="newCommittenteErrors.password" class="text-danger d-block mt-1">
                                    {{ newCommittenteErrors.password[0] }}
                                </small>
                            </BCol>

                            <!-- Password Confirmation -->
                            <BCol md="6" class="mb-3">
                                <label for="new_committente_password_confirmation" class="form-label">Conferma Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_committente_password_confirmation"
                                        v-model="newCommittenteForm.password_confirmation"
                                        :type="showNewCommittentePasswordConfirmation ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newCommittenteErrors.password_confirmation }"
                                        placeholder="Conferma la password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewCommittentePasswordConfirmation = !showNewCommittentePasswordConfirmation"
                                    >
                                        <i :class="showNewCommittentePasswordConfirmation ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small v-if="newCommittenteErrors.password_confirmation" class="text-danger d-block mt-1">
                                    {{ newCommittenteErrors.password_confirmation[0] }}
                                </small>
                            </BCol>
                        </BRow>
                    </div>
                </div>

                <!-- Info Message -->
                <div class="alert alert-info mb-3 mt-3">
                    <i class="ri-information-line me-2"></i>
                    Sarà creato un utente committente non attivo. Potrai aggiungere maggiori dati dalla sezione Utenti. Per permettere all'utente l'ingresso in easyNCC potrai attivarlo dalla sezione Utenti dopo la verifica dei dati.
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary" @click="showNewCommittenteModal = false">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="savingNewCommittente">
                        <span v-if="savingNewCommittente" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="ri-add-line me-1"></i>
                        Crea Committente
                    </button>
                </div>
            </form>
        </BModal>

        <!-- Modal: Nuovo Intermediario -->
        <BModal
            v-model="showNewIntermediarioModal"
            title="Nuovo Intermediario"
            size="lg"
            hide-footer
            @hidden="resetIntermediarioForm"
        >
            <form @submit.prevent="saveNewIntermediario">
                <BRow>
                    <!-- Cognome (o Nome Azienda) -->
                    <BCol md="6" class="mb-3">
                        <label for="new_intermediario_surname" class="form-label">Cognome (o Nome Azienda) *</label>
                        <input
                            id="new_intermediario_surname"
                            v-model="newIntermediarioForm.surname"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newIntermediarioErrors.surname }"
                            placeholder="Cognome o Nome Azienda"
                            required
                        />
                        <small v-if="newIntermediarioErrors.surname" class="text-danger d-block mt-1">
                            {{ newIntermediarioErrors.surname[0] }}
                        </small>
                    </BCol>

                    <!-- Nome -->
                    <BCol md="6" class="mb-3">
                        <label for="new_intermediario_name" class="form-label">Nome</label>
                        <input
                            id="new_intermediario_name"
                            v-model="newIntermediarioForm.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newIntermediarioErrors.name }"
                            placeholder="Nome"
                        />
                        <small v-if="newIntermediarioErrors.name" class="text-danger d-block mt-1">
                            {{ newIntermediarioErrors.name[0] }}
                        </small>
                    </BCol>

                    <!-- Telefono -->
                    <BCol md="6" class="mb-3">
                        <label for="new_intermediario_phone" class="form-label">Telefono</label>
                        <input
                            id="new_intermediario_phone"
                            v-model="newIntermediarioForm.phone"
                            type="tel"
                            class="form-control"
                            :class="{ 'is-invalid': newIntermediarioErrors.phone }"
                            placeholder="+39 123 4567890"
                        />
                        <small v-if="newIntermediarioErrors.phone" class="text-danger d-block mt-1">
                            {{ newIntermediarioErrors.phone[0] }}
                        </small>
                    </BCol>
                </BRow>

                <!-- Dati di sistema (collapsible) -->
                <div class="border-top pt-3 mt-3">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                        style="cursor: pointer;"
                        @click="showIntermediarioSystemDataSection = !showIntermediarioSystemDataSection"
                    >
                        <h6 class="text-muted mb-0">
                            <i class="ri-settings-3-line me-2"></i>Dati di sistema
                        </h6>
                        <i :class="showIntermediarioSystemDataSection ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-muted"></i>
                    </div>

                    <div v-show="showIntermediarioSystemDataSection">
                        <BRow>
                            <!-- Username -->
                            <BCol md="6" class="mb-3">
                                <label for="new_intermediario_username" class="form-label">Username *</label>
                                <input
                                    id="new_intermediario_username"
                                    v-model="newIntermediarioForm.username"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': newIntermediarioErrors.username }"
                                    placeholder="username"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newIntermediarioErrors.username" class="text-danger d-block mt-1">
                                    {{ newIntermediarioErrors.username[0] }}
                                </small>
                            </BCol>

                            <!-- Email -->
                            <BCol md="6" class="mb-3">
                                <label for="new_intermediario_email" class="form-label">Email *</label>
                                <input
                                    id="new_intermediario_email"
                                    v-model="newIntermediarioForm.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': newIntermediarioErrors.email }"
                                    placeholder="email@example.com"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newIntermediarioErrors.email" class="text-danger d-block mt-1">
                                    {{ newIntermediarioErrors.email[0] }}
                                </small>
                            </BCol>

                            <!-- Password -->
                            <BCol md="6" class="mb-3">
                                <label for="new_intermediario_password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_intermediario_password"
                                        v-model="newIntermediarioForm.password"
                                        :type="showNewIntermediarioPassword ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newIntermediarioErrors.password }"
                                        placeholder="Inserisci una password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewIntermediarioPassword = !showNewIntermediarioPassword"
                                    >
                                        <i :class="showNewIntermediarioPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">Password di default</small>
                                <small v-if="newIntermediarioErrors.password" class="text-danger d-block mt-1">
                                    {{ newIntermediarioErrors.password[0] }}
                                </small>
                            </BCol>

                            <!-- Password Confirmation -->
                            <BCol md="6" class="mb-3">
                                <label for="new_intermediario_password_confirmation" class="form-label">Conferma Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_intermediario_password_confirmation"
                                        v-model="newIntermediarioForm.password_confirmation"
                                        :type="showNewIntermediarioPasswordConfirmation ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newIntermediarioErrors.password_confirmation }"
                                        placeholder="Conferma la password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewIntermediarioPasswordConfirmation = !showNewIntermediarioPasswordConfirmation"
                                    >
                                        <i :class="showNewIntermediarioPasswordConfirmation ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small v-if="newIntermediarioErrors.password_confirmation" class="text-danger d-block mt-1">
                                    {{ newIntermediarioErrors.password_confirmation[0] }}
                                </small>
                            </BCol>
                        </BRow>
                    </div>
                </div>

                <!-- Info Message -->
                <div class="alert alert-info mb-3 mt-3">
                    <i class="ri-information-line me-2"></i>
                    Sarà creato un utente intermediario non attivo. Potrai aggiungere maggiori dati dalla sezione Utenti. Per permettere all'utente l'ingresso in easyNCC potrai attivarlo dalla sezione Utenti dopo la verifica dei dati.
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary" @click="showNewIntermediarioModal = false">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="savingNewIntermediario">
                        <span v-if="savingNewIntermediario" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="ri-add-line me-1"></i>
                        Crea Intermediario
                    </button>
                </div>
            </form>
        </BModal>

        <!-- Modal: Nuovo Collega -->
        <BModal
            v-model="showNewFornitoreModal"
            title="Nuovo Collega"
            size="lg"
            hide-footer
            @hidden="resetFornitoreForm"
        >
            <form @submit.prevent="saveNewFornitore">
                <BRow>
                    <!-- Cognome (o Nome Azienda) -->
                    <BCol md="6" class="mb-3">
                        <label for="new_fornitore_surname" class="form-label">Cognome (o Nome Azienda) *</label>
                        <input
                            id="new_fornitore_surname"
                            v-model="newFornitoreForm.surname"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newFornitoreErrors.surname }"
                            placeholder="Cognome o Nome Azienda"
                            required
                        />
                        <small v-if="newFornitoreErrors.surname" class="text-danger d-block mt-1">
                            {{ newFornitoreErrors.surname[0] }}
                        </small>
                    </BCol>

                    <!-- Nome -->
                    <BCol md="6" class="mb-3">
                        <label for="new_fornitore_name" class="form-label">Nome</label>
                        <input
                            id="new_fornitore_name"
                            v-model="newFornitoreForm.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': newFornitoreErrors.name }"
                            placeholder="Nome"
                        />
                        <small v-if="newFornitoreErrors.name" class="text-danger d-block mt-1">
                            {{ newFornitoreErrors.name[0] }}
                        </small>
                    </BCol>

                    <!-- Telefono -->
                    <BCol md="6" class="mb-3">
                        <label for="new_fornitore_phone" class="form-label">Telefono</label>
                        <input
                            id="new_fornitore_phone"
                            v-model="newFornitoreForm.phone"
                            type="tel"
                            class="form-control"
                            :class="{ 'is-invalid': newFornitoreErrors.phone }"
                            placeholder="+39 123 4567890"
                        />
                        <small v-if="newFornitoreErrors.phone" class="text-danger d-block mt-1">
                            {{ newFornitoreErrors.phone[0] }}
                        </small>
                    </BCol>
                </BRow>

                <!-- Dati di sistema (collapsible) -->
                <div class="border-top pt-3 mt-3">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                        style="cursor: pointer;"
                        @click="showFornitoreSystemDataSection = !showFornitoreSystemDataSection"
                    >
                        <h6 class="text-muted mb-0">
                            <i class="ri-settings-3-line me-2"></i>Dati di sistema
                        </h6>
                        <i :class="showFornitoreSystemDataSection ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-muted"></i>
                    </div>

                    <div v-show="showFornitoreSystemDataSection">
                        <BRow>
                            <!-- Username -->
                            <BCol md="6" class="mb-3">
                                <label for="new_fornitore_username" class="form-label">Username *</label>
                                <input
                                    id="new_fornitore_username"
                                    v-model="newFornitoreForm.username"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': newFornitoreErrors.username }"
                                    placeholder="username"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newFornitoreErrors.username" class="text-danger d-block mt-1">
                                    {{ newFornitoreErrors.username[0] }}
                                </small>
                            </BCol>

                            <!-- Email -->
                            <BCol md="6" class="mb-3">
                                <label for="new_fornitore_email" class="form-label">Email *</label>
                                <input
                                    id="new_fornitore_email"
                                    v-model="newFornitoreForm.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': newFornitoreErrors.email }"
                                    placeholder="email@example.com"
                                    required
                                    readonly
                                />
                                <small class="text-muted d-block mt-1">Generato automaticamente</small>
                                <small v-if="newFornitoreErrors.email" class="text-danger d-block mt-1">
                                    {{ newFornitoreErrors.email[0] }}
                                </small>
                            </BCol>

                            <!-- Password -->
                            <BCol md="6" class="mb-3">
                                <label for="new_fornitore_password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_fornitore_password"
                                        v-model="newFornitoreForm.password"
                                        :type="showNewFornitorePassword ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newFornitoreErrors.password }"
                                        placeholder="Inserisci una password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewFornitorePassword = !showNewFornitorePassword"
                                    >
                                        <i :class="showNewFornitorePassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">Password di default</small>
                                <small v-if="newFornitoreErrors.password" class="text-danger d-block mt-1">
                                    {{ newFornitoreErrors.password[0] }}
                                </small>
                            </BCol>

                            <!-- Password Confirmation -->
                            <BCol md="6" class="mb-3">
                                <label for="new_fornitore_password_confirmation" class="form-label">Conferma Password *</label>
                                <div class="input-group">
                                    <input
                                        id="new_fornitore_password_confirmation"
                                        v-model="newFornitoreForm.password_confirmation"
                                        :type="showNewFornitorePasswordConfirmation ? 'text' : 'password'"
                                        class="form-control"
                                        :class="{ 'is-invalid': newFornitoreErrors.password_confirmation }"
                                        placeholder="Conferma la password"
                                        required
                                        readonly
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        @click="showNewFornitorePasswordConfirmation = !showNewFornitorePasswordConfirmation"
                                    >
                                        <i :class="showNewFornitorePasswordConfirmation ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                <small v-if="newFornitoreErrors.password_confirmation" class="text-danger d-block mt-1">
                                    {{ newFornitoreErrors.password_confirmation[0] }}
                                </small>
                            </BCol>
                        </BRow>
                    </div>
                </div>

                <!-- Info Message -->
                <div class="alert alert-info mb-3 mt-3">
                    <i class="ri-information-line me-2"></i>
                    Sarà creato un utente collega non attivo. Potrai aggiungere maggiori dati dalla sezione Utenti. Per permettere all'utente l'ingresso in easyNCC potrai attivarlo dalla sezione Utenti dopo la verifica dei dati.
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary" @click="showNewFornitoreModal = false">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="savingNewFornitore">
                        <span v-if="savingNewFornitore" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="ri-add-line me-1"></i>
                        Crea Collega
                    </button>
                </div>
            </form>
        </BModal>
        <!-- Flight Tracking Modal -->
        <BModal v-model="showFlightModal" title="Informazioni Volo" size="lg" hide-footer>
            <div v-if="flightData">
                <!-- Header: volo + stato -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-flight-takeoff-line me-2"></i>
                            {{ flightData.flight?.iata }}
                            <small class="text-muted ms-2">{{ flightData.airline?.name }}</small>
                        </h5>
                        <small class="text-muted">{{ flightData.flight_date }}</small>
                    </div>
                    <span class="badge px-3 py-2" :class="getFlightStatusBadge(flightData.flight_status).class" style="font-size: 0.9rem;">
                        {{ getFlightStatusBadge(flightData.flight_status).label }}
                    </span>
                </div>

                <!-- Partenza e Arrivo -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body p-3">
                                <div class="text-success fw-bold mb-2"><i class="ri-flight-takeoff-line me-1"></i>Partenza</div>
                                <div class="fw-bold">{{ flightData.departure?.airport }}</div>
                                <div class="text-muted small">{{ flightData.departure?.iata }}
                                    <span v-if="flightData.departure?.terminal"> · Terminal {{ flightData.departure.terminal }}</span>
                                    <span v-if="flightData.departure?.gate"> · Gate {{ flightData.departure.gate }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="small">
                                    <div><strong>Previsto:</strong> {{ formatFlightTime(flightData.departure?.scheduled) }}</div>
                                    <div v-if="flightData.departure?.estimated"><strong>Stimato:</strong> {{ formatFlightTime(flightData.departure.estimated) }}</div>
                                    <div v-if="flightData.departure?.actual"><strong>Effettivo:</strong> {{ formatFlightTime(flightData.departure.actual) }}</div>
                                    <div v-if="flightData.departure?.delay" class="text-danger fw-bold mt-1">
                                        <i class="ri-time-line me-1"></i>Ritardo: {{ flightData.departure.delay }} min
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body p-3">
                                <div class="text-danger fw-bold mb-2"><i class="ri-flight-land-line me-1"></i>Arrivo</div>
                                <div class="fw-bold">{{ flightData.arrival?.airport }}</div>
                                <div class="text-muted small">{{ flightData.arrival?.iata }}
                                    <span v-if="flightData.arrival?.terminal"> · Terminal {{ flightData.arrival.terminal }}</span>
                                    <span v-if="flightData.arrival?.gate"> · Gate {{ flightData.arrival.gate }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="small">
                                    <div><strong>Previsto:</strong> {{ formatFlightTime(flightData.arrival?.scheduled) }}</div>
                                    <div v-if="flightData.arrival?.estimated"><strong>Stimato:</strong> {{ formatFlightTime(flightData.arrival.estimated) }}</div>
                                    <div v-if="flightData.arrival?.actual"><strong>Effettivo:</strong> {{ formatFlightTime(flightData.arrival.actual) }}</div>
                                    <div v-if="flightData.arrival?.delay" class="text-danger fw-bold mt-1">
                                        <i class="ri-time-line me-1"></i>Ritardo: {{ flightData.arrival.delay }} min
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </BModal>

        <!-- Extra Revenue Modal -->
        <BModal v-model="showExtraRevenueModal" :title="extraRevenueForm.editIndex !== null ? 'Modifica Ricavo Extra' : 'Nuovo Ricavo Extra'" size="lg" hide-footer>
            <form @submit.prevent="saveExtraRevenue">
                <BRow>
                    <BCol md="12" class="mb-3">
                        <label class="form-label">Descrizione <span class="text-danger">*</span></label>
                        <input v-model="extraRevenueForm.description" type="text" class="form-control" required maxlength="255" />
                    </BCol>
                </BRow>
                <BRow>
                    <BCol md="4" class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="radio" v-model="extraRevenueForm.sale_type" value="amount_taxable" class="form-check-input mt-0" />
                            <span :class="{ 'fw-semibold text-primary': extraRevenueForm.sale_type === 'amount_taxable' }">Imponibile €</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input
                                v-model.number="extraRevenueForm.amount_taxable"
                                type="number" step="0.01" min="0" class="form-control"
                                :class="{ 'border-primary': extraRevenueForm.sale_type === 'amount_taxable' }"
                                @input="calcExtraFromTaxable"
                                required
                            />
                        </div>
                    </BCol>
                    <BCol md="4" class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="radio" v-model="extraRevenueForm.sale_type" value="amount_handling" class="form-check-input mt-0" />
                            <span :class="{ 'fw-semibold text-primary': extraRevenueForm.sale_type === 'amount_handling' }">Bonifico Bancario €</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input
                                v-model.number="extraRevenueForm.amount_handling"
                                type="number" step="0.01" min="0" class="form-control"
                                :class="{ 'border-primary': extraRevenueForm.sale_type === 'amount_handling' }"
                            />
                        </div>
                    </BCol>
                    <BCol md="4" class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <input type="radio" v-model="extraRevenueForm.sale_type" value="amount_card" class="form-check-input mt-0" />
                            <span :class="{ 'fw-semibold text-primary': extraRevenueForm.sale_type === 'amount_card' }">Carta Credito €</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input
                                v-model.number="extraRevenueForm.amount_card"
                                type="number" step="0.01" min="0" class="form-control"
                                :class="{ 'border-primary': extraRevenueForm.sale_type === 'amount_card' }"
                            />
                        </div>
                    </BCol>
                </BRow>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" @click="showExtraRevenueModal = false">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        {{ extraRevenueForm.editIndex !== null ? 'Aggiorna' : 'Aggiungi' }}
                    </button>
                </div>
            </form>
        </BModal>

        <!-- Reverse Calculate Modal -->
        <BModal v-model="showReverseCalcModal" title="Calcola Imponibile dal Prezzo Finale" size="md" hide-footer>
            <BRow class="mb-3">
                <BCol md="7">
                    <label class="form-label fw-semibold">Prezzo Finale €</label>
                    <div class="input-group">
                        <span class="input-group-text">€</span>
                        <input
                            v-model.number="reverseCalc.finalPrice"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            placeholder="0.00"
                            @input="computeReverseCalc"
                        />
                    </div>
                </BCol>
                <BCol md="5">
                    <label class="form-label fw-semibold">Tipo Prezzo</label>
                    <div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="rev_type_card" v-model="reverseCalc.type" value="card" @change="computeReverseCalc" />
                            <label class="form-check-label" for="rev_type_card">Carta Credito</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="rev_type_handling" v-model="reverseCalc.type" value="handling" @change="computeReverseCalc" />
                            <label class="form-check-label" for="rev_type_handling">Bonifico Bancario</label>
                        </div>
                    </div>
                </BCol>
            </BRow>

            <BRow class="mb-3">
                <BCol md="6">
                    <label class="form-label">IVA %</label>
                    <input v-model.number="reverseCalc.vatRate" type="number" step="0.01" min="0" class="form-control form-control-sm" @input="computeReverseCalc" />
                </BCol>
                <BCol md="6">
                    <label class="form-label">Card Fees %</label>
                    <input v-model.number="reverseCalc.cardFeesPerc" type="number" step="0.01" min="0" class="form-control form-control-sm" @input="computeReverseCalc" />
                </BCol>
            </BRow>

            <div v-if="reverseCalc.result !== null" class="border rounded p-3 bg-light mb-3">
                <div class="mb-2">
                    <span class="text-muted">Imponibile calcolato:</span>
                    <span class="fw-bold fs-5 text-primary ms-2">€ {{ reverseCalc.result.toFixed(2) }}</span>
                </div>
                <hr class="my-2">
                <div class="small text-muted">Verifica:</div>
                <div class="d-flex justify-content-between small">
                    <span>Bonifico Bancario:</span>
                    <span class="fw-medium">€ {{ reverseCalc.verifyHandling.toFixed(2) }}</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Carta Credito:</span>
                    <span class="fw-medium">€ {{ reverseCalc.verifyCard.toFixed(2) }}</span>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="showReverseCalcModal = false">Annulla</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="!reverseCalc.result"
                    @click="applyReverseCalcResult"
                >
                    <i class="ri-check-line me-1"></i>Usa questo valore
                </button>
            </div>
        </BModal>

        <!-- Email Notification Modal -->
        <BModal
            v-model="showEmailModal"
            title="Invia Notifica Email al Collega"
            size="xl"
            hide-footer
            @hidden="closeEmailModal"
        >
            <div v-if="emailModalData">
                <BRow class="mb-3">
                    <BCol md="6">
                        <label class="form-label fw-bold">Destinatario</label>
                        <input
                            v-model="emailModalData.to"
                            type="email"
                            class="form-control"
                            readonly
                        />
                    </BCol>
                    <BCol md="6">
                        <label class="form-label fw-bold">Oggetto</label>
                        <input
                            v-model="emailModalData.subject"
                            type="text"
                            class="form-control"
                        />
                    </BCol>
                </BRow>

                <div class="mb-3">
                    <label class="form-label fw-bold">Corpo Email</label>
                    <div class="border rounded p-2" style="min-height: 300px;">
                        <div v-html="emailModalData.body" contenteditable="true" @input="onEmailBodyEdit" class="email-body-editor" style="min-height: 280px; outline: none;"></div>
                    </div>
                    <small class="text-muted">Il foglio di servizio PDF verrà allegato automaticamente.</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" @click="closeEmailModal" :disabled="emailSending">
                        Annulla
                    </button>
                    <button type="button" class="btn btn-primary" @click="sendServiceEmail" :disabled="emailSending">
                        <span v-if="emailSending" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-send-plane-line me-1"></i>
                        Invia Email
                    </button>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import { useNotify } from '@/composables/useNotify.js';
import ServiceAttachments from '@/Components/ProfileFields/ServiceAttachments.vue';
import AddressMapInput from '@/Components/AddressMapInput.vue';
import { driverLabel } from '@/composables/useDriverLabel.js';
import { VueDraggableNext } from 'vue-draggable-next';
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

const props = defineProps({
    service: {
        type: Object,
        default: null
    }
});

const notify = useNotify();

const isEdit = computed(() => !!props.service);

const scrollToSection = (sectionId) => {
    const el = document.getElementById(sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
const loading = ref(false);
const submitting = ref(false);
const exitAfterSave = ref(true); // Default: Salva ed Esci
const pendingExitAfterSave = ref(true); // Store exit preference when overlaps are detected
const returnUrl = ref(new URLSearchParams(window.location.search).get('returnUrl') || '');
const showActivityModal = ref(false);
const showTransactionModal = ref(false);
const showTaskModal = ref(false);
const showOverlapModal = ref(false);
// Email notification modal
const showEmailModal = ref(false);
const emailModalData = ref({ subject: '', body: '', to: '', gmail_account_id: null, token: '' });
const emailSending = ref(false);
const pendingEmailServiceId = ref(null);
const detectedOverlaps = ref([]);

// Computed: split detected overlaps into service overlaps and unavailability conflicts
const serviceOverlaps = computed(() => {
    return detectedOverlaps.value.filter(o => !['driver_unavailability', 'vehicle_unavailability'].includes(o.overlap_type));
});
const unavailabilityConflicts = computed(() => {
    return detectedOverlaps.value.filter(o => ['driver_unavailability', 'vehicle_unavailability'].includes(o.overlap_type));
});

const showNewCommittenteModal = ref(false);
const savingNewCommittente = ref(false);
const showNewCommittentePassword = ref(false);
const showNewCommittentePasswordConfirmation = ref(false);
const showNewIntermediarioModal = ref(false);
const savingNewIntermediario = ref(false);
const showNewIntermediarioPassword = ref(false);
const showNewIntermediarioPasswordConfirmation = ref(false);
const showNewFornitoreModal = ref(false);
const savingNewFornitore = ref(false);
const showNewFornitorePassword = ref(false);
const showNewFornitorePasswordConfirmation = ref(false);

// Current user and companies
const currentUser = ref(null);
const companies = ref([]);

// Lists
const committentiKey = ref(0);
const intermediariKey = ref(0);
const fornitoriKey = ref(0);
const committenti = ref([]);
const committentiLoaded = ref(false);
const committentiLoading = ref(false);
const intermediari = ref([]);
const intermediariLoaded = ref(false);
const intermediariLoading = ref(false);
const fornitori = ref([]);
const fornitoriLoaded = ref(false);
const fornitoriLoading = ref(false);
const vehicles = ref([]);
const drivers = ref([]);
const dressCodes = ref([]);
const serviceStatuses = ref([]);
const serviceTypes = ref([]);
const activityTypes = ref([]);
const activityPaymentTypes = ref([]);

// Reverse price calculator
const showReverseCalcModal = ref(false);
const reverseCalc = ref({
    finalPrice: 0,
    type: 'card',
    vatRate: 10,
    cardFeesPerc: 5,
    result: null,
    verifyHandling: 0,
    verifyCard: 0,
});

const openReverseCalcModal = () => {
    reverseCalc.value.vatRate = parseFloat(form.value.vat_rate) || 10;
    reverseCalc.value.cardFeesPerc = parseFloat(form.value.card_fees_percentage) || 5;
    reverseCalc.value.finalPrice = 0;
    reverseCalc.value.result = null;
    reverseCalc.value.verifyHandling = 0;
    reverseCalc.value.verifyCard = 0;
    showReverseCalcModal.value = true;
};

const computeReverseCalc = () => {
    const price = parseFloat(reverseCalc.value.finalPrice);
    if (!price || price <= 0) {
        reverseCalc.value.result = null;
        return;
    }

    const vatRate = parseFloat(reverseCalc.value.vatRate) || 0;
    const cardFeesPerc = parseFloat(reverseCalc.value.cardFeesPerc) || 0;

    let imponibile;
    if (reverseCalc.value.type === 'card') {
        // Prezzo = Imponibile × (1 + IVA/100) × (1 + CardFees/100)
        imponibile = price / (1 + vatRate / 100) / (1 + cardFeesPerc / 100);
    } else {
        // Prezzo = Imponibile × (1 + IVA/100)
        imponibile = price / (1 + vatRate / 100);
    }

    imponibile = Math.round(imponibile * 100) / 100;

    // Verifica
    const handling = Math.round(imponibile * (1 + vatRate / 100) * 100) / 100;
    const card = Math.round(imponibile * (1 + vatRate / 100) * (1 + cardFeesPerc / 100) * 100) / 100;

    reverseCalc.value.result = imponibile;
    reverseCalc.value.verifyHandling = handling;
    reverseCalc.value.verifyCard = card;
};

const applyReverseCalcResult = () => {
    if (reverseCalc.value.result) {
        form.value.service_price = reverseCalc.value.result;
        showReverseCalcModal.value = false;
    }
};

// Extra revenues
const showExtraRevenueModal = ref(false);
const extraRevenueForm = ref({
    description: '',
    amount_taxable: 0,
    amount_handling: 0,
    amount_card: 0,
    sale_type: 'amount_card',
    editIndex: null,
});

const extraRevenuesTotalFormatted = computed(() => {
    const total = (form.value.extra_revenues || []).reduce((sum, r) => sum + getExtraRevenueSelectedAmount(r), 0);
    return total.toFixed(2);
});

const openExtraRevenueModal = (extra = null, index = null) => {
    if (extra) {
        extraRevenueForm.value = {
            description: extra.description,
            amount_taxable: extra.amount_taxable || extra.amount || 0,
            amount_handling: extra.amount_handling || 0,
            amount_card: extra.amount_card || 0,
            sale_type: extra.sale_type || 'amount_card',
            editIndex: index,
        };
    } else {
        extraRevenueForm.value = {
            description: '',
            amount_taxable: 0,
            amount_handling: 0,
            amount_card: 0,
            sale_type: 'amount_card',
            editIndex: null,
        };
    }
    showExtraRevenueModal.value = true;
};

const calcExtraFromTaxable = () => {
    const taxable = parseFloat(extraRevenueForm.value.amount_taxable) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    const cardFeesPerc = parseFloat(form.value.card_fees_percentage) || 0;
    extraRevenueForm.value.amount_handling = Math.round(taxable * (1 + vatRate / 100) * 100) / 100;
    extraRevenueForm.value.amount_card = Math.round(taxable * (1 + vatRate / 100) * (1 + cardFeesPerc / 100) * 100) / 100;
};

const getExtraRevenueSelectedAmount = (extra) => {
    switch (extra.sale_type) {
        case 'amount_taxable': return parseFloat(extra.amount_taxable) || 0;
        case 'amount_handling': return parseFloat(extra.amount_handling) || 0;
        default: return parseFloat(extra.amount_card) || parseFloat(extra.amount) || 0;
    }
};

const getExtraRevenueSaleLabel = (saleType) => {
    switch (saleType) {
        case 'amount_taxable': return 'IMP';
        case 'amount_handling': return 'BB';
        default: return 'CC';
    }
};

const saveExtraRevenue = () => {
    if (!extraRevenueForm.value.description) return;
    const selectedAmount = extraRevenueForm.value.sale_type === 'amount_taxable'
        ? extraRevenueForm.value.amount_taxable
        : extraRevenueForm.value.sale_type === 'amount_handling'
            ? extraRevenueForm.value.amount_handling
            : extraRevenueForm.value.amount_card;
    if (!selectedAmount || selectedAmount <= 0) return;

    if (!form.value.extra_revenues) {
        form.value.extra_revenues = [];
    }

    const entry = {
        id: extraRevenueForm.value.editIndex !== null
            ? form.value.extra_revenues[extraRevenueForm.value.editIndex]?.id
            : 'new_' + Date.now(),
        description: extraRevenueForm.value.description,
        amount_taxable: parseFloat(extraRevenueForm.value.amount_taxable),
        amount_handling: parseFloat(extraRevenueForm.value.amount_handling),
        amount_card: parseFloat(extraRevenueForm.value.amount_card),
        sale_type: extraRevenueForm.value.sale_type,
        accounting_transaction_id: extraRevenueForm.value.editIndex !== null
            ? form.value.extra_revenues[extraRevenueForm.value.editIndex]?.accounting_transaction_id
            : null,
    };

    if (extraRevenueForm.value.editIndex !== null) {
        form.value.extra_revenues[extraRevenueForm.value.editIndex] = entry;
    } else {
        form.value.extra_revenues.push(entry);
    }

    showExtraRevenueModal.value = false;
};

const removeExtraRevenue = async (index) => {
    if (!confirm('Sei sicuro di voler rimuovere questo ricavo extra?')) return;
    const extra = form.value.extra_revenues[index];
    // Delete linked accounting transaction if exists
    if (extra?.accounting_transaction_id) {
        try {
            await axios.delete(`/api/accounting-transactions/${extra.accounting_transaction_id}`);
        } catch (err) {
            console.error('Error deleting extra revenue transaction:', err);
        }
    }
    form.value.extra_revenues.splice(index, 1);
};

// Flight tracking
const flightTracking = ref(false);
const flightData = ref(null);
const showFlightModal = ref(false);

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
    { code: 'LU', name: 'Lussemburgo', flag: '🇱🇺' },
    { code: 'MT', name: 'Malta', flag: '🇲🇹' },
    { code: 'CY', name: 'Cipro', flag: '🇨🇾' },
    { code: 'IS', name: 'Islanda', flag: '🇮🇸' },
    { code: 'AL', name: 'Albania', flag: '🇦🇱' },
    { code: 'RS', name: 'Serbia', flag: '🇷🇸' },
    { code: 'BA', name: 'Bosnia ed Erzegovina', flag: '🇧🇦' },
    { code: 'ME', name: 'Montenegro', flag: '🇲🇪' },
    { code: 'MK', name: 'Macedonia del Nord', flag: '🇲🇰' },
    { code: 'MD', name: 'Moldavia', flag: '🇲🇩' },
    { code: 'UA', name: 'Ucraina', flag: '🇺🇦' },
    { code: 'BY', name: 'Bielorussia', flag: '🇧🇾' },
    { code: 'GE', name: 'Georgia', flag: '🇬🇪' },
    { code: 'AM', name: 'Armenia', flag: '🇦🇲' },
    { code: 'AZ', name: 'Azerbaigian', flag: '🇦🇿' },
    { code: 'XK', name: 'Kosovo', flag: '🇽🇰' },
    { code: 'SM', name: 'San Marino', flag: '🇸🇲' },
    { code: 'MC', name: 'Monaco', flag: '🇲🇨' },
    { code: 'AD', name: 'Andorra', flag: '🇦🇩' },
    { code: 'LI', name: 'Liechtenstein', flag: '🇱🇮' },
    { code: 'CL', name: 'Cile', flag: '🇨🇱' },
    { code: 'CO', name: 'Colombia', flag: '🇨🇴' },
    { code: 'PE', name: 'Perù', flag: '🇵🇪' },
    { code: 'UY', name: 'Uruguay', flag: '🇺🇾' },
    { code: 'EC', name: 'Ecuador', flag: '🇪🇨' },
    { code: 'VE', name: 'Venezuela', flag: '🇻🇪' },
    { code: 'CR', name: 'Costa Rica', flag: '🇨🇷' },
    { code: 'PA', name: 'Panama', flag: '🇵🇦' },
    { code: 'CU', name: 'Cuba', flag: '🇨🇺' },
    { code: 'DO', name: 'Repubblica Dominicana', flag: '🇩🇴' },
    { code: 'QA', name: 'Qatar', flag: '🇶🇦' },
    { code: 'KW', name: 'Kuwait', flag: '🇰🇼' },
    { code: 'BH', name: 'Bahrein', flag: '🇧🇭' },
    { code: 'OM', name: 'Oman', flag: '🇴🇲' },
    { code: 'JO', name: 'Giordania', flag: '🇯🇴' },
    { code: 'LB', name: 'Libano', flag: '🇱🇧' },
    { code: 'PK', name: 'Pakistan', flag: '🇵🇰' },
    { code: 'BD', name: 'Bangladesh', flag: '🇧🇩' },
    { code: 'LK', name: 'Sri Lanka', flag: '🇱🇰' },
    { code: 'TW', name: 'Taiwan', flag: '🇹🇼' },
    { code: 'HK', name: 'Hong Kong', flag: '🇭🇰' },
    { code: 'TN', name: 'Tunisia', flag: '🇹🇳' },
    { code: 'GH', name: 'Ghana', flag: '🇬🇭' },
    { code: 'ET', name: 'Etiopia', flag: '🇪🇹' },
    { code: 'TZ', name: 'Tanzania', flag: '🇹🇿' },
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
].sort((a, b) => a.name.localeCompare(b.name)));

const countryOptions = computed(() =>
    countries.value.map(c => ({
        value: c.name,
        label: `${c.flag} ${c.name}`,
    }))
);

const countryNameToCode = computed(() => {
    const map = {};
    countries.value.forEach(c => { map[c.name] = c.code; });
    return map;
});

const countryCodeToName = computed(() => {
    const map = {};
    countries.value.forEach(c => { map[c.code] = c.name; });
    return map;
});

const getCountryCode = (nationality) => {
    if (!nationality) return null;
    return countryNameToCode.value[nationality] || null;
};

const onPhoneCountryChanged = (passenger, country) => {
    if (!country || !country.iso2) return;
    const countryName = countryCodeToName.value[country.iso2.toUpperCase()];
    if (countryName && !passenger.nationality) {
        passenger.nationality = countryName;
    }
};

// Tasks
const serviceTasks = ref([]);
const taskAssignableUsers = ref([]);
const taskSaving = ref(false);
const taskErrors = ref([]);
const activityConfirmationEnabled = ref(false);
const confirmationRoleUsers = ref([]);
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

// Computed for overlap modal - current service info
const currentServiceVehicle = computed(() => {
    if (!form.value.vehicle_id) return null;
    const vehicle = vehicles.value.find(v => v.id === form.value.vehicle_id);
    if (!vehicle) return null;
    return `${vehicle.license_plate} - ${vehicle.brand} ${vehicle.model}`;
});

const currentServiceDrivers = computed(() => {
    if (!form.value.driver_ids || form.value.driver_ids.length === 0) return null;
    const driverNames = form.value.driver_ids.map(id => {
        const driver = drivers.value.find(d => d.id === id);
        return driver ? driverLabel(driver) : null;
    }).filter(Boolean);
    return driverNames.join(', ');
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

const addDriverFromMultiselect = (driverId) => {
    if (driverId && !form.value.driver_ids.includes(driverId)) {
        form.value.driver_ids.push(driverId);
    }
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
    external_reference: '',
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
    driver_must_collect: false,
    service_price: null,
    vat_rate: 10,
    card_fees_percentage: 5,
    deposit_percentage: 30,
    deposit_taxable: null,
    deposit_handling_fees: null,
    deposit_amount: null,
    deposit_sale_type: 'deposit_card_fees',
    balance_taxable: null,
    balance_handling_fees: null,
    balance_card_fees: null,
    balance_sale_type: 'balance_taxable',
    intermediary_commission: null,
    driver_compensation: null,
    colleague_cost: null,
    fuel_cost: null,
    toll_cost: null,
    parking_cost: null,
    other_vehicle_costs: null,
    extra_revenues: [],
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
    should_account: false,
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
    notes: '',
    is_automatic: false,
});

// Selection and inline editing
const selectedTransactions = ref([]);
const editingTransactionStatus = ref(null);
const editingStatusValue = ref('');
const statusInputRefs = ref({});
const editingInstallment = ref(null);

// Bulk action state for transactions
const bulkPaymentDate = ref('');
const bulkPaymentType = ref('');
const bulkTransactionStatus = ref('');
const bulkApplyingTransactions = ref(false);

// Inline editing for activities
const editingActivityField = ref(null); // format: 'activityId_fieldName'
const editingActivityValue = ref(null);

const editingActivityTimeValue = ref('');

const startEditActivityField = async (activity, field) => {
    if (field === 'supplier_id') {
        await loadFornitoriLazy();
    }
    editingActivityField.value = `${activity.id}_${field}`;
    if (field === 'start_time' || field === 'end_time') {
        editingActivityTimeValue.value = activity[field] ? moment.utc(activity[field]).format('HH:mm') : '';
        editingActivityValue.value = activity[field] ? moment.utc(activity[field]).format('YYYY-MM-DDTHH:mm') : '';
    } else {
        editingActivityValue.value = activity[field] ?? '';
    }
};

const saveActivityTimeField = async (activity, field) => {
    // Combine time with service pickup date
    const serviceDate = form.value.pickup_datetime
        ? moment(form.value.pickup_datetime).format('YYYY-MM-DD')
        : moment().format('YYYY-MM-DD');
    const fullDatetime = editingActivityTimeValue.value
        ? `${serviceDate}T${editingActivityTimeValue.value}`
        : null;
    editingActivityValue.value = fullDatetime;
    await saveActivityField(activity, field);
};

const isEditingActivity = (activity, field) => {
    return editingActivityField.value === `${activity.id}_${field}`;
};

const saveActivityField = async (activity, field) => {
    try {
        const payload = {};
        payload[field] = editingActivityValue.value || null;
        await axios.put(`/api/activities/${activity.id}`, payload);
        // Update local
        if (field === 'start_time' || field === 'end_time') {
            activity[field] = editingActivityValue.value ? editingActivityValue.value : null;
        } else if (field === 'cost' || field === 'cost_per_person') {
            activity[field] = parseFloat(editingActivityValue.value) || 0;
        } else {
            activity[field] = editingActivityValue.value;
        }
        editingActivityField.value = null;
    } catch (err) {
        console.error('Error saving activity field:', err);
        editingActivityField.value = null;
    }
};

const cancelEditActivityField = () => {
    editingActivityField.value = null;
    editingActivityValue.value = null;
};

const saveActivityShouldAccount = async (activity, checked) => {
    activity.should_account = checked;
    try {
        await axios.put(`/api/activities/${activity.id}`, { should_account: checked });
    } catch (err) {
        console.error('Error saving should_account:', err);
        activity.should_account = !checked;
    }
};

const formatTimeHighlight = (datetime) => {
    if (!datetime) return { time: '-', date: '' };
    const m = moment.utc(datetime);
    return { time: m.format('HH:mm'), date: m.format('DD/MM/YYYY') };
};
const editingInstallmentValue = ref('');

// Additional lists for transaction modal
const accountingEntries = ref([]);
const paymentTypes = ref([]);
const transactionStatuses = ref([]);
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

// New Committente Form
const newCommittenteForm = ref({
    username: '',
    email: '',
    surname: '',
    name: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role: 'collaboratore',
    is_active: false,
    company_id: null,
    is_committente: true,
    is_fornitore: false,
    is_intermediario: false
});

const newCommittenteErrors = ref({});

// New Intermediario Form
const newIntermediarioForm = ref({
    username: '',
    email: '',
    surname: '',
    name: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role: 'collaboratore',
    is_active: false,
    company_id: null,
    is_committente: false,
    is_fornitore: false,
    is_intermediario: true
});

const newIntermediarioErrors = ref({});
const showIntermediarioSystemDataSection = ref(false);

// New Fornitore Form
const newFornitoreForm = ref({
    username: '',
    email: '',
    surname: '',
    name: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role: 'collaboratore',
    is_active: false,
    company_id: null,
    is_committente: false,
    is_fornitore: true,
    is_collega: true,
    is_intermediario: false
});

const newFornitoreErrors = ref({});
const showFornitoreSystemDataSection = ref(false);

// Computed
const selectedClientContact = computed(() => {
    if (!form.value.client_id) return '';
    const client = committenti.value.find(c => c.id === form.value.client_id);
    return client ? `${client.email || ''} ${client.phone || ''}`.trim() : '';
});

// Check if at least one passenger has some data filled in
const hasPassengerData = computed(() => {
    if (!form.value.passengers || form.value.passengers.length === 0) return false;
    const firstPassenger = form.value.passengers[0];
    return !!(firstPassenger.surname || firstPassenger.name || firstPassenger.phone || firstPassenger.email);
});

// Creating committente from passenger state
const creatingCommittenteFromPassenger = ref(false);

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
        // Exclude cancelled transactions from aggregates
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
    if (!form.value.accounting_transactions || form.value.accounting_transactions.length === 0) return [];
    const installmentOrder = { deposit: 0, extra: 1, balance: 2, supplier_refund: 3, customer_refund: 4 };
    const typeOrder = { sale: 0, intermediation: 1, purchase: 2 }; // DESC: sale first
    return [...form.value.accounting_transactions].sort((a, b) => {
        const instA = installmentOrder[a.installment] ?? 9;
        const instB = installmentOrder[b.installment] ?? 9;
        if (instA !== instB) return instA - instB;
        const typeA = typeOrder[a.transaction_type] ?? 9;
        const typeB = typeOrder[b.transaction_type] ?? 9;
        return typeA - typeB;
    });
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
        committentiLoaded.value = true;
    } catch (error) {
        console.error('Error loading counterparts:', error);
    }
};

// Injected options (used after creation to make them visible in async Multiselect)
const injectedCommittenteOption = ref(null);
const injectedIntermediarioOption = ref(null);
const injectedFornitoreOption = ref(null);

// Async search function for committenti
const searchCommittenti = async (query) => {
    if (!form.value.company_id) return [];

    committentiLoading.value = true;
    try {
        const params = {
            is_committente: 1,
            per_page: 30,
            company_id: isSuperAdmin.value ? form.value.company_id : undefined
        };

        // Add search parameter if query has 2+ characters
        if (query && query.length >= 2) {
            params.search = query;
        }

        const response = await axios.get('/api/users', { params });
        const users = response.data.data || [];

        // Map to options format
        let options = users.map(c => ({
            value: c.id,
            label: `${c.surname || ''} ${c.name || ''}`.trim() || c.email
        }));

        // If editing and current client is not in results, add it
        if (isEdit.value && props.service?.client && form.value.client_id) {
            const currentId = form.value.client_id;
            const exists = options.some(o => o.value === currentId);
            if (!exists) {
                const c = props.service.client;
                options.unshift({
                    value: c.id,
                    label: `${c.surname || ''} ${c.name || ''}`.trim() || c.email
                });
            }
        }

        // Include injected option (from create committente from passenger)
        if (injectedCommittenteOption.value) {
            const exists = options.some(o => o.value === injectedCommittenteOption.value.value);
            if (!exists) {
                options.unshift(injectedCommittenteOption.value);
            }
        }

        return options;
    } catch (error) {
        console.error('Error searching committenti:', error);
        return [];
    } finally {
        committentiLoading.value = false;
    }
};

// Load initial committenti when dropdown opens (for pre-populated list)
const loadCommittentiLazy = async () => {
    if (committentiLoaded.value || committentiLoading.value) return;
    if (!form.value.company_id) return;

    const options = await searchCommittenti('');
    committenti.value = options;
    committentiLoaded.value = true;
};

// Options for committenti Multiselect (used for initial load and selected value display)
const committentiOptions = computed(() => {
    return committenti.value;
});

// Async search function for intermediari
const searchIntermediari = async (query) => {
    if (!form.value.company_id) return [];

    intermediariLoading.value = true;
    try {
        const params = {
            is_intermediario: 1,
            per_page: 30,
            company_id: isSuperAdmin.value ? form.value.company_id : undefined
        };

        // Add search parameter if query has 2+ characters
        if (query && query.length >= 2) {
            params.search = query;
        }

        const response = await axios.get('/api/users', { params });
        const users = response.data.data || [];

        // Map to options format
        let options = users.map(i => ({
            value: i.id,
            label: `${i.surname || ''} ${i.name || ''}`.trim() || i.email
        }));

        // If editing and current intermediary is not in results, add it
        if (isEdit.value && props.service?.intermediary && form.value.intermediary_id) {
            const currentId = form.value.intermediary_id;
            const exists = options.some(o => o.value === currentId);
            if (!exists) {
                const i = props.service.intermediary;
                options.unshift({
                    value: i.id,
                    label: `${i.surname || ''} ${i.name || ''}`.trim() || i.email
                });
            }
        }

        // Include injected option
        if (injectedIntermediarioOption.value) {
            const exists = options.some(o => o.value === injectedIntermediarioOption.value.value);
            if (!exists) {
                options.unshift(injectedIntermediarioOption.value);
            }
        }

        return options;
    } catch (error) {
        console.error('Error searching intermediari:', error);
        return [];
    } finally {
        intermediariLoading.value = false;
    }
};

// Load initial intermediari when dropdown opens
const loadIntermediariLazy = async () => {
    if (intermediariLoaded.value || intermediariLoading.value) return;
    if (!form.value.company_id) return;

    const options = await searchIntermediari('');
    intermediari.value = options;
    intermediariLoaded.value = true;
};

// Options for intermediari Multiselect
const intermediariOptions = computed(() => {
    return intermediari.value;
});

// Async search function for fornitori
const searchFornitori = async (query) => {
    if (!form.value.company_id) return [];

    fornitoriLoading.value = true;
    try {
        const params = {
            is_fornitore: 1,
            is_collega: 1,
            per_page: 30,
            company_id: isSuperAdmin.value ? form.value.company_id : undefined
        };

        // Add search parameter if query has 2+ characters
        if (query && query.length >= 2) {
            params.search = query;
        }

        const response = await axios.get('/api/users', { params });
        const users = response.data.data || [];

        // Map to options format
        let options = users.map(f => ({
            value: f.id,
            label: `${f.surname || ''} ${f.name || ''}`.trim() || f.email
        }));

        // If current supplier is not in results, add it
        if (form.value.supplier_id) {
            const currentId = form.value.supplier_id;
            const exists = options.some(o => o.value === currentId);
            if (!exists) {
                // In edit mode, use service supplier data
                if (isEdit.value && props.service?.supplier) {
                    const s = props.service.supplier;
                    options.unshift({
                        value: s.id,
                        label: `${s.surname || ''} ${s.name || ''}`.trim() || s.email
                    });
                }
                // In new mode, use default supplier from settings
                else if (!isEdit.value && settings.value?.default_supplier && settings.value.default_supplier.id === currentId) {
                    const s = settings.value.default_supplier;
                    options.unshift({
                        value: s.id,
                        label: `${s.surname || ''} ${s.name || ''}`.trim() || s.email
                    });
                }
            }
        }

        // Include injected option
        if (injectedFornitoreOption.value) {
            const exists = options.some(o => o.value === injectedFornitoreOption.value.value);
            if (!exists) {
                options.unshift(injectedFornitoreOption.value);
            }
        }

        return options;
    } catch (error) {
        console.error('Error searching fornitori:', error);
        return [];
    } finally {
        fornitoriLoading.value = false;
    }
};

// Load initial fornitori when dropdown opens
const loadFornitoriLazy = async () => {
    if (fornitoriLoaded.value || fornitoriLoading.value) return;
    if (!form.value.company_id) return;

    const options = await searchFornitori('');
    fornitori.value = options;
    fornitoriLoaded.value = true;
};

// Cached supplier search for activity modal (light mode, single API call)
const activitySuppliersCache = ref([]);
let activitySuppliersCacheLoaded = false;

const searchActivitySuppliers = async (query) => {
    // Load once from light endpoint
    if (!activitySuppliersCacheLoaded) {
        try {
            const params = {
                light: true,
                is_fornitore: 1,
                per_page: 200,
                company_id: isSuperAdmin.value ? form.value.company_id : undefined
            };
            const response = await axios.get('/api/users', { params });
            activitySuppliersCache.value = (response.data.data || []).map(f => ({
                value: f.id,
                label: `${f.surname || ''} ${f.name || ''}`.trim() || f.email
            }));
            activitySuppliersCacheLoaded = true;
        } catch (error) {
            console.error('Error loading activity suppliers:', error);
            return [];
        }
    }

    // Filter client-side
    if (!query) return activitySuppliersCache.value;
    const needle = query.toLowerCase();
    return activitySuppliersCache.value.filter(s => s.label.toLowerCase().includes(needle));
};

// Options for fornitori Multiselect
const fornitoriOptions = computed(() => {
    return fornitori.value;
});

const vehiclesLoading = ref(false);

const searchVehicles = async (query) => {
    if (!form.value.company_id) return [];

    vehiclesLoading.value = true;
    try {
        const params = {
            per_page: 30,
            company_id: isSuperAdmin.value ? form.value.company_id : undefined
        };
        if (query && query.length >= 2) {
            params.search = query;
        }

        const response = await axios.get('/api/vehicles', { params });
        const vehicleList = response.data.data || [];
        // Update vehicles ref for computed properties (currentServiceVehicle, etc.)
        vehicles.value = vehicleList;
        return vehicleList.map(v => ({
            value: v.id,
            label: `${v.license_plate} - ${v.brand} ${v.model}`,
            vehicle: v
        }));
    } catch (error) {
        console.error('Error loading vehicles:', error);
        return [];
    } finally {
        vehiclesLoading.value = false;
    }
};

const driversLoading = ref(false);

const searchDrivers = async (query) => {
    if (!form.value.company_id) return [];

    driversLoading.value = true;
    try {
        const params = {
            role: 'driver',
            per_page: 30,
            company_id: isSuperAdmin.value ? form.value.company_id : undefined
        };
        if (query && query.length >= 2) {
            params.search = query;
        }

        const response = await axios.get('/api/users', { params });
        const driverList = response.data.data || [];
        // Merge into drivers ref (avoid duplicates)
        driverList.forEach(d => {
            if (!drivers.value.find(existing => existing.id === d.id)) {
                drivers.value.push(d);
            }
        });
        // Filter out already selected drivers
        return driverList
            .filter(d => !form.value.driver_ids.includes(d.id))
            .map(d => ({
                value: d.id,
                label: `${d.name} ${d.surname}${d.driver_profile?.allow_overlapping ? ' (Sovrapponibile)' : ''}`,
                driver: d
            }));
    } catch (error) {
        console.error('Error loading drivers:', error);
        return [];
    } finally {
        driversLoading.value = false;
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

const SETTINGS_CACHE_TTL = 5 * 60 * 1000; // 5 minutes

const loadSettings = async () => {
    if (!form.value.company_id) return;

    try {
        // Check sessionStorage cache first
        const cacheKey = `easyncc_settings_${form.value.company_id}`;
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            const parsed = JSON.parse(cached);
            if (Date.now() - parsed._cachedAt < SETTINGS_CACHE_TTL) {
                settings.value = parsed.data;
                applySettingsDefaults();
                loadConfirmationRoleUsers();
                return;
            }
        }

        const params = isSuperAdmin.value ? { company_id: form.value.company_id } : {};
        const response = await axios.get('/api/settings', { params });
        settings.value = response.data.data;

        // Cache in sessionStorage
        sessionStorage.setItem(cacheKey, JSON.stringify({
            data: response.data.data,
            _cachedAt: Date.now()
        }));

        applySettingsDefaults();
    } catch (error) {
        console.error('Error loading settings:', error);
    }

    // Load confirmation role users
    await loadConfirmationRoleUsers();
};

const loadConfirmationRoleUsers = async () => {
    const userIds = settings.value?.activity_confirmation_user_ids;
    if (!userIds || userIds.length === 0) {
        // Fallback to role-based if user_ids not configured
        const role = settings.value?.activity_confirmation_role;
        if (!role) {
            confirmationRoleUsers.value = [];
            return;
        }
        try {
            const params = { role: role, per_page: 100 };
            if (form.value.company_id) params.company_id = form.value.company_id;
            const response = await axios.get('/api/users', { params });
            confirmationRoleUsers.value = response.data.data || [];
        } catch (error) {
            console.error('Error loading confirmation role users:', error);
            confirmationRoleUsers.value = [];
        }
        return;
    }
    // Load specific users by IDs — fetch each individually to avoid pagination issues
    try {
        const users = await Promise.all(
            userIds.map(id => axios.get(`/api/users/${id}`).then(r => r.data.data || r.data).catch(() => null))
        );
        confirmationRoleUsers.value = users.filter(Boolean);
    } catch (error) {
        console.error('Error loading confirmation users:', error);
        confirmationRoleUsers.value = [];
    }
};

const applySettingsDefaults = () => {
    // Imposta i valori di default solo se stiamo creando un nuovo servizio (non in edit mode)
    if (!isEdit.value && settings.value) {
        if (settings.value.deposit_percentage !== null && settings.value.deposit_percentage !== undefined) {
            form.value.deposit_percentage = settings.value.deposit_percentage;
        }
        if (settings.value.card_fees_percentage !== null && settings.value.card_fees_percentage !== undefined) {
            form.value.card_fees_percentage = settings.value.card_fees_percentage;
        }
        // Imposta il fornitore di default
        if (settings.value.default_supplier_id !== null && settings.value.default_supplier_id !== undefined) {
            form.value.supplier_id = settings.value.default_supplier_id;
            // Pre-popola le opzioni fornitori con il fornitore di default per il resolve-on-load del Multiselect
            if (settings.value.default_supplier) {
                const s = settings.value.default_supplier;
                fornitori.value = [{
                    value: s.id,
                    label: `${s.surname || ''} ${s.name || ''}`.trim() || s.email
                }];
            }
            // Aggiorna il referente fornitore dopo aver impostato il fornitore
            onSupplierChange();
        }
    }
};

const loadFormData = async () => {
    if (!form.value.company_id) return;

    try {
        const params = isSuperAdmin.value ? { company_id: form.value.company_id } : {};
        const response = await axios.get('/api/services/form-data', { params });
        const data = response.data.data;

        dressCodes.value = data.dress_codes || [];
        serviceStatuses.value = data.service_statuses || [];
        serviceTypes.value = data.service_types || [];
        activityTypes.value = data.activity_types || [];
        accountingEntries.value = data.accounting_entries || [];
        paymentTypes.value = data.payment_types || [];
        transactionStatuses.value = data.transaction_statuses || [];
        activityPaymentTypes.value = data.activity_payment_types || [];
        settings.value = data.settings || null;

        // Cache settings in sessionStorage
        if (settings.value) {
            const cacheKey = `easyncc_settings_${form.value.company_id}`;
            sessionStorage.setItem(cacheKey, JSON.stringify({
                data: settings.value,
                _cachedAt: Date.now()
            }));
        }

        applySettingsDefaults();
        loadConfirmationRoleUsers(); // non-blocking, carica in parallelo
    } catch (error) {
        console.error('Error loading form data:', error);
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

    // Reset lazy-loaded data (will reload via Multiselect async search)
    vehicles.value = [];
    drivers.value = [];
    committenti.value = [];
    intermediari.value = [];
    fornitori.value = [];
    committentiLoaded.value = false;
    intermediariLoaded.value = false;
    fornitoriLoaded.value = false;

    // Ricarica dati dipendenti dall'azienda (dizionari + settings)
    await Promise.all([
        loadFormData(),
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
    if (!await notify.confirm('Rimuovere sosta', 'Sei sicuro di voler rimuovere questa sosta?')) {
        return;
    }

    try {
        // Delete linked accounting transaction if exists
        const activity = form.value.activities.find(a => a.id === activityId);
        if (activity?.accounting_transaction_id) {
            try {
                await axios.delete(`/api/accounting-transactions/${activity.accounting_transaction_id}`);
            } catch (err) {
                console.error('Error deleting activity transaction:', err);
            }
        }
        // Delete from backend if it has an ID
        if (activityId) {
            await axios.delete(`/api/activities/${activityId}`);
        }
        // Remove from local array
        form.value.activities = form.value.activities.filter(a => a.id !== activityId);
        notify.success('Sosta rimossa con successo');
    } catch (error) {
        console.error('Error removing activity:', error);
        notify.error('Errore durante la rimozione della sosta');
    }
};

// Arrotonda ai 5€ superiori
const roundUpTo5 = (value) => {
    return Math.ceil(value / 5) * 5;
};

// Calcola Corrispettivi di Vendita
const calculateTotals = () => {
    // Validazione campi richiesti
    if (!form.value.service_price || form.value.service_price <= 0) {
        notify.warning('Inserisci un Prezzo Imponibile Totale valido');
        return;
    }

    if (!form.value.vat_rate) {
        notify.warning('Seleziona l\'Aliquota IVA');
        return;
    }

    if (!form.value.card_fees_percentage && form.value.card_fees_percentage !== 0) {
        notify.warning('Inserisci il Card Fees %');
        return;
    }

    if (!form.value.deposit_percentage && form.value.deposit_percentage !== 0) {
        notify.warning('Inserisci l\'Acconto %');
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

    // 1. Acconto Imponibile = Imponibile × Acconto% / 100 (arrotondato ai 5€ superiori)
    form.value.deposit_taxable = roundUpTo5(imponibile * depositPerc / 100);

    // 1b. Acconto Handling Fees = Acconto Imponibile × (1 + Aliquota IVA / 100) arrotondato ai 5€ superiori
    form.value.deposit_handling_fees = roundUpTo5(form.value.deposit_taxable * (1 + vatRate / 100));

    // 2. Acconto Totale € = (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (Acconto% / 100) (arrotondato ai 5€ superiori)
    form.value.deposit_amount = roundUpTo5(prezzoConIvaECardFees * (depositPerc / 100));

    // 3. Saldo Imponibile = Imponibile - Acconto Imponibile
    form.value.balance_taxable = parseFloat((imponibile - form.value.deposit_taxable).toFixed(2));

    // 4. Saldo Handling Fees = (Imponibile × (100 + IVA%) / 100) × (100 - Acconto%) / 100 (arrotondato ai 5€ superiori)
    form.value.balance_handling_fees = roundUpTo5(prezzoConIva * (100 - depositPerc) / 100);

    // 5. Saldo Card Fees = (Imponibile × (100 + IVA%) / 100) × (100 + Card Fees%) / 100 × (100 - Acconto%) / 100 (arrotondato ai 5€ superiori)
    form.value.balance_card_fees = roundUpTo5(prezzoConIvaECardFees * (100 - depositPerc) / 100);
};

// Single-field calculate functions
const calcDepositHandlingFees = () => {
    const depositTaxable = parseFloat(form.value.deposit_taxable) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    form.value.deposit_handling_fees = roundUpTo5(depositTaxable * (1 + vatRate / 100));
};

const calcDepositAmount = () => {
    const imponibile = parseFloat(form.value.service_price) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    const cardFeesPerc = parseFloat(form.value.card_fees_percentage) || 0;
    const depositPerc = parseFloat(form.value.deposit_percentage) || 0;
    const prezzoConIva = imponibile * (100 + vatRate) / 100;
    const prezzoConIvaECardFees = prezzoConIva * (100 + cardFeesPerc) / 100;
    form.value.deposit_amount = roundUpTo5(prezzoConIvaECardFees * (depositPerc / 100));
};

const calcBalanceHandlingFees = () => {
    const imponibile = parseFloat(form.value.service_price) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    const depositPerc = parseFloat(form.value.deposit_percentage) || 0;
    const prezzoConIva = imponibile * (100 + vatRate) / 100;
    form.value.balance_handling_fees = roundUpTo5(prezzoConIva * (100 - depositPerc) / 100);
};

const calcBalanceCardFees = () => {
    const imponibile = parseFloat(form.value.service_price) || 0;
    const vatRate = parseFloat(form.value.vat_rate) || 0;
    const cardFeesPerc = parseFloat(form.value.card_fees_percentage) || 0;
    const depositPerc = parseFloat(form.value.deposit_percentage) || 0;
    const prezzoConIva = imponibile * (100 + vatRate) / 100;
    const prezzoConIvaECardFees = prezzoConIva * (100 + cardFeesPerc) / 100;
    form.value.balance_card_fees = roundUpTo5(prezzoConIvaECardFees * (100 - depositPerc) / 100);
};

const contabilizza = async () => {
    if (!props.service || !props.service.id) {
        notify.warning('Salva il servizio prima di contabilizzare');
        return;
    }

    if (!form.value.client_id) {
        notify.warning('Seleziona un committente prima di contabilizzare');
        return;
    }

    if (!settings.value) {
        notify.error('Impossibile recuperare le impostazioni aziendali');
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

        notify.success('Contabilizzazione completata con successo');
    } catch (error) {
        console.error('Error during contabilizza:', error);
        notify.error('Errore durante la contabilizzazione');
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
            start_time: activity.start_time ? moment.utc(activity.start_time).format('YYYY-MM-DDTHH:mm') : '',
            end_time: activity.end_time ? moment.utc(activity.end_time).format('YYYY-MM-DDTHH:mm') : '',
            cost: activity.cost || 0,
            cost_per_person: activity.cost_per_person || 0,
            payment_type: activity.payment_type || '',
            should_account: activity.should_account || false,
            notes: activity.notes || ''
        };
    } else {
        // Create mode - reset form with default dates from service
        const defaultStart = form.value.pickup_datetime || '';
        const defaultEnd = form.value.dropoff_datetime || '';
        activityForm.value = {
            id: null,
            name: '',
            activity_type_id: '',
            supplier_id: '',
            start_time: defaultStart,
            end_time: defaultEnd,
            cost: 0,
            cost_per_person: 0,
            payment_type: '',
            should_account: false,
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
    if (!activityForm.value.name) {
        notify.warning('Inserisci il nome della sosta');
        return;
    }

    try {
        const payload = {
            company_id: form.value.company_id,
            service_id: props.service.id,
            name: activityForm.value.name,
            activity_type_id: activityForm.value.activity_type_id || null,
            supplier_id: activityForm.value.supplier_id || null,
            start_time: activityForm.value.start_time || null,
            end_time: activityForm.value.end_time || null,
            cost: activityForm.value.cost || 0,
            cost_per_person: activityForm.value.cost_per_person || 0,
            payment_type: activityForm.value.payment_type || null,
            should_account: activityForm.value.should_account || false,
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

        notify.success(activityForm.value.id ? 'Sosta aggiornata con successo' : 'Sosta aggiunta con successo');
        closeActivityModal();
        cancelActivityEdit();
    } catch (error) {
        console.error('Error saving activity:', error);
        notify.error('Errore durante il salvataggio della sosta');
    }
};

const moveActivity = async (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.value.activities.length) return;

    // Swap in local array
    const activities = form.value.activities;
    const temp = activities[index];
    activities[index] = activities[newIndex];
    activities[newIndex] = temp;

    // Update sort_order for all activities
    const reorderPayload = activities.map((a, i) => ({
        id: a.id,
        sort_order: i + 1
    }));

    try {
        await axios.post('/api/activities/reorder', {
            activities: reorderPayload,
            company_id: form.value.company_id
        });
    } catch (error) {
        console.error('Error reordering activities:', error);
        // Revert swap on error
        const temp2 = activities[index];
        activities[index] = activities[newIndex];
        activities[newIndex] = temp2;
    }
};

const onActivityDragEnd = async () => {
    const activities = form.value.activities;
    const reorderPayload = activities.map((a, i) => ({
        id: a.id,
        sort_order: i + 1
    }));

    try {
        await axios.post('/api/activities/reorder', {
            activities: reorderPayload,
            company_id: form.value.company_id
        });
    } catch (error) {
        console.error('Error reordering activities after drag:', error);
    }
};

const toggleActivityRowConfirmation = (index, checked) => {
    form.value.activities[index].confirmation_enabled = checked;
    if (!checked) {
        form.value.activities[index].confirmation_assignee_id = null;
    }
};

const setActivityAssignee = (index, userId) => {
    form.value.activities[index].confirmation_assignee_id = userId ? parseInt(userId) : null;
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
            transaction_date: transaction.transaction_date ? moment.utc(transaction.transaction_date).format('YYYY-MM-DD') : '',
            amount: transaction.amount || 0,
            transaction_type: transaction.transaction_type || '',
            installment: transaction.installment || '',
            accounting_entry_id: transaction.accounting_entry_id || '',
            counterpart_id: transaction.counterpart_id || '',
            document_number: transaction.document_number || '',
            document_due_date: transaction.document_due_date ? moment.utc(transaction.document_due_date).format('YYYY-MM-DD') : '',
            payment_date: transaction.payment_date ? moment.utc(transaction.payment_date).format('YYYY-MM-DD') : '',
            payment_type: transaction.payment_type || '',
            payment_reason: transaction.payment_reason || '',
            iban: transaction.iban || '',
            status: transaction.status || '',
            notes: transaction.notes || '',
            is_automatic: transaction.is_automatic || false,
        };
    } else {
        // Create mode - always manual
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
            notes: '',
            is_automatic: false,
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
        notes: '',
        is_automatic: false,
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
        notify.warning('Compila tutti i campi obbligatori');
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
            notes: transactionForm.value.notes || null,
            is_automatic: transactionForm.value.is_automatic,
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

        notify.success(transactionForm.value.id ? 'Movimento aggiornato con successo' : 'Movimento aggiunto con successo');
        closeTransactionModal();
        cancelTransactionEdit();
    } catch (error) {
        console.error('Error saving transaction:', error);
        notify.error('Errore durante il salvataggio del movimento');
    }
};

const removeTransaction = async (transactionId) => {
    if (!await notify.confirm('Rimuovere movimento', 'Sei sicuro di voler rimuovere questo movimento contabile?')) {
        return;
    }

    try {
        // Delete from backend if it has an ID
        if (transactionId) {
            await axios.delete(`/api/accounting-transactions/${transactionId}`);
        }
        // Remove from local array
        form.value.accounting_transactions = form.value.accounting_transactions.filter(t => t.id !== transactionId);
        notify.success('Movimento rimosso con successo');
    } catch (error) {
        console.error('Error removing transaction:', error);
        notify.error('Errore durante la rimozione del movimento');
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
    // Filter to only manual transactions
    const manualIds = selectedTransactions.value.filter(id => {
        const t = form.value.accounting_transactions.find(tr => tr.id === id);
        return t && !t.is_automatic;
    });

    if (manualIds.length === 0) {
        notify.warning('Nessun movimento manuale selezionato. I movimenti automatici non possono essere eliminati.');
        return;
    }

    if (await notify.confirm('Eliminare movimenti', `Sei sicuro di voler eliminare ${manualIds.length} movimenti manuali selezionati?`)) {
        try {
            await Promise.all(
                manualIds.map(id =>
                    axios.delete(`/api/accounting-transactions/${id}`)
                )
            );

            form.value.accounting_transactions = form.value.accounting_transactions.filter(
                t => !manualIds.includes(t.id)
            );

            selectedTransactions.value = [];
            notify.success(`${manualIds.length} movimenti eliminati con successo`);
        } catch (error) {
            console.error('Error deleting transactions:', error);
            notify.error('Errore durante l\'eliminazione dei movimenti selezionati');
        }
    }
};

// Bulk action: available statuses based on selected transactions' types
const bulkAvailableStatuses = computed(() => {
    const selectedTxs = form.value.accounting_transactions.filter(t => selectedTransactions.value.includes(t.id));
    const types = [...new Set(selectedTxs.map(t => t.transaction_type))];
    if (types.length === 0) return [];
    if (types.length > 1) return []; // mixed types - no statuses available
    return filteredTransactionStatuses(types[0]);
});

// Reset bulk status when available statuses change (selection changed)
watch(bulkAvailableStatuses, (newStatuses) => {
    if (bulkTransactionStatus.value && !newStatuses.find(s => s.code === bulkTransactionStatus.value)) {
        bulkTransactionStatus.value = '';
    }
});

// Bulk action: apply a field value to all selected transactions
const applyBulkField = async (field, value) => {
    if (selectedTransactions.value.length === 0) return;
    bulkApplyingTransactions.value = true;
    let successCount = 0;
    let errorCount = 0;
    try {
        const results = await Promise.allSettled(
            selectedTransactions.value.map(async (id) => {
                const payload = {};
                payload[field] = value || null;
                const response = await axios.put(`/api/accounting-transactions/${id}`, payload);
                const index = form.value.accounting_transactions.findIndex(t => t.id === id);
                if (index !== -1) {
                    form.value.accounting_transactions[index] = response.data.data;
                }
            })
        );
        results.forEach(r => {
            if (r.status === 'fulfilled') successCount++;
            else errorCount++;
        });
        if (errorCount > 0) {
            notify.warning(`Aggiornati ${successCount} su ${successCount + errorCount} movimenti. ${errorCount} non modificati.`);
        } else {
            notify.success(`${successCount} movimenti aggiornati con successo`);
        }
        // Reset the bulk field
        if (field === 'payment_date') bulkPaymentDate.value = '';
        else if (field === 'payment_type') bulkPaymentType.value = '';
    } catch (error) {
        console.error('Error in bulk update:', error);
        notify.error('Errore durante l\'aggiornamento massivo');
    } finally {
        bulkApplyingTransactions.value = false;
    }
};

// Bulk action: apply status (with type check)
const applyBulkStatus = async () => {
    if (!bulkTransactionStatus.value || selectedTransactions.value.length === 0) return;

    const selectedTxs = form.value.accounting_transactions.filter(t => selectedTransactions.value.includes(t.id));
    const types = [...new Set(selectedTxs.map(t => t.transaction_type))];

    if (types.length > 1) {
        notify.warning('Hai selezionato movimenti di tipo diverso (vendita e acquisto). Seleziona movimenti dello stesso tipo per cambiare lo stato.');
        return;
    }

    bulkApplyingTransactions.value = true;
    let successCount = 0;
    let errorCount = 0;
    try {
        const results = await Promise.allSettled(
            selectedTransactions.value.map(async (id) => {
                const transaction = form.value.accounting_transactions.find(t => t.id === id);
                if (!transaction) return;
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
                    status: bulkTransactionStatus.value,
                    notes: transaction.notes || null
                };
                const response = await axios.put(`/api/accounting-transactions/${id}`, payload);
                const index = form.value.accounting_transactions.findIndex(t => t.id === id);
                if (index !== -1) {
                    form.value.accounting_transactions[index] = response.data.data;
                }
            })
        );
        results.forEach(r => {
            if (r.status === 'fulfilled') successCount++;
            else errorCount++;
        });
        if (errorCount > 0) {
            notify.warning(`Aggiornati ${successCount} su ${successCount + errorCount} movimenti. ${errorCount} non modificati (stato finale?).`);
        } else {
            notify.success(`Stato aggiornato su ${successCount} movimenti`);
        }
        bulkTransactionStatus.value = '';
    } catch (error) {
        console.error('Error in bulk status update:', error);
        notify.error('Errore durante l\'aggiornamento massivo dello stato');
    } finally {
        bulkApplyingTransactions.value = false;
    }
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
        notify.warning('Seleziona uno stato valido');
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
        notify.error('Errore durante l\'aggiornamento dello stato');
    }
};

const cancelEditTransactionStatus = () => {
    editingTransactionStatus.value = null;
    editingStatusValue.value = '';
};

const startEditInstallment = (transaction) => {
    editingInstallment.value = transaction.id;
    editingInstallmentValue.value = transaction.installment;
};

const saveInstallment = async (transaction) => {
    if (!editingInstallmentValue.value) return;
    try {
        const response = await axios.put(`/api/accounting-transactions/${transaction.id}`, {
            installment: editingInstallmentValue.value,
        });
        const index = form.value.accounting_transactions.findIndex(t => t.id === transaction.id);
        if (index !== -1) {
            form.value.accounting_transactions[index] = response.data.data;
        }
        editingInstallment.value = null;
        editingInstallmentValue.value = '';
    } catch (error) {
        console.error('Error updating installment:', error);
    }
};

const cancelEditInstallment = () => {
    editingInstallment.value = null;
    editingInstallmentValue.value = '';
};

// Inline editing for transaction fields (document_number, document_due_date, payment_date, payment_type)
const editingTransactionField = ref(null); // format: 'transactionId_fieldName'
const editingTransactionFieldValue = ref('');

const startEditTransactionField = (transaction, field) => {
    editingTransactionField.value = transaction.id + '_' + field;
    if (field === 'document_due_date' || field === 'payment_date') {
        editingTransactionFieldValue.value = transaction[field] ? moment.utc(transaction[field]).format('YYYY-MM-DD') : '';
    } else {
        editingTransactionFieldValue.value = transaction[field] || '';
    }
};

const saveTransactionField = async (transaction, field) => {
    const newValue = editingTransactionFieldValue.value || null;
    // Skip save if value unchanged
    const oldValue = transaction[field] || null;
    const compareNew = newValue;
    let compareOld = oldValue;
    if ((field === 'document_due_date' || field === 'payment_date') && oldValue) {
        compareOld = moment.utc(oldValue).format('YYYY-MM-DD');
    }
    if (compareNew === compareOld) {
        editingTransactionField.value = null;
        return;
    }
    try {
        const payload = {};
        payload[field] = newValue;
        const response = await axios.put(`/api/accounting-transactions/${transaction.id}`, payload);
        const index = form.value.accounting_transactions.findIndex(t => t.id === transaction.id);
        if (index !== -1) {
            form.value.accounting_transactions[index] = response.data.data;
        }
        editingTransactionField.value = null;
        editingTransactionFieldValue.value = '';
    } catch (error) {
        console.error('Error updating transaction field:', error);
        editingTransactionField.value = null;
    }
};

const cancelEditTransactionField = () => {
    editingTransactionField.value = null;
    editingTransactionFieldValue.value = '';
};

const getInstallmentLabel = (installment) => {
    const labels = {
        deposit: 'Acconto',
        extra: 'Extra',
        balance: 'Saldo',
        supplier_refund: 'Reso Fornitore',
        customer_refund: 'Rimborso Cliente'
    };
    return labels[installment] || installment;
};

const formatDateTime = (datetime) => {
    return datetime ? moment.utc(datetime).format('DD/MM/YYYY HH:mm') : '-';
};

const formatInfoBarDate = (datetime) => {
    if (!datetime) return '';
    const m = moment(datetime);
    const dayNames = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    return `${dayNames[m.day()]} ${m.format('DD/MM/YYYY')} ore ${m.format('HH:mm')}`;
};

const getPaymentTypeBadge = (type) => {
    const found = activityPaymentTypes.value.find(pt => pt.code === type);
    if (found && found.color) {
        return ''; // Use inline style instead
    }
    // Fallback for legacy values
    const classes = {
        'INCLUSO': 'bg-success-subtle text-success',
        'CLIENTE': 'bg-primary-subtle text-primary',
        'AGENZIA': 'bg-warning-subtle text-warning',
        'NESSUNO': 'bg-secondary-subtle text-secondary'
    };
    return classes[type] || 'bg-secondary-subtle text-secondary';
};

const getPaymentTypeBadgeStyle = (type) => {
    const found = activityPaymentTypes.value.find(pt => pt.code === type);
    if (found && found.color) {
        return { backgroundColor: found.color + '99', color: found.color, fontWeight: '500' };
    }
    return {};
};

const trackFlight = async (flightCode) => {
    if (!flightCode || flightCode.trim().length < 3) {
        await Swal.fire({ title: 'Codice volo non valido', text: 'Inserisci un codice volo valido (es. AZ1234)', icon: 'warning' });
        return;
    }

    flightTracking.value = true;
    try {
        const pickupDate = form.value.pickup_datetime ? moment(form.value.pickup_datetime).format('YYYY-MM-DD') : null;
        const response = await axios.post('/api/flights/track', {
            flight_code: flightCode.trim(),
            date: pickupDate,
            company_id: form.value.company_id,
        });

        const data = response.data.data;
        if (!data || !data.found) {
            await Swal.fire({ title: 'Volo non trovato', text: `Nessun risultato per il volo ${flightCode}`, icon: 'info' });
            return;
        }

        flightData.value = data;
        showFlightModal.value = true;
    } catch (error) {
        const message = error.response?.data?.message || 'Errore durante la ricerca del volo';
        await Swal.fire({ title: 'Errore', text: message, icon: 'error' });
    } finally {
        flightTracking.value = false;
    }
};

const getFlightStatusBadge = (status) => {
    const map = {
        'scheduled': { class: 'bg-info-subtle text-info', label: 'Programmato' },
        'active': { class: 'bg-primary-subtle text-primary', label: 'In Volo' },
        'landed': { class: 'bg-success-subtle text-success', label: 'Atterrato' },
        'cancelled': { class: 'bg-danger-subtle text-danger', label: 'Cancellato' },
        'incident': { class: 'bg-danger-subtle text-danger', label: 'Incidente' },
        'diverted': { class: 'bg-warning-subtle text-warning', label: 'Dirottato' },
    };
    return map[status] || { class: 'bg-secondary-subtle text-secondary', label: status || 'Sconosciuto' };
};

const formatFlightTime = (datetime) => {
    if (!datetime) return '-';
    return moment(datetime).format('DD/MM/YYYY HH:mm');
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
    return date ? moment.utc(date).format('DD/MM/YYYY') : '-';
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
    const found = transactionStatuses.value.find(s => s.code === status);
    return found ? found.name : status;
};

const bootstrapColorMap = {
    primary: '#405189', secondary: '#6c757d', success: '#0ab39c',
    danger: '#f06548', warning: '#f7b84b', info: '#299cdb',
};

const getStatusBadgeStyle = (status) => {
    const fallback = { backgroundColor: '#6c757d20', color: '#6c757d', fontWeight: '500' };
    const found = transactionStatuses.value.find(s => s.code === status);
    if (!found || !found.color) return fallback;
    const hex = found.color.startsWith('#') ? found.color : (bootstrapColorMap[found.color] || '#6c757d');
    return { backgroundColor: hex + '20', color: hex, fontWeight: '500' };
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
        extra: 'EXT',
        balance: 'SAL',
        supplier_refund: 'RES',
        customer_refund: 'RIM'
    };
    return abbrs[installment] || installment;
};

const getStatusAbbr = (status) => {
    const found = transactionStatuses.value.find(s => s.code === status);
    return found ? (found.abbreviation || found.name) : status;
};

const isStatusFinal = (statusCode) => {
    const found = transactionStatuses.value.find(s => s.code === statusCode);
    return found ? found.is_final : false;
};

const filteredTransactionStatuses = (transactionType) => {
    if (!transactionType) return transactionStatuses.value;
    const typeGroup = transactionType === 'sale' ? 'sale' : 'purchase';
    return transactionStatuses.value.filter(s =>
        s.transaction_type_group === typeGroup || s.transaction_type_group === 'both'
    );
};

const getDueDateClass = (transaction) => {
    if (!transaction.document_due_date) return 'text-muted';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(transaction.document_due_date);
    dueDate.setHours(0, 0, 0, 0);

    // Check if status is final (e.g. paid, collected)
    if (isStatusFinal(transaction.status)) {
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
            due_date: task.due_date ? moment.utc(task.due_date).format('YYYY-MM-DD') : '',
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
    if (!await notify.confirm('Eliminare task', 'Sei sicuro di voler eliminare questo task?')) {
        return;
    }

    try {
        await axios.delete(`/api/tasks/${taskId}`);
        // Reload tasks
        await reloadServiceTasks();
    } catch (error) {
        console.error('Error deleting task:', error);
        notify.error('Errore durante l\'eliminazione del task');
    }
};

// New Committente Management
const showSystemDataSection = ref(false);

const generateCommittenteCredentials = () => {
    const timestamp = Date.now();
    const username = `NCC-USR-${timestamp}`;
    const email = `${username}@nccgest.it`;
    const password = 'NCC-PWD-123!!';
    return { username, email, password };
};

const openNewCommittenteModal = () => {
    resetCommittenteForm();
    newCommittenteForm.value.company_id = form.value.company_id;

    // Auto-generate credentials
    const credentials = generateCommittenteCredentials();
    newCommittenteForm.value.username = credentials.username;
    newCommittenteForm.value.email = credentials.email;
    newCommittenteForm.value.password = credentials.password;
    newCommittenteForm.value.password_confirmation = credentials.password;

    showNewCommittenteModal.value = true;
};

const resetCommittenteForm = () => {
    const credentials = generateCommittenteCredentials();
    newCommittenteForm.value = {
        username: credentials.username,
        email: credentials.email,
        surname: '',
        name: '',
        password: credentials.password,
        password_confirmation: credentials.password,
        phone: '',
        role: 'collaboratore',
        is_active: false,
        company_id: null,
        is_committente: true,
        is_fornitore: false,
        is_intermediario: false
    };
    newCommittenteErrors.value = {};
    showNewCommittentePassword.value = false;
    showNewCommittentePasswordConfirmation.value = false;
    showSystemDataSection.value = false;
};

const saveNewCommittente = async () => {
    savingNewCommittente.value = true;
    newCommittenteErrors.value = {};

    try {
        const data = {
            username: newCommittenteForm.value.username,
            email: newCommittenteForm.value.email,
            surname: newCommittenteForm.value.surname,
            name: newCommittenteForm.value.name || null,
            password: newCommittenteForm.value.password,
            password_confirmation: newCommittenteForm.value.password_confirmation,
            phone: newCommittenteForm.value.phone || null,
            role: 'collaboratore',
            is_active: false,
            is_intermediario: false,
            company_id: newCommittenteForm.value.company_id,
            profile: {
                is_committente: true,
                is_fornitore: false
            }
        };

        const response = await axios.post('/api/users', data);
        const newUser = response.data;
        const newLabel = `${newUser.surname || ''} ${newUser.name || ''}`.trim() || newUser.email;

        // Select the newly created committente in Multiselect
        selectCommittenteInMultiselect(newUser.id, newLabel);

        // Close modal
        showNewCommittenteModal.value = false;

        // Show success message
        notify.success('Committente creato con successo!');
    } catch (error) {
        console.error('Error creating committente:', error);

        if (error.response && error.response.status === 422) {
            // Validation errors
            newCommittenteErrors.value = error.response.data.errors || {};
        } else {
            notify.error('Errore durante la creazione del committente. Riprova.');
        }
    } finally {
        savingNewCommittente.value = false;
    }
};

// Helper to select a committente in the async Multiselect
const selectCommittenteInMultiselect = (id, label) => {
    // Inject option so searchCommittenti includes it on next resolve
    injectedCommittenteOption.value = { value: id, label: label };
    // Set value
    form.value.client_id = id;
    // Force Multiselect to recreate — resolve-on-load will find the injected option
    committentiKey.value++;
};

// Create Committente from Passenger data
const createCommittenteFromPassenger = async () => {
    if (!hasPassengerData.value) {
        notify.warning('Inserisci almeno un passeggero con alcuni dati prima di creare un committente.');
        return;
    }

    // Prevent multiple concurrent clicks
    if (creatingCommittenteFromPassenger.value) return;
    creatingCommittenteFromPassenger.value = true;

    try {
        const firstPassenger = form.value.passengers[0];
        const surname = (firstPassenger.surname || '').trim();
        const name = (firstPassenger.name || '').trim();

        // Check for exact duplicates by name+surname (dedicated API call, no side effects on Multiselect)
        if (surname) {
            try {
                const params = {
                    is_committente: 1,
                    per_page: 50,
                    search: surname,
                    company_id: isSuperAdmin.value ? form.value.company_id : undefined
                };
                const dupResponse = await axios.get('/api/users', { params });
                const dupUsers = dupResponse.data.data || [];

                // Filter for exact match on surname + name
                const exactMatches = dupUsers.filter(u => {
                    const uSurname = (u.surname || '').trim().toLowerCase();
                    const uName = (u.name || '').trim().toLowerCase();
                    const matchSurname = uSurname === surname.toLowerCase();
                    const matchName = !name || uName === name.toLowerCase();
                    return matchSurname && matchName;
                });

                if (exactMatches.length > 0) {
                    const namesList = exactMatches.map(c => `<li>${c.surname || ''} ${c.name || ''}</li>`.trim()).join('');
                    const useExisting = await notify.confirmInfo(
                        'Committente esistente',
                        `Esiste già un committente con lo stesso nome:<ul>${namesList}</ul>Vuoi selezionarlo invece di crearne uno nuovo?`,
                        { confirmText: 'Sì, seleziona', cancelText: 'No, crea nuovo' }
                    );
                    if (useExisting) {
                        const match = exactMatches[0];
                        const matchLabel = `${match.surname || ''} ${match.name || ''}`.trim() || match.email;
                        selectCommittenteInMultiselect(match.id, matchLabel);
                    }
                    // In both cases (OK or Annulla) stop here — no new user created
                    return;
                }
            } catch (dupError) {
                console.error('Error checking duplicate committenti:', dupError);
            }
        }

        const timestamp = Date.now();
        const username = `NCC-USR-${timestamp}`;
        const email = firstPassenger.email || `${username}@nccgest.it`;
        const password = 'NCC-PWD-123!!';

        const data = {
            username: username,
            email: email,
            surname: surname || 'Da completare',
            name: name || null,
            password: password,
            password_confirmation: password,
            phone: firstPassenger.phone || null,
            role: 'collaboratore',
            is_active: false,
            is_intermediario: false,
            company_id: form.value.company_id,
            profile: {
                is_committente: true,
                is_fornitore: false
            }
        };

        const response = await axios.post('/api/users', data);
        const newUser = response.data;
        const newLabel = `${newUser.surname || ''} ${newUser.name || ''}`.trim() || newUser.email;

        selectCommittenteInMultiselect(newUser.id, newLabel);

        notify.success('Committente creato con successo dai dati del passeggero!');
    } catch (error) {
        console.error('Error creating committente from passenger:', error);

        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors || {};
            const errorMessages = Object.values(errors).flat().join('<br>');
            notify.error('Errore di validazione: ' + errorMessages);
        } else {
            notify.error('Errore durante la creazione del committente. Riprova.');
        }
    } finally {
        creatingCommittenteFromPassenger.value = false;
    }
};

// New Intermediario Management
const generateIntermediarioCredentials = () => {
    const timestamp = Date.now();
    const username = `NCC-USR-${timestamp}`;
    const email = `${username}@nccgest.it`;
    const password = 'NCC-PWD-123!!';
    return { username, email, password };
};

const openNewIntermediarioModal = () => {
    resetIntermediarioForm();
    newIntermediarioForm.value.company_id = form.value.company_id;

    // Auto-generate credentials
    const credentials = generateIntermediarioCredentials();
    newIntermediarioForm.value.username = credentials.username;
    newIntermediarioForm.value.email = credentials.email;
    newIntermediarioForm.value.password = credentials.password;
    newIntermediarioForm.value.password_confirmation = credentials.password;

    showNewIntermediarioModal.value = true;
};

const resetIntermediarioForm = () => {
    const credentials = generateIntermediarioCredentials();
    newIntermediarioForm.value = {
        username: credentials.username,
        email: credentials.email,
        surname: '',
        name: '',
        password: credentials.password,
        password_confirmation: credentials.password,
        phone: '',
        role: 'collaboratore',
        is_active: false,
        company_id: null,
        is_committente: false,
        is_fornitore: false,
        is_intermediario: true
    };
    newIntermediarioErrors.value = {};
    showNewIntermediarioPassword.value = false;
    showNewIntermediarioPasswordConfirmation.value = false;
    showIntermediarioSystemDataSection.value = false;
};

const saveNewIntermediario = async () => {
    savingNewIntermediario.value = true;
    newIntermediarioErrors.value = {};

    try {
        const data = {
            username: newIntermediarioForm.value.username,
            email: newIntermediarioForm.value.email,
            surname: newIntermediarioForm.value.surname,
            name: newIntermediarioForm.value.name || null,
            password: newIntermediarioForm.value.password,
            password_confirmation: newIntermediarioForm.value.password_confirmation,
            phone: newIntermediarioForm.value.phone || null,
            role: 'collaboratore',
            is_active: false,
            is_intermediario: true,
            company_id: newIntermediarioForm.value.company_id,
            profile: {
                is_committente: false,
                is_fornitore: false
            }
        };

        const response = await axios.post('/api/users', data);
        const newUser = response.data;
        const newLabel = `${newUser.surname || ''} ${newUser.name || ''}`.trim() || newUser.email;

        // Inject option, set value, recreate Multiselect
        injectedIntermediarioOption.value = { value: newUser.id, label: newLabel };
        form.value.intermediary_id = newUser.id;
        intermediariKey.value++;

        // Close modal
        showNewIntermediarioModal.value = false;

        // Show success message
        notify.success('Intermediario creato con successo!');
    } catch (error) {
        console.error('Error creating intermediario:', error);

        if (error.response && error.response.status === 422) {
            // Validation errors
            newIntermediarioErrors.value = error.response.data.errors || {};
        } else {
            notify.error('Errore durante la creazione dell\'intermediario. Riprova.');
        }
    } finally {
        savingNewIntermediario.value = false;
    }
};

// New Fornitore Management
const generateFornitoreCredentials = () => {
    const timestamp = Date.now();
    const username = `NCC-USR-${timestamp}`;
    const email = `${username}@nccgest.it`;
    const password = 'NCC-PWD-123!!';
    return { username, email, password };
};

const openNewFornitoreModal = () => {
    resetFornitoreForm();
    newFornitoreForm.value.company_id = form.value.company_id;

    // Auto-generate credentials
    const credentials = generateFornitoreCredentials();
    newFornitoreForm.value.username = credentials.username;
    newFornitoreForm.value.email = credentials.email;
    newFornitoreForm.value.password = credentials.password;
    newFornitoreForm.value.password_confirmation = credentials.password;

    showNewFornitoreModal.value = true;
};

const resetFornitoreForm = () => {
    const credentials = generateFornitoreCredentials();
    newFornitoreForm.value = {
        username: credentials.username,
        email: credentials.email,
        surname: '',
        name: '',
        password: credentials.password,
        password_confirmation: credentials.password,
        phone: '',
        role: 'collaboratore',
        is_active: false,
        company_id: null,
        is_committente: false,
        is_fornitore: true,
        is_intermediario: false
    };
    newFornitoreErrors.value = {};
    showNewFornitorePassword.value = false;
    showNewFornitorePasswordConfirmation.value = false;
    showFornitoreSystemDataSection.value = false;
};

const saveNewFornitore = async () => {
    savingNewFornitore.value = true;
    newFornitoreErrors.value = {};

    try {
        const data = {
            username: newFornitoreForm.value.username,
            email: newFornitoreForm.value.email,
            surname: newFornitoreForm.value.surname,
            name: newFornitoreForm.value.name || null,
            password: newFornitoreForm.value.password,
            password_confirmation: newFornitoreForm.value.password_confirmation,
            phone: newFornitoreForm.value.phone || null,
            role: 'collaboratore',
            is_active: false,
            is_intermediario: false,
            company_id: newFornitoreForm.value.company_id,
            profile: {
                is_committente: false,
                is_fornitore: true
            }
        };

        const response = await axios.post('/api/users', data);
        const newUser = response.data;
        const newLabel = `${newUser.surname || ''} ${newUser.name || ''}`.trim() || newUser.email;

        // Inject option, set value, recreate Multiselect
        injectedFornitoreOption.value = { value: newUser.id, label: newLabel };
        form.value.supplier_id = newUser.id;
        fornitoriKey.value++;

        // Close modal
        showNewFornitoreModal.value = false;

        // Show success message
        notify.success('Fornitore creato con successo!');
    } catch (error) {
        console.error('Error creating fornitore:', error);

        if (error.response && error.response.status === 422) {
            // Validation errors
            newFornitoreErrors.value = error.response.data.errors || {};
        } else {
            notify.error('Errore durante la creazione del fornitore. Riprova.');
        }
    } finally {
        savingNewFornitore.value = false;
    }
};

// Activity Confirmation Tasks Management - per row sync
const syncActivityConfirmationTasks = async () => {
    if (!settings.value || !settings.value.activity_confirmation_text) {
        console.warn('Activity confirmation text not configured');
        return false;
    }

    if (!form.value.activities || form.value.activities.length === 0) {
        return false;
    }

    try {
        // Get all existing confirmation tasks for this service
        const existingTasks = serviceTasks.value.filter(task =>
            task.notes && task.notes.includes('Task di conferma automatico per l\'esperienza:')
        );

        // Build maps: activity_id → task (primary), activity name → task (fallback)
        const taskByActivityId = {};
        const taskByActivityName = {};
        for (const task of existingTasks) {
            if (task.activity_id) {
                taskByActivityId[task.activity_id] = task;
            }
            const match = task.notes.match(/Task di conferma automatico per l'esperienza: (.+)/);
            if (match) {
                taskByActivityName[match[1]] = task;
            }
        }

        // Calculate due date (day before pickup)
        const dueDate = moment(form.value.pickup_datetime).subtract(1, 'days').format('YYYY-MM-DD');
        const serviceRef = form.value.reference_number || `Servizio #${props.service.id}`;

        // Fallback: default user from settings, or all configured users
        const defaultUserId = settings.value?.activity_confirmation_default_user_id;
        const allConfiguredUserIds = confirmationRoleUsers.value.map(u => u.id);
        const fallbackUserIds = defaultUserId ? [defaultUserId] : allConfiguredUserIds;

        for (const activity of form.value.activities) {
            // Find existing task: by activity_id first, fallback to name match
            const existingTask = taskByActivityId[activity.id] || taskByActivityName[activity.name];

            // First, persist confirmation_enabled and confirmation_assignee_id on the activity
            if (activity.id) {
                await axios.put(`/api/activities/${activity.id}`, {
                    confirmation_enabled: activity.confirmation_enabled || false,
                    confirmation_assignee_id: activity.confirmation_assignee_id || null
                });
            }

            if (activity.confirmation_enabled) {
                // Determine assignees: specific > default from settings > all configured
                const assigneeIds = activity.confirmation_assignee_id
                    ? [activity.confirmation_assignee_id]
                    : fallbackUserIds;

                if (assigneeIds.length === 0) continue;

                // Build task name from template with all placeholders
                const supplier = activity.supplier
                    ? `${activity.supplier.name} ${activity.supplier.surname || ''}`.trim()
                    : 'Fornitore non specificato';

                const supplierPhone = activity.supplier?.phone || '';
                const supplierEmail = activity.supplier?.clientProfile?.operational_email || activity.supplier?.email || '';

                const activityStartTime = activity.start_time ? moment.utc(activity.start_time).format('HH:mm') : '';
                const activityEndTime = activity.end_time ? moment.utc(activity.end_time).format('HH:mm') : '';
                const activityDate = activity.start_time ? moment.utc(activity.start_time).format('DD/MM/YYYY') : '';
                const activityTypeName = activity.activity_type?.name || '';

                const pickupDate = form.value.pickup_datetime ? moment(form.value.pickup_datetime).format('DD/MM/YYYY') : '';
                const pickupTime = form.value.pickup_datetime ? moment(form.value.pickup_datetime).format('HH:mm') : '';

                const firstPassenger = form.value.passengers?.[0];
                const passengerName = firstPassenger
                    ? `${firstPassenger.surname ? firstPassenger.surname.toUpperCase() : ''} ${firstPassenger.name || ''}`.trim()
                    : '';

                const clientName = form.value.client_id
                    ? (committenti.value.find(c => c.id === form.value.client_id)?.label || '')
                    : '';

                const vehiclePlate = form.value.vehicle_id
                    ? (vehicles.value.find(v => v.id === form.value.vehicle_id)?.license_plate || '')
                    : '';

                const baseUrl = window.location.origin;
                const serviceLink = `${baseUrl}/easyncc/services/${props.service.id}/edit`;
                const supplierLink = activity.supplier_id ? `${baseUrl}/easyncc/users/${activity.supplier_id}/edit` : '';

                let taskName = settings.value.activity_confirmation_text
                    // Legacy placeholders
                    .replace('{$fornitore$}', supplier)
                    .replace('{$servizio$}', serviceRef)
                    // Activity placeholders
                    .replace('{$nome_sosta$}', activity.name || '')
                    .replace('{$tipo_sosta$}', activityTypeName)
                    .replace('{$ora_inizio$}', activityStartTime)
                    .replace('{$ora_fine$}', activityEndTime)
                    .replace('{$data_sosta$}', activityDate)
                    // Service placeholders
                    .replace('{$data_servizio$}', pickupDate)
                    .replace('{$ora_pickup$}', pickupTime)
                    .replace('{$passeggero$}', passengerName)
                    .replace('{$committente$}', clientName)
                    .replace('{$veicolo$}', vehiclePlate)
                    // Supplier contact
                    .replace('{$telefono_fornitore$}', supplierPhone)
                    .replace('{$email_fornitore$}', supplierEmail)
                    // Links
                    .replace('{$link_servizio$}', serviceLink)
                    .replace('{$link_fornitore$}', supplierLink);

                if (!existingTask) {
                    // Create new task
                    await axios.post('/api/tasks', {
                        company_id: form.value.company_id,
                        name: taskName,
                        service_id: props.service.id,
                        activity_id: activity.id,
                        due_date: dueDate,
                        assigned_users: assigneeIds,
                        status: 'to_complete',
                        notes: `Task di conferma automatico per l'esperienza: ${activity.name}`
                    });
                } else {
                    // Update existing task assignees if changed
                    await axios.put(`/api/tasks/${existingTask.id}`, {
                        name: taskName,
                        assigned_users: assigneeIds,
                    });
                }
            } else if (existingTask) {
                // Confirmation disabled but task exists → delete
                await axios.delete(`/api/tasks/${existingTask.id}`);
            }
        }

        // Reload tasks
        await loadServiceTasks();

        return true;
    } catch (error) {
        console.error('Error syncing activity confirmation tasks:', error);
        return false;
    }
};

// Process accounting transactions via single batch API call
const processAccountingTransactions = async (overrideServiceId = null) => {
    if (!form.value.client_id) {
        console.warn('No client selected for accounting');
        return false;
    }

    if (!settings.value) {
        console.warn('Settings not available for accounting');
        return false;
    }

    try {
        const pickupDate = form.value.pickup_datetime
            ? moment(form.value.pickup_datetime).format('YYYY-MM-DD')
            : moment().format('YYYY-MM-DD');
        const clientId = form.value.client_id;
        const serviceId = overrideServiceId || props.service.id;
        const handlingFeesEntryId = settings.value.handling_fees_accounting_entry_id;
        const cardFeesEntryId = settings.value.card_fees_accounting_entry_id;

        const operations = [];

        // === ACCONTO VENDITA ===
        // Determine deposit sale amount based on deposit_sale_type
        let depositSaleAmount;
        switch (form.value.deposit_sale_type) {
            case 'deposit_handling_fees': depositSaleAmount = form.value.deposit_handling_fees; break;
            case 'deposit_taxable': depositSaleAmount = form.value.deposit_taxable; break;
            default: depositSaleAmount = form.value.deposit_amount; // deposit_card_fees (default)
        }

        if (depositSaleAmount && depositSaleAmount > 0) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'sale', installment: 'deposit', accounting_entry_id: settings.value.deposit_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: depositSaleAmount,
                    transaction_type: 'sale', installment: 'deposit',
                    accounting_entry_id: settings.value.deposit_accounting_entry_id,
                    counterpart_id: clientId, payment_type: 'carta_di_credito',
                    payment_reason: settings.value.deposit_reason, status: 'to_collect',
                }
            });
        } else if (settings.value.deposit_accounting_entry_id) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'sale', installment: 'deposit', accounting_entry_id: settings.value.deposit_accounting_entry_id },
                data: {}
            });
        }

        // === ACCONTO HANDLING FEES ===
        // Only create if deposit_sale_type is handling_fees or card_fees (handling is a subset of card_fees)
        const accontoHandlingAmount = (form.value.deposit_handling_fees || 0) - (form.value.deposit_taxable || 0);
        if ((form.value.deposit_sale_type === 'deposit_handling_fees' || form.value.deposit_sale_type === 'deposit_card_fees')
            && accontoHandlingAmount > 0 && handlingFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: handlingFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(accontoHandlingAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'deposit',
                    accounting_entry_id: handlingFeesEntryId,
                    counterpart_id: null, payment_type: 'carta_di_credito',
                    payment_reason: settings.value.handling_fees_reason, status: 'to_pay',
                }
            });
        } else if (handlingFeesEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: handlingFeesEntryId },
                data: {}
            });
        }

        // === ACCONTO CARD FEES ===
        // Only create if deposit_sale_type is card_fees
        const accontoCardAmount = (form.value.deposit_amount || 0) - (form.value.deposit_handling_fees || 0);
        if (form.value.deposit_sale_type === 'deposit_card_fees'
            && accontoCardAmount > 0 && cardFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: cardFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(accontoCardAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'deposit',
                    accounting_entry_id: cardFeesEntryId,
                    counterpart_id: null, payment_type: 'carta_di_credito',
                    payment_reason: settings.value.card_fees_reason, status: 'to_pay',
                }
            });
        } else if (cardFeesEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'deposit', accounting_entry_id: cardFeesEntryId },
                data: {}
            });
        }

        // === SALDO VENDITA ===
        let balanceAmount;
        switch (form.value.balance_sale_type) {
            case 'balance_handling_fees': balanceAmount = form.value.balance_handling_fees; break;
            case 'balance_card_fees': balanceAmount = form.value.balance_card_fees; break;
            default: balanceAmount = form.value.balance_taxable;
        }
        if (balanceAmount && balanceAmount > 0) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'sale', installment: 'balance', accounting_entry_id: settings.value.balance_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: balanceAmount,
                    transaction_type: 'sale', installment: 'balance',
                    accounting_entry_id: settings.value.balance_accounting_entry_id,
                    counterpart_id: clientId, payment_type: 'contanti',
                    payment_reason: settings.value.balance_reason, status: 'to_collect',
                }
            });
        }

        // === SALDO HANDLING FEES ===
        const saldoHandlingAmount = (form.value.balance_handling_fees || 0) - (form.value.balance_taxable || 0);
        if ((form.value.balance_sale_type === 'balance_handling_fees' || form.value.balance_sale_type === 'balance_card_fees')
            && saldoHandlingAmount > 0 && handlingFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: handlingFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(saldoHandlingAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: handlingFeesEntryId,
                    counterpart_id: null, payment_type: 'contanti',
                    payment_reason: settings.value.handling_fees_reason, status: 'to_pay',
                }
            });
        } else if (handlingFeesEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: handlingFeesEntryId },
                data: {}
            });
        }

        // === SALDO CARD FEES ===
        const saldoCardAmount = (form.value.balance_card_fees || 0) - (form.value.balance_handling_fees || 0);
        if (form.value.balance_sale_type === 'balance_card_fees'
            && saldoCardAmount > 0 && cardFeesEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: cardFeesEntryId },
                data: {
                    transaction_date: pickupDate, amount: parseFloat(saldoCardAmount.toFixed(2)),
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: cardFeesEntryId,
                    counterpart_id: null, payment_type: 'contanti',
                    payment_reason: settings.value.card_fees_reason, status: 'to_pay',
                }
            });
        } else if (cardFeesEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: cardFeesEntryId },
                data: {}
            });
        }

        // === INTERMEDIAZIONE ===
        if (form.value.intermediary_commission && form.value.intermediary_commission > 0 && form.value.intermediary_id) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'intermediation', installment: 'balance', accounting_entry_id: settings.value.commission_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: form.value.intermediary_commission,
                    transaction_type: 'intermediation', installment: 'balance',
                    accounting_entry_id: settings.value.commission_accounting_entry_id,
                    counterpart_id: form.value.intermediary_id,
                    payment_reason: settings.value.commission_reason, status: 'to_pay',
                }
            });
        } else {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'intermediation', installment: 'balance', accounting_entry_id: settings.value.commission_accounting_entry_id },
                data: {}
            });
        }

        // === CARBURANTE ===
        if (form.value.fuel_cost && form.value.fuel_cost > 0 && form.value.supplier_id) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: settings.value.fuel_accounting_entry_id },
                data: {
                    transaction_date: pickupDate, amount: form.value.fuel_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: settings.value.fuel_accounting_entry_id,
                    counterpart_id: form.value.supplier_id,
                    payment_reason: settings.value.fuel_reason, status: 'to_pay',
                }
            });
        } else {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: settings.value.fuel_accounting_entry_id },
                data: {}
            });
        }

        // === COSTO DRIVER ===
        const driverCostEntryId = settings.value.driver_cost_accounting_entry_id;
        const firstDriverId = form.value.driver_ids && form.value.driver_ids.length > 0 ? form.value.driver_ids[0] : null;
        if (form.value.driver_compensation && form.value.driver_compensation > 0 && firstDriverId && driverCostEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: driverCostEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.value.driver_compensation,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: driverCostEntryId,
                    counterpart_id: firstDriverId,
                    payment_reason: settings.value.driver_cost_reason, status: 'to_pay',
                }
            });
        } else if (driverCostEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: driverCostEntryId },
                data: {}
            });
        }

        // === COSTO COLLEGA ===
        const colleagueCostEntryId = settings.value.colleague_cost_accounting_entry_id;
        if (form.value.colleague_cost && form.value.colleague_cost > 0 && form.value.supplier_id && colleagueCostEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: colleagueCostEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.value.colleague_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: colleagueCostEntryId,
                    counterpart_id: form.value.supplier_id,
                    payment_reason: settings.value.colleague_cost_reason, status: 'to_pay',
                }
            });
        } else if (colleagueCostEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: colleagueCostEntryId },
                data: {}
            });
        }

        // === PEDAGGI ===
        const tollEntryId = settings.value.toll_accounting_entry_id;
        if (form.value.toll_cost && form.value.toll_cost > 0 && form.value.supplier_id && tollEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: tollEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.value.toll_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: tollEntryId,
                    counterpart_id: form.value.supplier_id,
                    payment_reason: settings.value.toll_reason, status: 'to_pay',
                }
            });
        } else if (tollEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: tollEntryId },
                data: {}
            });
        }

        // === PARCHEGGI ===
        const parkingEntryId = settings.value.parking_accounting_entry_id;
        if (form.value.parking_cost && form.value.parking_cost > 0 && form.value.supplier_id && parkingEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: parkingEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.value.parking_cost,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: parkingEntryId,
                    counterpart_id: form.value.supplier_id,
                    payment_reason: settings.value.parking_reason, status: 'to_pay',
                }
            });
        } else if (parkingEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: parkingEntryId },
                data: {}
            });
        }

        // === ALTRI COSTI VEICOLO ===
        const otherVehicleEntryId = settings.value.other_vehicle_accounting_entry_id;
        if (form.value.other_vehicle_costs && form.value.other_vehicle_costs > 0 && form.value.supplier_id && otherVehicleEntryId) {
            operations.push({
                action: 'upsert',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: otherVehicleEntryId },
                data: {
                    transaction_date: pickupDate, amount: form.value.other_vehicle_costs,
                    transaction_type: 'purchase', installment: 'balance',
                    accounting_entry_id: otherVehicleEntryId,
                    counterpart_id: form.value.supplier_id,
                    payment_reason: settings.value.other_vehicle_reason, status: 'to_pay',
                }
            });
        } else if (otherVehicleEntryId) {
            operations.push({
                action: 'delete',
                find_by: { transaction_type: 'purchase', installment: 'balance', accounting_entry_id: otherVehicleEntryId },
                data: {}
            });
        }

        // === COSTO ESPERIENZE — rimosso dal batch, gestito individualmente dopo ===
        // (ogni esperienza con should_account genera il proprio movimento contabile)

        // Single batch API call
        const response = await axios.post('/api/accounting-transactions/batch', {
            service_id: serviceId,
            operations: operations
        });

        // Update local transactions with server response
        if (response.data && response.data.data) {
            form.value.accounting_transactions = response.data.data;
        }

        // === RICAVI EXTRA — gestiti individualmente ===
        const extraRevenueEntryId = settings.value.extra_revenue_accounting_entry_id;
        let extraRevenuesChanged = false;
        if (extraRevenueEntryId && form.value.extra_revenues) {
            for (let i = 0; i < form.value.extra_revenues.length; i++) {
                const extra = form.value.extra_revenues[i];
                const selectedAmount = getExtraRevenueSelectedAmount(extra);
                if (!selectedAmount || selectedAmount <= 0) continue;

                const txPayload = {
                    service_id: serviceId,
                    company_id: form.value.company_id,
                    transaction_date: pickupDate,
                    amount: selectedAmount,
                    transaction_type: 'sale',
                    installment: 'extra',
                    accounting_entry_id: extraRevenueEntryId,
                    counterpart_id: clientId,
                    payment_type: null,
                    payment_reason: extra.description || settings.value.extra_revenue_reason || 'Ricavo extra',
                    status: 'to_collect',
                    is_automatic: true,
                };

                try {
                    if (extra.accounting_transaction_id) {
                        // Update existing
                        await axios.put(`/api/accounting-transactions/${extra.accounting_transaction_id}`, txPayload);
                    } else {
                        // Create new
                        const txRes = await axios.post('/api/accounting-transactions', txPayload);
                        form.value.extra_revenues[i].accounting_transaction_id = txRes.data.data?.id || txRes.data.id;
                        extraRevenuesChanged = true;
                    }
                } catch (err) {
                    console.error('Error saving extra revenue transaction:', err);
                }
            }

            // Persist updated accounting_transaction_id back to service
            if (extraRevenuesChanged) {
                try {
                    await axios.put(`/api/services/${serviceId}`, {
                        extra_revenues: form.value.extra_revenues,
                    });
                } catch (err) {
                    console.error('Error persisting extra_revenues transaction IDs:', err);
                }
            }
        }

        // === COSTO ESPERIENZE — gestiti individualmente ===
        const experienceEntryId = settings.value.experience_accounting_entry_id;
        if (experienceEntryId && form.value.activities) {
            for (const activity of form.value.activities) {
                if (!activity.id) continue;

                const shouldHaveTransaction = activity.should_account && parseFloat(activity.cost) > 0;

                if (shouldHaveTransaction) {
                    const txPayload = {
                        service_id: serviceId,
                        company_id: form.value.company_id,
                        transaction_date: pickupDate,
                        amount: parseFloat(activity.cost),
                        transaction_type: 'purchase',
                        installment: 'balance',
                        accounting_entry_id: experienceEntryId,
                        counterpart_id: activity.supplier_id || null,
                        payment_type: null,
                        payment_reason: activity.name || settings.value.experience_reason || 'Costo esperienza',
                        status: 'to_pay',
                        is_automatic: true,
                    };

                    try {
                        if (activity.accounting_transaction_id) {
                            await axios.put(`/api/accounting-transactions/${activity.accounting_transaction_id}`, txPayload);
                        } else {
                            const txRes = await axios.post('/api/accounting-transactions', txPayload);
                            const newTxId = txRes.data.data?.id || txRes.data.id;
                            activity.accounting_transaction_id = newTxId;
                            // Persist the link on the activity
                            await axios.put(`/api/activities/${activity.id}`, {
                                accounting_transaction_id: newTxId,
                            });
                        }
                    } catch (err) {
                        console.error('Error saving experience transaction:', err);
                    }
                } else if (activity.accounting_transaction_id) {
                    // should_account disabled or cost=0 but transaction exists → delete
                    try {
                        await axios.delete(`/api/accounting-transactions/${activity.accounting_transaction_id}`);
                        activity.accounting_transaction_id = null;
                        await axios.put(`/api/activities/${activity.id}`, {
                            accounting_transaction_id: null,
                        });
                    } catch (err) {
                        console.error('Error deleting experience transaction:', err);
                    }
                }
            }
        }

        return true;
    } catch (error) {
        console.error('Error processing accounting transactions:', error);
        return false;
    }
};

// Remove only automatic accounting transactions (is_automatic === true)
const removeAccountingTransactions = async () => {
    try {
        const transactionsToRemove = form.value.accounting_transactions.filter(
            t => t.is_automatic === true
        );

        if (transactionsToRemove.length === 0) {
            console.warn('No automatic accounting transactions to remove');
            return false;
        }

        for (const transaction of transactionsToRemove) {
            await axios.delete(`/api/accounting-transactions/${transaction.id}`);
        }

        return true;
    } catch (error) {
        console.error('Error removing accounting transactions:', error);
        return false;
    }
};

// Send service email notification
const sendServiceEmail = async () => {
    emailSending.value = true;
    try {
        const response = await axios.post(`/api/services/${props.service.id}/send-email`, {
            to: emailModalData.value.to,
            subject: emailModalData.value.subject,
            body: emailModalData.value.body,
            gmail_account_id: emailModalData.value.gmail_account_id,
        });

        if (response.data.success) {
            showEmailModal.value = false;
            notify.success('La notifica email è stata inviata con successo al collega.');
            // Reload page
            router.visit(route('easyncc.services.edit', props.service.id));
        }
    } catch (error) {
        console.error('Error sending email:', error);
        const msg = error.response?.data?.message || 'Errore durante l\'invio dell\'email';
        notify.error(msg);
    } finally {
        emailSending.value = false;
    }
};

const onEmailBodyEdit = (event) => {
    emailModalData.value.body = event.target.innerHTML;
};

const closeEmailModal = () => {
    showEmailModal.value = false;
    pendingEmailServiceId.value = null;
    // Reload page since service was already saved
    router.visit(route('easyncc.services.edit', props.service.id));
};

// Copy pickup address + coordinates to dropoff
const copyPickupToDropoff = () => {
    form.value.dropoff_address = form.value.pickup_address;
    form.value.dropoff_latitude = form.value.pickup_latitude;
    form.value.dropoff_longitude = form.value.pickup_longitude;
};

// Telegram location sending
const sendingTelegramLocation = ref(null); // 'pickup' or 'dropoff' while sending

const canSendTelegramLocation = (type) => {
    if (!isEdit.value || !props.service?.id) return false;
    const latField = type === 'pickup' ? 'pickup_latitude' : 'dropoff_latitude';
    const lngField = type === 'pickup' ? 'pickup_longitude' : 'dropoff_longitude';
    if (!form.value[latField] || !form.value[lngField]) return false;
    // Check status against allowed list from settings
    const allowedStatuses = settings.value?.telegram_location_status_ids;
    if (allowedStatuses && allowedStatuses.length > 0) {
        return allowedStatuses.includes(form.value.status_id);
    }
    // If no statuses configured, always allow
    return true;
};

const sendTelegramLocation = async (type) => {
    if (!props.service?.id) return;
    sendingTelegramLocation.value = type;
    try {
        const response = await axios.post(`/api/services/${props.service.id}/send-location-telegram`, { type });
        if (response.data.success) {
            notify.success(response.data.message);
        }
    } catch (error) {
        const msg = error.response?.data?.message || 'Errore nell\'invio della posizione';
        notify.error(msg);
    } finally {
        sendingTelegramLocation.value = null;
    }
};

// Function to handle "Salva" button (save and stay)
const saveAndStay = async () => {
    exitAfterSave.value = false;
    await submitForm();
};

// Function to handle "Salva ed Esci" button (save and exit)
const saveAndExit = async () => {
    exitAfterSave.value = true;
    await submitForm();
};

const submitForm = async (confirmOverlaps = false) => {
    // Check if status is changing to trigger status - determine Telegram vs Email flow
    if (isEdit.value && form.value.status_id && props.service.status_id !== form.value.status_id) {
        const newStatus = serviceStatuses.value.find(s => s.id === form.value.status_id);
        const triggerStatusId = settings.value?.telegram_trigger_status_id;
        const isTriggering = triggerStatusId && form.value.status_id === triggerStatusId;

        if (isTriggering && form.value.supplier_id) {
            const defaultSupplierId = settings.value?.default_supplier_id;

            if (defaultSupplierId && form.value.supplier_id !== defaultSupplierId) {
                // Email flow: check supplier operational email
                try {
                    const checkRes = await axios.get(`/api/services/${props.service.id}/check-email-flow`);
                    const { supplier_email, supplier_name } = checkRes.data;

                    if (!supplier_email) {
                        await Swal.fire({
                            title: 'Email non disponibile',
                            html: `Il collega <strong>${supplier_name || 'selezionato'}</strong> non ha l'email operativa configurata nella scheda anagrafica.<br><br>Impossibile inviare la notifica via email.`,
                            icon: 'error',
                            confirmButtonText: 'OK',
                        });
                        return;
                    }

                    const result = await Swal.fire({
                        title: 'Notifica via Email',
                        html: `Il fornitore <strong>${supplier_name}</strong> è diverso dal fornitore di default.<br><br>Verrà inviata una email a <strong>${supplier_email}</strong> con il foglio di servizio e il link per accettare.<br><br>Vuoi procedere?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sì, procedi',
                        cancelButtonText: 'Annulla',
                    });
                    if (!result.isConfirmed) {
                        return;
                    }
                    // Mark that we need to open email modal after save
                    pendingEmailServiceId.value = props.service.id;
                } catch (error) {
                    console.error('Error checking email flow:', error);
                    await Swal.fire({
                        title: 'Errore',
                        text: 'Errore durante la verifica del flusso email',
                        icon: 'error',
                    });
                    return;
                }
            } else {
                // Telegram flow (default supplier or no supplier)
                const result = await Swal.fire({
                    title: 'Notifica al Driver',
                    html: 'Cambiando lo stato in <strong>"Assegnato"</strong> verrà inviato un messaggio Telegram al driver per richiedere la conferma del servizio.<br><br>Vuoi procedere?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, procedi',
                    cancelButtonText: 'Annulla',
                });
                if (!result.isConfirmed) {
                    return;
                }
            }
        } else if (isTriggering) {
            // No supplier set, use Telegram flow
            const result = await Swal.fire({
                title: 'Notifica al Driver',
                html: 'Cambiando lo stato in <strong>"Assegnato"</strong> verrà inviato un messaggio Telegram al driver per richiedere la conferma del servizio.<br><br>Vuoi procedere?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sì, procedi',
                cancelButtonText: 'Annulla',
            });
            if (!result.isConfirmed) {
                return;
            }
        }
    }

    // Check if status is changing to a cancel status (configured in settings) - ask to cancel balance transactions
    let cancelBalanceAfterSave = false;
    if (isEdit.value && form.value.status_id && props.service.status_id !== form.value.status_id) {
        const cancelStatusIds = (settings.value?.service_cancel_status_ids || []).map(Number);
        const currentStatusId = parseInt(form.value.status_id);
        const newStatus = serviceStatuses.value.find(s => s.id === currentStatusId);
        if (cancelStatusIds.includes(currentStatusId)) {
            // Check if there are balance transactions to cancel
            const hasBalanceTransactions = form.value.accounting_transactions?.some(
                t => t.installment === 'balance' && t.status !== 'cancelled'
            );
            if (hasBalanceTransactions) {
                const result = await Swal.fire({
                    title: 'Annullare i movimenti di saldo?',
                    html: `Lo stato del servizio sta cambiando in <strong>"${newStatus.name}"</strong>.<br><br>Vuoi annullare i movimenti contabili di saldo? I movimenti di acconto resteranno invariati.`,
                    icon: 'question',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#f7b84b',
                    confirmButtonText: 'Sì, annulla i saldi',
                    denyButtonText: 'No, mantieni i saldi',
                    cancelButtonText: 'Annulla salvataggio',
                });
                if (result.isDismissed) {
                    return; // User cancelled the entire save
                }
                if (result.isConfirmed) {
                    cancelBalanceAfterSave = true;
                }
                // isDenied = continue saving without cancelling balances
            }
        }
    }

    submitting.value = true;
    try {
        const payload = { ...form.value };

        // Convert empty strings to null for nullable foreign keys
        if (payload.supplier_id === '') payload.supplier_id = null;
        if (payload.intermediary_id === '') payload.intermediary_id = null;
        if (payload.dress_code_id === '') payload.dress_code_id = null;

        // Add confirm_overlaps flag if user confirmed
        if (confirmOverlaps) {
            payload.confirm_overlaps = true;
        }

        const url = isEdit.value ? `/api/services/${props.service.id}` : '/api/services';
        const method = isEdit.value ? 'put' : 'post';

        const response = await axios[method](url, payload);
        const savedServiceId = response.data.data?.id || props.service?.id;

        // Handle post-save operations in parallel
        const postSavePromises = [];

        if (isEdit.value && props.service) {
            // Activity confirmation tasks per row (edit mode only)
            postSavePromises.push(syncActivityConfirmationTasks());
        }

        // Accounting transactions (both new and edit mode)
        if (savedServiceId) {
            const hasAccountingTransactions = form.value.accounting_transactions.some(
                t => (t.transaction_type === 'sale' && (t.installment === 'deposit' || t.installment === 'balance'))
                    || t.transaction_type === 'intermediation'
                    || t.transaction_type === 'purchase'
            );

            if (accountingEnabled.value) {
                postSavePromises.push(processAccountingTransactions(savedServiceId));
            } else if (!accountingEnabled.value && hasAccountingTransactions) {
                postSavePromises.push(removeAccountingTransactions());
            }
        }

        // Execute parallel post-save operations first
        if (postSavePromises.length > 0) {
            await Promise.all(postSavePromises);
        }

        // Cancel balance transactions AFTER accounting processing (must be sequential)
        if (cancelBalanceAfterSave && savedServiceId) {
            try {
                const cancelRes = await axios.post(`/api/accounting-transactions/cancel-balance/${savedServiceId}`);
                const count = cancelRes.data?.cancelled_count || 0;
                if (count > 0) {
                    await Swal.fire({
                        title: 'Saldi annullati',
                        text: `${count} movimenti di saldo sono stati annullati. La nota è stata aggiunta al servizio.`,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            } catch (error) {
                console.error('Error cancelling balance transactions:', error);
                await Swal.fire({
                    title: 'Errore',
                    text: 'Errore durante l\'annullamento dei movimenti di saldo',
                    icon: 'error',
                });
            }
        }

        // If email flow is pending, open the email modal instead of navigating
        if (pendingEmailServiceId.value) {
            try {
                const emailRes = await axios.post(`/api/services/${pendingEmailServiceId.value}/prepare-email`);
                if (emailRes.data.success) {
                    emailModalData.value = emailRes.data.data;
                    showEmailModal.value = true;
                    pendingEmailServiceId.value = null;
                    submitting.value = false;
                    return; // Don't navigate - stay on page with email modal open
                }
            } catch (emailError) {
                console.error('Error preparing email:', emailError);
                const msg = emailError.response?.data?.message || 'Errore durante la preparazione dell\'email';
                await Swal.fire({ title: 'Errore Email', text: msg, icon: 'error' });
                pendingEmailServiceId.value = null;
            }
        }

        // Decide where to go based on exitAfterSave flag
        if (exitAfterSave.value) {
            // Salva ed Esci: return to origin or services list
            router.visit(returnUrl.value || route('easyncc.services.index'));
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
        // Check for overlap confirmation request first (422 with requires_confirmation)
        if (error.response && error.response.status === 422) {
            if (error.response.data.requires_confirmation && error.response.data.overlaps) {
                // This is an overlap confirmation request, show modal
                // Store the current exitAfterSave preference before it gets reset
                pendingExitAfterSave.value = exitAfterSave.value;
                detectedOverlaps.value = error.response.data.overlaps;
                showOverlapModal.value = true;
                return; // Don't show error, just show the modal
            }

            // Regular validation error
            console.error('Validation failed:', error.response.data);

            // Show specific validation errors
            if (error.response.data.errors) {
                const errorMessages = Object.entries(error.response.data.errors)
                    .map(([field, messages]) => `<b>${field}</b>: ${messages.join(', ')}`)
                    .join('<br>');
                notify.error('Errori di validazione: ' + errorMessages);
            } else if (error.response.data.message) {
                notify.error(error.response.data.message);
            } else {
                notify.error('Errore durante il salvataggio del servizio');
            }
        } else {
            console.error('Error submitting form:', error);
            notify.error('Errore durante il salvataggio del servizio');
        }
    } finally {
        submitting.value = false;
        exitAfterSave.value = true; // Reset to default for next save
    }
};

// Confirm overlaps and proceed with save
const confirmOverlapsAndSave = async () => {
    showOverlapModal.value = false;
    // Restore the exit preference from when overlaps were detected
    exitAfterSave.value = pendingExitAfterSave.value;
    await submitForm(true);
};

// Cancel overlap confirmation
const cancelOverlapConfirmation = () => {
    showOverlapModal.value = false;
    detectedOverlaps.value = [];
    pendingExitAfterSave.value = true; // Reset pending preference
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

    // Load all dictionary data + settings in a single API call
    // Vehicles and drivers are lazy-loaded via Multiselect async search
    // Committenti/intermediari/fornitori are lazy-loaded when dropdown opens
    await loadFormData();

    // Pre-populate counterparts if editing
    if (isEdit.value && props.service?.client) {
        committenti.value = [props.service.client];
    }
    if (isEdit.value && props.service?.intermediary) {
        intermediari.value = [props.service.intermediary];
    }
    if (isEdit.value && props.service?.supplier) {
        fornitori.value = [props.service.supplier];
    }
    // Pre-populate vehicle in vehicles array for computed properties
    if (isEdit.value && props.service?.vehicle) {
        vehicles.value = [props.service.vehicle];
    }
    // Pre-populate drivers in drivers array for computed properties
    if (isEdit.value && props.service?.drivers) {
        drivers.value = [...props.service.drivers];
    }

    if (isEdit.value && props.service) {
        // Populate form with service data
        Object.keys(form.value).forEach(key => {
            if (props.service[key] !== undefined) {
                // Format datetime fields for datetime-local input
                if (key.includes('datetime') && props.service[key]) {
                    form.value[key] = moment.utc(props.service[key]).format('YYYY-MM-DDTHH:mm');
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

            // Merge soft-deleted drivers into the dropdown list so they still appear as selected
            props.service.drivers.forEach(d => {
                if (d.deleted_at && !drivers.value.find(existing => existing.id === d.id)) {
                    drivers.value.push(d);
                }
            });
        }

        // Merge soft-deleted vehicle into the dropdown list so it still appears as selected
        if (props.service.vehicle && props.service.vehicle.deleted_at) {
            if (!vehicles.value.find(v => v.id === props.service.vehicle.id)) {
                vehicles.value.push(props.service.vehicle);
            }
        }
        if (props.service.tasks) {
            serviceTasks.value = props.service.tasks;
        }

        // Initialize accountingEnabled based on existing sale or intermediation transactions
        if (props.service.accounting_transactions) {
            const hasAccountingTransactions = props.service.accounting_transactions.some(
                t => (t.transaction_type === 'sale' && (t.installment === 'deposit' || t.installment === 'balance'))
                    || t.transaction_type === 'intermediation'
            );
            accountingEnabled.value = hasAccountingTransactions;
        }

        // Set default values if not present
        if (!form.value.vat_rate) {
            form.value.vat_rate = 10;
        }
        if (form.value.card_fees_percentage === null || form.value.card_fees_percentage === undefined || form.value.card_fees_percentage === '') {
            form.value.card_fees_percentage = 5;
        }
        if (form.value.deposit_percentage === null || form.value.deposit_percentage === undefined || form.value.deposit_percentage === '') {
            form.value.deposit_percentage = 30;
        }
        if (!form.value.balance_sale_type) {
            form.value.balance_sale_type = 'balance_taxable';
        }
    } else {
        // Set defaults for new service
        form.value.vat_rate = 10;
        form.value.card_fees_percentage = 5;
        form.value.deposit_percentage = 30;

        // Generate automatic reference number: SRV-AAAAMMDDhhmmss
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        form.value.reference_number = `SRV-${year}${month}${day}${hours}${minutes}${seconds}`;

        // Pre-fill date from query parameter (e.g., from calendar right-click)
        const urlParams = new URLSearchParams(window.location.search);
        const dateParam = urlParams.get('date');
        if (dateParam) {
            const dateWithTime = `${dateParam}T09:00`;
            form.value.pickup_datetime = dateWithTime;
            form.value.vehicle_departure_datetime = dateWithTime;
            form.value.dropoff_datetime = dateWithTime;
            form.value.vehicle_return_datetime = dateWithTime;
        }
    }

    loading.value = false;
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

/* vue-tel-input small variant */
:deep(.vue-tel-input-sm) {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    font-size: 0.8125rem;
    min-height: 0;
}
:deep(.vue-tel-input-sm .vti__input) {
    font-size: 0.8125rem;
    padding: 0.25rem 0.5rem;
    height: auto;
    line-height: 1.5;
}
:deep(.vue-tel-input-sm .vti__dropdown) {
    padding: 0.25rem 0.4rem;
}
:deep(.vue-tel-input-sm .vti__dropdown-list) {
    font-size: 0.8125rem;
    z-index: 1060;
}
:deep(.vue-tel-input-sm .vti__search_box) {
    font-size: 0.8125rem;
    padding: 0.25rem 0.5rem;
}
:deep(.vue-tel-input-sm .vti__flag) {
    display: inline-block;
    min-width: 20px;
    min-height: 15px;
    margin-right: 4px;
}
:deep(.vue-tel-input-sm .vti__dropdown-item .vti__flag) {
    margin-right: 6px;
}
:deep(.vue-tel-input-sm:focus-within) {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
}

/* Service Form Card - Fixed Footer Layout */
.service-form-card {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 180px); /* Adjust based on header/page padding */
}

.service-form-body {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 1rem;
}

.service-form-footer {
    position: sticky;
    bottom: 0;
    background-color: #fff;
    border-top: 2px solid #dee2e6;
    z-index: 10;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

/* Smooth scroll behavior for anchor links */
html {
    scroll-behavior: smooth;
}

/* Fieldset scroll padding for better anchor positioning */
fieldset[id^="section-"] {
    scroll-margin-top: 20px;
}

/* Bulk action bar for accounting transactions */
.tx-bulk-action-bar {
    background: #405189;
    color: white;
    padding: 12px 16px;
    border-radius: 0 0 0.375rem 0.375rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.tx-bulk-divider {
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.2rem;
    line-height: 1;
    user-select: none;
}

.tx-bulk-action-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.tx-bulk-select {
    width: auto;
    min-width: 120px;
    max-width: 160px;
    font-size: 0.8rem;
    padding: 0.25rem 2rem 0.25rem 0.5rem;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.tx-bulk-select:focus {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 0.15rem rgba(255, 255, 255, 0.2);
}

.tx-bulk-select option {
    background-color: #405189;
    color: white;
}

input[type="date"].tx-bulk-select {
    padding: 0.25rem 0.5rem;
    min-width: 140px;
}

input[type="date"].tx-bulk-select::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Transition for bulk bar */
.tx-bulk-bar-enter-active,
.tx-bulk-bar-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}

.tx-bulk-bar-enter-from,
.tx-bulk-bar-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

@media (max-width: 576px) {
    .tx-bulk-select {
        min-width: 100px;
        max-width: 130px;
        font-size: 0.75rem;
    }

    .tx-bulk-action-bar {
        padding: 8px 12px;
    }
}
</style>
