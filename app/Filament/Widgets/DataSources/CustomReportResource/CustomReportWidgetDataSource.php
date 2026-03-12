<?php

namespace App\Filament\Widgets\DataSources\CustomReportResource;

use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use Padmission\DataLens\Models\CustomReport;

class CustomReportWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = CustomReport::class;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            TextAttribute::make('api_uuid')
                ->nullable(),
            NumberAttribute::make('team_id'),
            TextAttribute::make('name'),
            TextAttribute::make('data_model'),
            TextAttribute::make('columns'),
            TextAttribute::make('filters')
                ->nullable(),
            TextAttribute::make('settings')
                ->nullable(),
            RelationshipAttribute::make('creator')
                ->titleAttribute('name'),
            DateAttribute::make('created_at')
                ->time()
                ->nullable(),
            DateAttribute::make('updated_at')
                ->time()
                ->nullable(),
        ];
    }
}
