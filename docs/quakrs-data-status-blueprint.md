# Quakrs Data Status Blueprint (Step 3)

Last update: 2026-03-18
Scope: evoluzione di `/data-status.php` verso status page tecnica più credibile.
Reference baseline: `docs/quakrs-current-architecture.md`
Reference v2: `docs/quakrs-architecture-v2.md`

## 1) Stato attuale (as-is)

Punti già presenti e validi:
- KPI sintetici healthy/lagging/outdated.
- Lista feed con age, size e origine timestamp (payload/file).
- Endpoint `/api/health.php` con overall status, counters, stato archive mysql.

Gap residui:
- Assenza di vista "componenti" (ingest, cache, api surfaces) in modo esplicito.
- Nessuno storico breve incidenti/degradazioni.
- Mancano indicatori di affidabilità/coverage per feed oltre la sola freshness.
- Mancano SLO operativi dichiarati in pagina.

## 2) Obiettivo prodotto

Trasformare `/data-status.php` in una pagina che permetta di capire in <10 secondi:
1. se il sistema è sano o degradato,
2. dove si trova il problema,
3. da quanto tempo,
4. quale impatto ha sui dati visibili all'utente.

## 3) Principi

- Operativa e verificabile, non cosmetica.
- Linguaggio chiaro per utenti normali + dettaglio tecnico per utenti avanzati.
- Nessuna duplicazione con `/about-sources.php` (metodologia) e `/sources-status.php` (catalogo affidabilità feed).

## 4) IA proposta pagina

1. Status banner globale
- Stato: `healthy | warning | degraded | incident`.
- Last updated timestamp.
- Breve testo impatto utente.

2. KPI operativi (prima piega)
- Feed healthy / lagging / outdated / missing.
- Componenti degradati.
- Backlog ingest (count).
- Tempo medio freshness globale (rolling).

3. Component health matrix
- Componenti minimi:
  - feed ingestion
  - normalization pipeline
  - cache/write layer
  - public api read
  - archive db
- Ogni riga: stato, durata stato, impatto, note.

4. Feed status table (estensione della tabella attuale)
- Colonne consigliate:
  - Feed
  - Status
  - Age
  - Max age target
  - Reliability class
  - Last success
  - Error streak
  - Notes

5. Incident & degradation log (24h/7d)
- Lista compatta eventi stato:
  - start
  - end (se chiuso)
  - componente
  - severità
  - sintesi impatto

6. Footer tecnico
- Link a `/about-sources.php` e `/sources-status.php`.
- Disclaimer su eventuali latenze upstream.

## 5) Tassonomia stato proposta

Global status:
- `healthy`: nessun componente critico degradato.
- `warning`: lag limitato senza impatto severo.
- `degraded`: almeno un componente con impatto visibile.
- `incident`: multipli componenti critici o data gap esteso.

Feed status:
- `healthy`, `lagging`, `outdated`, `missing`, `unknown` (riuso attuale).

Component status:
- `up`, `degraded`, `down`, `maintenance`.

## 6) Data contract minimo (estensione `/api/health.php`)

```json
{
  "ok": true,
  "overall_status": "healthy|warning|degraded|incident",
  "generated_at": "ISO-8601",
  "counts": {
    "healthy": 0,
    "lagging": 0,
    "outdated": 0,
    "missing": 0,
    "unknown": 0
  },
  "components": [
    {
      "key": "ingest",
      "status": "up|degraded|down|maintenance",
      "since": "ISO-8601",
      "impact": "none|limited|visible|severe",
      "note": "..."
    }
  ],
  "feeds": [
    {
      "key": "earthquakes",
      "status": "healthy|lagging|outdated|missing|unknown",
      "age_minutes": 0,
      "max_age_minutes": 10,
      "last_success_at": "ISO-8601|null",
      "error_streak": 0,
      "reliability_class": "A|B|C"
    }
  ],
  "incidents": [
    {
      "id": "inc_...",
      "severity": "minor|major|critical",
      "component": "ingest",
      "started_at": "ISO-8601",
      "ended_at": null,
      "summary": "..."
    }
  ],
  "archive_mysql": {
    "status": "ok|unavailable",
    "reason": null
  }
}
```

## 7) Regole di visualizzazione

- Banner colore coerente con stato globale.
- Mostrare sempre `since` per stati degradati/down.
- Incident aperti sempre in cima.
- Mai mostrare "all good" se esiste almeno un feed missing/outdated critico.

## 8) Piano implementativo incrementale

Fase 1 (MVP+)
- Estendere `/api/health.php` con `max_age_minutes`, `last_success_at`, `components` base.
- Aggiornare `/data-status.php` con component matrix e banner impatto.

Fase 2
- Aggiungere `incidents` (rolling 24h/7d) da log leggero locale.
- Aggiungere error streak per feed.

Fase 3
- SLO panel (es. freshness target per feed e compliance 24h).
- Collegamento profondo con `/sources-status.php`.

## 9) Criteri di accettazione MVP+

- Stato globale comprensibile in prima schermata.
- Component matrix visibile con almeno 5 componenti.
- Tabella feed con `max age target` esplicito.
- Almeno un indicatore di impatto utente in caso di degradazione.
