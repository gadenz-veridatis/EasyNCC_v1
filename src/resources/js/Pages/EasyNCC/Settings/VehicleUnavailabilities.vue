<template>
  <Layout>
    <PageHeader title="Non Disponibilità Veicoli" :items="breadcrumbs" />
    <BRow>
      <BCol lg="12">
        <!-- Company selector for super-admin -->
        <div v-if="isSuperAdmin" class="mb-3">
          <div class="alert alert-info mb-0">
            <label class="form-label fw-bold">Seleziona Azienda</label>
            <select v-model="selectedCompanyId" class="form-select" @change="loadAll">
              <option value="">Tutte le aziende</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>
        </div>

        <BCard no-body>
          <BCardHeader>
            <BRow class="align-items-center">
              <BCol>
                <h5 class="card-title mb-0">Periodi di Non Disponibilità Veicoli</h5>
              </BCol>
              <BCol cols="auto">
                <BButton variant="primary" @click="openModal()">
                  <i class="ri-add-line align-bottom me-1"></i> Nuova Non Disponibilità
                </BButton>
              </BCol>
            </BRow>
          </BCardHeader>
          <BCardBody>
            <!-- Filters -->
            <BRow class="mb-3">
              <BCol md="4">
                <label class="form-label">Veicolo</label>
                <select v-model="filterVehicleId" class="form-select form-select-sm" @change="loadItems">
                  <option value="">Tutti i veicoli</option>
                  <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                    {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                  </option>
                </select>
              </BCol>
            </BRow>

            <!-- Table -->
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else-if="items.length === 0" class="text-center py-5 text-muted">
              Nessun periodo di non disponibilità registrato.
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="sortable-th" @click="toggleSort('vehicle')">
                      Veicolo
                      <i v-if="sortField === 'vehicle'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                    </th>
                    <th class="sortable-th" @click="toggleSort('type')">
                      Tipologia
                      <i v-if="sortField === 'type'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                    </th>
                    <th class="sortable-th" @click="toggleSort('start_date')">
                      Data Inizio
                      <i v-if="sortField === 'start_date'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                    </th>
                    <th class="sortable-th" @click="toggleSort('end_date')">
                      Data Fine
                      <i v-if="sortField === 'end_date'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                    </th>
                    <th>Note</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in sortedItems" :key="item.id">
                    <td>
                      <div class="fw-bold">{{ item.vehicle?.license_plate || '-' }}</div>
                      <div class="small text-muted">{{ item.vehicle?.brand }} {{ item.vehicle?.model }}</div>
                    </td>
                    <td>{{ item.unavailability_type?.name || '-' }}</td>
                    <td>{{ formatDateTime(item.start_date, item.all_day) }}</td>
                    <td>{{ formatDateTime(item.end_date, item.all_day) }}</td>
                    <td class="small text-muted" style="max-width: 250px;">{{ item.notes || '-' }}</td>
                    <td>
                      <div class="hstack gap-2">
                        <a href="javascript:void(0)" class="link-primary" @click="openModal(item)" title="Modifica">
                          <i class="ri-pencil-line"></i>
                        </a>
                        <a href="javascript:void(0)" class="link-danger" @click="deleteItem(item)" title="Elimina">
                          <i class="ri-delete-bin-line"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Modal -->
    <BModal v-model="showModal" :title="editingId ? 'Modifica Non Disponibilità' : 'Nuova Non Disponibilità'" hide-footer>
      <form @submit.prevent="saveItem">
        <div class="mb-3">
          <label class="form-label">Veicolo <span class="text-danger">*</span></label>
          <select v-model="form.vehicle_id" class="form-select" required :disabled="!!editingId">
            <option value="">Seleziona veicolo</option>
            <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
              {{ vehicle.license_plate }} - {{ vehicle.brand }} {{ vehicle.model }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Tipologia <span class="text-danger">*</span></label>
          <select v-model="form.vehicle_unavailability_type_id" class="form-select" required>
            <option value="">Seleziona tipologia</option>
            <option v-for="type in unavailabilityTypes" :key="type.id" :value="type.id">
              {{ type.name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" v-model="form.all_day" id="allDayToggleV" />
            <label class="form-check-label" for="allDayToggleV">Tutto il giorno</label>
          </div>
        </div>
        <BRow>
          <BCol :md="form.all_day ? 6 : 3">
            <div class="mb-3">
              <label class="form-label">Data Inizio <span class="text-danger">*</span></label>
              <input type="date" v-model="formStartDate" class="form-control" required />
            </div>
          </BCol>
          <BCol md="3" v-if="!form.all_day">
            <div class="mb-3">
              <label class="form-label">Ora Inizio <span class="text-danger">*</span></label>
              <input type="time" v-model="formStartTime" class="form-control" required />
            </div>
          </BCol>
          <BCol :md="form.all_day ? 6 : 3">
            <div class="mb-3">
              <label class="form-label">Data Fine <span class="text-danger">*</span></label>
              <input type="date" v-model="formEndDate" class="form-control" required />
            </div>
          </BCol>
          <BCol md="3" v-if="!form.all_day">
            <div class="mb-3">
              <label class="form-label">Ora Fine <span class="text-danger">*</span></label>
              <input type="time" v-model="formEndTime" class="form-control" required />
            </div>
          </BCol>
        </BRow>
        <div class="mb-3">
          <label class="form-label">Note</label>
          <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
        </div>
        <div class="text-end">
          <BButton variant="light" class="me-2" @click="showModal = false">Annulla</BButton>
          <BButton variant="primary" type="submit" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            Salva
          </BButton>
        </div>
      </form>
    </BModal>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment';
import { useNotify } from '@/composables/useNotify.js';

const notify = useNotify();

const breadcrumbs = [
  { text: 'EasyNCC', href: '/' },
  { text: 'Impostazioni', href: '#' },
  { text: 'Non Disponibilità Veicoli', active: true },
];

const items = ref([]);
const vehicles = ref([]);
const unavailabilityTypes = ref([]);
const companies = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const filterVehicleId = ref('');
const selectedCompanyId = ref('');
const currentUser = ref(null);
const isSuperAdmin = ref(false);

const form = ref({
  vehicle_id: '',
  vehicle_unavailability_type_id: '',
  start_date: '',
  end_date: '',
  all_day: true,
  notes: '',
});

const formStartDate = ref('');
const formStartTime = ref('00:00');
const formEndDate = ref('');
const formEndTime = ref('23:59');

// Sorting
const sortField = ref('start_date');
const sortDirection = ref('asc');

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
};

const sortedItems = computed(() => {
  return [...items.value].sort((a, b) => {
    let valA, valB;
    switch (sortField.value) {
      case 'vehicle':
        valA = (a.vehicle?.license_plate || '').toLowerCase();
        valB = (b.vehicle?.license_plate || '').toLowerCase();
        break;
      case 'type':
        valA = (a.unavailability_type?.name || '').toLowerCase();
        valB = (b.unavailability_type?.name || '').toLowerCase();
        break;
      case 'start_date':
        valA = a.start_date || '';
        valB = b.start_date || '';
        break;
      case 'end_date':
        valA = a.end_date || '';
        valB = b.end_date || '';
        break;
      default:
        return 0;
    }
    if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
    if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const formatDate = (d) => d ? moment.utc(d).format('DD/MM/YYYY') : '-';
const formatDateTime = (d, allDay) => {
  if (!d) return '-';
  return allDay ? moment.utc(d).format('DD/MM/YYYY') : moment.utc(d).format('DD/MM/YYYY HH:mm');
};

const composeDatetimes = () => {
  if (form.value.all_day) {
    form.value.start_date = formStartDate.value + ' 00:00:00';
    form.value.end_date = formEndDate.value + ' 23:59:59';
  } else {
    form.value.start_date = formStartDate.value + ' ' + (formStartTime.value || '00:00') + ':00';
    form.value.end_date = formEndDate.value + ' ' + (formEndTime.value || '23:59') + ':00';
  }
};

const loadAll = async () => {
  await Promise.all([loadItems(), loadVehicles(), loadUnavailabilityTypes()]);
};

const loadItems = async () => {
  loading.value = true;
  try {
    const params = {};
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    if (filterVehicleId.value) params.vehicle_id = filterVehicleId.value;
    const response = await axios.get('/api/vehicle-unavailabilities', { params });
    items.value = response.data || [];
  } catch (err) {
    console.error('Error loading unavailabilities:', err);
  } finally {
    loading.value = false;
  }
};

const loadVehicles = async () => {
  try {
    const params = { per_page: 200 };
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    const response = await axios.get('/api/vehicles', { params });
    vehicles.value = response.data.data || [];
  } catch (err) {
    console.error('Error loading vehicles:', err);
  }
};

const loadUnavailabilityTypes = async () => {
  try {
    const params = {};
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    const response = await axios.get('/api/dictionaries/vehicle-unavailability-types', { params });
    unavailabilityTypes.value = response.data.data || [];
  } catch (err) {
    console.error('Error loading unavailability types:', err);
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

const openModal = (item = null) => {
  if (item) {
    editingId.value = item.id;
    const allDay = item.all_day !== false;
    form.value = {
      vehicle_id: item.vehicle_id,
      vehicle_unavailability_type_id: item.vehicle_unavailability_type_id,
      start_date: '',
      end_date: '',
      all_day: allDay,
      notes: item.notes || '',
    };
    formStartDate.value = moment.utc(item.start_date).format('YYYY-MM-DD');
    formEndDate.value = moment.utc(item.end_date).format('YYYY-MM-DD');
    formStartTime.value = allDay ? '00:00' : moment.utc(item.start_date).format('HH:mm');
    formEndTime.value = allDay ? '23:59' : moment.utc(item.end_date).format('HH:mm');
  } else {
    editingId.value = null;
    form.value = { vehicle_id: '', vehicle_unavailability_type_id: '', start_date: '', end_date: '', all_day: true, notes: '' };
    formStartDate.value = '';
    formEndDate.value = '';
    formStartTime.value = '00:00';
    formEndTime.value = '23:59';
  }
  showModal.value = true;
};

const saveItem = async () => {
  saving.value = true;
  composeDatetimes();
  try {
    if (editingId.value) {
      const vehicleId = form.value.vehicle_id;
      await axios.put(`/api/vehicles/${vehicleId}/unavailabilities/${editingId.value}`, form.value);
    } else {
      await axios.post('/api/vehicle-unavailabilities', form.value);
    }
    showModal.value = false;
    await loadItems();
  } catch (err) {
    console.error('Error saving:', err);
    notify.error(err.response?.data?.message || 'Errore durante il salvataggio');
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (item) => {
  const confirmed = await notify.confirm('Conferma eliminazione', `Eliminare il periodo di non disponibilità per ${item.vehicle?.license_plate || 'questo veicolo'}?`);
  if (!confirmed) return;
  try {
    await axios.delete(`/api/vehicles/${item.vehicle_id}/unavailabilities/${item.id}`);
    await loadItems();
  } catch (err) {
    console.error('Error deleting:', err);
    notify.error('Errore durante l\'eliminazione');
  }
};

onMounted(async () => {
  try {
    const res = await axios.get('/api/user');
    currentUser.value = res.data;
    isSuperAdmin.value = res.data.role === 'super-admin';
  } catch (err) {
    console.error('Error loading user:', err);
  }
  // Pre-filter from query parameter (e.g. from calendar context menu)
  const urlParams = new URLSearchParams(window.location.search);
  const vehicleIdParam = urlParams.get('vehicle_id');
  if (vehicleIdParam) {
    filterVehicleId.value = vehicleIdParam;
  }
  await loadCompanies();
  await loadAll();
});
</script>

<style scoped>
.sortable-th {
  cursor: pointer;
  user-select: none;
}
.sortable-th:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
