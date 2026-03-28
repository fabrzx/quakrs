# Quakrs Telegram Bot — Specifiche v1 (Production-Oriented)

## 1. Executive summary

Obiettivo: costruire un bot Telegram Quakrs che funzioni da **filtro intelligente** e non da semplice mirror dei feed.

Principi guida:
- rilevanza prima del volume
- notifiche poche ma ad alto valore
- deduplica cross-source rigorosa
- output leggibile e contestualizzato
- copertura completa delle 4 categorie: Earthquakes, Volcanoes, Tsunami, Space Weather

Target esperienza:
- utenti standard: ~2–8 messaggi/giorno in condizioni normali
- utenti avanzati: più dettaglio, sempre con anti-spam e anti-duplicato

Stack consigliato (realistico per Quakrs):
- PHP 8.2+
- MySQL/MariaDB
- cron
- Telegram Bot API

---

## 2. Architettura proposta

### 2.1 Componenti

1. `Ingestors`
- un ingestor per categoria (earthquakes, volcanoes, tsunami, space weather)
- legge fonti esterne e/o endpoint Quakrs
- salva raw events

2. `Normalizer`
- converte i payload eterogenei in schema unico evento

3. `Deduper + Merger`
- identifica eventi equivalenti tra fonti
- crea evento canonico
- registra alias per tracciabilità

4. `Relevance Scoring Engine`
- assegna score 0–100
- usa fattori categoria-specifici

5. `Decision Engine`
- mappa score su: `IGNORE | DIGEST | ALERT`
- applica override per tipo utente e criticità evento

6. `User Preference Resolver`
- applica filtri utente (categorie, area, soglia EQ, mode)

7. `Notification Queue + Delivery Worker`
- accoda notifiche
- invia su Telegram con retry/backoff
- registra stato invio

8. `Digest Builder`
- costruisce daily brief globale + varianti Italia/personalizzate

9. `Command Handler`
- implementa comandi Telegram e callback keyboard

### 2.2 Flusso operativo

```text
[Sorgenti/API]
   -> [Ingestors]
   -> [source_events_raw]
   -> [Normalizer]
   -> [events_normalized]
   -> [Deduper/Merger + event_aliases]
   -> [Scoring Engine]
   -> [Decision Engine]
         -> IGNORE
         -> DIGEST_POOL
         -> ALERT_CANDIDATE
                -> [Preference Resolver]
                -> [Anti-spam/Rate limits]
                -> [notification_queue]
                -> [Telegram delivery worker]
```

---

## 3. Relevance scoring

## 3.1 Regola globale

Score `0–100`.

Soglie default:
- `< 40` => `IGNORE`
- `40–69` => `DIGEST`
- `>= 70` => `ALERT`

Modifica soglie per modalità:
- `essential`: più conservativa (meno alert)
- `balanced`: baseline
- `monitor`: più sensibile (più eventi monitor)

Implementazione pratica:
- essential: +8 soglia alert, +5 soglia digest
- monitor: -8 soglia alert, -5 soglia digest

### 3.2 Earthquakes (pesi esempio)

- magnitudo: 0–35
- profondità (shallow più rilevante): 0–10
- prossimità aree abitate: 0–20
- popolazione esposta: 0–10
- priorità geografica (Italia boost): 0–10
- tipo evento (mainshock/aftershock/swarm): -5..+10
- confidenza e coerenza sorgenti: -10..+5

### 3.3 Volcanoes

- cambio stato ufficiale: 0–35
- escalation attività: 0–25
- impatto potenziale: 0–20
- persistenza trend: 0–10
- confidenza fonte: -10..+10

### 3.4 Tsunami

- severità alert ufficiale: 0–45
- conferma strumentale/modellistica: 0–20
- estensione area: 0–15
- evoluzione severità: -10..+15
- confidenza fonte: 0–10

### 3.5 Space Weather

- flare class (M/X): 0–25
- CME Earth-directed: 0–30
- tempesta geomagnetica (Kp/G-scale): 0–20
- radio/radiation storm (R/S): 0–15
- persistenza trend: 0–10

