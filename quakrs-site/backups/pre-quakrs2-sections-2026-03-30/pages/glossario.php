<?php
declare(strict_types=1);

$pageTitle = 'Glossario sismico | Quakrs';
$pageDescription = 'Definizioni semplici dei termini tecnici usati nelle analisi editoriali.';
$currentPage = 'editoriale';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Glossario sismico</p>
    <h1>Termini tecnici spiegati semplice.</h1>
    <p class="sub">Apri un termine dalle analisi editoriali per leggere la definizione dedicata.</p>
  </div>
</main>

<section class="panel">
  <article class="card">
    <h3>Come usarlo</h3>
    <p class="insight-lead">Durante la lettura degli articoli editoriali puoi cliccare i termini evidenziati per aprire la loro pagina di spiegazione.</p>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars(qk_localized_url('/editoriale/'), ENT_QUOTES, 'UTF-8'); ?>">Vai all'editoriale</a></p>
  </article>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
