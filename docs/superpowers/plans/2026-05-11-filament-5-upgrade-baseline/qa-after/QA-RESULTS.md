# Filament 5 Upgrade — QA Evidence

Captured via agent-browser against the live local Herd domain
`https://packages-demo.padmission.test` running the `chore/filament-5-upgrade` branch.

Login flow: demo@padmission.com / demo2024 → routes to tenant panel (Acme Corporation, tenant 14).

## Tenant panel — all 9 resource list pages load

| Resource | Title | Screenshot |
|---|---|---|
| Customers | Customers - Filament Demo | tenant-list-shop-customers.png |
| Orders | Orders - Filament Demo (26 orders) | tenant-list-shop-orders.png |
| Products | Products - Filament Demo | tenant-list-shop-products.png |
| Brands | Brands - Filament Demo | tenant-list-shop-products-brands.png |
| Product Categories | Categories - Filament Demo | tenant-list-shop-products-categories.png |
| Authors | Authors - Filament Demo | tenant-list-blog-authors.png |
| Blog Categories | Categories - Filament Demo | tenant-list-blog-categories.png |
| Links | Links - Filament Demo | tenant-list-blog-links.png |
| Posts | Posts - Filament Demo | tenant-list-blog-posts.png |

## DataLens — original `Table::records()` bug fully resolved

The Flare error (#101, 2026-05-11) was at `https://datalens-demo.padmission.com/629/custom-dashboards/testing` with:

> Filament\Tables\Table::records(): Argument #1 ($dataSource) must be of type ?Closure, Illuminate\Support\Collection given, called in vendor/padmission/data-lens/src/Widgets/CustomDashboards/DataLensReportTableWidget.php on line 84

Three independent paths confirm the bug is dead:

### 1. Custom Report direct view (`/14/custom-reports/82` 🛍️ Product Catalog)
Renders a full Filament 5 table with real product data — Product / SKU / Brand / Price / Stock / Visible columns. (12-custom-report-82-product-catalog.png)

### 2. Custom Dashboard widget mount (Insert widget → Data Lens Report Table → 🛍️ Product Catalog)
Laravel log captured during the widget mount on 2026-05-12 08:53:16:
```
DataLens - Table Provider Created {"table_class":"Filament\\Tables\\Table","has_columns_method":true}
DataLens - Columns Built {"column_count":6,"column_names":["name","sku","brand.name","price","qty","is_visible"]}
DataLens - Columns Mounted {"mounted_count":6,...}
```
No `TypeError`, no `Argument #1 must be of type ?Closure`. (34-widget-picker.png, 35-widget-config.png, 36-configure-report-table.png, 37-report-selected.png)

### 3. Custom Dashboards index page
Loads cleanly at `/14/custom-dashboards` (01-tenant-custom-dashboards.png).

## Admin panel

Demo users are tenant-scoped — admin panel resources redirect demo users back to `/admin` (Dashboard).
Admin panel itself loads (20-admin-after-demo-login.png); the resource walk evidences the redirect
behavior consistently (admin-list-*.png each captures the dashboard fallback).

## Console

No browser-console exceptions or 500-status fetches were observed during the resource walk. The
agent-browser daemon occasionally stuttered ("Resource temporarily unavailable") under rapid command
sequences — orthogonal to the app; recovered with daemon restart.

## Build + framework health

- `php artisan filament:cache-components` — clean
- `php artisan optimize:clear` — clean
- `npm run build` — clean (theme CSS byte-identical to pre-upgrade baseline)
- PHPStan — 145 errors, all pre-existing Eloquent/Larastan issues (zero new from upgrade)
- No security advisories
