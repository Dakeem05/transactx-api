<?php

namespace App\Models\Business;

use App\Casts\TXAmountCast;
use App\Enums\Subscription\ModelNameEnum;
use App\Enums\Subscription\ModelStatusEnum;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionModel extends Model
{
    use HasFactory, UUID, SoftDeletes;


    protected $fillable = [
        'name',
        'serial',
        'features',
        'has_discount',
        'discount',
        'amount',
        'discount_amount',
        'full_amount',
        'status'
    ];

    protected $casts = [
        'name' => ModelNameEnum::class,
        'features' => 'array',
        'has_discount' => 'boolean',
        'amount' => TXAmountCast::class,
        'discount_amount' => TXAmountCast::class,
        'full_amount' => TXAmountCast::class,
        'status' => ModelStatusEnum::class
    ];


    protected function features(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => json_decode($value),
            set: fn (string $value) => json_encode($value),
        );
    }
}
