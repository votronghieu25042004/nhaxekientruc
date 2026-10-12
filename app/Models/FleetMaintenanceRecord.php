<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cost' => 'float',
        'odo_km' => 'integer',
        'next_service_km' => 'integer',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
