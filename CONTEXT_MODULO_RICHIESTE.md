# Modulo Richieste NCC — Documento di contesto

> Questo documento riassume le decisioni architetturali, il modello dati e i requisiti
> funzionali del nuovo modulo di gestione richieste per un'applicazione gestionale NCC
> (Noleggio Con Conducente). È destinato a Claude Code per supportare l'analisi del
> codice esistente e la progettazione del nuovo modulo.

---

## 1. Contesto applicativo

L'applicazione esistente gestisce un'azienda NCC in modo completo: dalla pianificazione
dei servizi alla contabilità. L'unità concettuale centrale è il **Servizio** — un
trasporto o un'esperienza turistica con un pickup e un dropoff. Il sistema attuale
gestisce già:

- Servizi (con macchina a stati e flusso di approvazione)
- Preventivi per singolo servizio (con macchina a stati: bozza → approvazione → inviato → confermato)
- Utenti con ruoli (tra cui il ruolo `committente`)
- Fatturazione e contabilità

Il nuovo modulo si innesta su questi elementi **senza modificarli**, aggiungendo un
livello superiore che gestisce le richieste in entrata prima che diventino servizi.

---

## 2. Obiettivo del nuovo modulo

Gestire il ciclo di vita di una richiesta cliente dall'arrivo (email, web form, telefono)
fino alla creazione automatica dei servizi nel sistema esistente, passando per la
produzione del preventivo, l'approvazione interna, l'invio al cliente e il pagamento
del deposito.

---

## 3. Decomposizione in sottomoduli

| # | Sottomodulo | Responsabilità | Integrazione esistente |
|---|---|---|---|
| 1 | **Gestione richieste** | Ricezione, ingestion, estrazione LLM, contacts, righe, thread Gmail | Nessuna — completamente nuovo |
| 2 | **Produzione preventivo** | Righe preventivo, prezzi, versioni, deposito | Estende il preventivo esistente |
| 3 | **Flusso approvazione** | Macchina a stati, permessi per ruolo, storico transizioni | Mutuato dal flusso preventivo esistente |
| 4 | **Invio** | Email multilingua via Gmail API, bozza AI, scadenza, sollecito | Gmail API esistente |
| 5 | **Pagamento** | Registrazione deposito, verifica/creazione utente, email conferma, generazione servizi | Tabella `users`, tabella `servizi` |

### Infrastruttura condivisa (serve tutti i sottomoduli)

- Gmail API + Google Cloud Pub/Sub + Webhook di ingestion
- LLM (Anthropic API) per estrazione e classificazione
- Tabella `contacts` e `thread_email`
- Sistema di notifiche operatore
- Audit trail / timeline eventi

---

## 4. Modello dati — entità nuove

### 4.1 `contacts`

Anagrafica leggera per i mittenti delle richieste, prima che diventino utenti del
sistema. **Tabella completamente nuova, isolata dalla tabella `users` esistente.**

```sql
contacts
  id                uuid        PK
  email             string      UNIQUE NOT NULL
  nome              string
  cognome           string
  telefono          string
  lingua            string      -- codice ISO: it, en, de, fr…
  fonte             enum        -- email | web_form | telefono | manuale
  note              text
  user_id           uuid        nullable FK → users.id  (popolato alla promozione)
  created_at        timestamp
  updated_at        timestamp
```

**Regola chiave:** `user_id` è null finché il contatto non paga il deposito. Al pagamento
si cerca un utente per email in `users`; se esiste si scrive l'id, altrimenti si crea
l'utente committente e si scrive l'id del nuovo record.

---

### 4.2 `richieste`

L'entità centrale del modulo. Aggrega più servizi richiesti da un cliente.

```sql
richieste
  id                uuid        PK
  contact_id        uuid        NOT NULL FK → contacts.id
  fonte             enum        -- email | web_form | telefono | manuale
  stato             enum        -- nuova | in_lavorazione | preventivata |
                                --   confermata | completata | annullata | sospesa
  data_ricezione    timestamp
  note              text
  origine_id        uuid        nullable FK → richieste.id  (usato per split/merge)
  relazione         enum        nullable  -- sdoppiata_da | unita_con
  created_at        timestamp
  updated_at        timestamp
```

---

### 4.3 `righe_richiesta`

I servizi richiesti dal cliente in forma grezza. Non cambiano tra versioni di preventivo.

