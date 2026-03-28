#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-25}"
TOKEN="${QUAKRS_REFRESH_TOKEN:-}"
LOCK_FILE="${LOCK_FILE:-/tmp/quakrs-refresh-feeds.lock}"

if [ -z "$TOKEN" ]; then
  echo "Missing QUAKRS_REFRESH_TOKEN" >&2
  exit 1
fi

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  if ! flock -n 9; then
    echo "[refresh-feeds] skipped: lock busy (${LOCK_FILE})"
    exit 0
  fi
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
