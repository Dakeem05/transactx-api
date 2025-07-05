<?php

namespace App\Enums\Subscription;

enum ModelPaymentMethodEnum: string
{
    case WALLET = 'WALLET';
    case CARD = 'CARD';

    public static function toArray(): array
    {
        return array_column(ModelPaymentMethodEnum::cases(), 'value');
    }
}
