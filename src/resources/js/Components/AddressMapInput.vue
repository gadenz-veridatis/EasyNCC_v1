<template>
    <div>
        <!-- Address input + buttons -->
        <label v-if="label" :for="id" class="form-label">{{ label }}<span v-if="required"> *</span></label>
        <div class="address-input-wrapper">
            <div class="input-group">
                <input
                    ref="addressInputEl"
                    :id="id"
                    :value="address"
                    @input="onAddressInput"
                    @blur="scheduleClearSuggestions"
                    type="text"
                    class="form-control"
                    :placeholder="placeholder"
                    :required="required"
                    autocomplete="off"
                />
                <button
                    type="button"
                    class="btn"
                    :class="hasCoordinates ? `btn-outline-${color}` : `btn-soft-${color}`"
                    @click="openMap"
                    title="Apri mappa per geocodificare"
                >
                    <i class="ri-map-pin-line"></i>
                </button>
                <button
                    v-if="hasCoordinates"
                    type="button"
                    class="btn btn-soft-info"
                    @click="openMapReview"
                    title="Rivedi posizione sulla mappa"
                >
                    <i class="ri-eye-line"></i>
                </button>
            </div>
            <!-- Autocomplete suggestions dropdown -->
            <div v-if="suggestions.length > 0" class="autocomplete-dropdown">
                <div
                    v-for="(suggestion, idx) in suggestions"
                    :key="idx"
                    class="autocomplete-item"
                    @mousedown.prevent="selectSuggestion(suggestion)"
                >
                    <i class="ri-map-pin-line me-2 text-muted"></i>
                    <span>{{ suggestion.text }}</span>
                </div>
            </div>
        </div>
        <!-- Coordinate display (compact, readonly) -->
        <div v-if="hasCoordinates" class="d-flex align-items-center gap-2 mt-1">
            <small class="text-muted">
                <i class="ri-map-pin-2-line me-1"></i>{{ Number(latitude).toFixed(6) }}, {{ Number(longitude).toFixed(6) }}
            </small>
            <button type="button" class="btn btn-link btn-sm p-0 text-danger" @click="clearCoordinates" title="Rimuovi coordinate">
                <i class="ri-close-circle-line"></i>
            </button>
        </div>

        <!-- Map Modal -->
        <Teleport to="body">
            <div v-if="showMapModal" class="modal d-block" tabindex="-1" style="z-index: 10600;" @click.self="cancelMap">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title">
                                <i class="ri-map-pin-line me-1"></i>
                                {{ isReviewMode ? 'Rivedi posizione' : 'Seleziona posizione' }}
                            </h6>
                            <button type="button" class="btn-close" @click="cancelMap"></button>
                        </div>
                        <div class="modal-body p-0">
                            <!-- Search bar inside modal -->
                            <div class="p-2 border-bottom bg-light">
                                <div class="address-input-wrapper">
                                    <div class="input-group input-group-sm">
                                        <input
                                            ref="modalSearchInputEl"
                                            v-model="modalAddress"
                                            type="text"
                                            class="form-control"
                                            placeholder="Cerca indirizzo..."
                                            @keydown.enter.prevent="geocodeModalAddress"
                                            @input="onModalSearchInput"
                                        />
                                        <button type="button" class="btn btn-primary" @click="geocodeModalAddress" :disabled="geocoding">
                                            <span v-if="geocoding" class="spinner-border spinner-border-sm" role="status"></span>
                                            <i v-else class="ri-search-line"></i>
                                        </button>
                                    </div>
                                    <!-- Modal autocomplete suggestions -->
                                    <div v-if="modalSuggestions.length > 0" class="autocomplete-dropdown">
                                        <div
                                            v-for="(suggestion, idx) in modalSuggestions"
                                            :key="idx"
                                            class="autocomplete-item"
                                            @mousedown.prevent="selectModalSuggestion(suggestion)"
                                        >
                                            <i class="ri-map-pin-line me-2 text-muted"></i>
                                            <span>{{ suggestion.text }}</span>
                                        </div>
                                    </div>
                                </div>
                                <small v-if="geocodeError" class="text-danger">{{ geocodeError }}</small>
                            </div>
                            <!-- Map container -->
                            <div ref="mapContainerEl" style="height: 400px; width: 100%;"></div>
                        </div>
                        <div class="modal-footer py-2">
                            <div v-if="modalLat && modalLng" class="me-auto small text-muted">
                                {{ Number(modalLat).toFixed(6) }}, {{ Number(modalLng).toFixed(6) }}
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" @click="cancelMap">Annulla</button>
                            <button type="button" class="btn btn-primary btn-sm" @click="confirmMap" :disabled="!modalLat">
                                <i class="ri-check-line me-1"></i>Conferma
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="showMapModal" class="modal-backdrop fade show" style="z-index: 10599;"></div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    address: { type: String, default: '' },
    latitude: { type: [String, Number], default: '' },
    longitude: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
    color: { type: String, default: 'primary' },
    id: { type: String, default: '' },
});

