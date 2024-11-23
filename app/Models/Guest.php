<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'country',
        'identification_type',
        'identification_number',
        'date_of_birth',
        'special_requests'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Helper method to get full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