---

## 4. Logica per categoria

## 4.1 Earthquakes

Output:
- alert immediati
- monitor/intermedi
- digest

Immediate alert iniziali:
- globale: M6.0+ sempre
- globale: M5.5+ se shallow o area popolata
- Italia: M3.5+ quasi sempre
- Italia: M3.0+ se shallow vicino centri abitati

Monitor:
- sciame in crescita
- aftershock significativo
- cluster anomalo
- revisione importante di magnitudo/profondità

Digest include:
- totale rilevanti
- evento massimo
- area più attiva
- cluster/sciami in corso
- nota Italia

## 4.2 Volcanoes

Immediate alert:
- cambio stato importante
- eruzione significativa
- incremento attività con impatto potenziale alto

Monitor:
- incremento tremore
- attività anomala
- emissioni rilevanti

Digest include:
- vulcani attivi/rilevanti
- cambi di stato
- aree sotto osservazione

## 4.3 Tsunami

Immediate alert:
- warning/advisory ufficiale
- evento potenzialmente tsunamigenico confermato/forte sospetto

Monitor:
- aggiornamento onde/previsioni
- downgrade/upgrade severità
- modifica aree
- cancellazione alert

Digest include:
- riepilogo eventi
- stato chiuso/in corso
- o nessun evento rilevante

## 4.4 Space Weather

Immediate alert:
- flare M forte o X
- CME Earth-directed
- G2+ (Kp>=6)
- R2+
- S2+

Monitor:
- crescita rapida Kp
- flare M minori
- vento solare elevato
- CME in arrivo non ancora impattante

Digest include:
- Kp max/attuale 24h
- flare ultime 24h
- velocità vento solare
- outlook 24–48h

Nota editoriale:
- no stream tecnico incomprensibile
- no spam per oscillazioni minime
- no inferenze pseudoscientifiche space-weather -> terremoti

---

## 5. Anti-spam, deduplica, normalizzazione

### 5.1 Event fingerprinting

Per terremoti:
- bucket temporale (es. ±90s)
- geohash (precisione media)
- magnitudo arrotondata 0.1

Fingerprint complessivo:
- `category + time_bucket + geo_bucket + severity_bucket`

### 5.2 Matching cross-source

Match score basato su:
- delta tempo
- distanza geografica
- delta magnitudo/profondità
- confidenza fonte

Se sopra soglia: merge in evento canonico.

### 5.3 Merge policy

- mantiene `canonical event`
- registra alias sorgenti
- per i campi numerici usa priorità fonte + media/ultima revisione quando opportuno

### 5.4 Update significance

Invia aggiornamento solo se materiale.

Esempi:
- earthquake: |ΔM|>=0.3 o |Δdepth|>=15km o cambio impatto area
- tsunami: cambio livello ufficiale o nuova area
- volcano: cambio stato
- space weather: salto fascia (G1->G2, R1->R2, ecc.)

### 5.5 Anti-spam operativo

- cooldown per evento/utente/categoria
- rate limit giornaliero per mode
- bundling monitor updates ravvicinati
- no reinvio identico (`message_hash`)

Cap suggeriti:
- essential: max 3 alert + 1 digest/die
- balanced: max 8/die
- monitor: max 20/die (con bundling)

---

## 6. Comandi bot (UX v1)

Comandi core:
- `/start`
- `/help`
- `/latest`
- `/earthquakes`
- `/volcanoes`
- `/tsunami`
- `/spaceweather`
- `/dailybrief`
- `/settings`
- `/profile`
- `/subscriptions`
- `/area`
- `/threshold`
- `/mode`

UX raccomandata:
- inline keyboard per toggle rapidi
- callback query per modifiche istantanee
- menu persistente semplice
- onboarding in 3 step max

---

## 7. Schema database iniziale

## 7.1 `telegram_users`
Scopo: anagrafica utenti Telegram.

Campi principali:
- `id` PK
- `telegram_user_id` BIGINT UNIQUE
- `chat_id` BIGINT
- `username`
- `first_name`
- `lang`
- `timezone`
- `is_active`
- `created_at`
- `last_seen_at`

