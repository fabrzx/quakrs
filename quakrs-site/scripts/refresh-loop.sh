#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
INTERVAL_SECONDS="${INTERVAL_SECONDS:-60}"

if ! echo "$INTERVAL_SECONDS" | grep -Eq '^[0-9]+$'; then
  echo "[refresh-loop] INTERVAL_SECONDS must be numeric" >&2
  exit 1
fi

if [ "$INTERVAL_SECONDS" -lt 30 ]; then
  INTERVAL_SECONDS=30
fi

echo "[refresh-loop] base=${BASE_URL} interval=${INTERVAL_SECONDS}s"

while :; do
  started_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  if /bin/sh "$(dirname "$0")/refresh-feeds.sh" "$BASE_URL"; then
    echo "[refresh-loop] ok ${started_at}"
  else
    echo "[refresh-loop] failed ${started_at}" >&2
  fi
  sleep "$INTERVAL_SECONDS"
done
