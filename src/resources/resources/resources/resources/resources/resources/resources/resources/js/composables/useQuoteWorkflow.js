import { ref } from 'vue';
import axios from 'axios';

export function useQuoteWorkflow() {
    const transitioning = ref(false);
    const transitionError = ref('');
    const emailPreview = ref(null);
    const previewLoading = ref(false);

    const STATUS_LABELS = {
        draft: 'Bozza',
        approved: 'Approvato',
        sent: 'Inviato',
        deposit_received: 'Acconto Ricevuto',
    };

    const STATUS_COLORS = {
        draft: 'secondary',
        approved: 'warning',
        sent: 'info',
        deposit_received: 'success',
    };

    const STEPS = ['draft', 'approved', 'sent', 'deposit_received'];

    function getStatusLabel(status) {
        return STATUS_LABELS[status] || status;
    }

    function getStatusColor(status) {
        return STATUS_COLORS[status] || 'secondary';
    }

    function getStepIndex(status) {
        return STEPS.indexOf(status);
    }

    async function executeTransition(quoteId, action, options = {}) {
        transitioning.value = true;
        transitionError.value = '';

        try {
            const payload = { action, ...options };
            const response = await axios.post(`/api/quotes/${quoteId}/transition`, payload);
            return response.data;
        } catch (error) {
            const message = error.response?.data?.message || 'Errore durante la transizione';
            transitionError.value = message;
            throw error;
        } finally {
            transitioning.value = false;
        }
    }

    async function loadEmailPreview(quoteId, templateId) {
        previewLoading.value = true;
        emailPreview.value = null;

        try {
            const response = await axios.post(`/api/quotes/${quoteId}/preview-email`, {
                email_template_id: templateId,
            });
            emailPreview.value = response.data.data;
            return response.data.data;
        } catch (error) {
            console.error('Error loading email preview:', error);
            return null;
        } finally {
            previewLoading.value = false;
        }
    }

    async function loadTransitions(quoteId) {
        try {
            const response = await axios.get(`/api/quotes/${quoteId}/transitions`);
            return response.data.data || [];
        } catch (error) {
            console.error('Error loading transitions:', error);
            return [];
        }
    }

    return {
        transitioning,
        transitionError,
        emailPreview,
        previewLoading,
        STATUS_LABELS,
        STATUS_COLORS,
        STEPS,
        getStatusLabel,
        getStatusColor,
        getStepIndex,
        executeTransition,
        loadEmailPreview,
        loadTransitions,
    };
}
