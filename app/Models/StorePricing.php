<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePricing extends Model
{
    protected $fillable = [
        'store_id', 'garment_category_id', 'garment_name', 'price', 'unit', 'is_active',
    ];

    protected $casts = [
        'price'     => 'float',
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(GarmentCategory::class, 'garment_category_id');
    }
}
