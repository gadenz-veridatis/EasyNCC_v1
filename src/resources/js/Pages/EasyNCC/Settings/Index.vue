<template>
    <Head title="Impostazioni" />

    <Layout>
        <PageHeader title="Impostazioni" pageTitle="Configurazione" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-settings-3-line me-2"></i>Impostazioni Azienda
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Settings Form -->
                        <form v-else @submit.prevent="saveSettings">
                            <!-- Company Selection (only for super-admin) -->
                            <BRow v-if="isSuperAdmin" class="mb-4">
                                <BCol md="12">
                                    <div class="alert alert-info">
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
                                </BCol>
                            </BRow>

                            <div v-if="selectedCompanyId || !isSuperAdmin">
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

                                <!-- Sezione Esperienze -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-map-pin-line me-2"></i>Esperienze
                                    </legend>

                                    <BRow>
                                        <!-- Testo Conferma Prenotazione Esperienza -->
                                        <BCol md="12" class="mb-3">
                                            <label class="form-label">
                                                Testo Conferma Prenotazione Esperienza
                                            </label>
                                            <textarea
                                                v-model="form.activity_confirmation_text"
                                                class="form-control"
                                                rows="3"
                                                placeholder="Es. Confermare prenotazione {$fornitore$} per servizio {$servizio$}"
                                            ></textarea>
                                            <small class="text-muted">
                                                Testo utilizzato nel Nome del Task. Segnaposto disponibili: <code>{$fornitore$}</code> e <code>{$servizio$}</code>
                                            </small>
                                        </BCol>
                                    </BRow>

                                    <BRow>
                                        <!-- Ruolo Assegnatari -->
                                        <BCol md="6" class="mb-3">
                                            <label class="form-label">Assegna a Ruolo</label>
                                            <select
                                                v-model="form.activity_confirmation_role"
                                                class="form-select"
                                            >
                                                <option :value="null">Seleziona ruolo</option>
                                                <option value="super-admin">Super Admin</option>
                                                <option value="admin">Admin</option>
                                                <option value="operator">Operatore</option>
                                                <option value="driver">Driver</option>
                                                <option value="collaboratore">Collaboratore</option>
                                                <option value="contabilita">Contabilità</option>
                                            </select>
                                            <small class="text-muted">
                                                Ruolo degli utenti a cui saranno assegnati i task di conferma prenotazione esperienza
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
                                                :class="{ 'is-invalid': errors.telegram_trigger_status_id }"
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
                                            <div v-if="errors.telegram_trigger_status_id" class="invalid-feedback">
                                                {{ errors.telegram_trigger_status_id }}
                                            </div>
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
                                                :class="{ 'is-invalid': errors.telegram_accepted_status_id }"
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
                                            <div v-if="errors.telegram_accepted_status_id" class="invalid-feedback">
                                                {{ errors.telegram_accepted_status_id }}
                                            </div>
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
                                        </ol>
                                        <p class="mb-0 mt-2">
                                            <i class="ri-alert-line me-1"></i>
                                            Se nessuno stato è selezionato come trigger, le notifiche Telegram saranno disabilitate.
                                        </p>
                                    </div>
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
                                        Salva Impostazioni
                                    </button>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                Seleziona un'azienda per modificare le impostazioni
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
    },
    data() {
        return {
            loading: true,
            saving: false,
            errors: [],
            successMessage: '',
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            accountingEntries: [],
            suppliers: [],
            serviceStatuses: [],
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
                activity_confirmation_text: null,
                activity_confirmation_role: null,
                default_supplier_id: null,
                telegram_trigger_status_id: null,
                telegram_accepted_status_id: null,
            },
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
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
            this.successMessage = '';

            try {
                await Promise.all([
                    this.loadSettings(),
                    this.loadAccountingEntries(),
                    this.loadSuppliers(),
                    this.loadServiceStatuses(),
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
                activity_confirmation_text: data.activity_confirmation_text || null,
                activity_confirmation_role: data.activity_confirmation_role || null,
                default_supplier_id: data.default_supplier_id || null,
                telegram_trigger_status_id: data.telegram_trigger_status_id || null,
                telegram_accepted_status_id: data.telegram_accepted_status_id || null,
            };
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
