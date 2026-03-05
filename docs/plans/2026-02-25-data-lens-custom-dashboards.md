# Data Lens + Custom Dashboards Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let users pull existing Data Lens stats, charts, and summary tables directly into Filament Custom Dashboards.

**Architecture:** Three wrapper widget classes in the data-lens package extend existing data-lens widgets and add `InteractsWithCustomDashboards`. A shared trait handles configuration loading and form building. A single `DataLensDashboardWidgetConfiguration` model stores which report/summary/widget to display.

**Tech Stack:** Filament v5, Custom Dashboards Plugin v1, data-lens package, Livewire 4, Tailwind CSS v4

---

## Status

The following files are ALREADY CREATED and working:

- `data-lens/database/migrations/03_create_data_lens_cd_configurations_table.php` (migrated)
- `data-lens/src/Models/DataLensDashboardWidgetConfiguration.php`
- `data-lens/src/Widgets/CustomDashboards/Concerns/LoadsDataLensConfiguration.php`
- `data-lens/src/Widgets/CustomDashboards/DataLensStatsWidget.php`
- `data-lens/src/Widgets/CustomDashboards/DataLensChartWidget.php`
- `data-lens/src/Widgets/WidgetServiceProvider.php` (updated with conditional registration)
- `packages-demo.padmission/app/Providers/Filament/AppPanelProvider.php` (updated with stats + chart)

---

### Task 1: Create DataLensTableWidget

**Files:**
- Create: `data-lens/src/Widgets/CustomDashboards/DataLensTableWidget.php`

**Step 1: Create the table widget class**

