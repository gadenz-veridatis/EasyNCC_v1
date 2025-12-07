<template>
    <Head title="Dettaglio Servizio" />

    <Layout>
        <PageHeader title="Dettaglio Servizio" pageTitle="Servizi" />

        <BRow>
            <BCol lg="12">
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
                <div v-else-if="service">
                    <!-- Header Card -->
                    <BCard no-body class="mb-3">
                        <BCardHeader class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                Servizio #{{ service.reference_number || service.id }}
                            </h5>
                            <div>
                                <span :class="`badge bg-${getStatusColor(service.status?.name)} me-2`">
                                    {{ service.status?.name || 'N/A' }}
                                </span>
                                <Link :href="route('easyncc.services.edit', service.id)" class="btn btn-sm btn-primary me-2">
                                    <i class="bx bx-edit me-1"></i>
                                    Modifica
                                </Link>
                                <Link :href="route('easyncc.services.index')" class="btn btn-sm btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>
                                    Torna alla lista
                                </Link>
                            </div>
                        </BCardHeader>
                    </BCard>

                    <BRow>
                        <!-- Left Column -->
                        <BCol lg="6">
                            <!-- Client & Parties Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Committente e Parti</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <div class="mb-3">
                                        <label class="text-muted small">Committente</label>
                                        <div class="fw-medium">
                                            {{ service.client ? `${service.client.name} ${service.client.surname}` : 'N/A' }}
                                        </div>
                                        <div v-if="service.client?.email" class="text-muted small">
                                            {{ service.client.email }}
                                        </div>
                                    </div>
                                    <div v-if="service.intermediary" class="mb-3">
                                        <label class="text-muted small">Intermediario</label>
                                        <div class="fw-medium">
                                            {{ `${service.intermediary.name} ${service.intermediary.surname}` }}
                                        </div>
                                        <div v-if="service.intermediary.email" class="text-muted small">
                                            {{ service.intermediary.email }}
                                        </div>
                                    </div>
                                    <div v-if="service.supplier" class="mb-3">
                                        <label class="text-muted small">Fornitore</label>
                                        <div class="fw-medium">
                                            {{ `${service.supplier.name} ${service.supplier.surname}` }}
                                        </div>
                                        <div v-if="service.supplier.email" class="text-muted small">
                                            {{ service.supplier.email }}
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="text-muted small">Numero Passeggeri</label>
                                        <div class="fw-medium">{{ service.passenger_count }}</div>
                                    </div>
                                </BCardBody>
                            </BCard>

                            <!-- Vehicle & Driver Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Veicolo e Autista</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <div class="mb-3">
                                        <label class="text-muted small">Veicolo</label>
                                        <div class="fw-medium">
                                            {{ service.vehicle ? `${service.vehicle.brand} ${service.vehicle.model}` : 'N/A' }}
                                        </div>
                                        <div v-if="service.vehicle?.license_plate" class="text-muted small">
                                            Targa: {{ service.vehicle.license_plate }}
                                        </div>
                                        <div v-if="service.vehicle_not_replaceable" class="mt-1">
                                            <span class="badge bg-warning-subtle text-warning">Veicolo non sostituibile</span>
                                        </div>
                                    </div>
                                    <div v-if="service.drivers && service.drivers.length > 0" class="mb-3">
                                        <label class="text-muted small">Autista/i</label>
                                        <div v-for="driver in service.drivers" :key="driver.id" class="fw-medium">
                                            {{ `${driver.name} ${driver.surname}` }}
                                        </div>
                                        <div v-if="service.driver_not_replaceable" class="mt-1">
                                            <span class="badge bg-warning-subtle text-warning">Autista non sostituibile</span>
                                        </div>
                                    </div>
                                    <div v-if="service.external_driver_name" class="mb-3">
                                        <label class="text-muted small">Autista Esterno</label>
                                        <div class="fw-medium">{{ service.external_driver_name }}</div>
                                        <div v-if="service.external_driver_phone" class="text-muted small">
                                            {{ service.external_driver_phone }}
                                        </div>
                                    </div>
                                    <div v-if="service.dress_code" class="mb-0">
                                        <label class="text-muted small">Dress Code</label>
                                        <div class="fw-medium">{{ service.dress_code.name }}</div>
                                    </div>
                                </BCardBody>
                            </BCard>

                            <!-- Luggage & Baby Seats Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Bagagli e Seggiolini</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <BRow>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Bagagli Grandi</label>
                                            <div class="fw-medium">{{ service.large_luggage || 0 }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Bagagli Medi</label>
                                            <div class="fw-medium">{{ service.medium_luggage || 0 }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Bagagli Piccoli</label>
                                            <div class="fw-medium">{{ service.small_luggage || 0 }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Seggiolino Ovetto</label>
                                            <div class="fw-medium">{{ service.baby_seat_infant || 0 }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Seggiolino Standard</label>
                                            <div class="fw-medium">{{ service.baby_seat_standard || 0 }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-0">
                                            <label class="text-muted small">Seggiolino Booster</label>
                                            <div class="fw-medium">{{ service.baby_seat_booster || 0 }}</div>
                                        </BCol>
                                    </BRow>
                                </BCardBody>
                            </BCard>
                        </BCol>

                        <!-- Right Column -->
                        <BCol lg="6">
                            <!-- Date & Time Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Orari e Luoghi</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <div class="mb-3">
                                        <label class="text-muted small">Uscita Veicolo</label>
                                        <div class="fw-medium">{{ formatDateTime(service.vehicle_departure_datetime) }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small">Pickup</label>
                                        <div class="fw-medium">{{ formatDateTime(service.pickup_datetime) }}</div>
                                        <div class="text-muted small mt-1">{{ service.pickup_address }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small">Dropoff</label>
                                        <div class="fw-medium">{{ formatDateTime(service.dropoff_datetime) }}</div>
                                        <div class="text-muted small mt-1">{{ service.dropoff_address }}</div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="text-muted small">Rientro Veicolo</label>
                                        <div class="fw-medium">{{ formatDateTime(service.vehicle_return_datetime) }}</div>
                                    </div>
                                </BCardBody>
                            </BCard>

                            <!-- Service Details Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Dettagli Servizio</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <div v-if="service.service_type" class="mb-3">
                                        <label class="text-muted small">Tipologia Servizio</label>
                                        <div class="fw-medium">{{ service.service_type }}</div>
                                    </div>
                                    <div v-if="service.vehicle_type" class="mb-3">
                                        <label class="text-muted small">Tipologia Veicolo</label>
                                        <div class="fw-medium">{{ service.vehicle_type }}</div>
                                    </div>
                                    <div v-if="service.notes" class="mb-0">
                                        <label class="text-muted small">Note</label>
                                        <div class="fw-medium">{{ service.notes }}</div>
                                    </div>
                                </BCardBody>
                            </BCard>

                            <!-- Financial Card -->
                            <BCard no-body class="mb-3">
                                <BCardHeader>
                                    <h6 class="card-title mb-0">Dati Economici</h6>
                                </BCardHeader>
                                <BCardBody>
                                    <BRow>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Prezzo Servizio</label>
                                            <div class="fw-medium">{{ formatCurrency(service.service_price) }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Compenso Autista</label>
                                            <div class="fw-medium">{{ formatCurrency(service.driver_compensation) }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-2">
                                            <label class="text-muted small">Commissione Intermediario</label>
                                            <div class="fw-medium">{{ formatCurrency(service.intermediary_commission) }}</div>
                                        </BCol>
                                        <BCol cols="6" class="mb-0">
                                            <label class="text-muted small">Spese Vive</label>
                                            <div class="fw-medium">{{ formatCurrency(service.expenses) }}</div>
                                        </BCol>
                                    </BRow>
                                </BCardBody>
                            </BCard>
                        </BCol>
                    </BRow>

                    <!-- Passengers Card -->
                    <BCard v-if="service.passengers && service.passengers.length > 0" no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">Passeggeri</h6>
                        </BCardHeader>
                        <BCardBody>
                            <div class="table-responsive">
                                <table class="table table-sm table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Telefono</th>
                                            <th>Nazionalità</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="passenger in service.passengers" :key="passenger.id">
                                            <td>{{ passenger.name }}</td>
                                            <td>{{ passenger.email || '-' }}</td>
                                            <td>{{ passenger.phone || '-' }}</td>
                                            <td>{{ passenger.nationality || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Stops Card -->
                    <BCard v-if="service.stops && service.stops.length > 0" no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">Fermate Intermedie</h6>
                        </BCardHeader>
                        <BCardBody>
                            <div class="table-responsive">
                                <table class="table table-sm table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome</th>
                                            <th>Indirizzo</th>
                                            <th>Ora Inizio</th>
                                            <th>Ora Fine</th>
                                            <th>Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="stop in service.stops" :key="stop.id">
                                            <td>{{ stop.name }}</td>
                                            <td>{{ stop.address }}</td>
                                            <td>{{ formatDateTime(stop.start_time) }}</td>
                                            <td>{{ formatDateTime(stop.end_time) }}</td>
                                            <td>{{ formatCurrency(stop.total_cost) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Payments Card -->
                    <BCard v-if="service.payments && service.payments.length > 0" no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">Pagamenti</h6>
                        </BCardHeader>
                        <BCardBody>
                            <div class="table-responsive">
                                <table class="table table-sm table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tranche</th>
                                            <th>Importo</th>
                                            <th>Tipologia</th>
                                            <th>Data Fattura</th>
                                            <th>Data Pagamento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="payment in service.payments" :key="payment.id">
                                            <td>{{ payment.tranche }}</td>
                                            <td>{{ formatCurrency(payment.amount) }}</td>
                                            <td>{{ payment.payment_type }}</td>
                                            <td>{{ formatDate(payment.invoice_date) }}</td>
                                            <td>{{ formatDate(payment.payment_date) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Costs Card -->
                    <BCard v-if="service.costs && service.costs.length > 0" no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">Costi</h6>
                        </BCardHeader>
                        <BCardBody>
                            <div class="table-responsive">
                                <table class="table table-sm table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Numero Documento</th>
                                            <th>Tipo</th>
                                            <th>Importo</th>
                                            <th>Data Pagamento</th>
                                            <th>Scadenza</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="cost in service.costs" :key="cost.id">
                                            <td>{{ cost.document_number }}</td>
                                            <td>{{ cost.document_type }}</td>
                                            <td>{{ formatCurrency(cost.amount) }}</td>
                                            <td>{{ formatDate(cost.payment_date) }}</td>
                                            <td>{{ formatDate(cost.due_date) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </BCardBody>
                    </BCard>
                </div>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    serviceId: {
        type: [String, Number],
        required: true
    }
});

const service = ref(null);
const loading = ref(false);
const error = ref('');

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

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return moment(datetime).format('DD/MM/YYYY HH:mm');
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '-';
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

const getStatusColor = (statusName) => {
    const colors = {
        'preventivo': 'info',
        'confermato': 'success',
        'in corso': 'primary',
        'completato': 'secondary',
        'cancellato': 'danger',
        'no-show': 'warning'
    };
    return colors[statusName?.toLowerCase()] || 'secondary';
};

onMounted(() => {
    loadService();
});
</script>
