<template>
    <Head title="Utenti" />

    <Layout>
        <PageHeader title="Utenti" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Utenti</h5>
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
                            <Link :href="route('easyncc.users.create')" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>
                                Nuovo Utente
                            </Link>
                        </div>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Collapsible Filters Section -->
                        <div v-show="showFilters" class="border rounded p-3 mb-3 bg-light">
                            <BRow class="mb-3">
                                <BCol md="2" v-if="isSuperAdmin">
                                    <label class="form-label">Azienda</label>
                                    <select v-model="filters.company_id" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutte</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 3">
                                    <label class="form-label">Ricerca</label>
                                    <input
                                        v-model="filters.search"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Nome, email, username..."
                                        @input="applyFilters"
                                    />
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 2">
                                    <label class="form-label">Ruolo</label>
                                    <select v-model="filters.role" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="super-admin">Super Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="operator">Operatore</option>
                                        <option value="driver">Driver</option>
                                        <option value="collaboratore">Collaboratore</option>
                                        <option value="contabilita">Contabilità</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 1 : 2">
                                    <label class="form-label">Stato</label>
                                    <select v-model="filters.is_active" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Attivi</option>
                                        <option value="0">Inattivi</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 2">
                                    <label class="form-label">Intermediario</label>
                                    <select v-model="filters.is_intermediario" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Sì</option>
                                        <option value="0">No</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 1 : 2">
                                    <label class="form-label">Committente</label>
                                    <select v-model="filters.is_committente" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Sì</option>
                                        <option value="0">No</option>
                                    </select>
                                </BCol>
                                <BCol :md="isSuperAdmin ? 2 : 1">
                                    <label class="form-label">Fornitore</label>
                                    <select v-model="filters.is_fornitore" class="form-select form-select-sm" @change="applyFilters">
                                        <option value="">Tutti</option>
                                        <option value="1">Sì</option>
                                        <option value="0">No</option>
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
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="users.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="sortable" @click="sortBy('surname')">
                                            Utente
                                            <i v-if="sortField === 'surname'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" class="sortable" @click="sortBy('email')">
                                            Email
                                            <i v-if="sortField === 'email'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" class="sortable" @click="sortBy('role')">
                                            Ruolo
                                            <i v-if="sortField === 'role'" :class="`bx bx-${sortDirection === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                                        </th>
                                        <th scope="col" style="width: 60px;">Int.</th>
                                        <th scope="col" style="width: 60px;">Com.</th>
                                        <th scope="col" style="width: 60px;">For.</th>
                                        <th v-if="isSuperAdmin" scope="col">Azienda</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="fw-medium">{{ user.surname?.toUpperCase() }} {{ user.name }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>
                                            <span :class="`badge bg-${getRoleBadge(user.role)}`">
                                                {{ getRoleLabel(user.role) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="user.is_intermediario" class="badge bg-success">Sì</span>
                                            <span v-else class="badge" style="background-color: silver;">No</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="user.role === 'collaboratore' && user.client_profile?.is_committente" class="badge bg-success">Sì</span>
                                            <span v-else-if="user.role === 'collaboratore'" class="badge" style="background-color: silver;">No</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="user.role === 'collaboratore' && user.client_profile?.is_fornitore" class="badge bg-success">Sì</span>
                                            <span v-else-if="user.role === 'collaboratore'" class="badge" style="background-color: silver;">No</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td v-if="isSuperAdmin">{{ user.company?.name || '-' }}</td>
                                        <td>
                                            <span v-if="user.is_active" class="badge bg-success">
                                                Attivo
                                            </span>
                                            <span v-else class="badge" style="background-color: silver;">
                                                Inattivo
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-soft-secondary me-1"
                                                @click="viewUserContact(user.id)"
                                                title="Info Contatto"
                                            >
                                                <i class="bx bx-id-card"></i>
                                            </button>
                                            <button
                                                class="btn btn-sm btn-soft-info me-1"
                                                @click="viewUserDetails(user.id)"
                                                title="Visualizza Dettagli"
                                            >
                                                <i class="bx bx-show"></i>
                                            </button>
                                            <Link :href="route('easyncc.users.edit', user.id)" class="btn btn-sm btn-soft-primary me-1">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteUser(user.id)"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination Controls -->
                            <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="perPageSelect" class="form-label mb-0 text-nowrap">
                                        Record per pagina:
                                    </label>
                                    <select
                                        id="perPageSelect"
                                        v-model="perPage"
                                        class="form-select form-select-sm"
                                        style="width: auto;"
                                        @change="changePerPage"
                                    >
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                    </select>
                                    <span class="text-muted small">
                                        Totale: {{ totalRecords }} record
                                    </span>
                                </div>

                                <nav aria-label="Navigazione pagine">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                            <a class="page-link" href="#" @click.prevent="changePage(1)">
                                                <i class="bx bx-chevrons-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                                <i class="bx bx-chevron-left"></i>
                                            </a>
                                        </li>

                                        <template v-if="totalPages <= 7">
                                            <li
                                                v-for="page in totalPages"
                                                :key="page"
                                                class="page-item"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                    {{ page }}
                                                </a>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li
                                                v-if="currentPage > 3"
                                                class="page-item"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
                                            </li>
                                            <li v-if="currentPage > 4" class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>

                                            <li
                                                v-for="page in [currentPage - 1, currentPage, currentPage + 1]"
                                                :key="page"
                                                v-show="page > 0 && page <= totalPages"
                                                class="page-item"
                                                :class="{ active: page === currentPage }"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(page)">
                                                    {{ page }}
                                                </a>
                                            </li>

                                            <li v-if="currentPage < totalPages - 3" class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li
                                                v-if="currentPage < totalPages - 2"
                                                class="page-item"
                                            >
                                                <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                                    {{ totalPages }}
                                                </a>
                                            </li>
                                        </template>

                                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                                <i class="bx bx-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                            <a class="page-link" href="#" @click.prevent="changePage(totalPages)">
                                                <i class="bx bx-chevrons-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- No Data -->
                        <div v-else class="text-center text-muted py-5">
                            <p>Nessun utente trovato</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- User Contact Info Modal -->
        <BModal v-model="showContactModal" size="lg" :title="`Informazioni Contatto: ${contactUser?.username}`" hide-footer>
            <div v-if="loadingContact" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
            </div>

            <div v-else-if="contactUser">
                <!-- Dati Generali -->
                <div class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Contatti Generali</h6>
                    <BRow>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Nome Completo</strong>
                            <span>{{ contactUser.name }} {{ contactUser.surname }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Email</strong>
                            <span>{{ contactUser.email }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Telefono</strong>
                            <span>{{ contactUser.phone || '-' }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Indirizzo</strong>
                            <span>{{ contactUser.address || '-' }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="contactUser.postal_code">
                            <strong class="d-block text-muted small">CAP</strong>
                            <span>{{ contactUser.postal_code }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="contactUser.city">
                            <strong class="d-block text-muted small">Comune</strong>
                            <span>{{ contactUser.city }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="contactUser.province">
                            <strong class="d-block text-muted small">Provincia</strong>
                            <span>{{ contactUser.province }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="contactUser.country">
                            <strong class="d-block text-muted small">Nazione</strong>
                            <span>{{ contactUser.country }}</span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Dati Specifici Collaboratore -->
                <div v-if="contactUser.role === 'collaboratore' && contactUser.client_profile" class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Dati Aziendali</h6>
                    <BRow>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.business_name">
                            <strong class="d-block text-muted small">Ragione Sociale</strong>
                            <span>{{ contactUser.client_profile.business_name }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.trade_name">
                            <strong class="d-block text-muted small">Denominazione</strong>
                            <span>{{ contactUser.client_profile.trade_name }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.vat_number">
                            <strong class="d-block text-muted small">Partita IVA</strong>
                            <span>{{ contactUser.client_profile.vat_number }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.fiscal_code">
                            <strong class="d-block text-muted small">Codice Fiscale</strong>
                            <span>{{ contactUser.client_profile.fiscal_code }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.pec">
                            <strong class="d-block text-muted small">PEC</strong>
                            <span>{{ contactUser.client_profile.pec }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.phone">
                            <strong class="d-block text-muted small">Telefono Aziendale</strong>
                            <span>{{ contactUser.client_profile.phone }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.admin_email">
                            <strong class="d-block text-muted small">Email Amministrativa</strong>
                            <span>{{ contactUser.client_profile.admin_email }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="contactUser.client_profile.operational_email">
                            <strong class="d-block text-muted small">Email Operativa</strong>
                            <span>{{ contactUser.client_profile.operational_email }}</span>
                        </BCol>
                    </BRow>

                    <!-- Business Contacts -->
                    <div v-if="contactBusinessContacts && contactBusinessContacts.length > 0" class="mt-3 pt-3 border-top">
                        <h6 class="text-muted mb-3">Referenti Aziendali</h6>
                        <div v-for="(contact, index) in contactBusinessContacts" :key="index" class="border rounded p-3 bg-light mb-2">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-primary me-2">{{ index + 1 }}</span>
                                <strong>{{ contact.name || 'N/A' }}</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6" v-if="contact.phone">
                                    <small class="text-muted">Telefono:</small>
                                    <span class="d-block">{{ contact.phone }}</span>
                                </div>
                                <div class="col-md-6" v-if="contact.email">
                                    <small class="text-muted">Email:</small>
                                    <span class="d-block">{{ contact.email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <Link
                        v-if="contactUser"
                        :href="route('easyncc.users.edit', contactUser.id)"
                        class="btn btn-primary"
                    >
                        <i class="ri-edit-line me-1"></i> Modifica
                    </Link>
                    <button type="button" class="btn btn-light ms-auto" @click="showContactModal = false">
                        Chiudi
                    </button>
                </div>
            </div>
        </BModal>

        <!-- User Details Modal -->
        <BModal v-model="showDetailsModal" size="xl" :title="`Dettagli Utente: ${selectedUser?.name} ${selectedUser?.surname}`" hide-footer>
            <div v-if="loadingDetails" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
            </div>

            <div v-else-if="selectedUser">
                <!-- Dati Identificativi -->
                <div class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Dati Identificativi</h6>
                    <BRow>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Username</strong>
                            <span>{{ selectedUser.username }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Ruolo</strong>
                            <span :class="`badge bg-${getRoleBadge(selectedUser.role)}`">
                                {{ getRoleLabel(selectedUser.role) }}
                            </span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Azienda</strong>
                            <span>{{ selectedUser.company?.name || '-' }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Stato</strong>
                            <span v-if="selectedUser.is_active" class="badge bg-success">Attivo</span>
                            <span v-else class="badge bg-danger">Inattivo</span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Dati Anagrafici -->
                <div class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Dati Anagrafici</h6>
                    <BRow>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Nome</strong>
                            <span>{{ selectedUser.name }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Cognome</strong>
                            <span>{{ selectedUser.surname }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Email</strong>
                            <span>{{ selectedUser.email }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Telefono</strong>
                            <span>{{ selectedUser.phone || '-' }}</span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Intermediazione -->
                <div class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Intermediazione</h6>
                    <BRow>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">È Intermediario</strong>
                            <span class="badge" :class="selectedUser.is_intermediario ? 'bg-success' : 'bg-secondary'">
                                {{ selectedUser.is_intermediario ? 'Sì' : 'No' }}
                            </span>
                        </BCol>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Percentuale Commissione</strong>
                            <span>{{ selectedUser.percentuale_commissione ? selectedUser.percentuale_commissione + '%' : '-' }}</span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Note -->
                <div class="mb-4" v-if="selectedUser.notes">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Note</h6>
                    <p class="mb-0">{{ selectedUser.notes }}</p>
                </div>

                <!-- Driver Profile -->
                <div v-if="selectedUser.driver_profile" class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Dati Driver</h6>
                    <BRow>
                        <BCol md="3" class="mb-3" v-if="selectedUser.driver_profile.birth_date">
                            <strong class="d-block text-muted small">Data di Nascita</strong>
                            <span>{{ formatDate(selectedUser.driver_profile.birth_date) }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="selectedUser.driver_profile.fiscal_code">
                            <strong class="d-block text-muted small">Codice Fiscale</strong>
                            <span>{{ selectedUser.driver_profile.fiscal_code }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="selectedUser.driver_profile.vat_number">
                            <strong class="d-block text-muted small">Partita IVA</strong>
                            <span>{{ selectedUser.driver_profile.vat_number }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="selectedUser.driver_profile.hourly_rate">
                            <strong class="d-block text-muted small">Tariffa Oraria</strong>
                            <span>€ {{ selectedUser.driver_profile.hourly_rate }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3" v-if="selectedUser.driver_profile.bank_name">
                            <strong class="d-block text-muted small">Banca</strong>
                            <span>{{ selectedUser.driver_profile.bank_name }}</span>
                        </BCol>
                        <BCol md="6" class="mb-3" v-if="selectedUser.driver_profile.iban">
                            <strong class="d-block text-muted small">IBAN</strong>
                            <span>{{ selectedUser.driver_profile.iban }}</span>
                        </BCol>
                        <BCol md="3" class="mb-3">
                            <strong class="d-block text-muted small">Colore</strong>
                            <span class="d-inline-block" :style="`background-color: ${selectedUser.driver_profile.color || '#3788d8'}; width: 30px; height: 20px; border-radius: 4px;`"></span>
                        </BCol>
                        <BCol md="3" class="mb-3">
                            <strong class="d-block text-muted small">Sovrapposizioni Consentite</strong>
                            <span class="badge" :class="selectedUser.driver_profile.allow_overlapping ? 'bg-success' : 'bg-secondary'">
                                {{ selectedUser.driver_profile.allow_overlapping ? 'Sì' : 'No' }}
                            </span>
                        </BCol>
                        <BCol md="12" class="mb-3" v-if="selectedUser.driver_profile.notes">
                            <strong class="d-block text-muted small">Note</strong>
                            <span>{{ selectedUser.driver_profile.notes }}</span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Business Profile (Collaboratore) -->
                <div v-if="selectedUser.client_profile" class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">
                        Dati {{ getBusinessProfileLabel(selectedUser.role) }}
                    </h6>
                    <BRow>
                        <template v-if="businessProfile">
                            <BCol md="6" class="mb-3" v-if="businessProfile.business_name">
                                <strong class="d-block text-muted small">Ragione Sociale</strong>
                                <span>{{ businessProfile.business_name }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.trade_name">
                                <strong class="d-block text-muted small">Denominazione (Alias)</strong>
                                <span>{{ businessProfile.trade_name }}</span>
                            </BCol>
                            <BCol md="3" class="mb-3" v-if="businessProfile.vat_number">
                                <strong class="d-block text-muted small">Partita IVA</strong>
                                <span>{{ businessProfile.vat_number }}</span>
                            </BCol>
                            <BCol md="3" class="mb-3" v-if="businessProfile.fiscal_code">
                                <strong class="d-block text-muted small">Codice Fiscale</strong>
                                <span>{{ businessProfile.fiscal_code }}</span>
                            </BCol>
                            <BCol md="3" class="mb-3" v-if="businessProfile.sdi">
                                <strong class="d-block text-muted small">SDI</strong>
                                <span>{{ businessProfile.sdi }}</span>
                            </BCol>
                            <BCol md="3" class="mb-3" v-if="businessProfile.pec">
                                <strong class="d-block text-muted small">PEC</strong>
                                <span>{{ businessProfile.pec }}</span>
                            </BCol>
                            <BCol md="12" class="mb-3" v-if="businessProfile.address">
                                <strong class="d-block text-muted small">Indirizzo</strong>
                                <span>{{ businessProfile.address }}</span>
                            </BCol>
                            <BCol md="2" class="mb-3" v-if="businessProfile.postal_code">
                                <strong class="d-block text-muted small">CAP</strong>
                                <span>{{ businessProfile.postal_code }}</span>
                            </BCol>
                            <BCol md="4" class="mb-3" v-if="businessProfile.city">
                                <strong class="d-block text-muted small">Comune</strong>
                                <span>{{ businessProfile.city }}</span>
                            </BCol>
                            <BCol md="2" class="mb-3" v-if="businessProfile.province">
                                <strong class="d-block text-muted small">Provincia</strong>
                                <span>{{ businessProfile.province }}</span>
                            </BCol>
                            <BCol md="4" class="mb-3" v-if="businessProfile.country">
                                <strong class="d-block text-muted small">Nazione</strong>
                                <span>{{ businessProfile.country }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.admin_email">
                                <strong class="d-block text-muted small">Email Amministrativa</strong>
                                <span>{{ businessProfile.admin_email }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.operational_email">
                                <strong class="d-block text-muted small">Email Operativa</strong>
                                <span>{{ businessProfile.operational_email }}</span>
                            </BCol>
                            <BCol md="4" class="mb-3" v-if="businessProfile.phone">
                                <strong class="d-block text-muted small">Telefono</strong>
                                <span>{{ businessProfile.phone }}</span>
                            </BCol>
                            <BCol md="4" class="mb-3" v-if="businessProfile.website">
                                <strong class="d-block text-muted small">Sito Web</strong>
                                <span>{{ businessProfile.website }}</span>
                            </BCol>
                            <BCol md="4" class="mb-3" v-if="businessProfile.commission !== null && businessProfile.commission !== undefined">
                                <strong class="d-block text-muted small">Commissione</strong>
                                <span>{{ businessProfile.commission }}%</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.bank_name">
                                <strong class="d-block text-muted small">Banca</strong>
                                <span>{{ businessProfile.bank_name }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.iban">
                                <strong class="d-block text-muted small">IBAN</strong>
                                <span>{{ businessProfile.iban }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3" v-if="businessProfile.payment_method">
                                <strong class="d-block text-muted small">Modalità di Pagamento</strong>
                                <span>{{ businessProfile.payment_method }}</span>
                            </BCol>
                            <BCol md="6" class="mb-3">
                                <strong class="d-block text-muted small">È Committente</strong>
                                <span class="badge" :class="businessProfile.is_committente ? 'bg-success' : 'bg-secondary'">
                                    {{ businessProfile.is_committente ? 'Sì' : 'No' }}
                                </span>
                            </BCol>
                            <BCol md="6" class="mb-3">
                                <strong class="d-block text-muted small">È Fornitore</strong>
                                <span class="badge" :class="businessProfile.is_fornitore ? 'bg-success' : 'bg-secondary'">
                                    {{ businessProfile.is_fornitore ? 'Sì' : 'No' }}
                                </span>
                            </BCol>
                        </template>
                    </BRow>

                    <!-- Business Contacts Section -->
                    <div v-if="businessContacts && businessContacts.length > 0" class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">Referenti Aziendali</h6>
                        <BRow>
                            <BCol md="12" v-for="(contact, index) in businessContacts" :key="index" class="mb-3">
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2">{{ index + 1 }}</span>
                                        <strong>{{ contact.name || 'N/A' }}</strong>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6" v-if="contact.phone">
                                            <small class="text-muted">Telefono:</small>
                                            <span class="d-block">{{ contact.phone }}</span>
                                        </div>
                                        <div class="col-md-6" v-if="contact.email">
                                            <small class="text-muted">Email:</small>
                                            <span class="d-block">{{ contact.email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </BCol>
                        </BRow>
                    </div>
                    <div v-else class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">Referenti Aziendali</h6>
                        <p class="text-muted small">Nessun referente aziendale registrato</p>
                    </div>
                </div>

                <!-- Operator Profile -->
                <div v-if="selectedUser.operator_profile" class="mb-4">
                    <h6 class="text-muted border-bottom pb-2 mb-3">Dati Operatore</h6>
                    <BRow>
                        <BCol md="6" class="mb-3">
                            <strong class="d-block text-muted small">Ha accesso alla contabilità</strong>
                            <span class="badge" :class="selectedUser.operator_profile.is_contabilita ? 'bg-success' : 'bg-secondary'">
                                {{ selectedUser.operator_profile.is_contabilita ? 'Sì' : 'No' }}
                            </span>
                        </BCol>
                    </BRow>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <Link
                        v-if="selectedUser"
                        :href="route('easyncc.users.edit', selectedUser.id)"
                        class="btn btn-primary"
                    >
                        <i class="ri-edit-line me-1"></i> Modifica
                    </Link>
                    <button type="button" class="btn btn-light ms-auto" @click="showDetailsModal = false">
                        Chiudi
                    </button>
                </div>
            </div>
        </BModal>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';

const users = ref([]);
const loading = ref(false);
const error = ref('');
const companies = ref([]);
const currentUser = ref(null);
const filters = ref({
    company_id: '',
    search: '',
    role: '',
    is_active: '',
    is_intermediario: '',
    is_committente: '',
    is_fornitore: ''
});
const sortField = ref('');
const sortDirection = ref('asc');
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(0);
const showDetailsModal = ref(false);
const selectedUser = ref(null);
const loadingDetails = ref(false);
const showContactModal = ref(false);
const contactUser = ref(null);
const loadingContact = ref(false);
const showFilters = ref(true);

const loadUsers = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            ...filters.value,
            page: currentPage.value,
            per_page: perPage.value
        };

        if (sortField.value) {
            params.sort_by = sortField.value;
            params.sort_direction = sortDirection.value;
        }

        const response = await axios.get('/api/users', { params });
        users.value = response.data.data || [];

        // Handle pagination metadata - Laravel paginate() returns meta directly in response.data
        if (response.data.last_page !== undefined) {
            totalPages.value = response.data.last_page || 1;
            totalRecords.value = response.data.total || 0;
            currentPage.value = response.data.current_page || 1;
        } else if (response.data.meta) {
            // Alternative structure with meta object
            totalPages.value = response.data.meta.last_page || 1;
            totalRecords.value = response.data.meta.total || 0;
            currentPage.value = response.data.meta.current_page || 1;
        } else {
            // Fallback if no pagination metadata
            totalPages.value = 1;
            totalRecords.value = users.value.length;
        }
    } catch (err) {
        error.value = 'Errore nel caricamento degli utenti';
        console.error('Error loading users:', err);
    } finally {
        loading.value = false;
    }
};

const sortBy = (field) => {
    if (sortField.value === field) {
        // Toggle direction if clicking same field
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // New field, default to ascending
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    loadUsers();
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadUsers();
};

const changePerPage = () => {
    currentPage.value = 1; // Reset to first page when changing per page
    loadUsers();
};

const applyFilters = () => {
    currentPage.value = 1; // Reset to first page when filters change
    loadUsers();
};

const resetFilters = () => {
    filters.value = {
        company_id: '',
        search: '',
        role: '',
        is_active: '',
        is_intermediario: '',
        is_committente: '',
        is_fornitore: ''
    };
    currentPage.value = 1;
    loadUsers();
};

const hasActiveFilters = computed(() => {
    return filters.value.company_id !== '' ||
           filters.value.search !== '' ||
           filters.value.role !== '' ||
           filters.value.is_active !== '' ||
           filters.value.is_intermediario !== '' ||
           filters.value.is_committente !== '' ||
           filters.value.is_fornitore !== '';
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.company_id !== '') count++;
    if (filters.value.search !== '') count++;
    if (filters.value.role !== '') count++;
    if (filters.value.is_active !== '') count++;
    if (filters.value.is_intermediario !== '') count++;
    if (filters.value.is_committente !== '') count++;
    if (filters.value.is_fornitore !== '') count++;
    return count;
});

const deleteUser = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo utente?')) {
        return;
    }

    try {
        await axios.delete(`/api/users/${id}`);
        await loadUsers();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione dell\'utente';
        console.error('Error deleting user:', err);
    }
};

const getRoleLabel = (role) => {
    const labels = {
        'super-admin': 'Super Admin',
        'admin': 'Amministratore',
        'operator': 'Operatore',
        'driver': 'Driver',
        'collaboratore': 'Collaboratore',
        'contabilita': 'Contabilità'
    };
    return labels[role] || role;
};

const getRoleBadge = (role) => {
    const badges = {
        'super-admin': 'dark',
        'admin': 'danger',
        'operator': 'warning',
        'driver': 'info',
        'collaboratore': 'primary',
        'contabilita': 'success'
    };
    return badges[role] || 'secondary';
};

const isSuperAdmin = computed(() => {
    return currentUser.value?.role === 'super-admin';
});

const loadCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user');
        currentUser.value = response.data;
    } catch (err) {
        console.error('Error loading current user:', err);
    }
};

const loadCompanies = async () => {
    if (!isSuperAdmin.value) return;

    try {
        const response = await axios.get('/api/companies');
        companies.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading companies:', err);
    }
};

const viewUserDetails = (userId) => {
    router.visit(route('easyncc.users.show', userId));
};

const viewUserContact = async (userId) => {
    loadingContact.value = true;
    showContactModal.value = true;
    contactUser.value = null;

    try {
        const response = await axios.get(`/api/users/${userId}`);
        contactUser.value = response.data;
    } catch (err) {
        error.value = 'Errore nel caricamento delle informazioni di contatto';
        console.error('Error loading user contact:', err);
        showContactModal.value = false;
    } finally {
        loadingContact.value = false;
    }
};

const businessProfile = computed(() => {
    if (!selectedUser.value) return null;
    return selectedUser.value.client_profile;
});

const businessContacts = computed(() => {
    if (!selectedUser.value?.client_profile) return [];
    // Check both camelCase and snake_case due to Laravel serialization
    return selectedUser.value.client_profile.business_contacts ||
           selectedUser.value.client_profile.businessContacts ||
           [];
});

const contactBusinessContacts = computed(() => {
    if (!contactUser.value?.client_profile) return [];
    // Check both camelCase and snake_case due to Laravel serialization
    return contactUser.value.client_profile.business_contacts ||
           contactUser.value.client_profile.businessContacts ||
           [];
});

const getBusinessProfileLabel = (role) => {
    const labels = {
        'collaboratore': 'Collaboratore'
    };
    return labels[role] || '';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

onMounted(async () => {
    await loadCurrentUser();
    await loadCompanies();
    await loadUsers();
});
</script>
