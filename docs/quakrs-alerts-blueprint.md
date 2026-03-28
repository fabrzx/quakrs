# Quakrs Alerts IA Blueprint (Step 2)

Last update: 2026-03-18
Scope: definizione architetturale e UX della nuova pagina `/alerts.php`.
Reference baseline: `docs/quakrs-current-architecture.md`
Reference v2: `docs/quakrs-architecture-v2.md`
Reference timeline: `docs/quakrs-timeline-blueprint.md`

## 1) Obiettivo prodotto

Rispondere in modo secco a: "quali allerte sono attive adesso?" con una vista breve, affidabile e action-oriented.

## 2) Non-obiettivi

- Non sostituire i monitor verticali (telemetria completa).
- Non duplicare la timeline cronologica completa.
- Non introdurre narrativa editoriale lunga.

## 3) Posizionamento nella navigazione

- Sezione: `Live`.
- Voce menu: `Alerts`.
- Route: `/alerts.php`.
- Relazione: pagina sorella di `/timeline.php` (subset operativo orientato ad allerta attiva).

## 4) Modello concettuale

- Timeline = tutto ciò che accade in ordine temporale.
- Alerts = solo condizioni operative attive che richiedono attenzione.
- Monitor = dettaglio specialistico per dominio.

## 5) Tassonomia allerta (standard Quakrs)

Ordine severità (alto -> basso):
1. `warning`
2. `watch`
3. `advisory`
4. `elevated-attention`
5. `information`

Campi minimi per ogni alert:
- `alert_id`
- `hazard`
- `level` (tassonomia sopra)
- `priority` (P1/P2/P3)
- `headline`
- `affected_area`
- `issued_at`
- `valid_until` (se presente)
- `status` (`active`, `updating`, `ended`)
- `source`
- `related_links` (monitor/map/detail)

## 6) IA della pagina (struttura)

1. Header operativo
- Titolo + contatore alert attivi.
- Freshness globale (ultimo update).

2. Filtro rapido
- Livello: all, warning, watch, advisory, elevated-attention, information.
- Hazard: all, earthquakes, volcanoes, tsunami, space-weather.
- Area: global, Italy.
- Stato: active, updating.

3. Core list (desktop: tabella; mobile: card compatte)
- Colonne tabella consigliate:
  - Level
  - Hazard
  - Area
  - Headline
  - Priority
  - Validità
  - Stato
  - Source
  - Actions

4. Actions per riga
- `Apri monitor`
- `Apri mappa`
- `Apri timeline correlata`
- `Dettaglio` (se disponibile)

5. Sezione secondaria (facoltativa)
- "Appena conclusi" (ultime allerte terminate entro 24h).

## 7) Regole di ranking

Ordinamento primario:
1. severità alert (`warning` prima)
2. priorità Quakrs (P1 > P2 > P3)
3. freschezza update (più recente prima)

Tie-breaker:
- copertura geografica più ampia sopra
- multi-source corroboration sopra

## 8) Regole lifecycle

- `active`: allerta valida e in corso.
- `updating`: fonte in aggiornamento con stato ancora aperto.
- `ended`: allerta chiusa (non nel core list, ma in storico breve opzionale).

Regola esposizione:
- Core list mostra solo `active` e `updating`.
- `ended` solo in modulo secondario e con timeout breve.

## 9) Data contract minimo (alert item)

```json
{
  "alert_id": "al_...",
  "hazard": "earthquakes|volcanoes|tsunami|space-weather",
  "level": "warning|watch|advisory|elevated-attention|information",
  "priority": "P1|P2|P3",
  "headline": "...",
  "affected_area": "...",
  "issued_at": "ISO-8601",
  "valid_until": "ISO-8601|null",
  "status": "active|updating|ended",
  "source": {
    "id": "noaa|ingv|usgs|...",
    "label": "..."
  },
  "links": {
    "monitor": "/tsunami.php",
    "map": "/maps.php",
    "timeline": "/timeline.php",
    "detail": null
  }
}
```

## 10) UX guardrails

- Leggibilità istantanea: livelli/severità in primo piano.
- Zero ambiguità su validità temporale.
- Nessuna riga senza fonte esplicita.
- Chiaro disclaimer: Quakrs non sostituisce autorità ufficiali.

## 11) Relazione con pagine esistenti

- `/tsunami.php`: resta monitor verticale, non viene assorbito.
- `/volcanoes.php`: resta console operativa dominio vulcanico.
- `/data-reports.php`: resta layer di report aggregati, non lista alert attivi.
- `/`: può mostrare solo top alert, con CTA verso `/alerts.php`.

## 12) Piano implementativo incrementale

Fase 1 (MVP)
- Route `/alerts.php`.
- Lista attiva con filtri livello/hazard/area.
- Ranking severità + priorità.

Fase 2
- Stato `updating` più robusto.
- Modulo "Appena conclusi".

Fase 3
- Collegamento con preferenze utente (My Quakrs) per default filtri.

## 13) Criteri di accettazione MVP

- Vista mostra solo alert attivi/updating.
- Ogni riga ha livello, area, validità e fonte.
- Ordinamento severità/priorità coerente.
- Deep links funzionanti verso monitor/map/timeline.
