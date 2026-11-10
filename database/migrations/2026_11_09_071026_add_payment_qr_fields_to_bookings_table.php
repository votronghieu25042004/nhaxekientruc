<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // QR payment transfer reference
            if (!Schema::hasColumn('bookings', 'payment_ref_code')) {
                $table->string('payment_ref_code', 30)->nullable()->after('payment_method')
                    ->comment('Mã nội dung chuyển khoản duy nhất');
            }
            if (!Schema::hasColumn('bookings', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('payment_ref_code')
                    ->comment('Thời điểm xác nhận thanh toán thành công');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumnIfExists('payment_ref_code');
            $table->dropColumnIfExists('payment_confirmed_at');
        });
    }
};
