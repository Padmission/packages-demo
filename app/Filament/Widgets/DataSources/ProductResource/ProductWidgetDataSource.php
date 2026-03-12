<?php

namespace App\Filament\Widgets\DataSources\ProductResource;

use App\Models\Shop\Product;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\BooleanAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class ProductWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Product::class;

    protected string|UnitEnum|null $group = 'Shop';

    protected ?int $sort = 0;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            NumberAttribute::make('shop_brand_id')
                ->nullable(),
            TextAttribute::make('name'),
            TextAttribute::make('slug')
                ->nullable(),
            TextAttribute::make('sku')
                ->nullable(),
            TextAttribute::make('barcode')
                ->nullable(),
            TextAttribute::make('description')
                ->nullable(),
            NumberAttribute::make('qty'),
            NumberAttribute::make('security_stock'),
            BooleanAttribute::make('featured'),
            BooleanAttribute::make('is_visible'),
            NumberAttribute::make('old_price')
                ->money()
                ->nullable(),
            NumberAttribute::make('price')
                ->money()
                ->nullable(),
            NumberAttribute::make('cost')
                ->money()
                ->nullable(),
            TextAttribute::make('type')
                ->nullable(),
            BooleanAttribute::make('backorder'),
            BooleanAttribute::make('requires_shipping'),
            DateAttribute::make('published_at')
                ->nullable(),
            TextAttribute::make('seo_title')
                ->nullable(),
            TextAttribute::make('seo_description')
                ->nullable(),
            NumberAttribute::make('weight_value')
                ->nullable(),
            TextAttribute::make('weight_unit'),
            NumberAttribute::make('height_value')
                ->nullable(),
            TextAttribute::make('height_unit'),
            NumberAttribute::make('width_value')
                ->nullable(),
            TextAttribute::make('width_unit'),
            NumberAttribute::make('depth_value')
                ->nullable(),
            TextAttribute::make('depth_unit'),
            NumberAttribute::make('volume_value')
                ->nullable(),
            TextAttribute::make('volume_unit'),
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
