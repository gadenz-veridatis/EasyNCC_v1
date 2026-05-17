<template>
    <BCard no-body class="mb-3">
        <BCardBody>
            <div class="d-flex justify-content-between align-items-center position-relative" style="padding: 0 2rem;">
                <div class="position-absolute" style="top: 50%; left: 2rem; right: 2rem; height: 2px; background: #e9ecef; z-index: 0;"></div>
                <div v-for="(step, idx) in steps" :key="step.key" class="text-center position-relative" style="z-index: 1;">
                    <div
                        class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                        :class="stepClass(idx)"
                        style="width: 40px; height: 40px;"
                    >
                        <i v-if="idx < currentStepIndex" class="ri-check-line"></i>
                        <span v-else>{{ idx + 1 }}</span>
                    </div>
                    <small :class="idx <= currentStepIndex ? 'fw-semibold' : 'text-muted'">{{ step.label }}</small>
                </div>
            </div>
        </BCardBody>
    </BCard>
</template>

<script>
const STEPS = [
    { key: 'draft', label: 'Bozza' },
    { key: 'approved', label: 'Approvato' },
    { key: 'sent', label: 'Inviato' },
    { key: 'deposit_received', label: 'Acconto Ricevuto' },
];

export default {
    props: {
        status: { type: String, default: 'draft' },
    },
    computed: {
        steps() {
            return STEPS;
        },
        currentStepIndex() {
            const idx = STEPS.findIndex(s => s.key === this.status);
            return idx >= 0 ? idx : 0;
        },
    },
    methods: {
        stepClass(idx) {
            if (idx < this.currentStepIndex) return 'bg-success text-white';
            if (idx === this.currentStepIndex) return 'bg-primary text-white';
            return 'bg-light text-muted border';
        },
    },
};
</script>
