import { watch } from 'vue';

/**
 * Syncs a filters ref and pagination/sort state with URL query params.
 * - On init: reads query params and applies them to the refs
 * - On change: updates URL query params via history.replaceState (no reload)
 *
 * @param {Ref} filters - reactive filters object
 * @param {Object} options - { page, sortField, sortDirection, defaults }
 *   defaults: object with default values for filters (values matching defaults are omitted from URL)
 */
export function useUrlFilters(filters, options = {}) {
    const { page = null, sortField = null, sortDirection = null, defaults = {} } = options;

    /**
     * Read URL query params into the refs on init.
     * Returns true if any param was read (so caller knows to skip default preset).
     */
    const readFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        let hadParams = false;

        // Restore filters
        for (const key of Object.keys(filters.value)) {
            if (params.has(key)) {
                filters.value[key] = params.get(key);
                hadParams = true;
            }
        }

        // Restore pagination
        if (page && params.has('page')) {
            page.value = parseInt(params.get('page')) || 1;
            hadParams = true;
        }

        // Restore sort
        if (sortField && params.has('sort_by')) {
            sortField.value = params.get('sort_by');
            hadParams = true;
        }
        if (sortDirection && params.has('sort_order')) {
            sortDirection.value = params.get('sort_order');
            hadParams = true;
        }

        return hadParams;
    };

    /**
     * Write current state to URL query params (no page reload).
     */
    const writeToUrl = () => {
        const params = new URLSearchParams();

        // Write filters (skip empty/default values)
        for (const [key, value] of Object.entries(filters.value)) {
            if (value !== '' && value !== null && value !== undefined) {
                // Skip if matches default
                if (defaults[key] !== undefined && String(value) === String(defaults[key])) continue;
                params.set(key, value);
            }
        }

        // Write pagination (skip page 1)
        if (page && page.value > 1) {
            params.set('page', page.value);
        }

        // Write sort (skip defaults)
        if (sortField && sortField.value && sortField.value !== 'pickup_datetime') {
            params.set('sort_by', sortField.value);
        }
        if (sortDirection && sortDirection.value && sortDirection.value !== 'asc') {
            params.set('sort_order', sortDirection.value);
        }

        const qs = params.toString();
        const newUrl = window.location.pathname + (qs ? '?' + qs : '');
        history.replaceState(null, '', newUrl);
    };

    /**
     * Build the current page URL with query params (for returnUrl).
     */
    const getCurrentUrl = () => {
        writeToUrl(); // ensure URL is up to date
        return window.location.pathname + window.location.search;
    };

    /**
     * Build a link with returnUrl appended.
     */
    const withReturnUrl = (href) => {
        const currentUrl = getCurrentUrl();
        const separator = href.includes('?') ? '&' : '?';
        return href + separator + 'returnUrl=' + encodeURIComponent(currentUrl);
    };

    // Watch filters and sync to URL
    watch(filters, writeToUrl, { deep: true });
    if (page) watch(page, writeToUrl);
    if (sortField) watch(sortField, writeToUrl);
    if (sortDirection) watch(sortDirection, writeToUrl);

    return { readFromUrl, writeToUrl, getCurrentUrl, withReturnUrl };
}
