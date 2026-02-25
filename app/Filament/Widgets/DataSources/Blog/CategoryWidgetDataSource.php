<?php

namespace App\Filament\Widgets\DataSources\Blog;

use App\Models\Blog\Category;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\BooleanAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class CategoryWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Category::class;

    protected string|UnitEnum|null $group = 'Blog';

    protected ?int $sort = 1;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            TextAttribute::make('name'),
            TextAttribute::make('slug'),
            TextAttribute::make('description')
                ->nullable(),
            BooleanAttribute::make('is_visible'),
            TextAttribute::make('seo_title')
                ->nullable(),
            TextAttribute::make('seo_description')
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
