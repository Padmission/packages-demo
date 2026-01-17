<?php

namespace App\Filament\App\Resources\Blog\LinkResource\Pages;

use App\Filament\App\Resources\Blog\LinkResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLink extends EditRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
