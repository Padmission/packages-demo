<?php

namespace App\Filament\App\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\App\Clusters\Products\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
