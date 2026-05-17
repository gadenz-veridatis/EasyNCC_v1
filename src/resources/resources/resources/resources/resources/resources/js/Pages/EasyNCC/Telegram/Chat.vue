<template>
    <Layout>
        <PageHeader title="Chat Telegram" pageTitle="Telegram" />
        <BRow>
            <BCol lg="12">
                <!-- Company Selection (super-admin only) -->
                <div v-if="isSuperAdmin" class="mb-3">
                    <div class="alert alert-info mb-0">
                        <label class="form-label fw-bold">Seleziona Azienda</label>
                        <select v-model="selectedCompanyId" class="form-select" @change="loadConversations">
                            <option value="">Seleziona un'azienda</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Driver not linked to Telegram -->
                <div v-if="isDriverUser && driverLoaded && !driverLinked" class="alert alert-warning d-flex align-items-center gap-3">
                    <i class="ri-telegram-line" style="font-size: 2.5rem;"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Account Telegram non collegato</h6>
                        <p class="mb-0">Il tuo profilo non è ancora stato collegato a un account Telegram. Contatta l'amministratore per attivare la possibilità di ricevere e inviare messaggi su Telegram.</p>
                    </div>
                </div>

                <div v-if="showChat" class="chat-container" :style="{ height: chatHeight + 'px' }">
                    <BRow class="g-0 h-100">
                        <!-- Colonna sinistra: Lista conversazioni (hidden for driver) -->
                        <BCol v-if="!isDriverUser" md="4" class="h-100 border-end" style="overflow: hidden;">
                            <div class="d-flex flex-column h-100">
                                <!-- Search -->
                                <div class="p-3 border-bottom">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input
                                            v-model="conversationSearch"
                                            type="text"
                                            class="form-control"
                                            placeholder="Cerca conversazione..."
                                        />
                                    </div>
                                </div>
                                <!-- Conversation list -->
                                <div class="flex-grow-1" style="overflow-y: auto;">
                                    <div v-if="loadingConversations" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                    </div>
                                    <div v-else-if="filteredConversations.length === 0" class="text-center py-4 text-muted small">
                                        Nessuna conversazione
                                    </div>
                                    <div
                                        v-for="conv in filteredConversations"
                                        :key="conv.id"
                                        class="conversation-item p-3 border-bottom"
                                        :class="{ 'active-conversation': activeConversation?.id === conv.id }"
                                        @click="selectConversation(conv)"
                                        style="cursor: pointer;"
                                    >
                                        <div class="d-flex align-items-start gap-2">
                                            <!-- Avatar -->
                                            <div
                                                class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                :style="{
                                                    width: '40px',
                                                    height: '40px',
                                                    backgroundColor: conv.user_id ? '#198754' : '#6c757d',
                                                    color: '#fff',
                                                    fontSize: '0.85rem',
                                                    fontWeight: 'bold'
                                                }"
                                            >
                                                {{ getInitials(conv) }}
                                            </div>
                                            <!-- Content -->
                                            <div class="flex-grow-1 min-width-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-bold text-truncate">
                                                        {{ getDisplayName(conv) }}
                                                    </div>
                                                    <small class="text-muted flex-shrink-0 ms-1">
                                                        {{ formatTime(conv.last_message?.created_at) }}
                                                    </small>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="small text-muted text-truncate" style="max-width: 180px;">
                                                        <span v-if="conv.last_message?.direction === 'outbound'" class="text-primary">Tu: </span>
                                                        <span v-if="conv.last_message?.message_type === 'document'"><i class="ri-file-line"></i> {{ conv.last_message?.content ? conv.last_message.content.split('\n')[0] : 'Documento' }}</span>
                                                        <span v-else-if="conv.last_message?.message_type === 'callback'"><i class="ri-check-double-line"></i> {{ conv.last_message?.content || 'Azione' }}</span>
                                                        <span v-else>{{ conv.last_message?.content || 'Nessun messaggio' }}</span>
                                                    </div>
                                                    <span
                                                        v-if="conv.unread_count > 0"
                                                        class="badge bg-primary rounded-pill flex-shrink-0"
                                                    >
                                                        {{ conv.unread_count }}
                                                    </span>
                                                </div>
                                                <div v-if="conv.driver" class="small text-success mt-1">
                                                    <i class="ri-steering-line"></i> {{ driverLabel(conv.driver) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </BCol>

                        <!-- Colonna destra: Chat -->
                        <BCol :md="isDriverUser ? 12 : 8" class="h-100" style="overflow: hidden;">
                            <div v-if="activeConversation" class="d-flex flex-column h-100">
                                <!-- Chat header -->
                                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">{{ isDriverUser ? 'Chat Telegram' : getDisplayName(activeConversation) }}</div>
                                        <small class="text-muted" v-if="!isDriverUser && activeConversation.username">@{{ activeConversation.username }}</small>
                                        <small class="text-success ms-2" v-if="!isDriverUser && activeConversation.driver">
                                            <i class="ri-steering-line"></i> {{ driverLabel(activeConversation.driver) }}
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <small v-if="polling" class="text-muted">
                                            <i class="ri-refresh-line ri-spin"></i>
                                        </small>
                                    </div>
                                </div>

                                <!-- Messages area -->
                                <div ref="messagesContainer" class="flex-grow-1 p-3" style="overflow-y: auto; background-color: #e5ddd5;">
                                    <div v-if="loadingMessages" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                    </div>
                                    <div v-else>
                                        <div
                                            v-for="msg in messages"
                                            :key="msg.id"
                                            class="d-flex mb-2"
                                            :class="msg.direction === 'outbound' ? 'justify-content-end' : 'justify-content-start'"
                                        >
                                            <div
                                                class="chat-bubble p-2 px-3 rounded-3"
                                                :class="msg.direction === 'outbound' ? 'bubble-outbound' : 'bubble-inbound'"
                                                style="max-width: 70%; word-wrap: break-word;"
                                            >
                                                <!-- Content -->
                                                <div v-if="msg.message_type === 'document'" class="small">
                                                    <div class="mb-1"><i class="ri-file-pdf-line me-1"></i><span class="fw-bold">Documento inviato</span></div>
                                                    <div v-if="msg.content && msg.direction === 'outbound'" style="white-space: pre-wrap;" v-html="msg.content"></div>
                                                    <div v-else-if="msg.content" style="white-space: pre-wrap;">{{ msg.content }}</div>
                                                </div>
                                                <div v-else-if="msg.message_type === 'callback'" class="small fst-italic" style="white-space: pre-wrap;">
                                                    <i class="ri-check-double-line me-1 text-success"></i>
                                                    {{ msg.content || msg.callback_data }}
                                                </div>
                                                <div v-else-if="msg.direction === 'outbound'" style="white-space: pre-wrap;" v-html="msg.content"></div>
                                                <div v-else style="white-space: pre-wrap;">{{ msg.content }}</div>

                                                <!-- Timestamp & read status -->
                                                <div class="d-flex justify-content-end align-items-center gap-1 mt-1">
                                                    <small class="chat-time">
                                                        {{ formatMsgTime(msg.created_at) }}
                                                    </small>
                                                    <span v-if="msg.direction === 'outbound'" class="chat-check">
                                                        <i class="ri-check-double-line" :class="msg.is_read ? 'text-info' : ''"></i>
                                                    </span>
                                                    <span v-else>
                                                        <i v-if="msg.is_read" class="ri-check-double-line text-info chat-check"></i>
                                                        <i v-else class="ri-check-line chat-check"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input area -->
                                <div class="p-3 border-top bg-white">
                                    <form @submit.prevent="sendMessage" class="d-flex gap-2">
                                        <input
                                            v-model="newMessage"
                                            type="text"
                                            class="form-control"
                                            placeholder="Scrivi un messaggio..."
                                            :disabled="sending"
                                            ref="messageInput"
                                        />
                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                            :disabled="!newMessage.trim() || sending"
                                        >
                                            <span v-if="sending" class="spinner-border spinner-border-sm"></span>
                                            <i v-else class="ri-send-plane-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- No conversation selected -->
                            <div v-else class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                <i class="ri-chat-3-line" style="font-size: 4rem;"></i>
                                <p class="mt-3">Seleziona una conversazione</p>
                            </div>
                        </BCol>
                    </BRow>
                </div>
            </BCol>
        </BRow>
    </Layout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import axios from "axios";
import { driverLabel } from '@/composables/useDriverLabel.js';

export default {
    components: {
        Head,
        Link,
        Layout,
        PageHeader,
    },
    data() {
        return {
            currentUser: null,
            companies: [],
            selectedCompanyId: '',
            conversations: [],
            activeConversation: null,
            messages: [],
            newMessage: '',
            conversationSearch: '',
            loadingConversations: false,
            loadingMessages: false,
            sending: false,
            polling: false,
            pollInterval: null,
            chatHeight: 600,
            driverLoaded: false,
            driverLinked: false,
        };
    },
    computed: {
        isSuperAdmin() {
            return this.currentUser?.role === 'super-admin';
        },
        isDriverUser() {
            return this.currentUser?.role === 'driver';
        },
        showChat() {
            if (this.isDriverUser) {
                return this.driverLoaded && this.driverLinked;
            }
            return !!this.selectedCompanyId;
        },
        filteredConversations() {
            if (!this.conversationSearch) return this.conversations;
            const s = this.conversationSearch.toLowerCase();
            return this.conversations.filter(c => {
                const name = ((c.first_name || '') + ' ' + (c.last_name || '')).toLowerCase();
                const username = (c.username || '').toLowerCase();
                const driver = c.driver ? this.driverLabel(c.driver).toLowerCase() : '';
                return name.includes(s) || username.includes(s) || driver.includes(s);
            });
        },
    },
    async mounted() {
        this.calculateChatHeight();
        window.addEventListener('resize', this.calculateChatHeight);

        await this.loadCurrentUser();

        if (this.isDriverUser) {
            await this.loadDriverConversation();
        } else if (this.isSuperAdmin) {
            await this.loadCompanies();
        } else {
            this.selectedCompanyId = this.currentUser.company_id;
            await this.loadConversations();
        }

        // Check if we need to auto-open a conversation (from URL params) - non-driver only
        if (!this.isDriverUser) {
            const urlParams = new URLSearchParams(window.location.search);
            const chatId = urlParams.get('chat_id');
            if (chatId) {
                this.$nextTick(() => {
                    const conv = this.conversations.find(c => String(c.telegram_chat_id) === String(chatId));
                    if (conv) this.selectConversation(conv);
                });
            }
        }
    },
    beforeUnmount() {
        this.stopPolling();
        window.removeEventListener('resize', this.calculateChatHeight);
    },
    methods: {
        driverLabel,
        calculateChatHeight() {
            this.chatHeight = Math.max(500, window.innerHeight - 220);
        },
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
        async loadDriverConversation() {
            try {
                const response = await axios.get('/api/telegram/chat/driver/conversation');
                this.driverLoaded = true;
                this.driverLinked = response.data.linked;

                if (this.driverLinked && response.data.data) {
                    this.activeConversation = response.data.data;
                    await this.loadMessages();
                    await this.markAsRead();
                    this.startPolling();
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        if (this.$refs.messageInput) this.$refs.messageInput.focus();
                    });
                }
            } catch (error) {
                console.error('Error loading driver conversation:', error);
                this.driverLoaded = true;
                this.driverLinked = false;
            }
        },
        async loadConversations() {
            if (!this.selectedCompanyId) return;

            this.loadingConversations = true;
            try {
                const params = { company_id: this.selectedCompanyId };
                const response = await axios.get('/api/telegram/chat/conversations', { params });
                this.conversations = response.data.data || [];
            } catch (error) {
                console.error('Error loading conversations:', error);
            } finally {
                this.loadingConversations = false;
            }
        },
        async selectConversation(conv) {
            this.activeConversation = conv;
            await this.loadMessages();
            await this.markAsRead();
            this.startPolling();
            this.$nextTick(() => {
                this.scrollToBottom();
                if (this.$refs.messageInput) this.$refs.messageInput.focus();
            });
        },
        async loadMessages() {
            if (!this.activeConversation) return;

            this.loadingMessages = true;
            try {
                let response;
                if (this.isDriverUser) {
                    response = await axios.get('/api/telegram/chat/driver/messages');
                } else {
                    const params = {
                        telegram_user_id: this.activeConversation.id,
                        company_id: this.selectedCompanyId,
                    };
                    response = await axios.get('/api/telegram/chat/messages', { params });
                }
                this.messages = response.data.data || [];
                this.$nextTick(() => this.scrollToBottom());
            } catch (error) {
                console.error('Error loading messages:', error);
            } finally {
                this.loadingMessages = false;
            }
        },
        async pollMessages() {
            if (!this.activeConversation) return;

            this.polling = true;
            try {
                const lastId = this.messages.length > 0 ? this.messages[this.messages.length - 1].id : 0;
                let response;

                if (this.isDriverUser) {
                    response = await axios.get('/api/telegram/chat/driver/messages', {
                        params: { after_id: lastId },
                    });
                } else {
                    const params = {
                        telegram_user_id: this.activeConversation.id,
                        company_id: this.selectedCompanyId,
                        after_id: lastId,
                    };
                    response = await axios.get('/api/telegram/chat/messages', { params });
                }

                const newMessages = response.data.data || [];

                if (newMessages.length > 0) {
                    this.messages.push(...newMessages);
                    this.$nextTick(() => this.scrollToBottom());
                    await this.markAsRead();
                    if (!this.isDriverUser) {
                        await this.loadConversations();
                    }
                }
            } catch (error) {
                console.error('Polling error:', error);
            } finally {
                this.polling = false;
            }
        },
        startPolling() {
            this.stopPolling();
            this.pollInterval = setInterval(() => {
                this.pollMessages();
            }, 10000);
        },
        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },
        async sendMessage() {
            if (!this.newMessage.trim() || !this.activeConversation) return;

            this.sending = true;
            const messageText = this.newMessage;
            this.newMessage = '';

            try {
                let response;
                if (this.isDriverUser) {
                    response = await axios.post('/api/telegram/chat/driver/send', {
                        content: messageText,
                    });
                } else {
                    response = await axios.post('/api/telegram/chat/send', {
                        telegram_user_id: this.activeConversation.id,
                        content: messageText,
                        company_id: this.selectedCompanyId,
                    });
                }

                this.messages.push(response.data.data);
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.messageInput) this.$refs.messageInput.focus();
                });
                if (!this.isDriverUser) {
                    await this.loadConversations();
                }
            } catch (error) {
                console.error('Error sending message:', error);
                this.newMessage = messageText; // Restore on error
                alert(error.response?.data?.message || 'Errore nell\'invio del messaggio');
            } finally {
                this.sending = false;
            }
        },
        async markAsRead() {
            if (!this.activeConversation) return;

            try {
                if (this.isDriverUser) {
                    await axios.post('/api/telegram/chat/driver/mark-read');
                } else {
                    await axios.post('/api/telegram/chat/mark-read', {
                        telegram_user_id: this.activeConversation.id,
                        company_id: this.selectedCompanyId,
                    });
                    const conv = this.conversations.find(c => c.id === this.activeConversation.id);
                    if (conv) conv.unread_count = 0;
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        },
        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },
        getInitials(conv) {
            const first = (conv.first_name || '')[0] || '';
            const last = (conv.last_name || '')[0] || '';
            return (first + last).toUpperCase() || '?';
        },
        getDisplayName(conv) {
            const name = ((conv.first_name || '') + ' ' + (conv.last_name || '')).trim();
            return name || conv.username || 'Utente sconosciuto';
        },
        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            const isToday = d.toDateString() === now.toDateString();
            if (isToday) {
                return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            }
            return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
        },
        formatMsgTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
        },
    },
};
</script>

<style scoped>
.chat-container {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #fff;
}

.conversation-item:hover {
    background-color: #f8f9fa;
}

.active-conversation {
    background-color: #e8f4f8 !important;
    border-left: 3px solid #0d6efd;
}

.min-width-0 {
    min-width: 0;
}

.bubble-inbound {
    background-color: #ffffff;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

.bubble-outbound {
    background-color: #dcf8c6;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

.chat-time {
    font-size: 0.7rem;
    color: #999;
}

.chat-check {
    font-size: 0.75rem;
    color: #999;
}

.ri-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