Indici:
- unique `telegram_user_id`
- index `chat_id`

## 7.2 `telegram_user_preferences`
Scopo: preferenze e filtri utente.

Campi:
- `id` PK
- `user_id` FK
- `mode` ENUM('essential','balanced','monitor')
- `categories_json`
- `focus_type` ENUM('global','country','radius')
- `focus_country`
- `center_lat`, `center_lon`, `radius_km`
- `eq_min_mag`
- `eq_max_depth_km` (nullable)
- `eq_max_distance_km` (nullable)
- `digest_enabled` BOOL
- `digest_variant` ENUM('global','italy','custom')
- `digest_times_json` (es. daily/morning/evening)
- `updated_at`

Indici:
- unique `user_id`
- index `mode`
- index `digest_enabled`

## 7.3 `source_events_raw`
Scopo: storico raw da sorgenti.

Campi:
- `id` PK
- `category`
- `source_name`
- `source_event_id`
- `event_time`
- `fetched_at`
- `payload_json`
- `checksum`

Indici:
- unique (`source_name`,`source_event_id`)
- index (`category`,`event_time`)
- index `checksum`

## 7.4 `events_normalized`
Scopo: evento canonico deduplicato.

Campi:
- `id` PK
- `category`
- `canonical_key` UNIQUE
- `event_time`
- `lat`, `lon`
- `depth_km`
- `magnitude`
- `severity_level`
- `country`, `region`, `place_label`
- `title`
- `summary_json`
- `score`
- `decision` ENUM('ignore','digest','alert')
- `status` ENUM('new','updated','closed')
- `is_update`
- `parent_event_id` (nullable)
- `created_at`, `updated_at`

Indici:
- unique `canonical_key`
- index (`category`,`event_time`)
- index (`decision`,`score`)
- index (`country`,`event_time`)

## 7.5 `event_aliases`
Scopo: mapping source event -> canonical event.

Campi:
- `id` PK
- `normalized_event_id` FK
- `source_name`
- `source_event_id`
- `match_confidence`
- `created_at`

Indici:
- unique (`source_name`,`source_event_id`)
- index `normalized_event_id`

## 7.6 `notification_queue`
Scopo: coda invio Telegram.

Campi:
- `id` PK
- `user_id` FK
- `normalized_event_id` FK (nullable per digest)
- `delivery_type` ENUM('alert','monitor','digest')
- `payload_json`
- `priority`
- `scheduled_at`
- `attempts`
- `status` ENUM('pending','processing','sent','failed','dead')
- `last_error`
- `created_at`, `updated_at`

Indici:
- index (`status`,`scheduled_at`,`priority`)
- index (`user_id`,`status`)

## 7.7 `event_notifications`
Scopo: storico invii effettuati.

Campi:
- `id` PK
- `normalized_event_id` (nullable)
- `user_id` FK
- `delivery_type`
- `message_hash`
- `telegram_message_id`
- `status`
- `sent_at`

Indici:
- unique (`normalized_event_id`,`user_id`,`delivery_type`)
- index (`user_id`,`sent_at`)
- index `message_hash`

## 7.8 `digest_runs`
Scopo: esecuzioni digest.

Campi:
- `id` PK
- `digest_date`
- `variant` ENUM('global','italy','custom')
- `scope_key`
- `status`
- `stats_json`
- `started_at`, `finished_at`

Indici:
- unique (`digest_date`,`variant`,`scope_key`)
- index `status`

## 7.9 `digest_items`
Scopo: eventi inclusi in ciascun digest.

Campi:
- `id` PK
- `digest_run_id` FK
- `normalized_event_id` FK
- `category`
- `rank_score`
- `snippet`

Indici:
- index (`digest_run_id`,`category`)

## 7.10 `earthquake_clusters`
Scopo: cluster/sciami in corso.

Campi:
- `id` PK
- `cluster_key` UNIQUE
- `region`
- `start_time`
- `last_event_time`
- `event_count`
- `max_magnitude`
- `trend_score`
- `status`

