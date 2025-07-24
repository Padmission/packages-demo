<?php

namespace App\Filament\App\Clusters\Products\Resources\CategoryResource\Pages;

use Filament\Actions\ImportAction;
use Filament\Actions\CreateAction;
use App\Filament\App\Clusters\Products\Resources\CategoryResource;
use App\Filament\Imports\Shop\CategoryImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getActions(): array
    {
        return [
            ImportAction::make()
                ->importer(CategoryImporter::class),
            CreateAction::make(),
        ];
    }
}
