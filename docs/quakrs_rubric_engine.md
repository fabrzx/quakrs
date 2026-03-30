# QUAKRS — RUBRIC ENGINE

## Scopo
Attivare automaticamente le rubriche editoriali di Quakrs sulla base dei dati sismici, assegnando a ciascun contenuto:
- un tipo/rubrica
- un titolo o tema provvisorio
- un set dati minimo
- il prompt corretto del file `/prompts/quakrs_prompts.json`

---

# PRINCIPIO GENERALE

Il sistema deve separare nettamente due livelli:

1. **RUBRIC ENGINE**
   - decide cosa scrivere
   - classifica il contenuto
   - genera il tema
   - seleziona i dati

2. **PROMPTS JSON**
   - scrivono l’articolo
   - determinano tono, struttura e stile

Il rubric engine NON deve riscrivere i prompt.
Il rubric engine NON deve occuparsi del tono.
Il rubric engine deve solo decidere quale prompt usare.

---

# PROMPT DISPONIBILI NEL JSON

Il rubric engine deve considerare disponibili i seguenti prompt:

- `event_live`
- `event_historical`
- `retrospective`
- `sequence_active`
- `zone_active`
- `focus_italy`
- `comparison`
- `timeline_sequence`
- `historical_today`

Ogni regola sotto riportata deve quindi mappare la rubrica a una di queste chiavi.

---

# INPUT DATI MINIMI

Il sistema deve poter lavorare almeno con:

- eventi ultime 24 ore
- eventi ultime 72 ore
- eventi ultimi 7 giorni
- eventi ultimi 30 giorni
- magnitudo
- profondità
- coordinate
- località / place
- timestamp UTC
- sorgente
- flag danni, se disponibile
- paese / area normalizzata
- cluster geografici
- collegamenti a eventi già pubblicati
- dataset storico, se presente

---

# NORMALIZZAZIONE PRELIMINARE

Prima di attivare qualsiasi rubrica, il sistema deve normalizzare i dati.

## 1) Normalizzazione area
Convertire stringhe grezze tipo:
- `120 km N of X`
- `57 km SE of Y`
- `Near the coast of ...`
- stringhe con doppia localizzazione

in una forma editoriale più pulita, adatta ai titoli e alle rubriche.

### Obiettivo
Ottenere una variabile `area_normalized` leggibile.

### Esempi
- `120 km N of Yakutat, Alaska` → `Yakutat, Alaska`
- `5 km S of Visso, Italy` → `Visso, Italy`
- `Central Mid-Atlantic Ridge` → `Central Mid-Atlantic Ridge`

## 2) Normalizzazione temporale
Calcolare almeno:
- `hours_since_event`
- `days_since_event`

## 3) Normalizzazione cluster
Identificare gruppi geografici usando almeno:
- distanza massima cluster
- finestra temporale
- numero eventi

## 4) Normalizzazione geografica nazionale
Determinare se un evento appartiene a:
- Italia
- resto del mondo

Serve per `event_live` e `focus_italy`.

---

# OUTPUT STANDARD DEL RUBRIC ENGINE

Ogni decisione del motore deve produrre un oggetto logico equivalente a questo:

```text
{
  type: "sequence_active",
  prompt_key: "sequence_active",
  title: "Sequenza sismica in corso in Calabria",
  area: "Calabria",
  priority: 2,
  source_scope: "dynamic",
  data: {...}
}
```

Campi minimi richiesti:

* `type` → nome logico della rubrica
* `prompt_key` → chiave esatta del prompt nel JSON
* `title` → tema o titolo provvisorio
* `area` → area editoriale normalizzata
* `priority` → priorità di pubblicazione
* `source_scope` → `live`, `dynamic`, `historical`, `comparative`, ecc.
* `data` → payload minimo per il prompt

---

# LIMITI GLOBALI DEL MOTORE

## Limiti per ciclo

Per ogni ciclo di generazione:

* massimo 1 contenuto per stessa rubrica e stessa area
* massimo 5 contenuti totali per ciclo
* evitare duplicati quasi equivalenti entro 24h

