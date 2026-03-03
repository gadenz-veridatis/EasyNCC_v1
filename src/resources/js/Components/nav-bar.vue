<script setup>
import { layoutMethods } from "@/state/helpers";
import { Link, router } from '@inertiajs/vue3';
const logout = () => {
  router.post(route('logout'));
};
</script>

<script>
import { Link } from '@inertiajs/vue3';
import simplebar from "simplebar-vue";
import axios from "axios";



/**
 * Nav-bar Component
 */
export default {
  data() {
    return {
      myVar: 1,
      // Search
      searchQuery: '',
      searchResults: null,
      searchLoading: false,
      searchTimeout: null,
      // Notifications
      notifications: [],
      unreadCount: 0,
      notificationPollingInterval: null,
      loadingNotifications: false,
    };
  },
  components: {
    simplebar,
    Link,
  },

  methods: {
    ...layoutMethods,
    onSearchInput() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout);
      const query = this.searchQuery.trim();
      if (query.length < 2) {
        this.searchResults = null;
        this.searchLoading = false;
        this.hideSearchDropdown();
        return;
      }
      this.searchLoading = true;
      this.showSearchDropdown();
      this.searchTimeout = setTimeout(() => {
        this.performSearch(query);
      }, 300);
    },
    async performSearch(query) {
      try {
        const response = await axios.get('/api/search', { params: { q: query } });
        this.searchResults = response.data;
        this.searchLoading = false;
        this.showSearchDropdown();
      } catch (error) {
        this.searchLoading = false;
        this.searchResults = null;
      }
    },
    showSearchDropdown() {
      const dropdown = document.getElementById("search-dropdown");
      const closeBtn = document.getElementById("search-close-options");
      if (dropdown) dropdown.classList.add("show");
      if (closeBtn) closeBtn.classList.remove("d-none");
    },
    hideSearchDropdown() {
      const dropdown = document.getElementById("search-dropdown");
      const closeBtn = document.getElementById("search-close-options");
      if (dropdown) dropdown.classList.remove("show");
      if (closeBtn) closeBtn.classList.add("d-none");
    },
    clearSearch() {
      this.searchQuery = '';
      this.searchResults = null;
      this.searchLoading = false;
      this.hideSearchDropdown();
    },
    onSearchFocus() {
      if (this.searchQuery.trim().length >= 2 && this.searchResults) {
        this.showSearchDropdown();
      }
    },
    initSearchClickOutside() {
      document.body.addEventListener("click", (e) => {
        const searchArea = document.querySelector('.app-search');
        if (searchArea && !searchArea.contains(e.target)) {
          this.hideSearchDropdown();
        }
      });
    },
    navigateToResult(type, item) {
      this.clearSearch();
      switch (type) {
        case 'services':
          window.location.href = `/easyncc/services/${item.id}/edit`;
          break;
        case 'drivers':
          window.location.href = `/easyncc/drivers/${item.id}/edit`;
          break;
        case 'vehicles':
          window.location.href = `/easyncc/vehicles/${item.id}/edit`;
          break;
        case 'clients':
          window.location.href = `/easyncc/committenti/${item.id}/edit`;
          break;
        case 'suppliers':
          window.location.href = `/easyncc/fornitori/${item.id}/edit`;
          break;
        case 'accounting':
          window.location.href = `/easyncc/accounting-transactions`;
          break;
      }
    },
    getEntityLabel(type) {
      const labels = {
        services: 'Servizi',
        drivers: 'Driver',
        vehicles: 'Veicoli',
        clients: 'Committenti',
        suppliers: 'Fornitori',
        accounting: 'Contabilità',
      };
      return labels[type] || type;
    },
    getEntityIcon(type) {
      const icons = {
        services: 'ri-list-check',
        drivers: 'ri-steering-2-line',
        vehicles: 'ri-car-line',
        clients: 'ri-building-4-line',
        suppliers: 'ri-store-2-line',
        accounting: 'ri-money-euro-circle-line',
      };
      return icons[type] || 'ri-search-line';
    },
    formatResultTitle(type, item) {
      switch (type) {
        case 'services':
          return `${item.reference_number || 'N/A'} - ${item.pickup_address || ''}`;
        case 'drivers':
          return `${item.name || ''} ${item.surname || ''}`.trim();
        case 'vehicles':
          return `${item.license_plate || ''} - ${item.brand || ''} ${item.model || ''}`.trim();
        case 'clients':
        case 'suppliers': {
          const businessName = item.client_profile?.business_name;
          const fullName = `${item.name || ''} ${item.surname || ''}`.trim();
          return businessName ? `${businessName} (${fullName})` : fullName;
        }
        case 'accounting': {
          const ref = item.service?.reference_number || '';
          const counterpart = item.counterpart ? `${item.counterpart.name || ''} ${item.counterpart.surname || ''}`.trim() : '';
          return `${item.transaction_type || ''} - ${ref}${counterpart ? ' - ' + counterpart : ''}`;
        }
        default:
          return '';
      }
    },
    formatResultSubtitle(type, item) {
      switch (type) {
        case 'services': {
          const price = item.service_price ? `€ ${parseFloat(item.service_price).toFixed(2)}` : '';
          const date = item.pickup_datetime ? new Date(item.pickup_datetime).toLocaleDateString('it-IT') : '';
          return [date, price].filter(Boolean).join(' - ');
        }
        case 'drivers':
          return item.email || item.phone || '';
        case 'vehicles':
          return '';
        case 'clients':
        case 'suppliers':
          return item.email || '';
        case 'accounting': {
          const amount = item.amount ? `€ ${parseFloat(item.amount).toFixed(2)}` : '';
          const date = item.transaction_date ? new Date(item.transaction_date).toLocaleDateString('it-IT') : '';
          return [date, amount].filter(Boolean).join(' - ');
        }
        default:
          return '';
      }
    },
    hasAnyResults() {
      if (!this.searchResults) return false;
      return Object.values(this.searchResults).some(arr => arr && arr.length > 0);
    },
    toggleHamburgerMenu() {
      var windowSize = document.documentElement.clientWidth;
      let layoutType = document.documentElement.getAttribute("data-layout");

      document.documentElement.setAttribute("data-sidebar-visibility", "show");
      let visiblilityType = document.documentElement.getAttribute("data-sidebar-visibility");

      if (windowSize > 767)
        document.querySelector(".hamburger-icon").classList.toggle("open");

      //For collapse horizontal menu
      if (
        document.documentElement.getAttribute("data-layout") === "horizontal"
      ) {
        document.body.classList.contains("menu") ?
          document.body.classList.remove("menu") :
          document.body.classList.add("menu");
      }

      //For collapse vertical menu

      if (visiblilityType === "show" && (layoutType === "vertical" || layoutType === "semibox")) {
        if (windowSize < 1025 && windowSize > 767) {
          document.body.classList.remove("vertical-sidebar-enable");
          document.documentElement.getAttribute("data-sidebar-size") == "sm" ?
            document.documentElement.setAttribute("data-sidebar-size", "") :
            document.documentElement.setAttribute("data-sidebar-size", "sm");
        } else if (windowSize > 1025) {
          document.body.classList.remove("vertical-sidebar-enable");
          document.documentElement.getAttribute("data-sidebar-size") == "lg" ?
            document.documentElement.setAttribute("data-sidebar-size", "sm") :
            document.documentElement.setAttribute("data-sidebar-size", "lg");
        } else if (windowSize <= 767) {
          document.body.classList.add("vertical-sidebar-enable");
          document.documentElement.setAttribute("data-sidebar-size", "lg");
        }
      }

      //Two column menu
      if (document.documentElement.getAttribute("data-layout") == "twocolumn") {
        document.body.classList.contains("twocolumn-panel") ?
          document.body.classList.remove("twocolumn-panel") :
          document.body.classList.add("twocolumn-panel");
      }
    },
    toggleMenu() {
      this.$parent.toggleMenu();
    },
    toggleRightSidebar() {
      this.$parent.toggleRightSidebar();
    },
    async loadNotifications() {
      try {
        const response = await axios.get('/api/notifications');
        this.notifications = response.data.data || [];
      } catch (error) {
        // silently fail
      }
    },
    async loadUnreadCount() {
      try {
        const response = await axios.get('/api/notifications/unread-count');
        this.unreadCount = response.data.count || 0;
      } catch (error) {
        // silently fail
      }
    },
    async markAllNotificationsRead() {
      try {
        await axios.post('/api/notifications/mark-all-read');
        this.unreadCount = 0;
        this.notifications.forEach(n => {
          n.is_read = true;
          n.read_at = new Date().toISOString();
        });
      } catch (error) {
        console.error('Error marking notifications as read:', error);
      }
    },
    async markNotificationRead(notification) {
      if (notification.is_read) return;
      try {
        await axios.post(`/api/notifications/${notification.id}/mark-read`);
        notification.is_read = true;
        notification.read_at = new Date().toISOString();
        this.unreadCount = Math.max(0, this.unreadCount - 1);
      } catch (error) {
        console.error('Error marking notification as read:', error);
      }
    },
    getNotificationIcon(type) {
      switch (type) {
        case 'new_message': return 'bx bx-message-dots';
        case 'service_accepted': return 'bx bx-check-circle';
        case 'service_notification_sent': return 'bx bx-send';
        default: return 'bx bx-bell';
      }
    },
    getNotificationIconClass(type) {
      switch (type) {
        case 'new_message': return 'bg-info-subtle text-info';
        case 'service_accepted': return 'bg-success-subtle text-success';
        case 'service_notification_sent': return 'bg-primary-subtle text-primary';
        default: return 'bg-warning-subtle text-warning';
      }
    },
    formatTimeAgo(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      const now = new Date();
      const diffMs = now - date;
      const diffMin = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMin < 1) return 'Adesso';
      if (diffMin < 60) return `${diffMin} min fa`;
      if (diffHours < 24) return `${diffHours} ore fa`;
      if (diffDays < 7) return `${diffDays} giorni fa`;
      return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    navigateToNotification(notification) {
      this.markNotificationRead(notification);
      if (notification.type === 'new_message') {
        window.location.href = '/easyncc/telegram/chat';
      } else if (notification.type === 'service_accepted') {
        window.location.href = '/easyncc/telegram/chat';
      }
    },
    startNotificationPolling() {
      this.loadUnreadCount();
      this.loadNotifications();
      this.notificationPollingInterval = setInterval(() => {
        this.loadUnreadCount();
      }, 30000); // Poll every 30 seconds
    },
    stopNotificationPolling() {
      if (this.notificationPollingInterval) {
        clearInterval(this.notificationPollingInterval);
        this.notificationPollingInterval = null;
      }
    },
  },

  mounted() {
    document.addEventListener("scroll", function () {
      var pageTopbar = document.getElementById("page-topbar");
      if (pageTopbar) {
        document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50 ? pageTopbar.classList.add(
          "topbar-shadow") : pageTopbar.classList.remove("topbar-shadow");
      }
    });
    if (document.getElementById("topnav-hamburger-icon"))
      document.getElementById("topnav-hamburger-icon").addEventListener("click", this.toggleHamburgerMenu);

    this.initSearchClickOutside();

    // Start notification polling
    this.startNotificationPolling();
  },
  beforeUnmount() {
    this.stopNotificationPolling();
  },
};
</script>

