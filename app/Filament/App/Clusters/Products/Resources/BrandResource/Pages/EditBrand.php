<?php

namespace App\Filament\App\Clusters\Products\Resources\BrandResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Clusters\Products\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
