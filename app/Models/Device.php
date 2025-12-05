<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'household_id',
        'device_id',
        'barangay',
        'status',
        'last_seen'
    ];

    protected $casts = [
        'last_seen' => 'datetime'
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}
