<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_number', 'user_id', 'store_id', 'booking_slot_id',
        'service_type', 'pickup_address', 'estimated_price', 'status', 'notes',
    ];

    protected $casts = ['estimated_price' => 'float'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function slot()
    {
        return $this->belongsTo(BookingSlot::class, 'booking_slot_id');
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public static function generateBookingNumber(): string
    {
        return 'DD' . strtoupper(substr(uniqid(), -8));
    }
}
