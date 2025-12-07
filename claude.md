Sistema Gestionale Multi-Tenant NCC - Specifiche per il Progetto

1.	Panoramica Generale
Sviluppa un sistema gestionale web per aziende di Noleggio Con Conducente (NCC) con architettura separata:
●	Backend: API REST in PHP Laravel 12
●	Frontend: Single Page Application in Vue.js 3 
●	Database: Postgres
L’applicazione gestionale deve gestire le anagrafiche degli utenti, dei veicoli e gestire l’agenda dei servizi come descritto nel resto del documento.

2.	Obiettivo
Creare un'applicazione multi-tenant dove ogni azienda NCC gestisce in modo isolato:
●	I propri utenti dell’applicazione
●	La propria flotta di automezzi
●	I propri conducenti
●	I propri clienti, fornitori e intermediari
●	I propri servizi di noleggio con conducente
●	I propri dizionari

L’applicazione deve gestire utenti con ruoli diversi, ognuno ha accessi e permessi diversificati come descritto nel resto del documento.

Il cuore dell’applicazione sono i servizi di noleggio con conducente che mettono insieme dati provenienti dalle anagrafiche e dai dizionari su base temporale. 

L’applicazione dovrà gestire l’agenda dei servizi sia come lista tabellare, che come calendario.

L’applicazione dovrà essere responsive.

3.	Requisiti Funzionali Chiave
3.1.	Multi-Tenancy:
●	Ogni azienda (company) ha dati isolati
●	Ogni azienda (company) ha utenti isolati
●	I super-amministratori di sistema possono accedere allo spazio di ogni company, scegliendo la company da gestire sul front end
●	Autenticazione JWT token-based
●	Tutte le query sono automaticamente filtrate per company_id dell'utente loggato o di quello selezionato dal super-amministratore

3.2.	Entità da gestire:
3.2.1.	Companies (Azienda)
L’entità COMPANIES raccoglie le informazioni relative alla singola azienda di noleggio con conducente, viene descritta dalle seguenti informazioni:
●	Identificativo univoco assegnato in automatico dal sistema
●	Nome azienda
●	Indirizzo email 
●	Numero di telefono
●	Partita IVA
●	SDI
●	PEC
●	indirizzo
●	sito web

3.2.2.	Users (Utenti)
3.2.2.1.	Tutti gli utenti
L’entità USERS raccoglie le informazioni relative agli utenti di sistema.
Gli utenti sono diversificati in base ai ruoli seguenti:
●	super-admin: ha pieno accesso a tutte le aree e funzionalità dell’applicazione
●	admin: accede e ha la gestione completa della sola dell’azienda cui appartiene
●	operatore: accede e ha la gestione operativa della sola azienda cui appartiene, può visualizzare tutte le pagine, modificare i dati degli utenti driver, committenti, intermediario, fornitore.
●	driver: accede in visualizzazione alla propria scheda descrittiva e ai servizi che gli sono stati assegnati, può visualizzare il calendario semplificato di tutti i servizi
●	collaboratori: accedono in visualizzazione alla propria scheda descrittiva e ai servizi da lui acquistati
●	contabilità: accedono in modalità gestione all'area contabilità

Ogni utente appartiene ad una sola azienda, ad esclusione degli utenti con ruolo super-admin che possono amministrare ogni azienda.

Ogni utente può accedere all’applicazione facendo login con email/password

Ogni utente ha uno stato attivo/non attivo, se lo stato + impostato a non attivo l’utente non può effettuare login all’applicazione

Le  informazioni descrittive comuni per gli utenti di ogni ruolo sono: 
●	identificativo utente assegnato dal sistema in automatico, 
●	username (obbligatorio)
●	nome 
●	cognome 
●	nickname
●	indirizzo
●	codice postale
●	comune
●	provincia 
●	nazione
●	telefono
●	email (obbligatorio).
●	password (obbligatorio)
●	intermediario (booleano)
●	percentuale commissioni

In base al ruolo gli utenti sono descritti da una serie di informazioni specifiche per quel ruolo, come descritto di seguito.

3.2.2.2.	Utenti con ruolo driver

Per utenti che hanno ruolo di tipo DRIVER, è necessario raccogliere anche le seguenti informazioni aggiuntive rispetto alla base comune a tutti gli utenti:
●	fotografia del profilo
●	data di nascita 
●	codice fiscale 
●	P IVA
●	tariffa oraria
●	istituto bancario
●	iban del conto corrente
●	veicolo assegnato (da anagrafica veicoli)
●	possibile sovrapposizioni su orario (SI/NO)
●	note

Inoltre, per ogni utente di tipo driver:

●	deve essere associato un colore che sarà utilizzato nell’app quando ci si riferisce al conducente e dal calendario per rappresentare gli eventi associati al driver
●	deve essere possibile allegare i documenti del conducente in formato digitale
●	per ogni documento allegato indicare il tipo di documento facendo riferimento a un dizionario dei documenti allegabili per i driver, secondo una lista di documenti contenuti in un dizionario come descritto nel paragrafo dal titolo Tipologia allegati driver

