<?php

namespace App\Models;

use App\Enums\Subscription\ModelBillingEnum;
use App\Enums\Subscription\ModelPaymentMethodEnum;
use App\Enums\Subscription\ModelUserStatusEnum;
use App\Models\Business\SubscriptionModel;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, UUID, SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'subscription_model_id',
        'next_subscription_model_id',
        'payment_gateway_id',
        'payment_intent',
        'method',
        'billing',
        'start_date',
        'end_date',
        'renewal_date',
        'cancelled_at',
        'status',
        'is_auto_renew',
        'metadata',
    ];

    protected $casts = [
        'method' => ModelPaymentMethodEnum::class,
        'billing' => ModelBillingEnum::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'renewal_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_auto_renew' => 'boolean',
        'status' => ModelUserStatusEnum::class,
        'metadata' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'payment_gateway_id',
        'payment_intent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(SubscriptionModel::class, 'subscription_model_id');
    }

    public function nextSubscriptionModel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionModel::class, 'next_subscription_model_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
