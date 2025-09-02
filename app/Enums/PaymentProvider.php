<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentProvider: string implements HasColor, HasIcon, HasLabel
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Square = 'square';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::PayPal => 'PayPal',
            self::Square => 'Square',
            self::Manual => 'Manual',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Stripe => 'primary',
            self::PayPal => 'warning',
            self::Square => 'success',
            self::Manual => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Stripe => 'heroicon-m-credit-card',
            self::PayPal => 'heroicon-m-globe-alt',
            self::Square => 'heroicon-m-squares-plus',
            self::Manual => 'heroicon-m-pencil-square',
        };
    }
}
