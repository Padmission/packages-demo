<?php

namespace App\Filament\App\Resources\Blog\LinkResource\Pages;

use App\Filament\App\Resources\Blog\LinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
