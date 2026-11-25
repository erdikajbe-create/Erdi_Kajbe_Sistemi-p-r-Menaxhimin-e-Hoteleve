<?php

declare(strict_types=1);

namespace HotelHub\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Kartë',
            self::BankTransfer => 'Transfertë bankare',
        };
    }
}
