<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'discount_type')) {
                    $table->string('discount_type')->default('PERCENT')->after('code');
                }
                if (!Schema::hasColumn('vouchers', 'discount_value')) {
                    $table->decimal('discount_value', 12, 0)->default(0)->after('discount_type');
                }
                if (!Schema::hasColumn('vouchers', 'min_order_value')) {
                    $table->decimal('min_order_value', 12, 0)->default(0)->after('discount_value');
                }
                if (!Schema::hasColumn('vouchers', 'max_discount')) {
                    $table->decimal('max_discount', 12, 0)->default(100000)->after('min_order_value');
                }
                if (!Schema::hasColumn('vouchers', 'usage_count')) {
                    $table->integer('usage_count')->default(0)->after('usage_limit');
                }
                if (!Schema::hasColumn('vouchers', 'valid_until')) {
                    $table->date('valid_until')->nullable()->after('usage_count');
                }
                if (!Schema::hasColumn('vouchers', 'description')) {
                    $table->string('description', 500)->nullable()->after('is_active');
                }
            });

            // Đồng bộ dữ liệu cũ nếu có
            if (Schema::hasColumn('vouchers', 'type')) {
                DB::statement("UPDATE vouchers SET discount_type = type WHERE discount_type IS NULL OR discount_type = ''");
            }
            if (Schema::hasColumn('vouchers', 'value')) {
                DB::statement("UPDATE vouchers SET discount_value = value WHERE discount_value = 0 AND value > 0");
            }
            if (Schema::hasColumn('vouchers', 'min_order_amount')) {
                DB::statement("UPDATE vouchers SET min_order_value = min_order_amount WHERE min_order_value = 0 AND min_order_amount > 0");
            }
            if (Schema::hasColumn('vouchers', 'used_count')) {
                DB::statement("UPDATE vouchers SET usage_count = used_count WHERE usage_count = 0 AND used_count > 0");
            }
            if (Schema::hasColumn('vouchers', 'expires_at')) {
                DB::statement("UPDATE vouchers SET valid_until = DATE(expires_at) WHERE valid_until IS NULL AND expires_at IS NOT NULL");
            }

            // Gán giá trị mặc định chuẩn mực cho các voucher ban đầu
            DB::table('vouchers')->where('code', 'BUSHUB30')->update([
                'code' => 'KIENTRUC30',
                'discount_type' => 'PERCENT',
                'discount_value' => 30,
                'min_order_value' => 150000,
                'max_discount' => 100000,
                'valid_until' => now()->addDays(90)->toDateString(),
                'description' => 'Giảm 30% cho khách đặt vé sớm tuyến Đà Nẵng - Huế - Đồng Hới',
            ]);

            DB::table('vouchers')->where('code', 'BUSHUB20')->update([
                'code' => 'KIENTRUC20',
                'discount_type' => 'PERCENT',
                'discount_value' => 20,
                'min_order_value' => 100000,
                'max_discount' => 50000,
                'valid_until' => now()->addDays(60)->toDateString(),
                'description' => 'Giảm 20% đặt vé nhân dịp tri ân khách hàng',
            ]);

            DB::table('vouchers')->where('code', 'CHAOBANMOI')->update([
                'discount_type' => 'FIXED',
                'discount_value' => 50000,
                'min_order_value' => 150000,
                'max_discount' => 50000,
                'valid_until' => now()->addDays(120)->toDateString(),
                'description' => 'Giảm ngay 50.000 VNĐ cho hành khách đăng ký tài khoản mới',
            ]);

            DB::table('vouchers')->where('code', 'TETSUMVAY')->update([
                'discount_type' => 'PERCENT',
                'discount_value' => 15,
                'min_order_value' => 200000,
                'max_discount' => 80000,
                'valid_until' => now()->addDays(90)->toDateString(),
                'description' => 'Mã khuyến mãi mùa cao điểm lễ tết sum vầy gia đình',
            ]);

            DB::table('vouchers')->where('code', 'VIPKIMCUONG')->update([
                'discount_type' => 'PERCENT',
                'discount_value' => 25,
                'min_order_value' => 0,
                'max_discount' => 150000,
                'valid_until' => now()->addYear()->toDateString(),
                'description' => 'Ưu đãi đặc quyền cho hội viên VIP Kim Cương',
            ]);

            DB::table('vouchers')->where('code', 'DA_NANG_FREE')->update([
                'discount_type' => 'FIXED',
                'discount_value' => 30000,
                'min_order_value' => 100000,
                'max_discount' => 30000,
                'valid_until' => now()->addDays(45)->toDateString(),
                'description' => 'Trợ giá 30.000 VNĐ cho các chuyến xuất bến từ Đà Nẵng',
            ]);

            DB::table('vouchers')->where('code', 'MIENTRUNG15')->update([
                'discount_type' => 'PERCENT',
                'discount_value' => 15,
                'min_order_value' => 120000,
                'max_discount' => 60000,
                'valid_until' => now()->addDays(60)->toDateString(),
                'description' => 'Giảm 15% cho hành trình kết nối các tỉnh Miền Trung',
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
