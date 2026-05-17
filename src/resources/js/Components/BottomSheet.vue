<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition name="bottomsheet-backdrop">
            <div
                v-if="modelValue"
                class="bottomsheet-backdrop"
                @click="$emit('update:modelValue', false)"
            ></div>
        </Transition>
        <!-- Sheet -->
        <Transition name="bottomsheet">
            <div
                v-if="modelValue"
                ref="sheetEl"
                class="bottomsheet"
                :class="{ 'bottomsheet-fullheight': fullHeight }"
                @click.stop
            >
                <!-- Drag handle -->
                <div class="bottomsheet-handle" @touchstart="onDragStart" @touchmove="onDragMove" @touchend="onDragEnd">
                    <div class="bottomsheet-handle-bar"></div>
                </div>
                <!-- Header -->
                <div v-if="title || $slots.header" class="bottomsheet-header">
                    <slot name="header">
                        <h6 class="bottomsheet-title mb-0">{{ title }}</h6>
                    </slot>
                    <button type="button" class="btn-close btn-close-sm" @click="$emit('update:modelValue', false)"></button>
                </div>
                <!-- Body -->
                <div class="bottomsheet-body" :style="bodyStyle">
                    <slot></slot>
                </div>
                <!-- Footer -->
                <div v-if="$slots.footer" class="bottomsheet-footer">
                    <slot name="footer"></slot>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    fullHeight: { type: Boolean, default: false },
    maxHeight: { type: String, default: '85vh' },
});

const emit = defineEmits(['update:modelValue']);

const sheetEl = ref(null);
let dragStartY = 0;
let dragCurrentY = 0;
let isDragging = false;

const bodyStyle = computed(() => ({
    maxHeight: props.fullHeight ? 'none' : `calc(${props.maxHeight} - 100px)`,
    overflowY: 'auto',
}));

const onDragStart = (e) => {
    isDragging = true;
    dragStartY = e.touches[0].clientY;
    dragCurrentY = 0;
    if (sheetEl.value) {
        sheetEl.value.style.transition = 'none';
    }
};

const onDragMove = (e) => {
    if (!isDragging) return;
    dragCurrentY = e.touches[0].clientY - dragStartY;
    // Only allow dragging down
    if (dragCurrentY > 0 && sheetEl.value) {
        sheetEl.value.style.transform = `translateY(${dragCurrentY}px)`;
    }
};

const onDragEnd = () => {
    if (!isDragging) return;
    isDragging = false;
    if (sheetEl.value) {
        sheetEl.value.style.transition = '';
        sheetEl.value.style.transform = '';
    }
    // Close if dragged more than 100px down
    if (dragCurrentY > 100) {
        emit('update:modelValue', false);
    }
    dragCurrentY = 0;
};
</script>

<style scoped>
.bottomsheet-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10500;
}

.bottomsheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-radius: 16px 16px 0 0;
    z-index: 10501;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
}

.bottomsheet-fullheight {
    max-height: 95vh;
}

.bottomsheet-handle {
    display: flex;
    justify-content: center;
    padding: 10px 0 4px;
    cursor: grab;
    touch-action: none;
}

.bottomsheet-handle-bar {
    width: 40px;
    height: 4px;
    background: #ccc;
    border-radius: 2px;
}

.bottomsheet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px 12px;
    border-bottom: 1px solid #eee;
}

.bottomsheet-title {
    font-size: 1rem;
    font-weight: 600;
}

.bottomsheet-body {
    padding: 16px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.bottomsheet-footer {
    padding: 12px 16px;
    border-top: 1px solid #eee;
}

/* Transitions */
.bottomsheet-enter-active,
.bottomsheet-leave-active {
    transition: transform 0.3s ease;
}
.bottomsheet-enter-from,
.bottomsheet-leave-to {
    transform: translateY(100%);
}

.bottomsheet-backdrop-enter-active,
.bottomsheet-backdrop-leave-active {
    transition: opacity 0.3s ease;
}
.bottomsheet-backdrop-enter-from,
.bottomsheet-backdrop-leave-to {
    opacity: 0;
}
</style>
