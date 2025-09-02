<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case CreditCard = 'credit_card';
    case DigitalWallet = 'digital_wallet';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit Card',
            self::DigitalWallet => 'Digital Wallet',
            self::BankTransfer => 'Bank Transfer',
            self::Cash => 'Cash',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::CreditCard => 'primary',
            self::DigitalWallet => 'warning',
            self::BankTransfer => 'info',
            self::Cash => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CreditCard => 'heroicon-m-credit-card',
            self::DigitalWallet => 'heroicon-m-device-phone-mobile',
            self::BankTransfer => 'heroicon-m-building-library',
            self::Cash => 'heroicon-m-banknotes',
        };
    }
}