<template>
  <header id="page-topbar">
    <div class="layout-width">
      <div class="navbar-header">
        <div class="d-flex">
          <!-- LOGO -->
          <div class="navbar-brand-box horizontal-logo">
            <Link href="/" class="logo logo-dark">
            <span class="logo-sm">
              <img src="@assets/images/easy_small_green.png" alt="" height="22" />
            </span>
            <span class="logo-lg">
              <img src="@assets/images/easydark.png" alt="" height="60" />
            </span>
            </Link>

            <Link href="/" class="logo logo-light">
            <span class="logo-sm">
              <img src="@assets/images/easy_small_green.png" alt="" height="22" />
            </span>
            <span class="logo-lg">
              <img src="@assets/images/easylight.png" alt="" height="60" />
            </span>
            </Link>
          </div>

          <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none" id="topnav-hamburger-icon">
            <span class="hamburger-icon">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </button>

          <!-- App Search-->
          <form class="app-search d-none d-md-block" @submit.prevent>
            <div class="position-relative">
              <input type="text" class="form-control" placeholder="Cerca servizi, driver, veicoli..." autocomplete="off" id="search-options"
                v-model="searchQuery" @input="onSearchInput" @focus="onSearchFocus" />
              <span class="mdi mdi-magnify search-widget-icon"></span>
              <span class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none" id="search-close-options" @click="clearSearch"></span>
            </div>
            <div class="dropdown-menu dropdown-menu-lg" id="search-dropdown" style="min-width: 380px;">
              <simplebar data-simplebar style="max-height: 400px">
                <!-- Loading -->
                <div v-if="searchLoading" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary"></div>
                  <p class="mt-2 text-muted mb-0 fs-13">Ricerca in corso...</p>
                </div>

                <!-- No results -->
                <div v-else-if="searchResults && !hasAnyResults()" class="text-center py-4">
                  <i class="ri-search-line fs-1 text-muted"></i>
                  <p class="mt-2 text-muted mb-0">Nessun risultato per "<strong>{{ searchQuery }}</strong>"</p>
                </div>

                <!-- Results grouped by entity -->
                <template v-else-if="searchResults">
                  <template v-for="(items, type) in searchResults" :key="type">
                    <template v-if="items && items.length > 0">
                      <div class="dropdown-header mt-1">
                        <h6 class="text-overflow text-muted mb-0 text-uppercase fs-12">
                          <i :class="getEntityIcon(type)" class="me-1"></i>
                          {{ getEntityLabel(type) }}
                          <span class="badge bg-primary-subtle text-primary ms-1">{{ items.length }}</span>
                        </h6>
                      </div>
                      <a v-for="item in items" :key="item.id"
                        href="javascript:void(0);"
                        class="dropdown-item notify-item py-2"
                        @click="navigateToResult(type, item)">
                        <div class="d-flex align-items-center">
                          <div class="avatar-xs me-2 flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-14">
                              <i :class="getEntityIcon(type)"></i>
                            </span>
                          </div>
                          <div class="flex-grow-1 overflow-hidden">
                            <h6 class="m-0 fs-13 text-truncate">{{ formatResultTitle(type, item) }}</h6>
                            <p v-if="formatResultSubtitle(type, item)" class="mb-0 fs-11 text-muted text-truncate">{{ formatResultSubtitle(type, item) }}</p>
                          </div>
                          <div v-if="type === 'services' && item.status" class="flex-shrink-0 ms-2">
                            <span class="badge rounded-pill fs-10" :style="{ backgroundColor: item.status.color_code || '#6c757d', color: '#fff' }">
                              {{ item.status.name }}
                            </span>
                          </div>
                        </div>
                      </a>
                    </template>
                  </template>
                </template>
              </simplebar>
            </div>
          </form>
        </div>

        <div class="d-flex align-items-center">
          <BDropdown class="dropdown d-md-none topbar-head-dropdown header-item" variant="ghost-secondary" dropstart :offset="{ alignmentAxis: 55, crossAxis: 15, mainAxis: 0 }" toggle-class="btn-icon btn-topbar rounded-circle arrow-none shadow-none" menu-class="dropdown-menu-lg dropdown-menu-end p-0">
            <template #button-content>
              <i class="bx bx-search fs-22"></i>
            </template>
            <BDropdownItem aria-labelledby="page-header-search-dropdown">
              <form class="p-3" @submit.prevent>
                <div class="form-group m-0">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Cerca..." v-model="searchQuery" @input="onSearchInput" />
                    <BButton variant="primary" @click="onSearchInput">
                      <i class="mdi mdi-magnify"></i>
                    </BButton>
                  </div>
                </div>
              </form>
            </BDropdownItem>
          </BDropdown>

          <!-- Chat Telegram -->
          <div class="ms-1 header-item">
            <Link href="/easyncc/telegram/chat" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle shadow-none" title="Chat Telegram">
              <i class="bx bx-chat fs-22"></i>
            </Link>
          </div>

          <BDropdown variant="ghost-dark" dropstart class="ms-1 dropdown" :offset="{ alignmentAxis: 57, crossAxis: 0, mainAxis: -42 }" toggle-class="btn-icon btn-topbar rounded-circle arrow-none shadow-none" id="page-header-notifications-dropdown" menu-class="dropdown-menu-lg dropdown-menu-end p-0" auto-close="outside" @show="loadNotifications">
            <template #button-content>
              <i class='bx bx-bell fs-22'></i>
              <span v-if="unreadCount > 0" class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger">
                <span class="notification-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
                <span class="visually-hidden">notifiche non lette</span>
              </span>
            </template>
            <div class="dropdown-head bg-primary bg-pattern rounded-top dropdown-menu-lg">
              <div class="p-3">
                <BRow class="align-items-center">
                  <BCol>
                    <h6 class="m-0 fs-16 fw-semibold text-white">
                      Notifiche
                    </h6>
                  </BCol>
                  <BCol cols="auto" class="dropdown-tabs">
                    <BBadge v-if="unreadCount > 0" variant="light-subtle" class="bg-light-subtle text-body fs-13">
                      {{ unreadCount }} nuove
                    </BBadge>
                  </BCol>
                </BRow>
              </div>
            </div>
            <div class="py-2 ps-2">
              <simplebar data-simplebar style="max-height: 350px" class="pe-2">
                <!-- Loading -->
                <div v-if="loadingNotifications" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>

                <!-- No notifications -->
                <div v-else-if="notifications.length === 0" class="text-center py-4">
                  <i class="bx bx-bell-off fs-1 text-muted"></i>
                  <p class="mt-2 text-muted mb-0">Nessuna notifica</p>
                </div>

                <!-- Notification items -->
                <template v-else>
                  <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="text-reset notification-item d-block dropdown-item position-relative"
                    :class="{ 'bg-light-subtle': !notification.is_read }"
                    style="cursor: pointer;"
                    @click="navigateToNotification(notification)"
                  >
                    <div class="d-flex">
                      <div class="avatar-xs me-3 flex-shrink-0">
                        <span class="avatar-title rounded-circle fs-16" :class="getNotificationIconClass(notification.type)">
                          <i :class="getNotificationIcon(notification.type)"></i>
                        </span>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="mt-0 mb-1 fs-13 fw-semibold" :class="{ 'text-body': notification.is_read }">
                          {{ notification.title }}
                        </h6>
                        <div v-if="notification.body" class="fs-13 text-muted">
                          <p class="mb-1">{{ notification.body.length > 80 ? notification.body.substring(0, 80) + '...' : notification.body }}</p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> {{ formatTimeAgo(notification.created_at) }}</span>
                        </p>
                      </div>
                      <div v-if="!notification.is_read" class="px-2 flex-shrink-0 align-self-center">
                        <span class="badge bg-primary rounded-circle p-1">&nbsp;</span>
                      </div>
                    </div>
                  </div>
                </template>

                <!-- Mark all as read button -->
                <div v-if="unreadCount > 0" class="my-3 text-center">
                  <BButton type="button" variant="soft-primary" size="sm" @click="markAllNotificationsRead">
                    <i class="bx bx-check-double me-1"></i> Segna tutte come lette
                  </BButton>
                </div>

                <!-- Link to chat -->
                <div class="my-2 text-center">
                  <Link href="/easyncc/telegram/chat" class="btn btn-sm btn-soft-success">
                    Apri Chat Telegram
                    <i class="ri-arrow-right-line align-middle"></i>
                  </Link>
                </div>
              </simplebar>
            </div>
          </BDropdown>

          <BDropdown variant="link" class="ms-sm-3 header-item topbar-user" toggle-class="rounded-circle arrow-none shadow-none" menu-class="dropdown-menu-end" :offset="{ alignmentAxis: -14, crossAxis: 0, mainAxis: 0 }">
            <template #button-content>
              <span class="d-flex align-items-center">
                <img v-if="$page.props.jetstream.managesProfilePhotos" class="rounded-circle header-profile-user" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                <span class="text-start ms-xl-2">
                  <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ $page.props.auth.user.name }}</span>
                  <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">Founder</span>
                </span>
              </span>
            </template>
            <h6 class="dropdown-header">Welcome {{ $page.props.auth.user.name }}!</h6>
            <Link class="dropdown-item" :href="route('profile.show')"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle">Profile</span>
            </Link>
            <Link class="dropdown-item" v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')"><i class="mdi mdi-key-variant text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> API Tokens</span>
            </Link>
            <div class="dropdown-divider"></div>
            <Link class="dropdown-item" href="/chat">
            <i class=" mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Messages</span>
            </Link>
            <Link class="dropdown-item" href="/apps/tasks-kanban">
            <i class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Taskboard</span>
            </Link>
            <Link class="dropdown-item" href="/pages/faqs"><i class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Help</span>
            </Link>
            <div class="dropdown-divider"></div>
            <Link class="dropdown-item" href="/pages/profile"><i class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Balance : <b>$5971.67</b></span>
            </Link>
            <Link class="dropdown-item" href="/pages/profile-setting">
            <BBadge variant="success-subtle" class="bg-success-subtle text-success mt-1 float-end">New</BBadge><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Settings</span>
            </Link>
            <Link class="dropdown-item" href="/auth/lockscreen-basic"><i class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Lock screen</span>
            </Link>

            <!-- Authentication -->
            <form method="POST" @submit.prevent="logout" class="dropdown-item">
              <BButton variant="none" type="submit" class="p-0 shadow-none"><i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> Logout</BButton>
            </form>
          </BDropdown>
        </div>
      </div>
    </div>
  </header>
</template>
