<?php

namespace App\Filament\App\Clusters\Products\Resources\CategoryResource\Pages;

use App\Filament\App\Clusters\Products\Resources\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