This widget extends `Filament\Widgets\Widget` (not a data-lens parent, since there's no existing table widget in data-lens). It uses `InteractsWithCustomDashboards` and `LoadsDataLensConfiguration`. Unlike stats/chart, the table widget doesn't need a widget_id -- it shows all summary data.

```php
<?php

declare(strict_types=1);

namespace Padmission\DataLens\Widgets\CustomDashboards;

use Filament\CustomDashboardsPlugin\Enums\WidgetThumbnailImage;
use Filament\CustomDashboardsPlugin\Widgets\Concerns\InteractsWithCustomDashboards;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Padmission\DataLens\Data\Summaries\AggregationData;
use Padmission\DataLens\Data\Summaries\GroupingColumnData;
use Padmission\DataLens\Data\Summaries\SummaryData;
use Padmission\DataLens\Models\CustomReportSummary;
use Padmission\DataLens\Models\DataLensDashboardWidgetConfiguration;
use Padmission\DataLens\Services\SummaryDataService;
use Padmission\DataLens\Widgets\CustomDashboards\Concerns\LoadsDataLensConfiguration;

class DataLensTableWidget extends \Filament\Widgets\Widget
{
    use InteractsWithCustomDashboards;
    use LoadsDataLensConfiguration;

    protected static string $view = 'data-lens::widgets.custom-dashboards.table';

    protected int | string | array $columnSpan = 'full';

    protected ?int $summaryId = null;

    protected ?Collection $tableData = null;

    protected ?SummaryData $summaryData = null;

    public function mount(): void
    {
        $config = $this->dashboardWidget?->configuration;

        if (! $config instanceof DataLensDashboardWidgetConfiguration) {
            return;
        }

        $this->summaryId = $config->summary_id;
    }

    public static function getCustomDashboardLabel(): string
    {
        return 'Data Lens Table';
    }

    public static function getCustomDashboardDescription(): ?string
    {
        return 'Display summary data from a Data Lens report as a table';
    }

    public static function getCustomDashboardThumbnailImage(): WidgetThumbnailImage | string
    {
        return WidgetThumbnailImage::Table;
    }

    public static function getCustomDashboardConfigurationModel(): ?string
    {
        return DataLensDashboardWidgetConfiguration::class;
    }

    public static function configureCustomDashboardConfigurationForm(Schema $schema): Schema
    {
        return $schema->schema([
            static::getReportSelectField(),
            static::getSummarySelectField(),
        ]);
    }

    public function getColumnSpan(): int | string | array
    {
        if ($this->dashboardWidget?->column_span) {
            return $this->dashboardWidget->column_span;
        }

        return $this->columnSpan;
    }

    protected function getSummary(): ?CustomReportSummary
    {
        if ($this->summaryId === null) {
            return null;
        }

        return CustomReportSummary::find($this->summaryId);
    }

    protected function getSummaryData(): ?SummaryData
    {
        return $this->summaryData ??= $this->getSummary()?->getSummaryData();
    }

    public function getTableData(): Collection
    {
        if ($this->tableData !== null) {
            return $this->tableData;
        }

        $summary = $this->getSummary();

        if (! $summary) {
            return $this->tableData = collect();
        }

        return $this->tableData = app(SummaryDataService::class)->getSummaryData($summary);
    }

    /** @return array<array{key: string, label: string}> */
    public function getTableColumns(): array
    {
        $summaryData = $this->getSummaryData();

        if (! $summaryData) {
            return [];
        }

        $columns = [];

        foreach ($summaryData->configuration->groupingColumns as $column) {
            $columns[] = [
                'key' => $column->fieldName,
                'label' => $column->getDisplayName(),
            ];
        }

        foreach ($summaryData->configuration->aggregations as $aggregation) {
            $columns[] = [
                'key' => $aggregation->id,
                'label' => $aggregation->label,
            ];
        }

        return $columns;
    }

    public function getHeading(): ?string
    {
        $summary = $this->getSummary();

        return $summary?->name;
    }

    public function formatCellValue(string $key, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        $summaryData = $this->getSummaryData();

        if (! $summaryData) {
            return (string) $value;
        }

        // Check if this key is an aggregation -- apply formatting
        foreach ($summaryData->configuration->aggregations as $aggregation) {
            if ($aggregation->id === $key) {
                return $aggregation->getDisplayValue($value);
            }
        }

        return (string) $value;
    }
}
```

**Step 2: Verify PHP syntax**

Run: `php -l data-lens/src/Widgets/CustomDashboards/DataLensTableWidget.php`
Expected: No syntax errors detected

---

### Task 2: Create the table Blade view

**Files:**
- Create: `data-lens/resources/views/widgets/custom-dashboards/table.blade.php`

**Step 1: Create the Blade view**

The view renders summary data as a styled table using Filament's widget wrapper and Tailwind classes. It reads columns from `getTableColumns()`, rows from `getTableData()`, and formats values via `formatCellValue()`.

```blade
@php
    $heading = $this->getHeading();
    $columns = $this->getTableColumns();
    $data = $this->getTableData();
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="$heading">
        @if (empty($columns) || $data->isEmpty())
            <div class="flex items-center justify-center p-6 text-sm text-gray-500 dark:text-gray-400">
                No summary data available. Configure a report summary in Data Lens first.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th class="px-3 py-2 text-start text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($data as $row)
                            <tr>
                                @foreach ($columns as $column)
                                    <td class="px-3 py-2 text-sm text-gray-950 dark:text-white">
                                        {{ $this->formatCellValue($column['key'], $row[$column['key']] ?? null) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

---

### Task 3: Register DataLensTableWidget in WidgetServiceProvider

**Files:**
- Modify: `data-lens/src/Widgets/WidgetServiceProvider.php`

**Step 1: Add the import and Livewire registration**

Add `DataLensTableWidget` import alongside existing imports, and register the Livewire component inside the `class_exists` block:

```php
use Padmission\DataLens\Widgets\CustomDashboards\DataLensTableWidget;
```

And inside the conditional block, add:

```php
Livewire::component(
    'padmission.data-lens.widgets.custom-dashboards.table',
    DataLensTableWidget::class
);
```

---

### Task 4: Add DataLensTableWidget to AppPanelProvider

**Files:**
- Modify: `packages-demo.padmission/app/Providers/Filament/AppPanelProvider.php`

**Step 1: Add the import**

```php
use Padmission\DataLens\Widgets\CustomDashboards\DataLensTableWidget;
```

**Step 2: Add to widgets array**

Change the `->widgets([...])` call on `CustomDashboardsPlugin` to include the table widget:

```php
->widgets([
    DataLensStatsWidget::class,
    DataLensChartWidget::class,
    DataLensTableWidget::class,
])
```

---

### Task 5: Verify full integration

**Step 1: Run PHP lint on all new files**

Run:
```bash
php -l data-lens/src/Widgets/CustomDashboards/DataLensTableWidget.php
php -l data-lens/src/Widgets/CustomDashboards/DataLensStatsWidget.php
php -l data-lens/src/Widgets/CustomDashboards/DataLensChartWidget.php
php -l data-lens/src/Widgets/CustomDashboards/Concerns/LoadsDataLensConfiguration.php
php -l data-lens/src/Models/DataLensDashboardWidgetConfiguration.php
```

Expected: All files report no syntax errors.

**Step 2: Verify Filament boots**

Run: `php artisan about --only=Filament`

Expected: Output shows Filament version and package list without errors.

**Step 3: Verify routes resolve**

Run: `php artisan route:list --path=app 2>&1 | head -5`

Expected: Routes listed without errors.

**Step 4: Run Pint on data-lens files**

Run: `cd data-lens && vendor/bin/pint src/Widgets/CustomDashboards/ src/Models/DataLensDashboardWidgetConfiguration.php --format agent`

Expected: Files formatted or already clean.

---

### Task 6: Commit

**Step 1: Stage and commit data-lens changes**

```bash
cd data-lens
git add \
  database/migrations/03_create_data_lens_cd_configurations_table.php \
  src/Models/DataLensDashboardWidgetConfiguration.php \
  src/Widgets/CustomDashboards/ \
  src/Widgets/WidgetServiceProvider.php \
  resources/views/widgets/custom-dashboards/
git commit -m "feat: add custom dashboards integration widgets (stats, chart, table)"
```

**Step 2: Stage and commit demo app changes**

```bash
cd packages-demo.padmission
git add \
  app/Providers/Filament/AppPanelProvider.php \
  docs/plans/
git commit -m "feat: register data lens widgets in custom dashboards plugin"
```
