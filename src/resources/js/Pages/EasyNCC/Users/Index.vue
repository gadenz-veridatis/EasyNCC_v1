<template>
    <Head title="Utenti" />

    <Layout>
        <PageHeader title="Utenti" pageTitle="EasyNCC" />

        <BRow>
            <BCol lg="12">
                <BCard no-body>
                    <BCardHeader class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Lista Utenti</h5>
                        <Link :href="route('easyncc.users.create')" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus me-1"></i>
                            Nuovo Utente
                        </Link>
                    </BCardHeader>
                    <BCardBody>
                        <!-- Filters -->
                        <BRow class="mb-4">
                            <BCol md="4">
                                <label class="form-label">Ricerca</label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Nome, email..."
                                    @input="loadUsers"
                                />
                            </BCol>
                            <BCol md="4">
                                <label class="form-label">Ruolo</label>
                                <select v-model="filters.role" class="form-select form-select-sm" @change="loadUsers">
                                    <option value="">Tutti i ruoli</option>
                                    <option value="admin">Amministratore</option>
                                    <option value="operatore">Operatore</option>
                                    <option value="driver">Driver</option>
                                    <option value="cliente">Cliente</option>
                                </select>
                            </BCol>
                            <BCol md="4">
                                <label class="form-label">Stato</label>
                                <select v-model="filters.is_active" class="form-select form-select-sm" @change="loadUsers">
                                    <option value="">Tutti gli stati</option>
                                    <option value="1">Attivi</option>
                                    <option value="0">Inattivi</option>
                                </select>
                            </BCol>
                        </BRow>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="users.length > 0" class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Nome</th>
                                        <th scope="col">Cognome</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Ruolo</th>
                                        <th scope="col">Azienda</th>
                                        <th scope="col">Stato</th>
                                        <th scope="col">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="fw-medium">{{ user.first_name }}</td>
                                        <td>{{ user.last_name }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>
                                            <span :class="`badge bg-${getRoleBadge(user.role)}`">
                                                {{ getRoleLabel(user.role) }}
                                            </span>
                                        </td>
                                        <td>{{ user.company?.name || '-' }}</td>
                                        <td>
                                            <span v-if="user.is_active" class="badge bg-success">
                                                Attivo
                                            </span>
                                            <span v-else class="badge bg-danger">
                                                Inattivo
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('easyncc.users.edit', user.id)" class="btn btn-sm btn-soft-primary me-2">
                                                <i class="bx bx-edit"></i>
                                            </Link>
                                            <button
                                                class="btn btn-sm btn-soft-danger"
                                                @click="deleteUser(user.id)"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No Data -->
                        <div v-else class="text-center text-muted py-5">
                            <p>Nessun utente trovato</p>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="alert alert-danger mt-3" role="alert">
                            {{ error }}
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';

const users = ref([]);
const loading = ref(false);
const error = ref('');
const filters = ref({
    search: '',
    role: '',
    is_active: ''
});

const loadUsers = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get('/api/users', {
            params: filters.value
        });
        users.value = response.data;
    } catch (err) {
        error.value = 'Errore nel caricamento degli utenti';
        console.error('Error loading users:', err);
    } finally {
        loading.value = false;
    }
};

const deleteUser = async (id) => {
    if (!confirm('Sei sicuro di voler eliminare questo utente?')) {
        return;
    }

    try {
        await axios.delete(`/api/users/${id}`);
        await loadUsers();
    } catch (err) {
        error.value = 'Errore nell\'eliminazione dell\'utente';
        console.error('Error deleting user:', err);
    }
};

const getRoleLabel = (role) => {
    const labels = {
        'admin': 'Amministratore',
        'operatore': 'Operatore',
        'driver': 'Driver',
        'cliente': 'Cliente'
    };
    return labels[role] || role;
};

const getRoleBadge = (role) => {
    const badges = {
        'admin': 'danger',
        'operatore': 'warning',
        'driver': 'info',
        'cliente': 'primary'
    };
    return badges[role] || 'secondary';
};

onMounted(() => {
    loadUsers();
});
</script>
