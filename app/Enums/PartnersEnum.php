<?php

namespace App\Enums;

enum PartnersEnum: string
{
    case PAYSTACK = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
    case SAFEHAVEN = 'safehaven';
    case MONO = 'mono';

    public static function toArray(): array
    {
        return array_column(PartnersEnum::cases(), 'value');
    }
}
