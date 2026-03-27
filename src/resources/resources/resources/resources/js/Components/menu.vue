<script>
import { Link, router } from '@inertiajs/vue3';
import { layoutComputed } from "@/state/helpers";
import simplebar from "simplebar-vue";

export default {
  components: {
    simplebar,
    Link
  },
  data() {
    return {
      settings: {
        minScrollbarLength: 60,
      },
    };
  },
  computed: {
    ...layoutComputed,
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
    userRole() {
      return this.$page?.props?.auth?.user?.role;
    },
    canManage() {
      return ['super-admin', 'admin', 'operator'].includes(this.userRole);
    },
    isAdmin() {
      return ['super-admin', 'admin'].includes(this.userRole);
    },
    isSuperAdmin() {
      return this.userRole === 'super-admin';
    },
  },

  watch: {
    $route: {
      handler: "onRoutechange",
      immediate: true,
      deep: true,
    },
  },

  mounted() {
    this.initActiveMenu();
    if (this.rmenu == 'vertical' && this.layoutType == 'twocolumn') {
      document.documentElement.setAttribute("data-layout", "vertical");
    }
    document.getElementById('overlay').addEventListener('click', () => {
      document.body.classList.remove('vertical-sidebar-enable');
    });
    window.addEventListener("resize", () => {
      if (this.layoutType == 'twocolumn') {
        var windowSize = document.documentElement.clientWidth;
        if (windowSize < 767) {
          document.documentElement.setAttribute("data-layout", "vertical");
          this.rmenu = 'vertical';
          localStorage.setItem('rmenu', 'vertical');
        } else {
          document.documentElement.setAttribute("data-layout", "vertical");
          this.rmenu = 'twocolumn';
          localStorage.setItem('rmenu', 'twocolumn');
          setTimeout(() => {
            this.initActiveMenu();
    this.onRoutechange();
          }, 50);
        }
      }
    });
    this.onRoutechange();
    if (document.querySelectorAll(".navbar-nav .collapse")) {
      let collapses = document.querySelectorAll(".navbar-nav .collapse");

      collapses.forEach((collapse) => {
        // Hide sibling collapses on `show.bs.collapse`
        collapse.addEventListener("show.bs.collapse", (e) => {
          e.stopPropagation();
          let closestCollapse = collapse.parentElement.closest(".collapse");
          if (closestCollapse) {
            let siblingCollapses =
              closestCollapse.querySelectorAll(".collapse");
            siblingCollapses.forEach((siblingCollapse) => {
              if (siblingCollapse.classList.contains("show")) {
                siblingCollapse.classList.remove("show");
                siblingCollapse.parentElement.firstChild.setAttribute("aria-expanded", "false");
              }
            });
          } else {
            let getSiblings = (elem) => {
              // Setup siblings array and get the first sibling
              let siblings = [];
              let sibling = elem.parentNode.firstChild;
              // Loop through each sibling and push to the array
              while (sibling) {
                if (sibling.nodeType === 1 && sibling !== elem) {
                  siblings.push(sibling);
                }
                sibling = sibling.nextSibling;
              }
              return siblings;
            };
            let siblings = getSiblings(collapse.parentElement);
            siblings.forEach((item) => {
              if (item.childNodes.length > 2) {
                item.firstElementChild.setAttribute("aria-expanded", "false");
                item.firstElementChild.classList.remove("active");
              }
              let ids = item.querySelectorAll("*[id]");
              ids.forEach((item1) => {
                item1.classList.remove("show");
                item1.parentElement.firstChild.setAttribute("aria-expanded", "false");
                item1.parentElement.firstChild.classList.remove("active");
                if (item1.childNodes.length > 2) {
                  let val = item1.querySelectorAll("ul li a");

                  val.forEach((subitem) => {
                    if (subitem.hasAttribute("aria-expanded"))
                      subitem.setAttribute("aria-expanded", "false");
                  });
                }
              });
            });
          }
        });

        // Hide nested collapses on `hide.bs.collapse`
        collapse.addEventListener("hide.bs.collapse", (e) => {
          e.stopPropagation();
          let childCollapses = collapse.querySelectorAll(".collapse");
          childCollapses.forEach((childCollapse) => {
            let childCollapseInstance = childCollapse;
            childCollapseInstance.classList.remove("show");
            childCollapseInstance.parentElement.firstChild.setAttribute("aria-expanded", "false");
          });
        });
      });
    }
  },

  methods: {
    onRoutechange() {
      // this.initActiveMenu();
      setTimeout(() => {
        var currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          let currentPosition = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]')?.offsetTop;
          if (currentPosition > document.documentElement.clientHeight) {
            document.querySelector("#scrollbar .simplebar-content-wrapper") ? document.querySelector("#scrollbar .simplebar-content-wrapper").scrollTop = currentPosition + 300 : '';
          }
        }
      }, 500);
    },

    initActiveMenu() {
      setTimeout(() => {
        var currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          let a = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]');
          if (a) {
            a.classList.add("active");
            let parentCollapseDiv = a.closest(".collapse.menu-dropdown");
            if (parentCollapseDiv) {
              parentCollapseDiv.classList.add("show");
              parentCollapseDiv.parentElement.children[0].classList.add("active");
              parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
              if (parentCollapseDiv.parentElement.closest(".collapse.menu-dropdown")) {
                parentCollapseDiv.parentElement.closest(".collapse").classList.add("show");
                if (parentCollapseDiv.parentElement.closest(".collapse").previousElementSibling)
                  parentCollapseDiv.parentElement.closest(".collapse").previousElementSibling.classList.add("active");
                const grandparent = parentCollapseDiv.parentElement.closest(".collapse").previousElementSibling.parentElement.closest(".collapse");
                if (grandparent && grandparent && grandparent.previousElementSibling) {
                  grandparent.previousElementSibling.classList.add("active");
                  grandparent.classList.add("show");
                }
              }
            }
          }
        }
      }, 0);
    },
  },
};
</script>

