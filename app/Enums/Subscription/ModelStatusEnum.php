<?php

namespace App\Enums\Subscription;

enum ModelStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    public static function toArray(): array
    {
        return array_column(ModelStatusEnum::cases(), 'value');
    }
}
