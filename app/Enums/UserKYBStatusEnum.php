<?php

namespace App\Enums;

enum UserKYBStatusEnum: string
{
    case INPROGRESS = 'IN_PROGRESS';
    case SUCCESSFUL = 'SUCCESSFUL';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
    case BLOCKED = 'BLOCKED';

    public static function toArray(): array
    {
        return array_column(UserKYCStatusEnum::cases(), 'value');
    }
}
