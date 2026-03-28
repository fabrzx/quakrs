#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-30}"
HISTORY_POINTS="${HISTORY_POINTS:-14}"
TOKEN="${QUAKRS_REFRESH_TOKEN:-}"
LOCK_FILE="${LOCK_FILE:-/tmp/quakrs-prewarm-all.lock}"

if [ -z "$TOKEN" ]; then
  echo "Missing QUAKRS_REFRESH_TOKEN" >&2
  exit 1
fi

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  if ! flock -n 9; then
    echo "[prewarm] skipped: lock busy (${LOCK_FILE})"
    exit 0
  fi
fi

echo "[prewarm] base=${BASE_URL}"

for endpoint in earthquakes aftershocks volcanoes tremors tsunami space-weather earthquake-cams weather-cams space-weather-cams tsunami-cams volcano-cams hotspots bulletins; do
  echo "[prewarm] refresh ${endpoint}"
  curl -fsS --max-time "$TIMEOUT" \
    -H 'Accept: application/json' \
    "${BASE_URL}/api/${endpoint}.php?force_refresh=1&token=${TOKEN}" \
    >/dev/null
done

echo "[prewarm] refresh tectonic-context"
curl -fsS --max-time "$TIMEOUT" \
  -H 'Accept: application/json' \
  "${BASE_URL}/api/tectonic-context.php?force_refresh=1&token=${TOKEN}&scope=global&max_plates=2400&max_faults=4200" \
  >/dev/null

echo "[prewarm] warm event-history for active zones"
coords="$(
  curl -fsS --max-time "$TIMEOUT" -H 'Accept: application/json' "${BASE_URL}/api/earthquakes.php" \
    | php -r '
      $d = json_decode(stream_get_contents(STDIN), true);
      $events = is_array($d["events"] ?? null) ? $d["events"] : [];
      $unique = [];
      foreach ($events as $e) {
        $lat = $e["latitude"] ?? null;
        $lon = $e["longitude"] ?? null;
        if (!is_numeric($lat) || !is_numeric($lon)) { continue; }
        $key = number_format((float) $lat, 2, ".", "") . "," . number_format((float) $lon, 2, ".", "");
        if (isset($unique[$key])) { continue; }
        $unique[$key] = [(float) $lat, (float) $lon];
      }
      $count = 0;
      $max = max(1, (int) (getenv("HISTORY_POINTS") ?: 14));
      foreach ($unique as $pair) {
        echo $pair[0] . "," . $pair[1] . PHP_EOL;
        $count++;
        if ($count >= $max) { break; }
      }
    '
)"

if [ -n "$coords" ]; then
  printf '%s\n' "$coords" | while IFS=',' read -r lat lon; do
    if [ -z "$lat" ] || [ -z "$lon" ]; then
      continue
    fi
    curl -fsS --max-time "$TIMEOUT" \
      -H 'Accept: application/json' \
      "${BASE_URL}/api/event-history.php?force_refresh=1&token=${TOKEN}&lat=${lat}&lon=${lon}&radius_km=500&start=1900-01-01&page=1&per_page=80" \
      >/dev/null || true
  done
fi

echo "[prewarm] done"