3.2.2.3.	Utenti con ruolo COLLABORATORE

Per utenti che hanno ruolo COLLABORATORE è necessario raccogliere anche le seguenti informazioni aggiuntive rispetto alla base comune a tutti gli utenti:
●	ragione sociale, 
●	denominazione (alias),
●	committente (booleano), 
●	fornitore (booleano),  
●	partita iva,  
●	codice fiscale
●	sdi
●	pec
●	indirizzo
●	cap
●	comune
●	provincia
●	nazione
●	logo
●	email amministrativa
●	email operativa
●	telefono
●	sito web
●	commissione
●	referente aziendale + di uno
●	numero del referente aziendale
●	email referente aziendale

3.2.3.	Veicoli (Mezzi)
L’entità VEICOLI raccoglie le informazioni relative ai mezzi utilizzati dall’azienda per erogare i servizi e raccoglie le seguenti informazioni relativamente a ogni veicolo:
●	identificativo veicolo assegnato dal sistema in automatico, 
●	targa, 
●	marca, 
●	modello, 
●	numero passeggeri, 
●	data d’acquisto, 
●	numero licenza NCC, 
●	comune della licenza
●	possibile sovrapposizione di orari (SI/NO)
●	Stati: in servizio, manutenzione, fuori servizio
●	Note

Inoltre per ogni veicolo:
●	deve essere possibile allegare documenti relativi al veicolo in formato digitale; 
●	per ogni documento allegato indicare il tipo di documento facendo riferimento a un dizionario dei documenti allegabili per i veicoli, come descritto nel paragrafo dal titolo Tipologie allegati veicolo
●	Per ogni allegato è possibile ma non obbligatorio indicare la scadenza di validità del documento stesso

3.2.4.	Dress codes
●	Dizionario con queste voci iniziali:
o	Voce di dizionario: Business casual; Descrizione: vestito in modo informale ma business
o	Voce di dizionario: Abito; Descrizione: abito e scarpe eleganti con cravatta.
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
●	Deve essere possibile indicare una voce da usare di default
3.2.5.	Tipologie di bagaglio
●	Dizionario con queste voci iniziali:
o	Voce di dizionario: Bagagli; Sigla: BAG
o	Voce di dizionario: Disposizione; Sigla: DISPO
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
3.2.6.	Tipologie di pagamento
●	Dizionario con queste voci iniziali:
o	Voce di dizionario: Bonifico
o	Voce di dizionario: Carta di Credito
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
3.2.7.	Tipologie di allegato driver
●	Dizionario con queste voci iniziali:
o	Voce di dizionario: Antincendio
o	Voce di dizionario: Codice fiscale
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
3.2.8.	Tipologia di allegato veicolo
●	Dizionario con queste voci iniziali:
o	Voce di dizionario: Assicurazione
o	Voce di dizionario: Bollo
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
3.2.9.	Tipologia di allegato veicolo
●	Dizionario con queste voci iniziali:
○	Voce di dizionario: preventivo
○	Voce di dizionario:no-show,
○	Voce di dizionario:onfermato
○	Voce di dizionario:in corso
○	Voce di dizionario:completato
○	Voce di dizionario:cancellato
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce
3.2.10.	ZTL
●	Dizionario con queste informazioni
o	Comune
o	Durata
o	Costo
●	Deve essere possibile inserire nuove voci nel dizionario, cambiare quelle esistenti o cancellare una voce

