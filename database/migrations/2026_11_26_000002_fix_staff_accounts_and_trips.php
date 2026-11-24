<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Driver;
use App\Models\Bus;
use App\Models\Trip;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $passHash = Hash::make('123456');

        // =========================================================================
        // 1. CHUẨN HÓA 10 TÀI KHOẢN LƠ XE (TÊN RIÊNG BIỆT & EMAIL loxe1..10@gmail.com)
        // =========================================================================
        $conductorsInfo = [
            1  => ['name' => 'Nguyễn Văn Phúc', 'email' => 'loxe1@gmail.com',  'phone' => '0905221101'],
            2  => ['name' => 'Lê Quốc Đạt',     'email' => 'loxe2@gmail.com',  'phone' => '0905221102'],
            3  => ['name' => 'Phạm Thành Long', 'email' => 'loxe3@gmail.com',  'phone' => '0905221103'],
            4  => ['name' => 'Vũ Anh Tuấn',     'email' => 'loxe4@gmail.com',  'phone' => '0905221104'],
            5  => ['name' => 'Đỗ Minh Trí',     'email' => 'loxe5@gmail.com',  'phone' => '0905221105'],
            6  => ['name' => 'Hoàng Hải Nam',   'email' => 'loxe6@gmail.com',  'phone' => '0905221106'],
            7  => ['name' => 'Bùi Gia Huy',     'email' => 'loxe7@gmail.com',  'phone' => '0905221107'],
            8  => ['name' => 'Ngô Tiến Dũng',   'email' => 'loxe8@gmail.com',  'phone' => '0905221108'],
            9  => ['name' => 'Dương Văn Kiên',  'email' => 'loxe9@gmail.com',  'phone' => '0905221109'],
            10 => ['name' => 'Trần Quốc Thanh', 'email' => 'loxe10@gmail.com', 'phone' => '0905221110'],
        ];

        // 10 Conductor IDs gốc trong hệ thống
        $canonicalConductorIds = [
            1 => 53, 2 => 55, 3 => 57, 4 => 58, 5 => 59,
            6 => 60, 7 => 61, 8 => 62, 9 => 63, 10 => 64,
        ];

        // Xóa các user phụ/clone không có chuyến để tránh trùng unique email
        $allTargetEmails = array_column($conductorsInfo, 'email');
        User::whereIn('email', $allTargetEmails)
            ->whereNotIn('id', array_values($canonicalConductorIds))
            ->delete();

        $conductorUserIds = [];
        foreach ($conductorsInfo as $idx => $cData) {
            $expectedId = $canonicalConductorIds[$idx] ?? null;
            $user = null;
            if ($expectedId) {
                $user = User::find($expectedId);
            }
            if (!$user) {
                $user = User::where('email', $cData['email'])
                    ->orWhere('email', "loxe{$idx}@bushub.vn")
                    ->orWhere('email', "loxe{$idx}@nhaxekientruc.vn")
                    ->first();
            }

            if ($user) {
                $user->update([
                    'name'     => $cData['name'],
                    'email'    => $cData['email'],
                    'phone'    => $cData['phone'],
                    'role'     => 'conductor',
                    'password' => $passHash,
                    'status'   => 'ACTIVE',
                ]);
            } else {
                $user = User::create([
                    'id'       => $expectedId,
                    'name'     => $cData['name'],
                    'email'    => $cData['email'],
                    'phone'    => $cData['phone'],
                    'role'     => 'conductor',
                    'password' => $passHash,
                    'status'   => 'ACTIVE',
                ]);
            }
            $conductorUserIds[$idx] = $user->id;
        }

        // Cập nhật các lơ xe còn lại 11..20 sang @gmail.com
        for ($k = 11; $k <= 20; $k++) {
            User::where('email', "loxe{$k}@bushub.vn")->update(['email' => "loxe{$k}@gmail.com", 'password' => $passHash]);
        }

        // =========================================================================
        // 2. CHUẨN HÓA 10 TÀI KHOẢN TÀI XẾ (TÊN RIÊNG BIỆT & EMAIL taixe1..10@gmail.com)
        // =========================================================================
        $driversInfo = [
            1  => ['name' => 'Trần Văn Kiên',  'email' => 'taixe1@gmail.com',  'phone' => '0905123401'],
            2  => ['name' => 'Nguyễn Văn Hùng', 'email' => 'taixe2@gmail.com',  'phone' => '0905123402'],
            3  => ['name' => 'Trần Đình Trọng', 'email' => 'taixe3@gmail.com',  'phone' => '0905123403'],
            4  => ['name' => 'Lê Hoàng Nam',    'email' => 'taixe4@gmail.com',  'phone' => '0905123404'],
            5  => ['name' => 'Phạm Quốc Bảo',   'email' => 'taixe5@gmail.com',  'phone' => '0905123405'],
            6  => ['name' => 'Hoàng Minh Tuấn', 'email' => 'taixe6@gmail.com',  'phone' => '0905123406'],
            7  => ['name' => 'Đặng Văn Lâm',   'email' => 'taixe7@gmail.com',  'phone' => '0905123407'],
            8  => ['name' => 'Bùi Tiến Dũng',  'email' => 'taixe8@gmail.com',  'phone' => '0905123408'],
            9  => ['name' => 'Hồ Tấn Tài',     'email' => 'taixe9@gmail.com',  'phone' => '0905123409'],
            10 => ['name' => 'Vũ Văn Thanh',   'email' => 'taixe10@gmail.com', 'phone' => '0905123410'],
        ];

        // 10 Driver User IDs gốc
        $canonicalDriverUserIds = [
            1 => 306, 2 => 78, 3 => 79, 4 => 80, 5 => 81,
            6 => 82,  7 => 307, 8 => 83, 9 => 84, 10 => 85,
        ];

        $allDriverEmails = array_column($driversInfo, 'email');
        User::whereIn('email', $allDriverEmails)
            ->whereNotIn('id', array_values($canonicalDriverUserIds))
            ->delete();

        foreach ($driversInfo as $idx => $dData) {
            $expectedUserId = $canonicalDriverUserIds[$idx] ?? null;
            $dUser = null;
            if ($expectedUserId) {
                $dUser = User::find($expectedUserId);
            }
            if (!$dUser) {
                $dUser = User::where('email', $dData['email'])
                    ->orWhere('email', "taixe{$idx}@bushub.vn")
                    ->orWhere('email', "taixe{$idx}@nhaxekientruc.vn")
                    ->first();
            }

            if ($dUser) {
                $dUser->update([
                    'name'      => $dData['name'],
                    'email'     => $dData['email'],
                    'phone'     => $dData['phone'],
                    'driver_id' => $idx,
                    'role'      => 'driver',
                    'password'  => $passHash,
                    'status'    => 'ACTIVE',
                ]);
            } else {
                $dUser = User::create([
                    'id'        => $expectedUserId,
                    'name'      => $dData['name'],
                    'email'     => $dData['email'],
                    'phone'     => $dData['phone'],
                    'driver_id' => $idx,
                    'role'      => 'driver',
                    'password'  => $passHash,
                    'status'    => 'ACTIVE',
                ]);
            }

            // Đồng bộ bảng drivers
            $driverRecord = Driver::find($idx);
            if ($driverRecord) {
                $driverRecord->update([
                    'name'    => $dData['name'],
                    'email'   => $dData['email'],
                    'phone'   => $dData['phone'],
                    'user_id' => $dUser->id,
                    'status'  => 'ACTIVE',
                ]);
            } else {
                Driver::create([
                    'id'               => $idx,
                    'name'             => $dData['name'],
                    'email'            => $dData['email'],
                    'phone'            => $dData['phone'],
                    'user_id'          => $dUser->id,
                    'license_number'   => 'GPLX-430000' . str_pad($idx, 2, '0', STR_PAD_LEFT),
                    'license_class'    => 'Hạng E (Xe khách trên 30 chỗ)',
                    'years_experience' => 5 + ($idx % 5),
                    'avg_rating'       => 4.9,
                    'total_trips'      => 80 + ($idx * 10),
                    'status'           => 'ACTIVE',
                ]);
            }
        }

        // =========================================================================
        // 3. SỬA CHUYẾN BỊ GÁN SAI XE & GÁN SAI TÀI XẾ/LƠ XE (QUẢNG TRỊ 11:00 & 18:30)
        // Chuyến Đà Nẵng ⇄ Quảng Trị (11:00 & 18:30) phải thuộc Xe 10, TX 10, Lơ xe 10
        // =========================================================================
        $c10Id = $conductorUserIds[10] ?? 64;

        // Cập nhật tất cả các chuyến 11:00 và 18:30 đi Quảng Trị từng bị nhầm sang Bus 1
        Trip::where('bus_id', 1)
            ->where(function ($q) {
                $q->whereTime('departure_time', '11:00:00')
                  ->orWhereTime('departure_time', '18:30:00');
            })
            ->update([
                'bus_id'       => 10,
                'driver_id'    => 10,
                'conductor_id' => $c10Id,
            ]);

        // =========================================================================
        // 4. ĐỒNG BỘ 10 XE CỐ ĐỊNH: MỖI XE 1 BÁC TÀI & 1 LƠ XE DUY NHẤT
        // Xe 1: Tài xế 1 & Lơ xe 1
        // Xe 2: Tài xế 2 & Lơ xe 2
        // ...
        // Xe 10: Tài xế 10 & Lơ xe 10
        // =========================================================================
        for ($busId = 1; $busId <= 10; $busId++) {
            $cId = $conductorUserIds[$busId] ?? $conductorUserIds[1];

            // Cập nhật tất cả các chuyến của xe này
            Trip::where('bus_id', $busId)->update([
                'driver_id'    => $busId,
                'conductor_id' => $cId,
            ]);
        }

        // Xóa cache lịch trình để hệ thống làm mới tức thì
        Cache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không hoàn tác
    }
};
