<?php

namespace App\Filament\Widgets\DataSources\Blog;

use App\Models\Blog\Post;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class PostWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Post::class;

    protected string | UnitEnum | null $group = 'Blog';

    protected ?int $sort = 0;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            NumberAttribute::make('blog_author_id')
                ->nullable(),
            NumberAttribute::make('blog_category_id')
                ->nullable(),
            TextAttribute::make('title'),
            TextAttribute::make('slug'),
            TextAttribute::make('content'),
            DateAttribute::make('published_at')
                ->nullable(),
            TextAttribute::make('seo_title')
                ->nullable(),
            TextAttribute::make('seo_description')
                ->nullable(),
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
