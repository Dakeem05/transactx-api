<?php

namespace App\Enums;

enum UserAccountTypeEnum: string
{
    case main = 'main';
    case sub = 'sub';
    
    public static function toArray(): array
    {
        return array_column(UserAccountTypeEnum::cases(), 'value');
    }
}