<template>
  <BContainer fluid>
    <div id="two-column-menu"></div>

    <template v-if="layoutType === 'vertical' || layoutType === 'semibox'">
      <ul class="navbar-nav h-100" id="navbar-nav">
        <!-- SERVIZI -->
        <li class="menu-title">
          <span data-key="t-menu">Servizi</span>
        </li>

        <!-- Dashboard (currently disabled) -->
        <!--
        <li class="nav-item">
          <Link href="/" class="nav-link menu-link">
            <i class="ri-dashboard-2-line"></i>
            <span>Dashboard</span>
          </Link>
        </li>
        -->

        <!-- Lista Servizi -->
        <li class="nav-item">
          <Link href="/easyncc/services" class="nav-link menu-link">
            <i class="ri-list-check"></i>
            <span>Lista</span>
          </Link>
        </li>

        <!-- Calendario -->
        <li class="nav-item">
          <Link href="/easyncc/services/calendar" class="nav-link menu-link">
            <i class="ri-calendar-line"></i>
            <span>Calendario</span>
          </Link>
        </li>

        <!-- Esperienze -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/activities" class="nav-link menu-link">
            <i class="ri-calendar-event-line"></i>
            <span>Esperienze</span>
          </Link>
        </li>

        <!-- RICHIESTE & PREVENTIVI -->
        <li class="menu-title" v-if="canManage">
          <i class="ri-more-fill"></i>
          <span data-key="t-quotes">Richieste &amp; Preventivi</span>
        </li>

        <!-- Contatti -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/contacts" class="nav-link menu-link">
            <i class="ri-contacts-line"></i>
            <span>Contatti</span>
          </Link>
        </li>

        <!-- Preventivi -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/quotes" class="nav-link menu-link">
            <i class="ri-file-list-3-line"></i>
            <span>Preventivi</span>
          </Link>
        </li>

        <!-- Calcolatore Prezzi -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/pricing-calculator" class="nav-link menu-link">
            <i class="ri-calculator-line"></i>
            <span>Calcolatore Prezzi</span>
          </Link>
        </li>

        <!-- AMMINISTRAZIONE -->
        <li class="menu-title" v-if="canManage">
          <i class="ri-more-fill"></i>
          <span data-key="t-administration">Amministrazione</span>
        </li>

        <!-- Task -->
        <li class="nav-item">
          <Link href="/easyncc/tasks" class="nav-link menu-link">
            <i class="ri-task-line"></i>
            <span>Task</span>
          </Link>
        </li>

        <!-- Contabilità -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/accounting-transactions" class="nav-link menu-link">
            <i class="ri-money-euro-circle-line"></i>
            <span>Contabilità</span>
          </Link>
        </li>

        <!-- ANAGRAFICHE -->
        <li class="menu-title" v-if="canManage">
          <i class="ri-more-fill"></i>
          <span data-key="t-registries">Anagrafiche</span>
        </li>

        <!-- Veicoli -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/vehicles" class="nav-link menu-link">
            <i class="ri-car-line"></i>
            <span>Veicoli</span>
          </Link>
        </li>

        <!-- Driver -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/drivers" class="nav-link menu-link">
            <i class="ri-steering-2-line"></i>
            <span>Driver</span>
          </Link>
        </li>

        <!-- Committenti -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/committenti" class="nav-link menu-link">
            <i class="ri-building-4-line"></i>
            <span>Committenti</span>
          </Link>
        </li>

        <!-- Fornitori -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/fornitori" class="nav-link menu-link">
            <i class="ri-store-2-line"></i>
            <span>Fornitori</span>
          </Link>
        </li>

        <!-- ZTL -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/dictionaries/ztl" class="nav-link menu-link">
            <i class="ri-road-map-line"></i>
            <span>ZTL</span>
          </Link>
        </li>

        <!-- Aziende (solo per super-admin) -->
        <li class="nav-item" v-if="isSuperAdmin">
          <Link href="/easyncc/companies" class="nav-link menu-link">
            <i class="ri-building-line"></i>
            <span>Aziende</span>
          </Link>
        </li>

        <!-- CONFIGURAZIONE -->
        <li class="menu-title" v-if="canManage">
          <i class="ri-more-fill"></i>
          <span data-key="t-configuration">Configurazione</span>
        </li>

        <!-- Utenti -->
        <li class="nav-item" v-if="canManage">
          <Link href="/easyncc/users" class="nav-link menu-link">
            <i class="ri-user-line"></i>
            <span>Utenti</span>
          </Link>
        </li>

        <!-- Dizionari -->
        <li class="nav-item" v-if="isAdmin">
          <a class="nav-link menu-link" href="#sidebarDictionaries" data-bs-toggle="collapse" role="button" aria-expanded="false"
            aria-controls="sidebarDictionaries">
            <i class="ri-book-2-line"></i>
            <span>Dizionari</span>
          </a>
          <div class="collapse menu-dropdown" id="sidebarDictionaries">
            <ul class="nav nav-sm flex-column">
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/dress-codes" class="nav-link">
                  Dress Codes
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/payment-types" class="nav-link">
                  Tipologie Pagamento
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/driver-attachment-types" class="nav-link">
                  Allegati Driver
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/vehicle-attachment-types" class="nav-link">
                  Allegati Veicoli
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/service-statuses" class="nav-link">
                  Stati Servizi
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/activity-types" class="nav-link">
                  Tipologie di Esperienze
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/service-types" class="nav-link">
                  Tipologie di Servizio
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/accounting-entries" class="nav-link">
                  Causali Contabili
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/transaction-statuses" class="nav-link">
                  Stato Movimenti
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/leave-types" class="nav-link">
                  Tipologie Congedo
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/dictionaries/vehicle-unavailability-types" class="nav-link">
                  Tipologie Non Disp. Veicoli
                </Link>
              </li>
            </ul>
          </div>
        </li>

        <!-- Impostazioni (solo per admin e super-admin) -->
        <li class="nav-item" v-if="isAdmin">
          <a class="nav-link menu-link" href="#sidebarSettings" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSettings">
            <i class="ri-settings-3-line"></i>
            <span>Impostazioni</span>
          </a>
          <div class="collapse menu-dropdown" id="sidebarSettings">
            <ul class="nav nav-sm flex-column">
              <li class="nav-item">
                <Link href="/easyncc/settings" class="nav-link">
                  Generali
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/settings/telegram" class="nav-link">
                  Telegram Bot
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/telegram/users" class="nav-link">
                  Utenti Telegram
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/settings/pricing" class="nav-link">
                  Preventivi
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/settings/quote-email-templates" class="nav-link">
                  Template Email Preventivi
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/settings/sumup" class="nav-link">
                  SumUp Merchant
                </Link>
              </li>
              <li class="nav-item">
                <Link href="/easyncc/settings/gmail" class="nav-link">
                  Account Gmail
                </Link>
              </li>
            </ul>
          </div>
        </li>

        <!-- Assenze e Non Disponibilità (primo livello, solo admin e super-admin) -->
        <li class="nav-item" v-if="isAdmin">
          <Link href="/easyncc/settings/driver-unavailabilities" class="nav-link menu-link">
            <i class="ri-user-unfollow-line"></i>
            <span>Assenze Driver</span>
          </Link>
        </li>
        <li class="nav-item" v-if="isAdmin">
          <Link href="/easyncc/settings/vehicle-unavailabilities" class="nav-link menu-link">
            <i class="ri-car-washing-line"></i>
            <span>Non Disp. Veicoli</span>
          </Link>
        </li>

        <!-- Cestino (solo per admin e super-admin) -->
        <li class="nav-item" v-if="isAdmin">
          <Link href="/easyncc/trash" class="nav-link menu-link">
            <i class="ri-delete-bin-line"></i>
            <span>Cestino</span>
          </Link>
        </li>
    </ul>
  </template>
</BContainer></template>
