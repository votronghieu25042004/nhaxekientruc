<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Driver;
use App\Models\Voucher;
use App\Models\Trip;
use App\Models\Booking;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================================
        // 1. TẮT TẤT CẢ ƯU ĐÃI NGOẠI TRỪ: SINHVIEN0DONG, SINHVIEN30, CHAOBAOMOI, TETSUMVAY
        // =========================================================================
        $activeCodes = ['SINHVIEN0DONG', 'SINHVIEN30', 'CHAOBAOMOI', 'TETSUMVAY'];

        Voucher::whereIn('code', $activeCodes)->update([
            'is_active' => true,
        ]);

        Voucher::whereNotIn('code', $activeCodes)->update([
            'is_active' => false,
        ]);

        if (Schema::hasColumn('vouchers', 'status')) {
            Voucher::whereIn('code', $activeCodes)->update(['status' => 'ACTIVE']);
            Voucher::whereNotIn('code', $activeCodes)->update(['status' => 'INACTIVE']);
        }

        // =========================================================================
        // 2. CHUẨN HÓA TÊN KHÁCH HÀNG & GHI CHÚ HÀNH KHÁCH
        // =========================================================================
        // 2.1 Làm sạch tên khách hàng trong bảng users
        $studentUsers = User::where('name', 'like', 'Sinh viên %')->get();
        foreach ($studentUsers as $u) {
            $cleanName = preg_replace('/^Sinh\s*vi[êe]n\s+/iu', '', $u->name);
            if (str_contains($cleanName, '_')) {
                $part = explode('_', $cleanName)[0];
                $cleanName = ucfirst($part);
            }
            $cleanName = trim(preg_replace('/\s*\([^)]*\)/', '', $cleanName));
            if (empty($cleanName) || is_numeric($cleanName)) {
                $cleanName = 'Võ Trọng Hiếu';
            }
            $u->name = $cleanName;
            $u->save();
        }

        // Xử lý cụ thể tài khoản test sinh viên
        User::where('email', 'like', '%2251220108%')
            ->orWhere('email', 'like', '%hieu_2251220108%')
            ->update(['name' => 'Trọng Hiếu']);

        User::where('name', 'like', '%Nguyễn Văn Sinh Viên%')
            ->update(['name' => 'Nguyễn Văn Sinh']);

        // 2.2 Rút gọn ghi chú dài dòng thành "Sinh viên"
        Booking::where('notes', 'like', '%Sinh viên Đại học Kiến Trúc%')
            ->orWhere('notes', 'like', '%về quê đón Tết%')
            ->update(['notes' => 'Sinh viên']);

        // =========================================================================
        // 3. TẠO & CHUẨN HÓA TÀI KHOẢN LƠ XE (loxe1 .. loxe10, Mật khẩu: 1)
        // =========================================================================
        $passHash = Hash::make('1');
        $conductorUserIds = [];

        for ($i = 1; $i <= 10; $i++) {
            $email = "loxe{$i}@bushub.vn";
            $phone = sprintf("09052211%02d", $i);
            $name = "Nguyễn Văn Phúc {$i}";

            $conductorUser = User::where('email', $email)
                ->orWhere('email', "loxe{$i}@gmail.com")
                ->first();

            if ($conductorUser) {
                $conductorUser->update([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'conductor',
                    'password' => $passHash,
                    'status' => 'ACTIVE',
                ]);
            } else {
                $conductorUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'conductor',
                    'password' => $passHash,
                    'status' => 'ACTIVE',
                    'reward_points' => 0,
                    'email_verified_at' => now(),
                ]);
            }

            $conductorUserIds[$i] = $conductorUser->id;
        }

        // =========================================================================
        // 4. TẠO & CHUẨN HÓA TÀI KHOẢN TÀI XẾ (taixe1 .. taixe10, Mật khẩu: 1)
        // =========================================================================
        $driverUserIds = [];

        for ($i = 1; $i <= 10; $i++) {
            $email = "taixe{$i}@bushub.vn";
            $phone = sprintf("09051234%02d", $i);
            $name = "Trần Văn Kiên {$i}";

            $driverUser = User::where('email', $email)
                ->orWhere('email', "taixe{$i}@gmail.com")
                ->first();

            if ($driverUser) {
                $driverUser->update([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'driver',
                    'password' => $passHash,
                    'status' => 'ACTIVE',
                ]);
            } else {
                $driverUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'driver',
                    'password' => $passHash,
                    'status' => 'ACTIVE',
                    'reward_points' => 0,
                    'email_verified_at' => now(),
                ]);
            }

            $driverUserIds[$i] = $driverUser->id;

            // Đồng bộ hồ sơ tài xế trong bảng drivers
            $driverRecord = Driver::find($i);
            if ($driverRecord) {
                $driverRecord->update([
                    'name' => $name,
                    'phone' => $phone,
                    'user_id' => $driverUser->id,
                    'email' => $email,
                    'status' => 'ACTIVE',
                ]);
            } else {
                Driver::create([
                    'id' => $i,
                    'name' => $name,
                    'phone' => $phone,
                    'user_id' => $driverUser->id,
                    'email' => $email,
                    'license_number' => "GPLX-D" . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'license_class' => 'Hạng E (Xe khách trên 30 chỗ)',
                    'years_experience' => 5 + ($i % 8),
                    'avg_rating' => 4.9,
                    'total_trips' => 120 + ($i * 15),
                    'status' => 'ACTIVE',
                ]);
            }
        }

        // =========================================================================
        // 5. ĐỒNG BỘ LỊCH CHẠY CÁC CHUYẾN XE (KHÔNG TRÙNG GIỜ, KHÔNG TRÙNG NGƯỜI)
        // Mỗi xe i (1..10) được phụ trách bởi riêng Bác tài i và Lơ xe i
        // =========================================================================
        for ($busId = 1; $busId <= 10; $busId++) {
            $cId = $conductorUserIds[$busId] ?? $conductorUserIds[1];
            Trip::where('bus_id', $busId)->update([
                'driver_id' => $busId,
                'conductor_id' => $cId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
