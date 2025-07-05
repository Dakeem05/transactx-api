<?php

namespace App\Enums\Subscription;

enum ModelNameEnum: string
{
    case FREE = 'FREE';
    case STARTUP = 'STARTUP';
    case GROWTH = 'GROWTH';
    case ENTERPRISE = 'ENTERPRISE';

    public static function toArray(): array
    {
        return array_column(ModelNameEnum::cases(), 'value');
    }
}
