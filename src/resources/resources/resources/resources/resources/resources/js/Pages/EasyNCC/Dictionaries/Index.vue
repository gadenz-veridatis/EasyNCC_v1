<template>
  <Layout>
    <PageHeader :title="pageTitle" :items="items" />
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader>
            <BRow class="align-items-center">
              <BCol>
                <h5 class="card-title mb-0">{{ pageTitle }}</h5>
              </BCol>
              <BCol cols="auto">
                <BButton variant="primary" @click="addNewItem">
                  <i class="ri-add-line align-bottom me-1"></i> Aggiungi
                </BButton>
              </BCol>
            </BRow>
          </BCardHeader>
          <BCardBody>
            <BRow class="mb-3">
              <BCol lg="4">
                <div class="search-box">
                  <input
                    type="text"
                    class="form-control search"
                    placeholder="Cerca..."
                    v-model="searchQuery"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>
              </BCol>
              <BCol lg="4" v-if="isSuperAdmin">
                <select class="form-select" v-model="selectedCompanyId" @change="loadItems">
                  <option value="">Tutte le aziende</option>
                  <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.name }}
                  </option>
                </select>
              </BCol>
            </BRow>

            <div class="table-responsive">
              <table class="table table-borderless align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" v-if="hasSortOrder" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('sort_order')">
                      #
                      <i v-if="sortField === 'sort_order'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('name')">
                      Nome
                      <i v-if="sortField === 'name'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasCode" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('code')">
                      Codice
                      <i v-if="sortField === 'code'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasDescription">Descrizione</th>
                    <th scope="col" v-if="hasAbbreviation" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('abbreviation')">
                      Sigla
                      <i v-if="sortField === 'abbreviation'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasColor" style="white-space: nowrap;">Colore</th>
                    <th scope="col" v-if="hasTransactionTypeGroup" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('transaction_type_group')">
                      Gruppo
                      <i v-if="sortField === 'transaction_type_group'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasIsFinal" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('is_final')">
                      Finale
                      <i v-if="sortField === 'is_final'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasIsDefault" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('is_default')">
                      Default
                      <i v-if="sortField === 'is_default'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasIsActive" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('is_active')">
                      Stato
                      <i v-if="sortField === 'is_active'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="hasType" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('type')">
                      Tipo
                      <i v-if="sortField === 'type'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" v-if="isSuperAdmin" class="sortable-th" style="white-space: nowrap;" @click="toggleSort('company_name')">
                      Azienda
                      <i v-if="sortField === 'company_name'" :class="sortDirection === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>
                    </th>
                    <th scope="col" style="white-space: nowrap;">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td :colspan="columnCount" class="text-center">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="sortedItems.length === 0">
                    <td :colspan="columnCount" class="text-center">
                      Nessun elemento trovato
                    </td>
                  </tr>
                  <tr v-for="item in sortedItems" :key="item.id" v-else>
                    <td v-if="hasSortOrder" style="white-space: nowrap;">{{ item.sort_order }}</td>
                    <td style="white-space: nowrap;">{{ item.name }}</td>
                    <td v-if="hasCode" style="white-space: nowrap;">
                      <code>{{ item.code }}</code>
                    </td>
                    <td v-if="hasDescription" style="max-width: 400px; word-wrap: break-word; white-space: normal;">{{ item.description }}</td>
                    <td v-if="hasAbbreviation" style="white-space: nowrap;">{{ item.abbreviation }}</td>
                    <td v-if="hasColor" style="white-space: nowrap;">
                      <span class="badge" :style="getColorBadgeStyle(item.color)">
                        {{ item.abbreviation || item.name }}
                      </span>
                    </td>
                    <td v-if="hasTransactionTypeGroup" style="white-space: nowrap;">
                      {{ transactionTypeGroupLabel(item.transaction_type_group) }}
                    </td>
                    <td v-if="hasIsFinal" style="white-space: nowrap;">
                      <BBadge :variant="item.is_final ? 'warning' : 'secondary'">
                        {{ item.is_final ? 'Sì' : 'No' }}
                      </BBadge>
                    </td>
                    <td v-if="hasIsDefault" style="white-space: nowrap;">
                      <BBadge :variant="item.is_default ? 'success' : 'secondary'">
                        {{ item.is_default ? 'Sì' : 'No' }}
                      </BBadge>
                    </td>
                    <td v-if="hasIsActive" style="white-space: nowrap;">
                      <BBadge :variant="item.is_active ? 'success' : 'danger'">
                        {{ item.is_active ? 'Attivo' : 'Inattivo' }}
                      </BBadge>
                    </td>
                    <td v-if="hasType" style="white-space: nowrap;">
                      <BBadge :variant="item.type === 'debit' ? 'info' : 'warning'">
                        {{ item.type === 'debit' ? 'Dare' : 'Avere' }}
                      </BBadge>
                    </td>
                    <td v-if="isSuperAdmin" style="white-space: nowrap;">{{ item.company?.name || '-' }}</td>
                    <td style="white-space: nowrap;">
                      <div class="hstack gap-3 flex-wrap">
                        <a
                          href="javascript:void(0)"
                          class="link-primary"
                          @click="editItem(item)"
                        >
                          <i class="ri-pencil-line"></i>
                        </a>
                        <a
                          href="javascript:void(0)"
                          class="link-danger"
                          @click="deleteItem(item)"
                        >
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

    <!-- Modal Form -->
    <BModal v-model="showModal" :title="formTitle" hide-footer>
      <form @submit.prevent="saveItem">
        <div class="mb-3" v-if="isSuperAdmin">
          <label class="form-label">Azienda <span class="text-danger">*</span></label>
          <select
            class="form-select"
            v-model="form.company_id"
            required
          >
            <option value="">Seleziona azienda</option>
            <option v-for="company in companies" :key="company.id" :value="company.id">
              {{ company.name }}
            </option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input
            type="text"
            class="form-control"
            v-model="form.name"
            required
          />
        </div>

        <div class="mb-3" v-if="hasCode">
          <label class="form-label">Codice <span class="text-danger">*</span></label>
          <input
            type="text"
            class="form-control"
            v-model="form.code"
            required
            placeholder="es. to_pay"
          />
        </div>

        <div class="mb-3" v-if="hasDescription">
          <label class="form-label">Descrizione</label>
          <textarea
            class="form-control"
            rows="3"
            v-model="form.description"
          ></textarea>
        </div>

        <div class="mb-3" v-if="hasAbbreviation">
          <label class="form-label">Sigla</label>
          <input
            type="text"
            class="form-control"
            v-model="form.abbreviation"
          />
        </div>

        <div class="mb-3" v-if="hasColor">
          <label class="form-label">Colore Badge</label>
          <div class="d-flex align-items-center gap-2">
            <input
              type="color"
              class="form-control form-control-color"
              v-model="form.color"
              title="Scegli un colore"
            />
            <span v-if="form.color" class="badge" :style="getColorBadgeStyle(form.color)">
              Anteprima
            </span>
            <button
              v-if="form.color"
              type="button"
              class="btn btn-sm btn-soft-secondary"
              @click="form.color = ''"
              title="Rimuovi colore"
            >
              <i class="ri-close-line"></i>
            </button>
          </div>
        </div>

        <div class="mb-3" v-if="hasTransactionTypeGroup">
          <label class="form-label">Gruppo Tipo Movimento <span class="text-danger">*</span></label>
          <select class="form-select" v-model="form.transaction_type_group" required>
            <option value="">Seleziona gruppo</option>
            <option value="purchase">Acquisto (dare)</option>
            <option value="sale">Vendita (avere)</option>
            <option value="both">Entrambi</option>
          </select>
        </div>

        <div class="mb-3" v-if="hasIsFinal">
          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              v-model="form.is_final"
            />
            <label class="form-check-label">Stato finale (blocca aggiornamenti automatici)</label>
          </div>
        </div>

        <div class="mb-3" v-if="hasIsDefault">
          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              v-model="form.is_default"
            />
            <label class="form-check-label">Imposta come predefinito</label>
          </div>
        </div>

        <div class="mb-3" v-if="hasIsActive">
          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              v-model="form.is_active"
            />
            <label class="form-check-label">Attivo</label>
          </div>
        </div>

        <div class="mb-3" v-if="hasSortOrder">
          <label class="form-label">Ordine</label>
          <input
            type="number"
            class="form-control"
            v-model.number="form.sort_order"
            min="0"
          />
        </div>

        <div class="mb-3" v-if="hasType">
          <label class="form-label">Tipo <span class="text-danger">*</span></label>
          <select
            class="form-select"
            v-model="form.type"
            required
          >
            <option value="">Seleziona tipo</option>
            <option value="debit">Dare (Debit)</option>
            <option value="credit">Avere (Credit)</option>
          </select>
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
  props: {
    type: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    hasDescription: {
      type: Boolean,
      default: false,
    },
    hasAbbreviation: {
      type: Boolean,
      default: false,
    },
    hasIsDefault: {
      type: Boolean,
      default: false,
    },
    hasIsActive: {
      type: Boolean,
      default: false,
    },
    hasType: {
      type: Boolean,
      default: false,
    },
    hasCode: {
      type: Boolean,
      default: false,
    },
    hasColor: {
      type: Boolean,
      default: false,
    },
    hasTransactionTypeGroup: {
      type: Boolean,
      default: false,
    },
    hasIsFinal: {
      type: Boolean,
      default: false,
    },
    hasSortOrder: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      items: [
        { text: "EasyNCC", href: "/" },
        { text: "Dizionari", href: "#" },
        { text: this.title, active: true },
      ],
      searchQuery: "",
      showModal: false,
      loading: false,
      dictionaryItems: [],
      form: this.getEmptyForm(),
      editingId: null,
      companies: [],
      selectedCompanyId: "",
      currentUser: null,
      sortField: "name",
      sortDirection: "asc",
    };
  },
  computed: {
    pageTitle() {
      return this.title;
    },
    formTitle() {
      return this.editingId ? "Modifica Elemento" : "Nuovo Elemento";
    },
    filteredItems() {
      if (!this.searchQuery) return this.dictionaryItems;

      const query = this.searchQuery.toLowerCase();
      return this.dictionaryItems.filter(
        (item) =>
          item.name?.toLowerCase().includes(query) ||
          item.description?.toLowerCase().includes(query) ||
          item.abbreviation?.toLowerCase().includes(query) ||
          item.code?.toLowerCase().includes(query)
      );
    },
    sortedItems() {
      const items = [...this.filteredItems];
      const field = this.sortField;
      const dir = this.sortDirection === 'asc' ? 1 : -1;

      return items.sort((a, b) => {
        let valA, valB;

        if (field === 'company_name') {
          valA = a.company?.name || '';
          valB = b.company?.name || '';
        } else if (typeof a[field] === 'boolean' || typeof b[field] === 'boolean') {
          valA = a[field] ? 1 : 0;
          valB = b[field] ? 1 : 0;
          return (valA - valB) * dir;
        } else if (typeof a[field] === 'number' || field === 'sort_order') {
          valA = Number(a[field]) || 0;
          valB = Number(b[field]) || 0;
          return (valA - valB) * dir;
        } else {
          valA = (a[field] || '').toString().toLowerCase();
          valB = (b[field] || '').toString().toLowerCase();
        }

        return valA.localeCompare(valB) * dir;
      });
    },
    columnCount() {
      let count = 2; // name + actions
      if (this.hasSortOrder) count++;
      if (this.hasCode) count++;
      if (this.hasDescription) count++;
      if (this.hasAbbreviation) count++;
      if (this.hasColor) count++;
      if (this.hasTransactionTypeGroup) count++;
      if (this.hasIsFinal) count++;
      if (this.hasIsDefault) count++;
      if (this.hasIsActive) count++;
      if (this.hasType) count++;
      if (this.isSuperAdmin) count++; // company column
      return count;
    },
    isSuperAdmin() {
      return this.currentUser?.role === 'super-admin';
    },
  },
  async mounted() {
    await this.loadCurrentUser();
    await this.loadCompanies();
    await this.loadItems();
  },
  methods: {
    toggleSort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
    },
    getColorBadgeStyle(color) {
      if (!color) return { backgroundColor: '#6c757d20', color: '#6c757d' };
      // Legacy Bootstrap color names
      const bootstrapMap = {
        primary: '#405189', secondary: '#6c757d', success: '#0ab39c',
        danger: '#f06548', warning: '#f7b84b', info: '#299cdb',
      };
      const hex = color.startsWith('#') ? color : (bootstrapMap[color] || '#6c757d');
      return { backgroundColor: hex + '20', color: hex, fontWeight: '500' };
    },
    transactionTypeGroupLabel(group) {
      const labels = {
        purchase: 'Acquisto',
        sale: 'Vendita',
        both: 'Entrambi',
      };
      return labels[group] || group;
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
    getEmptyForm() {
      const form = {
        name: "",
      };

      if (this.isSuperAdmin) form.company_id = "";
      if (this.hasDescription) form.description = "";
      if (this.hasAbbreviation) form.abbreviation = "";
      if (this.hasIsDefault) form.is_default = false;
      if (this.hasIsActive) form.is_active = true;
      if (this.hasType) form.type = "";
      if (this.hasCode) form.code = "";
      if (this.hasColor) form.color = "";
      if (this.hasTransactionTypeGroup) form.transaction_type_group = "";
      if (this.hasIsFinal) form.is_final = false;
      if (this.hasSortOrder) form.sort_order = 0;

      return form;
    },
    async loadItems() {
      this.loading = true;
      try {
        const params = {};
        if (this.selectedCompanyId) {
          params.company_id = this.selectedCompanyId;
        }

        const response = await axios.get(`/api/dictionaries/${this.type}`, { params });
        this.dictionaryItems = response.data.data || [];
      } catch (error) {
        console.error("Error loading dictionary items:", error);
        this.dictionaryItems = [];
      } finally {
        this.loading = false;
      }
    },
    addNewItem() {
      this.editingId = null;
      this.form = this.getEmptyForm();
      this.showModal = true;
    },
    editItem(item) {
      this.editingId = item.id;
      this.form = { ...item };
      this.showModal = true;
    },
    async deleteItem(item) {
      if (!confirm(`Sei sicuro di voler eliminare "${item.name}"?`)) {
        return;
      }

      try {
        await axios.delete(`/api/dictionaries/${this.type}/${item.id}`);
        await this.loadItems();
      } catch (error) {
        console.error("Error deleting item:", error);
        alert("Errore durante l'eliminazione");
      }
    },
    async saveItem() {
      try {
        if (this.editingId) {
          await axios.put(
            `/api/dictionaries/${this.type}/${this.editingId}`,
            this.form
          );
        } else {
          await axios.post(`/api/dictionaries/${this.type}`, this.form);
        }

        this.showModal = false;
        this.form = this.getEmptyForm();
        this.editingId = null;
        await this.loadItems();
      } catch (error) {
        console.error("Error saving item:", error);
        alert("Errore durante il salvataggio");
      }
    },
  },
};
</script>

<style scoped>
.sortable-th {
  cursor: pointer;
  user-select: none;
}
.sortable-th:hover {
  color: #405189;
}
.sortable-th i {
  font-size: 0.75rem;
  margin-left: 2px;
}
</style>
