<?php

namespace App\Enums;

enum UserKYCStatusEnum: string
{
    case INPROGRESS = 'IN_PROGRESS';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
    case BLOCKED = 'BLOCKED';

    public static function toArray(): array
    {
        return array_column(UserKYCStatusEnum::cases(), 'value');
    }
}
