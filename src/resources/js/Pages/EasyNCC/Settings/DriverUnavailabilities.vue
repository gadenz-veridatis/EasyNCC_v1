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
                    <th>Driver</th>
                    <th>Tipologia</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Note</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in items" :key="item.id">
                    <td>
                      <span
                        class="badge"
                        :style="`background-color: ${item.user?.driver_profile?.color || '#6c757d'}; color: #fff;`"
                      >
                        {{ item.user?.display_name || item.user?.surname + ' ' + item.user?.name }}
                      </span>
                    </td>
                    <td>{{ item.leave_type?.name || '-' }}</td>
                    <td>{{ formatDate(item.start_date) }}</td>
                    <td>{{ formatDate(item.end_date) }}</td>
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
        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Data Inizio <span class="text-danger">*</span></label>
              <input type="date" v-model="form.start_date" class="form-control" required />
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Data Fine <span class="text-danger">*</span></label>
              <input type="date" v-model="form.end_date" class="form-control" required />
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
import { ref, onMounted } from 'vue';
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
  notes: '',
});

const formatDate = (d) => d ? moment(d).format('DD/MM/YYYY') : '-';

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
    form.value = {
      user_id: item.user_id,
      leave_type_id: item.leave_type_id,
      start_date: moment(item.start_date).format('YYYY-MM-DD'),
      end_date: moment(item.end_date).format('YYYY-MM-DD'),
      notes: item.notes || '',
    };
  } else {
    editingId.value = null;
    form.value = { user_id: '', leave_type_id: '', start_date: '', end_date: '', notes: '' };
  }
  showModal.value = true;
};

const saveItem = async () => {
  saving.value = true;
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
  await loadCompanies();
  await loadAll();
});
</script>