Indici:
- unique `cluster_key`
- index (`region`,`last_event_time`)

## 7.11 `earthquake_aftershock_groups`
Scopo: grouping aftershock per mainshock.

Campi:
- `id` PK
- `mainshock_event_id` FK
- `group_key` UNIQUE
- `start_time`
- `last_time`
- `count`
- `max_aftershock_mag`

Indici:
- unique `group_key`
- index `mainshock_event_id`

---

## 8. Flusso cron/job suggerito

Schedulazione iniziale:
- ogni 1 min: `worker_notification_queue.php`
- ogni 2 min: `ingest_earthquakes.php`
- ogni 5 min: `ingest_tsunami.php`
- ogni 5 min: `ingest_spaceweather.php`
- ogni 10 min: `ingest_volcanoes.php`
- ogni 2 min: `normalize_score_decide.php`
- ogni 5 min: `update_clusters_aftershocks.php`
- ogni 15 min: `build_monitor_bundles.php`
- daily 07:30 local: `build_daily_digest.php`
- daily 07:40 local: `enqueue_daily_digest_delivery.php`
- nightly: `housekeeping_retention.php`

Policy retry delivery:
- tentativi: 5
- backoff: 1m, 3m, 10m, 30m, 60m
- poi stato `dead`

---

## 9. Template Telegram

## 9.1 Earthquake immediate alert

```text
🌍 Earthquake Alert
M{mag} • {region} ({country})
Profondità: {depth_km} km • Ora: {local_time}

Contesto: {relevance_note}
Dettagli: https://www.quakrs.com/{event_path}
```

## 9.2 Volcano alert

```text
🌋 Volcano Alert
{volcano_name} • {country}
Stato: {new_status} (da {old_status})
Ora: {local_time}

Contesto: {impact_note}
Dettagli: https://www.quakrs.com/{event_path}
```

## 9.3 Tsunami alert

```text
🌊 Tsunami Alert
Livello: {warning_level}
Area: {affected_area}
Ora: {local_time}

Aggiornamento ufficiale: {official_note}
Dettagli: https://www.quakrs.com/{event_path}
```

## 9.4 Space weather alert

```text
☀️ Space Weather Alert
Evento: {event_type} ({class_or_scale})
Geomagnetic: Kp {kp_value} / {g_scale}
Ora: {local_time}

Contesto: {impact_summary}
Dettagli: https://www.quakrs.com/{event_path}
```

## 9.5 Daily brief

```text
🛰 Quakrs Daily Brief — {date}

Earthquakes
• Eventi rilevanti: {eq_count}
• Magnitudo max: {eq_max}
• Area più attiva: {eq_hotspot}
• Sciami: {eq_swarm_note}

Volcanoes
• Vulcani rilevanti: {volc_count}
• Cambi stato: {volc_changes}

Tsunami
• {tsu_summary}

Space Weather
• Kp max 24h: {kp_max}
• Flare max 24h: {flare_max}
• Vento solare: {solar_wind}
• Outlook 24–48h: {outlook}

Approfondisci: https://www.quakrs.com/
```

## 9.6 Monitor cluster/swarm update

```text
📈 Seismic Monitor Update
Area: {region}
Ultime {hours}h: {count} eventi (max M{max_mag})
Trend: {trend_label}

Contesto: aggiornamento monitor (non emergenza).
Dettagli: https://www.quakrs.com/{cluster_path}
```

---

## 10. Roadmap implementativa

## Fase 1 — MVP utile

Deliverable:
- bot Telegram base
- comandi principali
- ingest + normalize + dedupe v1
- scoring v1 per 4 categorie
- alert principali
- daily brief globale
- preferenze minime

Dipendenze:
- token bot
- DB pronto
- endpoint sorgenti affidabili

Rischi:
- rumore iniziale
- difformità dati fonti

Priorità: massima

## Fase 2 — Personalizzazione avanzata

Deliverable:
- filtri area/radius
- soglie EQ personali
- digest personalizzati
- UX settings migliorata

