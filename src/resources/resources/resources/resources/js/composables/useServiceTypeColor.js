import { ref } from 'vue';
import axios from 'axios';

const serviceTypes = ref([]);
let loaded = false;
let loading = null;

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

    const serviceTypeBadgeClass = (serviceTypeName, fallback = 'bg-primary-subtle text-primary') => {
        const color = getServiceTypeColor(serviceTypeName);
        return color ? `bg-${color}-subtle text-${color}` : fallback;
    };

    return { serviceTypes, loadServiceTypes, getServiceTypeColor, serviceTypeBadgeClass };
}
