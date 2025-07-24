<?php

namespace App\Filament\App\Clusters\Products\Resources\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Clusters\Products\Resources\CategoryResource;
use Filament\Actions;
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
