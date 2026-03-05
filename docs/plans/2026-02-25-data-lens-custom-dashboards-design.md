# Data Lens + Custom Dashboards Integration

## Problem

Users create reports with summaries (stats, charts, grouped tables) in Data Lens. They want to pull those existing widgets directly into Filament Custom Dashboards without recreating them.

## Solution

Three custom dashboard widget classes in the data-lens package, each extending a data-lens parent and adding the Custom Dashboards `InteractsWithCustomDashboards` trait.

## Architecture

```
DataLensStatsWidget  -> extends data-lens StatsOverviewWidget + InteractsWithCustomDashboards
DataLensChartWidget  -> extends data-lens ChartWidget + InteractsWithCustomDashboards
DataLensTableWidget  -> extends Filament Widget + InteractsWithCustomDashboards
```

Shared across all three:
- `LoadsDataLensConfiguration` trait: config loading + form field builders
- `DataLensDashboardWidgetConfiguration` model: stores report_id, summary_id, widget_id
- One migration for the configuration table

## Configuration Flow

1. Select Report -- tenant-scoped dropdown of data-lens reports
2. Select Summary -- reactive, filtered by report
3. Select Widget (stats/chart only) -- reactive, filtered by summary + widget type. Table widget skips this step and shows all summary data.

## Widget Rendering

- **Stats & Chart:** `mount()` reads `DashboardWidget->configuration`, sets `summaryId` + `widgetConfig` on parent. Existing data-lens rendering handles the rest.
- **Table:** `mount()` reads configuration, calls `SummaryDataService->getSummaryData()`, renders grouped/aggregated rows via a Blade view. Columns derived from summary grouping columns + aggregations.

## Column Span

Each widget overrides `getColumnSpan()`: prefers Custom Dashboards' `dashboardWidget->column_span` (user-resizable), falls back to data-lens default.

## Conditional Registration

`WidgetServiceProvider` checks `class_exists(CustomDashboardsPlugin::class)` before registering Livewire components. No hard dependency on the paid plugin.

## Files

### data-lens package (new)
- `database/migrations/03_create_data_lens_cd_configurations_table.php`
- `src/Models/DataLensDashboardWidgetConfiguration.php`
- `src/Widgets/CustomDashboards/Concerns/LoadsDataLensConfiguration.php`
- `src/Widgets/CustomDashboards/DataLensStatsWidget.php`
- `src/Widgets/CustomDashboards/DataLensChartWidget.php`
- `src/Widgets/CustomDashboards/DataLensTableWidget.php`
- `resources/views/widgets/custom-dashboards/table.blade.php`

### data-lens package (modified)
- `src/Widgets/WidgetServiceProvider.php`

### demo app (modified)
- `app/Providers/Filament/AppPanelProvider.php`
