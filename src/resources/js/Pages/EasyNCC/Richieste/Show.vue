<template>
    <Head title="Dettaglio Richiesta" />

    <Layout>
        <PageHeader title="Dettaglio Richiesta" pageTitle="Richieste" />

        <!-- Status banner -->
        <div v-if="richiesta" class="alert d-flex align-items-center mb-3" :class="statoAlertClass">
            <i class="ri-information-line me-2 fs-5"></i>
            <div>
                Stato: <strong>{{ statoLabel(richiesta.stato) }}</strong>
                <span v-if="richiesta.relazione" class="ms-2 text-muted">
                    ({{ richiesta.relazione === 'sdoppiata_da' ? 'Sdoppiata' : 'Unita' }})
                </span>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button
                    v-for="t in allowedTransitions"
                    :key="t"
                    class="btn btn-sm btn-light"
                    @click="doTransition(t)"
                >
                    → {{ statoLabel(t) }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>

        <BRow v-else-if="richiesta">
            <!-- LEFT COLUMN: Client + Comunicazioni con estrazioni inline -->
            <BCol lg="7">
                <!-- Client card -->
                <BCard class="mb-3">
                    <BCardHeader>
                        <h6 class="mb-0"><i class="ri-user-line me-2"></i>Cliente</h6>
                    </BCardHeader>
                    <BCardBody>
                        <BRow>
                            <BCol md="6">
                                <div class="fw-semibold fs-5">{{ richiesta.contact?.name || '-' }}</div>
                                <div class="text-muted">{{ richiesta.contact?.email || '' }}</div>
                                <div class="text-muted">{{ richiesta.contact?.phone || '' }}</div>
                            </BCol>
                            <BCol md="6" class="text-end">
                                <span class="badge" :class="fonteBadgeClass(richiesta.fonte)">
                                    <i :class="fonteIcon(richiesta.fonte)" class="me-1"></i>{{ fonteLabel(richiesta.fonte) }}
                                </span>
                                <div class="text-muted small mt-1">Ricevuta: {{ formatDateTime(richiesta.data_ricezione) }}</div>
                                <div v-if="richiesta.operatore" class="text-muted small">
                                    Operatore: {{ richiesta.operatore.name }} {{ richiesta.operatore.surname || '' }}
                                </div>
                            </BCol>
                        </BRow>
                    </BCardBody>
                </BCard>

                <!-- Comunicazioni con estrazioni inline -->
                <BCard no-body class="mb-3">
                    <BCardHeader>
                        <h6 class="mb-0"><i class="ri-mail-line me-2"></i>Comunicazioni ({{ threadEmails.length }})</h6>
                    </BCardHeader>
                    <BCardBody>
                        <div v-if="threadEmails.length > 0" style="max-height: 600px; overflow-y: auto;">
                            <div v-for="email in threadEmails" :key="email.id" class="mb-4">
                                <!-- Message bubble -->
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs rounded-circle d-flex align-items-center justify-content-center"
                                             :class="email.direzione === 'inbound' ? 'bg-primary-subtle' : 'bg-success-subtle'">
                                            <i :class="email.direzione === 'inbound' ? 'ri-mail-download-line text-primary' : 'ri-mail-send-line text-success'"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 border rounded p-3"
                                         :class="email.direzione === 'outbound' ? 'bg-light' : ''">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong class="small">{{ email.mittente }}</strong>
                                            <small class="text-muted">{{ formatDateTime(email.ricevuto_at) }}</small>
                                        </div>
                                        <div class="small fw-semibold mb-1">{{ email.subject }}</div>
                                        <div class="small" v-html="expandedEmails[email.id] ? getFullBody(email) : getStrippedBody(email)"></div>
                                        <div v-if="bodyWasStripped(email)" class="mt-1">
                                            <a href="#" class="small text-muted" @click.prevent="toggleExpandEmail(email.id)">
                                                <i :class="expandedEmails[email.id] ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="me-1"></i>
                                                {{ expandedEmails[email.id] ? 'Nascondi messaggi precedenti' : 'Mostra messaggio completo' }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Extracted rows inline (only for inbound with extractions) -->
                                <div v-if="email.direzione === 'inbound' && email.righe_estratte && email.righe_estratte.length > 0"
                                     class="ms-5 mt-2">
                                    <div class="small text-muted mb-1">
                                        <i class="ri-magic-line me-1"></i>Righe estratte da questo messaggio:
                                    </div>
                                    <div v-for="re in email.righe_estratte" :key="re.id"
                                         class="border rounded p-2 mb-1 bg-light small">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <span class="badge bg-secondary-subtle text-secondary me-1">{{ re.tipo_servizio }}</span>
                                                <span v-if="re.data_servizio" class="me-2">{{ formatDate(re.data_servizio) }}</span>
                                                <span v-if="re.ora_pickup" class="me-2">{{ re.ora_pickup }}</span>
                                                <span v-if="re.passeggeri" class="me-2">{{ re.passeggeri }} pax</span>
                                                <div class="mt-1">
                                                    <span v-if="re.pickup" class="text-muted">{{ re.pickup }}</span>
                                                    <span v-if="re.pickup && re.dropoff" class="text-muted"> → </span>
                                                    <span v-if="re.dropoff">{{ re.dropoff }}</span>
                                                    <span v-if="!re.pickup && !re.dropoff" class="text-muted fst-italic">Destinazione non specificata</span>
                                                </div>
                                                <div v-if="re.note" class="text-muted fst-italic mt-1">{{ re.note }}</div>
                                            </div>
                                            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                <button class="btn btn-xs btn-outline-success" @click="addRigaFromEstratta(re)"
                                                        title="Aggiungi al piano">
                                                    <i class="ri-add-line"></i>
                                                </button>
                                                <button v-if="findSimilarRiga(re)"
                                                        class="btn btn-xs btn-outline-warning"
                                                        @click="updateRigaFromEstratta(re, findSimilarRiga(re))"
                                                        :title="'Aggiorna riga #' + (righeRichiesta.indexOf(findSimilarRiga(re)) + 1)">
                                                    <i class="ri-refresh-line"></i> #{{ righeRichiesta.indexOf(findSimilarRiga(re)) + 1 }}
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Link to riga_richiesta if already added -->
                                        <div v-if="re.riga_richiesta_id" class="mt-1">
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ri-check-line me-1"></i>Aggiunta al piano
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-muted">
                            <i class="ri-mail-open-line fs-1"></i>
                            <p class="mt-2">Nessuna comunicazione</p>
                        </div>

                        <!-- Reply box -->
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex gap-2 mb-2">
                                <select v-model="replyLang" class="form-select form-select-sm" style="width: 100px;">
                                    <option value="it">IT</option>
                                    <option value="en">EN</option>
                                    <option value="de">DE</option>
                                    <option value="fr">FR</option>
                                    <option value="es">ES</option>
                                </select>
                                <button class="btn btn-sm btn-outline-secondary" @click="generateAiDraft" :disabled="aiDraftLoading">
                                    <span v-if="aiDraftLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="ri-magic-line me-1"></i>Bozza AI
                                </button>
                            </div>
                            <textarea v-model="replyText" class="form-control mb-2" rows="3" placeholder="Scrivi la tua risposta..."></textarea>
                            <div class="d-flex justify-content-between">
                                <select v-model="replyGmailAccountId" class="form-select form-select-sm" style="width: 250px;">
                                    <option :value="null">-- Seleziona casella --</option>
                                    <option v-for="a in gmailAccounts" :key="a.id" :value="a.id">
                                        {{ a.account_label }} ({{ a.email_address }})
                                    </option>
                                </select>
                                <button class="btn btn-sm btn-primary" :disabled="!replyText || !replyGmailAccountId" @click="sendReply">
                                    <i class="ri-send-plane-line me-1"></i>Invia
                                </button>
                            </div>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>

            <!-- RIGHT COLUMN: Piano righe + Preventivo + Azioni -->
            <BCol lg="5">
                <!-- Piano righe richiesta -->
                <BCard class="mb-3">
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="ri-list-check me-2"></i>Piano Richiesta ({{ righeRichiesta.length }})</h6>
                    </BCardHeader>
                    <BCardBody>
                        <div v-if="righeRichiesta.length > 0">
                            <div v-for="(riga, idx) in righeRichiesta" :key="riga.id"
                                 class="border rounded p-2 mb-2"
                                 :class="{ 'border-warning': hasNullFields(riga) }">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 small">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge bg-dark text-white">#{{ idx + 1 }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary">{{ riga.tipo_servizio }}</span>
                                            <span v-if="riga.modificata_manualmente" class="badge bg-info-subtle text-info">manuale</span>
                                        </div>
                                        <div>
                                            <strong v-if="riga.data_servizio">{{ formatDate(riga.data_servizio) }}</strong>
                                            <span v-else class="text-warning fst-italic">Data da definire</span>
                                            <span v-if="riga.ora_pickup" class="ms-1">{{ riga.ora_pickup }}</span>
                                            <span v-if="riga.passeggeri" class="ms-2 text-muted">{{ riga.passeggeri }} pax</span>
                                        </div>
                                        <div class="mt-1">
                                            <span v-if="riga.pickup">{{ riga.pickup }}</span>
                                            <span v-else class="text-warning fst-italic">Pickup da definire</span>
                                            <span class="text-muted"> → </span>
                                            <span v-if="riga.dropoff">{{ riga.dropoff }}</span>
                                            <span v-else class="text-warning fst-italic">Dropoff da definire</span>
                                        </div>
                                        <div v-if="riga.note" class="text-muted fst-italic mt-1">{{ riga.note }}</div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                        <button class="btn btn-xs btn-outline-primary" @click="openEditRigaModal(riga)" title="Modifica">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-danger" @click="removeRiga(riga)" title="Rimuovi">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-3 text-muted small">
                            <p class="mb-1">Nessuna riga nel piano</p>
                            <p class="mb-0">Aggiungi righe dai messaggi o manualmente</p>
                        </div>

                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary flex-grow-1" @click="showAddManualModal = true">
                                <i class="ri-add-line me-1"></i>Aggiungi manuale
                            </button>
                            <!-- Quote action button: adapts based on state -->
                            <template v-if="righeRichiesta.length > 0">
                                <!-- No quote yet → Create -->
                                <button v-if="!activeQuote"
                                        class="btn btn-sm btn-success flex-grow-1"
                                        @click="createQuoteFromRichiesta">
                                    <i class="ri-file-list-3-line me-1"></i>Crea Preventivo
                                </button>
                                <!-- Quote in draft → Edit -->
                                <Link v-else-if="activeQuote.status === 'draft'"
                                      :href="`/easyncc/quotes/${activeQuote.id}/edit`"
                                      class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="ri-edit-line me-1"></i>Modifica Preventivo
                                </Link>
                                <!-- Quote approved/sent/scaduto → New version -->
                                <button v-else
                                        class="btn btn-sm btn-warning flex-grow-1"
                                        @click="showNewVersionModal = true">
                                    <i class="ri-file-copy-line me-1"></i>Nuova Versione
                                </button>
                            </template>
                        </div>
                    </BCardBody>
                </BCard>

                <!-- Preventivo panel -->
                <BCard v-if="activeQuote" class="mb-3">
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="ri-file-list-3-line me-2"></i>Preventivo</h6>
                        <Link :href="`/easyncc/quotes/${activeQuote.id}/edit`" class="btn btn-sm btn-soft-primary">
                            <i class="ri-edit-line me-1"></i>Modifica
                        </Link>
                    </BCardHeader>
                    <BCardBody>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge" :class="quoteStatusBadge(activeQuote.status)">
                                {{ quoteStatusLabel(activeQuote.status) }}
                            </span>
                            <small class="text-muted">v{{ activeQuote.version }}</small>
                        </div>
                        <div v-if="activeQuote.items && activeQuote.items.length > 0">
                            <div v-for="item in activeQuote.items" :key="item.id" class="border rounded p-2 mb-1 small">
                                <div class="fw-semibold">{{ item.destination_name || '-' }}</div>
                                <div class="d-flex justify-content-between text-muted">
                                    <span>{{ item.service_type || '-' }} · {{ item.pax_count || 0 }} pax</span>
                                    <span class="fw-semibold">{{ formatCurrency(item.taxable_price) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="border-top pt-2 mt-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Totale</span>
                                <span>{{ formatCurrency(activeQuote.final_price_rounded) }}</span>
                            </div>
                        </div>
                        <div v-if="activeQuote.scadenza" class="mt-2 small text-muted">
                            <i class="ri-calendar-event-line me-1"></i>Scadenza: {{ formatDate(activeQuote.scadenza) }}
                        </div>
                    </BCardBody>
                </BCard>

                <!-- Actions card -->
                <BCard class="mb-3">
                    <BCardHeader>
                        <h6 class="mb-0"><i class="ri-tools-line me-2"></i>Azioni</h6>
                    </BCardHeader>
                    <BCardBody>
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-warning" @click="showSplitModal = true"
                                    :disabled="richiesta.stato === 'confermata' || righeRichiesta.length < 2">
                                <i class="ri-scissors-line me-1"></i>Dividi Richiesta
                            </button>
                            <button class="btn btn-sm btn-outline-info" @click="showMergeModal = true"
                                    :disabled="richiesta.stato === 'confermata'">
                                <i class="ri-merge-cells-horizontal me-1"></i>Unisci con altra Richiesta
                            </button>
                        </div>
                    </BCardBody>
                </BCard>

                <Link href="/easyncc/richieste" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Torna alla lista
                </Link>
            </BCol>
        </BRow>

        <!-- Edit riga modal -->
        <BModal v-model="showEditRigaModal" title="Modifica Riga" hide-footer size="md">
            <BRow v-if="editingRiga">
                <BCol md="6" class="mb-3">
                    <label class="form-label">Data Servizio</label>
                    <input v-model="editingRiga.data_servizio" type="date" class="form-control" />
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Ora Pickup</label>
                    <input v-model="editingRiga.ora_pickup" type="time" class="form-control" />
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Tipo Servizio</label>
                    <select v-model="editingRiga.tipo_servizio" class="form-select">
                        <option value="trasferimento">Trasferimento</option>
                        <option value="tour">Tour</option>
                        <option value="esperienza">Esperienza</option>
                        <option value="altro">Altro</option>
                    </select>
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Passeggeri</label>
                    <input v-model.number="editingRiga.passeggeri" type="number" min="1" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Pickup</label>
                    <input v-model="editingRiga.pickup" type="text" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Dropoff</label>
                    <input v-model="editingRiga.dropoff" type="text" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea v-model="editingRiga.note" class="form-control" rows="2"></textarea>
                </BCol>
            </BRow>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-light" @click="showEditRigaModal = false">Annulla</button>
                <button class="btn btn-primary" @click="saveEditRiga">Salva</button>
            </div>
        </BModal>

        <!-- Add manual riga modal -->
        <BModal v-model="showAddManualModal" title="Aggiungi Riga Manuale" hide-footer size="md">
            <BRow>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Data Servizio</label>
                    <input v-model="manualRiga.data_servizio" type="date" class="form-control" />
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Ora Pickup</label>
                    <input v-model="manualRiga.ora_pickup" type="time" class="form-control" />
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Tipo Servizio <span class="text-danger">*</span></label>
                    <select v-model="manualRiga.tipo_servizio" class="form-select">
                        <option value="trasferimento">Trasferimento</option>
                        <option value="tour">Tour</option>
                        <option value="esperienza">Esperienza</option>
                        <option value="altro">Altro</option>
                    </select>
                </BCol>
                <BCol md="6" class="mb-3">
                    <label class="form-label">Passeggeri</label>
                    <input v-model.number="manualRiga.passeggeri" type="number" min="1" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Pickup</label>
                    <input v-model="manualRiga.pickup" type="text" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Dropoff</label>
                    <input v-model="manualRiga.dropoff" type="text" class="form-control" />
                </BCol>
                <BCol md="12" class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea v-model="manualRiga.note" class="form-control" rows="2"></textarea>
                </BCol>
            </BRow>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-light" @click="showAddManualModal = false">Annulla</button>
                <button class="btn btn-primary" @click="saveManualRiga">Aggiungi</button>
            </div>
        </BModal>

        <!-- New version confirmation modal -->
        <BModal v-model="showNewVersionModal" title="Nuova Versione Preventivo" hide-footer size="md">
            <div class="alert alert-warning small">
                <i class="ri-alert-line me-1"></i>
                La versione corrente del preventivo (v{{ activeQuote?.version }}, stato: {{ quoteStatusLabel(activeQuote?.status) }}) verrà archiviata.
                Verrà creata una nuova versione in bozza con le righe aggiornate dal piano richiesta.
            </div>
            <p class="small text-muted">
                La nuova versione includerà {{ righeRichiesta.length }} righe dal piano corrente.
                I prezzi dovranno essere compilati nuovamente.
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-light" @click="showNewVersionModal = false">Annulla</button>
                <button class="btn btn-warning" @click="createNewVersion" :disabled="creatingVersion">
                    <span v-if="creatingVersion" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="ri-file-copy-line me-1"></i>
                    Conferma Nuova Versione
                </button>
            </div>
        </BModal>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import moment from "moment";
import { useNotify } from '@/composables/useNotify.js';

const notify = useNotify();

const STATO_LABELS = {
    nuova: 'Nuova', in_lavorazione: 'In Lavorazione', preventivata: 'Preventivata',
    confermata: 'Confermata', annullata: 'Annullata', sospesa: 'Sospesa',
};
const STATO_ALERT = {
    nuova: 'alert-info', in_lavorazione: 'alert-warning', preventivata: 'alert-primary',
    confermata: 'alert-success', annullata: 'alert-danger', sospesa: 'alert-secondary',
};
const FONTE_LABELS = { email: 'Email', web_form: 'Web Form', telefono: 'Telefono', manuale: 'Manuale' };
const FONTE_ICONS = { email: 'ri-mail-line', web_form: 'ri-global-line', telefono: 'ri-phone-line', manuale: 'ri-edit-line' };
const FONTE_BADGE = { email: 'bg-primary-subtle text-primary', web_form: 'bg-info-subtle text-info', telefono: 'bg-success-subtle text-success', manuale: 'bg-secondary-subtle text-secondary' };
const QUOTE_STATUS_LABELS = {
    draft: 'Bozza', in_approvazione: 'In Approvazione', approved: 'Approvato',
    sent: 'Inviato', deposit_received: 'Acconto Ricevuto', scaduto: 'Scaduto',
};
const QUOTE_STATUS_BADGE = {
    draft: 'bg-secondary-subtle text-secondary', in_approvazione: 'bg-warning-subtle text-warning',
    approved: 'bg-primary-subtle text-primary', sent: 'bg-info-subtle text-info',
    deposit_received: 'bg-success-subtle text-success', scaduto: 'bg-danger-subtle text-danger',
};

export default {
    components: { Head, Link, Layout, PageHeader },
    data() {
        return {
            loading: true,
            richiesta: null,
            activeQuote: null,
            righeRichiesta: [],
            threadEmails: [],
            allowedTransitions: [],
            // Email display
            expandedEmails: {},
            // Reply
            replyText: '',
            replyLang: 'it',
            replyGmailAccountId: null,
            gmailAccounts: [],
            aiDraftLoading: false,
            // Modals
            showSplitModal: false,
            showMergeModal: false,
            showNewVersionModal: false,
            creatingVersion: false,
            showEditRigaModal: false,
            showAddManualModal: false,
            editingRiga: null,
            editingRigaId: null,
            manualRiga: { tipo_servizio: 'trasferimento', data_servizio: '', ora_pickup: '', pickup: '', dropoff: '', passeggeri: null, note: '' },
        };
    },
    computed: {
        statoAlertClass() { return STATO_ALERT[this.richiesta?.stato] || 'alert-secondary'; },
    },
    async mounted() {
        const id = window.location.pathname.split('/').pop();
        await this.loadRichiesta(id);
        await Promise.all([
            this.loadThreadEmails(),
            this.loadRigheRichiesta(),
            this.loadGmailAccounts(),
        ]);
        if (this.richiesta?.stato === 'nuova') {
            await this.autoTakeCharge();
        }
        if (this.richiesta?.has_unread) {
            await axios.post(`/api/richieste/${this.richiesta.id}/mark-read`).catch(() => {});
        }
    },
    methods: {
        async loadRichiesta(id) {
            this.loading = true;
            try {
                const res = await axios.get(`/api/richieste/${id}`);
                this.richiesta = res.data.data;
                this.activeQuote = this.richiesta.quotes?.find(q => q.is_active_version) || null;
                const transRes = await axios.get(`/api/richieste/${id}/transitions`);
                this.allowedTransitions = transRes.data.transizioni_possibili || [];
            } catch (error) {
                console.error('Error loading richiesta:', error);
            } finally {
                this.loading = false;
            }
        },
        async loadRigheRichiesta() {
            if (!this.richiesta) return;
            // righe_richiesta are loaded with the richiesta
            this.righeRichiesta = this.richiesta.righe || [];
        },
        async refreshRigheRichiesta() {
            const res = await axios.get(`/api/richieste/${this.richiesta.id}`);
            this.righeRichiesta = res.data.data.righe || [];
        },
        async autoTakeCharge() {
            try {
                const res = await axios.post(`/api/richieste/${this.richiesta.id}/take-charge`);
                if (res.data.success) {
                    this.richiesta.stato = 'in_lavorazione';
                    this.richiesta.operatore = res.data.data.operatore;
                    const transRes = await axios.get(`/api/richieste/${this.richiesta.id}/transitions`);
                    this.allowedTransitions = transRes.data.transizioni_possibili || [];
                }
            } catch (e) { /* */ }
        },
        async loadThreadEmails() {
            if (!this.richiesta) return;
            try {
                const res = await axios.get(`/api/richieste/${this.richiesta.id}/thread-emails`);
                this.threadEmails = res.data.data || [];
            } catch (e) { console.error(e); }
        },
        async loadGmailAccounts() {
            try {
                const res = await axios.get('/api/gmail-accounts');
                this.gmailAccounts = res.data.data || [];
                if (this.gmailAccounts.length > 0) this.replyGmailAccountId = this.gmailAccounts[0].id;
            } catch (e) { /* */ }
        },
        // --- Riga actions ---
        async addRigaFromEstratta(rigaEstratta) {
            try {
                await axios.post(`/api/richieste/${this.richiesta.id}/add-riga-from-estratta`, {
                    riga_estratta_id: rigaEstratta.id,
                });
                await this.refreshRigheRichiesta();
                await this.loadThreadEmails(); // refresh to show "Aggiunta al piano" badge
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            }
        },
        async updateRigaFromEstratta(rigaEstratta, rigaRichiesta) {
            try {
                await axios.post(`/api/richieste/${this.richiesta.id}/update-riga-from-estratta`, {
                    riga_estratta_id: rigaEstratta.id,
                    riga_richiesta_id: rigaRichiesta.id,
                });
                await this.refreshRigheRichiesta();
                await this.loadThreadEmails();
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            }
        },
        async removeRiga(riga) {
            const confirmed = await notify.confirm('Rimuovi Riga', 'Rimuovere questa riga dal piano?');
            if (!confirmed) return;
            try {
                await axios.delete(`/api/richieste/${this.richiesta.id}/righe/${riga.id}`);
                notify.success('Riga rimossa');
                await this.refreshRigheRichiesta();
                await this.loadThreadEmails();
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            }
        },
        openEditRigaModal(riga) {
            this.editingRigaId = riga.id;
            this.editingRiga = {
                data_servizio: riga.data_servizio ? moment(riga.data_servizio).format('YYYY-MM-DD') : '',
                ora_pickup: riga.ora_pickup || '',
                tipo_servizio: riga.tipo_servizio,
                pickup: riga.pickup || '',
                dropoff: riga.dropoff || '',
                passeggeri: riga.passeggeri,
                note: riga.note || '',
            };
            this.showEditRigaModal = true;
        },
        async saveEditRiga() {
            try {
                await axios.put(`/api/richieste/${this.richiesta.id}/righe/${this.editingRigaId}`, this.editingRiga);
                this.showEditRigaModal = false;
                await this.refreshRigheRichiesta();
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            }
        },
        async saveManualRiga() {
            try {
                await axios.post(`/api/richieste/${this.richiesta.id}/add-riga-manuale`, this.manualRiga);
                this.showAddManualModal = false;
                this.manualRiga = { tipo_servizio: 'trasferimento', data_servizio: '', ora_pickup: '', pickup: '', dropoff: '', passeggeri: null, note: '' };
                await this.refreshRigheRichiesta();
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            }
        },
        // --- Matching: find similar riga in piano ---
        findSimilarRiga(rigaEstratta) {
            if (this.righeRichiesta.length === 0) return null;
            for (const rr of this.righeRichiesta) {
                let matchScore = 0;
                // Match by date
                if (rigaEstratta.data_servizio && rr.data_servizio &&
                    moment(rigaEstratta.data_servizio).format('YYYY-MM-DD') === moment(rr.data_servizio).format('YYYY-MM-DD')) {
                    matchScore++;
                }
                // Match by tipo_servizio
                if (rigaEstratta.tipo_servizio && rr.tipo_servizio &&
                    rigaEstratta.tipo_servizio === rr.tipo_servizio) {
                    matchScore++;
                }
                // Match by destination (dropoff)
                if (rigaEstratta.dropoff && rr.dropoff &&
                    rigaEstratta.dropoff.toLowerCase().includes(rr.dropoff.toLowerCase().substring(0, 5))) {
                    matchScore++;
                }
                if (matchScore >= 2) return rr;
            }
            return null;
        },
        // --- Quote ---
        async createNewVersion() {
            this.creatingVersion = true;
            try {
                // Create new version via backend — archives current, creates new from righe_richiesta
                const res = await axios.post(`/api/quotes/from-richiesta/${this.richiesta.id}`, {
                    archive_current: true,
                });
                if (res.data.success) {
                    this.showNewVersionModal = false;
                    this.$inertia.visit(`/easyncc/quotes/${res.data.data.id}/edit`);
                }
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nella creazione della nuova versione');
            } finally {
                this.creatingVersion = false;
            }
        },
        async createQuoteFromRichiesta() {
            try {
                const res = await axios.post(`/api/quotes/from-richiesta/${this.richiesta.id}`);
                if (res.data.success) {
                    this.$inertia.visit(`/easyncc/quotes/${res.data.data.id}/edit`);
                }
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nella creazione del preventivo');
            }
        },
        // --- Transitions ---
        async doTransition(newStato) {
            const confirmed = await notify.confirm('Conferma Transizione', `Confermi la transizione a "<strong>${this.statoLabel(newStato)}</strong>"?`);
            if (!confirmed) return;
            try {
                await axios.post(`/api/richieste/${this.richiesta.id}/transition`, { stato: newStato });
                notify.success(`Stato aggiornato a "${this.statoLabel(newStato)}"`);
                await this.loadRichiesta(this.richiesta.id);
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nella transizione');
            }
        },
        // --- Reply ---
        async sendReply() {
            const lastInbound = [...this.threadEmails].reverse().find(e => e.direzione === 'inbound');
            try {
                await axios.post(`/api/richieste/${this.richiesta.id}/reply`, {
                    gmail_account_id: this.replyGmailAccountId,
                    to: this.richiesta.contact?.email,
                    subject: lastInbound ? `Re: ${lastInbound.subject}` : `Richiesta`,
                    body_html: this.replyText.replace(/\n/g, '<br>'),
                    in_reply_to_rfc: lastInbound?.message_id_rfc || null,
                    thread_id_gmail: lastInbound?.thread_id_gmail || null,
                });
                this.replyText = '';
                await this.loadThreadEmails();
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore nell\'invio');
            }
        },
        async generateAiDraft() {
            this.aiDraftLoading = true;
            try {
                const res = await axios.post(`/api/richieste/${this.richiesta.id}/draft-ai`, { lingua: this.replyLang });
                if (res.data.success) this.replyText = res.data.data.draft;
            } catch (error) {
                notify.error(error.response?.data?.message || 'Errore');
            } finally {
                this.aiDraftLoading = false;
            }
        },
        // --- Email body stripping ---
        getFullBody(email) {
            if (email.body_html) return email.body_html;
            return (email.body_text || '').replace(/\n/g, '<br>');
        },
        getStrippedBody(email) {
            const raw = email.body_text || this.stripHtmlToText(email.body_html || '');
            const stripped = this.stripQuotedText(raw);
            return stripped.replace(/\n/g, '<br>');
        },
        bodyWasStripped(email) {
            const raw = email.body_text || this.stripHtmlToText(email.body_html || '');
            const stripped = this.stripQuotedText(raw);
            return stripped.length < raw.length - 10;
        },
        stripQuotedText(text) {
            if (!text) return '';
            const lines = text.split('\n');
            let cutIndex = lines.length;

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();

                // "On ... wrote:" / "Il giorno ... ha scritto:" / "Am ... schrieb:" / "Le ... a écrit:"
                if (/^(On |Il giorno |Am |Le ).+(wrote:|ha scritto:|schrieb:|a écrit:)\s*$/i.test(line)) {
                    cutIndex = i;
                    break;
                }
                // "> " quoted lines — cut at first block of 2+ consecutive quoted lines
                if (i < lines.length - 1 && line.startsWith('>') && lines[i + 1]?.trim().startsWith('>')) {
                    // Look back one line for the "wrote:" header
                    cutIndex = i > 0 && /wrote:|scritto:|schrieb:|écrit:/i.test(lines[i - 1]) ? i - 1 : i;
                    break;
                }
                // "---------- Forwarded message ----------"
                if (/^-{5,}\s*(Forwarded|Messaggio inoltrato)/i.test(line)) {
                    cutIndex = i;
                    break;
                }
                // "From: ... " block after "---" separator
                if (/^-{3,}$/.test(line) && i + 1 < lines.length && /^(From|Da|Von|De)\s*:/i.test(lines[i + 1]?.trim())) {
                    cutIndex = i;
                    break;
                }
            }

            return lines.slice(0, cutIndex).join('\n').trim();
        },
        stripHtmlToText(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        },
        toggleExpandEmail(emailId) {
            this.expandedEmails = { ...this.expandedEmails, [emailId]: !this.expandedEmails[emailId] };
        },
        // --- Helpers ---
        formatDate(date) { return date ? moment(date).format('DD/MM/YYYY') : '-'; },
        formatDateTime(date) { return date ? moment(date).format('DD/MM/YYYY HH:mm') : '-'; },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '-';
            return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
        },
        statoLabel(stato) { return STATO_LABELS[stato] || stato; },
        fonteLabel(fonte) { return FONTE_LABELS[fonte] || fonte; },
        fonteIcon(fonte) { return FONTE_ICONS[fonte] || 'ri-question-line'; },
        fonteBadgeClass(fonte) { return FONTE_BADGE[fonte] || 'bg-secondary-subtle text-secondary'; },
        quoteStatusLabel(s) { return QUOTE_STATUS_LABELS[s] || s; },
        quoteStatusBadge(s) { return QUOTE_STATUS_BADGE[s] || 'bg-secondary'; },
        hasNullFields(riga) {
            return !riga.data_servizio || !riga.pickup || !riga.dropoff;
        },
    },
};
</script>

<style scoped>
.btn-xs {
    padding: 0.15rem 0.35rem;
    font-size: 0.75rem;
    line-height: 1.2;
}
.avatar-xs {
    width: 28px;
    height: 28px;
}
</style>
