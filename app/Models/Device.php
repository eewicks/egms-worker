<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'household_id',
        'household_name',
        'contact_number',
        'last_seen',
        'status'
    ];

    protected $casts = [
        'last_seen' => 'datetime'
    ];
}
