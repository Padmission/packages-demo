<?php

namespace App\Filament\App\Resources\Blog\PostResource\Pages;

use App\Filament\App\Resources\Blog\PostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
