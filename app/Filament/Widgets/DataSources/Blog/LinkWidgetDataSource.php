<?php

namespace App\Filament\Widgets\DataSources\Blog;

use App\Models\Blog\Link;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class LinkWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Link::class;

    protected string | UnitEnum | null $group = 'Blog';

    protected ?int $sort = 3;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            TextAttribute::make('url'),
            TextAttribute::make('title'),
            TextAttribute::make('description'),
            TextAttribute::make('color'),
            TextAttribute::make('image')
                ->nullable(),
            DateAttribute::make('created_at')
                ->time()
                ->nullable(),
            DateAttribute::make('updated_at')
                ->time()
                ->nullable(),
            RelationshipAttribute::make('team')
                ->titleAttribute('name')
                ->emptyable(),
        ];
    }
}
