# EasyNCC - Sistema Gestionale Multi-Tenant NCC

Sistema gestionale web completo per aziende di Noleggio Con Conducente (NCC) con architettura moderna e scalabile.

## Stack Tecnologico

### Backend
- **Framework**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL (schema EASYNCC)
- **Autenticazione**: Laravel Sanctum
- **API**: REST JSON

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **SPA**: Inertia.js
- **UI Framework**: Bootstrap 5 + BootstrapVue Next
- **Build Tool**: Vite
- **Librerie**: FullCalendar, ApexCharts, Moment.js

### Infrastructure
- **Containerizzazione**: Docker (app, nginx, mailhog)
- **Web Server**: Nginx
- **Mail Testing**: MailHog

---

## Caratteristiche Principali

### Multi-Tenancy Completo
- Isolamento dati per company
- 8 ruoli utente differenziati
- Super-admin può gestire tutte le aziende
- Global scope automatico per company_id

### Ruoli Utente

| Ruolo | Permessi |
|-------|----------|
| `super-admin` | Accesso completo a tutte le aziende |
| `admin` | Gestione completa propria azienda |
| `operator` | Gestione operativa azienda |
| `driver` | Visualizzazione propri servizi |
| `committente` | Visualizzazione servizi acquistati |
| `intermediario` | Visualizzazione servizi intermediati |
| `fornitore` | Visualizzazione servizi forniti |
| `passeggero` | Visualizzazione servizi come passeggero |

### Entità Gestite

#### Core
- **Companies** - Aziende NCC
- **Users** - Utenti con profili specifici per ruolo
- **Vehicles** - Flotta veicoli
- **Services** - Servizi NCC (cuore applicazione)

#### Profili Utente
- **DriverProfile** - Autisti con tariffe, colori, veicolo assegnato
- **ClientProfile** - Committenti con dati aziendali
- **IntermediaryProfile** - Intermediari con commissioni
- **SupplierProfile** - Fornitori con IBAN e metodi pagamento

#### Dati Servizio
- **ServicePassenger** - Passeggeri
- **ServiceStop** - Fermate intermedie
- **ServicePayment** - Pagamenti (acconti/saldi)
- **ServiceCost** - Costi fornitori
- **Attachment** - Allegati (polimorfica)

#### Dizionari
- DressCode, ServiceStatus, PaymentType
- LuggageType, DriverAttachmentType, VehicleAttachmentType
- ZTL (Zone a Traffico Limitato)

---

## Struttura Progetto

```
/src/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # 8 API REST controllers
│   │   │   └── EasyNCC/          # 4 Web Inertia controllers
│   │   ├── Middleware/           # 3 custom middleware
│   │   └── Kernel.php
│   ├── Models/                   # 20 Eloquent models
│   └── Traits/
│       └── HasCompany.php        # Multi-tenancy trait
│
├── database/
│   ├── migrations/               # 27 migrations
│   └── seeders/                  # 4 seeders
│
├── resources/
│   ├── js/
│   │   ├── Pages/EasyNCC/       # Pagine Vue.js
│   │   │   ├── Dashboard/       # Dashboard con statistiche
│   │   │   ├── Vehicles/        # CRUD veicoli
│   │   │   ├── Services/        # CRUD servizi + calendario
│   │   │   └── Users/           # CRUD utenti
│   │   ├── Layouts/             # Layout applicazione
│   │   └── Components/          # Componenti riusabili
│   └── scss/                    # Stili CSS/SASS
│
├── routes/
│   ├── api.php                  # API REST routes
│   └── web.php                  # Web Inertia routes
│
└── docker-compose.yml           # Configurazione Docker
```

---

## Installazione e Setup

### Prerequisiti
- Docker & Docker Compose
- Node.js 18+ & NPM

### 1. Clone e Setup

```bash
cd "/Users/Odn/Documents/Lavori O(n)/EasyNCC-Veridatis"

# Avvia Docker
docker-compose up -d

# Installa dipendenze PHP
docker exec easyncc_app composer install

# Installa dipendenze Node
npm install
```

### 2. Configurazione Database

Il file `.env` è già configurato con il database PostgreSQL su DigitalOcean:

```env
DB_CONNECTION=pgsql
DB_HOST=db-postgresql-ams3-58995-do-user-23429309-0.f.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=defaultdb
DB_SCHEMA=EASYNCC
DB_SSLMODE=require
```

### 3. Migrations e Seeding

```bash
# Esegui migrations
docker exec easyncc_app php artisan migrate

# Popola database con dati di test
docker exec easyncc_app php artisan db:seed

# O esegui fresh migration + seed
docker exec easyncc_app php artisan migrate:fresh --seed
```

### 4. Build Frontend

```bash
# Development
npm run dev

# Production
npm run build
```

---

## Accesso Applicazione

### URLs
- **Web App**: http://localhost:8095
- **MailHog UI**: http://localhost:8027
- **API Base**: http://localhost:8095/api

