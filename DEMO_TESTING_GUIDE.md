# Demo System Testing Guide

## Fixed Demo System Flow

The demo system has been updated to work properly with Filament's multi-tenancy. Here's the correct implementation:

1. **✅ Pre-populate demo users** - Create a pool of demo users with isolated data before launching the app
2. **✅ Auto-assign on login** - Visitors get assigned available pre-created users
3. **✅ Background replenishment** - Low pools trigger background jobs to create more users
4. **✅ No real-time seeding** - Eliminates Filament tenant context errors during requests

## Initial Setup (FIXED)

1. **Run migrations**:
   ```bash
   php artisan migrate:fresh
   ```

2. **Seed ticket metadata** (required for tickets plugin):
   ```bash
   php artisan tickets:seed --only=dispositions,priorities,statuses
   ```

3. **Pre-populate demo pool** (NEW - replaces old method):
   ```bash
   php artisan demo:populate 50
   ```
   This creates 50 demo users with isolated data without Filament context issues.

4. **Start queue worker** (in a separate terminal):
   ```bash
   php artisan queue:work --queue=demo,default
   ```

5. **Start development server**:
   ```bash
   php artisan serve
   npm run dev
   ```

## Key Fixes Applied

- **✅ Removed Filament tenant context** from DemoSeeder (was causing TenantSet errors)
- **✅ Fixed address relationships** to use proper polymorphic many-to-many structure  
- **✅ Fixed DataLens reports** to pass arrays instead of JSON strings
- **✅ Added error handling** for tickets and DataLens when plugins aren't configured
- **✅ Created populate command** for proper pre-seeding workflow

## Testing Auto-Login System

1. Navigate to http://localhost:8000/admin
2. You should see the login form pre-filled with:
   - Email: `demo@padmission.com`
   - Password: `demo2024`
3. Click login - you'll be assigned a unique demo user
4. Check that you're redirected to a team-specific URL (e.g., `/admin/1`)

## Testing Multi-Tenancy

1. After login, navigate between the two teams using the team switcher
2. Create data in one team:
   - Add a new product in Shop > Products
   - Create a new blog post in Blog > Posts
   - Create a Data Lens report
   - Create a support ticket
3. Switch to the other team and verify:
   - The data you created is NOT visible
   - Each team has its own isolated data

## Testing Data Lens Integration

1. Navigate to 📊 Data Lens > Custom Reports
2. You should see 4 pre-configured reports:
   - 📊 Sales Dashboard
   - 📈 Customer Analytics
   - 📦 Product Inventory
   - 📝 Blog Analytics
3. Create a new report:
   - Click "New custom report"
   - Select a model (e.g., Shop Product)
   - Add columns and filters
   - Save and verify it only appears in the current team

## Testing Tickets Integration

1. Navigate to 🎫 Tickets
2. Check that demo tickets exist for some customers
3. Create a new ticket:
   - Click "New ticket"
   - Fill in customer details
   - Submit and verify it's team-specific
4. Test the chat widget (if enabled in app panel)

## Testing Concurrent Users

1. Open multiple browser windows/incognito tabs
2. Visit the login page in each
3. Each should get a different demo user
4. Verify that each user:
   - Has their own isolated data
   - Can work independently without conflicts

## Testing Demo Refresh

1. **Test cleanup** (releases inactive users):
   ```bash
   php artisan demo:refresh
   ```

2. **Test pool replenishment**:
   - Check current pool size in logs
   - If below 50, it should create more instances

3. **Test forced refresh** (WARNING: deletes all data):
   ```bash
   php artisan demo:refresh --force
   ```

## Testing URL Persistence

1. Login as a demo user
2. Navigate to a specific page (e.g., `/admin/1/shop/products`)
3. Copy the URL
4. Logout and wait for cleanup (or force it)
5. Try to access the copied URL
6. You should be redirected to login with message: "Your demo session has expired. Please login again to continue."

## Testing Edge Cases

