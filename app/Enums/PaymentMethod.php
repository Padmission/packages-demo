<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case CreditCard = 'credit_card';
    case PayPal = 'paypal';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit Card',
            self::PayPal => 'PayPal',
            self::BankTransfer => 'Bank Transfer',
            self::Cash => 'Cash',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::CreditCard => 'primary',
            self::PayPal => 'warning',
            self::BankTransfer => 'info',
            self::Cash => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CreditCard => 'heroicon-m-credit-card',
            self::PayPal => 'heroicon-m-globe-alt',
            self::BankTransfer => 'heroicon-m-building-library',
            self::Cash => 'heroicon-m-banknotes',
        };
    }
}
