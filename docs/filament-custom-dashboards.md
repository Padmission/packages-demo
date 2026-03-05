# Custom Dashboards Plugin - Documentation

> Source: https://filamentphp.com/plugins/filament-custom-dashboards

## Overview

The Custom Dashboards Plugin enables users to build data-driven dashboards with a drag-and-drop interface. Rather than creating separate widget classes for each variation, developers define widget data sources in PHP while users customize through the UI.

## Installation

### Automated Setup

After purchasing a license, configure Composer credentials and install:

```bash
composer config repositories.filament composer https://packages.filamentphp.com/composer
composer config --auth http-basic.packages.filamentphp.com "YOUR_EMAIL_ADDRESS" "YOUR_LICENSE_KEY"
composer require filament/custom-dashboards-plugin:"^1.0@beta"
php artisan filament-cd:install
```

The install command guides you through database setup, theme configuration, CSS imports, asset compilation, and plugin registration.

### Manual Installation

**Step 1: Publish Migrations**

```bash
php artisan filament-cd:publish-migrations
php artisan migrate
```

**Step 2: Add CSS Import**

Add to your theme file (e.g., `resources/css/filament/admin/theme.css`):

```css
@import '../../../../vendor/filament/custom-dashboards-plugin/resources/css/index.css';
```

Create a theme if needed:

```bash
php artisan make:filament-theme
npm run build
```

**Step 3: Register Plugin**

In your panel provider:

```php
use Filament\CustomDashboardsPlugin\CustomDashboardsPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            CustomDashboardsPlugin::make()
                ->discoverDataSources(
                    in: app_path('Filament/Widgets/DataSources'),
                    for: 'App\\Filament\\Widgets\\DataSources'
                ),
        ]);
}
```

## Widget Data Sources

Widget data sources act as intermediaries between Eloquent models and dashboard widgets. They extend `EloquentWidgetDataSource` and define attributes available for charting.

### Creating Data Sources

**Using the Artisan command:**

```bash
php artisan make:filament-cd-widget-data-source Order --generate
```

The `--generate` flag automatically creates attributes from database schema.

**From existing resources:**

```bash
php artisan make:filament-cd-resource-widget-data-sources --generate
```

### Basic Structure

```php
use App\Models\Order;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;

class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Order::class;

    public function getAttributes(): array
    {
        return [
            NumberAttribute::make('amount'),
            NumberAttribute::make('quantity'),
            DateAttribute::make('created_at'),
            TextAttribute::make('status'),
        ];
    }
}
```

## Configuration

### Registering Data Sources

**Manually:**

```php
use Filament\CustomDashboardsPlugin\CustomDashboardsPlugin;

CustomDashboardsPlugin::make()
    ->widgetDataSources([
        OrderWidgetDataSource::class,
        CustomerWidgetDataSource::class,
    ])
```

**Automatically:**

```php
CustomDashboardsPlugin::make()
    ->discoverDataSources(
        in: app_path('Filament/Widgets/DataSources'),
        for: 'App\\Filament\\Widgets\\DataSources'
    )
```

### Registering Custom Widgets

```php
CustomDashboardsPlugin::make()
    ->widgets([
        RecentOrdersWidget::class,
        TopCustomersWidget::class,
    ])
```

Or discover automatically:

```php
CustomDashboardsPlugin::make()
    ->discoverWidgets(
        in: app_path('Filament/Widgets'),
        for: 'App\\Filament\\Widgets'
    )
```

## Attribute Types

### Number Attribute

```php
NumberAttribute::make('price')
    ->label('Price')
```

**Money formatting:**

```php
NumberAttribute::make('price')
    ->money('usd', divideBy: 100, locale: 'en_US', decimalPlaces: 2)
```

**Decimal customization:**

```php
NumberAttribute::make('rating')
    ->decimalPlaces(2)
    ->decimalSeparator(',')
    ->thousandsSeparator('.')
    ->locale('de_DE')
```

### Date Attribute

```php
DateAttribute::make('created_at')
    ->label('Created at')
    ->time()  // Include time component
    ->past(false)  // Disallow past dates
    ->future(true)  // Allow future dates
```

