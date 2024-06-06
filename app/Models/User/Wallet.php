<?php

namespace App\Models\User;

use App\Casts\TXAmountCast;
use App\Models\User;
use App\Models\VirtualBankAccount;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory, UUID;

    protected $fillable = ['user_id', 'currency', 'amount', 'is_active'];

    protected $casts = [
        'amount' => TXAmountCast::class,
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function VirtualBankAccount()
    {
        return $this->hasOne(VirtualBankAccount::class);
    }
}