### No Available Demo Users
1. Set pool size to 0 in config/demo.php
2. Try to login
3. System should create emergency batch synchronously

### Queue Failure
1. Stop the queue worker
2. Login as demo user
3. Check logs - replenishment job should be queued but not processed
4. Start queue worker - jobs should process

### Concurrent Pool Access
1. Use a load testing tool or multiple scripts
2. Hit the login page simultaneously
3. Each request should get a unique demo user
4. No conflicts or duplicate assignments

## Monitoring

Check logs for:
- Demo pool status updates
- User assignments
- Cleanup operations
- Job processing

```bash
tail -f storage/logs/laravel.log
```

## Configuration Testing

Test different configurations in `config/demo.php`:
- Disable demo mode: `DEMO_ENABLED=false`
- Change pool size: `DEMO_POOL_SIZE=100`
- Adjust TTLs: `DEMO_SESSION_TTL=1` (1 hour)
- Change password: `DEMO_PASSWORD=newpassword`

## Performance Testing

1. Monitor database queries with debugbar
2. Check that team scoping is applied correctly
3. Verify no N+1 queries in resource listings
4. Test with large datasets (increase seed counts)

## Security Testing

1. Try to access another team's data directly via URL manipulation
2. Verify 403 errors for unauthorized access
3. Check that demo users can't modify critical settings
4. Ensure admin user is not affected by demo system

## Troubleshooting Common Issues

### 1. "TenantSet: Argument #2 ($user) must be of type Model, null given"
**Cause**: DemoSeeder was trying to set Filament tenant context without authenticated user  
**Fix**: ✅ FIXED - Removed `filament()->setTenant($team)` from seeder  
**Solution**: Use `php artisan demo:populate` instead of running seeder during requests

### 2. "UNIQUE constraint failed: shop_customers.email"
**Cause**: Duplicate customer emails when reusing existing factory data  
**Fix**: ✅ FIXED - Start with fresh database: `php artisan migrate:fresh`  
**Prevention**: Always run `php artisan demo:populate` on clean database

### 3. "table addresses has no column named customer_id"
**Cause**: Addresses use polymorphic many-to-many via `addressables` pivot table  
**Fix**: ✅ FIXED - Updated relationships to use proper pivot table names  
**Solution**: Customer/Brand relationships now use `morphToMany` with explicit pivot table

### 4. "TicketStatus::getClosedStatus(): Return value must be of type TicketStatus, null returned"
**Cause**: Tickets plugin needs status/priority/disposition data seeded first  
**Fix**: ✅ FIXED - Added proper setup workflow  
**Solution**: Run `php artisan tickets:seed --only=dispositions,priorities,statuses` before demo populate

### 5. "CustomReport: Argument #1 ($value) must be of type DataCollection|array, string given"
**Cause**: DataLens expects arrays, not JSON strings for columns/filters/sorts  
**Fix**: ✅ FIXED - Pass arrays directly to model instead of json_encode  
**Solution**: Updated DemoSeeder to pass arrays with proper try/catch

### 6. Demo users not being assigned on login
**Cause**: Pool is empty or demo mode disabled  
**Solution**: 
```bash
# Check pool status
php artisan tinker --execute="echo \App\Models\User::whereNull('email_verified_at')->where('email', 'like', 'demo_%@demo.padmission.com')->count();"

# Replenish if needed
php artisan demo:populate 10
```

### 7. Queue jobs not processing
**Cause**: Queue worker not running or wrong queue name  
**Solution**: 
```bash
# Start queue worker with correct queues
php artisan queue:work --queue=demo,default

# Check failed jobs
php artisan queue:failed
```

## Cleanup

To disable demo mode and return to normal operation:
1. Set `DEMO_ENABLED=false` in `.env`
2. Run `php artisan migrate:fresh --seed` (without demo seeder)
3. Remove demo users: `DELETE FROM users WHERE email LIKE 'demo_%@demo.padmission.com'`