### Credenziali Default

Dopo il seeding, usa queste credenziali per il login:

```
Email: admin@easyncc.com
Password: password
Ruolo: super-admin
```

---

## API Endpoints

### Autenticazione
```
POST   /api/login              # Login utente
GET    /api/user               # Info utente corrente
```

### Dashboard
```
GET    /api/dashboard/stats                # Statistiche
GET    /api/dashboard/upcoming-services    # Prossimi servizi
```

### Companies
```
GET    /api/companies          # Lista aziende
POST   /api/companies          # Crea azienda
GET    /api/companies/{id}     # Dettaglio azienda
PUT    /api/companies/{id}     # Aggiorna azienda
DELETE /api/companies/{id}     # Elimina azienda
```

### Users
```
GET    /api/users              # Lista utenti
POST   /api/users              # Crea utente
GET    /api/users/{id}         # Dettaglio utente
PUT    /api/users/{id}         # Aggiorna utente
DELETE /api/users/{id}         # Elimina utente

Filtri: ?search=xxx&role=xxx&is_active=1
```

### Vehicles
```
GET    /api/vehicles           # Lista veicoli
POST   /api/vehicles           # Crea veicolo
GET    /api/vehicles/{id}      # Dettaglio veicolo
PUT    /api/vehicles/{id}      # Aggiorna veicolo
DELETE /api/vehicles/{id}      # Elimina veicolo

Filtri: ?search=xxx&status=in_service
```

### Services
```
GET    /api/services           # Lista servizi
POST   /api/services           # Crea servizio
GET    /api/services/{id}      # Dettaglio servizio
PUT    /api/services/{id}      # Aggiorna servizio
DELETE /api/services/{id}      # Elimina servizio

Filtri: ?search=xxx&status=xxx&date_from=2025-01-01&date_to=2025-12-31
```

### Dizionari
```
GET    /api/dress-codes
GET    /api/service-statuses
GET    /api/payment-types
```

---

## Web Routes (Inertia.js)

### Dashboard
```
GET    /easyncc                # Dashboard principale
```

### Veicoli
```
GET    /easyncc/vehicles                # Lista veicoli
GET    /easyncc/vehicles/create         # Form nuovo veicolo
GET    /easyncc/vehicles/{id}/edit      # Form modifica veicolo
```

### Servizi
```
GET    /easyncc/services                # Lista servizi
GET    /easyncc/services/calendar       # Calendario servizi
GET    /easyncc/services/create         # Form nuovo servizio
GET    /easyncc/services/{id}/edit      # Form modifica servizio
```

### Utenti
```
GET    /easyncc/users                   # Lista utenti
GET    /easyncc/users/create            # Form nuovo utente
GET    /easyncc/users/{id}/edit         # Form modifica utente
```

---

## Middleware

### Custom Middleware

**EnsureUserIsActive**
- Verifica che l'utente sia attivo (`is_active = true`)
- Logout automatico se inattivo

**CheckRole**
- Controlla permessi basati su ruolo
- Uso: `middleware('role:admin,operator')`

**SetCompanyContext**
- Gestisce contesto company per super-admin
- Super-admin può switchare company via header `X-Company-Id`

---

## Models e Relazioni

### Trait HasCompany

Applicato a: Vehicle, Service, Dizionari

```php
// Global scope automatico
$vehicles = Vehicle::all(); // Solo veicoli della company dell'utente

// Super-admin vede tutti
Auth::user()->role === 'super-admin' // Vede tutto
```

### Relazioni Chiave

**User**
- `belongsTo` Company
- `hasOne` DriverProfile/ClientProfile/IntermediaryProfile/SupplierProfile
- `hasMany` clientServices, intermediaryServices, supplierServices
- `belongsToMany` driverServices (pivot service_driver)

**Service**
- `belongsTo` Company, Vehicle, Client, Intermediary, Supplier, DressCode, Status
- `belongsToMany` Drivers (pivot service_driver)
- `hasMany` Passengers, Stops, Payments, Costs

**Vehicle**
- `belongsTo` Company
- `hasMany` AssignedDrivers (DriverProfile), Services

---

## Validazione Conflitti Temporali

Il sistema verifica automaticamente i conflitti temporali quando si crea/modifica un servizio:

```php
// ServiceController::checkConflicts()
- Verifica sovrapposizione orari veicolo (se allow_overlapping=false)
- Verifica sovrapposizione orari driver (se allow_overlapping=false)
- Esclude servizi cancellati/completati
- Esclude il servizio stesso in modifica
```

---

## Frontend - Pagine Vue.js

### Dashboard
- 4 card statistiche (servizi totali, oggi, veicoli disponibili, driver disponibili)
- Tabella prossimi servizi
- API calls con axios

### Vehicles Index
- Tabella responsive
- Filtri: search, status
- Badge colorati per stato
- Azioni: Modifica, Elimina

### Vehicles Form
- 10 campi con validazione
- Toggle per allow_overlapping
- Select per status

