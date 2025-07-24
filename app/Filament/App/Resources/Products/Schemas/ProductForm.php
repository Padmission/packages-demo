<?php

namespace App\Filament\App\Resources\Products\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shop_brand_id')
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug'),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('barcode'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('qty')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('security_stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('featured')
                    ->required(),
                Toggle::make('is_visible')
                    ->required(),
                TextInput::make('old_price')
                    ->numeric(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('type'),
                Toggle::make('backorder')
                    ->required(),
                Toggle::make('requires_shipping')
                    ->required(),
                DatePicker::make('published_at'),
                TextInput::make('seo_title'),
                TextInput::make('seo_description'),
                TextInput::make('weight_value')
                    ->numeric()
                    ->default(0),
                TextInput::make('weight_unit')
                    ->required()
                    ->default('kg'),
                TextInput::make('height_value')
                    ->numeric()
                    ->default(0),
                TextInput::make('height_unit')
                    ->required()
                    ->default('cm'),
                TextInput::make('width_value')
                    ->numeric()
                    ->default(0),
                TextInput::make('width_unit')
                    ->required()
                    ->default('cm'),
                TextInput::make('depth_value')
                    ->numeric()
                    ->default(0),
                TextInput::make('depth_unit')
                    ->required()
                    ->default('cm'),
                TextInput::make('volume_value')
                    ->numeric()
                    ->default(0),
                TextInput::make('volume_unit')
                    ->required()
                    ->default('l'),
                TextInput::make('team_id')
                    ->numeric(),
            ]);
    }
}
