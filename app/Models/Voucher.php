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

    protected static function booted()
    {
        static::saving(function ($voucher) {
            // Đồng bộ giữa các trường modern và legacy
            if ($voucher->discount_type && !$voucher->type) {
                $voucher->type = $voucher->discount_type;
            } elseif ($voucher->type && !$voucher->discount_type) {
                $voucher->discount_type = $voucher->type;
            }

            if ($voucher->discount_value !== null && $voucher->value === null) {
                $voucher->value = $voucher->discount_value;
            } elseif ($voucher->value !== null && $voucher->discount_value === null) {
                $voucher->discount_value = $voucher->value;
            }

            if ($voucher->min_order_value !== null && $voucher->min_order_amount === null) {
                $voucher->min_order_amount = $voucher->min_order_value;
            } elseif ($voucher->min_order_amount !== null && $voucher->min_order_value === null) {
                $voucher->min_order_value = $voucher->min_order_amount;
            }

            if ($voucher->usage_count !== null && $voucher->used_count === null) {
                $voucher->used_count = $voucher->usage_count;
            } elseif ($voucher->used_count !== null && $voucher->usage_count === null) {
                $voucher->usage_count = $voucher->used_count;
            }

            if ($voucher->valid_until && !$voucher->expires_at) {
                $voucher->expires_at = $voucher->valid_until;
            } elseif ($voucher->expires_at && !$voucher->valid_until) {
                $voucher->valid_until = date('Y-m-d', strtotime($voucher->expires_at));
            }
        });
    }
}
