<?php

namespace App\Models;

use App\Models\User\Wallet;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualBankAccount extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'wallet_id',
        'currency',
        'account_number',
        'account_name',
        'bank_name',
        'bank_code',
        'provider',
        'country',
        'account_reference',
        'barter_id',
    ];
    
    protected $hidden = [
        'account_reference',
        'barter_id',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
