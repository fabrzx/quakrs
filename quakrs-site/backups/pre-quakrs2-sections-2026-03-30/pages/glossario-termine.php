<?php
declare(strict_types=1);

$term = trim((string) ($glossaryTerm ?? 'Termine tecnico'));
$definition = trim((string) ($glossaryDefinition ?? 'Definizione non disponibile.'));
$slug = trim((string) ($glossarySlug ?? ''));

$termKey = mb_strtolower($term);
$guides = [
    'ipocentro' => [
        'extended' => 'L ipocentro e il punto interno alla crosta terrestre in cui inizia la rottura della faglia. Da qui si propaga l energia sismica che raggiunge la superficie.',
        'why' => 'Sapere dove sta l ipocentro aiuta a capire la profondita reale del fenomeno: eventi piu superficiali possono produrre scuotimenti piu intensi a parita di magnitudo.',
        'read' => 'Nei report Quakrs, l ipocentro si legge insieme a magnitudo, distanza dai centri abitati e tipo di area geologica. Non va mai interpretato da solo.',
        'example' => 'Un evento M5.0 con ipocentro a 8 km puo risultare piu avvertito di un M5.0 a 70 km, proprio per la minore profondita della sorgente.',
    ],
    'epicentro' => [
        'extended' => 'L epicentro e il punto in superficie verticale rispetto all ipocentro. E il riferimento geografico usato per localizzare rapidamente il sisma sulle mappe.',
        'why' => 'Serve per valutare quali aree sono piu vicine alla sorgente e quindi potenzialmente piu esposte allo scuotimento, insieme a profondita e geologia locale.',
        'read' => 'Nei report Quakrs l epicentro e sempre letto insieme a coordinate, comune/area vicina e distanza da zone sensibili.',
        'example' => 'Se l epicentro cade offshore ma vicino alla costa, la percezione puo essere elevata nelle aree costiere anche con danni limitati inland.',
    ],
    'magnitudo' => [
        'extended' => 'La magnitudo misura l energia rilasciata dal terremoto alla sorgente. E una misura strumentale e non coincide automaticamente con i danni osservati.',
        'why' => 'E utile per confrontare eventi diversi, ma l impatto reale dipende anche da profondita, distanza, vulnerabilita edilizia e condizioni del suolo.',
        'read' => 'In Quakrs la magnitudo va letta insieme a profondita, localizzazione e andamento delle repliche nelle 48h/7 giorni successive.',
        'example' => 'Un M4.8 superficiale vicino a un centro urbano puo essere percepito piu di un M5.2 profondo e lontano.',
    ],
    'faglia' => [
        'extended' => 'Una faglia e una frattura della crosta terrestre lungo cui i blocchi rocciosi possono muoversi. I terremoti avvengono quando questo movimento rilascia energia.',
        'why' => 'Conoscere il sistema di faglie attive aiuta a interpretare la sismicita di un area e la possibile evoluzione di una sequenza.',
        'read' => 'Nei report Quakrs la faglia e citata in chiave interpretativa: non come previsione, ma come contesto geologico del comportamento sismico osservato.',
        'example' => 'In aree con faglie normali attive possono comparire sequenze con repliche distribuite lungo lo stesso allineamento strutturale.',
    ],
    'replica' => [
        'extended' => 'Una replica e una scossa collegata allo stesso processo di rottura del terremoto principale. In genere si concentra nella stessa area e tende a diminuire con il tempo.',
        'why' => 'Seguire le repliche aiuta a capire se la sequenza sta decrescendo in modo tipico o se mantiene un attivita persistente che richiede attenzione operativa.',
        'read' => 'Nei report Quakrs va letta insieme a numero eventi nelle ultime 24-72 ore, magnitudo massima recente e distribuzione spaziale intorno all epicentro.',
        'example' => 'Dopo un M5.5, una serie di repliche M3-M4 nelle prime 48 ore puo essere coerente con un decadimento ordinario della sequenza.',
    ],
];

$fallbackExtended = sprintf(
    'Nel contesto sismico, "%s" indica %s. Questo parametro non si interpreta in isolamento: va sempre collegato a localizzazione, profondita, magnitudo e andamento temporale della sequenza.',
    $term,
    mb_strtolower($definition)
);
$fallbackWhy = sprintf(
    'Capire bene "%s" aiuta a evitare conclusioni rapide su rischio e impatto: lo stesso valore puo avere implicazioni diverse a seconda dell area e della vulnerabilita locale.',
    $term
);
$fallbackRead = sprintf(
    'Nei report Quakrs, "%s" viene letto insieme agli altri indicatori operativi (48h/7 giorni, distribuzione spaziale, massima magnitudo recente) per una valutazione piu robusta.',
    $term
);
$fallbackExample = sprintf(
    'Esempio pratico: se "%s" resta stabile mentre il numero eventi cala, il quadro puo indicare graduale attenuazione della sequenza; se cresce insieme al numero eventi, il monitoraggio va intensificato.',
    $term
);

$guide = $guides[$termKey] ?? null;
$extended = is_array($guide) ? (string) ($guide['extended'] ?? $fallbackExtended) : $fallbackExtended;
$why = is_array($guide) ? (string) ($guide['why'] ?? $fallbackWhy) : $fallbackWhy;
$read = is_array($guide) ? (string) ($guide['read'] ?? $fallbackRead) : $fallbackRead;
$example = is_array($guide) ? (string) ($guide['example'] ?? $fallbackExample) : $fallbackExample;

$pageTitle = 'Glossario sismico - ' . $term . ' | Quakrs';
$pageDescription = $definition;
$currentPage = 'editoriale';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Glossario sismico</p>
    <h1><?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="sub"><?= htmlspecialchars($definition, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
</main>

<section class="panel">
  <article class="card">
    <h3>Spiegazione semplice</h3>
    <p class="insight-lead"><?= htmlspecialchars($definition, ENT_QUOTES, 'UTF-8'); ?></p>
    <h3>Cosa significa in pratica</h3>
    <p class="insight-lead"><?= htmlspecialchars($extended, ENT_QUOTES, 'UTF-8'); ?></p>
    <h3>Perché è importante</h3>
    <p class="insight-lead"><?= htmlspecialchars($why, ENT_QUOTES, 'UTF-8'); ?></p>
    <h3>Come leggerlo nei report Quakrs</h3>
    <p class="insight-lead"><?= htmlspecialchars($read, ENT_QUOTES, 'UTF-8'); ?></p>
    <h3>Esempio operativo</h3>
    <p class="insight-lead"><?= htmlspecialchars($example, ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="feed-meta">Termine: <?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?><?php if ($slug !== ''): ?> · slug: <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/editoriale/'), ENT_QUOTES, 'UTF-8'); ?>">Torna all'editoriale</a></p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
