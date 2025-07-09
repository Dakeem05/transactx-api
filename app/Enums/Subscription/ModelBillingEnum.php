<?php

namespace App\Enums\Subscription;

enum ModelBillingEnum: string
{
    case ANNUAL = 'ANNUAL';
    case MONTHLY = 'MONTHLY';

    public static function toArray(): array
    {
        return array_column(ModelBillingEnum::cases(), 'value');
    }
}
