#!/bin/bash

# ABOUTME: Script to switch between Laravel project branches (3.x and 4.x) with full cleanup and rebuild
# ABOUTME: Handles git branch switching, dependency cleanup, composer install, and npm build

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if branch parameter is provided
if [ -z "$1" ]; then
    print_error "Please specify a branch (3.x or 4.x)"
    echo "Usage: ./switch-branch.sh <branch>"
    exit 1
fi

BRANCH=$1

# Validate branch name
if [ "$BRANCH" != "3.x" ] && [ "$BRANCH" != "4.x" ]; then
    print_error "Invalid branch. Please use '3.x' or '4.x'"
    exit 1
fi

print_status "Starting switch to branch $BRANCH..."

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    print_error "You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Switch to the specified branch
print_status "Switching to branch $BRANCH..."
if ! git checkout "$BRANCH"; then
    print_error "Failed to switch to branch $BRANCH"
    exit 1
fi

# Remove vendor directory
print_status "Removing vendor directory..."
rm -rf vendor

# Remove composer.lock
print_status "Removing composer.lock..."
rm -f composer.lock

# Remove node_modules
print_status "Removing node_modules directory..."
rm -rf node_modules

# Remove package-lock.json
print_status "Removing package-lock.json..."
rm -f package-lock.json

# Install composer dependencies
print_status "Installing composer dependencies..."
if ! composer install; then
    print_error "Failed to install composer dependencies"
    exit 1
fi

# Install npm dependencies
print_status "Installing npm dependencies..."
if ! npm install; then
    print_error "Failed to install npm dependencies"
    exit 1
fi

# Build frontend assets
print_status "Building frontend assets..."
if ! npm run build; then
    print_error "Failed to build frontend assets"
    exit 1
fi

# Clear Laravel caches
print_status "Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
print_status "Running database migrations..."
php artisan migrate

print_status "Successfully switched to branch $BRANCH!"
print_status "All dependencies have been reinstalled and assets rebuilt."