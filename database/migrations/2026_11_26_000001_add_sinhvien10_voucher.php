<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Voucher;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Voucher::updateOrCreate(
            ['code' => 'SINHVIEN10'],
            [
                'discount_type' => 'PERCENT',
                'discount_value' => 10,
                'min_order_value' => 0,
                'max_discount' => 50000,
                'usage_limit' => 5000,
                'usage_count' => 0,
                'max_uses_per_user' => 20,
                'target_audience' => 'STUDENT',
                'is_active' => true,
                'valid_until' => '2026-12-31',
                'description' => 'Đặc Quyền Sinh Viên: Giảm 10% giá vé xe cho sinh viên (.edu.vn)',
            ]
        );

        if (Schema::hasColumn('vouchers', 'status')) {
            Voucher::where('code', 'SINHVIEN10')->update(['status' => 'ACTIVE']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Voucher::where('code', 'SINHVIEN10')->delete();
    }
};
