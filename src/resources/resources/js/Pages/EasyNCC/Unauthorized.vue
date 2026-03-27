<template>
    <Head title="Accesso Negato" />

    <Layout>
        <BRow class="justify-content-center mt-5">
            <BCol md="8" lg="6">
                <BCard class="text-center shadow-sm">
                    <BCardBody class="py-5">
                        <div class="mb-4">
                            <i class="ri-lock-line text-warning" style="font-size: 4rem;"></i>
                        </div>

                        <h3 class="mb-3 text-dark">Accesso Non Autorizzato</h3>

                        <p class="text-muted fs-5 mb-4">
                            {{ message }}
                        </p>

                        <div class="d-flex justify-content-center gap-3">
                            <button
                                class="btn btn-primary"
                                @click="goBack"
                            >
                                <i class="ri-arrow-left-line me-1"></i>
                                Torna Indietro
                            </button>
                            <a
                                href="/"
                                class="btn btn-soft-secondary"
                            >
                                <i class="ri-home-line me-1"></i>
                                Vai alla Home
                            </a>
                        </div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";

export default {
    components: {
        Head,
        Layout,
    },
    props: {
        message: {
            type: String,
            default: 'Non hai i permessi per accedere a questa pagina. Se ritieni di dover avere accesso, contatta l\'amministratore della tua azienda.',
        },
    },
    methods: {
        goBack() {
            window.history.back();
        },
    },
};
</script>
