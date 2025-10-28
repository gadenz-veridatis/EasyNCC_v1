# Pagine CRUD Vue.js EasyNCC - Riepilogo Creazione

Data: 28/10/2025
Totale linee di codice: 1710

## Pagine Create

### 1. VEHICLES (Veicoli)

#### Vehicles/Index.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Vehicles/Index.vue`
- **Funzionalità**:
  - Tabella responsive con colonne: targa, marca, modello, passeggeri, stato, azioni
  - Filtri real-time: search (targa/marca/modello), status dropdown
  - Pulsante "Nuovo Veicolo" con icona
  - Azioni: Modifica (btn soft-primary), Elimina (btn soft-danger)
  - Loading spinner durante caricamento
  - Messaggi di errore
  - Badge colorati per stato (attivo=green, manutenzione=yellow, inattivo=red)
- **API**: `GET /api/vehicles` (con params: search, status)
- **Componenti usati**: Layout vertical.vue, PageHeader, Bootstrap Vue Next (BRow, BCol, BCard, BCardHeader, BCardBody, Link)

#### Vehicles/Form.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Vehicles/Form.vue`
- **Funzionalità**:
  - Form completo con 10 campi
  - Campi: license_plate, brand, model, passenger_capacity, purchase_date, ncc_license_number, license_city, allow_overlapping (checkbox), status, notes (textarea)
  - Validazione form con visualizzazione errori inline
  - Toggle switch per "Consenti Prenotazioni Sovrapposte"
  - Pulsante Submit con loading spinner
  - Link "Annulla" per tornare alla lista
  - Supporto create/edit (mode detection)
- **API**: `POST /api/vehicles` (create), `PUT /api/vehicles/{id}` (update)
- **Validazione**: Error handling 422 (validation errors)

### 2. SERVICES (Servizi)

#### Services/Index.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Services/Index.vue`
- **Funzionalità**:
  - Tabella con: riferimento, cliente, pickup, dropoff, data/ora, veicolo, stato, azioni
  - Filtri avanzati: search, status (6 stati), date_from, date_to
  - Pulsante "Nuovo Servizio"
  - Azioni: Visualizza (btn soft-primary), Modifica (btn soft-info), Elimina (btn soft-danger)
  - Badge colorati per stati (preventivo=warning, confermato=info, in corso=primary, completato=success, cancellato=danger, no-show=secondary)
  - Truncate dei testi lunghi con tooltip
- **API**: `GET /api/services` (con params: search, status, date_from, date_to)
- **Formattazione**: moment.js per date/time

#### Services/Form.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Services/Form.vue`
- **Funzionalità**:
  - Form con 11 campi
  - Campi: reference_number, status_id (select), client_id (select dinamico), vehicle_id (select dinamico), pickup_address, dropoff_address, pickup_datetime, dropoff_datetime, number_of_passengers, price, notes
  - Caricamento dinamico di clienti e veicoli dal backend
  - Validazione form completa
  - Pulsante Submit con loading spinner
- **API**: `POST /api/services`, `PUT /api/services/{id}`, GET `/api/clients`, GET `/api/vehicles?status=attivo`
- **Logica speciale**: Carica solo veicoli "attivi" per le prenotazioni

#### Services/Calendar.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Services/Calendar.vue`
- **Funzionalità**:
  - Integrazione FullCalendar (import dinamico)
  - Visualizzazione evento per servizio
  - Colori evento basati su stato del servizio
  - Click evento apre modal con dettagli
  - Modal mostra: cliente, stato, indirizzi, data/ora, veicolo, note
  - Button in modal per "Visualizza Dettagli" e "Modifica"
  - Toolbar: prev/next, today, view selector (month/week/day)
  - Locale italiano
- **API**: `GET /api/services`
- **Librerie**: @fullcalendar/vue3, @fullcalendar/daygrid, @fullcalendar/timegrid, @fullcalendar/interaction

### 3. USERS (Utenti)

#### Users/Index.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Users/Index.vue`
- **Funzionalità**:
  - Tabella con: nome, cognome, email, ruolo, azienda, stato, azioni
  - Filtri: search (nome/email), role (select), is_active (select)
  - Pulsante "Nuovo Utente"
  - Azioni: Modifica (btn soft-primary), Elimina (btn soft-danger)
  - Badge per ruoli colorati (admin=red, operatore=yellow, driver=info, cliente=primary)
  - Badge per stato (Attivo=green, Inattivo=red)
- **API**: `GET /api/users` (con params: search, role, is_active)

