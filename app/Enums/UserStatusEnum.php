<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case NEW = 'new';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
    case SUSPENDED = 'suspended';

    public static function toArray(): array
    {
        return array_column(UserStatusEnum::cases(), 'value');
    }
}
