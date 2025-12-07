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
                <BButton variant="primary" @click="showModal = true">
                  <i class="ri-add-line align-bottom me-1"></i> Aggiungi ZTL
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
                    placeholder="Cerca comune..."
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
              <table class="table table-borderless table-nowrap align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col">Comune</th>
                    <th scope="col">Durata (ore)</th>
                    <th scope="col">Costo (€)</th>
                    <th scope="col">Stato</th>
                    <th scope="col" v-if="isSuperAdmin">Azienda</th>
                    <th scope="col">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td :colspan="isSuperAdmin ? 6 : 5" class="text-center">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredItems.length === 0">
                    <td :colspan="isSuperAdmin ? 6 : 5" class="text-center">
                      Nessun elemento trovato
                    </td>
                  </tr>
                  <tr v-for="item in filteredItems" :key="item.id" v-else>
                    <td>{{ item.city }}</td>
                    <td>{{ item.duration }}</td>
                    <td>€ {{ item.cost }}</td>
                    <td>
                      <BBadge :variant="item.is_active ? 'success' : 'danger'">
                        {{ item.is_active ? 'Attivo' : 'Inattivo' }}
                      </BBadge>
                    </td>
                    <td v-if="isSuperAdmin">{{ item.company?.name || '-' }}</td>
                    <td>
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
        <div class="mb-3">
          <label class="form-label">Comune <span class="text-danger">*</span></label>
          <input
            type="text"
            class="form-control"
            v-model="form.city"
            required
          />
        </div>

        <div class="mb-3">
          <label class="form-label">Durata (ore) <span class="text-danger">*</span></label>
          <input
            type="number"
            step="0.1"
            class="form-control"
            v-model="form.duration"
            required
          />
        </div>

        <div class="mb-3">
          <label class="form-label">Costo (€) <span class="text-danger">*</span></label>
          <input
            type="number"
            step="0.01"
            class="form-control"
            v-model="form.cost"
            required
          />
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              v-model="form.is_active"
            />
            <label class="form-check-label">Attivo</label>
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
        { text: "Dizionari", href: "#" },
        { text: "ZTL", active: true },
      ],
      searchQuery: "",
      showModal: false,
      loading: false,
      ztlItems: [],
      form: {
        city: "",
        duration: 0,
        cost: 0,
        is_active: true,
      },
      editingId: null,
      companies: [],
      selectedCompanyId: "",
      currentUser: null,
    };
  },
  computed: {
    formTitle() {
      return this.editingId ? "Modifica ZTL" : "Nuova ZTL";
    },
    filteredItems() {
      if (!this.searchQuery) return this.ztlItems;

      const query = this.searchQuery.toLowerCase();
      return this.ztlItems.filter((item) =>
        item.city?.toLowerCase().includes(query)
      );
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
    editItem(item) {
      this.editingId = item.id;
      this.form = { ...item };
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
        if (this.editingId) {
          await axios.put(
            `/api/dictionaries/ztl/${this.editingId}`,
            this.form
          );
        } else {
          await axios.post("/api/dictionaries/ztl", this.form);
        }

        this.showModal = false;
        this.form = {
          city: "",
          duration: 0,
          cost: 0,
          is_active: true,
        };
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