Dipendenze:
- geocoding/geoquery stabili

Rischi:
- aumentare troppo complessità UX

Priorità: alta

## Fase 3 — Intelligence avanzata

Deliverable:
- swarm detection robusta
- aftershock grouping
- scoring raffinato
- queue più robusta
- analytics notifiche

Dipendenze:
- storico dati adeguato

Rischi:
- tuning soglie/false positive

Priorità: medio-alta

## Fase 4 — Ops/Admin

Deliverable:
- pannello admin soglie/template
- A/B testing messaggi
- segmentazione utenti

Dipendenze:
- telemetria consolidata

Rischi:
- overfitting su campione ridotto

Priorità: media

---

## 11. Pseudocodice critico

## 11.1 Pipeline evento

```pseudo
for raw in ingest_all_sources():
    save_raw(raw)

    norm = normalize(raw)
    canonical = dedupe_find_candidate(norm)

    if canonical exists:
        merged = merge(canonical, norm)
        if is_significant_update(canonical, merged):
            score = score_event(merged)
            decision = decide(score)
            save_normalized(merged, score, decision, is_update=true)
            plan_notifications(merged, decision)
    else:
        score = score_event(norm)
        decision = decide(score)
        save_normalized(norm, score, decision, is_update=false)
        plan_notifications(norm, decision)
```

## 11.2 Decision per utente

```pseudo
function plan_notifications(event, base_decision):
    users = get_subscribed_users(event.category)

    for user in users:
        pref = get_preferences(user)

        if not matches_geo(event, pref):
            continue
        if not matches_custom_threshold(event, pref):
            continue

        decision = adapt_by_mode(base_decision, pref.mode, event.score)

        if decision == IGNORE:
            continue
        if already_notified(user, event, decision):
            continue
        if violates_rate_limit(user, pref.mode):
            continue

        enqueue(user, event, decision)
```

## 11.3 Rilevazione swarm base

```pseudo
every 5m:
    eq = fetch_earthquakes(last_24h, min_mag=2.0)
    clusters = group_by_geo_time(eq, geohash_precision=4, bucket=1h)

    for c in clusters:
        trend = acceleration(c.time_series)
        if c.count >= N and trend > T:
            upsert_cluster(c, trend)
            if should_emit_monitor(c):
                enqueue_cluster_update(c)
```

---

## 12. Integrazione pragmatica in Quakrs

Linee guida:
- evitare overengineering
- riuso massimo di componenti backend esistenti
- introdurre moduli piccoli, testabili, indipendenti

Struttura suggerita:

```text
quakrs-site/
  bot/
    ingestors/
    core/
      normalize/
      dedupe/
      scoring/
      decision/
    telegram/
      commands/
      delivery/
      templates/
    jobs/
    config/
```

Best practice operative:
- feature flags per categoria
- kill switch invii globali
- logging strutturato e metriche minime
- retention dati raw (es. 30–90 giorni) e normalized più lunga

---

## 13. Esempio evento normalizzato JSON

```json
{
  "category": "earthquakes",
  "canonical_key": "eq:2026-03-24T09:16Z:gh9q:5.7",
  "event_time": "2026-03-24T09:16:22Z",
  "lat": 42.13,
  "lon": 13.52,
  "depth_km": 11.2,
  "magnitude": 5.7,
  "country": "IT",
  "region": "Central Italy",
  "is_aftershock": false,
  "cluster_key": "it-central-20260324-a",
  "source_confidence": 0.93,
  "score": 79,
  "decision": "ALERT"
}
```

---

## 14. Nota infrastrutturale (VPS)

Con 1GB totale su hosting condiviso è corretto aspettarsi limiti per bot + queue + job frequenti.

Approccio consigliato:
- preparare ora codice e schema DB modulari
- avviare in staging minimale
- spostare su VPS appena possibile
- partire con polling e rate conservativi

Stima iniziale baseline VPS:
- 1 vCPU / 2GB RAM (minimo pragmatico)
- MySQL ottimizzato leggero
- cron + PHP worker