### Text Attribute

```php
TextAttribute::make('name')
    ->label('Name')
    ->enum(OrderStatus::class)  // Associate with enum
```

### Boolean Attribute

```php
BooleanAttribute::make('is_active')
    ->label('Active')
```

### Relationship Attribute

```php
RelationshipAttribute::make('customer')
    ->label('Customer')
    ->titleAttribute('name')
    ->multiple()  // For HasMany/BelongsToMany
    ->emptyable()  // For optional relationships
```

### Global Attribute Options

```php
NumberAttribute::make('discount')
    ->nullable()
    ->formatValueUsing(fn ($value) => '$' . number_format($value, 2))
    ->queryBuilderConstraint(
        fn () => TextConstraint::make('amount')
            ->operators([IsFilledOperator::make()])
    )
```

## Advanced Attributes

### Related Attributes

Access related model attributes using dot notation:

```php
public function getAttributes(): array
{
    return [
        TextAttribute::make('customer.name')
            ->label('Customer name'),
        NumberAttribute::make('customer.credit_limit')
            ->label('Customer credit limit'),
    ];
}
```

### Authorization

Default behavior checks model policy:

```php
public function canAccess(): bool
{
    return get_authorization_response('viewAny', $this->getModel())->allowed();
}
```

Override for custom logic:

```php
public function canAccess(): bool
{
    return auth()->user()?->can('viewOrderAnalytics');
}

public function shouldSkipAuthorization(): bool
{
    return true;
}
```

## Grouping and Sorting

```php
class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $group = 'Sales';
    protected ?int $sort = 10;
}
```

Using enums:

```php
enum DataSourceGroup: string
{
    case Sales = 'sales';
    case Analytics = 'analytics';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Analytics => 'Analytics',
        };
    }
}

class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected string | UnitEnum | null $group = DataSourceGroup::Sales;
}
```

## Dashboard Sharing

### User Sharing

Control with three roles: Owner (full control), Write (edit only), Read (view only).

**Disable user sharing:**

```php
CustomDashboardsPlugin::make()
    ->userSharing(false)
```

**Control default role:**

```php
CustomDashboardsPlugin::make()
    ->defaultRole(false)  // Hide from UI

// Or use closure:
CustomDashboardsPlugin::make()
    ->defaultRole(fn () => Filament::auth()->user()?->is_admin)
```

**Scope shareable users:**

```php
CustomDashboardsPlugin::make()
    ->scopeUserSharingUsing(
        fn (Builder $query, $user) => $query->where('team_id', $user->team_id)
    )
```

### Teams and Organizations

**Step 1: Implement interface**

```php
use Filament\CustomDashboardsPlugin\Contracts\CanReceiveSharedDashboards;

class Team extends Model implements CanReceiveSharedDashboards
{
    public static function getDashboardShareableLabel(): string
    {
        return 'Team';
    }

    public static function getDashboardShareableTitleAttribute(): string
    {
        return 'name';
    }

    public static function resolveDashboardShareablesForUser(Authenticatable $user): ?Relation
    {
        return $user->teams();
    }

    public static function getDashboardShareableOptionsQuery(Authenticatable $user): Builder
    {
        return $user->teams()->getQuery();
    }
}
```

**Step 2: Register models**

```php
CustomDashboardsPlugin::make()
    ->shareableModels([
        Team::class,
        Organization::class,
    ])
```

**Customizing options query:**

```php
// Only show teams user owns
public static function getDashboardShareableOptionsQuery(Authenticatable $user): Builder
{
    return static::query()->where('owner_id', $user->getKey());
}

// Show teams from user's organization
public static function getDashboardShareableOptionsQuery(Authenticatable $user): Builder
{
    return static::query()->where('organization_id', $user->organization_id);
}
```

## Widget Types

### Stats Widget

Displays statistic cards with aggregation:

- **Metric options:** Count, Sum, Average, Min, Max
- **Value field:** Select attribute to aggregate (for non-count metrics)
- **Filters:** Query builder integration

### Line Chart Widget

Data over time or continuous values:

