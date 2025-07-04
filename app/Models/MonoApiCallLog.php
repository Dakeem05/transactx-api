<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonoApiCallLog extends Model
{
    protected $fillable = [
        'user_id',
        'linked_bank_account_id',
        'type',
        'has_new_data',
        'job_status',
        'job_id',
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
