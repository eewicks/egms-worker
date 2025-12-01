<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outage extends Model
{
    protected $fillable = [
        'device_id',
        'household_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'week_number',
        'iso_year',
        'status'
    ];

    protected $dates = ['started_at', 'ended_at'];
}
