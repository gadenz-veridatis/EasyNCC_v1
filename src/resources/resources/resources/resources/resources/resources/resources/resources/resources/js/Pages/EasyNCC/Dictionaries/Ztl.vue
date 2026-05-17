<template>
  <Layout>
    <PageHeader :title="pageTitle" :items="items" />
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ pageTitle }}</h5>
            <BButton variant="primary" size="sm" @click="openCreateModal">
              <i class="bx bx-plus me-1"></i> Aggiungi ZTL
            </BButton>
          </BCardHeader>
          <BCardBody>
            <BRow class="mb-3">
              <BCol :lg="isSuperAdmin ? 4 : 6">
                <div class="search-box">
                  <input
                    type="text"
                    class="form-control form-control-sm search"
                    placeholder="Cerca comune o targa veicolo..."
                    v-model="searchQuery"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>
              </BCol>
              <BCol lg="4" v-if="isSuperAdmin">
                <select class="form-select form-select-sm" v-model="selectedCompanyId" @change="loadItems">
                  <option value="">Tutte le aziende</option>
                  <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.name }}
                  </option>
                </select>
              </BCol>
            </BRow>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Caricamento...</span>
              </div>
            </div>

            <!-- Table -->
            <div v-else-if="sortedItems.length > 0" class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="border: 1px solid #dee2e6;">
                <thead class="table-dark">
                  <tr>
                    <th scope="col" class="sortable" @click="sort('city')">
                      Comune
                      <i v-if="sortByField === 'city'" :class="`bx bx-${sortOrderDir === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                    </th>
                    <th scope="col" class="sortable" @click="sort('periodicity')">
                      Periodicità
                      <i v-if="sortByField === 'periodicity'" :class="`bx bx-${sortOrderDir === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                    </th>
                    <th scope="col" class="sortable" @click="sort('expiration_date')">
                      Scadenza
                      <i v-if="sortByField === 'expiration_date'" :class="`bx bx-${sortOrderDir === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                    </th>
                    <th scope="col">Veicoli</th>
                    <th scope="col" class="sortable" @click="sort('is_active')">
                      Stato
                      <i v-if="sortByField === 'is_active'" :class="`bx bx-${sortOrderDir === 'asc' ? 'up' : 'down'}-arrow-alt`"></i>
                    </th>
                    <th scope="col" v-if="isSuperAdmin">Azienda</th>
                    <th scope="col">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in sortedItems" :key="item.id">
                    <td class="fw-bold">{{ item.city }}</td>
                    <td>{{ item.periodicity || '-' }}</td>
                    <td>{{ formatDate(item.expiration_date) }}</td>
                    <td>
                      <span v-if="item.vehicles && item.vehicles.length > 0">
                        <span
                          v-for="v in item.vehicles"
                          :key="v.id"
                          class="targa-auto me-1 mb-1"
                          style="display: inline-block;"
                        >
                          <span class="codice-targa">{{ v.license_plate }}</span>
                        </span>
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <BBadge :variant="item.is_active ? 'success' : 'danger'">
                        {{ item.is_active ? 'Attivo' : 'Inattivo' }}
                      </BBadge>
                    </td>
                    <td v-if="isSuperAdmin">
                      <span class="badge bg-info-subtle text-info">
                        {{ item.company?.name || '-' }}
                      </span>
                    </td>
                    <td>
                      <div class="hstack gap-2 flex-wrap">
                        <button
                          class="btn btn-sm btn-soft-primary"
                          @click="editItem(item)"
                        >
                          <i class="bx bx-edit"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-soft-danger"
                          @click="deleteItem(item)"
                        >
                          <i class="bx bx-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- No Data -->
            <div v-else class="text-center text-muted py-5">
              <p>Nessun elemento trovato</p>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Modal Form -->
    <BModal v-model="showModal" :title="formTitle" hide-footer size="lg">
      <form @submit.prevent="saveItem">
        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Comune <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                v-model="form.city"
                required
              />
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Periodicità</label>
              <input
                type="text"
                class="form-control"
                v-model="form.periodicity"
                placeholder="es. Annuale, Semestrale..."
              />
            </div>
          </BCol>
        </BRow>

        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Scadenza</label>
              <input
                type="date"
                class="form-control"
                v-model="form.expiration_date"
              />
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <div class="form-check form-switch mt-4">
                <input
                  class="form-check-input"
                  type="checkbox"
                  v-model="form.is_active"
                />
                <label class="form-check-label">Attivo</label>
              </div>
            </div>
          </BCol>
        </BRow>

        <div class="mb-3">
          <label class="form-label">Note</label>
          <textarea
            class="form-control"
            v-model="form.notes"
            rows="3"
          ></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Veicoli Abilitati</label>
          <div v-if="vehiclesLoading" class="text-center py-2">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Caricamento...</span>
            </div>
          </div>
          <div v-else-if="vehicles.length === 0" class="text-muted">
            Nessun veicolo disponibile
          </div>
          <div v-else class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
            <div
              v-for="vehicle in vehicles"
              :key="vehicle.id"
              class="form-check mb-2"
            >
              <input
                class="form-check-input"
                type="checkbox"
                :id="'vehicle-' + vehicle.id"
                :value="vehicle.id"
                v-model="form.vehicle_ids"
              />
              <label class="form-check-label" :for="'vehicle-' + vehicle.id">
                <span class="targa-auto targa-sm me-2">
                  <span class="codice-targa">{{ vehicle.license_plate }}</span>
                </span>
                {{ vehicle.brand }} {{ vehicle.model }}
              </label>
            </div>
          </div>
        </div>

        <div class="text-end">
          <BButton variant="light" @click="showModal = false" class="me-2">
            Annulla
          </BButton>
          <BButton variant="primary" type="submit">
            Salva
          </BButton>
        </div>
      </form>
    </BModal>
  </Layout>
