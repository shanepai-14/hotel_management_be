<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'type',
        'capacity',
        'price_per_night',
        'status',
        'description',
        'amenities'
    ];

    protected $casts = [
        'amenities' => 'array',
        'price_per_night' => 'decimal:2'
    ];
}

