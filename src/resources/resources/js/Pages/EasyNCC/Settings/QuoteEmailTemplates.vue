<template>
    <Layout>
        <PageHeader title="Template Email Preventivi" pageTitle="Impostazioni" />
        <BRow>
            <BCol lg="8">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-mail-settings-line me-2"></i>Template Email
                        </h5>
                        <button class="btn btn-sm btn-primary" @click="openCreateModal">
                            <i class="ri-add-line me-1"></i>Nuovo Template
                        </button>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Company Selection (super-admin only) -->
                        <BRow v-if="isSuperAdmin && !loading" class="mb-4">
                            <BCol md="12">
                                <div class="alert alert-info">
                                    <label class="form-label fw-bold">Seleziona Azienda</label>
                                    <select v-model="selectedCompanyId" class="form-select" @change="loadTemplates">
                                        <option value="">Seleziona un'azienda</option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.name }}
                                        </option>
                                    </select>
                                </div>
                            </BCol>
                        </BRow>

                        <!-- Alert errori -->
                        <div v-if="errors.length > 0" class="alert alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                            </ul>
                        </div>

                        <!-- Alert successo -->
                        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
                            {{ successMessage }}
                            <button type="button" class="btn-close" @click="successMessage = ''"></button>
                        </div>

                        <!-- Templates Table -->
                        <div v-if="!loading && selectedCompanyId" class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Oggetto</th>
                                        <th class="text-center">Predefinito</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="templates.length === 0">
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Nessun template configurato. Crea il primo template.
                                        </td>
                                    </tr>
                                    <tr v-for="template in templates" :key="template.id">
                                        <td class="fw-medium">{{ template.name }}</td>
                                        <td>
                                            <small class="text-muted">{{ template.subject }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="template.is_default" class="badge bg-success">
                                                <i class="ri-check-line"></i> Default
                                            </span>
                                            <button v-else class="btn btn-sm btn-outline-secondary" @click="setDefault(template.id)" :disabled="actionLoading">
                                                Imposta
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-soft-primary me-1" @click="openEditModal(template)" title="Modifica">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button class="btn btn-sm btn-soft-danger" @click="deleteTemplate(template)" title="Elimina" :disabled="actionLoading">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="!loading && !selectedCompanyId && isSuperAdmin" class="alert alert-warning">
                            Seleziona un'azienda per gestire i template email
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <!-- Colonna segnaposto -->
            <BCol lg="4">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-braces-line me-2"></i>Segnaposto Disponibili
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <p class="text-muted small mb-3">
                            Usa questi segnaposto nell'oggetto e nel corpo del template. Verranno sostituiti con i dati del preventivo.
                        </p>

                        <!-- Quote-level placeholders -->
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">Preventivo</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr v-for="ph in quotePlaceholders" :key="ph.key" class="cursor-pointer" @click="copyPlaceholder(ph.key)">
                                        <td>
                                            <code class="text-primary">{{ ph.key }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ ph.description }}</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-3" />

                        <!-- Item-level placeholders -->
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">
                            <i class="ri-repeat-line me-1"></i>Blocco Servizi
                        </h6>
                        <div class="alert alert-light border small py-2 px-3 mb-2" v-pre>
                            Racchiudi il testo tra <code>{{#each_service}}</code> e <code>{{/each_service}}</code> per ripeterlo per ogni servizio del preventivo. All'interno usa i segnaposto <code>{{item_*}}</code>.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr v-for="ph in itemPlaceholders" :key="ph.key" class="cursor-pointer" @click="copyPlaceholder(ph.key)">
                                        <td>
                                            <code :class="ph.is_block ? 'text-warning fw-bold' : 'text-success'">{{ ph.key }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ ph.description }}</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-3" />

                        <!-- Example -->
                        <h6 class="text-uppercase text-muted small fw-bold mb-2">
                            <i class="ri-lightbulb-line me-1"></i>Esempio
                        </h6>
                        <div class="bg-light border rounded p-2 small" style="font-family: monospace; white-space: pre-wrap;" v-pre>{{#each_service}}
Servizio {{item_number}}: {{item_destination_name}}
Tipo: {{item_service_type}} - {{item_mileage}} km
Durata: {{item_duration_hours}} ore
Imponibile: €{{item_taxable_price}}
{{/each_service}}</div>

                        <small class="text-muted d-block mt-3">
                            <i class="ri-information-line me-1"></i>Clicca su un segnaposto per copiarlo
                        </small>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <!-- Create/Edit Modal -->
        <BModal v-model="showModal" :title="editingTemplate ? 'Modifica Template' : 'Nuovo Template'" hide-footer size="lg" @shown="editorReady = true" @hidden="editorReady = false; editorInstance = null">
            <form @submit.prevent="saveTemplate">
                <div v-if="modalErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, index) in modalErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome Template <span class="text-danger">*</span></label>
                    <input v-model="modalForm.name" type="text" class="form-control" placeholder="es. Template Standard" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">Oggetto Email <span class="text-danger">*</span></label>
                    <input v-model="modalForm.subject" type="text" class="form-control" placeholder="es. Preventivo #{{quote_id}} - {{destination_name}}" required />
                    <small class="text-muted">Puoi usare i segnaposto nell'oggetto</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Corpo Email (HTML) <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Inserisci segnaposto:</label>
                        <div class="btn-group btn-group-sm flex-wrap">
                            <button v-for="ph in availablePlaceholders" :key="ph.key" type="button" class="btn btn-outline-secondary" @click="insertPlaceholder(ph.key)" :title="ph.description">
                                {{ ph.key }}
                            </button>
                        </div>
                    </div>
                    <Ckeditor v-if="editorReady" v-model="modalForm.body_html" :editor="editor" :config="editorConfig" ref="ckEditor" @ready="onEditorReady"></Ckeditor>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input v-model="modalForm.is_default" type="checkbox" class="form-check-input" id="isDefaultCheck" />
                        <label class="form-check-label" for="isDefaultCheck">Imposta come template predefinito</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <BButton variant="light" @click="showModal = false">Annulla</BButton>
                    <BButton type="submit" variant="primary" :disabled="modalSaving">
                        <span v-if="modalSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ri-save-line me-1"></i>
                        {{ editingTemplate ? 'Aggiorna' : 'Crea' }}
                    </BButton>
                </div>
            </form>
        </BModal>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import { ClassicEditor, Essentials, Bold, Italic, Underline, Strikethrough, Font, Link as CKLink, List, BlockQuote, Table, TableToolbar, Heading, Paragraph, Undo, Alignment } from 'ckeditor5';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import 'ckeditor5/ckeditor5.css';

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
        Ckeditor,
    },
    props: {
        availablePlaceholders: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            loading: false,
            actionLoading: false,
            modalSaving: false,
            errors: [],
            modalErrors: [],
            successMessage: '',
            companies: [],
            selectedCompanyId: '',
            templates: [],
            editingTemplate: null,
            modalForm: {
                name: '',
                subject: '',
                body_html: '',
                is_default: false,
            },
            showModal: false,
            editorReady: false,
            editorInstance: null,
            editor: ClassicEditor,
            editorConfig: {
                plugins: [
                    Essentials, Bold, Italic, Underline, Strikethrough,
                    Font, CKLink, List, BlockQuote, Table, TableToolbar,
                    Heading, Paragraph, Undo, Alignment,
                ],
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontColor', 'fontBackgroundColor', 'fontSize', '|',
                    'alignment', '|',
                    'link', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', '|',
                    'undo', 'redo',
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragrafo', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Titolo 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Titolo 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Titolo 3', class: 'ck-heading_heading3' },
                    ],
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
                },
                licenseKey: 'GPL',
                language: 'it',
            },
        };
    },
    computed: {
        currentUser() {
            return this.$page?.props?.auth?.user;
        },
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        quotePlaceholders() {
            return this.availablePlaceholders.filter(ph => !ph.is_item && !ph.is_block);
        },
        itemPlaceholders() {
            return this.availablePlaceholders.filter(ph => ph.is_item || ph.is_block);
        },
    },
    async mounted() {
        if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else if (this.currentUser?.company_id) {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadTemplates();
        }
    },
    methods: {
        async loadCompanies() {
            try {
                const response = await axios.get('/api/companies');
                this.companies = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },
        async loadTemplates() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/quote-email-templates', { params });
                this.templates = response.data.data || [];
            } catch (error) {
                console.error('Error loading templates:', error);
                this.errors = ['Errore nel caricamento dei template'];
            } finally {
                this.loading = false;
            }
        },
        openCreateModal() {
            this.editingTemplate = null;
            this.editorReady = false;
            this.modalForm = {
                name: '',
                subject: '',
                body_html: '',
                is_default: false,
            };
            this.modalErrors = [];
            this.showModal = true;
        },
        openEditModal(template) {
            this.editingTemplate = template;
            this.editorReady = false;
            this.modalForm = {
                name: template.name,
                subject: template.subject,
                body_html: template.body_html,
                is_default: template.is_default,
            };
            this.modalErrors = [];
            this.showModal = true;
        },
        onEditorReady(editor) {
            this.editorInstance = editor;
        },
        async saveTemplate() {
            this.modalSaving = true;
            this.modalErrors = [];

            try {
                const payload = { ...this.modalForm };
                if (this.isSuperAdmin) {
                    payload.company_id = this.selectedCompanyId;
                }

                if (this.editingTemplate) {
                    await axios.put(`/api/quote-email-templates/${this.editingTemplate.id}`, payload);
                    this.successMessage = 'Template aggiornato con successo';
                } else {
                    await axios.post('/api/quote-email-templates', payload);
                    this.successMessage = 'Template creato con successo';
                }

                this.showModal = false;
                await this.loadTemplates();
            } catch (error) {
                if (error.response?.status === 422) {
                    const validationErrors = error.response.data.errors || {};
                    this.modalErrors = Object.values(validationErrors).flat();
                } else {
                    this.modalErrors = ['Errore nel salvataggio del template'];
                }
            } finally {
                this.modalSaving = false;
            }
        },
        async setDefault(id) {
            this.actionLoading = true;
            this.errors = [];

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                await axios.post(`/api/quote-email-templates/${id}/set-default`, params);
                this.successMessage = 'Template impostato come predefinito';
                await this.loadTemplates();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nell\'impostazione del default'];
            } finally {
                this.actionLoading = false;
            }
        },
        async deleteTemplate(template) {
            if (!confirm(`Eliminare il template "${template.name}"?`)) return;

            this.actionLoading = true;
            this.errors = [];

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                await axios.delete(`/api/quote-email-templates/${template.id}`, { params });
                this.successMessage = 'Template eliminato con successo';
                await this.loadTemplates();
            } catch (error) {
                this.errors = [error.response?.data?.message || 'Errore nell\'eliminazione del template'];
            } finally {
                this.actionLoading = false;
            }
        },
        insertPlaceholder(key) {
            if (this.editorInstance) {
                this.editorInstance.model.change(writer => {
                    const insertPosition = this.editorInstance.model.document.selection.getFirstPosition();
                    writer.insertText(key, insertPosition);
                });
                this.editorInstance.editing.view.focus();
            } else {
                this.modalForm.body_html += key;
            }
        },
        copyPlaceholder(key) {
            navigator.clipboard.writeText(key).then(() => {
                this.successMessage = `Segnaposto ${key} copiato negli appunti`;
                setTimeout(() => { this.successMessage = ''; }, 2000);
            }).catch(() => {
                // Fallback: just show the key
            });
        },
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.cursor-pointer:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}
</style>

<style>
/* CKEditor balloon panels must appear above Bootstrap modals (z-index 1055) */
.ck-body-wrapper .ck-balloon-panel {
    z-index: 1060 !important;
}
</style>