### Services Index
- Filtri avanzati: search, status, date range
- Badge colorati per stati servizio
- Azioni: Visualizza, Modifica, Elimina

### Services Calendar
- Integrazione FullCalendar
- Eventi colorati per stato
- Modal dettagli al click
- Viste: mese, settimana, giorno

### Services Form
- Form complesso con 11 campi
- Select dinamici per cliente e veicolo
- Validazione date

### Users Index
- Filtri: search, role, is_active
- Badge per ruoli e stato
- Gestione permessi

### Users Form
- 9 campi con gestione password
- Select dinamici per company e role
- Toggle per is_active

---

## Database Seeding

### Dati Popolati

**CompanySeeder**
- NCC Roma Luxury
- Milano Executive Transport

**DictionarySeeder** (per ogni company)
- 3 DressCode
- 6 ServiceStatus
- 3 PaymentType
- 2 LuggageType
- 3 DriverAttachmentType
- 3 VehicleAttachmentType
- 2 ZTL

---

## Comandi Utili

### Docker
```bash
# Avvia servizi
docker-compose up -d

# Stop servizi
docker-compose down

# Logs
docker-compose logs -f app
```

### Laravel
```bash
# Migrations
docker exec easyncc_app php artisan migrate
docker exec easyncc_app php artisan migrate:fresh --seed
docker exec easyncc_app php artisan migrate:rollback

# Cache
docker exec easyncc_app php artisan cache:clear
docker exec easyncc_app php artisan config:clear
docker exec easyncc_app php artisan route:clear

# Tinker
docker exec -it easyncc_app php artisan tinker
```

### NPM
```bash
# Development server
npm run dev

# Production build
npm run build

# Lint
npm run lint
```

---

## Testing

```bash
# Run all tests
docker exec easyncc_app php artisan test

# Run specific test file
docker exec easyncc_app php artisan test --filter=VehicleTest

# With coverage
docker exec easyncc_app php artisan test --coverage
```

---

## Sicurezza

### Implementato
✅ Autenticazione Sanctum token-based
✅ CSRF protection
✅ SQL injection prevention (Eloquent)
✅ XSS protection (Vue.js escaping)
✅ Rate limiting API
✅ Password hashing (bcrypt)
✅ Middleware multi-livello
✅ Soft deletes (audit trail)

### Raccomandazioni Production
- [ ] HTTPS obbligatorio
- [ ] Variabili environment sicure
- [ ] Database backup automatici
- [ ] Log monitoring
- [ ] Two-factor authentication
- [ ] API versioning

---

## Performance

### Ottimizzazioni Implementate
- Eager loading (no N+1 queries)
- Query scoping automatico
- Paginazione API
- Caching config/routes
- Asset minification (Vite)
- Lazy loading componenti Vue

---

## Troubleshooting

### Database Connection Error
```bash
# Verifica connessione
docker exec easyncc_app php artisan tinker
>>> DB::connection()->getPdo();
```

### Frontend non si compila
```bash
# Pulisci cache node_modules
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Permessi Docker
```bash
# Fix permessi storage/cache
docker exec easyncc_app chmod -R 775 storage bootstrap/cache
docker exec easyncc_app chown -R www-data:www-data storage bootstrap/cache
```

---

## Prossimi Sviluppi

### Backend
- [ ] Form Requests per validazione avanzata
- [ ] Resource classes per API responses
- [ ] Upload/download allegati
- [ ] Export PDF/Excel servizi
- [ ] Notifiche email
- [ ] API versioning

### Frontend
- [ ] Gestione dizionari completa
- [ ] Upload allegati driver/veicoli
- [ ] Profilo utente
- [ ] Notifiche real-time
- [ ] PWA support
- [ ] Dark mode

### Testing
- [ ] Feature tests API
- [ ] Unit tests Models
- [ ] Browser tests (Dusk)
- [ ] CI/CD pipeline

---

## Autori

- **Sviluppo**: Claude (Anthropic) + Odn
- **Data**: Ottobre 2025
- **Versione**: 1.0.0-beta

---

## Licenza

Proprietario - Tutti i diritti riservati

---

## Supporto

Per supporto o domande:
- Documentazione: [PROJECT_STATUS.md](PROJECT_STATUS.md)
- Repository: Privato



 Credenziali di Accesso
Super Admin (Accesso completo al sistema)
Email: admin@easyncc.com
Password: password
Ruolo: Super-admin
Descrizione: Amministratore di sistema con accesso a tutte le funzionalità
Amministratore Azienda
Email: nccromaluxury@easyncc.com
Password: password
Ruolo: Admin
Descrizione: Amministratore della compagnia "NCC Roma Luxury"
Operatore
Email: operator1@easyncc.com
Password: password
Ruolo: Operator
Descrizione: Operatore della centrale operativa
Driver
Email: marco.rossi1@easyncc.com
Password: password
Ruolo: Driver
Descrizione: Autista Marco Rossi