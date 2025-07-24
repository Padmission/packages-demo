# Filament & Data Lens Demo Application

A comprehensive demo application showcasing Filament Admin Panel features alongside the Data Lens reporting plugin. This demo provides a realistic e-commerce and blogging platform with multi-tenant architecture and advanced reporting capabilities.

![Filament Demo](https://github.com/filamentphp/demo/assets/171715/899161a9-3c85-4dc9-9599-13928d3a4412)

[Open in Gitpod](https://gitpod.io/#https://github.com/filamentphp/demo) to edit it and preview your changes with no setup required.

## Installation

Clone the repo locally:

```sh
git clone https://github.com/laravel-filament/demo.git filament-demo && cd filament-demo
```

Install PHP dependencies:

```sh
composer install
```

Setup configuration:

```sh
cp .env.example .env
```

Generate application key:

```sh
php artisan key:generate
```

Create an SQLite database. You can also use another database (MySQL, Postgres), simply update your configuration accordingly.

```sh
touch database/database.sqlite
```

Run database migrations:

```sh
php artisan migrate
```

Run database seeder:

```sh
php artisan db:seed
```

> **Note**  
> If you get an "Invalid datetime format (1292)" error, this is probably related to the timezone setting of your database.  
> Please see https://dba.stackexchange.com/questions/234270/incorrect-datetime-value-mysql


Create a symlink to the storage:

```sh
php artisan storage:link
```

Run the dev server (the output will give the address):

```sh
php artisan serve
```

You're ready to go! Visit the url in your browser, and login with:

-   **Username:** admin@filamentphp.com
-   **Password:** password

## Demo Mode Setup

This application includes a demo mode that provides isolated environments for each visitor. To enable it:

### Initial Setup

Pre-populate the demo user pool before launching:

```sh
# Create 50 demo users (default)
php artisan demo:populate

# Or create a specific number
php artisan demo:populate 100
```

### Queue Worker (Laravel Horizon)

This application uses Laravel Horizon for queue management. Start Horizon to handle background pool replenishment:

```sh
php artisan horizon
```

Access the Horizon dashboard at `/horizon` to monitor queue jobs and performance.

### Scheduled Tasks

Set up a cron job to run the scheduler for automatic cleanup:

```sh
# Add to your crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or run the scheduler locally for development:

```sh
php artisan schedule:work
```

### Demo Management Commands

```sh
# Maintain demo system health (cleanup expired data and ensure pool availability)
php artisan demo:refresh
```

When demo mode is enabled, visitors will be automatically assigned a demo account with isolated data in their own tenant (team).

## Key Features

### Multi-Tenant Architecture
- Each demo user gets their own isolated tenant (team)
- All data is automatically scoped to the current tenant
- Seamless tenant switching in the admin panel

### E-Commerce Features (Shop Domain)
- **Products**: Full product catalog with categories, brands, and pricing
- **Orders**: Complete order management with status tracking
- **Customers**: Customer profiles with order history and addresses
- **Payments**: Payment tracking integrated with orders
- **Brands**: Product brand management with addresses

### Content Management (Blog Domain)
- **Posts**: Blog posts with rich content editing
- **Authors**: Author profiles and post associations
- **Categories**: Hierarchical category system
- **Comments**: Polymorphic commenting system

### Data Lens Integration
- **Custom Reports**: Pre-configured reports for sales, customers, inventory, and blog analytics
- **Dynamic Filtering**: Advanced filtering capabilities on all reports
- **Export Functionality**: Export report data in various formats
- **Saved Views**: Save and share custom report configurations

### Demo Mode Features
- Automatic demo user assignment from pre-populated pool
- Isolated data environment per visitor
- Background pool replenishment via queues
- Automatic cleanup of expired sessions

## Features to explore

### Relations

#### BelongsTo
- ProductResource
- OrderResource
- PostResource

#### BelongsToMany
- CategoryResource\RelationManagers\ProductsRelationManager

#### HasMany
- OrderResource\RelationManagers\PaymentsRelationManager

#### HasManyThrough
- CustomerResource\RelationManagers\PaymentsRelationManager

#### MorphOne
- OrderResource -> Address

#### MorphMany
- ProductResource\RelationManagers\CommentsRelationManager
- PostResource\RelationManagers\CommentsRelationManager

#### MorphToMany
- BrandResource\RelationManagers\AddressRelationManager
- CustomerResource\RelationManagers\AddressRelationManager
