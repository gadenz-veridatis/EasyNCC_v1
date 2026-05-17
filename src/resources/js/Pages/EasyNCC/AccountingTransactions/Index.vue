<template>
    <Head title="Contabilità - Movimenti" />

    <Layout>
        <PageHeader title="Movimenti Contabili" pageTitle="Contabilità" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Movimenti</h5>
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
                            <button
                                type="button"
                                class="btn btn-soft-primary btn-sm"
                                @click="showSummary = !showSummary"
                            >
                                <i :class="showSummary ? 'bx bx-chevron-up' : 'bx bx-chevron-down'"></i>
                                {{ showSummary ? 'Nascondi Riepilogo' : 'Mostra Riepilogo' }}
                            </button>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <fieldset>
                                <legend class="fs-6 fw-semibold text-primary mb-3">
                                    <i class="ri-filter-3-line me-2"></i>Filtri di Ricerca
                                </legend>
                            <!-- Riga 1: Ricerca, Tipo, Stato, Causale -->
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Numero documento, causale, note..."
                                        @input="() => loadTransactions(1)"
                                    />
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Tipo Movimento</label>
                                    <select v-model="filters.transaction_type" class="form-select form-select-sm" @change="() => loadTransactions(1)">
                                        <option value="">Tutti</option>
                                        <option value="purchase">Acquisto</option>
                                        <option value="sale">Vendita</option>
                                        <option value="intermediation">Intermediazione</option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Stato</label>
                                    <select v-model="filters.status" class="form-select form-select-sm" @change="() => loadTransactions(1)">
                                        <option value="">Tutti</option>
                                        <option v-for="ts in transactionStatuses" :key="ts.code" :value="ts.code">
                                            {{ ts.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Causale Contabile</label>
                                    <select v-model="filters.accounting_entry_id" class="form-select form-select-sm" @change="() => loadTransactions(1)">
                                        <option value="">Tutte</option>
                                        <option v-for="entry in accountingEntries" :key="entry.id" :value="entry.id">
                                            {{ entry.name }} ({{ entry.abbreviation }})
                                        </option>
                                    </select>
                                </BCol>
                            </BRow>
                            <!-- Riga 2: Servizio, Controparte Movimento, Date -->
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Servizio</label>
                                    <select v-model="filters.service_id" class="form-select form-select-sm" @change="() => loadTransactions(1)">
                                        <option value="">Tutti</option>
                                        <option v-for="service in services" :key="service.id" :value="service.id">
                                            {{ service.reference_number }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Controparte Movimento</label>
                                    <Multiselect
                                        v-model="filters.counterpart_id"
                                        :options="searchCounterparts"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Data Da</label>
                                    <input v-model="filters.start_date" type="date" class="form-control form-control-sm" @change="() => loadTransactions(1)" />
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Data A</label>
                                    <input v-model="filters.end_date" type="date" class="form-control form-control-sm" @change="() => loadTransactions(1)" />
                                </BCol>
                            </BRow>
                            <!-- Riga 3: Filtri per relazioni servizio -->
                            <BRow class="mb-3">
                                <BCol md="3">
                                    <label class="form-label">Committente (Servizio)</label>
                                    <Multiselect
                                        v-model="filters.client_id"
                                        :options="searchClients"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Fornitore (Servizio)</label>
                                    <Multiselect
                                        v-model="filters.supplier_id"
                                        :options="searchSuppliers"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Intermediario (Servizio)</label>
                                    <Multiselect
                                        v-model="filters.intermediary_id"
                                        :options="searchIntermediaries"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                                <BCol md="3">
                                    <label class="form-label">Driver (Servizio)</label>
                                    <Multiselect
                                        v-model="filters.driver_id"
                                        :options="searchDrivers"
                                        :searchable="true"
                                        :filter-results="false"
                                        :min-chars="2"
                                        :delay="300"
                                        :resolve-on-load="false"
                                        placeholder="Digita per cercare..."
                                        no-options-text="Digita almeno 2 caratteri"
                                        no-results-text="Nessun risultato"
                                        :can-clear="true"
                                    >
                                        <template v-slot:singlelabel="{ value }">
                                            <div class="multiselect-single-label text-truncate" style="max-width: 100%;">{{ value.label }}</div>
                                        </template>
                                    </Multiselect>
                                </BCol>
                            </BRow>
                            <BRow class="mb-3" v-if="isSuperAdmin">
                                <BCol md="3">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="() => loadTransactions(1)">
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
                                        Resetta Filtri
                                    </button>
                                </BCol>
                            </BRow>
                            </fieldset>
                        </div>

                        <!-- Summary Statistics (Collapsible) -->
                        <div v-if="showSummary" class="border rounded p-3 mb-3 bg-light">
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
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Vendite
                                                        </p>
                                                        <h5 class="mb-0 text-success">
                                                            € {{ formatAmount(summary.sales) }}
                                                        </h5>
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
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Acquisti
                                                        </p>
                                                        <h5 class="mb-0 text-danger">
                                                            € {{ formatAmount(summary.purchases) }}
                                                        </h5>
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
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Intermediazioni
                                                        </p>
                                                        <h5 class="mb-0 text-warning">
                                                            € {{ formatAmount(summary.intermediations) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-team-line fs-2 text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Resi Fornitore -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-info">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Resi
                                                        </p>
                                                        <h5 class="mb-0 text-info">
                                                            € {{ formatAmount(summary.supplierRefunds) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-refund-line fs-2 text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Rimborsi Cliente -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border border-secondary">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Rimborsi
                                                        </p>
                                                        <h5 class="mb-0 text-secondary">
                                                            € {{ formatAmount(summary.customerRefunds) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i class="ri-hand-coin-line fs-2 text-secondary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>

                                    <!-- Risultato Totale (moved to the end) -->
                                    <BCol md="4" lg="2" class="mb-3">
                                        <div class="card card-animate border" :class="summary.total >= 0 ? 'border-success' : 'border-danger'">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-1">
                                                            Risultato
                                                        </p>
                                                        <h5 class="mb-0" :class="summary.total >= 0 ? 'text-success' : 'text-danger'">
                                                            € {{ formatAmount(summary.total) }}
                                                        </h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <i :class="['fs-2', summary.total >= 0 ? 'ri-arrow-up-circle-line text-success' : 'ri-arrow-down-circle-line text-danger']"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </BCol>
                                </BRow>
                            </fieldset>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-primary btn-sm" @click="openTransactionModal()">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Movimento
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="transactions.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 70px;">
                                            <input
                                                type="checkbox"
                                                class="form-check-input me-2"
                                                :checked="isAllSelected"
                                                @change="toggleSelectAll"
                                                title="Seleziona tutti"
                                            />Azioni
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('transaction_date')">
                                            Data
                                            <i v-if="sortField === 'transaction_date'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('transaction_type')">
                                            Tipo
                                            <i v-if="sortField === 'transaction_type'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('amount')">
                                            Importo
                                            <i v-if="sortField === 'amount'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('installment')">
                                            Rata
                                            <i v-if="sortField === 'installment'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" style="max-width: 250px;">Causali</th>
                                        <th scope="col" style="max-width: 200px;">Controparte</th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('document_number')">
                                            Documenti
                                            <i v-if="sortField === 'document_number'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col" class="cursor-pointer" @click="sortBy('status')">
                                            Stato
                                            <i v-if="sortField === 'status'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'" class="ms-1"></i>
                                        </th>
                                        <th scope="col">Servizio</th>
                                        <th scope="col" v-if="isSuperAdmin">Azienda</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="transaction in transactions" :key="transaction.id" :class="{ 'table-active': selectedTransactions.includes(transaction.id) }">
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    :value="transaction.id"
                                                    v-model="selectedTransactions"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-primary btn-sm px-1 py-0"
                                                    title="Modifica"
                                                    @click="openTransactionModal(transaction)"
                                                >
                                                    <i class="ri-pencil-line"></i>
                                                </button>
                                                <button
                                                    @click="deleteTransaction(transaction.id)"
                                                    class="btn btn-soft-danger btn-sm px-1 py-0"
                                                    title="Elimina"
                                                    v-if="canDelete && !transaction.is_automatic"
                                                >
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>{{ formatDate(transaction.transaction_date) }}</td>
                                        <td>
                                            <span
                                                :class="getTransactionTypeBadgeClass(transaction.transaction_type)"
                                                :title="getTransactionTypeLabel(transaction.transaction_type)"
                                            >
                                                {{ getTransactionTypeAbbr(transaction.transaction_type) }}
                                            </span>
                                            <span v-if="transaction.is_automatic" class="badge bg-info-subtle text-info ms-1" title="Automatico">A</span>
                                            <span v-else class="badge bg-warning-subtle text-warning ms-1" title="Manuale">M</span>
                                        </td>
                                        <td class="fw-medium">€ {{ parseFloat(transaction.amount).toFixed(2) }}</td>
                                        <td>
                                            <span
                                                v-if="editingInstallment !== transaction.id"
                                                class="badge bg-secondary-subtle text-secondary cursor-pointer"
                                                :title="getInstallmentLabel(transaction.installment) + ' - Clicca per modificare'"
                                                @click="startEditInstallment(transaction)"
                                            >
                                                {{ getInstallmentAbbr(transaction.installment) }}
                                            </span>
                                            <div v-else>
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
                                        <td style="max-width: 200px; word-wrap: break-word; white-space: normal;">
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
                                                v-if="editingStatus !== transaction.id"
                                                @click="startEditStatus(transaction)"
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
                                                    @keyup.esc="cancelEditStatus"
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
                                                        @click="saveStatus(transaction)"
                                                        title="Salva"
                                                    >
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary btn-sm"
                                                        @click="cancelEditStatus"
                                                        title="Annulla"
                                                    >
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <Link v-if="transaction.service"
                                                :href="withReturnUrl(route('easyncc.services.edit', transaction.service.id))"
                                                class="text-primary text-decoration-underline"
                                                :title="'Vai al servizio ' + transaction.service.reference_number"
                                            >
                                                {{ transaction.service.reference_number }}
                                            </Link>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td v-if="isSuperAdmin">
                                            <span v-if="transaction.company">{{ transaction.company.name }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="ri-file-list-3-line display-4 text-muted"></i>
                            <p class="text-muted mt-3">Nessun movimento trovato</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="pagination.total > 0" class="row align-items-center mt-3">
                            <div class="col-sm-6">
                                <div class="text-muted">
                                    Visualizzazione <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</strong> -
                                    <strong>{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong>
                                    di <strong>{{ pagination.total }}</strong> risultati
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <nav aria-label="Paginazione movimenti">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                            <button class="page-link" @click="goToPage(1)" :disabled="pagination.current_page === 1">
                                                <i class="bx bx-chevrons-left"></i>
                                            </button>
                                        </li>
                                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                            <button class="page-link" @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">
                                                <i class="bx bx-chevron-left"></i>
                                            </button>
                                        </li>

                                        <li
                                            v-for="page in visiblePages"
                                            :key="page"
                                            class="page-item"
                                            :class="{ active: pagination.current_page === page }"
                                        >
                                            <button class="page-link" @click="goToPage(page)">
                                                {{ page }}
                                            </button>
                                        </li>

                                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                            <button class="page-link" @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">
                                                <i class="bx bx-chevron-right"></i>
                                            </button>
                                        </li>
                                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                            <button class="page-link" @click="goToPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page">
                                                <i class="bx bx-chevrons-right"></i>
                                            </button>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- Per Page Selector -->
                        <div v-if="pagination.total > 0" class="row mt-3">
                            <div class="col-sm-12 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0">Risultati per pagina:</label>
                                    <select v-model="perPage" @change="changePerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Spacer for bulk action bar -->
                        <div v-if="selectedTransactions.length > 0" style="height: 80px;"></div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>

    <!-- Transaction Edit/Create Modal -->
    <BModal
        v-model="showTransactionModal"
        :title="transactionForm.id ? 'Modifica Movimento Contabile' : 'Nuovo Movimento Contabile'"
        size="lg"
        hide-footer
        @hidden="cancelTransactionEdit"
    >
        <!-- Loading spinner -->
        <div v-if="loadingModal" class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <!-- Fieldset 1: Dati Principali -->
        <fieldset v-show="!loadingModal" class="border rounded p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                <i class="ri-file-list-3-line me-1"></i>
                Dati Principali
            </legend>
            <BRow>
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
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Data Movimento <span class="text-danger">*</span></label>
                        <input v-model="transactionForm.transaction_date" type="date" class="form-control" required :disabled="transactionForm.is_automatic" />
                        <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
                    </div>
                </BCol>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Importo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input v-model="transactionForm.amount" type="number" step="0.01" min="0" class="form-control" required :disabled="transactionForm.is_automatic" />
                        </div>
                        <small v-if="transactionForm.is_automatic" class="text-muted">Campo gestito automaticamente</small>
                    </div>
                </BCol>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Causale Movimento</label>
                        <input v-model="transactionForm.payment_reason" type="text" class="form-control" placeholder="Es. Pagamento servizio, Acconto, Saldo finale..." />
                    </div>
                </BCol>
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
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Stato <span class="text-danger">*</span></label>
                        <select v-model="transactionForm.status" class="form-select" required>
                            <option value="">Seleziona stato</option>
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
        <fieldset v-show="!loadingModal" class="border rounded p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                <i class="ri-links-line me-1"></i>
                Riferimenti
            </legend>
            <BRow>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Servizio</label>
                        <select v-model="transactionForm.service_id" class="form-select">
                            <option value="">Nessuno</option>
                            <option v-for="service in modalServices" :key="service.id" :value="service.id">
                                {{ service.reference_number }}
                            </option>
                        </select>
                    </div>
                </BCol>
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
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Controparte</label>
                        <select v-model="transactionForm.counterpart_id" class="form-select" :disabled="transactionForm.is_automatic">
                            <option value="">Nessuna</option>
                            <option v-for="cp in modalCounterparts" :key="cp.id" :value="cp.id">
                                {{ cp.name }} {{ cp.surname }} - {{ cp.email }}
                            </option>
                        </select>
                        <small v-if="transactionForm.is_automatic" class="text-muted d-block mt-1">Campo gestito automaticamente</small>
                    </div>
                </BCol>
            </BRow>
        </fieldset>

        <!-- Fieldset 3: Documenti e Pagamenti -->
        <fieldset v-show="!loadingModal" class="border rounded p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                <i class="ri-file-text-line me-1"></i>
                Documenti e Pagamenti
            </legend>
            <BRow>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Numero Documento</label>
                        <input v-model="transactionForm.document_number" type="text" class="form-control" placeholder="Es: FT-2024-001" />
                    </div>
                </BCol>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Scadenza Documento</label>
                        <input v-model="transactionForm.document_due_date" type="date" class="form-control" />
                    </div>
                </BCol>
                <BCol md="6">
                    <div class="mb-3">
                        <label class="form-label">Data Pagamento</label>
                        <input v-model="transactionForm.payment_date" type="date" class="form-control" />
                    </div>
                </BCol>
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
                <BCol md="12">
                    <div class="mb-3">
                        <label class="form-label">IBAN</label>
                        <input v-model="transactionForm.iban" type="text" class="form-control" placeholder="IT60X0542811101000000123456" />
                    </div>
                </BCol>
            </BRow>
        </fieldset>

        <!-- Fieldset 4: Note -->
        <fieldset v-show="!loadingModal" class="border rounded p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6 fw-semibold text-primary">
                <i class="ri-sticky-note-line me-1"></i>
                Note
            </legend>
            <BRow>
                <BCol md="12">
                    <div class="mb-3">
                        <textarea v-model="transactionForm.notes" class="form-control" rows="3" placeholder="Note aggiuntive..."></textarea>
                    </div>
                </BCol>
            </BRow>
        </fieldset>

        <!-- Form Actions -->
        <div v-show="!loadingModal" class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-soft-secondary" @click="closeTransactionModal">
                <i class="ri-close-line me-1"></i> Annulla
            </button>
            <button
                type="button"
                class="btn btn-primary"
                @click="saveTransaction"
                :disabled="savingTransaction || !transactionForm.transaction_date || !transactionForm.amount || !transactionForm.transaction_type || !transactionForm.installment || !transactionForm.status"
            >
                <span v-if="savingTransaction" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else :class="transactionForm.id ? 'ri-save-line' : 'ri-add-line'" class="me-1"></i>
                {{ transactionForm.id ? 'Aggiorna' : 'Crea' }} Movimento
            </button>
        </div>
    </BModal>

    <!-- Bulk Action Bar -->
    <Teleport to="body">
        <div v-if="selectedTransactions.length > 0" class="bulk-action-bar">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <!-- Counter + Deselect -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-white text-dark fs-6">{{ selectedTransactions.length }}</span>
                        <span class="text-white small d-none d-sm-inline">selezionati</span>
                        <button class="btn btn-sm btn-outline-light" @click="clearSelection">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Bulk Status -->
                        <div class="bulk-action-group">
                            <select v-model="bulkStatusCode" class="form-select form-select-sm bulk-select">
                                <option value="">Stato...</option>
                                <option v-for="ts in transactionStatuses" :key="ts.code" :value="ts.code">
                                    {{ ts.name }}
                                </option>
                            </select>
                            <button
                                class="btn btn-sm btn-light"
                                :disabled="!bulkStatusCode || bulkApplying"
                                @click="applyBulkStatus"
                            >
                                <i class="ri-check-line"></i>
                            </button>
                        </div>

                        <!-- Bulk Payment Date -->
                        <div class="bulk-action-group">
                            <input
                                v-model="bulkPaymentDate"
                                type="date"
                                class="form-control form-control-sm bulk-select"
                                style="min-width: 140px;"
                                title="Data Pagamento"
                            />
                            <button
                                class="btn btn-sm btn-light"
                                :disabled="!bulkPaymentDate || bulkApplying"
                                @click="applyBulkPaymentDate"
                            >
                                <i class="ri-check-line"></i>
                            </button>
                        </div>

                        <!-- Bulk Payment Type -->
                        <div class="bulk-action-group">
                            <select v-model="bulkPaymentType" class="form-select form-select-sm bulk-select" style="min-width: 140px;">
                                <option value="">Pagamento...</option>
                                <option v-for="type in paymentTypes" :key="type.id" :value="type.name">
                                    {{ type.name }}
                                </option>
                            </select>
                            <button
                                class="btn btn-sm btn-light"
                                :disabled="!bulkPaymentType || bulkApplying"
                                @click="applyBulkPaymentType"
                            >
                                <i class="ri-check-line"></i>
                            </button>
                        </div>

                        <span class="bulk-divider d-none d-sm-inline">|</span>

                        <!-- Bulk Delete -->
                        <button
                            v-if="canDelete"
                            class="btn btn-sm btn-danger"
                            :disabled="bulkApplying"
                            @click="deleteSelectedTransactions"
                        >
                            <span v-if="bulkApplying" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="ri-delete-bin-line me-1"></i>
                            Elimina
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useUrlFilters } from '@/composables/useUrlFilters.js';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';
import { useNotify } from '@/composables/useNotify.js';
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
    setup() {
        const notify = useNotify();
        const transactions = ref([]);
        const companies = ref([]);
        const services = ref([]);
        const counterparts = ref([]);
        const accountingEntries = ref([]);
        const transactionStatuses = ref([]);
        const loading = ref(false);
        const showFilters = ref(false);
        const showSummary = ref(true);
        const perPage = ref(10);
        const sortField = ref('transaction_date');
        const sortDirection = ref('desc');
        const selectedTransactions = ref([]);
        const bulkStatusCode = ref('');
        const bulkPaymentDate = ref('');
        const bulkPaymentType = ref('');
        const bulkApplying = ref(false);
        const editingStatus = ref(null);
        const editingStatusValue = ref('');
        const editingInstallment = ref(null);
        const editingInstallmentValue = ref('');

        // Transaction modal state
        const showTransactionModal = ref(false);
        const loadingModal = ref(false);
        const savingTransaction = ref(false);
        const modalServices = ref([]);
        const modalCounterparts = ref([]);
        const paymentTypes = ref([]);
        const transactionForm = ref({
            id: null,
            service_id: '',
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

        const filters = ref({
            company_id: '',
            search: '',
            transaction_type: '',
            status: '',
            service_id: '',
            accounting_entry_id: '',
            counterpart_id: '',
            client_id: '',
            supplier_id: '',
            intermediary_id: '',
            driver_id: '',
            start_date: '',
            end_date: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        });

        // URL sync: use a proxy ref for currentPage
        const currentPageRef = ref(1);
        watch(currentPageRef, (v) => { pagination.value.current_page = v; });
        const { readFromUrl, withReturnUrl } = useUrlFilters(filters, {
            page: currentPageRef,
            sortField,
            sortDirection,
        });

        const summary = ref({
            total: 0,
            sales: 0,
            purchases: 0,
            intermediations: 0,
            supplierRefunds: 0,
            customerRefunds: 0,
        });

        const user = ref(null);

        const isSuperAdmin = computed(() => user.value?.role === 'super-admin');
        const canDelete = computed(() => user.value?.role === 'super-admin' || user.value?.role === 'admin');

        const isAllSelected = computed(() => {
            return transactions.value.length > 0 &&
                   selectedTransactions.value.length === transactions.value.length;
        });

        const hasActiveFilters = computed(() => {
            return Object.values(filters.value).some(v => v !== '' && v !== null);
        });

        const activeFiltersCount = computed(() => {
            return Object.values(filters.value).filter(v => v !== '' && v !== null).length;
        });

        const visiblePages = computed(() => {
            const pages = [];
            const maxVisiblePages = 5;
            const halfVisible = Math.floor(maxVisiblePages / 2);

            let startPage = Math.max(1, pagination.value.current_page - halfVisible);
            let endPage = Math.min(pagination.value.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                pages.push(i);
            }

            return pages;
        });

        const loadUser = async () => {
            try {
                const response = await axios.get('/api/user');
                user.value = response.data;
            } catch (error) {
                console.error('Error loading user:', error);
            }
        };

        const loadCompanies = async () => {
            try {
                const response = await axios.get('/api/companies');
                companies.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        };

        const loadServices = async () => {
            try {
                const params = {};
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/accounting-transactions/services', { params });
                services.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading services:', error);
            }
        };

        const searchCounterparts = async (query) => {
            try {
                const params = {};
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                if (query && query.length >= 2) {
                    params.search = query;
                }
                const response = await axios.get('/api/accounting-transactions/counterparts', { params });
                const users = response.data.data || [];
                return users.map(u => ({
                    value: u.id,
                    label: `${u.surname || ''} ${u.name || ''}`.trim() || u.email,
                    type_label: u.type_label || u.role,
                }));
            } catch (error) {
                console.error('Error loading counterparts:', error);
                return [];
            }
        };

        const makeUserSearch = (extraParams) => async (query) => {
            try {
                const params = { light: true, per_page: 30, ...extraParams };
                if (filters.value.company_id) params.company_id = filters.value.company_id;
                if (query && query.length >= 1) params.search = query;
                const response = await axios.get('/api/users', { params });
                return (response.data.data || []).map(u => ({
                    value: u.id,
                    label: `${u.surname || ''} ${u.name || ''}`.trim() || u.email,
                }));
            } catch (error) {
                console.error('Error searching users:', error);
                return [];
            }
        };

        const searchClients = makeUserSearch({ is_committente: 1 });
        const searchSuppliers = makeUserSearch({ is_fornitore: 1 });
        const searchIntermediaries = makeUserSearch({ is_intermediario: 1 });
        const searchDrivers = makeUserSearch({ role: 'driver' });

        const loadAccountingEntries = async () => {
            try {
                const params = {
                    per_page: 1000, // Load all entries
                };
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/dictionaries/accounting-entries', { params });
                accountingEntries.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading accounting entries:', error);
            }
        };

        const loadTransactionStatuses = async () => {
            try {
                const params = {};
                if (filters.value.company_id) {
                    params.company_id = filters.value.company_id;
                }
                const response = await axios.get('/api/dictionaries/transaction-statuses', { params });
                transactionStatuses.value = response.data.data || [];
            } catch (error) {
                console.error('Error loading transaction statuses:', error);
            }
        };

        const loadTransactions = async (page = 1) => {
            loading.value = true;
            selectedTransactions.value = []; // Reset selection on page change
            try {
                const params = {
                    page,
                    per_page: perPage.value,
                    sort_by: sortField.value,
                    sort_order: sortDirection.value,
                    ...filters.value,
                };

                // Load paginated transactions and summary totals in parallel
                const [transResponse] = await Promise.all([
                    axios.get('/api/accounting-transactions', { params }),
                    loadSummary(),
                ]);

                if (transResponse.data.success) {
                    transactions.value = transResponse.data.data;
                    pagination.value = transResponse.data.meta;
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            } finally {
                loading.value = false;
            }
        };

        const loadSummary = async () => {
            try {
                const params = { ...filters.value };
                const response = await axios.get('/api/accounting-transactions/summary', { params });

                if (response.data.success) {
                    summary.value = response.data.data;
                }
            } catch (error) {
                console.error('Error loading summary:', error);
            }
        };

        const sortBy = (field) => {
            if (sortField.value === field) {
                // Toggle direction
                sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
            } else {
                sortField.value = field;
                sortDirection.value = 'asc';
            }
            loadTransactions(1);
        };

        const goToPage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                loadTransactions(page);
            }
        };

        const changePerPage = () => {
            loadTransactions(1);
        };

        const resetFilters = () => {
            filters.value = {
                company_id: '',
                search: '',
                transaction_type: '',
                status: '',
                service_id: '',
                accounting_entry_id: '',
                counterpart_id: '',
                client_id: '',
                supplier_id: '',
                intermediary_id: '',
                driver_id: '',
                start_date: '',
                end_date: '',
            };
            loadTransactions(1);
        };

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedTransactions.value = [];
            } else {
                selectedTransactions.value = transactions.value.map(t => t.id);
            }
        };

        const clearSelection = () => {
            selectedTransactions.value = [];
            bulkStatusCode.value = '';
            bulkPaymentDate.value = '';
            bulkPaymentType.value = '';
        };

        const deleteTransaction = async (id) => {
            const confirmed = await notify.confirm('Conferma eliminazione', 'Sei sicuro di voler eliminare questo movimento?');
            if (confirmed) {
                try {
                    await axios.delete(`/api/accounting-transactions/${id}`);
                    selectedTransactions.value = [];
                    loadTransactions(pagination.value.current_page);
                } catch (error) {
                    console.error('Error deleting transaction:', error);
                    notify.error('Errore durante l\'eliminazione del movimento');
                }
            }
        };

        const deleteSelectedTransactions = async () => {
            if (selectedTransactions.value.length === 0) return;

            const confirmed = await notify.confirm('Conferma eliminazione', `Eliminare ${selectedTransactions.value.length} movimenti selezionati?`, { confirmText: 'Elimina' });
            if (!confirmed) return;

            bulkApplying.value = true;
            try {
                await Promise.all(
                    selectedTransactions.value.map(id =>
                        axios.delete(`/api/accounting-transactions/${id}`)
                    )
                );
                clearSelection();
                loadTransactions(pagination.value.current_page);
            } catch (error) {
                console.error('Error deleting transactions:', error);
            } finally {
                bulkApplying.value = false;
            }
        };

        const applyBulkStatus = async () => {
            if (!bulkStatusCode.value || selectedTransactions.value.length === 0) return;

            const statusObj = transactionStatuses.value.find(s => s.code === bulkStatusCode.value);
            const statusName = statusObj?.name || bulkStatusCode.value;

            const confirmed = await notify.confirmInfo(`Modifica stato di ${selectedTransactions.value.length} movimenti`, `Impostare lo stato "${statusName}" per i movimenti selezionati?`, { confirmText: 'Applica' });
            if (!confirmed) return;

            bulkApplying.value = true;
            try {
                await Promise.all(
                    selectedTransactions.value.map(id =>
                        axios.put(`/api/accounting-transactions/${id}`, { status: bulkStatusCode.value })
                    )
                );
                clearSelection();
                loadTransactions(pagination.value.current_page);
            } catch (error) {
                console.error('Error updating status:', error);
            } finally {
                bulkApplying.value = false;
            }
        };

        const applyBulkPaymentDate = async () => {
            if (!bulkPaymentDate.value || selectedTransactions.value.length === 0) return;

            const confirmed = await notify.confirmInfo(`Modifica data pagamento di ${selectedTransactions.value.length} movimenti`, `Impostare la data "${moment(bulkPaymentDate.value).format('DD/MM/YYYY')}"?`, { confirmText: 'Applica' });
            if (!confirmed) return;

            bulkApplying.value = true;
            try {
                await Promise.all(
                    selectedTransactions.value.map(id =>
                        axios.put(`/api/accounting-transactions/${id}`, { payment_date: bulkPaymentDate.value })
                    )
                );
                clearSelection();
                loadTransactions(pagination.value.current_page);
            } catch (error) {
                console.error('Error updating payment date:', error);
            } finally {
                bulkApplying.value = false;
            }
        };

        const applyBulkPaymentType = async () => {
            if (!bulkPaymentType.value || selectedTransactions.value.length === 0) return;

            const confirmed = await notify.confirmInfo(`Modifica modalità pagamento di ${selectedTransactions.value.length} movimenti`, `Impostare "${bulkPaymentType.value}"?`, { confirmText: 'Applica' });
            if (!confirmed) return;

            bulkApplying.value = true;
            try {
                await Promise.all(
                    selectedTransactions.value.map(id =>
                        axios.put(`/api/accounting-transactions/${id}`, { payment_type: bulkPaymentType.value })
                    )
                );
                clearSelection();
                loadTransactions(pagination.value.current_page);
            } catch (error) {
                console.error('Error updating payment type:', error);
            } finally {
                bulkApplying.value = false;
            }
        };

        // Inline status editing functions
        const startEditStatus = (transaction) => {
            editingStatus.value = transaction.id;
            editingStatusValue.value = transaction.status;
        };

        const saveStatus = async (transaction) => {
            try {
                await axios.put(`/api/accounting-transactions/${transaction.id}`, {
                    status: editingStatusValue.value,
                });
                // Update local data
                transaction.status = editingStatusValue.value;
                editingStatus.value = null;
                editingStatusValue.value = '';
                // Reload summary
                loadSummary();
            } catch (error) {
                console.error('Error updating status:', error);
                notify.error('Errore durante l\'aggiornamento dello stato');
            }
        };

        const cancelEditStatus = () => {
            editingStatus.value = null;
            editingStatusValue.value = '';
        };

        const startEditInstallment = (transaction) => {
            editingInstallment.value = transaction.id;
            editingInstallmentValue.value = transaction.installment;
        };

        const saveInstallment = async (transaction) => {
            if (!editingInstallmentValue.value) return;
            try {
                await axios.put(`/api/accounting-transactions/${transaction.id}`, {
                    installment: editingInstallmentValue.value,
                });
                transaction.installment = editingInstallmentValue.value;
                editingInstallment.value = null;
                editingInstallmentValue.value = '';
                loadSummary();
            } catch (error) {
                console.error('Error updating installment:', error);
                notify.error('Errore durante l\'aggiornamento della rata');
            }
        };

        const cancelEditInstallment = () => {
            editingInstallment.value = null;
            editingInstallmentValue.value = '';
        };

        // --- Transaction Modal functions ---
        const loadModalDropdowns = async () => {
            try {
                const params = {};
                if (filters.value.company_id) params.company_id = filters.value.company_id;

                const [servicesRes, counterpartsRes, paymentTypesRes] = await Promise.all([
                    axios.get('/api/accounting-transactions/services', { params }),
                    axios.get('/api/accounting-transactions/counterparts', { params }),
                    axios.get('/api/dictionaries/payment-types', { params }),
                ]);
                modalServices.value = servicesRes.data.data || [];
                modalCounterparts.value = counterpartsRes.data.data || [];
                paymentTypes.value = paymentTypesRes.data.data || [];
            } catch (error) {
                console.error('Error loading modal dropdowns:', error);
            }
        };

        const populateTransactionForm = (tx) => {
            transactionForm.value = {
                id: tx.id,
                service_id: tx.service_id || '',
                transaction_date: tx.transaction_date ? moment.utc(tx.transaction_date).format('YYYY-MM-DD') : '',
                amount: tx.amount || 0,
                transaction_type: tx.transaction_type || '',
                installment: tx.installment || '',
                accounting_entry_id: tx.accounting_entry_id || '',
                counterpart_id: tx.counterpart_id || '',
                document_number: tx.document_number || '',
                document_due_date: tx.document_due_date ? moment.utc(tx.document_due_date).format('YYYY-MM-DD') : '',
                payment_date: tx.payment_date ? moment.utc(tx.payment_date).format('YYYY-MM-DD') : '',
                payment_type: tx.payment_type || '',
                payment_reason: tx.payment_reason || '',
                iban: tx.iban || '',
                status: tx.status || '',
                notes: tx.notes || '',
                is_automatic: tx.is_automatic || false,
            };
        };

        const openTransactionModal = async (transaction = null) => {
            // Open modal immediately with spinner
            loadingModal.value = true;
            showTransactionModal.value = true;

            if (transaction) {
                // Pre-fill from list data for instant feedback
                populateTransactionForm(transaction);
            } else {
                transactionForm.value = {
                    id: null, service_id: '', transaction_date: moment().format('YYYY-MM-DD'),
                    amount: 0, transaction_type: '', installment: '', accounting_entry_id: '',
                    counterpart_id: '', document_number: '', document_due_date: '',
                    payment_date: '', payment_type: '', payment_reason: '',
                    iban: '', status: '', notes: '', is_automatic: false,
                };
            }

            try {
                // Load dropdowns and fresh transaction data in parallel
                const promises = [loadModalDropdowns()];
                if (transaction) {
                    promises.push(
                        axios.get(`/api/accounting-transactions/${transaction.id}`).then(res => {
                            populateTransactionForm(res.data.data);
                        })
                    );
                }
                await Promise.all(promises);
            } catch (error) {
                console.error('Error loading modal data:', error);
            } finally {
                loadingModal.value = false;
            }
        };

        const closeTransactionModal = () => {
            showTransactionModal.value = false;
        };

        const cancelTransactionEdit = () => {
            transactionForm.value = {
                id: null, service_id: '', transaction_date: '', amount: 0,
                transaction_type: '', installment: '', accounting_entry_id: '',
                counterpart_id: '', document_number: '', document_due_date: '',
                payment_date: '', payment_type: '', payment_reason: '',
                iban: '', status: '', notes: '', is_automatic: false,
            };
            showTransactionModal.value = false;
        };

        const onTransactionTypeChange = () => {
            transactionForm.value.counterpart_id = '';
            transactionForm.value.status = '';
        };

        const saveTransaction = async () => {
            if (!transactionForm.value.transaction_date || !transactionForm.value.amount || !transactionForm.value.transaction_type || !transactionForm.value.installment || !transactionForm.value.status) {
                notify.warning('Compila tutti i campi obbligatori');
                return;
            }
            savingTransaction.value = true;
            try {
                const payload = {
                    service_id: transactionForm.value.service_id || null,
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

                if (transactionForm.value.id) {
                    await axios.put(`/api/accounting-transactions/${transactionForm.value.id}`, payload);
                } else {
                    await axios.post('/api/accounting-transactions', payload);
                }

                closeTransactionModal();
                cancelTransactionEdit();
                loadTransactions(pagination.value.current_page);
            } catch (error) {
                console.error('Error saving transaction:', error);
                if (error.response?.status === 422) {
                    const errors = error.response.data.errors;
                    const errorMessages = Object.values(errors).flat().join('\n');
                    notify.error('Errori di validazione:\n' + errorMessages);
                } else {
                    notify.error('Errore durante il salvataggio del movimento');
                }
            } finally {
                savingTransaction.value = false;
            }
        };

        const formatDate = (date) => {
            return date ? moment.utc(date).format('DD/MM/YYYY') : '-';
        };

        const getTransactionTypeLabel = (type) => {
            const labels = {
                purchase: 'Acquisto',
                sale: 'Vendita',
                intermediation: 'Intermediazione',
            };
            return labels[type] || type;
        };

        const getTransactionTypeAbbr = (type) => {
            const abbrs = {
                purchase: 'ACQ',
                sale: 'VEN',
                intermediation: 'INT',
            };
            return abbrs[type] || type;
        };

        const getTransactionTypeBadgeClass = (type) => {
            const classes = {
                purchase: 'badge bg-danger-subtle text-danger',
                sale: 'badge bg-success-subtle text-success',
                intermediation: 'badge bg-warning-subtle text-warning',
            };
            return classes[type] || 'badge bg-secondary-subtle text-secondary';
        };

        const getInstallmentLabel = (installment) => {
            const labels = {
                deposit: 'Acconto',
                balance: 'Saldo',
                supplier_refund: 'Reso Fornitore',
                customer_refund: 'Rimborso Cliente',
            };
            return labels[installment] || installment;
        };

        const getInstallmentAbbr = (installment) => {
            const abbrs = {
                deposit: 'ACC',
                balance: 'SAL',
                supplier_refund: 'RES',
                customer_refund: 'RIM',
            };
            return abbrs[installment] || installment;
        };

        const getStatusLabel = (status) => {
            const found = transactionStatuses.value.find(s => s.code === status);
            return found ? found.name : status;
        };

        const getStatusAbbr = (status) => {
            const found = transactionStatuses.value.find(s => s.code === status);
            return found ? (found.abbreviation || found.name) : status;
        };

        const bootstrapMap = {
            primary: '#405189', secondary: '#6c757d', success: '#0ab39c',
            danger: '#f06548', warning: '#f7b84b', info: '#299cdb',
        };

        const getStatusBadgeStyle = (status) => {
            const fallback = { backgroundColor: '#6c757d20', color: '#6c757d', fontWeight: '500' };
            const found = transactionStatuses.value.find(s => s.code === status);
            if (!found || !found.color) return fallback;
            const hex = found.color.startsWith('#') ? found.color : (bootstrapMap[found.color] || '#6c757d');
            return { backgroundColor: hex + '20', color: hex, fontWeight: '500' };
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
            if (!transaction.document_due_date) return '';

            const dueDate = moment.utc(transaction.document_due_date);
            const today = moment();

            // Se stato finale, non evidenziare
            if (isStatusFinal(transaction.status)) {
                return '';
            }

            // Se scaduto
            if (dueDate.isBefore(today, 'day')) {
                return 'text-danger fw-bold';
            }

            // Se scade entro 7 giorni
            if (dueDate.diff(today, 'days') <= 7) {
                return 'text-warning fw-bold';
            }

            return '';
        };

        const formatAmount = (amount) => {
            return new Intl.NumberFormat('it-IT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Math.abs(amount));
        };

        onMounted(async () => {
            await loadUser();
            readFromUrl();

            // Load all dropdown data and transactions in parallel
            const promises = [
                loadServices(),
                loadAccountingEntries(),
                loadTransactionStatuses(),
                loadTransactions(),
                loadModalDropdowns(),
            ];
            if (isSuperAdmin.value) {
                promises.push(loadCompanies());
            }
            await Promise.all(promises);
        });

        // Watch multiselect filter values — @change on @vueform/multiselect fires
        // before v-model is updated, so we use watchers to ensure the correct value
        // is sent to the API.
        const multiselectKeys = ['counterpart_id', 'client_id', 'supplier_id', 'intermediary_id', 'driver_id'];
        multiselectKeys.forEach(key => {
            watch(() => filters.value[key], () => loadTransactions(1));
        });

        return {
            transactions,
            companies,
            services,
            searchCounterparts,
            searchClients,
            searchSuppliers,
            searchIntermediaries,
            searchDrivers,
            accountingEntries,
            transactionStatuses,
            loading,
            showFilters,
            showSummary,
            filters,
            pagination,
            perPage,
            sortField,
            sortDirection,
            summary,
            user,
            isSuperAdmin,
            canDelete,
            hasActiveFilters,
            activeFiltersCount,
            visiblePages,
            selectedTransactions,
            isAllSelected,
            bulkStatusCode,
            bulkPaymentDate,
            bulkPaymentType,
            bulkApplying,
            loadTransactions,
            sortBy,
            goToPage,
            changePerPage,
            resetFilters,
            toggleSelectAll,
            clearSelection,
            applyBulkStatus,
            applyBulkPaymentDate,
            applyBulkPaymentType,
            deleteTransaction,
            deleteSelectedTransactions,
            formatDate,
            formatAmount,
            getTransactionTypeLabel,
            getTransactionTypeAbbr,
            getTransactionTypeBadgeClass,
            getInstallmentLabel,
            getInstallmentAbbr,
            getStatusLabel,
            getStatusAbbr,
            getStatusBadgeStyle,
            getDueDateClass,
            filteredTransactionStatuses,
            editingStatus,
            editingStatusValue,
            startEditStatus,
            saveStatus,
            cancelEditStatus,
            editingInstallment,
            editingInstallmentValue,
            startEditInstallment,
            saveInstallment,
            cancelEditInstallment,
            // Transaction modal
            showTransactionModal,
            loadingModal,
            savingTransaction,
            transactionForm,
            modalServices,
            modalCounterparts,
            paymentTypes,
            openTransactionModal,
            closeTransactionModal,
            cancelTransactionEdit,
            onTransactionTypeChange,
            saveTransaction,
            route: window.route,
            withReturnUrl,
        };
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
    user-select: none;
}

.cursor-pointer:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* Bulk Action Bar */
.bulk-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #405189;
    color: white;
    padding: 12px 16px;
    z-index: 1050;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.25);
}

.bulk-divider {
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.2rem;
    line-height: 1;
    user-select: none;
}

.bulk-action-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.bulk-select {
    width: auto;
    min-width: 120px;
    max-width: 180px;
    font-size: 0.8rem;
    padding: 0.25rem 2rem 0.25rem 0.5rem;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

.bulk-select:focus {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 0.15rem rgba(255, 255, 255, 0.2);
}

.bulk-select option {
    background-color: #405189;
    color: white;
}

@media (max-width: 576px) {
    .bulk-action-bar {
        padding: 8px 12px;
    }
    .bulk-select {
        min-width: 100px;
        font-size: 0.75rem;
    }
}
</style>
