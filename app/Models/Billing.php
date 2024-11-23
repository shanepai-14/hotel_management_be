<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $fillable = [
        'booking_id',
        'invoice_number',
        'room_charges',
        'tax_amount',
        'total_amount',
        'payment_status',
        'payment_method'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
