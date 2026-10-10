<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'hours_before' => 'float',
        'refund_percent' => 'float',
        'fee_percent' => 'float',
    ];
}