## Deduplicazione

Non generare due contenuti se:

* stessa area
* stessa rubrica
* stessa finestra temporale
* differenza tematica trascurabile

## Priorità

Se troppe rubriche risultano attivabili, usare l’ordine di priorità definito più sotto.

---

## CONTROLLO FREQUENZA GIORNALIERA

Il sistema deve limitare la produzione totale giornaliera.

### Limiti consigliati

* massimo 8–12 articoli al giorno
* massimo 3 articoli per stessa area al giorno
* massimo 2 articoli per stessa rubrica al giorno

### Regola

Se il limite è raggiunto:

→ bloccare nuove generazioni  
→ oppure rimandare al ciclo successivo

---

## CONTROLLO SATURAZIONE PER AREA

Evitare di saturare una singola zona.

### Regole

Non generare nuovi contenuti se:

* stessa area già coperta nelle ultime 6 ore
* stessa area + stessa rubrica nelle ultime 12 ore

---

## CONTROLLO EVENTI MINORI

Ridurre rumore editoriale.

### Regole

Non generare contenuti se:

* magnitudo < 4.5 globale
* magnitudo < 3.5 Italia
* nessun cluster / nessuna rilevanza

Eccezione:

→ se fanno parte di una sequenza attiva

---

## CONTROLLO PRIORITÀ DINAMICA

Se il numero di contenuti attivabili supera il limite:

1. ordinare per priorità
2. selezionare solo i primi N

Dove N = limite per ciclo (es. 5)

---

## MODALITÀ "LOW ACTIVITY"

Se attività globale bassa:

* permettere contenuti editoriali (historical, comparison, ecc.)
* massimo 2 contenuti per ciclo

---

## MODALITÀ "HIGH ACTIVITY"

Se attività globale alta:

* privilegiare:
  - event_live
  - sequence_active
  - zone_active

* bloccare temporaneamente:
  - historical
  - comparison
  - timeline

---


# RUBRICHE E REGOLE

---

## 1) EVENTI RILEVANTI (LIVE)

### Prompt JSON da usare

`event_live`

### Scopo

Generare contenuti su eventi nuovi e rilevanti che superano le soglie operative.

### Condizione di attivazione

Attivare se almeno una delle seguenti condizioni è vera:

* magnitudo ≥ 7.0 globale
* magnitudo ≥ 5.5 in Italia
* flag danni = true

### Dati minimi da passare

* `magnitude`
* `place`
* `lat`
* `lon`
* `depth`
* `datetime`
* `source`
* `damage_info`
* `recent_events`
* `weekly_context`

### Titoli/temi suggeriti

* `Terremoto M{magnitude} in {area_normalized}`
* `Evento sismico rilevante in {area_normalized}`
* `Scossa forte in {area_normalized}: contesto e implicazioni`

### Note operative

* questa rubrica ha priorità massima
* il contenuto deve essere pubblicato il prima possibile
* il rubric engine non deve spiegare il tono: deve solo attivare il prompt corretto

### Output atteso

```text
type: "event_live"
prompt_key: "event_live"
```

---

## 2) RETROSPETTIVE

### Prompt JSON da usare

`retrospective`

### Scopo

Analizzare l’evoluzione di un evento già avvenuto, dopo alcuni giorni.

### Condizione di attivazione

Attivare se:

* esiste un articolo live già pubblicato o un evento live classificato
* sono passate tra 72 e 168 ore dall’evento iniziale

### Dati minimi da passare

* `event_data`
* eventuali repliche/aftershock
* trend attività successiva
* stato attuale area
* riferimento all’evento originale

### Titoli/temi suggeriti

* `A una settimana dal terremoto di {area_normalized}`
* `Come si è evoluta la sequenza dopo il sisma di {area_normalized}`
* `Retrospettiva: {evento} dopo {n} giorni`

### Regole aggiuntive

* non generare più di una retrospettiva per lo stesso evento nella stessa finestra
* se l’evento ha evoluzione minima, valutare se saltarla
* privilegiare eventi che hanno avuto repliche, migrazioni o persistenza

### Output atteso

