# Demo System Testing Documentation

## Overview

The demo system has comprehensive test coverage across all major components:
- Auto-login functionality
- Multi-tenancy isolation
- Demo management commands
- Middleware behavior
- Background jobs
- End-to-end integration flows

## Test Structure

```
tests/
├── Feature/
│   └── Demo/
│       ├── DemoLoginTest.php        # Auto-login system tests
│       ├── MultiTenancyTest.php     # Tenant isolation tests
│       ├── DemoCommandsTest.php     # Command tests (refresh, add)
│       └── DemoIntegrationTest.php  # Full integration tests
└── Unit/
    ├── Middleware/
    │   └── HandleMissingTenantTest.php  # Tenant middleware tests
    ├── Jobs/
    │   └── ReplenishDemoPoolTest.php    # Background job tests
    └── Seeders/
        └── DemoSeederTest.php           # Demo data generation tests
```

## Running Tests

### Quick Test Run
```bash
# Run all demo tests
./run-demo-tests.sh

# Run with coverage report
./run-demo-tests.sh --coverage

# Run all project tests
./run-demo-tests.sh --all
```

### Individual Test Suites
```bash
# Auto-login tests
php artisan test tests/Feature/Demo/DemoLoginTest.php

# Multi-tenancy tests
php artisan test tests/Feature/Demo/MultiTenancyTest.php

# Commands tests
php artisan test tests/Feature/Demo/DemoCommandsTest.php

# Integration tests
php artisan test tests/Feature/Demo/DemoIntegrationTest.php
```

### Unit Tests
```bash
# Middleware tests
php artisan test tests/Unit/Middleware/HandleMissingTenantTest.php

# Job tests
php artisan test tests/Unit/Jobs/ReplenishDemoPoolTest.php

# Seeder tests
php artisan test tests/Unit/Seeders/DemoSeederTest.php
```

## Test Coverage

### DemoLoginTest (8 tests)
- ✅ Pre-filled demo credentials on login page
- ✅ Demo user assignment on login
- ✅ Demo users have two teams
- ✅ Emergency batch creation when pool empty
- ✅ Admin credentials shown when demo disabled
- ✅ Concurrent users get different accounts

### MultiTenancyTest (8 tests)
- ✅ Users can access their assigned teams
- ✅ Users cannot access other teams
- ✅ Data isolation between teams
- ✅ Team scope filters data correctly
- ✅ Auto-assignment of team_id on model creation
- ✅ Demo seeder creates isolated data
- ✅ Console commands bypass team scope
- ✅ User-team relationships

### DemoCommandsTest (10 tests)
- ✅ `demo:add` creates instances
- ✅ `demo:add --queue` dispatches job
- ✅ Count validation (1-100)
- ✅ Commands fail when demo disabled
- ✅ `demo:refresh` cleans expired users
- ✅ `demo:refresh` deletes old unused users
- ✅ `demo:refresh` replenishes pool
- ✅ `demo:refresh --force` full reset
- ✅ Queue configuration respected

### HandleMissingTenantTest (6 tests)
- ✅ Allows requests without tenant parameter
- ✅ Allows requests with valid tenant
- ✅ Redirects demo users for missing tenant
- ✅ Shows 404 for non-demo users
- ✅ Shows 403 for unauthorized tenant access
- ✅ Allows guest access when tenant exists

### ReplenishDemoPoolTest (8 tests)
- ✅ Creates demo instances when needed
- ✅ Respects pool size limit
- ✅ Does nothing when pool full
- ✅ Only counts available users
- ✅ Disabled when demo mode off
- ✅ Uses configured queue
- ✅ Logs activity
- ✅ Respects count parameter

### DemoSeederTest (10 tests)
- ✅ Creates demo user with teams
- ✅ Creates isolated shop data per team
- ✅ Creates isolated blog data per team
- ✅ Creates multiple instances
- ✅ Assigns unique emails
- ✅ Creates customer addresses
- ✅ Creates order items and payments
- ✅ Respects configuration
- ✅ Handles missing plugins gracefully

### DemoIntegrationTest (6 tests)
- ✅ Complete demo flow (login → use → logout)
- ✅ Demo refresh cycle
- ✅ Missing tenant handling
- ✅ Concurrent user scenario
- ✅ Data Lens reports per team
- ✅ Full demo lifecycle

## Test Database Configuration

Tests use SQLite for speed and isolation:

```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

For persistent test database:
```bash
touch database/testing.sqlite
DB_DATABASE=database/testing.sqlite php artisan test
```

## Writing New Tests

### Test Helpers

```php
// Configure demo settings in tests
config(['demo.pool_size' => 10]);

// Create demo instances
$seeder = new DemoSeeder();
$seeder->run(5);

// Get demo user
$demoUser = User::where('email', 'like', 'demo_%@demo.padmission.com')->first();

// Set tenant context
filament()->setTenant($team);
```

### Common Assertions

```php
// Check user assignment
$this->assertAuthenticatedAs($demoUser);

// Check tenant access
$this->assertTrue($user->canAccessTenant($team));

// Check data isolation
filament()->setTenant($team1);
$this->assertCount(0, Product::where('team_id', $team2->id)->get());

// Check job dispatch
Queue::assertPushed(ReplenishDemoPool::class);

// Check redirects
$response->assertRedirect('/admin/login');
$response->assertSessionHas('error', 'Your demo session has expired...');
```

## Continuous Integration

Add to your CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
- name: Run Demo Tests
  run: |
    cp .env.example .env
    php artisan key:generate
    ./run-demo-tests.sh --coverage
```

## Troubleshooting

### Common Issues

1. **SQLite not found**
   ```bash
   sudo apt-get install sqlite3  # Ubuntu/Debian
   brew install sqlite          # macOS
   ```

2. **Tests failing on fresh install**
   ```bash
   composer install
   php artisan migrate:fresh --env=testing
   ```

3. **Filament not initialized**
   ```bash
   php artisan filament:install --panels
   ```

4. **Queue tests timing out**
   - Ensure `Queue::fake()` is called in test setup
   - Check for synchronous queue driver in testing

## Performance Considerations

- Tests use `RefreshDatabase` trait for isolation
- Consider using `:memory:` SQLite for faster tests
- Mock external services (email, storage) when possible
- Use factories efficiently to minimize database operations

## Test Metrics

- **Total Tests**: 56
- **Assertions**: 200+
- **Coverage Target**: 80%+
- **Execution Time**: ~30 seconds