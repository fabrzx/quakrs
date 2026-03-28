# Quakrs Priority Levels IA Blueprint (Step 4)

Last update: 2026-03-18
Scope: evoluzione IA della pagina `/priority-levels.php`.
Reference baseline: `docs/quakrs-current-architecture.md`
Reference v2: `docs/quakrs-architecture-v2.md`
References: `docs/quakrs-timeline-blueprint.md`, `docs/quakrs-alerts-blueprint.md`

## 1) Obiettivo prodotto

Rendere P1/P2/P3 una feature operativa comprensibile e utilizzabile in tempo reale, non solo una spiegazione statica.

## 2) Stato attuale (as-is)

Punti forti:
- Spiega la logica generale di ranking cross-hazard.
- Include esempi per earthquake/volcano/tsunami/space-weather.

Gap:
- Mancano regole di salita/discesa (lifecycle del livello).
- Mancano esempi temporali concreti "prima/dopo".
- Poca connessione esplicita con Timeline/Alerts/Situation.
- P3 è trattato meno chiaramente rispetto a P1/P2.

## 3) Principi di redesign IA

- Prima la guida rapida operativa, poi il dettaglio tecnico.
- Distinguere chiaramente: `priority` (ranking Quakrs) vs `official alert level` (fonte esterna).
- Esporre i limiti del modello in modo trasparente.
- Evitare linguaggio ambiguo o marketing.

## 4) Struttura consigliata pagina

1. Hero "Quick Read" (10 secondi)
- Cos'è P1/P2/P3 in una frase ciascuno.
- Micro tabella: "quando guardarlo" + "dove vederlo" (home, timeline, alerts, monitor).

2. Sezione "Regole operative"
- Come un evento sale a P1.
- Come resta P2.
- Come scende a P3.
- Regole di de-escalation temporale (segnali attenuati, assenza update, ecc.).

3. Sezione "Esempi reali per hazard"
- Earthquakes: magnitudo/recency/depth (con esempio di escalation e de-escalation).
- Volcanoes: segnali eruttivi e cicli bollettino.
- Tsunami: advisory/watch/warning e impatto su priorità.
- Space weather: classi/storm tier e finestra impatti.

4. Sezione "Priority vs Alerts"
- Tabella comparativa:
  - Priority (interno Quakrs, comparativo cross-hazard)
  - Alert level (ufficiale, authority-specific)
- Esempi di casi in cui non coincidono perfettamente.

5. Sezione "Dove lo vedi nel prodotto"
- Home: board/attention stream.
- Timeline: ordinamento + filtri P1/P2/P3.
- Alerts: relazione con severity e priorità.
- Monitor verticali: contesto completo oltre la priorità.

6. Sezione "Limiti e caveat"
- Possibili ritardi upstream.
- Cross-hazard normalization non equivale a rischio locale assoluto.
- Necessità di riferimento a fonti ufficiali per decisioni locali.

7. CTA operative
- Link: `/timeline.php`, `/alerts.php`, `/about-methodology.php`, `/data-status.php`.

## 5) Glossario minimo da fissare in pagina

- `Priority`: ranking operativo interno Quakrs.
- `Severity`: intensità del singolo fenomeno secondo dominio/fonte.
- `Alert level`: classificazione ufficiale emessa da autorità/istituzioni.
- `Freshness`: età del dato rispetto all'ultimo update utile.

## 6) Pattern contenuto consigliato

Per ogni livello (P1/P2/P3) usare schema fisso:
1. Definizione breve.
2. Trigger tipici.
3. Cosa aspettarsi in UI.
4. Cosa fare (azione utente).
5. Quando può cambiare livello.

## 7) Aggiornamenti microcopy consigliati

- Nav label da valutare: `Priority Levels (P1/P2/P3)` per coerenza attuale.
- Hero title suggerito: `Logica operativa P1/P2/P3`.
- Evitare testo solo teorico; privilegiare frasi decision-oriented.

## 8) Piano implementativo incrementale

Fase 1 (MVP contenutistico)
- Ristrutturazione sezioni con quick read + regole + esempi.
- Inserimento blocco `Priority vs Alerts`.

Fase 2
- Aggiunta esempi dinamici (snapshot da eventi recenti, se disponibile).
- Cross-link contestuali da timeline/alerts.

Fase 3
- Localizzazione avanzata IT/EN con glossario coerente nei due linguaggi.

## 9) Criteri di accettazione MVP

- Un utente capisce in meno di 1 minuto differenza tra P1/P2/P3.
- Presente distinzione esplicita tra priority e alert ufficiale.
- Presente spiegazione di escalation/de-escalation.
- Presenza di link operativi verso timeline/alerts/status/methodology.