</template>

<script>
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";

export default {
  components: {
    Layout,
    PageHeader,
  },
  data() {
    return {
      pageTitle: "ZTL",
      items: [
        { text: "EasyNCC", href: "/" },
        { text: "ZTL", active: true },
      ],
      searchQuery: "",
      showModal: false,
      loading: false,
      vehiclesLoading: false,
      ztlItems: [],
      vehicles: [],
      form: {
        city: "",
        periodicity: "",
        expiration_date: "",
        notes: "",
        vehicle_ids: [],
        is_active: true,
      },
      editingId: null,
      companies: [],
      selectedCompanyId: "",
      currentUser: null,
      sortByField: "city",
      sortOrderDir: "asc",
    };
  },
  computed: {
    formTitle() {
      return this.editingId ? "Modifica ZTL" : "Nuova ZTL";
    },
    filteredItems() {
      if (!this.searchQuery) return this.ztlItems;

      const query = this.searchQuery.toLowerCase();
      return this.ztlItems.filter((item) => {
        // Search by city name
        if (item.city?.toLowerCase().includes(query)) return true;
        // Search by vehicle license plate
        if (item.vehicles && item.vehicles.some(v =>
          v.license_plate?.toLowerCase().includes(query)
        )) return true;
        return false;
      });
    },
    sortedItems() {
      if (!this.filteredItems.length) return [];

      const sorted = [...this.filteredItems].sort((a, b) => {
        let aVal = a[this.sortByField];
        let bVal = b[this.sortByField];

        // Handle null/undefined values
        if (aVal === null || aVal === undefined) return 1;
        if (bVal === null || bVal === undefined) return -1;

        // Boolean comparison
        if (typeof aVal === 'boolean') {
          aVal = aVal ? 1 : 0;
          bVal = bVal ? 1 : 0;
        }

        // String comparison
        if (typeof aVal === 'string') {
          aVal = aVal.toLowerCase();
          bVal = (bVal || '').toLowerCase();
        }

        if (this.sortOrderDir === 'asc') {
          return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
          return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
      });

      return sorted;
    },
    isSuperAdmin() {
      return this.currentUser?.role === 'super-admin';
    },
  },
  async mounted() {
    await this.loadCurrentUser();
    await Promise.all([
      this.loadCompanies(),
      this.loadItems(),
      this.loadVehicles(),
    ]);
  },
  methods: {
    sort(column) {
      if (this.sortByField === column) {
        this.sortOrderDir = this.sortOrderDir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortByField = column;
        this.sortOrderDir = 'asc';
      }
    },
    async loadCurrentUser() {
      try {
        const response = await axios.get('/api/user');
        this.currentUser = response.data;
      } catch (error) {
        console.error("Error loading current user:", error);
      }
    },
    async loadCompanies() {
      if (!this.isSuperAdmin) return;

      try {
        const response = await axios.get('/api/companies');
        this.companies = response.data.data || [];
      } catch (error) {
        console.error("Error loading companies:", error);
      }
    },
    async loadItems() {
      this.loading = true;
      try {
        const params = {};
        if (this.selectedCompanyId) {
          params.company_id = this.selectedCompanyId;
        }

        const response = await axios.get("/api/dictionaries/ztl", { params });
        this.ztlItems = response.data.data || [];
      } catch (error) {
        console.error("Error loading ZTL items:", error);
        this.ztlItems = [];
      } finally {
        this.loading = false;
      }
    },
    async loadVehicles() {
      this.vehiclesLoading = true;
      try {
        const response = await axios.get('/api/vehicles', { params: { per_page: 200 } });
        this.vehicles = response.data.data || [];
      } catch (error) {
        console.error("Error loading vehicles:", error);
        this.vehicles = [];
      } finally {
        this.vehiclesLoading = false;
      }
    },
    openCreateModal() {
      this.editingId = null;
      this.form = {
        city: "",
        periodicity: "",
        expiration_date: "",
        notes: "",
        vehicle_ids: [],
        is_active: true,
      };
      this.showModal = true;
    },
    editItem(item) {
      this.editingId = item.id;
      this.form = {
        city: item.city || "",
        periodicity: item.periodicity || "",
        expiration_date: item.expiration_date ? item.expiration_date.substring(0, 10) : "",
        notes: item.notes || "",
        vehicle_ids: item.vehicles ? item.vehicles.map(v => v.id) : [],
        is_active: item.is_active,
      };
      this.showModal = true;
    },
    async deleteItem(item) {
      if (!confirm(`Sei sicuro di voler eliminare la ZTL di "${item.city}"?`)) {
        return;
      }

      try {
        await axios.delete(`/api/dictionaries/ztl/${item.id}`);
        await this.loadItems();
      } catch (error) {
        console.error("Error deleting item:", error);
        alert("Errore durante l'eliminazione");
      }
    },
    async saveItem() {
      try {
        const payload = { ...this.form };

        if (this.editingId) {
          await axios.put(
            `/api/dictionaries/ztl/${this.editingId}`,
            payload
          );
        } else {
          await axios.post("/api/dictionaries/ztl", payload);
        }

        this.showModal = false;
        this.resetForm();
        await this.loadItems();
      } catch (error) {
        console.error("Error saving item:", error);
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          alert("Errori di validazione:\n" + errors.join("\n"));
        } else {
          alert("Errore durante il salvataggio");
        }
      }
    },
    resetForm() {
      this.form = {
        city: "",
        periodicity: "",
        expiration_date: "",
        notes: "",
        vehicle_ids: [],
        is_active: true,
      };
      this.editingId = null;
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      return date.toLocaleDateString('it-IT');
    },
  },
};
</script>

<style scoped>
.targa-auto {
    background: linear-gradient(to right, #003399 0%, #003399 8%, #ffffff 8%, #ffffff 92%, #003399 92%, #003399 100%);
    border: 1px solid #000;
    border-radius: 3px;
    padding: 3px 8px;
    display: inline-block;
    font-family: 'Arial', sans-serif;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    min-width: 90px;
}

.targa-sm {
    padding: 2px 6px;
    min-width: 70px;
}

.targa-sm .codice-targa {
    font-size: 12px;
}

.codice-targa {
    font-size: 14px;
    font-weight: bold;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table {
    border: 1px solid #dee2e6;
}

.table td,
.table th {
    border: 1px solid #dee2e6;
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
}

.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>
