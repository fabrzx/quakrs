#!/bin/sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
DEFAULT_SOURCE="${SCRIPT_DIR}/../"
DEFAULT_EXCLUDES_FILE="${SCRIPT_DIR}/deploy-excludes.txt"
DEFAULT_ENV_FILE="${SCRIPT_DIR}/deploy.env"

SOURCE_DIR="${DEPLOY_SOURCE_DIR:-$DEFAULT_SOURCE}"
REMOTE_HOST="${DEPLOY_REMOTE_HOST:-}"
REMOTE_USER="${DEPLOY_REMOTE_USER:-}"
REMOTE_PATH="${DEPLOY_REMOTE_PATH:-}"
REMOTE_PORT="${DEPLOY_REMOTE_PORT:-22}"
SSH_KEY="${DEPLOY_SSH_KEY:-}"
EXCLUDES_FILE="${DEPLOY_EXCLUDES_FILE:-$DEFAULT_EXCLUDES_FILE}"
ENV_FILE="${DEPLOY_ENV_FILE:-$DEFAULT_ENV_FILE}"
DELETE_REMOTE=0
DRY_RUN=0
VERBOSE=0
CHECK_ONLY=0

print_usage() {
  cat <<'EOF'
Usage:
  sh scripts/deploy.sh [options]

Required env vars:
  DEPLOY_REMOTE_HOST   Server hostname (e.g. example.com)
  DEPLOY_REMOTE_USER   SSH user (e.g. deploy)
  DEPLOY_REMOTE_PATH   Remote destination path (e.g. /var/www/quakrs-site)

Optional env vars:
  DEPLOY_SOURCE_DIR    Local source dir (default: quakrs-site root)
  DEPLOY_REMOTE_PORT   SSH port (default: 22)
  DEPLOY_SSH_KEY       SSH private key path
  DEPLOY_EXCLUDES_FILE rsync excludes file (default: scripts/deploy-excludes.txt)

Options:
  --check      Check SSH connectivity and remote path (no upload)
  --delete     Delete remote files removed locally
  --dry-run    Show what would change without uploading
  --verbose    More log output
  -h, --help   Show this help

Examples:
  DEPLOY_REMOTE_HOST=server.tld \
  DEPLOY_REMOTE_USER=deploy \
  DEPLOY_REMOTE_PATH=/var/www/quakrs-site \
  sh scripts/deploy.sh --dry-run

  DEPLOY_REMOTE_HOST=server.tld \
  DEPLOY_REMOTE_USER=deploy \
  DEPLOY_REMOTE_PATH=/var/www/quakrs-site \
  sh scripts/deploy.sh --delete
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --check)
      CHECK_ONLY=1
      ;;
    --delete)
      DELETE_REMOTE=1
      ;;
    --dry-run)
      DRY_RUN=1
      ;;
    --verbose)
      VERBOSE=1
      ;;
    -h|--help)
      print_usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      print_usage >&2
      exit 1
      ;;
  esac
  shift
done

if ! command -v rsync >/dev/null 2>&1; then
  echo "rsync is required but not installed." >&2
  exit 1
fi

if [ -z "$REMOTE_HOST" ] || [ -z "$REMOTE_USER" ] || [ -z "$REMOTE_PATH" ]; then
  if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck disable=SC1090
    . "$ENV_FILE"
    set +a
    REMOTE_HOST="${DEPLOY_REMOTE_HOST:-$REMOTE_HOST}"
    REMOTE_USER="${DEPLOY_REMOTE_USER:-$REMOTE_USER}"
    REMOTE_PATH="${DEPLOY_REMOTE_PATH:-$REMOTE_PATH}"
    REMOTE_PORT="${DEPLOY_REMOTE_PORT:-$REMOTE_PORT}"
    SSH_KEY="${DEPLOY_SSH_KEY:-$SSH_KEY}"
    SOURCE_DIR="${DEPLOY_SOURCE_DIR:-$SOURCE_DIR}"
    EXCLUDES_FILE="${DEPLOY_EXCLUDES_FILE:-$EXCLUDES_FILE}"
  fi
fi

if [ -z "$REMOTE_HOST" ] || [ -z "$REMOTE_USER" ] || [ -z "$REMOTE_PATH" ]; then
  echo "Missing required env vars." >&2
  echo "Tip: create ${ENV_FILE} from scripts/deploy.env.example" >&2
  print_usage >&2
  exit 1
fi

if [ ! -d "$SOURCE_DIR" ]; then
  echo "Source dir not found: $SOURCE_DIR" >&2
  exit 1
fi

if [ ! -f "$EXCLUDES_FILE" ]; then
  echo "Excludes file not found: $EXCLUDES_FILE" >&2
  exit 1
fi

SSH_CMD="ssh -p ${REMOTE_PORT}"
if [ -n "$SSH_KEY" ]; then
  SSH_CMD="${SSH_CMD} -i ${SSH_KEY}"
fi

RSYNC_ARGS="-az --itemize-changes --human-readable --omit-dir-times --no-perms --no-owner --no-group --exclude-from=${EXCLUDES_FILE}"
if [ "$DELETE_REMOTE" -eq 1 ]; then
  RSYNC_ARGS="${RSYNC_ARGS} --delete --delete-excluded"
fi
if [ "$DRY_RUN" -eq 1 ]; then
  RSYNC_ARGS="${RSYNC_ARGS} --dry-run"
fi
if [ "$VERBOSE" -eq 1 ]; then
  RSYNC_ARGS="${RSYNC_ARGS} -v"
fi

echo "[deploy] source: ${SOURCE_DIR}"
echo "[deploy] remote: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}"
echo "[deploy] ssh port: ${REMOTE_PORT}"
if [ "$DRY_RUN" -eq 1 ]; then
  echo "[deploy] mode: dry-run"
fi
if [ "$DELETE_REMOTE" -eq 1 ]; then
  echo "[deploy] mode: delete enabled"
fi

SOURCE_WITH_SLASH="${SOURCE_DIR%/}/"
REMOTE_TARGET="${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH%/}/"

# Ensure remote path exists and SSH is reachable before upload.
if ! ${SSH_CMD} "${REMOTE_USER}@${REMOTE_HOST}" "mkdir -p '${REMOTE_PATH}'" >/dev/null 2>&1; then
  echo "SSH check failed. Verify host/user/key/port and Aruba SSH availability." >&2
  exit 1
fi

if [ "$CHECK_ONLY" -eq 1 ]; then
  echo "[deploy] check ok"
  exit 0
fi

# shellcheck disable=SC2086
rsync ${RSYNC_ARGS} -e "${SSH_CMD}" "${SOURCE_WITH_SLASH}" "${REMOTE_TARGET}"

echo "[deploy] completed"
