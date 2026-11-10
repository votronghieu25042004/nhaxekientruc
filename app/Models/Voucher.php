<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_value' => 'float',
        'min_order_value' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'max_uses_per_user' => 'integer',
    ];

    public function getTypeAttribute()
    {
        return $this->attributes['discount_type'] ?? null;
    }

    public function getValueAttribute()
    {
        return $this->attributes['discount_value'] ?? null;
    }

    public function getMinOrderAmountAttribute()
    {
        return $this->attributes['min_order_value'] ?? null;
    }

    public function getExpiresAtAttribute()
    {
        return $this->attributes['valid_until'] ?? null;
    }

    protected static function booted()
    {
        static::saving(function ($voucher) {
            if ($voucher->usage_count !== null && empty($voucher->used_count)) {
                $voucher->used_count = $voucher->usage_count;
            } elseif ($voucher->used_count !== null && empty($voucher->usage_count)) {
                $voucher->usage_count = $voucher->used_count;
            }
        });
    }
}
