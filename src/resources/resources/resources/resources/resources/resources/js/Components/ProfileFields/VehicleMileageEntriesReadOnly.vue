<template>
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-subtitle mb-0 text-muted">Registro Chilometraggio</h6>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        <div v-else-if="entries.length === 0" class="text-muted text-center py-3">
            <small>Nessuna lettura registrata.</small>
        </div>

        <template v-else>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Data Lettura</th>
                            <th class="text-end">Km</th>
                            <th>Tipo</th>
                            <th>Utente</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries" :key="entry.id">
                            <td>{{ formatDate(entry.entry_date) }}</td>
                            <td class="text-end fw-semibold">{{ formatNumber(entry.mileage) }}</td>
                            <td>
                                <span :class="getTypeClass(entry.update_type)">
                                    {{ getTypeLabel(entry.update_type) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ entry.creator ? `${entry.creator.name} ${entry.creator.surname}` : '-' }}</small>
                            </td>
                            <td>
                                <small>{{ entry.notes || '-' }}</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    {{ pagination.from }}-{{ pagination.to }} di {{ pagination.total }} registrazioni
                </small>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                        <a class="page-link" href="#" @click.prevent="loadEntries(pagination.current_page - 1)">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    </li>
                    <li
                        v-for="page in visiblePages"
                        :key="page"
                        class="page-item"
                        :class="{ active: page === pagination.current_page }"
                    >
                        <a class="page-link" href="#" @click.prevent="loadEntries(page)">{{ page }}</a>
                    </li>
                    <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                        <a class="page-link" href="#" @click.prevent="loadEntries(pagination.current_page + 1)">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
    vehicleId: {
        type: Number,
        required: true
    }
});

const entries = ref([]);
const loading = ref(false);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
});

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const loadEntries = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${props.vehicleId}/mileage-entries`, {
            params: { page, per_page: 15 }
        });
        entries.value = response.data.data || [];
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            from: response.data.from || 0,
            to: response.data.to || 0,
            total: response.data.total || 0,
        };
    } catch (error) {
        console.error('Error loading mileage entries:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return moment(date).format('DD/MM/YYYY');
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '-';
    return num.toLocaleString('it-IT');
};

const getTypeLabel = (type) => {
    const labels = {
        'manual': 'Manuale',
        'correction': 'Rettifica',
        'service': 'Servizio',
    };
    return labels[type] || type;
};

const getTypeClass = (type) => {
    const classes = {
        'manual': 'badge bg-info',
        'correction': 'badge bg-warning',
        'service': 'badge bg-success',
    };
    return classes[type] || 'badge bg-secondary';
};

onMounted(() => {
    loadEntries();
});
</script>