- **Horizontal axis:** Date, number, or relationship attributes
- **Date range:** Rolling window or specific dates
- **Time period:** Preset ranges or custom
- **Metric:** Count, Sum, Average, Min, Max
- **Running total:** Enable cumulative mode (count/sum only)
- **Filters:** Query builder constraints

### Bar Chart Widget

Categorical data as vertical bars:

- **Group by:** Date, number, boolean, text enum, or relationship attributes
- **Display mode:** Show items separately, count items, or show presence
- **Metric:** Count, Sum, Average, Min, Max
- **Value field:** Number attributes only
- **Filters:** Query builder

### Pie/Doughnut Chart Widgets

Proportional data as slices:

- Same configuration as bar chart
- Doughnut has center hole visualization

### Polar Area Chart Widget

Circular data layout:

- Same configuration as pie/doughnut charts

### Scatter Chart Widget

X-Y axis correlation display:

- **X-axis:** Date, number, or relationship attributes
- **Metric:** Count, Sum, Average, Min, Max (or blank for individual points)
- **Y-axis:** Number or relationship attributes
- **Filters:** Query builder

### Table Widget

Tabular data display:

- **Columns:** Add multiple attributes
- **Display mode:** Show items separately, count items, or show presence
- **Filters:** Query builder constraints

## Custom Widgets

Create fixed-behavior widgets users can add without configuration:

```php
use Filament\CustomDashboardsPlugin\Widgets\Concerns\InteractsWithCustomDashboards;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecentOrdersWidget extends StatsOverviewWidget
{
    use InteractsWithCustomDashboards;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', Order::query()->count()),
            Stat::make('Pending Orders', Order::query()->where('status', 'pending')->count()),
            Stat::make('Revenue', '$' . number_format(Order::query()->sum('total'), 2)),
        ];
    }

    public static function getCustomDashboardLabel(): string
    {
        return 'Recent Orders';
    }

    public static function getCustomDashboardDescription(): ?string
    {
        return 'Overview of recent order statistics';
    }
}
```

### Custom Widget Configuration

**Create configuration model:**

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecentOrdersWidgetConfiguration extends Model
{
    protected $guarded = [];
    protected $table = 'recent_orders_widget_configs';
    protected $casts = [
        'show_pending' => 'boolean',
        'show_completed' => 'boolean',
    ];
}
```

**Create migration:**

```php
Schema::create('recent_orders_widget_configs', function (Blueprint $table): void {
    $table->id();
    $table->boolean('show_pending')->default(true);
    $table->boolean('show_completed')->default(true);
    $table->timestamps();
});
```

**Define form:**

```php
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

public static function configureCustomDashboardConfigurationForm(Schema $schema): Schema
{
    return $schema->schema([
        Checkbox::make('show_pending')
            ->label('Show pending orders')
            ->default(true),
        Checkbox::make('show_completed')
            ->label('Show completed orders')
            ->default(true),
    ]);
}

public static function getCustomDashboardConfigurationModel(): ?string
{
    return RecentOrdersWidgetConfiguration::class;
}
```

**Access configuration:**

```php
protected function getStats(): array
{
    $configuration = $this->dashboardWidget->configuration;
    assert($configuration instanceof RecentOrdersWidgetConfiguration);

    $stats = [];
    $stats[] = Stat::make('Total Orders', Order::query()->count());

    if ($configuration->show_pending) {
        $stats[] = Stat::make('Pending Orders', Order::query()->where('status', 'pending')->count());
    }

    if ($configuration->show_completed) {
        $stats[] = Stat::make('Completed Orders', Order::query()->where('status', 'completed')->count());
    }

    return $stats;
}
```

## Advanced Configuration

### Customizing Navigation Item

```php
use Filament\CustomDashboardsPlugin\CustomDashboardsPlugin;
use Filament\Navigation\NavigationItem;

CustomDashboardsPlugin::make()
    ->navigationItem(fn (NavigationItem $item) => $item
        ->sort(3)
        ->group('Tools')
    )