```sql
righe_richiesta
  id                uuid        PK
  richiesta_id      uuid        NOT NULL FK → richieste.id
  ordinamento       integer     -- ordinato per data_servizio
  data_servizio     date        NOT NULL
  ora_pickup        time        nullable
  tipo_servizio     enum        -- trasferimento | tour | esperienza | altro
  pickup            string      NOT NULL
  dropoff           string      NOT NULL
  passeggeri        integer
  veicolo_preferito string      nullable
  note              text        nullable
  created_at        timestamp
```

---

### 4.4 `thread_email`

Collega uno o più thread Gmail a una richiesta. Una richiesta può avere N thread.

```sql
thread_email
  id                    uuid        PK
  richiesta_id          uuid        NOT NULL FK → richieste.id
  thread_id_gmail       string      NOT NULL
  casella               string      -- indirizzo email della casella aziendale
  ultimo_messaggio_at   timestamp
  created_at            timestamp
```

---

### 4.5 `preventivi_pacchetto`

Versione del preventivo per una richiesta. Solo uno alla volta è `attivo = true`.

```sql
preventivi_pacchetto
  id                uuid        PK
  richiesta_id      uuid        NOT NULL FK → richieste.id
  versione          integer     NOT NULL
  attivo            boolean     NOT NULL DEFAULT false
  stato             enum        -- bozza | in_approvazione | inviato |
                                --   confermato | annullato | scaduto
  totale            decimal
  deposito          decimal
  scadenza          date        nullable
  note_cliente      text        nullable
  created_at        timestamp
  updated_at        timestamp
```

**Vincolo di integrità** — al massimo un preventivo attivo per richiesta:

```sql
CREATE UNIQUE INDEX idx_preventivo_attivo
ON preventivi_pacchetto (richiesta_id)
WHERE attivo = true;
```

---

### 4.6 `righe_preventivo_pacchetto`

Righe di prezzo del preventivo. Corrispondono alle righe richiesta ma sono entità
separate: cambiano i prezzi, non la richiesta originale.

```sql
righe_preventivo_pacchetto
  id                    uuid        PK
  preventivo_id         uuid        NOT NULL FK → preventivi_pacchetto.id
  riga_richiesta_id     uuid        NOT NULL FK → righe_richiesta.id
  descrizione           string
  prezzo_netto          decimal
  aliquota_iva          decimal
  prezzo_lordo          decimal     -- calcolato
  note                  text        nullable
  ordinamento           integer
```

---

### 4.7 `mailbox_registry`

Configurazione delle caselle Gmail monitorate.

```sql
mailbox_registry
  id                  uuid        PK
  email               string      UNIQUE NOT NULL
  oauth_token         text        -- encrypted
  refresh_token       text        -- encrypted
  history_id          string      -- cursore Gmail per messaggi non processati
  watch_scadenza      timestamp   -- scade ogni 7 giorni, rinnovare via cron
  operatore_default   uuid        nullable FK → users.id
  filtra_mittenti     boolean     DEFAULT false
  attiva              boolean     DEFAULT true
  created_at          timestamp
```

---

## 5. Relazioni tra entità nuove ed esistenti

```
contacts             (NEW)  1──N  richieste              (NEW)
richieste            (NEW)  1──N  righe_richiesta         (NEW)
richieste            (NEW)  1──N  thread_email            (NEW)
richieste            (NEW)  1──N  preventivi_pacchetto    (NEW)
preventivi_pacchetto (NEW)  1──N  righe_preventivo_pacchetto (NEW)
righe_richiesta      (NEW)  1──N  righe_preventivo_pacchetto (NEW)
preventivi_pacchetto (NEW)  1──N  servizi                 (EXISTING — generati alla conferma)
contacts             (NEW)  N──1  users                   (EXISTING — FK opzionale)
```

---

## 6. Macchina a stati — Preventivo pacchetto

Mutuata dalla macchina a stati del preventivo singolo esistente, con due differenze:

1. **`confermato`** richiede come prerequisito hard il deposito registrato
2. **`scaduto`** è uno stato nuovo, generato automaticamente al superamento della `scadenza`

