import { ref } from 'vue';
import axios from 'axios';

const transactionStatuses = ref([]);
let loaded = false;
let loading = null;

// Legacy Bootstrap to hex mapping for backwards compatibility
const bootstrapMap = {
    primary: '#405189', secondary: '#6c757d', success: '#0ab39c',
    danger: '#f06548', warning: '#f7b84b', info: '#299cdb',
};

const noDataFallback = { backgroundColor: '#6c757d', color: '#fff' }; // grey for null/missing map

/**
 * Resolve a color value (hex or legacy Bootstrap name) to hex.
 */
function resolveHex(color) {
    if (!color) return null;
    if (color.startsWith('#')) return color;
    return bootstrapMap[color] || null;
}

/**
 * Loads transaction statuses once (singleton) and provides color lookup.
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
     * Get the hex color for a transaction status code.
     * Returns null if no match found.
     */
    const getTransactionStatusColor = (statusCode) => {
        if (!statusCode || !transactionStatuses.value.length) return null;
        const found = transactionStatuses.value.find(s => s.code === statusCode);
        return found ? resolveHex(found.color) : null;
    };

    /**
     * Get inline style for a SOLID badge (used in service list totals).
     * White text on colored background.
     */
    const transactionBadgeSolidStyle = (statusMap, key, aggregateKey = null) => {
        if (!statusMap) return noDataFallback;

        const statusCode = statusMap[key] || (aggregateKey ? statusMap[aggregateKey] : null);
        if (!statusCode) return noDataFallback;

        const hex = getTransactionStatusColor(statusCode);
        if (!hex) return noDataFallback;

        return { backgroundColor: hex, color: '#fff' };
    };

    /**
     * Get inline style for a SUBTLE badge (used in accounting transactions list).
     * Colored text on light-colored background.
     */
    const transactionBadgeSubtleStyle = (statusCode) => {
        const fallback = { backgroundColor: '#6c757d20', color: '#6c757d', fontWeight: '500' };
        if (!statusCode) return fallback;

        const hex = getTransactionStatusColor(statusCode);
        if (!hex) return fallback;

        return { backgroundColor: hex + '20', color: hex, fontWeight: '500' };
    };

    /**
     * Get inline style for aggregate total (e.g. deposit + balance).
     * Uses only aggregate keys (sale_deposit, sale_balance) to determine color.
     * Logic: all final → final color; otherwise → worst non-final color.
     */
    const transactionBadgeAggregateStyle = (statusMap, keys) => {
        if (!statusMap) return noDataFallback;

        const statuses = keys
            .map(k => statusMap[k])
            .filter(Boolean);

        if (statuses.length === 0) return noDataFallback;

        // Find corresponding status objects
        const statusObjects = statuses
            .map(code => transactionStatuses.value.find(s => s.code === code))
            .filter(Boolean);

        if (statusObjects.length === 0) return noDataFallback;

        // Exclude cancelled statuses from aggregation
        const activeStatuses = statusObjects.filter(s => s.code !== 'cancelled');

        // If all are cancelled, show cancelled color
        if (activeStatuses.length === 0) {
            const cancelledObj = statusObjects[0];
            const hex = resolveHex(cancelledObj.color);
            return { backgroundColor: hex || noDataFallback.backgroundColor, color: '#fff' };
        }

        // If all active are final (e.g. all collected/paid) → use the final color
        const allFinal = activeStatuses.every(s => s.is_final);
        if (allFinal) {
            const hex = resolveHex(activeStatuses[0].color);
            return { backgroundColor: hex || noDataFallback.backgroundColor, color: '#fff' };
        }

        // Otherwise use the color of the first non-final active status (worst state)
        const nonFinal = activeStatuses.find(s => !s.is_final);
        if (nonFinal) {
            const hex = resolveHex(nonFinal.color);
            return { backgroundColor: hex || noDataFallback.backgroundColor, color: '#fff' };
        }

        return noDataFallback;
    };

    return {
        transactionStatuses,
        loadTransactionStatuses,
        getTransactionStatusColor,
        transactionBadgeSolidStyle,
        transactionBadgeSubtleStyle,
        transactionBadgeAggregateStyle,
    };
}
