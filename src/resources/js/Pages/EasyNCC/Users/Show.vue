<template>
    <Layout>
        <PageHeader :title="`Dettaglio Utente: ${user.name} ${user.surname}`" :items="items" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Informazioni Utente</h5>
                        <div>
                            <Link
                                v-if="canEdit"
                                :href="`/easyncc/users/${user.id}/edit`"
                                class="btn btn-soft-primary btn-sm me-2"
                            >
                                <i class="ri-edit-line me-1"></i> Modifica
                            </Link>
                            <Link
                                href="/easyncc/users"
                                class="btn btn-soft-secondary btn-sm"
                            >
                                <i class="ri-arrow-left-line me-1"></i> Torna alla Lista
                            </Link>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Basic Info -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Informazioni Base</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Azienda</label>
                                <p class="text-muted mb-0">{{ user.company?.name || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Ruolo</label>
                                <p class="mb-0">
                                    <span :class="getRoleBadgeClass(user.role)">
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Nome</label>
                                <p class="text-muted mb-0">{{ user.name }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Cognome</label>
                                <p class="text-muted mb-0">{{ user.surname }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Nickname</label>
                                <p class="text-muted mb-0">{{ user.nickname || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <p class="text-muted mb-0">{{ user.email }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <p class="text-muted mb-0">{{ user.username }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Telefono</label>
                                <p class="text-muted mb-0">{{ user.phone || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Nickname</label>
                                <p class="text-muted mb-0">{{ user.nickname || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Stato</label>
                                <p class="mb-0">
                                    <span :class="user.is_active ? 'badge bg-success' : 'badge bg-danger'">
                                        {{ user.is_active ? 'Attivo' : 'Non Attivo' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- User Photo -->
                        <div v-if="user.user_photo" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Fotografia Profilo</h6>
                            </div>
                            <div class="col-md-12 mb-3">
                                <img
                                    :src="`/storage/${user.user_photo}`"
                                    alt="Foto profilo"
                                    class="img-thumbnail"
                                    style="max-width: 300px; max-height: 300px;"
                                />
                            </div>
                        </div>

                        <!-- Address Info -->
                        <div class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Indirizzo</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Indirizzo</label>
                                <p class="text-muted mb-0">{{ user.address || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">CAP</label>
                                <p class="text-muted mb-0">{{ user.postal_code || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Città</label>
                                <p class="text-muted mb-0">{{ user.city || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Provincia</label>
                                <p class="text-muted mb-0">{{ user.province || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Nazione</label>
                                <p class="text-muted mb-0">{{ user.country || '-' }}</p>
                            </div>
                        </div>

                        <!-- Intermediazione Info -->
                        <div v-if="user.is_intermediario" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Informazioni Intermediazione</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">È Intermediario</label>
                                <p class="mb-0">
                                    <span class="badge bg-success">Sì</span>
                                </p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Percentuale Commissione</label>
                                <p class="text-muted mb-0">{{ user.percentuale_commissione || '-' }}%</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="user.notes" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Note</h6>
                            </div>
                            <div class="col-md-12 mb-3">
                                <p class="text-muted mb-0">{{ user.notes }}</p>
                            </div>
                        </div>

                        <!-- Driver Profile -->
                        <div v-if="user.role === 'driver' && user.driver_profile" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Informazioni Conducente</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Data di Nascita</label>
                                <p class="text-muted mb-0">{{ formatDate(user.driver_profile.birth_date) || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Codice Fiscale</label>
                                <p class="text-muted mb-0">{{ user.driver_profile.fiscal_code || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">P. IVA</label>
                                <p class="text-muted mb-0">{{ user.driver_profile.vat_number || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Tariffa Oraria</label>
                                <p class="text-muted mb-0">
                                    {{ user.driver_profile.hourly_rate ? `€ ${user.driver_profile.hourly_rate}` : '-' }}
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Istituto Bancario</label>
                                <p class="text-muted mb-0">{{ user.driver_profile.bank_name || '-' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">IBAN</label>
                                <p class="text-muted mb-0">{{ user.driver_profile.iban || '-' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Veicolo Assegnato</label>
                                <p class="text-muted mb-0">
                                    <span v-if="user.driver_profile.assigned_vehicle">
                                        {{ user.driver_profile.assigned_vehicle.plate }} -
                                        {{ user.driver_profile.assigned_vehicle.brand }}
                                        {{ user.driver_profile.assigned_vehicle.model }}
                                    </span>
                                    <span v-else>-</span>
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Colore Identificativo</label>
                                <p class="mb-0">
                                    <span
                                        v-if="user.driver_profile.color"
                                        class="badge"
                                        :style="{ backgroundColor: user.driver_profile.color, color: '#fff' }"
                                    >
                                        {{ user.driver_profile.color }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Sovrapposizioni Orario</label>
                                <p class="mb-0">
                                    <span :class="user.driver_profile.allow_overlap ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ user.driver_profile.allow_overlap ? 'Consentite' : 'Non Consentite' }}
                                    </span>
                                </p>
                            </div>
                            <div v-if="user.driver_profile.notes" class="col-12 mb-3">
                                <label class="form-label fw-semibold">Note</label>
                                <p class="text-muted mb-0">{{ user.driver_profile.notes }}</p>
                            </div>
                        </div>

                        <!-- Client Profile -->
                        <div v-if="user.role === 'collaboratore' && user.client_profile" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Informazioni Cliente/Fornitore/Intermediario</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Ragione Sociale</label>
                                <p class="text-muted mb-0">{{ user.client_profile.business_name || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Denominazione</label>
                                <p class="text-muted mb-0">{{ user.client_profile.trade_name || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">P. IVA</label>
                                <p class="text-muted mb-0">{{ user.client_profile.vat_number || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Codice Fiscale</label>
                                <p class="text-muted mb-0">{{ user.client_profile.fiscal_code || '-' }}</p>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-semibold">Committente</label>
                                <p class="mb-0">
                                    <span :class="user.client_profile.is_committente ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ user.client_profile.is_committente ? 'Sì' : 'No' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-semibold">Fornitore</label>
                                <p class="mb-0">
                                    <span :class="user.client_profile.is_fornitore ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ user.client_profile.is_fornitore ? 'Sì' : 'No' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Commissione %</label>
                                <p class="text-muted mb-0">{{ user.client_profile.commission ? user.client_profile.commission + '%' : '-' }}</p>
                            </div>

                            <!-- Logo -->
                            <div v-if="user.client_profile.logo" class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Logo Aziendale</label>
                                <div>
                                    <img
                                        :src="`/storage/${user.client_profile.logo}`"
                                        alt="Logo aziendale"
                                        class="img-thumbnail"
                                        style="max-width: 200px; max-height: 200px;"
                                    />
                                </div>
                            </div>

                            <!-- Business Contacts -->
                            <div v-if="user.client_profile.business_contacts && user.client_profile.business_contacts.length > 0" class="col-12 mt-3">
                                <h6 class="text-muted mb-3">Referenti Aziendali</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Telefono</th>
                                                <th>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(contact, index) in user.client_profile.business_contacts" :key="index">
                                                <td>{{ contact.name || '-' }}</td>
                                                <td>{{ contact.phone || '-' }}</td>
                                                <td>{{ contact.email || '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Driver Attachments -->
                        <div v-if="user.role === 'driver' && user.id" class="pt-3 border-top">
                            <DriverAttachmentsReadOnly :user-id="user.id" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import DriverAttachmentsReadOnly from '@/Components/ProfileFields/DriverAttachmentsReadOnly.vue';
import moment from 'moment';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

const items = computed(() => [
    { text: 'EasyNCC', href: '/easyncc' },
    { text: 'Utenti', href: '/easyncc/users' },
    { text: `${props.user.name} ${props.user.surname}`, active: true }
]);

const getRoleLabel = (role) => {
    const roles = {
        'super-admin': 'Super Admin',
        'admin': 'Amministratore',
        'operator': 'Operatore',
        'driver': 'Conducente',
        'collaboratore': 'Collaboratore',
        'contabilita': 'Contabilità'
    };
    return roles[role] || role;
};

const getRoleBadgeClass = (role) => {
    const classes = {
        'super-admin': 'badge bg-danger',
        'admin': 'badge bg-primary',
        'operator': 'badge bg-info',
        'driver': 'badge bg-success',
        'collaboratore': 'badge bg-warning',
        'contabilita': 'badge bg-secondary'
    };
    return classes[role] || 'badge bg-secondary';
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};
</script>
