<template>
    <div>
        <h6 class="card-subtitle mb-3 text-muted border-bottom pb-2">
            Dati Specifici {{ roleLabel }}
        </h6>

        <BRow>
            <!-- Is Committente -->
            <BCol md="6" class="mb-3">
                <div class="form-check form-switch">
                    <input
                        id="is_committente"
                        v-model="localProfile.is_committente"
                        type="checkbox"
                        class="form-check-input"
                    />
                    <label for="is_committente" class="form-check-label">
                        È Committente
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Il collaboratore può essere committente di servizi
                </small>
            </BCol>

            <!-- Is Fornitore -->
            <BCol md="6" class="mb-3">
                <div class="form-check form-switch">
                    <input
                        id="is_fornitore"
                        v-model="localProfile.is_fornitore"
                        type="checkbox"
                        class="form-check-input"
                    />
                    <label for="is_fornitore" class="form-check-label">
                        È Fornitore
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Il collaboratore può fornire servizi
                </small>
            </BCol>

            <!-- Business Name -->
            <BCol md="6" class="mb-3">
                <label for="business_name" class="form-label">Ragione Sociale</label>
                <input
                    id="business_name"
                    v-model="localProfile.business_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.business_name }"
                    placeholder="Azienda S.r.l."
                />
                <small v-if="errors.business_name" class="text-danger d-block mt-1">
                    {{ errors.business_name[0] }}
                </small>
            </BCol>

            <!-- Trade Name -->
            <BCol md="6" class="mb-3">
                <label for="trade_name" class="form-label">Denominazione (Alias)</label>
                <input
                    id="trade_name"
                    v-model="localProfile.trade_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.trade_name }"
                    placeholder="Nome commerciale"
                />
                <small v-if="errors.trade_name" class="text-danger d-block mt-1">
                    {{ errors.trade_name[0] }}
                </small>
            </BCol>

            <!-- VAT Number -->
            <BCol md="6" class="mb-3">
                <label for="vat_number" class="form-label">Partita IVA</label>
                <input
                    id="vat_number"
                    v-model="localProfile.vat_number"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.vat_number }"
                    placeholder="12345678901"
                />
                <small v-if="errors.vat_number" class="text-danger d-block mt-1">
                    {{ errors.vat_number[0] }}
                </small>
            </BCol>

            <!-- Fiscal Code -->
            <BCol md="6" class="mb-3">
                <label for="fiscal_code" class="form-label">Codice Fiscale</label>
                <input
                    id="fiscal_code"
                    v-model="localProfile.fiscal_code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.fiscal_code }"
                    placeholder="RSSMRA80A01H501X"
                />
                <small v-if="errors.fiscal_code" class="text-danger d-block mt-1">
                    {{ errors.fiscal_code[0] }}
                </small>
            </BCol>

            <!-- SDI -->
            <BCol md="6" class="mb-3">
                <label for="sdi" class="form-label">SDI</label>
                <input
                    id="sdi"
                    v-model="localProfile.sdi"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.sdi }"
                    placeholder="A1B2C3D"
                />
                <small v-if="errors.sdi" class="text-danger d-block mt-1">
                    {{ errors.sdi[0] }}
                </small>
            </BCol>

            <!-- PEC -->
            <BCol md="6" class="mb-3">
                <label for="pec" class="form-label">PEC</label>
                <input
                    id="pec"
                    v-model="localProfile.pec"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.pec }"
                    placeholder="pec@example.it"
                />
                <small v-if="errors.pec" class="text-danger d-block mt-1">
                    {{ errors.pec[0] }}
                </small>
            </BCol>

            <!-- Address -->
            <BCol md="12" class="mb-3">
                <label for="address" class="form-label">Indirizzo</label>
                <textarea
                    id="address"
                    v-model="localProfile.address"
                    class="form-control"
                    :class="{ 'is-invalid': errors.address }"
                    rows="2"
                    placeholder="Via Roma 123"
                ></textarea>
                <small v-if="errors.address" class="text-danger d-block mt-1">
                    {{ errors.address[0] }}
                </small>
            </BCol>

            <!-- Postal Code -->
            <BCol md="3" class="mb-3">
                <label for="postal_code" class="form-label">CAP</label>
                <input
                    id="postal_code"
                    v-model="localProfile.postal_code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.postal_code }"
                    placeholder="00100"
                />
                <small v-if="errors.postal_code" class="text-danger d-block mt-1">
                    {{ errors.postal_code[0] }}
                </small>
            </BCol>

            <!-- City -->
            <BCol md="4" class="mb-3">
                <label for="city" class="form-label">Comune</label>
                <input
                    id="city"
                    v-model="localProfile.city"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.city }"
                    placeholder="Roma"
                />
                <small v-if="errors.city" class="text-danger d-block mt-1">
                    {{ errors.city[0] }}
                </small>
            </BCol>

            <!-- Province -->
            <BCol md="2" class="mb-3">
                <label for="province" class="form-label">Provincia</label>
                <input
                    id="province"
                    v-model="localProfile.province"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.province }"
                    placeholder="RM"
                    maxlength="2"
                />
                <small v-if="errors.province" class="text-danger d-block mt-1">
                    {{ errors.province[0] }}
                </small>
            </BCol>

            <!-- Country -->
            <BCol md="3" class="mb-3">
                <label for="country" class="form-label">Nazione</label>
                <input
                    id="country"
                    v-model="localProfile.country"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.country }"
                    placeholder="Italia"
                />
                <small v-if="errors.country" class="text-danger d-block mt-1">
                    {{ errors.country[0] }}
                </small>
            </BCol>

            <!-- Admin Email -->
            <BCol md="6" class="mb-3">
                <label for="admin_email" class="form-label">Email Amministrativa</label>
                <input
                    id="admin_email"
                    v-model="localProfile.admin_email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.admin_email }"
                    placeholder="amministrazione@example.com"
                />
                <small v-if="errors.admin_email" class="text-danger d-block mt-1">
                    {{ errors.admin_email[0] }}
                </small>
            </BCol>

            <!-- Operational Email -->
            <BCol md="6" class="mb-3">
                <label for="operational_email" class="form-label">Email Operativa</label>
                <input
                    id="operational_email"
                    v-model="localProfile.operational_email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.operational_email }"
                    placeholder="operativo@example.com"
                />
                <small v-if="errors.operational_email" class="text-danger d-block mt-1">
                    {{ errors.operational_email[0] }}
                </small>
            </BCol>

            <!-- Phone -->
            <BCol md="6" class="mb-3">
                <label for="profile_phone" class="form-label">Telefono Aziendale</label>
                <input
                    id="profile_phone"
                    v-model="localProfile.phone"
                    type="tel"
                    class="form-control"
                    :class="{ 'is-invalid': errors.phone }"
                    placeholder="+39 06 1234567"
                />
                <small v-if="errors.phone" class="text-danger d-block mt-1">
                    {{ errors.phone[0] }}
                </small>
            </BCol>

            <!-- Website -->
            <BCol md="6" class="mb-3">
                <label for="website" class="form-label">Sito Web</label>
                <input
                    id="website"
                    v-model="localProfile.website"
                    type="url"
                    class="form-control"
                    :class="{ 'is-invalid': errors.website }"
                    placeholder="https://www.example.com"
                />
                <small v-if="errors.website" class="text-danger d-block mt-1">
                    {{ errors.website[0] }}
                </small>
            </BCol>
        </BRow>

        <!-- Business Contacts Section -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="card-subtitle mb-0 text-muted">Referenti Aziendali</h6>
                <button type="button" class="btn btn-sm btn-soft-primary" @click="addContact">
                    <i class="ri-add-line me-1"></i> Aggiungi Referente
                </button>
            </div>

            <div v-if="businessContacts.length === 0" class="text-muted text-center py-3">
                <small>Nessun referente aziendale. Clicca su "Aggiungi Referente" per aggiungerne uno.</small>
            </div>

            <div v-for="(contact, index) in businessContacts" :key="index" class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Referente #{{ index + 1 }}</strong>
                    <button type="button" class="btn btn-sm btn-soft-danger" @click="removeContact(index)">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
                <BRow>
                    <BCol md="4" class="mb-2">
                        <label :for="`contact_name_${index}`" class="form-label">Nome</label>
                        <input
                            :id="`contact_name_${index}`"
                            v-model="contact.name"
                            type="text"
                            class="form-control"
                            placeholder="Mario Rossi"
                        />
                    </BCol>
                    <BCol md="4" class="mb-2">
                        <label :for="`contact_phone_${index}`" class="form-label">Telefono</label>
                        <input
                            :id="`contact_phone_${index}`"
                            v-model="contact.phone"
                            type="tel"
                            class="form-control"
                            placeholder="+39 123 4567890"
                        />
                    </BCol>
                    <BCol md="4" class="mb-2">
                        <label :for="`contact_email_${index}`" class="form-label">Email</label>
                        <input
                            :id="`contact_email_${index}`"
                            v-model="contact.email"
                            type="email"
                            class="form-control"
                            placeholder="mario.rossi@example.com"
                        />
                    </BCol>
                </BRow>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    role: {
        type: String,
        required: true,
        validator: (value) => ['collaboratore'].includes(value)
    },
    modelValue: {
        type: Object,
        default: () => ({
            is_committente: false,
            is_fornitore: false,
            business_name: '',
            trade_name: '',
            vat_number: '',
            fiscal_code: '',
            sdi: '',
            pec: '',
            address: '',
            postal_code: '',
            city: '',
            province: '',
            country: '',
            admin_email: '',
            operational_email: '',
            phone: '',
            website: '',
            business_contacts: []
        })
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

// Initialize localProfile with proper defaults for boolean fields
const initializeProfile = (value) => {
    // If value is null or undefined, return a complete default object
    if (!value) {
        return {
            is_committente: false,
            is_fornitore: false,
            business_name: '',
            trade_name: '',
            vat_number: '',
            fiscal_code: '',
            sdi: '',
            pec: '',
            address: '',
            postal_code: '',
            city: '',
            province: '',
            country: '',
            admin_email: '',
            operational_email: '',
            phone: '',
            website: '',
            business_contacts: []
        };
    }

    return {
        ...value,
        is_committente: value.is_committente ?? false,
        is_fornitore: value.is_fornitore ?? false,
        business_contacts: value.business_contacts || []
    };
};

const localProfile = ref(initializeProfile(props.modelValue));
const businessContacts = ref(localProfile.value.business_contacts || []);

// Watch for changes in modelValue to sync data (only when prop reference changes, not nested properties)
watch(() => props.modelValue, (newValue, oldValue) => {
    // Only update if the prop reference actually changed from parent (not from our own emit)
    if (newValue !== oldValue) {
        const initialized = initializeProfile(newValue);
        localProfile.value = initialized;
        businessContacts.value = initialized.business_contacts || [];
    }
}, { immediate: true });

const roleLabel = computed(() => {
    const labels = {
        'collaboratore': 'Collaboratore'
    };
    return labels[props.role] || '';
});

const addContact = () => {
    businessContacts.value.push({
        name: '',
        phone: '',
        email: ''
    });
    // Directly update localProfile and emit
    localProfile.value.business_contacts = [...businessContacts.value];
};

const removeContact = (index) => {
    businessContacts.value.splice(index, 1);
    // Directly update localProfile and emit
    localProfile.value.business_contacts = [...businessContacts.value];
};

// Watch for changes in businessContacts (for when user types in contact fields)
watch(businessContacts, (newContacts) => {
    // Create a new array to trigger reactivity
    localProfile.value.business_contacts = [...newContacts];
}, { deep: true });

// Watch for changes and emit to parent - but exclude business_contacts changes
// since they're already handled above
let lastEmittedValue = null;
watch(localProfile, (newValue) => {
    // Prevent infinite loop by checking if value actually changed
    if (JSON.stringify(newValue) !== JSON.stringify(lastEmittedValue)) {
        lastEmittedValue = JSON.parse(JSON.stringify(newValue));
        emit('update:modelValue', newValue);
    }
}, { deep: true });
</script>
