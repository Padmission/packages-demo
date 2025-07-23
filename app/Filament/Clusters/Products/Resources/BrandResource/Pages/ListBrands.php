<?php

namespace App\Filament\Clusters\Products\Resources\BrandResource\Pages;

use Filament\Actions\ExportAction;
use Filament\Actions\CreateAction;
use App\Filament\Clusters\Products\Resources\BrandResource;
use App\Filament\Exports\Shop\BrandExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    protected function getActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(BrandExporter::class),
            CreateAction::make(),
        ];
    }
}