```text
type: "retrospective"
prompt_key: "retrospective"
```

---

## 3) SEQUENZE IN EVOLUZIONE

### Prompt JSON da usare

`sequence_active`

### Scopo

Descrivere una sequenza o uno sciame in corso, senza dipendere da un singolo evento maggiore.

### Condizione di attivazione

Attivare se:

* almeno 5 eventi nello stesso cluster
* distanza massima cluster ≤ 50 km
* finestra temporale cluster ≤ 72h

Condizioni rafforzative utili:

* progressione di magnitudo
* migrazione spaziale
* concentrazione temporale elevata
* distribuzione di profondità interessante

### Dati minimi da passare

* `cluster_data`

  * numero eventi
  * range magnitudo
  * range profondità
  * coordinate cluster
  * timestamp inizio/fine
  * area normalizzata

### Titoli/temi suggeriti

* `Sequenza sismica in corso in {area_normalized}`
* `Sciame attivo in {area_normalized}`
* `Attività concentrata in {area_normalized}: evoluzione delle ultime 72 ore`

### Regole aggiuntive

* non richiede magnitudo alta
* non focalizzarsi su un solo evento
* evitare duplicati se esiste già un live identico: qui il focus deve essere il sistema, non la scossa

### Output atteso

```text
type: "sequence_active"
prompt_key: "sequence_active"
```

---

## 4) ZONE ATTIVE

### Prompt JSON da usare

`zone_active`

### Scopo

Monitorare aree che mostrano attività persistente e ricorrente su scala di giorni.

### Condizione di attivazione

Attivare se:

* almeno 15 eventi negli ultimi 7 giorni
* stessa area o cluster geograficamente coerente
* continuità temporale percepibile, non un singolo burst già esaurito

### Dati minimi da passare

* `area_data`

  * area normalizzata
  * numero eventi 7d
  * range magnitudo
  * range profondità
  * eventuali cluster interni
  * confronto con finestra precedente, se disponibile

### Titoli/temi suggeriti

* `{area_normalized} resta una delle zone più attive`
* `Attività persistente in {area_normalized}`
* `Ultimi sette giorni in {area_normalized}: quadro dell’attività sismica`

### Regole aggiuntive

* massimo 1 contenuto per area a settimana
* non usare se l’area è attiva solo per un singolo evento già coperto da live
* il focus è la persistenza, non il picco

### Output atteso

```text
type: "zone_active"
prompt_key: "zone_active"
```

---

## 5) FOCUS ITALIA

### Prompt JSON da usare

`focus_italy`

### Scopo

Dare copertura editoriale specifica a situazioni italiane che non rientrano sempre nelle soglie globali più alte ma hanno sensibilità locale.

### Condizione di attivazione

Attivare se almeno una delle seguenti condizioni è vera:

* evento in Italia con magnitudo ≥ 4.0
* cluster attivo in Italia
* sequenza locale in area notoriamente sensibile
* attività recente italiana che merita un contenuto dedicato

### Dati minimi da passare

* `italy_data`

  * area italiana
  * eventi recenti
  * range magnitudo
  * range profondità
  * eventuali repliche o cluster
  * contesto territoriale minimo

### Titoli/temi suggeriti

* `Attività recente in {area_italiana}`
* `Nuova fase di attività in {area_italiana}`
* `Quadro sismico recente in {area_italiana}`

### Regole aggiuntive

* non duplicare un live italiano appena pubblicato, a meno che il focus sia più ampio e territoriale
* preferire aree con memoria sismica nota o interesse operativo elevato

### Output atteso

```text
type: "focus_italy"
prompt_key: "focus_italy"
```

---

## 6) EVENTI STORICI

### Prompt JSON da usare

`event_historical`

### Scopo

Produrre contenuti evergreen su eventi storici rilevanti.

### Condizione di attivazione

Attivare tramite scheduler o calendario editoriale, non su dati live.

### Dati minimi da passare

* `event_data`
* `sequence_info`
* `related_events`

### Titoli/temi suggeriti