```
                    ┌──────────────┐
              ┌────►│    bozza     │◄─── nuova versione dopo rinegoziazione
              │     └──────┬───────┘
              │            │ invia in approvazione
              │     ┌──────▼───────┐
   rigetta    │     │in_approvaz.  │
              │     └──────┬───────┘
              │            │ approva
              │     ┌──────▼───────┐
              │     │   inviato    │──────────────────────┐
              │     └──────┬───────┘                      │ scadenza superata
              │            │ deposito registrato           │
              │     ┌──────▼───────┐               ┌──────▼──────┐
              │     │  confermato  │               │   scaduto   │
              │     └──────┬───────┘               └─────────────┘
              │            │
              │     ┌──────▼───────┐
              │     │  [servizi]   │ → flusso esistente (bozza → approvazione → attivo)
              │     └─────────────-┘
              │
        annullato ← da qualsiasi stato tranne confermato
```

Quando un preventivo viene annullato per rinegoziazione:
- Il preventivo corrente passa a `stato = annullato`, `attivo = false`
- Si crea un nuovo record con `versione + 1`, `attivo = true`, `stato = bozza`

---

## 7. Macchina a stati — Richiesta

```
nuova → in_lavorazione → preventivata → confermata → completata

Da qualsiasi stato → annullata
Da qualsiasi stato → sospesa
```

La richiesta passa a `confermata` automaticamente quando il preventivo pacchetto
attivo passa a `confermato`.

---

## 8. Integrazione Gmail

### Approccio scelto: ingestion push via Google Cloud Pub/Sub

- Ogni casella in `mailbox_registry` ha un `watch()` attivo che punta allo stesso topic Pub/Sub
- Pub/Sub invia notifiche HTTP al webhook del gestionale
- Il webhook riceve `emailAddress` + `historyId`, recupera i messaggi nuovi via Gmail API
- `watch()` scade ogni 7 giorni — un cron job lo rinnova ogni 6 giorni
- Il payload Pub/Sub contiene `emailAddress` per disambiguare la casella di origine

### Pipeline di ingestion

**Filtro 1 — strutturale (senza LLM, a costo zero):**
- `threadId` in `thread_email` → aggiornamento richiesta esistente, skip filtri successivi
- Mittente in `users` → flusso committente noto, skip creazione contact
- Header `List-Unsubscribe` → scarta
- Mittente è indirizzo interno → scarta
- Casella con `filtra_mittenti = false` → passa tutto al filtro 2

**Filtro 2 — classificazione LLM (prompt leggero, non estrazione completa):**

Classi: `nuova_richiesta` | `aggiornamento` | `conferma` | `domanda_info` | `annullamento` | `altro`

**Filtro 3 — disambiguazione (solo se mittente noto con richieste aperte):**

Se il mittente ha più richieste aperte e il thread è nuovo, l'LLM confronta il
contenuto con le richieste aperte e decide se collegarlo a una esistente o crearne una
nuova. Confidenza < 85% → proposta all'operatore con i candidati evidenziati.

**Estrazione righe (solo per `nuova_richiesta` o `aggiornamento`):**

L'LLM estrae le righe servizio in JSON con campo `confidenza` per campo
(`alta` | `media` | `bassa`). I campi a bassa confidenza vengono evidenziati nel
form per revisione dell'operatore.

```json
[
  {
    "data": "2026-04-20",
    "confidenza_data": "alta",
    "ora_pickup": null,
    "confidenza_ora": "bassa",
    "pickup": "Aeroporto FCO",
    "confidenza_pickup": "alta",
    "dropoff": "Centro Roma",
    "confidenza_dropoff": "media",
    "passeggeri": 3,
    "tipo": "trasferimento"
  }
]
```

---

## 9. Gestione contatti e promozione a utente

### Principio fondamentale

- Le richieste puntano **sempre** a `contact_id` — prima, durante e dopo la promozione
- `contacts` e `users` sono **completamente isolate** — nessuna FK strutturale da `users`
- L'unico collegamento è `contacts.user_id` (nullable), popolato alla promozione
- La **duplicazione dell'anagrafica** al momento della promozione è accettata
  consapevolmente — evita qualsiasi modifica alla tabella `users` esistente

### Lookup per email in ingestion (ordine obbligatorio)

```
1. Cerca in `users` per email
   → trovato: usa il contesto utente committente

2. Cerca in `contacts` per email
   → trovato: collega alla richiesta esistente

3. Nessuno trovato
   → crea nuovo contact, poi crea richiesta
```

### Promozione al pagamento del deposito

```
1. Cerca utente per email in `users`
2. Se non esiste → crea utente con ruolo committente
                   copia anagrafica da contact (nome, cognome, email, telefono, lingua)
3. Scrivi contacts.user_id = users.id
4. Crea servizi nel sistema esistente (stato: bozza / da approvare)
5. Invia email di conferma al cliente
```

---

