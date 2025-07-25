<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Squire\Models\Country;

class AddressForm extends Field
{
    protected string $view = 'filament-schemas::components.grid';

    /** @var string|callable|null */
    public $relationship = null;

    public function relationship(string | callable $relationship): static
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function saveRelationships(): void
    {
        $state = $this->getState();
        $record = $this->getRecord();
        $relationship = $record?->{$this->getRelationship()}();

        if ($relationship === null) {
            return;
        } elseif ($address = $relationship->first()) {
            $address->update($state);
        } else {
            $relationship->updateOrCreate($state);
        }

        $record?->touch();
    }

    public function getDefaultChildComponents(): array
    {
        return [
            Grid::make()
                ->schema([
                    Select::make('country')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $query) => Country::where('name', 'like', "%{$query}%")->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value): ?string => Country::firstWhere('id', $value)?->getAttribute('name')),
                ]),
            TextInput::make('street')
                ->label('Street address')
                ->maxLength(255),
            Grid::make(3)
                ->schema([
                    TextInput::make('city')
                        ->maxLength(255),
                    TextInput::make('state')
                        ->label('State / Province')
                        ->maxLength(255),
                    TextInput::make('zip')
                        ->label('Zip / Postal code')
                        ->maxLength(255),
                ]),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (AddressForm $component, ?Model $record) {
            $address = $record?->getRelationValue($this->getRelationship());

            $component->state($address ? $address->toArray() : [
                'country' => null,
                'street' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
            ]);
        });

        $this->dehydrated(false);
    }

    public function getRelationship(): string
    {
        return $this->evaluate($this->relationship) ?? $this->getName();
    }
}
