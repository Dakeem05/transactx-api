<?php

namespace App\Models;

use App\Casts\TXAmountCast;
use App\Models\User\Wallet;
use App\Models\User\Wallet\WalletTransaction;
use App\Traits\UUID;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


class Transaction extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'wallet_transaction_id',
        'principal_transaction_id',
        'type',
        'description',
        'amount',
        'currency',
        'narration',
        'payload',
        'reference',
        'external_transaction_reference',
        'status',
        'user_ip',
    ];

    protected $casts = [
        'amount' => TXAmountCast::class,
        'payload' => 'json',
    ];

    protected $hidden = [
        'user_ip'
    ];


    /**
     * Transaction.wallet_transaction_id cannot be updated once it has been set.
     * This ensures a transaction record cannot be associated with more than one wallet transaction.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID when creating
        static::creating(function ($transaction) {
            if (empty($transaction->{$transaction->getKeyName()})) {
                $transaction->{$transaction->getKeyName()} = Str::uuid()->toString();
            }
        });
    
        static::updating(function ($transaction) {
            if ($transaction->isDirty('wallet_transaction_id')) {
                $originalWalletTransactionId = $transaction->getOriginal('wallet_transaction_id');
                if ($originalWalletTransactionId !== null) {
                    throw new Exception('Cannot update wallet_transaction_id once it has been set.');
                }
            }
        });
    }

    /**
     * Check if the transaction is a send money type
     *
     * @return boolean
     */
    public function isSendMoneyTransaction()
    {
        return $this->type == "SEND_MONEY";
    }

    /**
     * Check if the transaction is a request money type
     *
     * @return boolean
     */
    public function isRequestMoneyTransaction()
    {
        return $this->type == "REQUEST_MONEY";
    }

    /**
     * Check if the transaction is a fund wallet type
     *
     * @return boolean
     */
    public function isFundWalletTransaction()
    {
        return $this->type == "FUND_WALLET";
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the principal transaction this fee belongs to
     */
    public function principalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'principal_transaction_id');
    }

    /**
     * Get all fee transactions associated with this principal transaction
     */
    public function feeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'principal_transaction_id'); // Optional: scope to just fees
    }

    /**
     * Get all related transactions (both principal and fees)
     */
    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'principal_transaction_id');
    }

    /**
     * Scope to only include fee transactions
     */
    public function scopeFees($query)
    {
        return $query->where('type', 'SEND_MONEY_FEE');
    }

    /**
     * Scope to only include principal transactions
     */
    public function scopePrincipal($query)
    {
        return $query->whereNull('principal_transaction_id');
    }
}
