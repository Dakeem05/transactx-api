<?php

namespace App\Enums;

enum PartnersEnum: string
{
    case PAYSTACK = 'paystack';

    public static function toArray(): array
    {
        return array_column(PartnersEnum::cases(), 'value');
    }
}
