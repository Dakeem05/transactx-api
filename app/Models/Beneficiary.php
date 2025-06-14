<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory, UUID;
    
    protected $fillable = [
        'user_id',
        'service',
        'payload',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'payload' => 'json',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'user_id',
        'service',
    ];
}
