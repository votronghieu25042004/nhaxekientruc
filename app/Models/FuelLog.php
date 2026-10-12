<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'distance_km' => 'float',
        'liters_filled' => 'float',
        'fuel_rate_actual' => 'float',
        'fuel_rate_standard' => 'float',
        'discrepancy_liters' => 'float',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