#### Users/Form.vue
- **Path**: `/src/resources/js/Pages/EasyNCC/Users/Form.vue`
- **Funzionalità**:
  - Form con 9 campi
  - Campi: first_name, last_name, email, role (select), company_id (select dinamico), password (solo create), password_confirmation (solo create), is_active (toggle switch), phone, notes (textarea)
  - Caricamento dinamico aziende
  - Logica: password visibile solo nella creazione
  - Toggle switch per "Utente Attivo"
  - Validazione completa
- **API**: `POST /api/users`, `PUT /api/users/{id}`, GET `/api/companies`
- **Logica speciale**: Password opzionale in edit (rimosso se vuoto)

## Architettura e Pattern

### Struttura File
```
src/resources/js/Pages/EasyNCC/
├── Dashboard/
│   └── Index.vue
├── Vehicles/
│   ├── Index.vue (Lista)
│   └── Form.vue (Create/Edit)
├── Services/
│   ├── Index.vue (Lista)
│   ├── Form.vue (Create/Edit)
│   └── Calendar.vue (Vista Calendario)
└── Users/
    ├── Index.vue (Lista)
    └── Form.vue (Create/Edit)
```

### Imports Standard
```javascript
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/vertical.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import moment from 'moment'; // (dove necessario)
```

### Bootstrap Vue Next Components Usati
- `BRow`, `BCol` - Layout grid
- `BCard`, `BCardHeader`, `BCardBody` - Card container
- `BLink` - Link component
- `BModal` - Modal (Services/Calendar.vue)

### Caratteristiche Comuni

1. **Layout Vertical**
   - Tutte le pagine usano `<Layout>` component dalla vertical.vue
   - PageHeader con title e breadcrumb

2. **Responsive Design**
   - Tabelle con `table-responsive` wrapper
   - BRow/BCol con breakpoints (md, lg)
   - Truncate testi lunghi su mobile

3. **Gestione Loading/Errori**
   - Spinner durante caricamento
   - Messaggi di errore con alert danger
   - Validazione form con errori inline

4. **API Calls**
   - axios per GET/POST/PUT/DELETE
   - Gestione 422 (validation errors)
   - Error console logging

5. **Filtraggio Real-time**
   - Input/Select con `@input`/`@change` che triggerano `loadData()`
   - Params passati direttamente a axios

6. **Colori e Styling**
   - Badge di stato con colori Bootstrap
   - Button con classi soft (btn-soft-primary, etc.)
   - Icone Remixicon (bx-*)

## Note Implementazione

### Routing
I nomi delle route utilizzate:
- `vehicles.index`, `vehicles.create`, `vehicles.edit`
- `services.index`, `services.create`, `services.edit`, `services.show`
- `users.index`, `users.create`, `users.edit`

### Dipendenze Richieste
```json
{
  "dependencies": {
    "@inertiajs/vue3": "^1.0.0",
    "bootstrap-vue-next": "^0.x.x",
    "axios": "^1.0.0",
    "moment": "^2.29.x"
  },
  "optionalDependencies": {
    "@fullcalendar/vue3": "^6.x.x",
    "@fullcalendar/daygrid": "^6.x.x",
    "@fullcalendar/timegrid": "^6.x.x",
    "@fullcalendar/interaction": "^6.x.x"
  }
}
```

### Backend API Endpoints Attesi
```
GET    /api/vehicles              (params: search, status)
POST   /api/vehicles              (create)
PUT    /api/vehicles/{id}         (update)
DELETE /api/vehicles/{id}         (delete)

GET    /api/services              (params: search, status, date_from, date_to)
POST   /api/services              (create)
PUT    /api/services/{id}         (update)
DELETE /api/services/{id}         (delete)

GET    /api/users                 (params: search, role, is_active)
POST   /api/users                 (create)
PUT    /api/users/{id}            (update)
DELETE /api/users/{id}            (delete)

GET    /api/clients               (per Services/Form.vue)
GET    /api/companies             (per Users/Form.vue)
```

## Statistiche
- **Pagine Create**: 8 Vue files
- **Linee di Codice**: 1710
- **Moduli**: 3 (Vehicles, Services, Users)
- **Viste**: 2x Index + 1x Form per modulo + 1x Calendar per Services

## Prossimi Passi
1. Verificare backend API endpoints
2. Configurare routing Laravel
3. Testare validazione form
4. Aggiungere toast notifications (opzionale)
5. Integrare FullCalendar se non già presente
