<template>
    <Head title="Impostazioni" />

    <Layout>
        <PageHeader title="Impostazioni" pageTitle="Configurazione" />

        <BRow>
            <BCol lg="12">
                <!-- Company Selection (only for super-admin) -->
                <div v-if="isSuperAdmin" class="alert alert-info mb-3">
                    <label class="form-label fw-bold">Seleziona Azienda</label>
                    <select
                        v-model="selectedCompanyId"
                        class="form-select"
                        @change="loadAllData"
                    >
                        <option value="">Seleziona un'azienda</option>
                        <option v-for="company in companies" :key="company.id" :value="company.id">
                            {{ company.name }}
                        </option>
                    </select>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>

                <div v-else-if="selectedCompanyId || !isSuperAdmin">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'company' }"
                                @click="activeTab = 'company'"
                                type="button"
                            >
                                <i class="ri-building-line me-1"></i> Dati Azienda
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'accounting' }"
                                @click="activeTab = 'accounting'"
                                type="button"
                            >
                                <i class="ri-money-dollar-circle-line me-1"></i> Contabilit&agrave;
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'services' }"
                                @click="activeTab = 'services'"
                                type="button"
                            >
                                <i class="ri-car-line me-1"></i> Servizi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'notifications' }"
                                @click="activeTab = 'notifications'"
                                type="button"
                            >
                                <i class="ri-notification-3-line me-1"></i> Notifiche
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: activeTab === 'maintenance' }"
                                @click="activeTab = 'maintenance'"
                                type="button"
                            >
                                <i class="ri-tools-line me-1"></i> Manutenzione
                            </button>
                        </li>
                    </ul>

                    <!-- Tab: Dati Azienda -->
                    <BCard v-show="activeTab === 'company'" no-body class="border-top-0 rounded-top-0">
                        <BCardBody>
                            <form @submit.prevent="saveCompanyData">
                                <!-- Dati Anagrafici -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-list-3-line me-2"></i>Dati Anagrafici
                                    </legend>

                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Nome Azienda <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                v-model="companyForm.name"
                                                type="text"
                                                class="form-control"
                                                required
                                                maxlength="255"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input
                                                v-model="companyForm.email"
                                                type="email"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Telefono</label>
                                            <input
                                                v-model="companyForm.phone"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Sito Web</label>
                                            <input
                                                v-model="companyForm.website"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                                placeholder="https://..."
                                            />
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">Indirizzo</label>
                                            <textarea
                                                v-model="companyForm.address"
                                                class="form-control"
                                                rows="2"
                                            ></textarea>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Dati Fiscali -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-government-line me-2"></i>Dati Fiscali
                                    </legend>

                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Partita IVA</label>
                                            <input
                                                v-model="companyForm.vat_number"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">REA</label>
                                            <input
                                                v-model="companyForm.rea"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                                placeholder="Es. RM-1234567"
                                            />
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">SDI</label>
                                            <input
                                                v-model="companyForm.sdi"
                                                type="text"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">PEC</label>
                                            <input
                                                v-model="companyForm.pec"
                                                type="email"
                                                class="form-control"
                                                maxlength="255"
                                            />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Immagini Aziendali -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-image-line me-2"></i>Immagini Aziendali
                                    </legend>

                                    <BRow>
                                        <!-- Logo -->
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">Logo</label>
                                            <div v-if="companyForm.logo_url && !companyForm.remove_logo" class="mb-2">
                                                <div class="position-relative d-inline-block">
                                                    <img
                                                        :src="companyForm.logo_url"
                                                        alt="Logo"
                                                        class="img-thumbnail"
                                                        style="max-height: 120px; max-width: 100%;"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                                        @click="companyForm.remove_logo = true"
                                                        title="Rimuovi"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input
                                                type="file"
                                                class="form-control"
                                                accept="image/*"
                                                @change="onFileChange($event, 'logo')"
                                            />
                                            <small class="text-muted">Max 2MB. Formati: JPG, PNG, GIF, SVG</small>
                                        </BCol>

                                        <!-- Timbro -->
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">Timbro</label>
                                            <div v-if="companyForm.stamp_url && !companyForm.remove_stamp" class="mb-2">
                                                <div class="position-relative d-inline-block">
                                                    <img
                                                        :src="companyForm.stamp_url"
                                                        alt="Timbro"
                                                        class="img-thumbnail"
                                                        style="max-height: 120px; max-width: 100%;"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                                        @click="companyForm.remove_stamp = true"
                                                        title="Rimuovi"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input
                                                type="file"
                                                class="form-control"
                                                accept="image/*"
                                                @change="onFileChange($event, 'stamp')"
                                            />
                                            <small class="text-muted">Max 2MB. Formati: JPG, PNG, GIF, SVG</small>
                                        </BCol>

                                        <!-- Timbro con Firma -->
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">Timbro con Firma</label>
                                            <div v-if="companyForm.stamp_with_signature_url && !companyForm.remove_stamp_with_signature" class="mb-2">
                                                <div class="position-relative d-inline-block">
                                                    <img
                                                        :src="companyForm.stamp_with_signature_url"
                                                        alt="Timbro con Firma"
                                                        class="img-thumbnail"
                                                        style="max-height: 120px; max-width: 100%;"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                                        @click="companyForm.remove_stamp_with_signature = true"
                                                        title="Rimuovi"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input
                                                type="file"
                                                class="form-control"
                                                accept="image/*"
                                                @change="onFileChange($event, 'stamp_with_signature')"
                                            />
                                            <small class="text-muted">Max 2MB. Formati: JPG, PNG, GIF, SVG</small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Alert per errori -->
                                <div v-if="companyErrors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in companyErrors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>

                                <!-- Alert successo -->
                                <div v-if="companySuccessMessage" class="alert alert-success">
                                    {{ companySuccessMessage }}
                                </div>

                                <!-- Pulsanti -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="savingCompany">
                                        <span v-if="savingCompany" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Dati Azienda
                                    </button>
                                </div>
                            </form>
                        </BCardBody>
                    </BCard>

                    <!-- Tab: Contabilità -->
                    <BCard v-show="activeTab === 'accounting'" no-body class="border-top-0 rounded-top-0">
                        <BCardBody>
                            <form @submit.prevent="saveSettings">
                                <!-- Sezione Ricavi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-money-dollar-circle-line me-2"></i>Ricavi
                                    </legend>

                                    <BRow>
                                        <!-- Percentuale Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Percentuale Acconto di Default (%)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.deposit_percentage"
                                                type="number"
                                                class="form-control"
                                                required
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="Es. 30.00"
                                            />
                                            <small class="text-muted">
                                                Percentuale di default per calcolare l'acconto di vendita
                                            </small>
                                        </BCol>

                                        <!-- Percentuale Commissioni Carta -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Commissioni Carta di Credito (%)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.card_fees_percentage"
                                                type="number"
                                                class="form-control"
                                                required
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                placeholder="Es. 5.00"
                                            />
                                            <small class="text-muted">
                                                Percentuale commissioni applicate sui pagamenti con carta
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Causale Contabile Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acconto Vendita</label>
                                            <select
                                                v-model="form.deposit_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acconto vendita
                                            </small>
                                        </BCol>

                                        <!-- Causale Movimento Acconto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acconto Vendita</label>
                                            <input
                                                v-model="form.deposit_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acconto servizio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acconto
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Causale Contabile Saldo -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Saldo Vendita</label>
                                            <select
                                                v-model="form.balance_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di saldo vendita
                                            </small>
                                        </BCol>

                                        <!-- Causale Movimento Saldo -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Saldo Vendita</label>
                                            <input
                                                v-model="form.balance_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Saldo servizio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di saldo
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Causale Contabile Ricavi Extra -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Ricavi Extra</label>
                                            <select
                                                v-model="form.extra_revenue_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di ricavi extra
                                            </small>
                                        </BCol>

                                        <!-- Causale Movimento Ricavi Extra -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Ricavi Extra</label>
                                            <input
                                                v-model="form.extra_revenue_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Ricavo extra servizio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di ricavo extra
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Costi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-shopping-cart-line me-2"></i>Costi
                                    </legend>

                                    <!-- A: Commissioni -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Commissioni</label>
                                            <select
                                                v-model="form.commission_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di commissioni
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Commissioni</label>
                                            <input
                                                v-model="form.commission_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Commissioni intermediario"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di commissioni
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- B: Acquisto Carburanti -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Carburanti</label>
                                            <select
                                                v-model="form.fuel_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acquisto carburanti
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Carburanti</label>
                                            <input
                                                v-model="form.fuel_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto carburante"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto carburanti
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- C: Acquisto Pedaggio -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Pedaggio</label>
                                            <select
                                                v-model="form.toll_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acquisto pedaggio
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Pedaggio</label>
                                            <input
                                                v-model="form.toll_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Pedaggio autostradale"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto pedaggio
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- D: Acquisto Parcheggio -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Acquisto Parcheggio</label>
                                            <select
                                                v-model="form.parking_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acquisto parcheggio
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Parcheggio</label>
                                            <input
                                                v-model="form.parking_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Parcheggio"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto parcheggio
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- E: Altri Costi del Veicolo -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Altri Costi del Veicolo</label>
                                            <select
                                                v-model="form.other_vehicle_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per altri costi veicolo
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Altri Costi del Veicolo</label>
                                            <input
                                                v-model="form.other_vehicle_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Altri costi veicolo"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di altri costi veicolo
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- F: Costi Driver -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Costi Driver</label>
                                            <select
                                                v-model="form.driver_cost_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i costi driver
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Costi Driver</label>
                                            <input
                                                v-model="form.driver_cost_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Compenso driver"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di costi driver
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- G: Costi Collega -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile Costi Collega</label>
                                            <select
                                                v-model="form.colleague_cost_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i costi collega
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto da Collega</label>
                                            <input
                                                v-model="form.colleague_cost_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto da collega"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto da collega
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- H: Acquisto Esperienze -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Acquisto Esperienze</label>
                                            <select
                                                v-model="form.experience_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di acquisto esperienze
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Acquisto Esperienze</label>
                                            <input
                                                v-model="form.experience_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Acquisto esperienza"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di acquisto esperienze
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- I: Handling Fees -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Handling Fees</label>
                                            <select
                                                v-model="form.handling_fees_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di handling fees
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Handling Fees</label>
                                            <input
                                                v-model="form.handling_fees_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Handling fees"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di handling fees
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- J: Card Fees -->
                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Contabile per Card Fees</label>
                                            <select
                                                v-model="form.card_fees_accounting_entry_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona causale contabile</option>
                                                <option
                                                    v-for="entry in accountingEntries"
                                                    :key="entry.id"
                                                    :value="entry.id"
                                                >
                                                    {{ entry.abbreviation }} - {{ entry.name }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Causale contabile da utilizzare per i movimenti di card fees
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Causale Movimento Card Fees</label>
                                            <input
                                                v-model="form.card_fees_reason"
                                                type="text"
                                                class="form-control"
                                                placeholder="Es. Card fees"
                                                maxlength="255"
                                            />
                                            <small class="text-muted">
                                                Testo da usare come causale del movimento di card fees
                                            </small>
                                        </BCol>
                                    </BRow>

                                </fieldset>

                                <!-- Alert per errori -->
                                <div v-if="errors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>
                                <div v-if="successMessage" class="alert alert-success">
                                    {{ successMessage }}
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Contabilit&agrave;
                                    </button>
                                </div>
                            </form>
                        </BCardBody>
                    </BCard>

                    <!-- Tab: Servizi -->
                    <BCard v-show="activeTab === 'services'" no-body class="border-top-0 rounded-top-0">
                        <BCardBody>
                            <form @submit.prevent="saveSettings">
                                <!-- Sezione Soste -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-map-pin-line me-2"></i>Soste
                                    </legend>

                                    <BRow>
                                        <!-- Testo Conferma Prenotazione Sosta -->
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">
                                                Testo Conferma Prenotazione Sosta
                                            </label>
                                            <textarea
                                                v-model="form.activity_confirmation_text"
                                                class="form-control"
                                                rows="4"
                                                placeholder="Confermare {$nome_sosta$} ({$tipo_sosta$}) con {$fornitore$} per {$data_servizio$} ore {$ora_inizio$} - {$servizio$} - Pax: {$passeggero$} - Tel: {$telefono_fornitore$}"
                                            ></textarea>
                                            <div class="mt-2">
                                                <small class="text-muted d-block mb-1 fw-semibold">Segnaposto disponibili:</small>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$nome_sosta$}')" title="Nome/descrizione della sosta">{$nome_sosta$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$tipo_sosta$}')" title="Tipologia sosta">{$tipo_sosta$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$ora_inizio$}')" title="Ora inizio sosta">{$ora_inizio$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$ora_fine$}')" title="Ora fine sosta">{$ora_fine$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$data_sosta$}')" title="Data della sosta">{$data_sosta$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$fornitore$}')" title="Nome fornitore della sosta">{$fornitore$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$telefono_fornitore$}')" title="Telefono fornitore">{$telefono_fornitore$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$email_fornitore$}')" title="Email operativa fornitore">{$email_fornitore$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$servizio$}')" title="Riferimento servizio">{$servizio$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$data_servizio$}')" title="Data pickup servizio">{$data_servizio$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$ora_pickup$}')" title="Ora pickup servizio">{$ora_pickup$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$passeggero$}')" title="Nome primo passeggero">{$passeggero$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$committente$}')" title="Nome committente">{$committente$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$veicolo$}')" title="Targa veicolo">{$veicolo$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$link_servizio$}')" title="Link alla pagina modifica servizio">{$link_servizio$}</span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem; cursor: pointer;" @click="insertPlaceholder('{$link_fornitore$}')" title="Link alla scheda fornitore">{$link_fornitore$}</span>
                                                </div>
                                                <small class="text-muted d-block mt-1">Clicca su un segnaposto per aggiungerlo al testo</small>
                                            </div>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Utenti disponibili per conferma soste -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Utenti disponibili per conferma soste</label>
                                            <Multiselect
                                                v-model="form.activity_confirmation_user_ids"
                                                :options="companyUsers"
                                                mode="tags"
                                                :searchable="true"
                                                :close-on-select="false"
                                                placeholder="Seleziona utenti..."
                                                no-results-text="Nessun utente trovato"
                                            />
                                            <small class="text-muted">
                                                Utenti che appariranno nella lista di assegnazione task per le soste. Se vuoto, nessun utente sarà disponibile.
                                            </small>
                                        </BCol>
                                        <!-- Assegnatario di default -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Assegnatario di default</label>
                                            <Multiselect
                                                v-model="form.activity_confirmation_default_user_id"
                                                :options="selectedConfirmationUsers"
                                                :searchable="true"
                                                :can-clear="true"
                                                :can-deselect="true"
                                                placeholder="Tutti gli utenti selezionati"
                                                no-results-text="Nessun utente selezionato"
                                            />
                                            <small class="text-muted">
                                                Utente assegnato di default ai task di conferma. Se non impostato, il task viene assegnato a tutti gli utenti selezionati.
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Veicoli -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-car-line me-2"></i>Veicoli
                                    </legend>

                                    <BRow>
                                        <!-- Fornitore Default -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Fornitore Default</label>
                                            <select
                                                v-model="form.default_supplier_id"
                                                class="form-select"
                                            >
                                                <option :value="null">Nessun fornitore di default</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.name }} {{ supplier.surname }}
                                                </option>
                                            </select>
                                            <small class="text-muted">
                                                Fornitore che verrà selezionato automaticamente nei nuovi servizi
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Alert per errori -->
                                <div v-if="errors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>
                                <div v-if="successMessage" class="alert alert-success">
                                    {{ successMessage }}
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Servizi
                                    </button>
                                </div>
                            </form>
                        </BCardBody>
                    </BCard>

                    <!-- Tab: Notifiche -->
                    <BCard v-show="activeTab === 'notifications'" no-body class="border-top-0 rounded-top-0">
                        <BCardBody>
                            <form @submit.prevent="saveSettings">
                                <!-- Sezione Notifiche Telegram -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-telegram-line me-2"></i>Notifiche Telegram
                                    </legend>

                                    <BRow>
                                        <!-- Status che Triggera Notifica -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato che Attiva Notifica Telegram
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_trigger_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Nessuno (disabilitato) --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Quando un servizio passa a questo stato, verrà inviata automaticamente
                                                la notifica Telegram con PDF ai driver assegnati
                                            </small>
                                        </BCol>

                                        <!-- Status dopo Accettazione -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato dopo Accettazione Driver
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_accepted_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Non modificare automaticamente --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Quando un driver accetta il servizio tramite Telegram,
                                                lo stato cambierà automaticamente a questo valore
                                            </small>
                                        </BCol>

                                        <!-- Status Chiusura OK -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato Chiusura OK
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_closed_ok_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Chiusura servizio disabilitata --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Stato impostato quando il driver chiude il servizio con esito positivo via Telegram
                                            </small>
                                        </BCol>

                                        <!-- Status Chiusura KO -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato Chiusura KO
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_closed_ko_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Non modificare automaticamente --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Stato impostato quando il driver chiude il servizio con esito negativo via Telegram
                                            </small>
                                        </BCol>

                                        <!-- Status Incasso Avvenuto -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato Incasso Avvenuto
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_collected_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Usa default (collected_driver) --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Stato applicato alla transazione contabile quando il driver segnala l'incasso avvenuto via Telegram
                                            </small>
                                        </BCol>

                                        <!-- Stati per Invio Posizione -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stati per Invio Posizione al Driver
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <select
                                                v-model="form.telegram_location_status_ids"
                                                class="form-select"
                                                multiple
                                                size="4"
                                            >
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Quando il servizio è in uno di questi stati, sarà visibile il bottone per inviare la posizione pickup/dropoff al driver via Telegram.
                                                Se nessuno stato è selezionato, il bottone sarà sempre visibile.
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <!-- Alert informativo -->
                                    <div class="alert alert-info small mb-0">
                                        <i class="ri-information-line me-2"></i>
                                        <strong>Come funziona:</strong>
                                        <ol class="mb-0 mt-2 ps-3">
                                            <li>L'operator imposta il servizio allo <strong>Stato Trigger</strong> →
                                                Sistema invia PDF + bottone "ACCETTA SERVIZIO" ai driver via Telegram</li>
                                            <li>Il driver clicca "ACCETTA SERVIZIO" →
                                                Sistema cambia automaticamente lo stato a <strong>Stato Accettato</strong></li>
                                            <li>Dopo accettazione (e incasso se previsto) → Sistema chiede chiusura servizio (OK/KO)</li>
                                            <li>Il driver chiude il servizio → Stato cambia a <strong>Chiusura OK</strong> o <strong>Chiusura KO</strong></li>
                                            <li>Dopo la chiusura → Sistema chiede la lettura km del veicolo</li>
                                        </ol>
                                        <p class="mb-0 mt-2">
                                            <i class="ri-alert-line me-1"></i>
                                            Se nessuno stato è selezionato come trigger, le notifiche Telegram saranno disabilitate.
                                            Se lo stato Chiusura OK non è configurato, il flusso di chiusura servizio non verrà attivato.
                                        </p>
                                    </div>
                                </fieldset>

                                <!-- Sezione Annullamento Saldi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-close-circle-line me-2"></i>Annullamento Saldi
                                    </legend>

                                    <BRow>
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">Stati servizio che propongono l'annullamento dei movimenti di saldo</label>
                                            <div class="d-flex flex-wrap gap-2">
                                                <div v-for="status in serviceStatuses" :key="status.id" class="form-check">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        :id="'cancel_status_' + status.id"
                                                        :value="status.id"
                                                        v-model="form.service_cancel_status_ids"
                                                    />
                                                    <label class="form-check-label" :for="'cancel_status_' + status.id">
                                                        {{ status.name }}
                                                    </label>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Quando lo stato del servizio cambia in uno di questi stati, verrà proposto all'utente di annullare i movimenti contabili di saldo. I movimenti di acconto resteranno invariati.
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Tracking Voli -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-flight-takeoff-line me-2"></i>Tracking Voli
                                    </legend>

                                    <BRow>
                                        <BCol md="6" class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="flight_tracking_enabled"
                                                    v-model="form.flight_tracking_enabled"
                                                />
                                                <label class="form-check-label" for="flight_tracking_enabled">
                                                    <strong>Abilita tracking voli</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                Permette di cercare informazioni sui voli dal campo "Riferimenti vettore" nella pagina servizio.
                                            </small>
                                        </BCol>
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">API Key AviationStack</label>
                                            <input
                                                v-model="form.aviationstack_api_key"
                                                type="password"
                                                class="form-control"
                                                placeholder="Inserisci la API key..."
                                                :disabled="!form.flight_tracking_enabled"
                                            />
                                            <small class="text-muted">
                                                Registrati su <a href="https://aviationstack.com" target="_blank">aviationstack.com</a> per ottenere una API key gratuita (500 richieste/mese).
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Notifiche Email Colleghi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-mail-send-line me-2"></i>Notifiche Email Colleghi
                                    </legend>

                                    <div class="alert alert-info small mb-3">
                                        <i class="ri-information-line me-2"></i>
                                        Quando un servizio viene assegnato a un fornitore <strong>diverso dal fornitore di default</strong>,
                                        il sistema utilizza l'email invece di Telegram per notificare il collega.
                                        Configura qui i parametri per il flusso email.
                                    </div>

                                    <BRow>
                                        <!-- Account Gmail -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Account Gmail per Invio
                                                <span class="text-muted small">(obbligatorio)</span>
                                            </label>
                                            <select
                                                v-model="form.email_gmail_account_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Seleziona account Gmail --</option>
                                                <option
                                                    v-for="account in gmailAccounts"
                                                    :key="account.id"
                                                    :value="account.id"
                                                >
                                                    {{ account.account_label }} ({{ account.email_address }})
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Account Gmail da cui verranno inviate le email ai colleghi
                                            </small>
                                        </BCol>

                                        <!-- Email notifiche admin -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Email Notifiche Accettazione/Chiusura
                                                <span class="text-muted small">(obbligatorio)</span>
                                            </label>
                                            <input
                                                v-model="form.email_notification_address"
                                                type="email"
                                                class="form-control"
                                                placeholder="admin@azienda.it"
                                            />
                                            <small class="form-text text-muted">
                                                Email che riceverà le notifiche quando il collega accetta o chiude il servizio
                                            </small>
                                        </BCol>

                                        <!-- Template Assegnazione -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Template Email Assegnazione
                                            </label>
                                            <select
                                                v-model="form.email_assignment_template_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Usa template di default --</option>
                                                <option
                                                    v-for="tpl in assignmentTemplates"
                                                    :key="tpl.id"
                                                    :value="tpl.id"
                                                >
                                                    {{ tpl.name }}{{ tpl.is_default ? ' (default)' : '' }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Template utilizzato per l'email di assegnazione servizio al collega
                                            </small>
                                        </BCol>

                                        <!-- Template Chiusura -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Template Email Chiusura
                                            </label>
                                            <select
                                                v-model="form.email_closure_template_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Usa template di default --</option>
                                                <option
                                                    v-for="tpl in closureTemplates"
                                                    :key="tpl.id"
                                                    :value="tpl.id"
                                                >
                                                    {{ tpl.name }}{{ tpl.is_default ? ' (default)' : '' }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Template utilizzato per l'email di chiusura servizio inviata dopo l'accettazione
                                            </small>
                                        </BCol>

                                        <!-- Stato Accettazione Email -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato dopo Accettazione via Email
                                            </label>
                                            <select
                                                v-model="form.email_accepted_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Non modificare stato --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Stato del servizio dopo che il collega accetta tramite il link email
                                            </small>
                                        </BCol>

                                        <!-- Stato Chiusura Email -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Stato dopo Chiusura via Email
                                            </label>
                                            <select
                                                v-model="form.email_closed_status_id"
                                                class="form-select"
                                            >
                                                <option :value="null">-- Non modificare stato --</option>
                                                <option
                                                    v-for="status in serviceStatuses"
                                                    :key="status.id"
                                                    :value="status.id"
                                                >
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Stato del servizio dopo che il collega conferma la chiusura tramite il link email
                                            </small>
                                        </BCol>

                                        <!-- Scadenza Token -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">
                                                Scadenza Link (giorni)
                                            </label>
                                            <input
                                                v-model.number="form.email_token_expiry_days"
                                                type="number"
                                                class="form-control"
                                                min="1"
                                                max="90"
                                                placeholder="7"
                                            />
                                            <small class="form-text text-muted">
                                                Numero di giorni dopo i quali i link di accettazione/chiusura scadranno
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Sezione Ingestion Email -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-mail-download-line me-2"></i>Ingestion Email
                                    </legend>

                                    <BRow>
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">
                                                Label Gmail Suggerita
                                                <span class="text-muted small">(precompila nuove caselle)</span>
                                            </label>
                                            <input
                                                v-model="form.gmail_label_richieste"
                                                type="text"
                                                class="form-control"
                                                placeholder="es. NCC/Richiesta"
                                            />
                                            <small class="form-text text-muted">
                                                Nome suggerito della label Gmail. Ogni casella dovrà comunque verificare la label nella configurazione ingestion.
                                            </small>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">
                                                Subject Tag Suggerito
                                                <span class="text-muted small">(opzionale)</span>
                                            </label>
                                            <input
                                                v-model="form.gmail_subject_tag"
                                                type="text"
                                                class="form-control"
                                                placeholder="es. [NCC]"
                                            />
                                            <small class="form-text text-muted">
                                                Tag nel subject come segnale secondario per identificare richieste
                                            </small>
                                        </BCol>
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">
                                                Intervallo Polling
                                                <span class="text-muted small">(minuti)</span>
                                            </label>
                                            <input
                                                v-model.number="form.gmail_polling_interval"
                                                type="number"
                                                class="form-control"
                                                min="5"
                                                max="1440"
                                                placeholder="60"
                                            />
                                            <small class="form-text text-muted">
                                                Ogni quanti minuti il sistema controlla le nuove email etichettate (default: 60)
                                            </small>
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Alert per errori -->
                                <div v-if="errors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>

                                <!-- Alert successo -->
                                <div v-if="successMessage" class="alert alert-success">
                                    {{ successMessage }}
                                </div>

                                <!-- Pulsanti -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Notifiche
                                    </button>
                                </div>
                            </form>
                        </BCardBody>
                    </BCard>

                    <!-- Tab: Manutenzione -->
                    <BCard v-show="activeTab === 'maintenance'" no-body class="border-top-0 rounded-top-0">
                        <BCardBody>
                            <h5 class="mb-3"><i class="ri-tools-line me-2"></i>Manutenzione</h5>

                            <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light">
                                <div>
                                    <h6 class="mb-1">Ricalcola Sovrapposizioni Servizi</h6>
                                    <p class="text-muted mb-0 small">
                                        Ricalcola tutte le sovrapposizioni tra servizi, rispettando i flag "Permetti sovrapposizioni" impostati sui veicoli e driver.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-warning ms-3"
                                    :disabled="recalculatingOverlaps"
                                    @click="recalculateOverlaps"
                                >
                                    <span v-if="recalculatingOverlaps" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="ri-refresh-line me-1"></i>
                                    Ricalcola
                                </button>
                            </div>
                            <div v-if="recalculateResult" class="alert mt-3" :class="recalculateResult.success ? 'alert-success' : 'alert-danger'">
                                {{ recalculateResult.message }}
                            </div>
                        </BCardBody>
                    </BCard>
                </div>

                <div v-else class="alert alert-warning">
                    Seleziona un'azienda per modificare le impostazioni
                </div>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
        Multiselect,
    },
    data() {
        return {
            loading: true,
            saving: false,
            savingCompany: false,
            errors: [],
            companyErrors: [],
            successMessage: '',
            companySuccessMessage: '',
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            accountingEntries: [],
            suppliers: [],
            companyUsers: [],
            serviceStatuses: [],
            gmailAccounts: [],
            assignmentTemplates: [],
            closureTemplates: [],
            activeTab: 'company',
            recalculatingOverlaps: false,
            recalculateResult: null,
            // Settings form
            form: {
                deposit_percentage: 30.00,
                card_fees_percentage: 5.00,
                deposit_accounting_entry_id: null,
                deposit_reason: null,
                balance_accounting_entry_id: null,
                balance_reason: null,
                commission_accounting_entry_id: null,
                commission_reason: null,
                fuel_accounting_entry_id: null,
                fuel_reason: null,
                toll_accounting_entry_id: null,
                toll_reason: null,
                parking_accounting_entry_id: null,
                parking_reason: null,
                other_vehicle_accounting_entry_id: null,
                other_vehicle_reason: null,
                driver_cost_accounting_entry_id: null,
                driver_cost_reason: null,
                colleague_cost_accounting_entry_id: null,
                colleague_cost_reason: null,
                experience_accounting_entry_id: null,
                experience_reason: null,
                handling_fees_accounting_entry_id: null,
                handling_fees_reason: null,
                card_fees_accounting_entry_id: null,
                card_fees_reason: null,
                extra_revenue_accounting_entry_id: null,
                extra_revenue_reason: null,
                activity_confirmation_text: null,
                activity_confirmation_role: null,
                activity_confirmation_user_ids: [],
                activity_confirmation_default_user_id: null,
                default_supplier_id: null,
                telegram_trigger_status_id: null,
                telegram_accepted_status_id: null,
                telegram_closed_ok_status_id: null,
                telegram_closed_ko_status_id: null,
                telegram_collected_status_id: null,
                service_cancel_status_ids: [],
                telegram_location_status_ids: [],
                email_accepted_status_id: null,
                email_closed_status_id: null,
                email_notification_address: null,
                email_assignment_template_id: null,
                email_closure_template_id: null,
                email_gmail_account_id: null,
                email_token_expiry_days: 7,
                aviationstack_api_key: null,
                flight_tracking_enabled: false,
                gmail_label_richieste: null,
                gmail_subject_tag: null,
                gmail_polling_interval: 60,
            },
            // Company form
            companyForm: {
                name: '',
                email: '',
                phone: '',
                vat_number: '',
                sdi: '',
                pec: '',
                address: '',
                website: '',
                rea: '',
                logo_url: null,
                stamp_url: null,
                stamp_with_signature_url: null,
                remove_logo: false,
                remove_stamp: false,
                remove_stamp_with_signature: false,
            },
            // File references for upload
            companyFiles: {
                logo: null,
                stamp: null,
                stamp_with_signature: null,
            },
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        selectedConfirmationUsers() {
            if (!this.form.activity_confirmation_user_ids || this.form.activity_confirmation_user_ids.length === 0) {
                return [];
            }
            return this.companyUsers.filter(u => this.form.activity_confirmation_user_ids.includes(u.value));
        },
    },
    async mounted() {
        await this.loadCurrentUser();
        if (this.isSuperAdmin) {
            await this.loadCompanies();
            this.loading = false;
        } else {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadAllData();
        }
    },
    methods: {
        insertPlaceholder(placeholder) {
            this.form.activity_confirmation_text = (this.form.activity_confirmation_text || '') + placeholder;
        },
        async recalculateOverlaps() {
            if (!this.selectedCompanyId) {
                this.recalculateResult = { success: false, message: 'Seleziona un\'azienda prima di ricalcolare le sovrapposizioni.' };
                return;
            }
            if (!confirm('Ricalcolare tutte le sovrapposizioni dei servizi? Le sovrapposizioni esistenti verranno rimosse e ricalcolate.')) return;
            this.recalculatingOverlaps = true;
            this.recalculateResult = null;
            try {
                const { data } = await axios.post('/api/services/recalculate-overlaps', {
                    company_id: this.selectedCompanyId
                });
                this.recalculateResult = { success: true, message: data.message };
            } catch (error) {
                this.recalculateResult = { success: false, message: error.response?.data?.message || 'Errore nel ricalcolo delle sovrapposizioni' };
            } finally {
                this.recalculatingOverlaps = false;
            }
        },
        async loadCurrentUser() {
            try {
                const response = await axios.get('/api/user');
                this.currentUser = response.data;
            } catch (error) {
                console.error('Error loading current user:', error);
            }
        },
        async loadCompanies() {
            try {
                const response = await axios.get('/api/companies');
                this.companies = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },
        async loadAllData() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            this.errors = [];
            this.companyErrors = [];
            this.successMessage = '';
            this.companySuccessMessage = '';

            try {
                await Promise.all([
                    this.loadSettings(),
                    this.loadAccountingEntries(),
                    this.loadSuppliers(),
                    this.loadServiceStatuses(),
                    this.loadCompanyData(),
                    this.loadGmailAccounts(),
                    this.loadEmailTemplates(),
                    this.loadCompanyUsers(),
                ]);
            } catch (error) {
                console.error('Error loading settings page data:', error);
                this.errors = ['Errore durante il caricamento delle impostazioni'];
            } finally {
                this.loading = false;
            }
        },
        async loadSettings() {
            if (!this.selectedCompanyId) return;

            const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
            const response = await axios.get('/api/settings', { params });

            const data = response.data.data;
            this.form = {
                deposit_percentage: data.deposit_percentage || 30.00,
                card_fees_percentage: data.card_fees_percentage || 5.00,
                deposit_accounting_entry_id: data.deposit_accounting_entry_id || null,
                deposit_reason: data.deposit_reason || null,
                balance_accounting_entry_id: data.balance_accounting_entry_id || null,
                balance_reason: data.balance_reason || null,
                commission_accounting_entry_id: data.commission_accounting_entry_id || null,
                commission_reason: data.commission_reason || null,
                fuel_accounting_entry_id: data.fuel_accounting_entry_id || null,
                fuel_reason: data.fuel_reason || null,
                toll_accounting_entry_id: data.toll_accounting_entry_id || null,
                toll_reason: data.toll_reason || null,
                parking_accounting_entry_id: data.parking_accounting_entry_id || null,
                parking_reason: data.parking_reason || null,
                other_vehicle_accounting_entry_id: data.other_vehicle_accounting_entry_id || null,
                other_vehicle_reason: data.other_vehicle_reason || null,
                driver_cost_accounting_entry_id: data.driver_cost_accounting_entry_id || null,
                driver_cost_reason: data.driver_cost_reason || null,
                colleague_cost_accounting_entry_id: data.colleague_cost_accounting_entry_id || null,
                colleague_cost_reason: data.colleague_cost_reason || null,
                experience_accounting_entry_id: data.experience_accounting_entry_id || null,
                experience_reason: data.experience_reason || null,
                handling_fees_accounting_entry_id: data.handling_fees_accounting_entry_id || null,
                handling_fees_reason: data.handling_fees_reason || null,
                card_fees_accounting_entry_id: data.card_fees_accounting_entry_id || null,
                card_fees_reason: data.card_fees_reason || null,
                extra_revenue_accounting_entry_id: data.extra_revenue_accounting_entry_id || null,
                extra_revenue_reason: data.extra_revenue_reason || null,
                activity_confirmation_text: data.activity_confirmation_text || null,
                activity_confirmation_role: data.activity_confirmation_role || null,
                activity_confirmation_user_ids: data.activity_confirmation_user_ids || [],
                activity_confirmation_default_user_id: data.activity_confirmation_default_user_id || null,
                default_supplier_id: data.default_supplier_id || null,
                telegram_trigger_status_id: data.telegram_trigger_status_id || null,
                telegram_accepted_status_id: data.telegram_accepted_status_id || null,
                telegram_closed_ok_status_id: data.telegram_closed_ok_status_id || null,
                telegram_closed_ko_status_id: data.telegram_closed_ko_status_id || null,
                telegram_collected_status_id: data.telegram_collected_status_id || null,
                service_cancel_status_ids: data.service_cancel_status_ids || [],
                telegram_location_status_ids: data.telegram_location_status_ids || [],
                email_accepted_status_id: data.email_accepted_status_id || null,
                email_closed_status_id: data.email_closed_status_id || null,
                email_notification_address: data.email_notification_address || null,
                email_assignment_template_id: data.email_assignment_template_id || null,
                email_closure_template_id: data.email_closure_template_id || null,
                email_gmail_account_id: data.email_gmail_account_id || null,
                email_token_expiry_days: data.email_token_expiry_days || 7,
                aviationstack_api_key: data.aviationstack_api_key || null,
                flight_tracking_enabled: data.flight_tracking_enabled || false,
                gmail_label_richieste: data.gmail_label_richieste || null,
                gmail_subject_tag: data.gmail_subject_tag || null,
                gmail_polling_interval: data.gmail_polling_interval || 60,
            };
        },
        async loadCompanyData() {
            if (!this.selectedCompanyId) return;

            const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
            const response = await axios.get('/api/settings/company-data', { params });

            const data = response.data.data;
            this.companyForm = {
                name: data.name || '',
                email: data.email || '',
                phone: data.phone || '',
                vat_number: data.vat_number || '',
                sdi: data.sdi || '',
                pec: data.pec || '',
                address: data.address || '',
                website: data.website || '',
                rea: data.rea || '',
                logo_url: data.logo_url || null,
                stamp_url: data.stamp_url || null,
                stamp_with_signature_url: data.stamp_with_signature_url || null,
                remove_logo: false,
                remove_stamp: false,
                remove_stamp_with_signature: false,
            };
            this.companyFiles = { logo: null, stamp: null, stamp_with_signature: null };
        },
        async loadAccountingEntries() {
            if (!this.selectedCompanyId) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/settings/accounting-entries', { params });
                this.accountingEntries = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounting entries:', error);
            }
        },
        async loadSuppliers() {
            if (!this.selectedCompanyId) return;

            const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
            const response = await axios.get('/api/settings/suppliers', { params });
            this.suppliers = response.data.data || [];
        },
        async loadCompanyUsers() {
            if (!this.selectedCompanyId) return;
            try {
                const params = { per_page: 200, role: 'admin,operator' };
                if (this.isSuperAdmin) params.company_id = this.selectedCompanyId;
                const response = await axios.get('/api/users', { params });
                this.companyUsers = (response.data.data || []).map(u => ({
                    value: u.id,
                    label: `${u.surname || ''} ${u.name || ''}`.trim() || u.email,
                }));
            } catch (error) {
                console.error('Error loading company users:', error);
                this.companyUsers = [];
            }
        },
        async loadServiceStatuses() {
            if (!this.selectedCompanyId) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/settings/service-statuses', { params });
                this.serviceStatuses = response.data.data || [];
            } catch (error) {
                console.error('Error loading service statuses:', error);
                this.serviceStatuses = [];
            }
        },
        async loadGmailAccounts() {
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/gmail-accounts', { params });
                this.gmailAccounts = response.data.data || response.data || [];
            } catch (error) {
                console.error('Error loading Gmail accounts:', error);
                this.gmailAccounts = [];
            }
        },
        async loadEmailTemplates() {
            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const [assignmentRes, closureRes] = await Promise.all([
                    axios.get('/api/quote-email-templates', { params: { ...params, type: 'service_assignment' } }),
                    axios.get('/api/quote-email-templates', { params: { ...params, type: 'service_closure' } }),
                ]);
                this.assignmentTemplates = assignmentRes.data.data || [];
                this.closureTemplates = closureRes.data.data || [];
            } catch (error) {
                console.error('Error loading email templates:', error);
                this.assignmentTemplates = [];
                this.closureTemplates = [];
            }
        },
        onFileChange(event, field) {
            const file = event.target.files[0];
            if (file) {
                this.companyFiles[field] = file;
                // Reset remove flag if user selects a new file
                this.companyForm[`remove_${field}`] = false;
            }
        },
        async saveCompanyData() {
            this.savingCompany = true;
            this.companyErrors = [];
            this.companySuccessMessage = '';

            try {
                const formData = new FormData();

                // Text fields
                formData.append('name', this.companyForm.name || '');
                formData.append('email', this.companyForm.email || '');
                formData.append('phone', this.companyForm.phone || '');
                formData.append('vat_number', this.companyForm.vat_number || '');
                formData.append('sdi', this.companyForm.sdi || '');
                formData.append('pec', this.companyForm.pec || '');
                formData.append('address', this.companyForm.address || '');
                formData.append('website', this.companyForm.website || '');
                formData.append('rea', this.companyForm.rea || '');

                if (this.isSuperAdmin) {
                    formData.append('company_id', this.selectedCompanyId);
                }

                // Image files
                for (const field of ['logo', 'stamp', 'stamp_with_signature']) {
                    if (this.companyFiles[field]) {
                        formData.append(field, this.companyFiles[field]);
                    }
                    if (this.companyForm[`remove_${field}`]) {
                        formData.append(`remove_${field}`, '1');
                    }
                }

                const response = await axios.post('/api/settings/company-data', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                // Update URLs from response
                const data = response.data.data;
                this.companyForm.logo_url = data.logo_url || null;
                this.companyForm.stamp_url = data.stamp_url || null;
                this.companyForm.stamp_with_signature_url = data.stamp_with_signature_url || null;
                this.companyForm.remove_logo = false;
                this.companyForm.remove_stamp = false;
                this.companyForm.remove_stamp_with_signature = false;
                this.companyFiles = { logo: null, stamp: null, stamp_with_signature: null };

                this.companySuccessMessage = 'Dati aziendali salvati con successo!';
                setTimeout(() => { this.companySuccessMessage = ''; }, 3000);
            } catch (error) {
                console.error('Error saving company data:', error);
                if (error.response?.data?.errors) {
                    this.companyErrors = Object.values(error.response.data.errors).flat();
                } else {
                    this.companyErrors = ['Errore durante il salvataggio dei dati aziendali'];
                }
            } finally {
                this.savingCompany = false;
            }
        },
        async saveSettings() {
            this.saving = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const data = { ...this.form };
                if (this.isSuperAdmin) {
                    data.company_id = this.selectedCompanyId;
                }

                const response = await axios.put('/api/settings', data);
                this.successMessage = 'Impostazioni salvate con successo!';

                // Auto-hide success message after 3 seconds
                setTimeout(() => {
                    this.successMessage = '';
                }, 3000);
            } catch (error) {
                console.error('Error saving settings:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = ['Errore durante il salvataggio delle impostazioni'];
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
