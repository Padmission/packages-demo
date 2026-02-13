#!/usr/bin/env bash
set -uo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m'

error() { echo -e "${RED}[ERROR]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
info() { echo -e "${BLUE}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# ---------------------------------------------------------------------------
# Derive workspace domain name (same logic as setup)
# ---------------------------------------------------------------------------
DOMAIN_NAME="${SUPERSET_WORKSPACE_NAME:-$(basename "$PWD")}"
DOMAIN_NAME="${DOMAIN_NAME##*/}"
DOMAIN_NAME=$(echo "$DOMAIN_NAME" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]/-/g' | sed 's/--*/-/g' | sed 's/^-//' | sed 's/-$//')

if [[ -z "$DOMAIN_NAME" ]]; then
  error "Could not derive domain name from workspace — aborting"
  exit 1
fi

# ---------------------------------------------------------------------------
# Detect project type
# ---------------------------------------------------------------------------
IS_LARAVEL_APP=false
if [[ -f "artisan" ]]; then
  IS_LARAVEL_APP=true
fi

if $IS_LARAVEL_APP; then
  echo "Tearing down Laravel app workspace: $DOMAIN_NAME"
else
  echo "Tearing down package workspace: $DOMAIN_NAME"
fi
echo ""

# ---------------------------------------------------------------------------
# Helper: read a key from a .env file
# ---------------------------------------------------------------------------
env_read() {
  local FILE="$1"
  local KEY="$2"
  grep "^${KEY}=" "$FILE" 2>/dev/null | head -1 | cut -d'=' -f2- | tr -d '"' | tr -d "'"
}

# ---------------------------------------------------------------------------
# Helper: sanitize a string for use as a database name
# ---------------------------------------------------------------------------
sanitize_db_name() {
  echo "$1" | tr '-' '_' | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_]/_/g' | sed 's/__*/_/g' | sed 's/^_//' | sed 's/_$//'
}

# ===========================================================================
# LARAVEL APP TEARDOWN
# ===========================================================================
if $IS_LARAVEL_APP; then

  # --- Herd unlink ---
  info "Unlinking from Herd..."
  if herd unlink "$DOMAIN_NAME" 2>/dev/null; then
    success "Herd unlinked: $DOMAIN_NAME"
  else
    warn "Was not linked or already removed"
  fi

  # --- Database cleanup (auto-detect driver) ---
  # Try local .env first (setup already updated it), fall back to root
  if [[ -f ".env" ]]; then
    ENV_FILE=".env"
  elif [[ -n "${SUPERSET_ROOT_PATH:-}" && -f "$SUPERSET_ROOT_PATH/.env" ]]; then
    ENV_FILE="$SUPERSET_ROOT_PATH/.env"
  else
    ENV_FILE=""
  fi

  if [[ -n "$ENV_FILE" ]]; then
    DB_CONNECTION=$(env_read "$ENV_FILE" "DB_CONNECTION")
    DB_CONNECTION="${DB_CONNECTION:-sqlite}"
    DB_HOST=$(env_read "$ENV_FILE" "DB_HOST")
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT=$(env_read "$ENV_FILE" "DB_PORT")
    DB_USERNAME=$(env_read "$ENV_FILE" "DB_USERNAME")
    DB_USERNAME="${DB_USERNAME:-root}"
    DB_PASSWORD=$(env_read "$ENV_FILE" "DB_PASSWORD")

    # Reconstruct the workspace DB name
    ROOT_ENV="${SUPERSET_ROOT_PATH:-.}/.env"
    SOURCE_DB_NAME=$(env_read "$ROOT_ENV" "DB_DATABASE")
    SAFE_DOMAIN=$(sanitize_db_name "$DOMAIN_NAME")
    WS_DB_NAME="${SOURCE_DB_NAME:+$(sanitize_db_name "$SOURCE_DB_NAME")_}${SAFE_DOMAIN}"

    case "$DB_CONNECTION" in
      mysql)
        DB_PORT="${DB_PORT:-3306}"
        info "Dropping MySQL database: $WS_DB_NAME..."

        MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME")
        [[ -n "$DB_PASSWORD" ]] && MYSQL_ARGS+=(-p"$DB_PASSWORD")

        if mysql "${MYSQL_ARGS[@]}" -e "DROP DATABASE IF EXISTS \`$WS_DB_NAME\`;"; then
          success "MySQL database dropped: $WS_DB_NAME"
        else
          error "Failed to drop MySQL database: $WS_DB_NAME"
        fi
        ;;

      pgsql)
        DB_PORT="${DB_PORT:-5432}"
        info "Dropping PostgreSQL database: $WS_DB_NAME..."

        export PGHOST="$DB_HOST"
        export PGPORT="$DB_PORT"
        export PGUSER="$DB_USERNAME"
        [[ -n "$DB_PASSWORD" ]] && export PGPASSWORD="$DB_PASSWORD"

        if dropdb --if-exists "$WS_DB_NAME"; then
          success "PostgreSQL database dropped: $WS_DB_NAME"
        else
          error "Failed to drop PostgreSQL database: $WS_DB_NAME"
        fi

        unset PGHOST PGPORT PGUSER PGPASSWORD
        ;;

      sqlite)
        info "SQLite database will be removed with the worktree"
        ;;

      *)
        warn "Unknown DB_CONNECTION: $DB_CONNECTION — skipping database cleanup"
        ;;
    esac
  else
    warn "No .env found — skipping database cleanup"
  fi

fi

echo ""
success "Teardown complete"
