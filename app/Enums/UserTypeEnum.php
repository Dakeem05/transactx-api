<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case individual = 'individual';
    case organization = 'organization';
    
    public static function toArray(): array
    {
        return array_column(UserTypeEnum::cases(), 'value');
    }
}
