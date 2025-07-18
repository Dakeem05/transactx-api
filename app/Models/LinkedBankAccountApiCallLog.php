<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedBankAccountApiCallLog extends Model
{
    use HasFactory, UUID;
    
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
