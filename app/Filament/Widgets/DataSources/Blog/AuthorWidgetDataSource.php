<?php

namespace App\Filament\Widgets\DataSources\Blog;

use App\Models\Blog\Author;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class AuthorWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Author::class;

    protected string | UnitEnum | null $group = 'Blog';

    protected ?int $sort = 2;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            TextAttribute::make('name'),
            TextAttribute::make('email'),
            TextAttribute::make('photo')
                ->nullable(),
            TextAttribute::make('bio')
                ->nullable(),
            TextAttribute::make('github_handle')
                ->nullable(),
            TextAttribute::make('twitter_handle')
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
