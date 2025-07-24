<?php

namespace App\Filament\App\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('photo'),
                TextInput::make('gender')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                DatePicker::make('birthday'),
                Select::make('team_id')
                    ->relationship('team', 'name'),
            ]);
    }
}
