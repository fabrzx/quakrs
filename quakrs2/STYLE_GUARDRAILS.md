# Quakrs2 Style Guardrails

Questo file blocca le scelte di UI/UX per il prototipo `quakrs2`.
Ogni modifica futura deve rispettare questi vincoli.

## Regole non negoziabili

1. Non uscire dal linguaggio brutalista tecnico già definito.
2. Non introdurre layout "AI dashboard" (glow, glass, gradient premium, card morbide).
3. Non fare deploy senza richiesta esplicita dell'utente.
4. Lavorare e validare in locale sulla pagina `index-test.html`.
5. Mantenere coerenza con `Uncodixfy.md`.

## Mappa (stato approvato)

1. Una sola mappa (no confronto A/B), variante `GRAPHITE`.
2. Mappa full-box (nessun rimpicciolimento del frame utile).
3. Dark mode non nera piena.
4. Coordinate live mouse in alto a destra nel box mappa.
5. Ticks laterali/in basso dinamici con pan/zoom.
6. Niente "giro infinito": bounds mondiali bloccati, no wrap.

## Feed sinistro (stato approvato)

1. Titolo `GLOBAL SEISMIC EVENTS` centrato orizzontalmente.
2. Spaziatura ridotta tra titolo e toolbar feed.
3. Toolbar in testa con `7D VIEW` e `LIVE FEED`.
4. Card eventi nere con separatori netti e tipografia forte.
5. Prima riga feed con bordo superiore visibile.
6. Gerarchia dati: magnitudo grande, location, meta `DEPTH`/`UTC`.

## Palette operativa

1. Base: neri/antracite e grigi tecnici.
2. Accenti funzionali:
   - Seismic: arancione
   - Volcano: giallo/verde acido
   - Tsunami: ciano
3. Evitare colori nuovi non necessari.

## Processo modifiche

1. Prima cambiare `index.html`, `style.css`, `script.js`.
2. Poi sincronizzare su `index-test.html`, `style-test.css`, `script-test.js`.
3. Validare in locale.
4. Deploy solo su richiesta esplicita.
