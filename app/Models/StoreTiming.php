<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTiming extends Model
{
    protected $fillable = ['store_id', 'day_of_week', 'open_time', 'close_time', 'is_closed'];

    protected $casts = ['is_closed' => 'boolean'];

    protected $appends = ['day_name'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getDayNameAttribute(): string
    {
        return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$this->day_of_week];
    }
}