const emit = defineEmits(['update:address', 'update:latitude', 'update:longitude']);

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';
const DEFAULT_CENTER = { lat: 41.9028, lng: 12.4964 }; // Roma

// Refs
const addressInputEl = ref(null);
const modalSearchInputEl = ref(null);
const mapContainerEl = ref(null);

// State
const showMapModal = ref(false);
const isReviewMode = ref(false);
const modalAddress = ref('');
const modalLat = ref(null);
const modalLng = ref(null);
const geocoding = ref(false);
const geocodeError = ref('');
const suggestions = ref([]);
const modalSuggestions = ref([]);

// Google libraries (loaded lazily)
let mapsLib = null;
let placesLib = null;
let geocodingLib = null;
let mapInstance = null;
let markerInstance = null;
let geocoderInstance = null;

let debounceTimer = null;
let modalDebounceTimer = null;
let blurTimeout = null;

const hasCoordinates = computed(() => {
    return props.latitude && props.longitude && props.latitude !== '' && props.longitude !== '';
});

// Load Google Maps API lazily via script tag (avoids @googlemaps/js-api-loader v2 bugs)
let loadPromise = null;
const loadGoogleMaps = async () => {
    if (mapsLib) return true;
    if (!GOOGLE_MAPS_API_KEY) {
        console.warn('Google Maps API key not configured (VITE_GOOGLE_MAPS_API_KEY)');
        return false;
    }
    if (loadPromise) return loadPromise;

    loadPromise = (async () => {
        // Load the script if not already present
        if (!window.google?.maps?.importLibrary) {
            await new Promise((resolve, reject) => {
                // Check if script already exists
                if (document.querySelector('script[src*="maps.googleapis.com"]')) {
                    // Wait for it to load
                    const check = setInterval(() => {
                        if (window.google?.maps?.importLibrary) {
                            clearInterval(check);
                            resolve();
                        }
                    }, 100);
                    return;
                }
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places,geocoding&v=weekly&loading=async&callback=__gmapsInit`;
                script.async = true;
                script.defer = true;
                window.__gmapsInit = () => {
                    delete window.__gmapsInit;
                    resolve();
                };
                script.onerror = () => reject(new Error('Failed to load Google Maps'));
                document.head.appendChild(script);
            });
        }

        const [maps, places, geocoding] = await Promise.all([
            google.maps.importLibrary('maps'),
            google.maps.importLibrary('places'),
            google.maps.importLibrary('geocoding'),
        ]);
        mapsLib = maps;
        placesLib = places;
        geocodingLib = geocoding;
        geocoderInstance = new geocoding.Geocoder();
        return true;
    })();
    return loadPromise;
};

// ===== AUTOCOMPLETE via new AutocompleteSuggestion API =====

const fetchSuggestions = async (input, target) => {
    if (!input || input.length < 3) {
        if (target === 'main') suggestions.value = [];
        else modalSuggestions.value = [];
        return;
    }
    const loaded = await loadGoogleMaps();
    if (!loaded) return;

    try {
        const request = { input };
        const { suggestions: results } = await placesLib.AutocompleteSuggestion.fetchAutocompleteSuggestions(request);

        const items = (results || []).slice(0, 5).map(s => ({
            text: s.placePrediction.text.text,
            placeId: s.placePrediction.placeId,
        }));
        if (target === 'main') suggestions.value = items;
        else modalSuggestions.value = items;
    } catch (err) {
        console.warn('AutocompleteSuggestion error:', err);
    }
};

// Debounced input handler for main address field
const onAddressInput = (e) => {
    const val = e?.target?.value ?? '';
    emit('update:address', val);
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchSuggestions(val, 'main'), 300);
};

// Debounced input handler for modal search field
const onModalSearchInput = () => {
    if (modalDebounceTimer) clearTimeout(modalDebounceTimer);
    modalDebounceTimer = setTimeout(() => fetchSuggestions(modalAddress.value, 'modal'), 300);
};

const scheduleClearSuggestions = () => {
    blurTimeout = setTimeout(() => { suggestions.value = []; }, 200);
};

// Select a suggestion — use Place.fetchFields() to get coordinates
const selectSuggestion = async (suggestion) => {
    if (blurTimeout) { clearTimeout(blurTimeout); blurTimeout = null; }
    suggestions.value = [];
    emit('update:address', suggestion.text);

    await fetchPlaceDetails(suggestion.placeId, (addr, lat, lng) => {
        if (addr) emit('update:address', addr);
        emit('update:latitude', lat.toFixed(8));
        emit('update:longitude', lng.toFixed(8));
    });
};

const selectModalSuggestion = async (suggestion) => {
    modalSuggestions.value = [];
    modalAddress.value = suggestion.text;

    await fetchPlaceDetails(suggestion.placeId, (addr, lat, lng) => {
        if (addr) modalAddress.value = addr;
        modalLat.value = lat;
        modalLng.value = lng;
        centerMapOn(lat, lng);
    });
};

// Use new Place class to fetch details
const fetchPlaceDetails = async (placeId, callback) => {
    const loaded = await loadGoogleMaps();
    if (!loaded) return;

    try {
        const place = new placesLib.Place({ id: placeId });
        await place.fetchFields({ fields: ['formattedAddress', 'location'] });

        if (place.location) {
            callback(
                place.formattedAddress || null,
                place.location.lat(),
                place.location.lng()
            );
        }
    } catch (err) {
        // Fallback: geocode the placeId
        console.warn('Place.fetchFields failed, falling back to geocoder:', err);
        if (geocoderInstance) {
            try {
                const result = await geocoderInstance.geocode({ placeId });
                if (result.results?.[0]) {
                    const loc = result.results[0].geometry.location;
                    callback(result.results[0].formatted_address, loc.lat(), loc.lng());
                }
            } catch (e) {
                console.error('Geocoder fallback failed:', e);
            }
        }
    }
};

// ===== MAP MODAL =====

const openMap = async () => {
    isReviewMode.value = false;
    modalAddress.value = props.address || '';
    modalLat.value = hasCoordinates.value ? Number(props.latitude) : null;
    modalLng.value = hasCoordinates.value ? Number(props.longitude) : null;
    geocodeError.value = '';
    modalSuggestions.value = [];
    showMapModal.value = true;

    await nextTick();
    await initMap();

    if (modalLat.value && modalLng.value) {
        centerMapOn(modalLat.value, modalLng.value);
    } else if (modalAddress.value) {
        await geocodeModalAddress();
    } else {
        centerMapOn(DEFAULT_CENTER.lat, DEFAULT_CENTER.lng);
    }
};

const openMapReview = async () => {
    isReviewMode.value = true;
    modalAddress.value = props.address || '';
    modalLat.value = Number(props.latitude);
    modalLng.value = Number(props.longitude);
    geocodeError.value = '';
    modalSuggestions.value = [];
    showMapModal.value = true;

    await nextTick();
    await initMap();
    centerMapOn(modalLat.value, modalLng.value);
};

const initMap = async () => {
    const loaded = await loadGoogleMaps();
    if (!loaded || !mapContainerEl.value) return;

    const center = (modalLat.value && modalLng.value)
        ? { lat: modalLat.value, lng: modalLng.value }
        : DEFAULT_CENTER;

    mapInstance = new mapsLib.Map(mapContainerEl.value, {
        center,
        zoom: 15,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    // Use legacy google.maps.Marker for reliable drag support
    markerInstance = new google.maps.Marker({
        map: mapInstance,
        position: center,
        draggable: true,
    });

    // On marker drag end, reverse geocode
    markerInstance.addListener('dragend', async () => {
        const pos = markerInstance.getPosition();
        const lat = pos.lat();
        const lng = pos.lng();
        modalLat.value = lat;
        modalLng.value = lng;
        await reverseGeocode(lat, lng);
    });

    // On map click, move marker
    mapInstance.addListener('click', (e) => {
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();
        markerInstance.setPosition(e.latLng);
        modalLat.value = lat;
        modalLng.value = lng;
        reverseGeocode(lat, lng);
    });
};

const centerMapOn = (lat, lng) => {
    if (!mapInstance || !markerInstance) return;
    const pos = { lat, lng };
    mapInstance.setCenter(pos);
    markerInstance.setPosition(pos);
};

// Geocode address text → coordinates
const geocodeModalAddress = async () => {
    if (!modalAddress.value.trim()) return;
    const loaded = await loadGoogleMaps();
    if (!loaded || !geocoderInstance) return;

    geocoding.value = true;
    geocodeError.value = '';
    modalSuggestions.value = [];
    try {
        const result = await geocoderInstance.geocode({ address: modalAddress.value });
        if (result.results && result.results.length > 0) {
            const loc = result.results[0].geometry.location;
            modalLat.value = loc.lat();
            modalLng.value = loc.lng();
            modalAddress.value = result.results[0].formatted_address;
            centerMapOn(loc.lat(), loc.lng());
        } else {
            geocodeError.value = 'Indirizzo non trovato';
        }
    } catch (err) {
        geocodeError.value = 'Errore nella ricerca dell\'indirizzo';
        console.error('Geocode error:', err);
    } finally {
        geocoding.value = false;
    }
};

// Reverse geocode coordinates → address
const reverseGeocode = async (lat, lng) => {
    if (!geocoderInstance) return;
    try {
        const result = await geocoderInstance.geocode({ location: { lat, lng } });
        if (result.results && result.results.length > 0) {
            modalAddress.value = result.results[0].formatted_address;
        }
    } catch (err) {
        console.error('Reverse geocode error:', err);
    }
};

// Confirm selection
const confirmMap = () => {
    emit('update:address', modalAddress.value);
    emit('update:latitude', modalLat.value ? modalLat.value.toFixed(8) : '');
    emit('update:longitude', modalLng.value ? modalLng.value.toFixed(8) : '');
    closeMap();
};

const cancelMap = () => {
    closeMap();
};

const closeMap = () => {
    showMapModal.value = false;
    modalSuggestions.value = [];
    if (markerInstance) { markerInstance.setMap(null); markerInstance = null; }
    if (mapInstance) { mapInstance = null; }
};

const clearCoordinates = () => {
    emit('update:latitude', '');
    emit('update:longitude', '');
};

// Libraries are loaded lazily on first user interaction (typing or opening map)

onUnmounted(() => {
    closeMap();
});
</script>

<style scoped>
.address-input-wrapper {
    position: relative;
}

.autocomplete-dropdown {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    background: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 6px 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 10700;
    max-height: 250px;
    overflow-y: auto;
}

.autocomplete-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.15s;
}

.autocomplete-item:hover {
    background-color: #f0f4ff;
}
</style>
