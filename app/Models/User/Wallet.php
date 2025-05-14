<?php

namespace App\Models\User;

use App\Casts\TXAmountCast;
use App\Models\Transaction;
use App\Models\User;
use App\Models\User\Wallet\WalletTransaction;
use App\Models\VirtualBankAccount;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory, UUID;

    protected $fillable = ['user_id', 'currency', 'amount', 'ledger_balance',  'is_active'];

    protected $casts = [
        'amount' => TXAmountCast::class,
        'ledger_balance' => TXAmountCast::class,
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function virtualBankAccount()
    {
        return $this->hasOne(VirtualBankAccount::class);
    }


    public function Transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
