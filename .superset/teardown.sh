#!/usr/bin/env bash
set -uo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

error() { echo -e "${RED}[ERROR]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }

# Derive domain name (same logic as setup)
DOMAIN_NAME="${SUPERSET_WORKSPACE_NAME:-$(basename "$PWD")}"
DOMAIN_NAME="${DOMAIN_NAME##*/}"
DOMAIN_NAME=$(echo "$DOMAIN_NAME" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]/-/g' | sed 's/--*/-/g' | sed 's/^-//' | sed 's/-$//')

if [[ -z "$DOMAIN_NAME" ]]; then
  error "Could not derive domain name from workspace — aborting"
  exit 1
fi

echo "Tearing down workspace: $DOMAIN_NAME"
echo ""

# Step 1: Unlink from Herd
echo "Unlinking from Herd..."
if herd unlink "$DOMAIN_NAME" 2>/dev/null; then
  success "Herd unlinked: $DOMAIN_NAME"
else
  echo "  (was not linked or already removed)"
fi

# Step 2: Drop database
DB_NAME="relaticle-$DOMAIN_NAME"
echo "Dropping database: $DB_NAME..."
if mysql -h 127.0.0.1 -u root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"; then
  success "Database dropped: $DB_NAME"
else
  error "Failed to drop database: $DB_NAME"
fi

echo ""
success "Teardown complete"
