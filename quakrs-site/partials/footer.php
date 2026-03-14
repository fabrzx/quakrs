    <footer class="site-footer">
      <div class="site-footer-inner">
        <div class="site-footer-brand">
          <p class="brand">Quakrs<span>.com</span></p>
          <p class="site-footer-note"><?= htmlspecialchars(qk_t('footer.note'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <nav class="site-footer-nav" aria-label="<?= htmlspecialchars(qk_t('footer.nav_aria'), ENT_QUOTES, 'UTF-8'); ?>">
          <a href="<?= htmlspecialchars(qk_localized_url('/earthquakes.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.earthquakes'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/volcanoes.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.volcanoes'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/tsunami.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.tsunami_alerts'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/space-weather.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.space_weather'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/maps.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.maps'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/data-energy.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('footer.data_energy'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/data-status.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.data_status'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/data-archive.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.archive'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/about-sources.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('footer.about_sources'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/about-methodology.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.methodology'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/priority-levels.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.priority_levels'), ENT_QUOTES, 'UTF-8'); ?></a>
          <a href="<?= htmlspecialchars(qk_localized_url('/resources-safety.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('nav.safety_guides'), ENT_QUOTES, 'UTF-8'); ?></a>
        </nav>
        <div class="site-footer-meta">
          <p><?= htmlspecialchars(qk_t('footer.sources_line'), ENT_QUOTES, 'UTF-8'); ?></p>
          <p id="footer-update-interval"><?= htmlspecialchars(qk_t('footer.update_interval'), ENT_QUOTES, 'UTF-8'); ?></p>
          <p id="footer-data-latency"><?= htmlspecialchars(qk_t('footer.data_latency'), ENT_QUOTES, 'UTF-8'); ?></p>
          <p><a href="<?= htmlspecialchars(qk_localized_url('/privacy.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('footer.privacy'), ENT_QUOTES, 'UTF-8'); ?></a> · <a href="<?= htmlspecialchars(qk_localized_url('/terms.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(qk_t('footer.terms'), ENT_QUOTES, 'UTF-8'); ?></a></p>
          <p>&copy; <?= date('Y'); ?> Quakrs. <?= htmlspecialchars(qk_t('footer.copyright'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </div>
    </footer>
    <?php
      $bootstrapData = [
          'earthquakes' => null,
          'volcanoes' => null,
          'tremors' => null,
          'tsunami' => null,
          'space-weather' => null,
      ];
      $bootstrapFiles = [
          'earthquakes' => __DIR__ . '/../data/earthquakes_latest.json',
          'volcanoes' => __DIR__ . '/../data/volcanoes_latest.json',
          'tremors' => __DIR__ . '/../data/tremors_latest.json',
          'tsunami' => __DIR__ . '/../data/tsunami_latest.json',
          'space-weather' => __DIR__ . '/../data/space_weather_latest.json',
      ];

      foreach ($bootstrapFiles as $key => $path) {
          if (!is_file($path)) {
              continue;
          }
          $raw = @file_get_contents($path);
          if (!is_string($raw) || $raw === '') {
              continue;
          }
          $decoded = json_decode($raw, true);
          if (is_array($decoded)) {
              $bootstrapData[$key] = $decoded;
          }
      }
    ?>
    <script>
      window.__QUAKRS_BOOTSTRAP = <?= json_encode($bootstrapData, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php $mainJsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>
    <script src="/assets/js/main.js?v=<?= urlencode((string) $mainJsVersion); ?>"></script>
  </body>
</html>
