#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-25}"

for endpoint in earthquakes volcanoes tremors tsunami space-weather volcano-cams hotspots bulletins; do
  curl -fsS --max-time "$TIMEOUT" \
    -H 'Accept: application/json' \
    "${BASE_URL}/api/${endpoint}.php?force_refresh=1" \
    >/dev/null
done

curl -fsS --max-time "$TIMEOUT" \
  -H 'Accept: application/json' \
  "${BASE_URL}/api/tectonic-context.php?force_refresh=1&scope=global&max_plates=1800&max_faults=3200" \
  >/dev/null
