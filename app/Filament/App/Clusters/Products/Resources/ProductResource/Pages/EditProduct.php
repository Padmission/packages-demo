<?php

namespace App\Filament\App\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\App\Clusters\Products\Resources\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
