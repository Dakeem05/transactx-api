<?php

namespace App\Models;

use App\Casts\TXAmountCast;
use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelPaymentStatusEnum;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPayment extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'payment_reference',
        'external_reference',
        'method',
        'amount',
        'currency',
        'gateway_response',
        'status',
        'metadata'
    ];

    protected $casts = [
        'method' => ModelPaymentMethodEnum::class,
        'amount' => TXAmountCast::class,
        'metadata' => 'array',
        'status' => ModelPaymentStatusEnum::class,
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // Helper method to mark payment as successful
    public function markAsSuccessful(): void
    {
        $this->update([
            'status' => ModelPaymentStatusEnum::SUCCESSFUL,
        ]);
    }
}
