<?php

namespace App\Filament\App\Clusters\Products\Resources\CategoryResource\Pages;

use App\Filament\App\Clusters\Products\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