* `{evento} — analisi tecnica`
* `Il terremoto di {luogo} del {anno}`
* `{evento}: ruolo nella sequenza e rilevanza attuale`

### Regole aggiuntive

* usare solo eventi realmente significativi
* privilegiare eventi con almeno 10 anni di distanza temporale
* evitare eventi troppo recenti salvo casi eccezionali
* evitare articoli troppo vicini tra loro sulla stessa area senza angolazione diversa
* privilegiare eventi con forte valore analitico o storico

### Output atteso

```text
type: "event_historical"
prompt_key: "event_historical"
```

---

## 7) SE ACCADESSE OGGI

### Prompt JSON da usare

`historical_today`

### Scopo

Riprendere un evento storico e valutarne l’impatto nel contesto moderno.

### Condizione di attivazione

Attivare se:

* è stato selezionato un evento storico rilevante
* l’evento ha almeno 15 anni di distanza temporale dalla data corrente
* esiste sufficiente contesto moderno per renderlo comparabile

### Dati minimi da passare

* `event_data`
* dati di contesto attuale minimi, se disponibili
* area attuale / urbanizzazione / vulnerabilità generale, se il sistema li gestisce

### Titoli/temi suggeriti

* `Se il terremoto di {evento} accadesse oggi`
* `Come apparirebbe oggi il sisma di {evento}`
* `Rileggere {evento} nel contesto attuale`

### Regole aggiuntive

* non attivare questa rubrica per eventi troppo recenti
* evitare eventi successivi al 2010, salvo eccezioni esplicite
* usare questa rubrica solo per eventi con reale distanza storica
* non deve essere sensazionalistico
* non usare scenari estremi gratuiti
* non generare questa rubrica se il confronto tra allora e oggi non produce differenze concrete
* può essere generato in accoppiata con `event_historical`, ma non necessariamente nello stesso ciclo

### Output atteso

```text
type: "historical_today"
prompt_key: "historical_today"
```

---

## 8) CONFRONTI

### Prompt JSON da usare

`comparison`

### Scopo

Confrontare due eventi, due sequenze o due casi con relazione sensata.

### Condizione di attivazione

Attivare se esistono due eventi con almeno una delle seguenti relazioni:

* stessa area
* stessa tipologia meccanica o tettonica
* stessa sequenza
* stesso ordine di grandezza
* confronto editoriale utile e non banale

### Dati minimi da passare

* `event1`
* `event2`

### Titoli/temi suggeriti

* `{evento1} vs {evento2}: confronto tecnico`
* `Due terremoti a confronto in {area}`
* `{evento1} e {evento2}: analogie e differenze`

### Regole aggiuntive

* non attivare confronti casuali
* il confronto deve aggiungere valore reale
* evitare ripetizione di coppie già pubblicate di recente

### Output atteso

```text
type: "comparison"
prompt_key: "comparison"
```

---

## 9) PATTERN RICORRENTI

### Prompt JSON da usare

NOTA: nel JSON attuale non esiste una chiave dedicata `pattern_recurring`.

### Comportamento richiesto

Finché non viene creato un prompt dedicato, questa rubrica deve appoggiarsi a:

* `zone_active` se il focus è un’area
  oppure
* `sequence_active` se il focus è una dinamica di cluster/sequenza
  oppure
* `comparison` se il focus è il parallelo tra casi

### Scopo

Evidenziare configurazioni che ritornano nel tempo o in contesti diversi.

### Condizione di attivazione

Attivare se si rilevano pattern come:

* clustering ripetuto
* migrazioni simili
* sequenze comparabili
* distribuzioni ricorrenti

### Dati minimi da passare

Dipendono dal prompt finale scelto.

### Titoli/temi suggeriti

* `Pattern sismico ricorrente in {area}`
* `Una configurazione che si ripete in {area}`
* `Cluster e ricorrenze nell’attività recente di {area}`

### Regola di mapping temporanea

* se il pattern è territoriale → `zone_active`
* se il pattern è dinamico → `sequence_active`
* se il pattern è comparativo → `comparison`

### Output atteso

```text
type: "pattern_recurring"
prompt_key: "zone_active" | "sequence_active" | "comparison"
```

