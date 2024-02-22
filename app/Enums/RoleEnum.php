<?php

namespace App\Enums;

enum RoleEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUB_USER = 'sub_user';

    public static function toArray(): array
    {
        return array_column(RoleEnum::cases(), 'value');
    }
}
