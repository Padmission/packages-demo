# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12.x demo application for Filament Admin Panel showcasing:
- Multi-tenant architecture with teams
- E-commerce models (Products, Orders, Customers, Payments)
- Blog functionality (Posts, Authors, Categories)
- Demo mode with isolated user environments
- Custom `data-lens` package integration for reporting

## Key Commands

### Development
```bash
# Start Laravel development server
php artisan serve

# Run frontend dev server (Vite)
npm run dev

# Build frontend assets
npm run build
```

### Testing & Code Quality
```bash
# Run PHPUnit tests
php artisan test

# Run specific test
php artisan test --filter TestClassName

# Static analysis with PHPStan (level 8)
composer test:phpstan

# Code formatting with Laravel Pint
composer cs
```

### Demo Management
```bash
# Populate demo user pool (required for demo mode)
php artisan demo:populate [count]

# Add more demo instances
php artisan demo:add <count>

# Refresh demo data
php artisan demo:refresh [--force]

# Start queue worker for demo operations
php artisan queue:work --queue=demo,default
```

### Database
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Run DemoSeeder specifically
php artisan db:seed --class=DemoSeeder
```

## Architecture & Structure

### Domain Models
The application is organized around two main domains:

**Shop Domain** (`app/Models/Shop/`):
- Product: Core product entity with categories, brand relationships
- Order: Orders with payment tracking and address morphing
- Customer: Customer management with payment history
- Brand: Product brands with address relationships

**Blog Domain** (`app/Models/Blog/`):
- Post: Blog posts with authors and categories
- Author: Content creators
- Category: Post categorization

### Filament Resources
Located in `app/Filament/Resources/`, each resource provides:
- List views with filters and actions
- Create/Edit forms with validation
- Relation managers for nested data
- Custom widgets for analytics

### Multi-Tenancy
- Uses teams (`App\Models\Team`) as tenant model
- Tenant awareness configured via `config/data-lens.php`
- All models automatically scope to current tenant
- Demo mode assigns isolated tenants to visitors

### Local Package Development
The `data-lens` package is loaded from `../data-lens` directory:
- Provides custom reporting functionality
- Integrated with Filament admin panel
- Configured in `config/data-lens.php`

## Important Considerations

1. **Demo Mode**: When enabled, visitors get isolated environments. Ensure queue workers are running for pool replenishment.

2. **Tenant Scoping**: All queries are automatically scoped to the current tenant. Be aware of this when debugging or writing custom queries.

3. **Frontend Assets**: Uses Vite with Tailwind CSS 4.x. Run `npm run dev` during development for hot reloading.

4. **Testing**: PHPUnit tests should be run with test database configuration. Check `phpunit.xml` for environment settings.

5. **Queue Processing**: Demo mode and certain features require queue workers. Use Laravel Horizon for production queue management.