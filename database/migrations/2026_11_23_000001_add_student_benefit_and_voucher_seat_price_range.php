<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm các trường đặc quyền sinh viên & gói hội viên vào bảng users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_student')) {
                    $table->boolean('is_student')->default(false)->after('role');
                }
                if (!Schema::hasColumn('users', 'student_registered_at')) {
                    $table->timestamp('student_registered_at')->nullable()->after('is_student');
                }
                if (!Schema::hasColumn('users', 'student_discount_expires_at')) {
                    $table->timestamp('student_discount_expires_at')->nullable()->after('student_registered_at');
                }
                if (!Schema::hasColumn('users', 'student_first_trip_used')) {
                    $table->boolean('student_first_trip_used')->default(false)->after('student_discount_expires_at');
                }
                if (!Schema::hasColumn('users', 'membership_plan')) {
                    $table->string('membership_plan')->default('STANDARD')->after('student_first_trip_used');
                }
                if (!Schema::hasColumn('users', 'membership_discount_percent')) {
                    $table->integer('membership_discount_percent')->default(0)->after('membership_plan');
                }
                if (!Schema::hasColumn('users', 'membership_expires_at')) {
                    $table->timestamp('membership_expires_at')->nullable()->after('membership_discount_percent');
                }
            });

            // Tự động khởi tạo gói sinh viên 1 năm giảm 30% cho các tài khoản sinh viên đã có
            $now = now();
            $oneYearLater = now()->addYear();
            DB::statement("
                UPDATE users 
                SET is_student = 1,
                    student_registered_at = COALESCE(created_at, '{$now}'),
                    student_discount_expires_at = COALESCE(student_discount_expires_at, '{$oneYearLater}'),
                    membership_plan = 'STUDENT_DEFAULT',
                    membership_discount_percent = 30,
                    membership_expires_at = COALESCE(membership_expires_at, '{$oneYearLater}')
                WHERE email LIKE '%.edu.vn%' OR email LIKE '%sinhvien%'
            ");
        }

        // 2. Thêm trường giới hạn khoảng giá vé/ghế vào bảng vouchers (Yêu Cầu 3)
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'min_seat_price')) {
                    $table->decimal('min_seat_price', 12, 0)->default(0)->after('min_order_value');
                }
                if (!Schema::hasColumn('vouchers', 'max_seat_price')) {
                    $table->decimal('max_seat_price', 12, 0)->nullable()->after('min_seat_price');
                }
            });

            // Khởi tạo một số voucher mẫu có giới hạn giá ghế theo yêu cầu (từ 0đ đến 200k / 300k)
            DB::table('vouchers')->updateOrInsert(
                ['code' => 'GHE200K'],
                [
                    'discount_type' => 'FIXED',
                    'discount_value' => 30000,
                    'min_order_value' => 0,
                    'min_seat_price' => 0,
                    'max_seat_price' => 200000,
                    'description' => 'Giảm 30.000đ áp dụng riêng cho các ghế có giá từ 0đ đến 200.000đ',
                    'target_audience' => 'ALL',
                    'usage_count' => 100,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('vouchers')->updateOrInsert(
                ['code' => 'GHE300K'],
                [
                    'discount_type' => 'PERCENT',
                    'discount_value' => 15,
                    'min_order_value' => 0,
                    'min_seat_price' => 0,
                    'max_seat_price' => 300000,
                    'description' => 'Giảm 15% áp dụng riêng cho các ghế có giá từ 0đ đến 300.000đ',
                    'target_audience' => 'ALL',
                    'usage_count' => 100,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Tạo bảng membership_subscriptions để lưu lịch sử nâng cấp gói Ultra/Pro
        if (!Schema::hasTable('membership_subscriptions')) {
            Schema::create('membership_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('plan_code'); // STUDENT_ECO, STUDENT_PRO, STUDENT_ULTRA, STANDARD_ECO, STANDARD_PRO, STANDARD_ULTRA
                $table->string('plan_name');
                $table->integer('discount_percent'); // 20, 25, 40
                $table->decimal('price', 12, 0);
                $table->integer('duration_months')->default(12);
                $table->string('payment_method')->default('QR_TRANSFER');
                $table->string('status')->default('ACTIVE'); // ACTIVE, EXPIRED, CANCELLED
                $table->timestamp('starts_at');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('membership_subscriptions')) {
            Schema::dropIfExists('membership_subscriptions');
        }

        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (Schema::hasColumn('vouchers', 'min_seat_price')) {
                    $table->dropColumn('min_seat_price');
                }
                if (Schema::hasColumn('vouchers', 'max_seat_price')) {
                    $table->dropColumn('max_seat_price');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $cols = [
                    'is_student',
                    'student_registered_at',
                    'student_discount_expires_at',
                    'student_first_trip_used',
                    'membership_plan',
                    'membership_discount_percent',
                    'membership_expires_at'
                ];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('users', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
