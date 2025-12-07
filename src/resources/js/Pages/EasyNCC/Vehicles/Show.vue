<template>
    <Layout>
        <PageHeader :title="`Dettaglio Veicolo: ${vehicle.license_plate}`" :items="items" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Informazioni Veicolo</h5>
                        <div>
                            <Link
                                v-if="canEdit"
                                :href="`/easyncc/vehicles/${vehicle.id}/edit`"
                                class="btn btn-soft-primary btn-sm me-2"
                            >
                                <i class="ri-edit-line me-1"></i> Modifica
                            </Link>
                            <Link
                                href="/easyncc/vehicles"
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
                                <label class="form-label fw-semibold">Targa</label>
                                <p class="text-muted mb-0">{{ vehicle.license_plate }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Marca</label>
                                <p class="text-muted mb-0">{{ vehicle.brand }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Modello</label>
                                <p class="text-muted mb-0">{{ vehicle.model }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Capacità Passeggeri</label>
                                <p class="text-muted mb-0">{{ vehicle.passenger_capacity }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Data Acquisto</label>
                                <p class="text-muted mb-0">{{ formatDate(vehicle.purchase_date) }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Numero Licenza NCC</label>
                                <p class="text-muted mb-0">{{ vehicle.ncc_license_number || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Città Licenza</label>
                                <p class="text-muted mb-0">{{ vehicle.license_city || '-' }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Sovrapposizioni Consentite</label>
                                <p class="mb-0">
                                    <span :class="vehicle.allow_overlapping ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ vehicle.allow_overlapping ? 'Sì' : 'No' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="vehicle.notes" class="row mb-4 pt-3 border-top">
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Note</h6>
                            </div>
                            <div class="col-12">
                                <p class="text-muted mb-0">{{ vehicle.notes }}</p>
                            </div>
                        </div>

                        <!-- Vehicle Attachments -->
                        <div class="row pt-3 border-top">
                            <div class="col-12">
                                <VehicleAttachmentsReadOnly :vehicle-id="vehicle.id" />
                            </div>
                        </div>

                        <!-- Vehicle Unavailabilities -->
                        <div class="row pt-3 border-top">
                            <div class="col-12">
                                <VehicleUnavailabilitiesReadOnly :vehicle-id="vehicle.id" />
                            </div>
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
import VehicleAttachmentsReadOnly from '@/Components/ProfileFields/VehicleAttachmentsReadOnly.vue';
import VehicleUnavailabilitiesReadOnly from '@/Components/ProfileFields/VehicleUnavailabilitiesReadOnly.vue';
import moment from 'moment';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

const items = computed(() => [
    {
        text: "EasyNCC",
        href: "/easyncc"
    },
    {
        text: "Veicoli",
        href: "/easyncc/vehicles"
    },
    {
        text: props.vehicle.license_plate,
        active: true
    }
]);

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};
</script>
