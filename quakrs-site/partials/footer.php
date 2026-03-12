    <footer class="site-footer">
      <div class="site-footer-inner">
        <div class="site-footer-brand">
          <p class="brand">Quakrs<span>.com</span></p>
          <p class="site-footer-note">Global seismic intelligence across earthquakes, volcanoes and tremor signals.</p>
        </div>
        <nav class="site-footer-nav" aria-label="Footer navigation">
          <a href="/earthquakes.php">Earthquakes</a>
          <a href="/volcanoes.php">Volcanoes</a>
          <a href="/tsunami.php">Tsunami Alerts</a>
          <a href="/space-weather.php">Space Weather</a>
          <a href="/maps.php">Maps</a>
          <a href="/data-energy.php">Data / Energy</a>
          <a href="/data-status.php">Data Status</a>
          <a href="/data-archive.php">Archive</a>
          <a href="/about-sources.php">About / Sources</a>
          <a href="/about-methodology.php">Methodology</a>
          <a href="/resources-safety.php">Safety Guides</a>
        </nav>
        <div class="site-footer-meta">
          <p>Sources: USGS, INGV, EMSC, Smithsonian GVP</p>
          <p id="footer-update-interval">Update interval: ~3 min</p>
          <p id="footer-data-latency">Data latency: estimating...</p>
          <p><a href="/privacy.php">Privacy</a> · <a href="/terms.php">Terms</a></p>
          <p>&copy; <?= date('Y'); ?> Quakrs. Real-time monitoring interface.</p>
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
