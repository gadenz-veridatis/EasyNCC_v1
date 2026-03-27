<template>
    <Head :title="isEditing ? 'Modifica Preventivo' : 'Nuovo Preventivo'" />

    <Layout>
        <PageHeader
            :title="pageTitle"
            pageTitle="Preventivi"
        />

        <BRow>
            <BCol lg="12">
                <!-- Stepper (only when editing an existing quote) -->
                <QuoteWorkflowStepper v-if="isEditing" :status="quoteStatus" />

                <!-- Version toolbar -->
                <div v-if="isEditing" class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <Link :href="`/easyncc/quotes/${quote.id}`" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="ri-eye-line me-1"></i>Dettaglio
                        </Link>
                    </div>
                </div>

                <!-- Success message -->
                <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
                    {{ successMessage }}
                    <button type="button" class="btn-close" @click="successMessage = ''"></button>
                </div>

                <!-- Transition error -->
                <div v-if="transitionError" class="alert alert-danger alert-dismissible fade show">
                    {{ transitionError }}
                    <button type="button" class="btn-close" @click="transitionError = ''"></button>
                </div>
            </BCol>
        </BRow>

        <BRow>
            <!-- Versions Sidebar (only when editing and has versions) -->
            <BCol v-if="isEditing && versionsList.length > 0" lg="4" class="mb-3">
                <QuoteVersionsSidebar
                    :versions="versionsList"
                    :creating="creatingVersion"
                    :restoring="restoringVersion"
                    @create-version="createNewVersion"
                    @preview-version="previewVersion"
                    @restore-version="confirmRestoreVersion"
                />
            </BCol>

            <!-- Main content -->
            <BCol :lg="isEditing && versionsList.length > 0 ? 8 : 12">
                <form @submit.prevent="saveQuote">
                    <!-- Sezione 1: Dati Cliente -->
                    <BCard no-body class="mb-3">
                        <BCardHeader>
                            <h6 class="card-title mb-0">
                                <i class="ri-user-line me-2"></i>Dati Cliente
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <ContactAutocomplete
                                v-if="!isReadOnly"
                                :contactId="form.contact_id"
                                @select="onContactSelect"
                                @unlink="form.contact_id = null"
                            />
                            <BRow>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Nome Cliente</label>
                                    <input v-model="form.client_name" type="text" class="form-control" placeholder="Nome del cliente" :disabled="isReadOnly" />
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Email Cliente</label>
                                    <input v-model="form.client_email" type="email" class="form-control" placeholder="email@esempio.it" :disabled="isReadOnly" />
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Data Servizio</label>
                                    <input v-model="form.service_date" type="date" class="form-control" :disabled="isReadOnly" />
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="12" class="mb-3">
                                    <label class="form-label">Note</label>
                                    <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Note aggiuntive" :disabled="isReadOnly"></textarea>
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione 2: Servizi (Items) -->
                    <BCard no-body class="mb-3">
                        <BCardHeader class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="ri-route-line me-2"></i>Servizi
                            </h6>
                            <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-soft-primary" @click="addItem">
                                <i class="ri-add-line me-1"></i>Aggiungi Servizio
                            </button>
                        </BCardHeader>
                        <BCardBody class="p-0">
                            <div v-for="(item, index) in form.items" :key="index" class="border-bottom">
                                <!-- Item Header (clickable to collapse) -->
                                <div
                                    class="d-flex justify-content-between align-items-center px-3 py-2 bg-light"
                                    @click="toggleItem(index)"
                                    style="cursor: pointer;"
                                >
                                    <div class="d-flex align-items-center gap-2">
                                        <i :class="expandedItems[index] ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" class="fs-5"></i>
                                        <strong>Servizio {{ index + 1 }}</strong>
                                        <span v-if="item.destination_name" class="text-muted">- {{ item.destination_name }}</span>
                                        <span v-if="item.service_type" class="badge ms-1" :class="serviceTypeBadgeClass(item.service_type, 'bg-info-subtle text-info')">{{ item.service_type }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-primary">{{ formatCurrency(item.taxable_price) }}</span>
                                        <button
                                            v-if="!isReadOnly && form.items.length > 1"
                                            type="button"
                                            class="btn btn-sm btn-soft-danger"
                                            @click.stop="removeItem(index)"
                                            title="Rimuovi servizio"
                                        >
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Item Body (collapsible) -->
                                <div v-show="expandedItems[index]" class="p-3">
                                    <BRow>
                                        <BCol md="4" class="mb-3">
                                            <label class="form-label">Destinazione</label>
                                            <select v-model="item.pricing_destination_id" class="form-select" @change="onItemDestinationChange(index)" :disabled="isReadOnly">
                                                <option :value="null">-- Personalizzato --</option>
                                                <option v-for="dest in pricingDestinations" :key="dest.id" :value="dest.id">
                                                    {{ dest.destination }} - {{ dest.service_type }}
                                                </option>
                                            </select>
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Tipo Servizio</label>
                                            <select v-model="item.service_type" class="form-select" :disabled="isReadOnly">
                                                <option value="TRF">TRF</option>
                                                <option value="TOUR HD">TOUR HD</option>
                                                <option value="TOUR FD">TOUR FD</option>
                                                <option value="TOUR FD+">TOUR FD+</option>
                                            </select>
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Nome Destinazione</label>
                                            <input v-model="item.destination_name" type="text" class="form-control" placeholder="Es. Chianti Classico" :disabled="isReadOnly" />
                                        </BCol>
                                    </BRow>
                                    <BRow>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Km</label>
                                            <input v-model.number="item.mileage" type="number" class="form-control" min="0" step="1" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Extra Km</label>
                                            <input v-model.number="item.extra_km" type="number" class="form-control" min="0" step="1" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Durata (ore)</label>
                                            <input v-model.number="item.duration_hours" type="number" class="form-control" min="0" step="0.5" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Estensione (ore)</label>
                                            <input v-model.number="item.extension_hours" type="number" class="form-control" min="0" step="0.5" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Ore Spost. Extra</label>
                                            <input v-model.number="item.extra_travel_hours" type="number" class="form-control" min="0" step="0.5" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Pedaggio (&euro;)</label>
                                            <input v-model.number="item.toll_cost" type="number" class="form-control" min="0" step="1" :disabled="isReadOnly" />
                                        </BCol>
                                    </BRow>
                                    <BRow>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Pax</label>
                                            <input v-model.number="item.pax_count" type="number" class="form-control" min="0" step="1" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="2" class="mb-3">
                                            <label class="form-label">Esperienza p.p. (&euro;)</label>
                                            <input v-model.number="item.experience_per_pax" type="number" class="form-control" min="0" step="1" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label fw-bold">Imponibile (&euro;)</label>
                                            <input v-model.number="item.taxable_price" type="number" class="form-control fw-bold text-primary" min="0" step="5" @input="recalculate" :disabled="isReadOnly" />
                                        </BCol>
                                        <BCol md="3" class="mb-3 d-flex align-items-end">
                                            <button v-if="!isReadOnly" type="button" class="btn btn-outline-primary" @click="openCalculatorModal(index)">
                                                <i class="ri-calculator-line me-1"></i>Calcolatore
                                            </button>
                                        </BCol>
                                    </BRow>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione 3: Riepilogo Prezzi -->
                    <BCard no-body class="mb-3">
                        <BCardHeader class="bg-light">
                            <h6 class="card-title mb-0">
                                <i class="ri-money-euro-circle-line me-2"></i>Riepilogo Prezzi
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <BRow>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label small text-muted">Somma Imponibili Servizi</label>
                                    <div class="fw-semibold">{{ formatCurrency(itemsTaxableSum) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label small text-muted">Imponibile Totale (arr. 5&euro;)</label>
                                    <div class="fs-5 fw-bold text-primary">{{ formatCurrency(result.taxable_price_rounded) }}</div>
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Prezzo Imponibile Override (&euro;)</label>
                                    <input v-model.number="form.override_taxable" type="number" class="form-control fw-bold text-primary" min="0" step="5" @input="recalculate" :disabled="isReadOnly" />
                                    <small class="text-muted">Calcolato: {{ formatCurrency(result.taxable_price_rounded) }}</small>
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Sconto (%)</label>
                                    <input v-model.number="form.discount_percentage" type="number" class="form-control" min="0" max="100" step="1" @input="recalculate" :disabled="isReadOnly" />
                                </BCol>
                                <BCol md="3" class="mb-3">
                                    <label class="form-label">Promozione <span v-if="form.discount_percentage > 0" class="text-danger">*</span></label>
                                    <input v-model="form.discount_name" type="text" class="form-control" placeholder="Nome promozione" :disabled="isReadOnly" />
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">VAT (%)</label>
                                    <select v-model.number="form.vat_percentage" class="form-select" @change="recalculate" :disabled="isReadOnly">
                                        <option :value="10">10%</option>
                                        <option :value="22">22%</option>
                                    </select>
                                </BCol>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Card Fees (%)</label>
                                    <input v-model.number="form.card_fees_percentage" type="number" class="form-control" min="0" max="100" step="0.5" @input="recalculate" :disabled="isReadOnly" />
                                </BCol>
                            </BRow>
                            <!-- Imponibile scontato -->
                            <BRow v-if="form.discount_percentage > 0">
                                <BCol md="3" class="mb-3">
                                    <label class="form-label small text-muted">Imponibile Scontato &euro;</label>
                                    <div class="fs-5 fw-bold text-primary">{{ formatCurrency(result.discounted_taxable) }}</div>
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">VAT</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.vat_amount) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">CC Fees</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.cc_fees_amount) }}</div>
                                </BCol>
                                <BCol md="3" class="mb-2">
                                    <label class="form-label small text-muted">Prezzo Finale (arr. 5&euro;)</label>
                                    <div class="fs-5 fw-bold text-success">{{ formatCurrency(result.final_price_rounded) }}</div>
                                </BCol>
                            </BRow>
                            <hr />
                            <!-- Acconto -->
                            <BRow>
                                <BCol md="2" class="mb-3">
                                    <label class="form-label">Acconto (%)</label>
                                    <input v-model.number="form.deposit_percentage" type="number" class="form-control" min="0" max="100" step="5" @input="recalculate" :disabled="isReadOnly" />
                                </BCol>
                            </BRow>
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Imponibile &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_taxable) }}</div>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Handling Fees &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_handling_fees) }}</div>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Acconto Totale &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.deposit_total) }}</div>
                                </BCol>
                            </BRow>
                            <!-- Saldo -->
                            <BRow>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Imponibile &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_taxable) }}</div>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Handling Fees &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_handling_fees) }}</div>
                                </BCol>
                                <BCol md="4" class="mb-3">
                                    <label class="form-label small text-muted">Saldo Card Fees &euro;</label>
                                    <div class="fw-semibold">{{ formatCurrency(result.balance_card_fees) }}</div>
                                </BCol>
                            </BRow>
                        </BCardBody>
                    </BCard>

                    <!-- Sezione: Pannello Email (solo stato approved) -->
                    <BCard v-if="isEditing && quoteStatus === 'approved'" no-body class="mb-3 border-primary">
                        <BCardHeader class="bg-primary bg-opacity-10">
                            <h6 class="card-title mb-0">
                                <i class="ri-mail-send-line me-2"></i>Email Preventivo
                            </h6>
                        </BCardHeader>
                        <BCardBody>
                            <div class="mb-3">
                                <label class="form-label">Oggetto</label>
                                <input v-model="editableSubject" type="text" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Corpo Email</label>
                                <Ckeditor v-if="editorReady" v-model="editableBodyHtml" :editor="editor" :config="editorConfig" @ready="onEditorReady"></Ckeditor>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" @click="sendEmail" :disabled="transitioning">
                                    <span v-if="transitioning" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="ri-send-plane-line me-1"></i>
                                    Invia Email
                                </button>
                                <button type="button" class="btn btn-outline-warning" @click="revertToDraft" :disabled="transitioning">
                                    <i class="ri-arrow-go-back-line me-1"></i>
                                    Torna a Bozza
                                </button>
                            </div>
                            <div v-if="quote.sumup_checkout_url" class="mt-3">
                                <small class="text-muted">Link pagamento SumUp:</small>
                                <a :href="quote.sumup_checkout_url" target="_blank" class="d-block">{{ quote.sumup_checkout_url }}</a>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Pannello stato Sent -->
                    <BCard v-if="isEditing && quoteStatus === 'sent'" no-body class="mb-3 border-info">
                        <BCardBody>
                            <div class="alert alert-info mb-0">
                                <i class="ri-mail-check-line me-2"></i>
                                <strong>Email inviata</strong>
                                <span v-if="quote.sent_at"> il {{ formatDate(quote.sent_at) }}</span>.
                                In attesa del pagamento dell'acconto.
                                <div v-if="quote.sumup_checkout_url" class="mt-2">
                                    <small>Link pagamento:</small>
                                    <a :href="quote.sumup_checkout_url" target="_blank" class="ms-1">{{ quote.sumup_checkout_url }}</a>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Pannello stato Deposit Received -->
                    <BCard v-if="isEditing && quoteStatus === 'deposit_received'" no-body class="mb-3 border-success">
                        <BCardBody>
                            <div class="alert alert-success mb-0">
                                <i class="ri-checkbox-circle-line me-2"></i>
                                <strong>Acconto ricevuto</strong>
                                <span v-if="quote.deposit_received_at"> il {{ formatDate(quote.deposit_received_at) }}</span>.
                                <div v-if="quote.service_id" class="mt-2">
                                    <Link :href="`/easyncc/services/${quote.service_id}/edit`" class="btn btn-sm btn-success">
                                        <i class="ri-external-link-line me-1"></i>Vai al Servizio
                                    </Link>
                                </div>
                            </div>
                        </BCardBody>
                    </BCard>

                    <!-- Errors & Actions -->
                    <div v-if="errors.length > 0" class="alert alert-danger">
                        <ul class="mb-0">
                            <li v-for="(error, idx) in errors" :key="idx">{{ error }}</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between sticky-bottom bg-white py-3 px-3 border-top" style="z-index: 10; margin: 0 -1rem;">
                        <Link href="/easyncc/quotes" class="btn btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i>Torna alla Lista
                        </Link>
                        <div class="d-flex gap-2">
                            <button v-if="!isReadOnly" type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="ri-save-line me-1"></i>
                                {{ isEditing ? 'Aggiorna Preventivo' : 'Salva Preventivo' }}
                            </button>
                            <button v-if="isEditing && quoteStatus === 'draft'" type="button" class="btn btn-success" @click="openApproveModal">
                                <i class="ri-check-double-line me-1"></i>Approva Preventivo
                            </button>
                        </div>
                    </div>
                </form>
            </BCol>
        </BRow>

        <!-- Modale Approvazione -->
        <BModal v-model="showApproveModal" title="Approva Preventivo" hide-footer size="lg">
            <div v-if="approveError" class="alert alert-danger">{{ approveError }}</div>

            <div class="mb-3">
                <label class="form-label">Email Cliente <span class="text-danger">*</span></label>
                <input v-model="approveForm.client_email" type="email" class="form-control" placeholder="email@esempio.it" />
            </div>

            <BRow>
                <BCol md="4" class="mb-3">
                    <label class="form-label">Merchant SumUp</label>
                    <select v-model="approveForm.sumup_config_id" class="form-select">
                        <option :value="null">Default azienda</option>
                        <option v-for="cfg in integrationOptions.sumup_configs" :key="cfg.id" :value="cfg.id">
                            {{ cfg.merchant_name }}
                        </option>
                    </select>
                </BCol>
                <BCol md="4" class="mb-3">
                    <label class="form-label">Account Gmail</label>
                    <select v-model="approveForm.gmail_account_id" class="form-select">
                        <option :value="null">Default azienda</option>
                        <option v-for="acc in integrationOptions.gmail_accounts" :key="acc.id" :value="acc.id">
                            {{ acc.account_label }} ({{ acc.email_address }})
                        </option>
                    </select>
                </BCol>
                <BCol md="4" class="mb-3">
                    <label class="form-label">Template Email</label>
                    <select v-model="approveForm.email_template_id" class="form-select" @change="loadPreview">
                        <option :value="null">Default azienda</option>
                        <option v-for="tpl in integrationOptions.email_templates" :key="tpl.id" :value="tpl.id">
                            {{ tpl.name }} {{ tpl.is_default ? '(Default)' : '' }}
                        </option>
                    </select>
                </BCol>
            </BRow>

            <!-- Email Preview -->
            <div v-if="emailPreview" class="border rounded p-3 bg-light">
                <h6 class="mb-2">Anteprima Email</h6>
                <div class="alert alert-info small py-2 mb-2">
                    <i class="ri-information-line me-1"></i>
                    Il link di pagamento SumUp verr&agrave; inserito automaticamente dopo la conferma dell'approvazione.
                </div>
                <div class="mb-2"><strong>Oggetto:</strong> {{ emailPreview.subject }}</div>
                <div class="border rounded p-2 bg-white" style="max-height: 300px; overflow-y: auto;" v-html="emailPreview.body"></div>
            </div>
            <div v-if="previewLoading" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <small class="ms-2">Caricamento anteprima...</small>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <BButton variant="light" @click="showApproveModal = false">Annulla</BButton>
                <BButton variant="success" @click="confirmApprove" :disabled="transitioning || !approveForm.client_email">
                    <span v-if="transitioning" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ri-check-double-line me-1"></i>
                    Conferma Approvazione
                </BButton>
            </div>
        </BModal>

        <!-- Pricing Calculator Modal -->
        <PricingCalculatorModal
            :show="showCalculatorModal"
            @update:show="showCalculatorModal = $event"
            :pricingDestinations="pricingDestinations"
            :pricingConfig="pricingConfig"
            :initialData="calculatorInitialData"
            :vatPercentage="form.vat_percentage"
            :cardFeesPercentage="form.card_fees_percentage"
            @apply="applyCalculatorResult"
        />

        <!-- Version Preview Modal -->
        <QuoteVersionPreviewModal
            :show="showPreviewModal"
            @update:show="showPreviewModal = $event"
            :versionData="previewData"
            :loading="previewLoading2"
            :restoring="restoringVersion"
            @restore="confirmRestoreVersion"
        />
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import { usePricingCalculator } from "@/composables/usePricingCalculator.js";
import { useQuoteWorkflow } from "@/composables/useQuoteWorkflow.js";
import { ClassicEditor, Essentials, Bold, Italic, Underline, Strikethrough, Font, Link as CKLink, List, BlockQuote, Table, TableToolbar, Heading, Paragraph, Undo, Alignment } from 'ckeditor5';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import 'ckeditor5/ckeditor5.css';

