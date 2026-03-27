import { ref } from 'vue';
import axios from 'axios';

const transactionStatuses = ref([]);
let loaded = false;
let loading = null;

/**
 * Loads transaction statuses once (singleton) and provides color lookup.
 * Colors are Bootstrap class names (warning, success, info, danger, etc.)
 */
export function useTransactionStatusColor() {
    const loadTransactionStatuses = () => {
        if (loaded || loading) return loading || Promise.resolve();
        loading = axios.get('/api/dictionaries/transaction-statuses')
            .then(res => {
                transactionStatuses.value = res.data.data || [];
                loaded = true;
            })
            .catch(err => {
                console.error('Error loading transaction statuses:', err);
            })
            .finally(() => {
                loading = null;
            });
        return loading;
    };

    /**
     * Get the Bootstrap color class for a transaction status code.
     * Returns null if no match found.
     */
    const getTransactionStatusColor = (statusCode) => {
        if (!statusCode || !transactionStatuses.value.length) return null;
        const found = transactionStatuses.value.find(s => s.code === statusCode);
        return found?.color || null;
    };

    /**
     * Get the badge class for a transaction status from the service's transaction_status_map.
     * @param {Object} statusMap - The service.transaction_status_map object
     * @param {string} key - The map key (e.g., 'sale_deposit', 'purchase_balance_11')
     * @param {string} fallback - Fallback Bootstrap class if no status found
     */
    const transactionBadgeClass = (statusMap, key, fallback = 'bg-danger bg-opacity-75') => {
        if (!statusMap || !key) return fallback;
        const statusCode = statusMap[key];
        if (!statusCode) return fallback;
        const color = getTransactionStatusColor(statusCode);
        return color ? `bg-${color}` : fallback;
    };

    return { transactionStatuses, loadTransactionStatuses, getTransactionStatusColor, transactionBadgeClass };
}
