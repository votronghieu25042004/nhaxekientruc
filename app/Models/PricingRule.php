<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'days_of_week' => 'array',
        'multiplier_percent' => 'float',
    ];
}
