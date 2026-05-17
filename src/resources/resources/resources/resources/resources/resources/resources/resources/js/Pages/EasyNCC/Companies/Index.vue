<template>
  <Layout>
    <PageHeader :title="pageTitle" :items="items" />
    <BRow>
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader>
            <BRow class="align-items-center">
              <BCol>
                <h5 class="card-title mb-0">Lista Aziende</h5>
              </BCol>
              <BCol cols="auto">
                <BButton variant="primary" @click="$inertia.visit('/easyncc/companies/create')">
                  <i class="ri-add-line align-bottom me-1"></i> Nuova Azienda
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
                    placeholder="Cerca azienda..."
                    v-model="searchQuery"
                  />
                  <i class="ri-search-line search-icon"></i>
                </div>
              </BCol>
            </BRow>

            <div class="table-responsive">
              <table class="table table-borderless table-nowrap align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col">Nome Azienda</th>
                    <th scope="col">Email</th>
                    <th scope="col">Telefono</th>
                    <th scope="col">P.IVA</th>
                    <th scope="col">Stato</th>
                    <th scope="col">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading">
                    <td colspan="6" class="text-center">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="filteredCompanies.length === 0">
                    <td colspan="6" class="text-center">
                      Nessuna azienda trovata
                    </td>
                  </tr>
                  <tr v-for="company in filteredCompanies" :key="company.id" v-else>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                          <div class="avatar-sm">
                            <div class="avatar-title rounded-circle bg-light text-primary">
                              {{ company.name.charAt(0).toUpperCase() }}
                            </div>
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <h5 class="fs-14 mb-0">
                            <a href="javascript:void(0)" class="text-dark">{{ company.name }}</a>
                          </h5>
                        </div>
                      </div>
                    </td>
                    <td>{{ company.email }}</td>
                    <td>{{ company.phone }}</td>
                    <td>{{ company.vat_number }}</td>
                    <td>
                      <BBadge :variant="company.is_active ? 'success' : 'danger'">
                        {{ company.is_active ? 'Attiva' : 'Inattiva' }}
                      </BBadge>
                    </td>
                    <td>
                      <button
                        class="btn btn-sm btn-soft-info me-1"
                        @click="$inertia.visit(`/easyncc/companies/${company.id}`)"
                        title="Visualizza Dettagli"
                      >
                        <i class="bx bx-show"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-soft-primary me-1"
                        @click="$inertia.visit(`/easyncc/companies/${company.id}/edit`)"
                        title="Modifica"
                      >
                        <i class="bx bx-edit"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-soft-danger"
                        @click="deleteCompany(company)"
                        title="Elimina"
                      >
                        <i class="bx bx-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
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
      pageTitle: "Aziende",
      items: [
        { text: "EasyNCC", href: "/" },
        { text: "Aziende", active: true },
      ],
      searchQuery: "",
      loading: false,
      companies: [],
    };
  },
  computed: {
    filteredCompanies() {
      if (!this.searchQuery) return this.companies;

      const query = this.searchQuery.toLowerCase();
      return this.companies.filter(
        (company) =>
          company.name?.toLowerCase().includes(query) ||
          company.email?.toLowerCase().includes(query) ||
          company.vat_number?.toLowerCase().includes(query)
      );
    },
  },
  mounted() {
    this.loadCompanies();
  },
  methods: {
    async loadCompanies() {
      this.loading = true;
      try {
        const response = await axios.get("/api/companies");
        this.companies = response.data.data || [];
      } catch (error) {
        console.error("Error loading companies:", error);
        // Dati di esempio per sviluppo
        this.companies = [];
      } finally {
        this.loading = false;
      }
    },
    async deleteCompany(company) {
      if (!confirm(`Sei sicuro di voler eliminare l'azienda "${company.name}"?`)) {
        return;
      }

      try {
        await axios.delete(`/api/companies/${company.id}`);
        await this.loadCompanies();
      } catch (error) {
        console.error("Error deleting company:", error);
        alert("Errore durante l'eliminazione");
      }
    },
  },
};
</script>
