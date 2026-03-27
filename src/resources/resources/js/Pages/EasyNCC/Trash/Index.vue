<template>
    <Head title="Cestino" />
    <Layout>
        <PageHeader title="Cestino" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <ul class="nav nav-tabs card-header-tabs">
                            <li v-for="tab in tabs" :key="tab.key" class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: activeTab === tab.key }"
                                    href="#"
                                    @click.prevent="switchTab(tab.key)"
                                >
                                    <i :class="tab.icon" class="me-1"></i>
                                    {{ tab.label }}
                                    <span v-if="counts[tab.key] > 0" class="badge bg-danger ms-1">{{ counts[tab.key] }}</span>
                                </a>
                            </li>
                        </ul>
                    </BCardHeader>
                    <BCardBody>
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>

                        <div v-else-if="items.length === 0" class="text-center text-muted py-5">
                            <i class="ri-delete-bin-line" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-2">Nessun elemento nel cestino per questa categoria.</p>
                        </div>

                        <div v-else class="table-responsive">
                            <!-- Services -->
                            <table v-if="activeTab === 'services'" class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Rif.</th>
                                        <th>Data Pickup</th>
                                        <th>Passeggero</th>
                                        <th>Veicolo</th>
                                        <th>Eliminato il</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td class="fw-bold">{{ item.reference_number || '#' + item.id }}</td>
                                        <td>{{ formatDateTime(item.pickup_datetime) }}</td>
                                        <td>
                                            <span v-if="item.passengers && item.passengers.length > 0">
                                                {{ item.passengers[0].surname }} {{ item.passengers[0].name }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span v-if="item.vehicle">{{ item.vehicle.license_plate }}</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="text-muted small">{{ formatDateTime(item.deleted_at) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="restoreItem(item.id)" :disabled="processing" title="Ripristina">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="forceDeleteItem(item.id)" :disabled="processing" title="Elimina definitivamente">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Users -->
                            <table v-if="activeTab === 'users'" class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Cognome</th>
                                        <th>Ruolo</th>
                                        <th>Email</th>
                                        <th>Eliminato il</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td>{{ item.name }}</td>
                                        <td class="fw-bold">{{ item.surname }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ item.role }}</span></td>
                                        <td>{{ item.email }}</td>
                                        <td class="text-muted small">{{ formatDateTime(item.deleted_at) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="restoreItem(item.id)" :disabled="processing" title="Ripristina">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="forceDeleteItem(item.id)" :disabled="processing" title="Elimina definitivamente">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Vehicles -->
                            <table v-if="activeTab === 'vehicles'" class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Targa</th>
                                        <th>Marca</th>
                                        <th>Modello</th>
                                        <th>Eliminato il</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td class="fw-bold">{{ item.license_plate }}</td>
                                        <td>{{ item.brand }}</td>
                                        <td>{{ item.model }}</td>
                                        <td class="text-muted small">{{ formatDateTime(item.deleted_at) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="restoreItem(item.id)" :disabled="processing" title="Ripristina">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="forceDeleteItem(item.id)" :disabled="processing" title="Elimina definitivamente">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Quotes -->
                            <table v-if="activeTab === 'quotes'" class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Cliente</th>
                                        <th>Data</th>
                                        <th>Prezzo</th>
                                        <th>Stato</th>
                                        <th>Eliminato il</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td class="fw-bold">#{{ item.id }} <small class="text-muted">v{{ item.version }}</small></td>
                                        <td>{{ item.client_name || '-' }}</td>
                                        <td>{{ formatDate(item.service_date) }}</td>
                                        <td>{{ formatCurrency(item.final_price_rounded) }}</td>
                                        <td><span class="badge bg-secondary">{{ item.status }}</span></td>
                                        <td class="text-muted small">{{ formatDateTime(item.deleted_at) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="restoreItem(item.id)" :disabled="processing" title="Ripristina">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="forceDeleteItem(item.id)" :disabled="processing" title="Elimina definitivamente">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Contacts -->
                            <table v-if="activeTab === 'contacts'" class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefono</th>
                                        <th>Eliminato il</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td class="fw-bold">{{ item.name }}</td>
                                        <td>{{ item.email || '-' }}</td>
                                        <td>{{ item.phone || '-' }}</td>
                                        <td class="text-muted small">{{ formatDateTime(item.deleted_at) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-soft-success me-1" @click="restoreItem(item.id)" :disabled="processing" title="Ripristina">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="forceDeleteItem(item.id)" :disabled="processing" title="Elimina definitivamente">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="successMessage" class="alert alert-success mt-3">{{ successMessage }}</div>
                        <div v-if="errorMessage" class="alert alert-danger mt-3">{{ errorMessage }}</div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import moment from "moment";
import Swal from "sweetalert2";

export default {
    components: { Head, Layout, PageHeader },
    data() {
        return {
            activeTab: 'services',
            tabs: [
                { key: 'services', label: 'Servizi', icon: 'ri-car-line' },
                { key: 'users', label: 'Utenti', icon: 'ri-user-line' },
                { key: 'vehicles', label: 'Veicoli', icon: 'ri-roadster-line' },
                { key: 'quotes', label: 'Preventivi', icon: 'ri-file-list-3-line' },
                { key: 'contacts', label: 'Contatti', icon: 'ri-contacts-line' },
            ],
            counts: { services: 0, users: 0, vehicles: 0, quotes: 0, contacts: 0 },
            items: [],
            loading: false,
            processing: false,
            successMessage: '',
            errorMessage: '',
        };
    },
    async mounted() {
        await this.loadCounts();
        await this.loadItems();
    },
    methods: {
        async loadCounts() {
            try {
                const { data } = await axios.get('/api/trash/counts');
                this.counts = data;
            } catch (e) {
                console.error('Error loading counts:', e);
            }
        },
        async loadItems() {
            this.loading = true;
            this.successMessage = '';
            this.errorMessage = '';
            try {
                const { data } = await axios.get(`/api/trash/${this.activeTab}`);
                this.items = data.data || [];
            } catch (e) {
                console.error('Error loading trash items:', e);
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        async switchTab(tab) {
            this.activeTab = tab;
            await this.loadItems();
        },
        async restoreItem(id) {
            const { isConfirmed } = await Swal.fire({
                title: 'Ripristinare?',
                text: 'Il record e tutti i dati collegati verranno ripristinati.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Ripristina',
                cancelButtonText: 'Annulla',
            });
            if (!isConfirmed) return;

            this.processing = true;
            try {
                await axios.post(`/api/trash/${this.activeTab}/${id}/restore`);
                this.successMessage = 'Record ripristinato con successo';
                await Promise.all([this.loadItems(), this.loadCounts()]);
            } catch (e) {
                this.errorMessage = e.response?.data?.message || 'Errore nel ripristino';
            } finally {
                this.processing = false;
            }
        },
        async forceDeleteItem(id) {
            const { isConfirmed } = await Swal.fire({
                title: 'Eliminare definitivamente?',
                html: '<strong class="text-danger">Questa operazione non può essere annullata.</strong><br>Il record e tutti i dati collegati verranno eliminati permanentemente dal database.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Elimina per sempre',
                cancelButtonText: 'Annulla',
            });
            if (!isConfirmed) return;

            this.processing = true;
            try {
                await axios.delete(`/api/trash/${this.activeTab}/${id}`);
                this.successMessage = 'Record eliminato definitivamente';
                await Promise.all([this.loadItems(), this.loadCounts()]);
            } catch (e) {
                this.errorMessage = e.response?.data?.message || 'Errore nell\'eliminazione';
            } finally {
                this.processing = false;
            }
        },
        formatDateTime(date) {
            return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-';
        },
        formatDate(date) {
            return date ? moment(date).format('DD/MM/YYYY') : '-';
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '\u20AC 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
    },
};
</script>
