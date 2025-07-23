<?php

namespace App\Filament\Resources\Blog\LinkResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Blog\LinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListLinks extends ListRecords
{
    use Translatable;

    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            LocaleSwitcher::make(),
        ];
    }
}
