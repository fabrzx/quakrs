#!/bin/sh
set -eu

ROOT_DIR="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
DATA_DIR="${DATA_DIR:-${ROOT_DIR}/data}"
LOCK_FILE="${LOCK_FILE:-/tmp/quakrs-cache-gc.lock}"

# Conservative defaults:
# - event_history_* can accumulate quickly from query permutations.
# - archive_meta_cache entries are short-lived query metadata.
EVENT_HISTORY_MAX_AGE_MINUTES="${EVENT_HISTORY_MAX_AGE_MINUTES:-1440}"   # 24h
ARCHIVE_META_MAX_AGE_MINUTES="${ARCHIVE_META_MAX_AGE_MINUTES:-180}"      # 3h

if ! echo "$EVENT_HISTORY_MAX_AGE_MINUTES" | grep -Eq '^[0-9]+$'; then
  echo "[cache-gc] EVENT_HISTORY_MAX_AGE_MINUTES must be numeric" >&2
  exit 1
fi
if ! echo "$ARCHIVE_META_MAX_AGE_MINUTES" | grep -Eq '^[0-9]+$'; then
  echo "[cache-gc] ARCHIVE_META_MAX_AGE_MINUTES must be numeric" >&2
  exit 1
fi

if [ ! -d "$DATA_DIR" ]; then
  echo "[cache-gc] data dir not found: ${DATA_DIR}" >&2
  exit 0
fi

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  if ! flock -n 9; then
    echo "[cache-gc] skipped: lock busy (${LOCK_FILE})"
    exit 0
  fi
fi

event_deleted=0
agg_deleted=0
facets_deleted=0

event_deleted="$(find "$DATA_DIR" -maxdepth 1 -type f -name 'event_history_*.json' -mmin "+${EVENT_HISTORY_MAX_AGE_MINUTES}" -print -delete | wc -l | tr -d ' ')"

ARCHIVE_META_DIR="${DATA_DIR}/archive_meta_cache"
if [ -d "$ARCHIVE_META_DIR" ]; then
  agg_deleted="$(find "$ARCHIVE_META_DIR" -maxdepth 1 -type f -name 'agg_*.json' -mmin "+${ARCHIVE_META_MAX_AGE_MINUTES}" -print -delete | wc -l | tr -d ' ')"
  facets_deleted="$(find "$ARCHIVE_META_DIR" -maxdepth 1 -type f -name 'facets_*.json' -mmin "+${ARCHIVE_META_MAX_AGE_MINUTES}" -print -delete | wc -l | tr -d ' ')"
fi

echo "[cache-gc] data_dir=${DATA_DIR} deleted event_history=${event_deleted} agg=${agg_deleted} facets=${facets_deleted}"