```

### Embedding Dashboards in Pages

```php
use Filament\CustomDashboardsPlugin\Actions\EditEmbeddedDashboardsAction;
use Filament\CustomDashboardsPlugin\Concerns\InteractsWithEmbeddedDashboards;
use Filament\CustomDashboardsPlugin\Contracts\HasEmbeddedDashboards;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords implements HasEmbeddedDashboards
{
    use InteractsWithEmbeddedDashboards;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditEmbeddedDashboardsAction::make(),
            CreateAction::make(),
        ];
    }
}
```

**Custom component identifier:**

```php
public function getEmbeddedDashboardComponent(): string
{
    return 'orders.list';
}
```

### Customizing Query Builder Constraints

```php
use Filament\QueryBuilder\Constraints\DateConstraint;
use Filament\QueryBuilder\Constraints\NumberConstraint;
use Filament\QueryBuilder\Constraints\TextConstraint;

public function getQueryBuilderConstraints(): array
{
    return [
        TextConstraint::make('status')
            ->label('Order status'),
        NumberConstraint::make('amount')
            ->label('Order amount')
            ->operators([IsMinOperator::make(), IsMaxOperator::make()]),
        DateConstraint::make('created_at')
            ->label('Created at'),
    ];
}
```

**Customize columns:**

```php
public function getQueryBuilderConstraintPickerColumns(): array | int
{
    return 3;  // Display in 3 columns
}
```

## Migrations

The plugin uses semantic tracking instead of filenames. Migrations are tagged with identifiers (e.g., `create_filament_cd_dashboards_table`). A `filament_cd` column tracks execution in your migrations table.

**After updates:**

```bash
php artisan filament-cd:publish-migrations
php artisan migrate
```

**Force republish all migrations:**

```bash
php artisan filament-cd:publish-migrations --force
```

**Automate with Composer:**

```json
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan filament-cd:publish-migrations --no-interaction"
        ]
    }
}
```

## Reference

### Metric Options

- **Count:** Record count (no value field required)
- **Sum:** Numeric attribute total
- **Average:** Numeric attribute mean
- **Min:** Numeric attribute minimum
- **Max:** Numeric attribute maximum

Running totals available for count/sum metrics in line charts only.

### Date Grouping Periods

Second, Minute, Hour, Day, Month, Quarter, Year, Decade

### Date Range Presets

**Past:** Past minute/hour/week/2 weeks/month/quarter/6 months/year/2 years/5 years/decade

**Present:** This minute/hour/day/month/quarter/year/decade

**Future:** Next minute/hour/week/2 weeks/month/quarter/6 months/year/2 years/5 years/decade

Plus custom absolute date ranges.

### Filter Constraints

- **Text:** Equals, contains, starts with, ends with, is filled, not filled
- **Number:** Equals, not equals, greater than, less than, between, is filled, not filled
- **Date:** Equals, before, after, between, is filled, not filled
- **Boolean:** Is true, is false, is filled, not filled
- **Relationship:** Equals, contains (multiple only), is filled, not filled

## Complete Example

```php
namespace App\Filament\Widgets\DataSources;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;

class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Order::class;

    public function getAttributes(): array
    {
        return [
            NumberAttribute::make('amount')
                ->label('Amount')
                ->money('usd', divideBy: 100),
            NumberAttribute::make('quantity')
                ->label('Quantity'),
            DateAttribute::make('created_at')
                ->label('Created at'),
            DateAttribute::make('completed_at')
                ->label('Completed at')
                ->nullable(),
            TextAttribute::make('status')
                ->label('Status')
                ->enum(OrderStatus::class),
            TextAttribute::make('priority')
                ->label('Priority')
                ->enum(OrderPriority::class),
            RelationshipAttribute::make('customer')
                ->label('Customer')
                ->emptyable()
                ->titleAttribute('name'),
            TextAttribute::make('customer.name')
                ->label('Customer name'),
            NumberAttribute::make('customer.credit_limit')
                ->label('Customer credit limit')
                ->money('usd', divideBy: 100),
        ];
    }
}
```

## Support

For issues or suggestions, visit the [Custom Dashboards Issues GitHub repository](https://github.com/filamentphp/custom-dashboards-plugin). Add your GitHub username to your customer dashboard at [packages.filamentphp.com](https://packages.filamentphp.com) to access the repository. For account/license questions, email support@filamentphp.com.
