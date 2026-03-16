<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id', 'store_pricing_id', 'garment_name',
        'quantity', 'unit_price', 'total_price',
    ];

    protected $casts = [
        'unit_price'  => 'float',
        'total_price' => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function pricing()
    {
        return $this->belongsTo(StorePricing::class, 'store_pricing_id');
    }
}
