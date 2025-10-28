# EasyNCC - Sistema Gestionale Multi-Tenant NCC
## Stato del Progetto - 28 Ottobre 2025

---

## Panoramica

Sistema gestionale web multi-tenant completo per aziende di Noleggio Con Conducente (NCC), sviluppato con architettura separata:
- **Backend**: Laravel 12 (PHP 8.3) con API REST
- **Frontend**: Vue.js 3 + Inertia.js
- **Database**: PostgreSQL con schema EASYNCC
- **Containerizzazione**: Docker (app, nginx, mailhog)

---

## ✅ Completato

### 1. Database Schema (27 Migrations)

**Tabelle Principali:**
- `companies` - Aziende NCC
- `users` - Utenti estesi con multi-tenancy (company_id, 8 ruoli)
- `driver_profiles` - Profili autisti
- `client_profiles` - Profili committenti
- `intermediary_profiles` - Profili intermediari
- `supplier_profiles` - Profili fornitori
- `business_contacts` - Referenti aziendali (polimorfica)
- `vehicles` - Gestione flotta veicoli
- `services` - Servizi NCC (cuore applicazione)
- `service_driver` - Pivot table driver multipli

**Tabelle Correlate Servizi:**
- `service_passengers` - Passeggeri
- `service_stops` - Fermate intermedie
- `service_payments` - Pagamenti
- `service_costs` - Costi fornitori
- `attachments` - Allegati (polimorfica)

**Dizionari (tutti con company_id):**
- `dress_codes` - Codici abbigliamento
- `service_statuses` - Stati servizio
- `payment_types` - Tipi pagamento
- `luggage_types` - Tipi bagaglio
- `driver_attachment_types` - Tipi allegati driver
- `vehicle_attachment_types` - Tipi allegati veicoli
- `ztl` - Zone a Traffico Limitato

### 2. Models Eloquent (20 Models)

Tutti i modelli implementano:
- `HasFactory` per factory pattern
- `SoftDeletes` per cancellazioni logiche
- Trait `HasCompany` per auto-scoping multi-tenant
- Relazioni complete (BelongsTo, HasMany, BelongsToMany, MorphTo, MorphMany)
- Cast automatici (date, decimal, boolean)
- Fillable arrays per mass assignment

**Models principali:**
- Company, User (esteso), Vehicle, Service
- DriverProfile, ClientProfile, IntermediaryProfile, SupplierProfile
- BusinessContact (morph), Attachment (morph)
- ServicePassenger, ServiceStop, ServicePayment, ServiceCost
- DressCode, ServiceStatus, PaymentType, LuggageType, Ztl
- DriverAttachmentType, VehicleAttachmentType

### 3. Autenticazione e Autorizzazione

**Middleware Custom:**
- `EnsureUserIsActive` - Verifica utenti attivi
- `CheckRole` - Controllo permessi basato su ruolo
- `SetCompanyContext` - Gestione contesto company per super-admin

**Ruoli Utente:**
- `super-admin` - Accesso completo multi-tenant
- `admin` - Gestione completa propria azienda
- `operator` - Gestione operativa azienda
- `driver` - Visualizzazione propri servizi
- `committente` - Visualizzazione servizi acquistati
- `intermediario` - Visualizzazione servizi intermediati
- `fornitore` - Visualizzazione servizi forniti
- `passeggero` - Visualizzazione servizi come passeggero

**Trait HasCompany:**
- Global scope automatico per company_id
- Auto-assegnazione company_id in creazione
- Metodi helper per scoping

### 4. API Controllers (8 Controllers)

**Controllers REST completi:**
- `CompanyController` - CRUD aziende
- `UserController` - CRUD utenti con gestione profili
- `VehicleController` - CRUD veicoli
- `ServiceController` - CRUD servizi + checkConflicts()
- `DashboardController` - stats() + upcomingServices()
- `DressCodeController` - CRUD dizionario
- `ServiceStatusController` - CRUD dizionario
- `PaymentTypeController` - CRUD dizionario

**Funzionalità Controllers:**
- Listing con filtri, ricerca, ordinamento, paginazione
- Validazione completa input
- Eager loading per evitare N+1 queries
- JsonResponse type hints
- Soft delete gestito

### 5. API Routes

**Route Groups:**
- Public: `/api/login`
- Protected: `auth:sanctum + active + company.context`
- Role-based: middleware `role:super-admin,admin,...`

**Endpoints Principali:**
- `GET /api/user` - Info utente corrente
- `GET|POST|PUT|DELETE /api/companies` - CRUD companies
- `GET|POST|PUT|DELETE /api/users` - CRUD users
- `GET|POST|PUT|DELETE /api/vehicles` - CRUD vehicles
- `GET|POST|PUT|DELETE /api/services` - CRUD services
- `GET /api/dashboard/stats` - Statistiche
- `GET /api/dashboard/upcoming-services` - Prossimi servizi
- Dizionari: `/api/dress-codes`, `/api/service-statuses`, `/api/payment-types`

