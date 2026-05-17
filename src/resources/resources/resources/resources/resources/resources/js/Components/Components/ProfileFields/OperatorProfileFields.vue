<template>
    <div>
        <h6 class="card-subtitle mb-3 text-muted border-bottom pb-2">Dati Specifici Operatore</h6>

        <BRow>
            <!-- Is Contabilità -->
            <BCol md="6" class="mb-3">
                <div class="form-check form-switch mt-4">
                    <input
                        id="is_contabilita"
                        v-model="localProfile.is_contabilita"
                        type="checkbox"
                        class="form-check-input"
                    />
                    <label for="is_contabilita" class="form-check-label">
                        Ha accesso alla contabilità
                    </label>
                </div>
                <small class="text-muted">Se attivo, l'operatore può accedere alle funzionalità di contabilità</small>
            </BCol>
        </BRow>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            is_contabilita: false
        })
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const localProfile = ref({ ...props.modelValue });

// Watch for changes and emit to parent
watch(localProfile, (newValue) => {
    emit('update:modelValue', newValue);
}, { deep: true });
</script>