3.2.11.	Services (Servizi)
L’entità SERVICES raccoglie le informazioni relative ai servizi di noleggio con conducente da erogare e raccoglie le seguenti informazioni relativamente a ogni servizio:
●	identificativo servizio assegnato dal sistema in automatico
●	numero di passeggeri (obbligatorio)
●	dati dei passeggeri ( uno o più) (obbligatorio): 
o	nome, 
o	telefono, 
o	email, 
o	nazionalità
o	Provenienza 
o	Riferimenti vettore provenienza
●	Riferimenti identificativi del committente e referente dall’anagrafica (obbligatorio)
●	Riferimenti identificativi del fornitore dall’anagrafica (obbligatorio)
●	Riferimenti identificativi dell’intermediario e referente dall’anagrafica (obbligatorio)
●	Numero riferimento del servizio 
●	Tipologia di servizio da elenco dizionario (obbligatorio)
●	Tipologia di veicolo da elenco dizionario 
●	targa, marca e modello del veicolo assegnato da anagrafica veicoli (obbligatorio)
●	flag di non sostituibilità del mezzo
●	driver assegnati, uno o più, in caso di assegnazione multipla i driver devono avere il flag sovrapponibile attivato (obbligatorio)
●	nome del driver esterno
●	telefono del driver esterno
●	flag di non sostituibilità dell’autista
●	Dress code da elenco dizionario
●	Numero bagagli grandi
●	Numero bagagli medi
●	Numero bagagli piccoli
●	Numero babyseat ovetto
●	Numero babysear standard
●	Numero babyseat booster
●	Data e ora pickup (obbligatorio)
●	Indirizzo pickup (obbligatorio)
●	coordinate pickup
●	Data e ora uscita mezzo (obbligatorio)
●	Data e ora dropoff (obbligatorio)
●	Indirizzo dropoff (obbligatorio)
●	coordinate dropoff
●	Data e ora rientro mezzo (obbligatorio)
●	Fermate intermedie (una o più):
o	nome della fermata
o	indirizzo completo
o	ora inizio fermata
o	ora fine fermata
o	costo totale
o	costo per persona
o	note
o	acquirente a scelta tra committente, passeggero, agenzia, incluso
●	Stato del servizio, a scelta tra le voci del dizionario
●	Prezzo del servizio 
●	Una o più voci relative al pagamento con le seguenti informazioni:
o	tranche di pagamento (acconto, saldo)
o	importo
o	tipologia pagamento
o	data fattura
o	data pagamento
o	ricevuta/fattura (potrebbe essere più di uno)
o	tipologia incasso
●	Una o più voci relative ai costi da riconoscere ai fornitori con le seguenti informazioni:
o	numero documento (fattura), 
o	data pagamento, 
o	tipologia pagamento, 
o	scadenza documento,
o	tipo documento (acconto/saldo)
o	iban fornitore (da anagrafica ma modificabile) 
●	compenso autista,
●	commissioni intermediario
●	spese vive
●	Note 

3.2.12.	Activities (Attività)
L’entità ACTIVITES raccoglie le informazioni relative alle attività erogate durante i servizi o tour, con i seguenti campi:
●	identificativo veicolo assegnato dal sistema in automatico
●	tipologia (proveniente dal dizionario tipologie di attività), 
●	nome dell'attività, 
●	fornitore (dalla lista di collaboratori con flag is_fornitore impostato a vero)
●	ora inizio
●	ora fine (successiva a ora inizio)
●	costo
●	costo per persona
●	tipologia di pagamento (tra queste voci: INCLUSO, CLIENTE, AGENZIA, NESSUNO)
●	note

4.	Validazioni critiche
Conflitti Temporali Quando si crea/modifica un servizio, il sistema DEVE verificare che:
●	Il veicolo non sia già impegnato in servizi con orari sovrapposti
●	Ogni driver non sia già impegnato in servizi con orari sovrapposti
●	I conflitti vanno controllati solo per servizi non cancellati o completati
●	Nella modifica, escludere il servizio stesso dal controllo conflitti
●	Quando un servizio mostra sovrapposizioni si chiede all’utente che lo sta inserendo se conferma l’inserimento e si rappresenta nell’app con uno stile che mette in evidenza la sovrapposizione.
Validazioni Dati
●	Data/ora pick-up deve essere futura (per nuovi servizi)
●	Data/ora drop-off deve essere dopo pick-up
●	Email valide
●	Campi obbligatori rispettati
5.	Funzionalità principali
5.1.	Gestione entità
●	CRUD Completo per users, vehicles, drivers, services e tutti i dizionari per gli utenti amministratori e superadmin.
●	Solo CRU per users, vehicles, drivers per utenti operator
●	CRUD completo per services per utenti operator
●	Accesso in lettura a Drivers e servizi filtrati sull’utente per gli utenti driver
Tutte le entità devono essere consultabili in formato lista, con un set di dati sintetico. La lista deve essere ricercabile, ordinabile e filtrabile.
Dalla lista si può passare alle schede di dettaglio dei record.
Dalla lista si possono modificare i dati presenti nelle colonne della lista senza bisogno di accedere alla scheda di dettaglio.

5.2.	Dashboard
●	Statistiche: totale servizi, servizi oggi, veicoli disponibili, driver disponibili
●	Prossimi servizi in programma
5.3.	Servizi
5.3.1.	Vista calendario
●	Vista calendario mensile/settimanale/giornaliera dei servizi
●	Integrazione con FullCalendar
●	Eventi colorati per colore di riferimento del driver assegnato al servizio
●	Se l’evento di calendario riguarda un servizio cui sono stati assegnati più di un driver, lo stile grafico è con barre oblique alternate con i colori dei drivers che sono stati assegnati
●	Click su evento mostra scheda sintetica dei dettagli servizio
●	Link a scheda di dettaglio servizio completa

5.3.2.	Vista in lista tabellare
●	Vista dei servizi su lista tabellare, per ogni riga della tabella sono riportati i dati sintetici
●	Dalla lista deve essere possibile accedere alla schedda di dettaglio del servizio
●	I dati presenti sulla tabella sono editabili uno a uno senza dover accedere alla scheda di dettaglio del servizio