## 10. Utilities: Split e Merge

### Split richiesta

Divide una richiesta in due distribuendo le righe.

**Prerequisiti:** nessun preventivo con `stato = confermato`

**Comportamento:**
- L'operatore sceglie quali righe vanno in ciascuna delle due nuove richieste
- I thread email vengono copiati su entrambe le richieste figlie
- La richiesta origine: `stato = annullata`, `relazione = sdoppiata_da`
- Le richieste figlie: `origine_id → richiesta origine`
- I preventivi in bozza vanno riassegnati manualmente

### Merge richieste

Unisce due richieste in una.

**Prerequisiti:** nessuna delle due ha preventivi confermati o servizi aperti

**Comportamento:**
- Tutte le righe confluiscono nella nuova richiesta unificata
- Tutti i thread email vengono riassegnati alla nuova richiesta
- Le richieste origine: `stato = annullata`, `relazione = unita_con`
- I preventivi in bozza delle richieste origine vengono annullati
- Si ricomincia con un nuovo preventivo sulla richiesta unificata

---

## 11. Interfaccia operatore

### Inbox richieste (schermata principale)

Lista filtrata per stato con sezioni:
- **Da gestire:** richieste nuove e in lavorazione
- **In attesa risposta cliente:** preventivo inviato, in scadenza
- **Confermate di recente:** deposito ricevuto, servizi da approvare

Ogni card mostra: cliente, numero richiesta, stato, date, riepilogo servizi, lingua,
fonte, azione primaria contestuale (cambia in base allo stato).

### Dettaglio richiesta (due colonne su desktop)

**Colonna sinistra:**
- Testata cliente (dati contact + link a profilo)
- Tab: Righe richiesta / Comunicazioni / Timeline

**Colonna destra:**
- Pannello preventivo: stato-bar, righe con prezzi editabili, totali, deposito, note
- Storico versioni
- Il pannello si blocca in sola lettura quando il preventivo supera lo stato `bozza`

**Azioni contestuali (drawer/modale):**
Nuovo preventivo, Registra deposito, Split, Merge, Assegna operatore, Chiudi richiesta

### Pannello comunicazioni (tab nel dettaglio)

- Thread Gmail visualizzato in-app in ordine cronologico
- Tag LLM su ogni messaggio in entrata con campi estratti e confidenza
- Reply box con selettore lingua e bottone "Bozza AI"
- Le risposte vengono inviate via Gmail API come reply al thread originale

---

## 12. Domande da esplorare nel codice esistente

Verificare prima di iniziare lo sviluppo:

1. **Tabella `users`** — quali campi ha l'anagrafica? Ci sono validazioni che assumono
   credenziali obbligatorie o email verificata?

2. **Macchina a stati preventivo singolo** — come è implementata? È una libreria,
   un enum con transizioni esplicite, o logica custom? Si può estendere?

3. **Modello `Servizio`** — quali campi ha, quali stati, come è collegato al committente?
   Lo stato "bozza/da approvare" è già presente o va aggiunto?

4. **Sistema di ruoli e permessi** — come sono implementati? Chi può fare quali
   transizioni sul preventivo esistente?

5. **Gmail API** — è già integrata? Esistono token OAuth già gestiti nell'app?

6. **Sistema di notifiche in-app** — esiste già un meccanismo di notifica per
   l'operatore? Possiamo riusarlo?

7. **Preventivo singolo** — quali tabelle usa, come sono strutturate le righe,
   come gestisce IVA e totali? Il `preventivo_pacchetto` deve essere compatibile.

8. **Audit trail** — esiste già un sistema di logging delle azioni sulle entità?
   Possiamo riusarlo per la timeline della richiesta?

---

## 13. Vincoli e principi da rispettare

- **Nessuna modifica alla tabella `users`** — il sistema esistente non va alterato
- **Nessuna modifica al flusso servizi esistente** — i servizi creati entrano nel
  flusso esistente senza alterazioni
- **Il preventivo singolo esistente non va riscritto** — il nuovo modulo lo affianca
- **LLM non agisce mai senza supervisione** — estrazione e classificazione sono sempre
  proposte all'operatore, mai applicate automaticamente senza conferma esplicita
- **Unico topic Pub/Sub per tutte le caselle Gmail** — `emailAddress` nel payload
  disambigua la casella di origine
- **`watch()` Gmail va rinnovato ogni 6 giorni** via cron — se scade l'ingestion
  si interrompe silenziosamente senza errori evidenti
