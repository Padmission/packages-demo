<?php

namespace App\Filament\App\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shop_customer_id')
                    ->numeric(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('total_price')
                    ->numeric(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('new')
                    ->required(),
                TextInput::make('currency')
                    ->required(),
                TextInput::make('shipping_price')
                    ->numeric(),
                TextInput::make('shipping_method'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Select::make('team_id')
                    ->relationship('team', 'name'),
            ]);
    }
}
