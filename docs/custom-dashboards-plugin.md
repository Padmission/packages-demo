# Filament Custom Dashboards Plugin

Source: https://filamentphp.com/plugins/filament-custom-dashboards

## Overview

Enables users to build personalized dashboards with drag-and-drop interfaces without requiring custom widget code. Users define data through PHP-based widget data sources while the plugin handles configurable charts, stats, and tables.

## Installation

```bash
composer require filament/custom-dashboards-plugin:"^1.0@beta"
php artisan filament-cd:install
```

### Manual Installation

1. Publish and run migrations:
```bash
php artisan filament-cd:publish-migrations
php artisan migrate
```

2. Add CSS import to theme file:
```css
@import '../../../../vendor/filament/custom-dashboards-plugin/resources/css/index.css';
```

3. Register plugin in panel provider:
```php
use Filament\CustomDashboardsPlugin\CustomDashboardsPlugin;

CustomDashboardsPlugin::make()
    ->discoverDataSources(
        in: app_path('Filament/Widgets/DataSources'),
        for: 'App\\Filament\\Widgets\\DataSources'
    )
```

## Plugin Configuration

### Registering Widget Data Sources - Manual

```php
CustomDashboardsPlugin::make()
    ->widgetDataSources([
        OrderWidgetDataSource::class,
        CustomerWidgetDataSource::class,
    ])
```

### Registering Widget Data Sources - Auto-Discovery

```php
CustomDashboardsPlugin::make()
    ->discoverDataSources(
        in: app_path('Filament/Widgets/DataSources'),
        for: 'App\\Filament\\Widgets\\DataSources'
    )
```

### Registering Custom Widgets - Manual

```php
CustomDashboardsPlugin::make()
    ->widgets([
        RecentOrdersWidget::class,
        TopCustomersWidget::class,
    ])
```

### Registering Custom Widgets - Auto-Discovery

```php
CustomDashboardsPlugin::make()
    ->discoverWidgets(
        in: app_path('Filament/Widgets'),
        for: 'App\\Filament\\Widgets'
    )
```

## Creating Widget Data Sources

### Artisan Commands

```bash
php artisan make:filament-cd-widget-data-source Order
php artisan make:filament-cd-widget-data-source Order --generate
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

## Attribute Types

### NumberAttribute

```php
NumberAttribute::make('price')
    ->label('Price')

// Money formatting
NumberAttribute::make('price')
    ->money('usd')

NumberAttribute::make('price')
    ->money(
        currency: 'usd',
        divideBy: 100,
        locale: 'en_US',
        decimalPlaces: 2,
    )

// Decimal customization
NumberAttribute::make('rating')
    ->decimalPlaces(2)
    ->decimalSeparator(',')
    ->thousandsSeparator('.')

NumberAttribute::make('rating')
    ->maxDecimalPlaces(2)

// Locale
NumberAttribute::make('amount')
    ->locale('de_DE')
```

### DateAttribute

```php
DateAttribute::make('created_at')
    ->label('Created at')

// With time
DateAttribute::make('created_at')
    ->time()

// Date range control
DateAttribute::make('scheduled_at')
    ->past(false)
    ->future(true)
```

### TextAttribute

```php
TextAttribute::make('name')
    ->label('Name')

// With enum
TextAttribute::make('status')
    ->enum(OrderStatus::class)
```

### BooleanAttribute

```php
BooleanAttribute::make('is_active')
    ->label('Active')
```

### RelationshipAttribute

```php
RelationshipAttribute::make('customer')
    ->label('Customer')
    ->titleAttribute('name')

// Multiple relationships
RelationshipAttribute::make('tags')
    ->multiple()
    ->titleAttribute('name')

// Optional relationships
RelationshipAttribute::make('manager')
    ->emptyable()
    ->titleAttribute('name')
```

### Common Methods

```php
// Custom labels
NumberAttribute::make('total_amount')
    ->label('Total amount')

// Nullable values
NumberAttribute::make('discount')
    ->nullable()

// Custom formatting
NumberAttribute::make('price')
    ->formatValueUsing(fn ($value) => '$' . number_format($value, 2))

// Custom filter constraints
NumberAttribute::make('amount')
    ->queryBuilderConstraint(
        fn () => TextConstraint::make('amount')
            ->operators([
                IsFilledOperator::make()
                    ->label('Has a value'),
            ])
    )
```

### Accessing Related Attributes

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

## Grouping and Sorting

### Grouping Data Sources

```php
class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Order::class;
    protected ?string $group = 'Sales';
}
```

With enums:

```php
class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected string | UnitEnum | null $group = DataSourceGroup::Sales;
}

enum DataSourceGroup: string
{
    case Sales = 'sales';
    case Analytics = 'analytics';
    case Reports = 'reports';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Analytics => 'Analytics',
            self::Reports => 'Reports',
        };
    }
}
```

### Sorting Data Sources

```php
class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $group = 'Sales';
    protected ?int $sort = 10;
}
```

## Authorization

```php
// Default: checks model policy's viewAny method

// Custom authorization
public function canAccess(): bool
{
    return auth()->user()?->can('viewOrderAnalytics');
}

// Skip authorization
public function shouldSkipAuthorization(): bool
{
    return true;
}
```

## Dashboard Sharing

### User Sharing

```php
CustomDashboardsPlugin::make()
    ->userSharing(false)
```

### Default Role

```php
CustomDashboardsPlugin::make()
    ->defaultRole(false)

CustomDashboardsPlugin::make()
    ->defaultRole(fn () => Filament::auth()->user()?->is_admin)
```

### Scope Shareable Users

```php
CustomDashboardsPlugin::make()
    ->scopeUserSharingUsing(
        fn (Builder $query, $user) => $query->where('team_id', $user->team_id)
    )
