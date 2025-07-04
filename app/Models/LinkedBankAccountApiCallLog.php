<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkedBankAccountApiCallLog extends Model
{
    protected $fillable = [
        'user_id',
        'linked_bank_account_id',
        'type',
        'provider',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function linkedBankAccount()
    {
        return $this->belongsTo(LinkedBankAccount::class);
    }
}
