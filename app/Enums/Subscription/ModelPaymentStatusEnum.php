<?php

namespace App\Enums\Subscription;

enum ModelPaymentStatusEnum: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';

    public static function toArray(): array
    {
        return array_column(ModelPaymentStatusEnum::cases(), 'value');
    }
}
