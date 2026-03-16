<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'address',
        'otp', 'otp_expires_at', 'is_verified', 'is_active',
    ];

    protected $hidden = ['otp', 'otp_expires_at'];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active'   => 'boolean',
        'otp_expires_at' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}
