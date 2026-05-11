<?php

namespace App\Filament\Widgets\DataSources\Shop;

use App\Enums\OrderStatus;
use App\Models\Shop\Order;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\Attribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\DateAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\NumberAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\RelationshipAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\Attributes\TextAttribute;
use Filament\CustomDashboardsPlugin\Widgets\DataSources\EloquentWidgetDataSource;
use UnitEnum;

class OrderWidgetDataSource extends EloquentWidgetDataSource
{
    protected ?string $model = Order::class;

    protected string | UnitEnum | null $group = 'Shop';

    protected ?int $sort = 1;

    /**
     * @return array<Attribute>
     */
    public function getAttributes(): array
    {
        return [
            NumberAttribute::make('shop_customer_id')
                ->nullable(),
            TextAttribute::make('number'),
            NumberAttribute::make('total_price')
                ->money()
                ->nullable(),
            TextAttribute::make('status')
                ->enum(OrderStatus::class),
            TextAttribute::make('currency'),
            NumberAttribute::make('shipping_price')
                ->money()
                ->nullable(),
            TextAttribute::make('shipping_method')
                ->nullable(),
            TextAttribute::make('notes')
                ->nullable(),
            DateAttribute::make('created_at')
                ->time()
                ->nullable(),
            DateAttribute::make('updated_at')
                ->time()
                ->nullable(),
            DateAttribute::make('deleted_at')
                ->time()
                ->nullable(),
            RelationshipAttribute::make('team')
                ->titleAttribute('name')
                ->emptyable(),
        ];
    }
}
