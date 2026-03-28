# Quakrs Timeline IA Blueprint (Step 1)

Last update: 2026-03-18
Scope: definizione architetturale e UX della nuova pagina `/timeline.php`.
Reference baseline: `docs/quakrs-current-architecture.md`
Reference v2: `docs/quakrs-architecture-v2.md`

## 1) Obiettivo prodotto

Creare un nastro operativo trasversale che risponda in pochi secondi a: "cosa sta succedendo ora, in ordine temporale, tra hazard diversi?".

## 2) Non-obiettivi

- Non sostituire i monitor verticali (`/earthquakes.php`, `/volcanoes.php`, ecc.).
- Non trasformare la timeline in feed editoriale/news.
- Non modificare la natura di `/event.php` (resta sismo-centrica).

## 3) Posizionamento nella navigazione

- Sezione: `Live`.
- Voce menu: `Timeline`.
- Route: `/timeline.php`.
- Collocazione consigliata: tra `Situation` e `Alerts` nella famiglia Live.

## 4) IA della pagina (struttura)

1. Header operativo
- Titolo pagina + stato aggiornamento (freshness globale).
- Toggle rapido: `Solo attivi` / `Tutti gli aggiornamenti`.

2. Barra filtri essenziali
- Hazard: all, earthquakes, volcanoes, tsunami, space-weather.
- Priorità: all, P1, P2, P3.
- Area: global, Italy.
- Tipo voce: new, update, resolved.
- Finestra temporale: 1h, 6h, 24h, 72h.

3. Stream centrale
- Lista cronologica discendente (più recente sopra).
- Elemento timeline standard con campi minimi:
  - timestamp UTC + locale
  - hazard badge
  - chip priorità
  - titolo sintetico
  - area
  - stato (`new`, `updated`, `resolved`)
  - sorgente primaria
  - azioni: `Apri monitor`, `Apri mappa`, `Dettaglio` (solo se disponibile)

4. Rail laterale (desktop) / blocco inferiore (mobile)
- KPI veloci: eventi 1h/24h, attivi P1/P2, ultimi update feed.
- Link rapidi ai monitor verticali.

5. Paginazione operativa
- `Carica altri` (non infinite scroll puro, per controllo operativo).

## 5) Regole di ordinamento e deduplicazione

- Ordinamento base: `event_time desc`.
- Per update sullo stesso evento: mostra entry autonoma solo quando cambia stato operativo rilevante.
- Se update non significativo: aggrega nel dettaglio espandibile della voce padre.
- Deduplica cross-source con chiave stabile `hazard + canonical_event_id + phase`.

## 6) Data contract minimo (timeline item)

```json
{
  "timeline_id": "tl_...",
  "hazard": "earthquakes|volcanoes|tsunami|space-weather",
  "canonical_event_id": "...",
  "kind": "new|update|resolved",
  "priority": "P1|P2|P3",
  "title": "...",
  "area_label": "...",
  "event_time": "ISO-8601",
  "updated_at": "ISO-8601",
  "source": {
    "id": "usgs|ingv|noaa|...",
    "label": "..."
  },
  "links": {
    "monitor": "/earthquakes.php",
    "map": "/maps.php",
    "detail": "/event.php?id=..."
  },
  "is_active": true
}
```

## 7) Regole UX fondamentali

- Massima scansionabilità: 1 riga informativa forte + meta compatta.
- Colori e badge coerenti con tokens hazard già in uso.
- Nessun testo lungo nel feed primario.
- Stato aggiornamento sempre visibile (`updated Xm ago`).

## 8) Relazione con altre pagine

- Home: snapshot sintetico -> CTA verso timeline completa.
- Alerts: vista “solo allerta attiva” (subset operativo della timeline).
- Monitor verticali: deep dive specialistico.
- Mappe: contesto geografico immediato.

## 9) Piano implementativo incrementale

Fase 1 (MVP)
- Route `/timeline.php`.
- Filtri base (hazard, priorità, finestra).
- Stream con `new` e `update`.

Fase 2
- Aggiunta `resolved` + aggregazione update minori.
- KPI rail e blocco "feed freshness".

Fase 3
- Persistenza preferenze filtri (local storage; integrazione futura con My Quakrs).

## 10) Metriche di successo

- Riduzione click necessari per arrivare a evento rilevante.
- Tempo medio alla comprensione stato globale (target: pochi secondi).
- Uso ripetuto della pagina da utenti operativi (return rate).

## 11) Criteri di accettazione MVP

- Timeline caricata con almeno 24h di eventi.
- Filtri hazard/priorità funzionanti lato utente.
- Link monitor/map sempre presenti per ogni item.
- Nessuna regressione su navigazione esistente.