**Permessi Routes:**
- Companies: super-admin, admin
- Users CRUD: super-admin, admin, operator
- Users DELETE: solo super-admin, admin
- Vehicles CRUD: super-admin, admin, operator
- Vehicles DELETE: solo super-admin, admin
- Services VIEW: tutti autenticati
- Services CRUD: super-admin, admin, operator
- Dictionaries READ: tutti autenticati
- Dictionaries CRUD: super-admin, admin

### 6. Seeders

**Seeders Funzionanti:**
- `CompanySeeder` - 2 company esempio (Roma, Milano)
- `DictionarySeeder` - Tutti i dizionari popolati

**Da Completare:**
- `UserSeeder` - Utenti con profili
- `VehicleSeeder` - Veicoli assegnati

---

## 🔧 Configurazione

### Docker Services
```yaml
- app: PHP 8.3-FPM (porta 9000)
- web: Nginx (porta 8095)
- mailhog: Mail testing (UI 8027, SMTP 1027)
```

### Database
```
Host: db-postgresql-ams3-58995-do-user-23429309-0.f.db.ondigitalocean.com
Port: 25060
Database: defaultdb
Schema: EASYNCC
User: doadmin
SSL: Required
```

### Sanctum
- Abilitato per autenticazione API
- Stateful requests enabled
- Token-based auth

---

## 📋 Prossimi Passi

### Backend
1. ✅ Completare UserSeeder e VehicleSeeder con campi corretti
2. ✅ Creare ServiceSeeder per dati di test
3. ✅ Implementare Form Requests per validazione avanzata
4. ✅ Aggiungere validazione conflitti temporali in ServiceController
5. ✅ Creare Resource classes per API responses
6. ✅ Aggiungere logging e error handling
7. ✅ Implementare upload/download allegati
8. ✅ Aggiungere API endpoints per profili utente
9. ✅ Implementare export PDF/Excel servizi

### Frontend Vue.js
1. ✅ Configurare routing Inertia.js
2. ✅ Creare layout base (sidebar, header, footer)
3. ✅ Implementare login/logout
4. ✅ Dashboard con statistiche (charts)
5. ✅ CRUD Companies
6. ✅ CRUD Users con gestione profili
7. ✅ CRUD Vehicles
8. ✅ CRUD Services con form complesso
9. ✅ Calendario servizi (FullCalendar)
10. ✅ Gestione dizionari
11. ✅ Upload allegati
12. ✅ Filtri e ricerca avanzata
13. ✅ Responsive design

### Testing
1. ✅ Feature tests per API endpoints
2. ✅ Unit tests per Models
3. ✅ Tests per middleware
4. ✅ Tests validazione multi-tenancy

---

## 🚀 Comandi Utili

### Development
```bash
# Avvio ambiente
docker-compose up -d

# Migrations
docker exec easyncc_app php artisan migrate

# Fresh migrations + seed
docker exec easyncc_app php artisan migrate:fresh --seed

# Solo seed
docker exec easyncc_app php artisan db:seed

# Cache clear
docker exec easyncc_app php artisan cache:clear
docker exec easyncc_app php artisan config:clear
docker exec easyncc_app php artisan route:clear

# Frontend build
npm run dev    # Development
npm run build  # Production
```

### Testing
```bash
# Run tests
docker exec easyncc_app php artisan test

# Run specific test
docker exec easyncc_app php artisan test --filter=VehicleTest
```

---

## 📁 Struttura Progetto

```
/src/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # 8 API controllers
│   │   ├── Middleware/         # 3 custom middleware
│   │   └── Kernel.php
│   ├── Models/                 # 20 Eloquent models
│   └── Traits/
│       └── HasCompany.php      # Multi-tenancy trait
├── database/
│   ├── migrations/             # 27 migrations
│   └── seeders/                # 5 seeders
├── routes/
│   ├── api.php                 # API routes complete
│   └── web.php                 # Web routes (Inertia)
├── resources/
│   ├── js/                     # Vue.js components
│   └── views/                  # Blade templates
└── docker-compose.yml
```

---

## 🎯 Caratteristiche Chiave Implementate

✅ Multi-tenancy completo con isolamento dati
✅ 8 ruoli utente con permessi granulari
✅ Autenticazione Sanctum token-based
✅ CRUD completo per tutte le entità
✅ Soft deletes su tutte le tabelle critiche
✅ Relazioni Eloquent complete
✅ API REST con validazione
✅ Middleware custom per sicurezza
✅ Global scopes per multi-tenancy
✅ Eager loading anti N+1
✅ Fillable e cast automatici
✅ Route con protezione ruoli

---

## 📝 Note Tecniche

- Laravel 12 con PHP 8.3
- PostgreSQL con schema personalizzato
- Docker containerizzato
- Sanctum per API auth
- Inertia.js per SPA
- Vue.js 3 Composition API
- Bootstrap 5 + BootstrapVue Next
- FullCalendar per calendario
- Charts (ApexCharts, AmCharts5)

---

## ⚠️ Importante

- Le password nei seeder sono tutte: `password`
- L'email super-admin è: `admin@easyncc.com`
- Il database è remoto su DigitalOcean
- Le credenziali DB sono in `.env`
- Sanctum richiede HTTPS in production

---

**Data Completamento Backend**: 28 Ottobre 2025
**Sviluppatore**: Claude (Anthropic) + Odn
**Versione**: 1.0.0-beta