import QuoteWorkflowStepper from './components/QuoteWorkflowStepper.vue';
import PricingCalculatorModal from './components/PricingCalculatorModal.vue';
import ContactAutocomplete from './components/ContactAutocomplete.vue';
import QuoteVersionsSidebar from './components/QuoteVersionsSidebar.vue';
import QuoteVersionPreviewModal from './components/QuoteVersionPreviewModal.vue';
import { useServiceTypeColor } from '@/composables/useServiceTypeColor.js';

const { loadServiceTypes, serviceTypeBadgeClass } = useServiceTypeColor();

export default {
    components: { Head, Link, Layout, PageHeader, Ckeditor, QuoteWorkflowStepper, PricingCalculatorModal, ContactAutocomplete, QuoteVersionsSidebar, QuoteVersionPreviewModal },
    props: {
        quote: { type: Object, default: null },
        pricingDestinations: { type: Array, default: () => [] },
        pricingConfig: { type: Object, default: null },
        integrationOptions: {
            type: Object,
            default: () => ({ sumup_configs: [], gmail_accounts: [], email_templates: [], defaults: {} }),
        },
        versions: { type: Array, default: () => [] },
    },
    setup() {
        const {
            transitioning, transitionError, emailPreview, previewLoading,
            STEPS, getStatusLabel, getStatusColor, getStepIndex,
            executeTransition, loadEmailPreview,
        } = useQuoteWorkflow();

        return {
            transitioning, transitionError, emailPreview, previewLoading,
            STEPS, getStatusLabel, getStatusColor, getStepIndex,
            executeTransition, loadEmailPreview,
        };
    },
    data() {
        return {
            loading: true,
            saving: false,
            errors: [],
            successMessage: '',
            expandedItems: [true],
            // Versioning
            versionsList: [...(this.versions || [])],
            creatingVersion: false,
            restoringVersion: false,
            showPreviewModal: false,
            previewLoading2: false,
            previewData: null,
            // Calculator
            showCalculatorModal: false,
            calculatorItemIndex: null,
            calculatorInitialData: {},
            // Form
            form: {
                contact_id: null,
                client_name: '',
                client_email: '',
                service_date: '',
                notes: '',
                vat_percentage: 10,
                card_fees_percentage: 5,
                override_taxable: null,
                discount_percentage: 0,
                discount_name: '',
                deposit_percentage: 30,
                items: [this.createEmptyItem()],
            },
            result: {
                taxable_price: 0,
                taxable_price_rounded: 0,
                vat_amount: 0,
                cc_fees_amount: 0,
                final_price: 0,
                final_price_rounded: 0,
                discounted_taxable: 0,
                discount_amount: 0,
                deposit_percentage: 30,
                deposit_taxable: 0,
                deposit_handling_fees: 0,
                deposit_total: 0,
                balance_taxable: 0,
                balance_handling_fees: 0,
                balance_card_fees: 0,
            },
            // Workflow
            showApproveModal: false,
            approveError: '',
            approveForm: {
                client_email: '',
                sumup_config_id: null,
                gmail_account_id: null,
                email_template_id: null,
            },
            // Email editing (approved state)
            editableSubject: '',
            editableBodyHtml: '',
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
        isEditing() {
            return !!this.quote;
        },
        isReadOnly() {
            return this.isEditing && this.quote.status && this.quote.status !== 'draft';
        },
        pageTitle() {
            if (!this.isEditing) return 'Nuovo Preventivo';
            const version = this.quote.version ? ` - v${this.quote.version}` : '';
            return `Modifica Preventivo #${this.quote.id}${version}`;
        },
        quoteStatus() {
            return this.quote?.status || 'draft';
        },
        itemsTaxableSum() {
            return this.form.items.reduce((sum, item) => sum + (parseFloat(item.taxable_price) || 0), 0);
        },
    },
    async mounted() {
        if (!this.pricingConfig) {
            await this.loadSettings();
        }

        if (this.quote) {
            this.populateForm(this.quote);
            this.approveForm.client_email = this.quote.client_email || '';
            this.approveForm.sumup_config_id = this.integrationOptions?.defaults?.sumup_config_id || null;
            this.approveForm.gmail_account_id = this.integrationOptions?.defaults?.gmail_account_id || null;
            this.approveForm.email_template_id = this.integrationOptions?.defaults?.email_template_id || null;

            if (this.quote.status === 'approved') {
                this.editableSubject = this.quote.rendered_subject || '';
                this.editableBodyHtml = this.quote.rendered_body_html || '';
                this.$nextTick(() => { this.editorReady = true; });
            }
        }

        this.recalculate();
        this.loading = false;
        loadServiceTypes();
    },
    methods: {
        serviceTypeBadgeClass,
        // --- Contact ---
        onContactSelect(contact) {
            this.form.contact_id = contact.id;
            this.form.client_name = contact.name || '';
            this.form.client_email = contact.email || '';
        },
        // --- Item methods ---
        createEmptyItem() {
            return {
                pricing_destination_id: null,
                destination_name: '',
                service_type: 'TRF',
                mileage: 0,
                extra_km: 0,
                duration_hours: 0,
                extension_hours: 0,
                extra_travel_hours: 0,
                toll_cost: 0,
                pax_count: 0,
                experience_per_pax: 0,
                taxable_price: 0,
            };
        },
        addItem() {
            this.form.items.push(this.createEmptyItem());
            this.expandedItems.push(true);
            for (let i = 0; i < this.expandedItems.length - 1; i++) {
                this.expandedItems[i] = false;
            }
        },
        removeItem(index) {
            if (this.form.items.length <= 1) return;
            if (!confirm('Rimuovere questo servizio?')) return;
            this.form.items.splice(index, 1);
            this.expandedItems.splice(index, 1);
            this.recalculate();
        },
        toggleItem(index) {
            this.expandedItems[index] = !this.expandedItems[index];
        },
        onItemDestinationChange(index) {
            const item = this.form.items[index];
            if (!item.pricing_destination_id) return;
            const dest = this.pricingDestinations.find(d => d.id === item.pricing_destination_id);
            if (!dest) return;
            item.destination_name = dest.destination;
            item.service_type = dest.service_type;
            item.mileage = dest.mileage_km || 0;
            item.duration_hours = dest.duration_hours || 0;
            item.toll_cost = dest.toll_cost || 0;
            item.experience_per_pax = dest.experience_a || 0;
        },
        // --- Calculator ---
        openCalculatorModal(index) {
            this.calculatorItemIndex = index;
            this.calculatorInitialData = { ...this.form.items[index] };
            this.showCalculatorModal = true;
        },
        applyCalculatorResult(result) {
            const index = this.calculatorItemIndex;
            if (index === null || index === undefined) return;
            const item = this.form.items[index];
            Object.assign(item, result);
            this.recalculate();
        },
        // --- Pricing ---
        async loadSettings() {
            try {
                const response = await axios.get('/api/settings/public');
                const data = response.data;
                this.pricingConfig = {
                    pricing_markups: data.pricing_markups || {},
                    pricing_vehicle_costs: data.pricing_vehicle_costs || {},
                    pricing_vehicle_assumptions: data.pricing_vehicle_assumptions || {},
                    pricing_annual_expenses: data.pricing_annual_expenses || {},
                    pricing_season_service: data.pricing_season_service || {},
                    pricing_vehicle_service: data.pricing_vehicle_service || {},
                    pricing_season_experience: data.pricing_season_experience || {},
                    pricing_vehicle_experience: data.pricing_vehicle_experience || {},
                    pricing_attenuation_transport: data.pricing_attenuation_transport || {},
                    pricing_attenuation_driver: data.pricing_attenuation_driver || {},
                    pricing_extension: data.pricing_extension || {},
                    pricing_depreciation: data.pricing_depreciation || [0.22, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2],
                    pricing_toll: data.pricing_toll || {},
                };

                if (data.deposit_percentage) this.form.deposit_percentage = data.deposit_percentage;
                if (data.card_fees_percentage) this.form.card_fees_percentage = data.card_fees_percentage;
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        },
        populateForm(quote) {
            const clientFields = [
                'contact_id', 'client_name', 'client_email', 'service_date', 'notes',
                'vat_percentage', 'card_fees_percentage',
                'override_taxable', 'discount_percentage', 'discount_name',
                'deposit_percentage',
            ];
            clientFields.forEach(f => {
                if (quote[f] !== null && quote[f] !== undefined) {
                    if (f === 'service_date' && quote[f]) {
                        this.form[f] = quote[f].substring(0, 10);
                    } else {
                        this.form[f] = quote[f];
                    }
                }
            });

            if (this.form.vat_percentage) {
                this.form.vat_percentage = parseFloat(this.form.vat_percentage);
            }

            if (quote.items && quote.items.length > 0) {
                this.form.items = quote.items.map(item => ({
                    pricing_destination_id: item.pricing_destination_id,
                    destination_name: item.destination_name || '',
                    service_type: item.service_type || 'TRF',
                    mileage: parseFloat(item.mileage) || 0,
                    extra_km: parseFloat(item.extra_km) || 0,
                    duration_hours: parseFloat(item.duration_hours) || 0,
                    extension_hours: parseFloat(item.extension_hours) || 0,
                    extra_travel_hours: parseFloat(item.extra_travel_hours) || 0,
                    toll_cost: parseFloat(item.toll_cost) || 0,
                    pax_count: parseInt(item.pax_count) || 0,
                    experience_per_pax: parseFloat(item.experience_per_pax) || 0,
                    taxable_price: parseFloat(item.taxable_price) || 0,
                }));
                this.expandedItems = this.form.items.map((_, i) => i === 0);
            } else {
                this.form.items = [{
                    pricing_destination_id: null,
                    destination_name: quote.destination_name || '',
                    service_type: quote.service_type || 'TRF',
                    mileage: parseFloat(quote.mileage) || 0,
                    extra_km: parseFloat(quote.extra_km) || 0,
                    duration_hours: parseFloat(quote.duration_hours) || 0,
                    extension_hours: parseFloat(quote.extension_hours) || 0,
                    extra_travel_hours: parseFloat(quote.extra_travel_hours) || 0,
                    toll_cost: parseFloat(quote.toll_cost) || 0,
                    pax_count: parseInt(quote.pax_count) || 0,
                    experience_per_pax: parseFloat(quote.experience_per_pax) || 0,
                    taxable_price: parseFloat(quote.taxable_price_rounded) || 0,
                }];
                this.expandedItems = [true];
            }
        },
        recalculate() {
            const { calculateQuoteTotals } = usePricingCalculator();
            this.result = calculateQuoteTotals({
                items_taxable_sum: this.itemsTaxableSum,
                vat_percentage: this.form.vat_percentage,
                card_fees_percentage: this.form.card_fees_percentage,
                override_taxable: this.form.override_taxable,
                discount_percentage: this.form.discount_percentage,
                deposit_percentage: this.form.deposit_percentage,
            });
        },
        // --- Versioning ---
        async createNewVersion() {
            if (!confirm('Creare una nuova versione? La versione attuale verr\u00E0 archiviata.')) return;
            this.creatingVersion = true;
            try {
                const { data } = await axios.post(`/api/quotes/${this.quote.id}/create-version`);
                window.location.href = `/easyncc/quotes/${data.data.id}/edit`;
            } catch (e) {
                alert(e.response?.data?.message || 'Errore nella creazione della versione');
                this.creatingVersion = false;
            }
        },
        async previewVersion(ver) {
            this.showPreviewModal = true;
            this.previewLoading2 = true;
            this.previewData = null;
            try {
                const { data } = await axios.get(`/api/quotes/${ver.id}`);
                this.previewData = data.data;
            } catch (e) {
                alert('Errore nel caricamento dell\'anteprima');
                this.showPreviewModal = false;
            } finally {
                this.previewLoading2 = false;
            }
        },
        async confirmRestoreVersion(ver) {
            if (!confirm(`Ripristinare la versione v${ver.version}? La versione attuale verr\u00E0 archiviata e verr\u00E0 creata una nuova versione in bozza con i dati della v${ver.version}.`)) return;
            this.restoringVersion = true;
            try {
                const { data } = await axios.post(`/api/quotes/${ver.id}/restore-version`);
                this.showPreviewModal = false;
                window.location.href = `/easyncc/quotes/${data.data.id}/edit`;
            } catch (e) {
                alert(e.response?.data?.message || 'Errore nel ripristino della versione');
                this.restoringVersion = false;
            }
        },
        // --- Save ---
        async saveQuote() {
            this.saving = true;
            this.errors = [];

            if (this.form.discount_percentage > 0 && !this.form.discount_name) {
                this.errors = ['Il nome della promozione \u00E8 obbligatorio quando lo sconto \u00E8 maggiore di zero.'];
                this.saving = false;
                return;
            }

            try {
                const payload = {
                    contact_id: this.form.contact_id,
                    client_name: this.form.client_name,
                    client_email: this.form.client_email,
                    service_date: this.form.service_date,
                    notes: this.form.notes,
                    vat_percentage: this.form.vat_percentage,
                    card_fees_percentage: this.form.card_fees_percentage,
                    override_taxable: this.form.override_taxable,
                    discount_percentage: this.form.discount_percentage,
                    discount_name: this.form.discount_name,
                    deposit_percentage: this.form.deposit_percentage,
                    items: this.form.items,
                };

                if (this.isEditing) {
                    await axios.put(`/api/quotes/${this.quote.id}`, payload);
                } else {
                    await axios.post('/api/quotes', payload);
                }

                window.location.href = '/easyncc/quotes';
            } catch (error) {
                console.error('Error saving quote:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = [error.response?.data?.message || 'Errore durante il salvataggio'];
                }
            } finally {
                this.saving = false;
            }
        },
        // --- Workflow ---
        openApproveModal() {
            this.approveError = '';
            this.approveForm.client_email = this.form.client_email || '';
            this.showApproveModal = true;

            if (this.approveForm.email_template_id) {
                this.loadPreview();
            }
        },
        async loadPreview() {
            if (!this.approveForm.email_template_id) {
                this.emailPreview = null;
                return;
            }
            await this.loadEmailPreview(this.quote.id, this.approveForm.email_template_id);
        },
        async confirmApprove() {
            this.approveError = '';
            try {
                await this.executeTransition(this.quote.id, 'approve', {
                    client_email: this.approveForm.client_email,
                    sumup_config_id: this.approveForm.sumup_config_id,
                    gmail_account_id: this.approveForm.gmail_account_id,
                    email_template_id: this.approveForm.email_template_id,
                });
                this.showApproveModal = false;
                window.location.reload();
            } catch (error) {
                this.approveError = error.response?.data?.message || this.transitionError || 'Errore durante l\'approvazione';
            }
        },
        async sendEmail() {
            if (!confirm('Inviare l\'email al cliente?')) return;
            try {
                await this.executeTransition(this.quote.id, 'send', {
                    rendered_subject: this.editableSubject,
                    rendered_body_html: this.editableBodyHtml,
                });
                window.location.reload();
            } catch (error) {
                // transitionError is set by the composable
            }
        },
        async revertToDraft() {
            if (!confirm('Tornare allo stato bozza? Il checkout SumUp e la bozza Gmail verranno eliminati.')) return;
            try {
                await this.executeTransition(this.quote.id, 'revert_to_draft');
                window.location.reload();
            } catch (error) {
                // transitionError is set by the composable
            }
        },
        onEditorReady(editor) {
            this.editorInstance = editor;
        },
        // --- Utilities ---
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '\u20AC 0,00';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    },
};
</script>

<style>
/* CKEditor balloon panels must appear above Bootstrap modals (z-index 1055) */
.ck-body-wrapper .ck-balloon-panel {
    z-index: 1060 !important;
}
</style>
