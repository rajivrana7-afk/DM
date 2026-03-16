<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_owner_id', 'name', 'description', 'address',
        'city', 'state', 'pincode', 'phone', 'email',
        'latitude', 'longitude', 'status', 'is_available', 'images',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'images'       => 'array',
        'latitude'     => 'float',
        'longitude'    => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(StoreOwner::class, 'store_owner_id');
    }

    public function timings()
    {
        return $this->hasMany(StoreTiming::class)->orderBy('day_of_week');
    }

    public function pricing()
    {
        return $this->hasMany(StorePricing::class)->where('is_active', true);
    }

    public function bookingSlots()
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope: only stores approved and available.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')->where('is_available', true);
    }

    /**
     * Calculate distance (km) from given coordinates using Haversine formula.
     */
    public function distanceFrom(float $lat, float $lon): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($this->latitude - $lat);
        $dLon = deg2rad($this->longitude - $lon);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
