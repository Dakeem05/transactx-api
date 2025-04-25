<?php

namespace App\Enums\Subscription;

enum ModelNameEnum: string
{
    case HYDROGEN = 'HYDROGEN';
    case HELIUM = 'HELIUM';
    case LITHIUM = 'LITHIUM';

    public static function toArray(): array
    {
        return array_column(ModelNameEnum::cases(), 'value');
    }
}
