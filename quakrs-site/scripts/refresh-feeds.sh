#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-25}"
TOKEN="${QUAKRS_REFRESH_TOKEN:-}"

if [ -z "$TOKEN" ]; then
  echo "Missing QUAKRS_REFRESH_TOKEN" >&2
  exit 1
fi

for endpoint in earthquakes aftershocks volcanoes tremors tsunami space-weather volcano-cams hotspots bulletins; do
  curl -fsS --max-time "$TIMEOUT" \
    -H 'Accept: application/json' \
    "${BASE_URL}/api/${endpoint}.php?force_refresh=1&token=${TOKEN}" \
    >/dev/null
done

curl -fsS --max-time "$TIMEOUT" \
  -H 'Accept: application/json' \
  "${BASE_URL}/api/tectonic-context.php?force_refresh=1&token=${TOKEN}&scope=global&max_plates=1800&max_faults=3200" \
  >/dev/null
