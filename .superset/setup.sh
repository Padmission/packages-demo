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

FAILED=0

# ---------------------------------------------------------------------------
# Derive workspace domain name
# ---------------------------------------------------------------------------
DOMAIN_NAME="${SUPERSET_WORKSPACE_NAME:-$(basename "$PWD")}"
DOMAIN_NAME="${DOMAIN_NAME##*/}"
DOMAIN_NAME=$(echo "$DOMAIN_NAME" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]/-/g' | sed 's/--*/-/g' | sed 's/^-//' | sed 's/-$//')

if [[ -z "$DOMAIN_NAME" ]]; then
  error "Could not derive domain name from workspace"
  exit 1
fi

# ---------------------------------------------------------------------------
# Detect project type: Laravel app (has artisan) vs package (no artisan)
# ---------------------------------------------------------------------------
IS_LARAVEL_APP=false
if [[ -f "artisan" ]]; then
  IS_LARAVEL_APP=true
fi

if $IS_LARAVEL_APP; then
  echo "Setting up Laravel app workspace: $DOMAIN_NAME"
else
  echo "Setting up package workspace: $DOMAIN_NAME"
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
# Helper: set a key in the local .env
# ---------------------------------------------------------------------------
env_set() {
  local KEY="$1"
  local VALUE="$2"
  local ESCAPED_VALUE
  ESCAPED_VALUE=$(printf '%s' "$VALUE" | sed 's/[\\&]/\\&/g')

  if grep -q "^${KEY}=" .env 2>/dev/null; then
    sed -i '' "s|^${KEY}=.*|${KEY}=${ESCAPED_VALUE}|" .env
  else
    printf '%s=%s\n' "$KEY" "$VALUE" >> .env
  fi
}

# ---------------------------------------------------------------------------
# Helper: sanitize a string for use as a database name
# ---------------------------------------------------------------------------
sanitize_db_name() {
  echo "$1" | tr '-' '_' | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_]/_/g' | sed 's/__*/_/g' | sed 's/^_//' | sed 's/_$//'
}

