<?php

namespace App\Enums\Subscription;

enum ModelUserStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING = 'PENDING';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';

    public static function toArray(): array
    {
        return array_column(ModelUserStatusEnum::cases(), 'value');
    }
}
