<?php

namespace App\Enums;

enum RoleEnum: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';
    case SUB_USER = 'SUB_USER';

    public static function toArray(): array
    {
        return array_column(RoleEnum::cases(), 'value');
    }
}