# ===========================================================================
# LARAVEL APP SETUP
# ===========================================================================
if $IS_LARAVEL_APP; then

  # --- Herd link ---
  info "Linking with Herd..."
  if herd link "$DOMAIN_NAME" --secure; then
    success "Herd linked: https://$DOMAIN_NAME.test"
  else
    error "Failed to link with Herd"
    FAILED=1
  fi

  # --- Copy .env ---
  info "Copying .env..."
  if [[ -z "${SUPERSET_ROOT_PATH:-}" ]]; then
    error "SUPERSET_ROOT_PATH is not set"
    FAILED=1
  elif [[ -f "$SUPERSET_ROOT_PATH/.env" ]]; then
    if cp "$SUPERSET_ROOT_PATH/.env" ./; then
      success ".env copied from root"
    else
      error "Failed to copy .env"
      FAILED=1
    fi
  elif [[ -f ".env.example" ]]; then
    if cp .env.example .env; then
      warn ".env not found at root, copied .env.example instead"
    else
      error "Failed to copy .env.example"
      FAILED=1
    fi
  else
    error "No .env source found"
    FAILED=1
  fi

  # --- Database setup (auto-detect driver) ---
  ROOT_ENV="${SUPERSET_ROOT_PATH:-.}/.env"
  DB_CONNECTION=$(env_read "$ROOT_ENV" "DB_CONNECTION")
  DB_CONNECTION="${DB_CONNECTION:-sqlite}"

  SOURCE_DB_NAME=$(env_read "$ROOT_ENV" "DB_DATABASE")
  DB_HOST=$(env_read "$ROOT_ENV" "DB_HOST")
  DB_HOST="${DB_HOST:-127.0.0.1}"
  DB_PORT=$(env_read "$ROOT_ENV" "DB_PORT")
  DB_USERNAME=$(env_read "$ROOT_ENV" "DB_USERNAME")
  DB_USERNAME="${DB_USERNAME:-root}"
  DB_PASSWORD=$(env_read "$ROOT_ENV" "DB_PASSWORD")

  SAFE_DOMAIN=$(sanitize_db_name "$DOMAIN_NAME")
  NEW_DB_NAME="${SOURCE_DB_NAME:+$(sanitize_db_name "$SOURCE_DB_NAME")_}${SAFE_DOMAIN}"

  case "$DB_CONNECTION" in
    mysql)
      DB_PORT="${DB_PORT:-3306}"
      info "Setting up MySQL database: $NEW_DB_NAME (cloning from $SOURCE_DB_NAME)..."

      MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME")
      [[ -n "$DB_PASSWORD" ]] && MYSQL_ARGS+=(-p"$DB_PASSWORD")

      if mysql "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$NEW_DB_NAME\`;"; then
        if mysqldump "${MYSQL_ARGS[@]}" --single-transaction --routines --triggers --events --quick "$SOURCE_DB_NAME" | mysql "${MYSQL_ARGS[@]}" "$NEW_DB_NAME"; then
          success "MySQL database cloned: $NEW_DB_NAME"
        else
          error "Failed to clone MySQL database"
          FAILED=1
        fi
      else
        error "Failed to create MySQL database"
        FAILED=1
      fi
      ;;

    pgsql)
      DB_PORT="${DB_PORT:-5432}"
      info "Setting up PostgreSQL database: $NEW_DB_NAME (cloning from $SOURCE_DB_NAME)..."

      export PGHOST="$DB_HOST"
      export PGPORT="$DB_PORT"
      export PGUSER="$DB_USERNAME"
      [[ -n "$DB_PASSWORD" ]] && export PGPASSWORD="$DB_PASSWORD"

      if psql -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '$NEW_DB_NAME'" | grep -q 1; then
        info "Database $NEW_DB_NAME already exists, dropping first..."
        dropdb "$NEW_DB_NAME" 2>/dev/null
      fi

      if createdb "$NEW_DB_NAME"; then
        if pg_dump "$SOURCE_DB_NAME" | psql -q "$NEW_DB_NAME" > /dev/null 2>&1; then
          success "PostgreSQL database cloned: $NEW_DB_NAME"
        else
          error "Failed to clone PostgreSQL database"
          FAILED=1
        fi
      else
        error "Failed to create PostgreSQL database"
        FAILED=1
      fi

      unset PGHOST PGPORT PGUSER PGPASSWORD
      ;;

    sqlite)
      info "Setting up SQLite database..."
      SOURCE_DB_PATH=$(env_read "$ROOT_ENV" "DB_DATABASE")

      # Handle relative paths
      if [[ "$SOURCE_DB_PATH" != /* ]]; then
        SOURCE_DB_PATH="$SUPERSET_ROOT_PATH/$SOURCE_DB_PATH"
      fi

      TARGET_DB_PATH="database/database.sqlite"
      mkdir -p "$(dirname "$TARGET_DB_PATH")"

      NEEDS_MIGRATION=false

      if [[ -f "$SOURCE_DB_PATH" ]]; then
        if cp "$SOURCE_DB_PATH" "$TARGET_DB_PATH"; then
          success "SQLite database copied"
        else
          error "Failed to copy SQLite database"
          FAILED=1
        fi
      else
        touch "$TARGET_DB_PATH"
        NEEDS_MIGRATION=true
        warn "Source SQLite not found at $SOURCE_DB_PATH, created empty database"
      fi

      NEW_DB_NAME="$TARGET_DB_PATH"
      ;;

    *)
      warn "Unknown DB_CONNECTION: $DB_CONNECTION — skipping database setup"
      NEW_DB_NAME=""
      ;;
  esac

  # --- Update .env values ---
  info "Updating .env..."
  if [[ -f .env ]]; then
    env_set APP_URL "https://$DOMAIN_NAME.test"
    env_set SESSION_SECURE_COOKIE true
    env_set SESSION_DOMAIN ".$DOMAIN_NAME.test"
    if [[ -n "$NEW_DB_NAME" && "$DB_CONNECTION" != "sqlite" ]]; then
      env_set DB_DATABASE "$NEW_DB_NAME"
    fi
    success ".env updated"

    # --- Run migrations for empty SQLite databases ---
    if [[ "$DB_CONNECTION" == "sqlite" && "${NEEDS_MIGRATION:-false}" == "true" ]]; then
      info "Running migrations for empty SQLite database..."
      if php artisan migrate --seed --no-interaction --force 2>/dev/null; then
        success "Migrations and seeders completed"
      elif php artisan migrate --no-interaction --force 2>/dev/null; then
        success "Migrations completed (seeders skipped or failed)"
      else
        error "Failed to run migrations"
        FAILED=1
      fi
    fi
  else
    error ".env file missing, cannot update"
    FAILED=1
  fi

fi

# ===========================================================================
# DEPENDENCIES (both apps and packages)
# ===========================================================================

# --- Fix local path repositories for worktrees ---
if [[ -f "composer.json" && -n "${SUPERSET_ROOT_PATH:-}" ]]; then
  HAS_BROKEN_PATHS=false
  for url in $(jq -r '.repositories[]? | select(.type == "path") | .url' composer.json); do
    if [[ "$url" != /* && ! -d "$url" ]]; then
      HAS_BROKEN_PATHS=true
      break
    fi
  done

  if $HAS_BROKEN_PATHS; then
    info "Resolving local package paths relative to root project..."

    # Build sed replacements for each relative path repository URL
    SED_ARGS=()
    while IFS= read -r url; do
      [[ "$url" == /* ]] && continue
      SED_ARGS+=(-e "s|\"$url\"|\"$SUPERSET_ROOT_PATH/$url\"|g")
    done < <(jq -r '.repositories[]? | select(.type == "path") | .url' composer.json)

    if [[ ${#SED_ARGS[@]} -gt 0 ]]; then
      sed -i '' "${SED_ARGS[@]}" composer.json
      success "Local package paths resolved in composer.json"

      if [[ -f "composer.lock" ]]; then
        sed -i '' "${SED_ARGS[@]}" composer.lock
        success "Local package paths resolved in composer.lock"
      fi
    else
      warn "Failed to resolve local package paths"
    fi
  fi
fi

# --- Composer ---
if [[ -f "composer.json" ]]; then
  info "Installing composer dependencies..."
  if composer install --no-interaction --quiet; then
    success "Composer dependencies installed"
  else
    error "Composer install failed"
    FAILED=1
  fi
fi

# --- npm ---
if [[ -f "package.json" ]]; then
  info "Installing npm dependencies and building..."
  if npm install --silent && npm run build 2>/dev/null; then
    success "npm dependencies installed and built"
  else
    error "npm install/build failed"
    FAILED=1
  fi
fi

# ===========================================================================
# SUMMARY
# ===========================================================================
echo ""
if $IS_LARAVEL_APP; then
  if [[ $FAILED -eq 0 ]]; then
    success "Workspace ready: https://$DOMAIN_NAME.test"
    open "https://$DOMAIN_NAME.test"
  else
    error "Setup completed with errors"
    exit 1
  fi
else
  if [[ $FAILED -eq 0 ]]; then
    success "Package workspace ready: $DOMAIN_NAME"
  else
    error "Setup completed with errors"
    exit 1
  fi
fi
