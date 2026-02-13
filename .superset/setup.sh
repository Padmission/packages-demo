#!/usr/bin/env bash
set -uo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

error() { echo -e "${RED}[ERROR]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }

FAILED=0

# Derive domain name from workspace name
DOMAIN_NAME="${SUPERSET_WORKSPACE_NAME:-$(basename "$PWD")}"
DOMAIN_NAME="${DOMAIN_NAME##*/}"
DOMAIN_NAME=$(echo "$DOMAIN_NAME" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]/-/g' | sed 's/--*/-/g' | sed 's/^-//' | sed 's/-$//')

if [[ -z "$DOMAIN_NAME" ]]; then
  error "Could not derive domain name from workspace"
  exit 1
fi

echo "Setting up workspace: $DOMAIN_NAME"
echo ""

# Step 1: Link with Herd
echo "Linking with Herd..."
if herd link "$DOMAIN_NAME" --secure; then
  success "Herd linked: https://$DOMAIN_NAME.test"
else
  error "Failed to link with Herd"
  FAILED=1
fi

# Step 2: Copy gitignored config files from root worktree
echo "Copying config files from root worktree..."
if [[ -z "${SUPERSET_ROOT_PATH:-}" ]]; then
  error "SUPERSET_ROOT_PATH is not set"
  FAILED=1
else
  for item in .env .ai .claude CLAUDE.md; do
    SOURCE="$SUPERSET_ROOT_PATH/$item"
    if [[ -e "$SOURCE" ]]; then
      if cp -R "$SOURCE" ./; then
        success "$item copied"
      else
        error "Failed to copy $item"
        FAILED=1
      fi
    fi
  done
fi

# Step 3: Create and clone database (read source DB from root .env, not local copy)
SOURCE_DB_NAME=$(grep '^DB_DATABASE=' "$SUPERSET_ROOT_PATH/.env" 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'")
SOURCE_DB_NAME="${SOURCE_DB_NAME:-relaticle_app}"
NEW_DB_NAME="relaticle-$DOMAIN_NAME"
echo "Setting up database: $NEW_DB_NAME (from $SOURCE_DB_NAME)..."
if mysql -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS \`$NEW_DB_NAME\`;"; then
  if mysqldump -h 127.0.0.1 -u root --single-transaction --routines --triggers --events --quick "$SOURCE_DB_NAME" | mysql -h 127.0.0.1 -u root "$NEW_DB_NAME"; then
    success "Database cloned: $NEW_DB_NAME"
  else
    error "Failed to clone database"
    FAILED=1
  fi
else
  error "Failed to create database"
  FAILED=1
fi

# Step 4: Update .env values
echo "Updating .env..."
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

if [[ -f .env ]]; then
  env_set SESSION_SECURE_COOKIE true
  env_set SESSION_DOMAIN ".$DOMAIN_NAME.test"
  env_set DB_DATABASE "$NEW_DB_NAME"
  env_set APP_URL "https://$DOMAIN_NAME.test"
  success ".env updated"
else
  error ".env file missing, cannot update"
  FAILED=1
fi

# Step 5: Install dependencies
echo "Installing composer dependencies..."
if composer install --no-interaction; then
  success "Composer dependencies installed"
else
  error "Composer install failed"
  FAILED=1
fi

echo "Installing npm dependencies and building..."
if npm install && npm run build; then
  success "npm dependencies installed and built"
else
  error "npm install/build failed"
  FAILED=1
fi

# Summary
echo ""
if [[ $FAILED -eq 0 ]]; then
  success "Workspace ready: https://$DOMAIN_NAME.test"
  open "https://$DOMAIN_NAME.test"
else
  error "Setup completed with errors"
  exit 1
fi