---

## 10) TIMELINE DELLE SEQUENZE

### Prompt JSON da usare

`timeline_sequence`

### Scopo

Ricostruire una sequenza sismica complessa in ordine temporale.

### Condizione di attivazione

Attivare se:

* sequenza con almeno 10 eventi rilevanti
* durata almeno 5 giorni
* presenza di fasi distinguibili o passaggi significativi

### Dati minimi da passare

* `sequence_data`

  * lista ordinata eventi
  * inizio/fine
  * evoluzione magnitudo
  * evoluzione profondità
  * aree coinvolte
  * fasi principali

### Titoli/temi suggeriti

* `Timeline della sequenza sismica in {area}`
* `Come si è sviluppata la sequenza di {area}`
* `{area}: cronologia tecnica della sequenza`

### Regole aggiuntive

* privilegiare sequenze leggibili e non banali
* evitare timeline su cluster troppo corti o poveri di evoluzione

### Output atteso

```text
type: "timeline_sequence"
prompt_key: "timeline_sequence"
```

---

# PRIORITÀ GENERALE DEL MOTORE

Se più rubriche risultano attivabili nello stesso ciclo, usare questo ordine:

1. `event_live`
2. `sequence_active`
3. `zone_active`
4. `focus_italy`
5. `retrospective`
6. `comparison`
7. `timeline_sequence`
8. `event_historical`
9. `historical_today`
10. `pattern_recurring` (tramite mapping temporaneo)

Nota:
`pattern_recurring` è in coda solo perché al momento non ha un prompt dedicato nel JSON.

---

# REGOLE DI NON SOVRAPPOSIZIONE

## 1) Live vs Focus Italia

Se esiste già un live italiano appena generato, non creare subito un `focus_italy` sulla stessa identica scossa, salvo che il focus sia territoriale più ampio.

## 2) Sequence vs Zone

* usare `sequence_active` quando il focus è la dinamica ravvicinata
* usare `zone_active` quando il focus è la persistenza su scala settimanale

## 3) Historical vs Historical Today

* `event_historical` = spiegazione dell’evento nel suo contesto reale
* `historical_today` = proiezione ragionata nel presente

## 4) Comparison vs Timeline

* `comparison` = due casi messi a confronto
* `timeline_sequence` = un solo caso ricostruito in ordine temporale

---

# PAYLOAD MINIMI ATTESI PER PROMPT

## `event_live`

```text
{
  magnitude,
  place,
  lat,
  lon,
  depth,
  datetime,
  source,
  damage_info,
  recent_events,
  weekly_context
}
```

## `event_historical`

```text
{
  event_data,
  sequence_info,
  related_events
}
```

## `retrospective`

```text
{
  event_data,
  aftershock_data,
  trend_data,
  source_event_reference
}
```

## `sequence_active`

```text
{
  cluster_data
}
```

## `zone_active`

```text
{
  area_data
}
```

## `focus_italy`

```text
{
  italy_data
}
```

## `comparison`

```text
{
  event1,
  event2
}
```

## `timeline_sequence`

```text
{
  sequence_data
}
```

## `historical_today`

```text
{
  event_data
}
```

---

# SCHEDULAZIONE CONSIGLIATA

## Rubriche event-driven

Da valutare ogni ciclo dati:

* `event_live`
* `sequence_active`
* `zone_active`
* `focus_italy`

## Rubriche delayed / follow-up

Da valutare una o più volte al giorno:

* `retrospective`

## Rubriche editoriali calendarizzate

Da valutare con scheduler dedicato:

* `event_historical`
* `historical_today`
* `comparison`
* `timeline_sequence`
* `pattern_recurring`

---

# RISULTATO FINALE

Questo motore deve permettere al sistema di passare da una logica manuale a una logica automatica:

* i dati determinano la rubrica
* la rubrica determina il prompt
* il prompt determina la scrittura

Il rubric engine NON deve scrivere articoli.
Il rubric engine deve solo decidere:

* tipo
* titolo/tema
* area
* priorità
* dati
* prompt da usare
