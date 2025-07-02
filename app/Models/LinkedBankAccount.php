<?php

namespace App\Models;

use App\Casts\TXAmountCast;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedBankAccount extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'user_id',
        'account_id',
        'customer',
        'reference',
        'account_number',
        'account_name',
        'bank_name',
        'type',
        'data_status',
        'auth_method',
        'bank_code',
        'provider',
        'currency',
        'country',
        'balance',
    ];
    
    protected $casts = [
        'balance' => TXAmountCast::class,
    ];

    protected $hidden = [
        'account_id',
        'customer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
