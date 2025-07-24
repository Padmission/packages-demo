<?php

namespace App\Filament\App\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop_brand_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('security_stock')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('featured')
                    ->boolean(),
                IconColumn::make('is_visible')
                    ->boolean(),
                TextColumn::make('old_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('type')
                    ->searchable(),
                IconColumn::make('backorder')
                    ->boolean(),
                IconColumn::make('requires_shipping')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('seo_title')
                    ->searchable(),
                TextColumn::make('seo_description')
                    ->searchable(),
                TextColumn::make('weight_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight_unit')
                    ->searchable(),
                TextColumn::make('height_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('height_unit')
                    ->searchable(),
                TextColumn::make('width_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('width_unit')
                    ->searchable(),
                TextColumn::make('depth_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('depth_unit')
                    ->searchable(),
                TextColumn::make('volume_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('volume_unit')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('team_id')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
