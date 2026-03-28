#!/bin/sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
DEFAULT_SOURCE="${SCRIPT_DIR}/../"
DEFAULT_EXCLUDES_FILE="${SCRIPT_DIR}/deploy-excludes.txt"
DEFAULT_ENV_FILE="${SCRIPT_DIR}/deploy-ftp.env"

SOURCE_DIR="${DEPLOY_SOURCE_DIR:-$DEFAULT_SOURCE}"
FTP_HOST="${DEPLOY_FTP_HOST:-}"
FTP_USER="${DEPLOY_FTP_USER:-}"
FTP_PASSWORD="${DEPLOY_FTP_PASSWORD:-}"
FTP_REMOTE_PATH="${DEPLOY_FTP_REMOTE_PATH:-/}"
FTP_PORT="${DEPLOY_FTP_PORT:-21}"
FTP_PROTOCOL="${DEPLOY_FTP_PROTOCOL:-ftp}"
FTP_SSL_ALLOW="${DEPLOY_FTP_SSL_ALLOW:-true}"
FTP_PASSIVE_MODE="${DEPLOY_FTP_PASSIVE_MODE:-true}"
EXCLUDES_FILE="${DEPLOY_EXCLUDES_FILE:-$DEFAULT_EXCLUDES_FILE}"
ENV_FILE="${DEPLOY_ENV_FILE:-$DEFAULT_ENV_FILE}"
DELETE_REMOTE=0
DRY_RUN=0
VERBOSE=0
CHECK_ONLY=0
FORCE_UPLOAD=0

run_lftp_safe() {
  tmp_out="$(mktemp)"
  if lftp -c "$1" >"$tmp_out" 2>&1; then
    status=0
  else
    status=$?
  fi
  sed -E 's#(ftp://[^:/]+:)[^@]+@#\1***@#g' "$tmp_out"
  rm -f "$tmp_out"
  return "$status"
}

print_usage() {
  cat <<'EOF'
Usage:
  sh scripts/deploy-ftp.sh [options]

Required env vars:
  DEPLOY_FTP_HOST       FTP host (e.g. ftp.quakrs.com)
  DEPLOY_FTP_USER       FTP username
  DEPLOY_FTP_PASSWORD   FTP password
  DEPLOY_FTP_REMOTE_PATH Remote destination path (default: /)

Optional env vars:
  DEPLOY_SOURCE_DIR     Local source dir (default: quakrs-site root)
  DEPLOY_FTP_PORT       FTP port (default: 21)
  DEPLOY_FTP_PROTOCOL   ftp or ftps (default: ftp)
  DEPLOY_FTP_SSL_ALLOW  true/false (default: true)
  DEPLOY_FTP_PASSIVE_MODE true/false (default: true)
  DEPLOY_EXCLUDES_FILE  Exclude list (default: scripts/deploy-excludes.txt)

Options:
  --check      Check FTP connectivity and remote path (no upload)
  --force      Force upload ignoring remote timestamps
  --delete     Delete remote files removed locally
  --dry-run    Show what would change without uploading
  --verbose    More log output
  -h, --help   Show this help
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --check)
      CHECK_ONLY=1
      ;;
    --force)
      FORCE_UPLOAD=1
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

if ! command -v lftp >/dev/null 2>&1; then
  echo "lftp is required but not installed." >&2
  exit 1
fi

if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWORD" ]; then
  if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck disable=SC1090
    . "$ENV_FILE"
    set +a
    FTP_HOST="${DEPLOY_FTP_HOST:-$FTP_HOST}"
    FTP_USER="${DEPLOY_FTP_USER:-$FTP_USER}"
    FTP_PASSWORD="${DEPLOY_FTP_PASSWORD:-$FTP_PASSWORD}"
    FTP_REMOTE_PATH="${DEPLOY_FTP_REMOTE_PATH:-$FTP_REMOTE_PATH}"
    FTP_PORT="${DEPLOY_FTP_PORT:-$FTP_PORT}"
    FTP_PROTOCOL="${DEPLOY_FTP_PROTOCOL:-$FTP_PROTOCOL}"
    FTP_SSL_ALLOW="${DEPLOY_FTP_SSL_ALLOW:-$FTP_SSL_ALLOW}"
    FTP_PASSIVE_MODE="${DEPLOY_FTP_PASSIVE_MODE:-$FTP_PASSIVE_MODE}"
    SOURCE_DIR="${DEPLOY_SOURCE_DIR:-$SOURCE_DIR}"
    EXCLUDES_FILE="${DEPLOY_EXCLUDES_FILE:-$EXCLUDES_FILE}"
  fi
fi

if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWORD" ]; then
  echo "Missing required FTP env vars." >&2
  echo "Tip: create ${ENV_FILE} from scripts/deploy-ftp.env.example" >&2
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

echo "[deploy-ftp] source: ${SOURCE_DIR}"
echo "[deploy-ftp] remote: ${FTP_PROTOCOL}://${FTP_HOST}:${FTP_PORT}${FTP_REMOTE_PATH}"
if [ "$DRY_RUN" -eq 1 ]; then
  echo "[deploy-ftp] mode: dry-run"
fi
if [ "$DELETE_REMOTE" -eq 1 ]; then
  echo "[deploy-ftp] mode: delete enabled"
fi

SOURCE_WITH_SLASH="${SOURCE_DIR%/}/"
REMOTE_PATH_NORMALIZED="${FTP_REMOTE_PATH%/}/"

MIRROR_OPTS="--reverse --only-newer --parallel=1"
if [ "$FORCE_UPLOAD" -eq 1 ]; then
  MIRROR_OPTS="--reverse --ignore-time --parallel=1"
fi
if [ "$DELETE_REMOTE" -eq 1 ]; then
  MIRROR_OPTS="${MIRROR_OPTS} --delete"
fi
if [ "$DRY_RUN" -eq 1 ]; then
  MIRROR_OPTS="${MIRROR_OPTS} --dry-run"
fi
if [ "$VERBOSE" -eq 1 ]; then
  MIRROR_OPTS="${MIRROR_OPTS} --verbose=2"
fi

LFTP_CMDS="
set cmd:fail-exit true;
set net:max-retries 2;
set net:timeout 20;
set ftp:passive-mode ${FTP_PASSIVE_MODE};
set ftp:use-mode-z no;
set ssl:verify-certificate no;
set ftp:ssl-allow ${FTP_SSL_ALLOW};
set xfer:clobber on;
set mirror:use-pget-n 0;
set mirror:overwrite yes;
set xfer:verify yes;
open -p \"${FTP_PORT}\" \"${FTP_PROTOCOL}://${FTP_HOST}\";
user \"${FTP_USER}\" \"${FTP_PASSWORD}\";
"

if [ "$REMOTE_PATH_NORMALIZED" = "/" ]; then
  LFTP_CMDS="${LFTP_CMDS}
cd \"/\";
"
else
  LFTP_CMDS="${LFTP_CMDS}
cd \"${REMOTE_PATH_NORMALIZED}\" || (mkdir -p \"${REMOTE_PATH_NORMALIZED}\" && cd \"${REMOTE_PATH_NORMALIZED}\");
"
fi

if [ "$CHECK_ONLY" -eq 1 ]; then
  run_lftp_safe "${LFTP_CMDS} bye"
  echo "[deploy-ftp] check ok"
  exit 0
fi

run_lftp_safe "${LFTP_CMDS} mirror ${MIRROR_OPTS} --exclude-glob-from=\"${EXCLUDES_FILE}\" \"${SOURCE_WITH_SLASH}\" .; bye"

echo "[deploy-ftp] completed"
