<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case NEW = 'NEW';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case BLOCKED = 'BLOCKED';
    case SUSPENDED = 'SUSPENDED';

    public static function toArray(): array
    {
        return array_column(UserStatusEnum::cases(), 'value');
    }
}
