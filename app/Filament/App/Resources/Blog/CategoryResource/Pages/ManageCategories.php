<?php

namespace App\Filament\App\Resources\Blog\CategoryResource\Pages;

use App\Filament\App\Resources\Blog\CategoryResource;
use App\Filament\Imports\Blog\CategoryImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
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
