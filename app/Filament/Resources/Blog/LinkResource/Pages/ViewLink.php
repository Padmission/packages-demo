<?php

namespace App\Filament\Resources\Blog\LinkResource\Pages;

use App\Filament\Resources\Blog\LinkResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLink extends ViewRecord
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
