<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedFleetExpense extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
