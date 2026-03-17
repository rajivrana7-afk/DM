<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    protected $fillable = [
        'store_id', 'slot_date', 'slot_time',
        'max_bookings', 'current_bookings', 'is_available',
    ];

    protected $casts = [
        'slot_date'    => 'date',
        'is_available' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isFull(): bool
    {
        return $this->current_bookings >= $this->max_bookings;
    }
}
