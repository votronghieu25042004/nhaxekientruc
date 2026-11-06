<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'voucher_code')) {
                    $table->string('voucher_code')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('bookings', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 0)->default(0)->after('voucher_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'discount_amount')) {
                    $table->dropColumn('discount_amount');
                }
                if (Schema::hasColumn('bookings', 'voucher_code')) {
                    $table->dropColumn('voucher_code');
                }
            });
        }
    }
};
