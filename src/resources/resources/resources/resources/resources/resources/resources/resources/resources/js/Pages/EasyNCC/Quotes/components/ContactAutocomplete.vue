<template>
    <div>
        <!-- Contact search -->
        <BRow v-if="!disabled" class="mb-3">
            <BCol md="6">
                <label class="form-label">Cerca contatto</label>
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control"
                            placeholder="Cerca per nome, email o telefono..."
                            @input="onSearch"
                            @focus="showDropdown = searchResults.length > 0"
                            @blur="hideDropdown"
                        />
                    </div>
                    <!-- Dropdown risultati -->
                    <div v-if="showDropdown && searchResults.length > 0" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="z-index: 1050; max-height: 250px; overflow-y: auto;">
                        <div
                            v-for="c in searchResults"
                            :key="c.id"
                            class="px-3 py-2 border-bottom cursor-pointer"
                            style="cursor: pointer;"
                            @mousedown.prevent="selectContact(c)"
                        >
                            <div class="fw-medium">{{ c.name }}</div>
                            <small class="text-muted">
                                <span v-if="c.email"><i class="ri-mail-line me-1"></i>{{ c.email }}</span>
                                <span v-if="c.phone" class="ms-2"><i class="ri-phone-line me-1"></i>{{ c.phone }}</span>
                            </small>
                        </div>
                    </div>
                    <div v-if="showDropdown && searchQuery.length >= 2 && searchResults.length === 0 && !searching" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm px-3 py-2 text-muted" style="z-index: 1050;">
                        Nessun contatto trovato
                    </div>
                </div>
            </BCol>
            <BCol md="3" class="d-flex align-items-end">
                <button type="button" class="btn btn-soft-success" @click="showNewContactModal = true">
                    <i class="ri-user-add-line me-1"></i>Nuovo Contatto
                </button>
            </BCol>
            <BCol v-if="contactId" md="3" class="d-flex align-items-end">
                <span class="badge bg-primary-subtle text-primary me-2">
                    <i class="ri-link me-1"></i>Contatto #{{ contactId }}
                </span>
                <button type="button" class="btn btn-sm btn-soft-warning" @click="unlinkContact" title="Scollega contatto">
                    <i class="ri-link-unlink me-1"></i>Scollega
                </button>
            </BCol>
        </BRow>

        <!-- Modal Nuovo Contatto -->
        <BModal v-model="showNewContactModal" title="Nuovo Contatto" hide-footer size="md">
            <div v-if="newContactErrors.length > 0" class="alert alert-danger">
                <ul class="mb-0">
                    <li v-for="(err, i) in newContactErrors" :key="i">{{ err }}</li>
                </ul>
            </div>
            <div class="mb-3">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input v-model="newContactForm.name" type="text" class="form-control" placeholder="Nome completo" />
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input v-model="newContactForm.email" type="email" class="form-control" placeholder="email@esempio.it" />
            </div>
            <div class="mb-3">
                <label class="form-label">Telefono</label>
                <input v-model="newContactForm.phone" type="text" class="form-control" placeholder="+39 333..." />
            </div>
            <div class="d-flex justify-content-end gap-2">
                <BButton variant="light" @click="showNewContactModal = false">Annulla</BButton>
                <BButton variant="primary" @click="createAndSelectContact" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ri-save-line me-1"></i>Crea e Seleziona
                </BButton>
            </div>
        </BModal>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        contactId: { type: [Number, null], default: null },
        disabled: { type: Boolean, default: false },
    },
    emits: ['select', 'unlink'],
    data() {
        return {
            searchQuery: '',
            searchResults: [],
            searching: false,
            searchTimeout: null,
            showDropdown: false,
            showNewContactModal: false,
            saving: false,
            newContactErrors: [],
            newContactForm: { name: '', email: '', phone: '' },
        };
    },
    methods: {
        onSearch() {
            clearTimeout(this.searchTimeout);
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                this.showDropdown = false;
                return;
            }
            this.searching = true;
            this.showDropdown = true;
            this.searchTimeout = setTimeout(async () => {
                try {
                    const { data } = await axios.get('/api/contacts/search', { params: { q: this.searchQuery } });
                    this.searchResults = data.data || [];
                } catch (e) {
                    this.searchResults = [];
                } finally {
                    this.searching = false;
                }
            }, 300);
        },
        hideDropdown() {
            setTimeout(() => { this.showDropdown = false; }, 200);
        },
        selectContact(contact) {
            this.$emit('select', contact);
            this.searchQuery = '';
            this.searchResults = [];
            this.showDropdown = false;
        },
        unlinkContact() {
            this.$emit('unlink');
        },
        async createAndSelectContact() {
            this.saving = true;
            this.newContactErrors = [];
            try {
                const { data } = await axios.post('/api/contacts', this.newContactForm);
                this.selectContact(data.data);
                this.showNewContactModal = false;
                this.newContactForm = { name: '', email: '', phone: '' };
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.newContactErrors = Object.values(error.response.data.errors).flat();
                } else {
                    this.newContactErrors = [error.response?.data?.message || 'Errore nella creazione del contatto'];
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
