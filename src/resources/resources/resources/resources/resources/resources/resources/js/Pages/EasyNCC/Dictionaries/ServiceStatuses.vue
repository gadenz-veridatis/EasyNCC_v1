<template>
  <Layout>
    <PageHeader :title="pageTitle" :items="breadcrumbs" />
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader>
            <BRow class="align-items-center">
              <BCol>
                <h5 class="card-title mb-0">{{ pageTitle }}</h5>
              </BCol>
              <BCol cols="auto" v-if="isSuperAdmin">
                <select class="form-select form-select-sm" v-model="selectedCompanyId" @change="loadItems" style="width: auto;">
                  <option value="">Tutte le aziende</option>
                  <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
                </select>
              </BCol>
              <BCol cols="auto">
                <BButton variant="primary" @click="openModal()">
                  <i class="ri-add-line align-bottom me-1"></i> Aggiungi
                </BButton>
              </BCol>
            </BRow>
          </BCardHeader>
          <BCardBody>
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else-if="items.length === 0" class="text-center py-4 text-muted">Nessun elemento trovato</div>
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Colore Testo</th>
                    <th>Colore Sfondo Riga</th>
                    <th>Anteprima</th>
                    <th>Attivo</th>
                    <th v-if="isSuperAdmin">Azienda</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in items" :key="item.id">
                    <td class="fw-medium">{{ item.name }}</td>
                    <td class="small text-muted" style="max-width: 250px;">{{ item.description || '-' }}</td>
                    <td>
                      <span v-if="item.color_code" class="d-inline-flex align-items-center gap-1">
                        <span class="color-swatch" :style="{ backgroundColor: item.color_code }"></span>
                        <code class="small">{{ item.color_code }}</code>
                      </span>
                      <span v-else class="text-muted small">-</span>
                    </td>
                    <td>
                      <span v-if="item.bg_color" class="d-inline-flex align-items-center gap-1">
                        <span class="color-swatch" :style="{ backgroundColor: item.bg_color }"></span>
                        <code class="small">{{ item.bg_color }}</code>
                      </span>
                      <span v-else class="text-muted small">-</span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :style="{
                          backgroundColor: item.color_code || '#6c757d',
                          color: '#fff',
                        }"
                      >{{ item.name }}</span>
                      <span
                        v-if="item.bg_color"
                        class="ms-2 px-2 py-1 rounded small"
                        :style="{ backgroundColor: item.bg_color + '20', borderLeft: '3px solid ' + item.bg_color }"
                      >riga</span>
                    </td>
                    <td>
                      <span class="badge" :class="item.is_active ? 'bg-success' : 'bg-danger'">
                        {{ item.is_active ? 'Sì' : 'No' }}
                      </span>
                    </td>
                    <td v-if="isSuperAdmin">{{ item.company?.name || '-' }}</td>
                    <td>
                      <div class="hstack gap-3">
                        <a href="javascript:void(0)" class="link-primary" @click="openModal(item)"><i class="ri-pencil-line"></i></a>
                        <a href="javascript:void(0)" class="link-danger" @click="deleteItem(item)"><i class="ri-delete-bin-line"></i></a>
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
    <BModal v-model="showModal" :title="editingId ? 'Modifica Stato' : 'Nuovo Stato'" hide-footer>
      <form @submit.prevent="saveItem">
        <div class="mb-3" v-if="isSuperAdmin">
          <label class="form-label">Azienda <span class="text-danger">*</span></label>
          <select class="form-select" v-model="form.company_id" required>
            <option value="">Seleziona azienda</option>
            <option v-for="company in companies" :key="company.id" :value="company.id">{{ company.name }}</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Nome <span class="text-danger">*</span></label>
          <input type="text" class="form-control" v-model="form.name" required />
        </div>

        <div class="mb-3">
          <label class="form-label">Descrizione</label>
          <textarea class="form-control" rows="2" v-model="form.description"></textarea>
        </div>

        <BRow>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Colore Testo (Foreground)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" v-model="form.color_code" class="form-control form-control-color" style="width: 50px; height: 38px;" />
                <input type="text" v-model="form.color_code" class="form-control" placeholder="#000000" maxlength="7" style="width: 100px;" />
                <button v-if="form.color_code" type="button" class="btn btn-sm btn-outline-secondary" @click="form.color_code = ''" title="Rimuovi">
                  <i class="ri-close-line"></i>
                </button>
              </div>
            </div>
          </BCol>
          <BCol md="6">
            <div class="mb-3">
              <label class="form-label">Colore Sfondo Riga</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" v-model="form.bg_color" class="form-control form-control-color" style="width: 50px; height: 38px;" />
                <input type="text" v-model="form.bg_color" class="form-control" placeholder="#000000" maxlength="7" style="width: 100px;" />
                <button v-if="form.bg_color" type="button" class="btn btn-sm btn-outline-secondary" @click="form.bg_color = ''" title="Rimuovi">
                  <i class="ri-close-line"></i>
                </button>
              </div>
            </div>
          </BCol>
        </BRow>

        <!-- Preview -->
        <div v-if="form.color_code || form.bg_color" class="mb-3 p-3 border rounded bg-light">
          <label class="form-label small text-muted">Anteprima</label>
          <div class="d-flex align-items-center gap-3">
            <span class="badge" :style="{ backgroundColor: form.color_code || '#6c757d', color: '#fff' }">
              {{ form.name || 'Stato' }}
            </span>
            <span v-if="form.bg_color" class="px-3 py-2 rounded small" :style="{ backgroundColor: form.bg_color + '20', borderLeft: '3px solid ' + form.bg_color }">
              Sfondo riga tabella
            </span>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" v-model="form.is_active" />
            <label class="form-check-label">Attivo</label>
          </div>
        </div>

        <div class="text-end">
          <BButton variant="light" class="me-2" @click="showModal = false">Annulla</BButton>
          <BButton variant="primary" type="submit">Salva</BButton>
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
  components: { Layout, PageHeader },
  data() {
    return {
      pageTitle: 'Stati Servizi',
      breadcrumbs: [
        { text: 'EasyNCC', href: '/' },
        { text: 'Dizionari', href: '#' },
        { text: 'Stati Servizi', active: true },
      ],
      items: [],
      companies: [],
      loading: false,
      showModal: false,
      editingId: null,
      selectedCompanyId: '',
      currentUser: null,
      form: this.getEmptyForm(),
    };
  },
  computed: {
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
    getEmptyForm() {
      return {
        name: '',
        description: '',
        color_code: '',
        bg_color: '',
        is_active: true,
        company_id: '',
      };
    },
    async loadCurrentUser() {
      try {
        const res = await axios.get('/api/user');
        this.currentUser = res.data;
      } catch (e) { console.error(e); }
    },
    async loadCompanies() {
      if (!this.isSuperAdmin) return;
      try {
        const res = await axios.get('/api/companies');
        this.companies = res.data.data || [];
      } catch (e) { console.error(e); }
    },
    async loadItems() {
      this.loading = true;
      try {
        const params = {};
        if (this.selectedCompanyId) params.company_id = this.selectedCompanyId;
        const res = await axios.get('/api/dictionaries/service-statuses', { params });
        this.items = res.data.data || [];
      } catch (e) {
        console.error(e);
        this.items = [];
      } finally {
        this.loading = false;
      }
    },
    openModal(item = null) {
      if (item) {
        this.editingId = item.id;
        this.form = { ...item };
        if (!this.form.color_code) this.form.color_code = '';
        if (!this.form.bg_color) this.form.bg_color = '';
      } else {
        this.editingId = null;
        this.form = this.getEmptyForm();
      }
      this.showModal = true;
    },
    async saveItem() {
      try {
        const data = { ...this.form };
        if (!data.color_code) data.color_code = null;
        if (!data.bg_color) data.bg_color = null;

        if (this.editingId) {
          await axios.put(`/api/dictionaries/service-statuses/${this.editingId}`, data);
        } else {
          await axios.post('/api/dictionaries/service-statuses', data);
        }
        this.showModal = false;
        await this.loadItems();
      } catch (e) {
        console.error(e);
        alert('Errore durante il salvataggio');
      }
    },
    async deleteItem(item) {
      if (!confirm(`Eliminare "${item.name}"?`)) return;
      try {
        await axios.delete(`/api/dictionaries/service-statuses/${item.id}`);
        await this.loadItems();
      } catch (e) {
        console.error(e);
        alert("Errore durante l'eliminazione");
      }
    },
  },
};
</script>

<style scoped>
.color-swatch {
  display: inline-block;
  width: 18px;
  height: 18px;
  border-radius: 3px;
  border: 1px solid rgba(0,0,0,0.2);
}
</style>
