<template>
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Periodi di Non Disponibilit&agrave;</h6>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <div v-else-if="unavailabilities.length === 0" class="text-muted text-center py-3">
            <small>Nessun periodo di non disponibilit&agrave;.</small>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="unavailability in unavailabilities" :key="unavailability.id">
                        <td>
                            <span class="badge bg-warning">
                                {{ unavailability.unavailability_type?.name || unavailability.type || '-' }}
                            </span>
                        </td>
                        <td>{{ formatDate(unavailability.start_date) }}</td>
                        <td>{{ formatDate(unavailability.end_date) }}</td>
                        <td>
                            <small>{{ unavailability.notes || '-' }}</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicleId: {
        type: Number,
        required: true
    }
});

const unavailabilities = ref([]);
const loading = ref(false);

const loadUnavailabilities = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${props.vehicleId}/unavailabilities`);
        unavailabilities.value = response.data;
    } catch (error) {
        console.error('Error loading unavailabilities:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

onMounted(() => {
    loadUnavailabilities();
});
</script>
