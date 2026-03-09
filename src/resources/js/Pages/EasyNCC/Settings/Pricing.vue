<template>
    <Head title="Impostazioni Preventivi" />

    <Layout>
        <PageHeader title="Impostazioni Preventivi" pageTitle="Configurazione" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader>
                        <h5 class="card-title mb-0">
                            <i class="ri-calculator-line me-2"></i>Configurazione Pricing e Destinazioni
                        </h5>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Settings Form -->
                        <form v-else @submit.prevent="saveSettings">
                            <!-- Company Selection (only for super-admin) -->
                            <BRow v-if="isSuperAdmin" class="mb-4">
                                <BCol md="12">
                                    <div class="alert alert-info">
                                        <label class="form-label fw-bold">Seleziona Azienda</label>
                                        <select
                                            v-model="selectedCompanyId"
                                            class="form-select"
                                            @change="loadAllData"
                                        >
                                            <option value="">Seleziona un'azienda</option>
                                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                                {{ company.name }}
                                            </option>
                                        </select>
                                    </div>
                                </BCol>
                            </BRow>

                            <div v-if="selectedCompanyId || !isSuperAdmin">
                                <!-- Pricing - Costi Operativi -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-gas-station-line me-2"></i>Costi Operativi
                                    </legend>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Prezzo Carburante (€/litro)</label>
                                            <input v-model.number="form.pricing_vehicle_costs.fuel_price" type="number" class="form-control" step="0.01" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Prezzo Adblue (€/litro)</label>
                                            <input v-model.number="form.pricing_vehicle_costs.adblue" type="number" class="form-control" step="0.01" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Costo Autista (€/ora)</label>
                                            <input v-model.number="form.pricing_vehicle_costs.driver_hourly" type="number" class="form-control" step="0.5" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Costo Veicolo Standard (€/ora)</label>
                                            <input v-model.number="form.pricing_vehicle_costs.vehicle_hourly" type="number" class="form-control" step="0.5" min="0" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Veicolo -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-car-line me-2"></i>Veicolo
                                    </legend>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Consumo (km/litro)</label>
                                            <input v-model.number="form.pricing_vehicle_assumptions.fuel_consumption" type="number" class="form-control" step="0.1" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Costo Veicolo (€)</label>
                                            <input v-model.number="form.pricing_vehicle_assumptions.vehicle_cost" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Vita Utile (km)</label>
                                            <input v-model.number="form.pricing_vehicle_assumptions.vehicle_life_km" type="number" class="form-control" step="1000" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Utilizzo Annuale (km)</label>
                                            <input v-model.number="form.pricing_vehicle_assumptions.annual_usage_km" type="number" class="form-control" step="1000" min="0" />
                                        </BCol>
                                    </BRow>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Età Veicolo (anni)</label>
                                            <input v-model.number="form.pricing_vehicle_assumptions.vehicle_age" type="number" class="form-control" min="0" max="10" />
                                            <small class="text-muted">0 = media di tutti gli anni</small>
                                        </BCol>
                                    </BRow>
                                    <label class="form-label fw-semibold">Ammortamento (% per anno)</label>
                                    <BRow>
                                        <BCol v-for="(val, idx) in form.pricing_depreciation" :key="idx" md="1" class="mb-2">
                                            <label class="form-label small">Anno {{ idx }}</label>
                                            <input v-model.number="form.pricing_depreciation[idx]" type="number" class="form-control form-control-sm" step="0.01" min="0" max="1" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Spese Fisse Annuali -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-file-list-3-line me-2"></i>Spese Fisse Annuali
                                    </legend>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Assicurazione (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.insurance" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Bollo (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.tax" type="number" class="form-control" step="10" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Revisione (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.inspection" type="number" class="form-control" step="10" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Tagliandi (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.service" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                    </BRow>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Carrozziere (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.bodywork" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Meccanico (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.mechanic" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Lavaggi (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.washing" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Incidenza Forfettari (%)</label>
                                            <input v-model.number="form.pricing_annual_expenses.forfeit_pct" type="number" class="form-control" step="0.01" min="0" max="1" />
                                        </BCol>
                                    </BRow>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Cambio Gomme ogni (km)</label>
                                            <input v-model.number="form.pricing_annual_expenses.tire_change_km" type="number" class="form-control" step="1000" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Costo Ricambio Gomme (€)</label>
                                            <input v-model.number="form.pricing_annual_expenses.tire_cost" type="number" class="form-control" step="100" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Pedaggio Medio (€/km)</label>
                                            <input v-model.number="form.pricing_toll.avg_price_per_km" type="number" class="form-control" step="0.01" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">% Strade a Pedaggio</label>
                                            <input v-model.number="form.pricing_toll.toll_road_share" type="number" class="form-control" step="0.01" min="0" max="1" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Markup -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-percent-line me-2"></i>Markup
                                    </legend>
                                    <BRow>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Markup Trasporto (%)</label>
                                            <input v-model.number="form.pricing_markups.transport" type="number" class="form-control" step="1" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Markup Esperienze (%)</label>
                                            <input v-model.number="form.pricing_markups.experiences" type="number" class="form-control" step="1" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Markup Aggiuntivo Tour (%)</label>
                                            <input v-model.number="form.pricing_markups.tour" type="number" class="form-control" step="1" min="0" />
                                        </BCol>
                                        <BCol md="3" class="mb-3">
                                            <label class="form-label">Markup Solo Driver (%)</label>
                                            <input v-model.number="form.pricing_markups.driver" type="number" class="form-control" step="1" min="0" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Politiche Stagionali e Veicolo -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-calendar-line me-2"></i>Politiche Stagionali e Veicolo
                                    </legend>

                                    <h6 class="fw-semibold mb-2">Servizio - Moltiplicatori Stagione</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Low</label>
                                            <input v-model.number="form.pricing_season_service.low" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Average</label>
                                            <input v-model.number="form.pricing_season_service.average" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">High</label>
                                            <input v-model.number="form.pricing_season_service.high" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Servizio - Moltiplicatori Veicolo</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Car (2 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_service.car" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Van (3-6 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_service.van" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Full (7-8 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_service.full" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Esperienze - Moltiplicatori Stagione</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Low</label>
                                            <input v-model.number="form.pricing_season_experience.low" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Average</label>
                                            <input v-model.number="form.pricing_season_experience.average" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">High</label>
                                            <input v-model.number="form.pricing_season_experience.high" type="number" class="form-control" step="0.1" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Esperienze - Moltiplicatori Veicolo</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Car (2 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_experience.car" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Van (3-6 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_experience.van" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Full (7-8 pax)</label>
                                            <input v-model.number="form.pricing_vehicle_experience.full" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Estensione Servizio - Moltiplicatori Veicolo</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Car</label>
                                            <input v-model.number="form.pricing_extension.car" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Van</label>
                                            <input v-model.number="form.pricing_extension.van" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Full</label>
                                            <input v-model.number="form.pricing_extension.full" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Estensione Servizio - Moltiplicatori Stagione</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Vuoto</label>
                                            <input v-model.number="form.pricing_extension.empty" type="number" class="form-control" step="0.01" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Low</label>
                                            <input v-model.number="form.pricing_extension.low" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Average</label>
                                            <input v-model.number="form.pricing_extension.average" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">High</label>
                                            <input v-model.number="form.pricing_extension.high" type="number" class="form-control" step="0.05" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Curve di Attenuazione -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-line-chart-line me-2"></i>Curve di Attenuazione
                                    </legend>

                                    <h6 class="fw-semibold mb-2">Trasporto (con passeggeri)</h6>
                                    <BRow class="mb-3">
                                        <BCol md="3">
                                            <label class="form-label">Max Aggiunta (0 km)</label>
                                            <input v-model.number="form.pricing_attenuation_transport.max_add" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Velocità Decadimento</label>
                                            <input v-model.number="form.pricing_attenuation_transport.decay_speed" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Velocità Attenuazione</label>
                                            <input v-model.number="form.pricing_attenuation_transport.att_speed" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Soglia Attenuazione (km)</label>
                                            <input v-model.number="form.pricing_attenuation_transport.threshold" type="number" class="form-control" step="10" />
                                        </BCol>
                                    </BRow>

                                    <h6 class="fw-semibold mb-2">Solo Driver (extra km)</h6>
                                    <BRow>
                                        <BCol md="3">
                                            <label class="form-label">Max Aggiunta (0 km)</label>
                                            <input v-model.number="form.pricing_attenuation_driver.max_add" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Velocità Decadimento</label>
                                            <input v-model.number="form.pricing_attenuation_driver.decay_speed" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Velocità Attenuazione</label>
                                            <input v-model.number="form.pricing_attenuation_driver.att_speed" type="number" class="form-control" step="1" />
                                        </BCol>
                                        <BCol md="3">
                                            <label class="form-label">Soglia Attenuazione (km)</label>
                                            <input v-model.number="form.pricing_attenuation_driver.threshold" type="number" class="form-control" step="10" />
                                        </BCol>
                                    </BRow>
                                </fieldset>

                                <!-- Pricing - Destinazioni -->
                                <fieldset class="border rounded p-3 mb-4">
                                    <legend class="fs-5 fw-semibold text-primary mb-3">
                                        <i class="ri-map-pin-line me-2"></i>Destinazioni
                                    </legend>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Destinazione</th>
                                                    <th>Tipo</th>
                                                    <th>Ore</th>
                                                    <th>Km</th>
                                                    <th>Pedaggio €</th>
                                                    <th>Esp. A €</th>
                                                    <th>Esp. B €</th>
                                                    <th>Esp. C €</th>
                                                    <th>Esp. D €</th>
                                                    <th style="width:100px">Azioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(dest, idx) in destinations" :key="dest.id || idx">
                                                    <td>
                                                        <input v-model="dest.destination" type="text" class="form-control form-control-sm" />
                                                    </td>
                                                    <td>
                                                        <select v-model="dest.service_type" class="form-select form-select-sm">
                                                            <option value="TRF">TRF</option>
                                                            <option value="TOUR HD">TOUR HD</option>
                                                            <option value="TOUR FD">TOUR FD</option>
                                                            <option value="TOUR FD+">TOUR FD+</option>
                                                        </select>
                                                    </td>
                                                    <td><input v-model.number="dest.duration_hours" type="number" class="form-control form-control-sm" step="0.5" min="0" /></td>
                                                    <td><input v-model.number="dest.mileage_km" type="number" class="form-control form-control-sm" min="0" /></td>
                                                    <td><input v-model.number="dest.toll_cost" type="number" class="form-control form-control-sm" step="1" min="0" /></td>
                                                    <td><input v-model.number="dest.experience_a" type="number" class="form-control form-control-sm" step="1" min="0" /></td>
                                                    <td><input v-model.number="dest.experience_b" type="number" class="form-control form-control-sm" step="1" min="0" /></td>
                                                    <td><input v-model.number="dest.experience_c" type="number" class="form-control form-control-sm" step="1" min="0" /></td>
                                                    <td><input v-model.number="dest.experience_d" type="number" class="form-control form-control-sm" step="1" min="0" /></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-success me-1" @click="saveDestination(dest, idx)" title="Salva">
                                                            <i class="ri-save-line"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteDestination(dest, idx)" title="Elimina">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addDestination">
                                        <i class="ri-add-line me-1"></i>Aggiungi Destinazione
                                    </button>
                                </fieldset>

                                <!-- Alert per errori -->
                                <div v-if="errors.length > 0" class="alert alert-danger">
                                    <ul class="mb-0">
                                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                                    </ul>
                                </div>

                                <!-- Alert successo -->
                                <div v-if="successMessage" class="alert alert-success">
                                    {{ successMessage }}
                                </div>

                                <!-- Pulsanti -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="ri-save-line me-1"></i>
                                        Salva Impostazioni Pricing
                                    </button>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                Seleziona un'azienda per modificare le impostazioni
                            </div>
                        </form>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";

export default {
    components: { Head, Layout, PageHeader },
    data() {
        return {
            loading: true,
            saving: false,
            errors: [],
            successMessage: '',
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            destinations: [],
            form: {
                pricing_markups: { transport: 45, experiences: 10, tour: 25, driver: 0 },
                pricing_vehicle_costs: { fuel_price: 1.8, adblue: 0.1, driver_hourly: 22, vehicle_hourly: 77 },
                pricing_vehicle_assumptions: { fuel_consumption: 10, vehicle_cost: 67500, vehicle_life_km: 250000, annual_usage_km: 80000, vehicle_age: 0 },
                pricing_annual_expenses: { insurance: 2500, tax: 400, inspection: 100, service: 3000, bodywork: 1000, mechanic: 1000, washing: 1200, forfeit_pct: 0.15, tire_change_km: 40000, tire_cost: 1000 },
                pricing_season_service: { low: 0.8, average: 1.0, high: 1.3 },
                pricing_vehicle_service: { car: 0.85, van: 1.0, full: 1.2 },
                pricing_season_experience: { low: 0.5, average: 1.0, high: 1.7 },
                pricing_vehicle_experience: { car: 1.0, van: 1.1, full: 1.2 },
                pricing_attenuation_transport: { max_add: 120, decay_speed: 45, att_speed: 5, threshold: 600 },
                pricing_attenuation_driver: { max_add: 60, decay_speed: 30, att_speed: 25, threshold: 60 },
                pricing_extension: { car: 0.9, van: 1.0, full: 1.1, empty: 0.71, low: 0.85, average: 1.0, high: 1.15 },
                pricing_depreciation: [0.22, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2],
                pricing_toll: { avg_price_per_km: 0.07, toll_road_share: 0.35 },
            },
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
    },
    async mounted() {
        await this.loadCurrentUser();
        if (this.isSuperAdmin) {
            await this.loadCompanies();
            this.loading = false;
        } else {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadAllData();
        }
    },
    methods: {
        async loadCurrentUser() {
            try {
                const response = await axios.get('/api/user');
                this.currentUser = response.data;
            } catch (error) {
                console.error('Error loading current user:', error);
            }
        },
        async loadCompanies() {
            try {
                const response = await axios.get('/api/companies');
                this.companies = response.data.data || [];
            } catch (error) {
                console.error('Error loading companies:', error);
            }
        },
        async loadAllData() {
            if (!this.selectedCompanyId) return;

            this.loading = true;
            this.errors = [];
            this.successMessage = '';

            try {
                await Promise.all([
                    this.loadSettings(),
                    this.loadDestinations(),
                ]);
            } catch (error) {
                console.error('Error loading pricing settings:', error);
                this.errors = ['Errore durante il caricamento delle impostazioni'];
            } finally {
                this.loading = false;
            }
        },
        async loadSettings() {
            if (!this.selectedCompanyId) return;

            const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
            const response = await axios.get('/api/settings', { params });

            const data = response.data.data;
            const defaults = this.$options.data().form;
            this.form = {
                pricing_markups: { ...defaults.pricing_markups, ...(data.pricing_markups || {}) },
                pricing_vehicle_costs: { ...defaults.pricing_vehicle_costs, ...(data.pricing_vehicle_costs || {}) },
                pricing_vehicle_assumptions: { ...defaults.pricing_vehicle_assumptions, ...(data.pricing_vehicle_assumptions || {}) },
                pricing_annual_expenses: { ...defaults.pricing_annual_expenses, ...(data.pricing_annual_expenses || {}) },
                pricing_season_service: { ...defaults.pricing_season_service, ...(data.pricing_season_service || {}) },
                pricing_vehicle_service: { ...defaults.pricing_vehicle_service, ...(data.pricing_vehicle_service || {}) },
                pricing_season_experience: { ...defaults.pricing_season_experience, ...(data.pricing_season_experience || {}) },
                pricing_vehicle_experience: { ...defaults.pricing_vehicle_experience, ...(data.pricing_vehicle_experience || {}) },
                pricing_attenuation_transport: { ...defaults.pricing_attenuation_transport, ...(data.pricing_attenuation_transport || {}) },
                pricing_attenuation_driver: { ...defaults.pricing_attenuation_driver, ...(data.pricing_attenuation_driver || {}) },
                pricing_extension: { ...defaults.pricing_extension, ...(data.pricing_extension || {}) },
                pricing_depreciation: data.pricing_depreciation || [...defaults.pricing_depreciation],
                pricing_toll: { ...defaults.pricing_toll, ...(data.pricing_toll || {}) },
            };
        },
        async loadDestinations() {
            if (!this.selectedCompanyId) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                const response = await axios.get('/api/pricing-destinations', { params });
                this.destinations = response.data.data || [];
            } catch (error) {
                console.error('Error loading destinations:', error);
                this.destinations = [];
            }
        },
        addDestination() {
            this.destinations.push({
                id: null,
                destination: '',
                service_type: 'TRF',
                duration_hours: 0,
                mileage_km: 0,
                toll_cost: 0,
                experience_a: 0,
                experience_b: 0,
                experience_c: 0,
                experience_d: 0,
            });
        },
        async saveDestination(dest, idx) {
            try {
                const payload = { ...dest };
                if (this.isSuperAdmin) {
                    payload.company_id = this.selectedCompanyId;
                }

                if (dest.id) {
                    const response = await axios.put(`/api/pricing-destinations/${dest.id}`, payload);
                    this.destinations[idx] = response.data.data;
                } else {
                    const response = await axios.post('/api/pricing-destinations', payload);
                    this.destinations[idx] = response.data.data;
                }
                this.successMessage = 'Destinazione salvata con successo!';
                setTimeout(() => { this.successMessage = ''; }, 3000);
            } catch (error) {
                console.error('Error saving destination:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = ['Errore durante il salvataggio della destinazione'];
                }
            }
        },
        async deleteDestination(dest, idx) {
            if (!dest.id) {
                this.destinations.splice(idx, 1);
                return;
            }

            if (!confirm('Eliminare questa destinazione?')) return;

            try {
                const params = this.isSuperAdmin ? { company_id: this.selectedCompanyId } : {};
                await axios.delete(`/api/pricing-destinations/${dest.id}`, { params });
                this.destinations.splice(idx, 1);
                this.successMessage = 'Destinazione eliminata con successo!';
                setTimeout(() => { this.successMessage = ''; }, 3000);
            } catch (error) {
                console.error('Error deleting destination:', error);
                this.errors = ['Errore durante l\'eliminazione della destinazione'];
            }
        },
        async saveSettings() {
            this.saving = true;
            this.errors = [];
            this.successMessage = '';

            try {
                const data = { ...this.form };
                if (this.isSuperAdmin) {
                    data.company_id = this.selectedCompanyId;
                }

                await axios.put('/api/settings', data);
                this.successMessage = 'Impostazioni pricing salvate con successo!';
                setTimeout(() => { this.successMessage = ''; }, 3000);
            } catch (error) {
                console.error('Error saving settings:', error);
                if (error.response?.data?.errors) {
                    this.errors = Object.values(error.response.data.errors).flat();
                } else {
                    this.errors = ['Errore durante il salvataggio delle impostazioni'];
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