```

## Teams and Organizations

### Implement CanReceiveSharedDashboards Interface

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

### Register Shareable Models

```php
CustomDashboardsPlugin::make()
    ->shareableModels([
        Team::class,
        Organization::class,
    ])
```

## Supported Widget Types

### Stats Widget
Aggregated statistic cards. Metrics: count, sum, average, min, max with filter support.

### Line Chart Widget
Data over time/continuous values. Features: time/grouping config, date range type (rolling/specific), time period presets, running total (count/sum).

### Bar Chart Widget
Categorical data as vertical bars. Features: grouping by dimension, relationship display modes (show separately, count related, show presence).

### Pie Chart Widget
Proportional data as circle slices. Same config as bar charts.

### Doughnut Chart Widget
Pie chart with center hole. Identical config to pie charts.

### Polar Area Chart Widget
Circular layout with varying segment sizes. Same config as pie charts.

### Scatter Chart Widget
X-Y axis data points. Features: X/Y axis field selection, optional metric aggregation.

### Table Widget
Tabular data display. Features: multiple column attributes, relationship display modes, filters.

## Custom Widgets with InteractsWithCustomDashboards

```php
use Filament\CustomDashboardsPlugin\Widgets\Concerns\InteractsWithCustomDashboards;

class RecentOrdersWidget extends StatsOverviewWidget
{
    use InteractsWithCustomDashboards;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', Order::query()->count()),
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

### Custom Widget with Configuration Model

```php
class RecentOrdersWidget extends StatsOverviewWidget
{
    use InteractsWithCustomDashboards;

    public static function configureCustomDashboardConfigurationForm(Schema $schema): Schema
    {
        return $schema->schema([
            Checkbox::make('show_pending')->label('Show pending orders')->default(true),
            Checkbox::make('show_completed')->label('Show completed orders')->default(true),
        ]);
    }

    public static function getCustomDashboardConfigurationModel(): ?string
    {
        return RecentOrdersWidgetConfiguration::class;
    }

    protected function getStats(): array
    {
        $configuration = $this->dashboardWidget->configuration;
        assert($configuration instanceof RecentOrdersWidgetConfiguration);
        // Use $configuration->show_pending, etc.
    }
}
```

### Custom Widget Methods

- `getCustomDashboardId()` - Stored identifier (defaults to class name)
- `getCustomDashboardLabel()` - Display name
- `getCustomDashboardDescription()` - Optional description
- `canAccess()` - Authorization control

## Navigation Customization

```php
CustomDashboardsPlugin::make()
    ->navigationItem(fn (NavigationItem $item) => $item
        ->sort(3)
        ->group('Tools')
    )
```

## Embedded Dashboards

```php
use Filament\CustomDashboardsPlugin\Actions\EditEmbeddedDashboardsAction;
use Filament\CustomDashboardsPlugin\Concerns\InteractsWithEmbeddedDashboards;
use Filament\CustomDashboardsPlugin\Contracts\HasEmbeddedDashboards;

class ListOrders extends ListRecords implements HasEmbeddedDashboards
{
    use InteractsWithEmbeddedDashboards;

    protected function getHeaderActions(): array
    {
        return [
            EditEmbeddedDashboardsAction::make(),
            CreateAction::make(),
        ];
    }
}
```

Custom component identifier:

```php
public function getEmbeddedDashboardComponent(): string
{
    return 'orders.list';
}
```

## Query Builder Customization

```php
public function getQueryBuilderConstraints(): array
{
    return [
        TextConstraint::make('status')->label('Order status'),
        NumberConstraint::make('amount')->label('Order amount'),
        DateConstraint::make('created_at')->label('Created at'),
    ];
}

public function getQueryBuilderConstraintPickerColumns(): array | int
{
    return 3;
}
```

## Custom WidgetDataSource (Non-Eloquent)

The `DataLensWidgetDataSource` in this project extends `WidgetDataSource` (not `EloquentWidgetDataSource`) and implements chart/stat interfaces directly. It uses Data Lens report summaries as the data source instead of Eloquent models.

Interfaces available:
- `StatsOverviewWidgetDataSource`
- `BarChartWidgetDataSource`
- `LineChartWidgetDataSource`
- `PieChartWidgetDataSource`
- `DoughnutChartWidgetDataSource`
- `PolarAreaChartWidgetDataSource`
- `ScatterChartWidgetDataSource` (not used in current implementation)
- `TableWidgetDataSource` (not used in current implementation)

## Reference

### Metric Options
Count, Sum, Average, Min, Max. Running total support (count/sum in line charts).

### Date Grouping Units
Second, Minute, Hour, Day, Month, Quarter, Year, Decade

### Date Range Presets
- **Past**: Past minute, hour, week, 2 weeks, month, quarter, 6 months, year, 2 years, 5 years, decade
- **Present**: This minute, hour, today, month, quarter, year, decade
- **Future**: Next minute, hour, week, 2 weeks, month, quarter, 6 months, year, 2 years, 5 years, decade

### Filter Constraints by Type
- **Text**: Equals, contains, starts with, ends with, is filled, is not filled
- **Number**: Equals, not equals, greater than, less than, between, is filled, is not filled
- **Date**: Equals, before, after, between, is filled, is not filled
- **Boolean**: Is true, is false, is filled, is not filled
- **Relationship**: Equals, contains (multiple), is filled, is not filled

### Artisan Commands
- `php artisan filament-cd:install` - Full install
- `php artisan filament-cd:publish-migrations` - Publish migrations
- `php artisan make:filament-cd-widget-data-source <Name>` - Create data source
- `php artisan make:filament-cd-widget-data-source <Name> --generate` - Auto-generate from model
- `php artisan make:filament-cd-resource-widget-data-sources --generate` - Generate from all resources
