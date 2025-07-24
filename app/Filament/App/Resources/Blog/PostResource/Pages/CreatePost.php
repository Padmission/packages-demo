<?php

namespace App\Filament\App\Resources\Blog\PostResource\Pages;

use App\Filament\App\Resources\Blog\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
