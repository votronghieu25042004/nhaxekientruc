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
                if (!Schema::hasColumn('vouchers', 'target_audience')) {
                    $table->string('target_audience')->default('ALL')->after('description'); // ALL, INDIVIDUAL, STUDENT
                }
                if (!Schema::hasColumn('vouchers', 'max_uses_per_user')) {
                    $table->integer('max_uses_per_user')->default(2)->after('usage_limit');
                }
            });

            // Đồng bộ dữ liệu vouchers chuẩn hóa giữa Admin và Client
            $vouchers = [
                [
                    'code' => 'SINHVIEN0DONG',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 100,
                    'min_order_value' => 0,
                    'max_discount' => 500000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 1,
                    'target_audience' => 'STUDENT',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Vé 0đ Sinh Viên Lần Đầu: Miễn phí 100% (0 VNĐ) cho 1 ghế đầu tiên của sinh viên (.edu.vn)',
                ],
                [
                    'code' => 'SINHVIEN30',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 30,
                    'min_order_value' => 0,
                    'max_discount' => 150000,
                    'usage_limit' => 5000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 10,
                    'target_audience' => 'STUDENT',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Đặc Quyền Sinh Viên 1 Năm: Giảm 30% giá vé xe xuyên suốt cả năm cho sinh viên (.edu.vn)',
                ],
                [
                    'code' => 'SINHVIEN',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 30,
                    'min_order_value' => 0,
                    'max_discount' => 150000,
                    'usage_limit' => 5000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 10,
                    'target_audience' => 'STUDENT',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Gói Sinh Viên Kiến Trúc: Vé đầu MIỄN PHÍ 100% • Lần sau giảm 30% suốt năm',
                ],
                [
                    'code' => 'CHAOBAOMOI',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 10,
                    'min_order_value' => 0,
                    'max_discount' => 50000,
                    'usage_limit' => 2000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'INDIVIDUAL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Gói Ưu Đãi Thành Viên Mới: Giảm 10% cho khách hàng cá nhân đặt vé lần đầu',
                ],
                [
                    'code' => 'CHAOBANMOI',
                    'discount_type' => 'FIXED',
                    'discount_value' => 50000,
                    'min_order_value' => 150000,
                    'max_discount' => 50000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'INDIVIDUAL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Giảm ngay 50.000 VNĐ cho hành khách cá nhân đăng ký tài khoản mới',
                ],
                [
                    'code' => 'KIENTRUC20',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 20,
                    'min_order_value' => 100000,
                    'max_discount' => 50000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'INDIVIDUAL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Tri ân khách hàng cá nhân: Giảm 20% đặt vé nhân dịp tri ân',
                ],
                [
                    'code' => 'KIENTRUC30',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 30,
                    'min_order_value' => 150000,
                    'max_discount' => 100000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'ALL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Giảm 30% cho khách đặt vé sớm tuyến Đà Nẵng - Huế - Đồng Hới',
                ],
                [
                    'code' => 'VIPKIMCUONG',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 25,
                    'min_order_value' => 0,
                    'max_discount' => 150000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 5,
                    'target_audience' => 'INDIVIDUAL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Ưu đãi đặc quyền cho hội viên VIP Kim Cương',
                ],
                [
                    'code' => 'TETSUMVAY',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 15,
                    'min_order_value' => 200000,
                    'max_discount' => 80000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'ALL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Mã khuyến mãi mùa cao điểm lễ tết sum vầy gia đình',
                ],
                [
                    'code' => 'DA_NANG_FREE',
                    'discount_type' => 'FIXED',
                    'discount_value' => 30000,
                    'min_order_value' => 100000,
                    'max_discount' => 30000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'ALL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Trợ giá 30.000 VNĐ cho các chuyến xuất bến từ Đà Nẵng',
                ],
                [
                    'code' => 'MIENTRUNG15',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 15,
                    'min_order_value' => 120000,
                    'max_discount' => 60000,
                    'usage_limit' => 1000,
                    'usage_count' => 0,
                    'max_uses_per_user' => 2,
                    'target_audience' => 'ALL',
                    'is_active' => true,
                    'valid_until' => '2026-12-31',
                    'description' => 'Giảm 15% cho hành trình kết nối các tỉnh Miền Trung',
                ],
            ];

            foreach ($vouchers as $item) {
                $exists = DB::table('vouchers')->where('code', $item['code'])->first();
                if ($exists) {
                    DB::table('vouchers')->where('code', $item['code'])->update([
                        'discount_type' => $item['discount_type'],
                        'discount_value' => $item['discount_value'],
                        'min_order_value' => $item['min_order_value'],
                        'max_discount' => $item['max_discount'],
                        'usage_limit' => $item['usage_limit'],
                        'max_uses_per_user' => $item['max_uses_per_user'],
                        'target_audience' => $item['target_audience'],
                        'valid_until' => $item['valid_until'],
                        'is_active' => $item['is_active'],
                        'description' => $item['description'],
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('vouchers')->insert(array_merge($item, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (Schema::hasColumn('vouchers', 'target_audience')) {
                    $table->dropColumn('target_audience');
                }
                if (Schema::hasColumn('vouchers', 'max_uses_per_user')) {
                    $table->dropColumn('max_uses_per_user');
                }
            });
        }
    }
};
