#!/bin/sh
set -eu

BASE_URL="${1:-https://www.quakrs.com}"
TIMEOUT="${TIMEOUT:-25}"
TOKEN="${QUAKRS_REFRESH_TOKEN:-}"
LOCK_FILE="${LOCK_FILE:-/tmp/quakrs-refresh-editorial.lock}"
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
SITE_ROOT="${SCRIPT_DIR}/.."

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  if ! flock -n 9; then
    echo "[refresh-editorial] skipped: lock busy (${LOCK_FILE})"
    exit 0
  fi
fi

if command -v php >/dev/null 2>&1; then
  (
    cd "$SITE_ROOT"
    php -r '$appConfig=require "config/app.php"; require "lib/editorial_engine.php"; qk_editorial_generate($appConfig, 120);'
  ) >/dev/null
else
  if [ -z "$TOKEN" ]; then
    echo "Missing QUAKRS_REFRESH_TOKEN and php CLI is unavailable" >&2
    exit 1
  fi
  curl -fsS --max-time "$TIMEOUT" \
    -H 'Accept: application/json' \
    "${BASE_URL}/api/editorial.php?force_refresh=1&token=${TOKEN}" \
    >/dev/null
fi
