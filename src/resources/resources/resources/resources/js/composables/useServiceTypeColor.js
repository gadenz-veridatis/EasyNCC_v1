import { ref } from 'vue';
import axios from 'axios';

const serviceTypes = ref([]);
let loaded = false;
let loading = null;

// Bootstrap color name → hex mapping (for backward compatibility)
const bootstrapColorMap = {
    primary: '#405189',
    secondary: '#6c757d',
    success: '#0ab39c',
    danger: '#f06548',
    warning: '#f7b84b',
    info: '#299cdb',
};

/**
 * Convert a color (hex or Bootstrap name) to a hex value.
 */
const resolveColorToHex = (color) => {
    if (!color) return null;
    if (color.startsWith('#')) return color;
    return bootstrapColorMap[color] || null;
};

/**
 * Generate a subtle badge style object from a hex color.
 */
const hexToBadgeStyle = (hex) => {
    return {
        backgroundColor: hex + '20', // ~12% opacity
        color: hex,
        fontWeight: '500',
    };
};

/**
 * Loads service types once (singleton) and provides a color lookup function.
 */
export function useServiceTypeColor() {
    const loadServiceTypes = () => {
        if (loaded || loading) return loading || Promise.resolve();
        loading = axios.get('/api/dictionaries/service-types')
            .then(res => {
                serviceTypes.value = res.data.data || [];
                loaded = true;
            })
            .catch(err => {
                console.error('Error loading service types:', err);
            })
            .finally(() => {
                loading = null;
            });
        return loading;
    };

    const getServiceTypeColor = (serviceTypeName) => {
        if (!serviceTypeName || !serviceTypes.value.length) return null;
        const needle = serviceTypeName.toLowerCase();
        const found = serviceTypes.value.find(st => st.name?.toLowerCase() === needle);
        return found?.color || null;
    };

    /**
     * Returns an inline style object for a service type badge.
     * Supports both hex colors (#e74c3c) and legacy Bootstrap names (primary, success, etc.)
     */
    const serviceTypeBadgeStyle = (serviceTypeName, fallbackHex = '#405189') => {
        const color = getServiceTypeColor(serviceTypeName);
        const hex = resolveColorToHex(color) || fallbackHex;
        return hexToBadgeStyle(hex);
    };

    // Keep backward-compatible class function (delegates to style when possible)
    const serviceTypeBadgeClass = (serviceTypeName, fallback = 'bg-primary-subtle text-primary') => {
        const color = getServiceTypeColor(serviceTypeName);
        if (!color) return fallback;
        // Legacy Bootstrap names still work as classes
        if (bootstrapColorMap[color]) return `bg-${color}-subtle text-${color}`;
        // Hex colors: return empty string (use serviceTypeBadgeStyle instead)
        return '';
    };

    return { serviceTypes, loadServiceTypes, getServiceTypeColor, serviceTypeBadgeClass, serviceTypeBadgeStyle };
}
