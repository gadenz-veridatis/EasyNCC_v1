<template>
  <Layout>
    <PageHeader title="Assenze Driver" :items="breadcrumbs" />
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
                <h5 class="card-title mb-0">Periodi di Assenza Driver</h5>
              </BCol>
              <BCol cols="auto">
                <BButton variant="primary" @click="openModal()">
                  <i class="ri-add-line align-bottom me-1"></i> Nuova Assenza
                </BButton>
              </BCol>
            </BRow>
          </BCardHeader>
          <BCardBody>
            <!-- Filters -->
            <BRow class="mb-3">
              <BCol md="4">
                <label class="form-label">Driver</label>
                <select v-model="filterDriverId" class="form-select form-select-sm" @change="loadItems">
                  <option value="">Tutti i driver</option>
                  <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                    {{ driver.display_name || driver.surname + ' ' + driver.name }}
                  </option>
                </select>
              </BCol>
            </BRow>

            <!-- Table -->
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else-if="items.length === 0" class="text-center py-5 text-muted">
              Nessun periodo di assenza registrato.
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="sortable-th" @click="toggleSort('driver')">
                      Driver
                      <i v-if="sortField === 'driver'" :class="sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
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
                      <span
                        class="badge"
                        :style="`background-color: ${item.user?.driver_profile?.color || '#6c757d'}; color: #fff;`"
                      >
                        {{ item.user?.display_name || item.user?.surname + ' ' + item.user?.name }}
                      </span>
                    </td>
                    <td>{{ item.leave_type?.name || '-' }}</td>
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
    <BModal v-model="showModal" :title="editingId ? 'Modifica Assenza' : 'Nuova Assenza'" hide-footer>
      <form @submit.prevent="saveItem">
        <div class="mb-3">
          <label class="form-label">Driver <span class="text-danger">*</span></label>
          <select v-model="form.user_id" class="form-select" required :disabled="!!editingId">
            <option value="">Seleziona driver</option>
            <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
              {{ driver.display_name || driver.surname + ' ' + driver.name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Tipologia <span class="text-danger">*</span></label>
          <select v-model="form.leave_type_id" class="form-select" required>
            <option value="">Seleziona tipologia</option>
            <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
              {{ type.name }}
            </option>
          </select>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" v-model="form.all_day" id="allDayToggle" />
            <label class="form-check-label" for="allDayToggle">Tutto il giorno</label>
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

const breadcrumbs = [
  { text: 'EasyNCC', href: '/' },
  { text: 'Impostazioni', href: '#' },
  { text: 'Assenze Driver', active: true },
];

const items = ref([]);
const drivers = ref([]);
const leaveTypes = ref([]);
const companies = ref([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const filterDriverId = ref('');
const selectedCompanyId = ref('');
const currentUser = ref(null);
const isSuperAdmin = ref(false);

const form = ref({
  user_id: '',
  leave_type_id: '',
  start_date: '',
  end_date: '',
  all_day: true,
  notes: '',
});

// Split date/time for form inputs
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
      case 'driver':
        valA = (a.user?.display_name || a.user?.surname || '').toLowerCase();
        valB = (b.user?.display_name || b.user?.surname || '').toLowerCase();
        break;
      case 'type':
        valA = (a.leave_type?.name || '').toLowerCase();
        valB = (b.leave_type?.name || '').toLowerCase();
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

// Compose datetime from split fields before saving
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
  await Promise.all([loadItems(), loadDrivers(), loadLeaveTypes()]);
};

const loadItems = async () => {
  loading.value = true;
  try {
    const params = {};
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    if (filterDriverId.value) params.user_id = filterDriverId.value;
    const response = await axios.get('/api/driver-unavailabilities', { params });
    items.value = response.data || [];
  } catch (err) {
    console.error('Error loading unavailabilities:', err);
  } finally {
    loading.value = false;
  }
};

const loadDrivers = async () => {
  try {
    const params = { role: 'driver', light: true, per_page: 200 };
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    const response = await axios.get('/api/users', { params });
    drivers.value = response.data.data || [];
  } catch (err) {
    console.error('Error loading drivers:', err);
  }
};

const loadLeaveTypes = async () => {
  try {
    const params = {};
    if (selectedCompanyId.value) params.company_id = selectedCompanyId.value;
    const response = await axios.get('/api/dictionaries/leave-types', { params });
    leaveTypes.value = response.data.data || [];
  } catch (err) {
    console.error('Error loading leave types:', err);
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
      user_id: item.user_id,
      leave_type_id: item.leave_type_id,
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
    form.value = { user_id: '', leave_type_id: '', start_date: '', end_date: '', all_day: true, notes: '' };
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
      const userId = form.value.user_id;
      await axios.put(`/api/users/${userId}/unavailabilities/${editingId.value}`, form.value);
    } else {
      await axios.post('/api/driver-unavailabilities', form.value);
    }
    showModal.value = false;
    await loadItems();
  } catch (err) {
    console.error('Error saving:', err);
    alert(err.response?.data?.message || 'Errore durante il salvataggio');
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (item) => {
  if (!confirm(`Eliminare il periodo di assenza di ${item.user?.display_name || 'questo driver'}?`)) return;
  try {
    await axios.delete(`/api/users/${item.user_id}/unavailabilities/${item.id}`);
    await loadItems();
  } catch (err) {
    console.error('Error deleting:', err);
    alert('Errore durante l\'eliminazione');
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
  const driverIdParam = urlParams.get('driver_id');
  if (driverIdParam) {
    filterDriverId.value = driverIdParam;
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
