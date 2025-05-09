<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory, UUID;
    
    protected $fillable = [
        'id',
        'name',
        'value',
        'description',
        'created_at',
        'updated_at'
    ];
}
