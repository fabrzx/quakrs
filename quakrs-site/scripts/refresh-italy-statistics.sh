#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-45}"
TOKEN="${QUAKRS_REFRESH_TOKEN:-}"

if [ -z "$TOKEN" ]; then
  echo "Missing QUAKRS_REFRESH_TOKEN" >&2
  exit 1
fi

curl -fsS --max-time "$TIMEOUT" \
  -H 'Accept: application/json' \
  "${BASE_URL}/api/italy-statistics.php?force_refresh=1&token=${TOKEN}" \
  >/dev/null
