<template>
    <div>
        <h6 class="card-subtitle mb-3 text-muted border-bottom pb-2">Dati Specifici Driver</h6>

        <BRow>
            <!-- Birth Date -->
            <BCol md="6" class="mb-3">
                <label for="birth_date" class="form-label">Data di Nascita</label>
                <input
                    id="birth_date"
                    v-model="localProfile.birth_date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.birth_date }"
                />
                <small v-if="errors.birth_date" class="text-danger d-block mt-1">
                    {{ errors.birth_date[0] }}
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

            <!-- Hourly Rate -->
            <BCol md="6" class="mb-3">
                <label for="hourly_rate" class="form-label">Tariffa Oraria (€)</label>
                <input
                    id="hourly_rate"
                    v-model.number="localProfile.hourly_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.hourly_rate }"
                    placeholder="25.00"
                />
                <small v-if="errors.hourly_rate" class="text-danger d-block mt-1">
                    {{ errors.hourly_rate[0] }}
                </small>
            </BCol>

            <!-- Bank Name -->
            <BCol md="6" class="mb-3">
                <label for="bank_name" class="form-label">Istituto Bancario</label>
                <input
                    id="bank_name"
                    v-model="localProfile.bank_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.bank_name }"
                    placeholder="Banca Intesa"
                />
                <small v-if="errors.bank_name" class="text-danger d-block mt-1">
                    {{ errors.bank_name[0] }}
                </small>
            </BCol>

            <!-- IBAN -->
            <BCol md="6" class="mb-3">
                <label for="iban" class="form-label">IBAN</label>
                <input
                    id="iban"
                    v-model="localProfile.iban"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.iban }"
                    placeholder="IT60 X054 2811 1010 0000 0123 456"
                />
                <small v-if="errors.iban" class="text-danger d-block mt-1">
                    {{ errors.iban[0] }}
                </small>
            </BCol>

            <!-- Assigned Vehicle -->
            <BCol md="6" class="mb-3">
                <label for="assigned_vehicle_id" class="form-label">Veicolo Assegnato</label>
                <select
                    id="assigned_vehicle_id"
                    v-model="localProfile.assigned_vehicle_id"
                    class="form-select"
                    :class="{ 'is-invalid': errors.assigned_vehicle_id }"
                >
                    <option value="">Nessun veicolo assegnato</option>
                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                        {{ vehicle.plate }} - {{ vehicle.brand }} {{ vehicle.model }}
                    </option>
                </select>
                <small v-if="errors.assigned_vehicle_id" class="text-danger d-block mt-1">
                    {{ errors.assigned_vehicle_id[0] }}
                </small>
            </BCol>

            <!-- Color Picker -->
            <BCol md="6" class="mb-3">
                <label for="color" class="form-label">Colore Identificativo</label>
                <input
                    id="color"
                    v-model="localProfile.color"
                    type="color"
                    class="form-control form-control-color"
                    :class="{ 'is-invalid': errors.color }"
                />
                <small class="text-muted">Colore usato nel calendario per identificare il driver</small>
                <small v-if="errors.color" class="text-danger d-block mt-1">
                    {{ errors.color[0] }}
                </small>
            </BCol>

            <!-- Allow Overlapping -->
            <BCol md="6" class="mb-3">
                <div class="form-check form-switch mt-4">
                    <input
                        id="allow_overlapping"
                        v-model="localProfile.allow_overlapping"
                        type="checkbox"
                        class="form-check-input"
                    />
                    <label for="allow_overlapping" class="form-check-label">
                        Permetti sovrapposizioni orari
                    </label>
                </div>
            </BCol>

            <!-- Profile Notes -->
            <BCol md="12" class="mb-3">
                <label for="profile_notes" class="form-label">Note Profilo Driver</label>
                <textarea
                    id="profile_notes"
                    v-model="localProfile.notes"
                    class="form-control"
                    :class="{ 'is-invalid': errors.notes }"
                    rows="3"
                    placeholder="Note specifiche per il profilo driver..."
                ></textarea>
                <small v-if="errors.notes" class="text-danger d-block mt-1">
                    {{ errors.notes[0] }}
                </small>
            </BCol>
        </BRow>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            birth_date: '',
            fiscal_code: '',
            vat_number: '',
            hourly_rate: null,
            bank_name: '',
            iban: '',
            assigned_vehicle_id: '',
            color: '#3788d8',
            allow_overlapping: false,
            notes: ''
        })
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const localProfile = ref({ ...props.modelValue });
const vehicles = ref([]);

// Load vehicles for the dropdown
const loadVehicles = async () => {
    try {
        const response = await axios.get('/api/vehicles', { params: { per_page: 1000 } });
        vehicles.value = response.data.data || [];
    } catch (err) {
        console.error('Error loading vehicles:', err);
        vehicles.value = [];
    }
};

// Watch for changes and emit to parent
watch(localProfile, (newValue) => {
    emit('update:modelValue', newValue);
}, { deep: true });

onMounted(() => {
    loadVehicles();
});
</script>
