<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarmentCategory extends Model
{
    protected $fillable = ['name', 'icon', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function pricing()
    {
        return $this->hasMany(StorePricing::class);
    }
}
