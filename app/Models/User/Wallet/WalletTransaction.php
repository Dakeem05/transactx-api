<?php

namespace App\Models\User\Wallet;

use App\Casts\TXAmountCast;
use App\Models\User\Wallet;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'wallet_id',
        'currency',
        'type',
        'previous_balance',
        'new_balance',
        'amount_change',
        'external_transaction_reference'
    ];


    protected $casts = [
        'previous_balance' => TXAmountCast::class,
        'new_balance'  => TXAmountCast::class,
        'amount_change' => TXAmountCast::class,
    ];


    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
