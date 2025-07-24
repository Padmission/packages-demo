<?php

namespace App\Filament\App\Resources\Blog\AuthorResource\Pages;

use App\Filament\App\Resources\Blog\AuthorResource;
use App\Filament\Exports\Blog\AuthorExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAuthors extends ManageRecords
{
    protected static string $resource = AuthorResource::class;

    protected function getActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(AuthorExporter::class),
            CreateAction::make(),
        ];
    }
}
