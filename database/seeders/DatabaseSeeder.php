<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Review;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. TÀI KHOẢN HỆ THỐNG
        // ============================================================
        $admin = User::updateOrCreate(['email' => 'admin@bushub.vn'], [
            'name'     => 'Tổng Quản Trị Bến Xe BusHub',
            'phone'    => '0901112233',
            'password' => Hash::make('123456'),
            'role'     => 'admin',
        ]);

        // ============================================================
        // 2. TẠO 20 TÀI KHOẢN LƠ XE (Conductor)
        // ============================================================
        $conductorsData = [
            ['name' => 'Nguyễn Văn Phúc', 'phone' => '0905221133', 'email' => 'loxe1@bushub.vn'],
            ['name' => 'Lê Quốc Đạt',     'phone' => '0905334455', 'email' => 'loxe2@bushub.vn'],
            ['name' => 'Phạm Thành Long',  'phone' => '0905447788', 'email' => 'loxe3@bushub.vn'],
            ['name' => 'Vũ Anh Tuấn',      'phone' => '0905558899', 'email' => 'loxe4@bushub.vn'],
            ['name' => 'Đỗ Minh Trí',      'phone' => '0905669900', 'email' => 'loxe5@bushub.vn'],
            ['name' => 'Hoàng Hải Nam',    'phone' => '0905771122', 'email' => 'loxe6@bushub.vn'],
            ['name' => 'Bùi Gia Huy',      'phone' => '0905882233', 'email' => 'loxe7@bushub.vn'],
            ['name' => 'Ngô Tiến Dũng',    'phone' => '0905993344', 'email' => 'loxe8@bushub.vn'],
            ['name' => 'Dương Văn Kiên',   'phone' => '0905004455', 'email' => 'loxe9@bushub.vn'],
            ['name' => 'Trần Quốc Thanh',  'phone' => '0905115566', 'email' => 'loxe10@bushub.vn'],
            ['name' => 'Nguyễn Hữu Hải',  'phone' => '0905226677', 'email' => 'loxe11@bushub.vn'],
            ['name' => 'Đinh Nhật Quang',  'phone' => '0905337788', 'email' => 'loxe12@bushub.vn'],
            ['name' => 'Hoàng Quốc Việt',  'phone' => '0905448899', 'email' => 'loxe13@bushub.vn'],
            ['name' => 'Phạm Đăng Khoa',   'phone' => '0905559900', 'email' => 'loxe14@bushub.vn'],
            ['name' => 'Trương Trung Hiếu', 'phone' => '0905660011', 'email' => 'loxe15@bushub.vn'],
            ['name' => 'Phan Thanh Bình',  'phone' => '0905771123', 'email' => 'loxe16@bushub.vn'],
            ['name' => 'Lê Đại Nghĩa',    'phone' => '0905882234', 'email' => 'loxe17@bushub.vn'],
            ['name' => 'Nguyễn Đức Thuận', 'phone' => '0905993345', 'email' => 'loxe18@bushub.vn'],
            ['name' => 'Mai Văn Phong',    'phone' => '0905004456', 'email' => 'loxe19@bushub.vn'],
            ['name' => 'Võ Tấn Hùng',     'phone' => '0905115567', 'email' => 'loxe20@bushub.vn'],
        ];

        $conductors = [];
        foreach ($conductorsData as $c) {
            $u = User::updateOrCreate(['email' => $c['email']], [
                'name'     => $c['name'],
                'phone'    => $c['phone'],
                'password' => Hash::make('123456'),
                'role'     => 'conductor',
            ]);
            $conductors[] = $u;
        }

        // ============================================================
        // 3. TẠO 10 TÀI KHOẢN TÀI XẾ (Driver users để login)
        // ============================================================
        $driverUsersData = [
            ['name' => 'Nguyễn Văn Hùng',  'phone' => '0905123456', 'email' => 'taixe1@bushub.vn'],
            ['name' => 'Trần Đình Trọng',  'phone' => '0905234567', 'email' => 'taixe2@bushub.vn'],
            ['name' => 'Lê Hoàng Nam',     'phone' => '0905345678', 'email' => 'taixe3@bushub.vn'],
            ['name' => 'Phạm Quốc Bảo',   'phone' => '0905456789', 'email' => 'taixe4@bushub.vn'],
            ['name' => 'Hoàng Minh Tuấn', 'phone' => '0905567890', 'email' => 'taixe5@bushub.vn'],
            ['name' => 'Đặng Văn Lâm',    'phone' => '0905678901', 'email' => 'taixe6@bushub.vn'],
            ['name' => 'Bùi Tiến Dũng',   'phone' => '0905789012', 'email' => 'taixe7@bushub.vn'],
            ['name' => 'Hồ Tấn Tài',      'phone' => '0905890123', 'email' => 'taixe8@bushub.vn'],
            ['name' => 'Vũ Văn Thanh',    'phone' => '0905901234', 'email' => 'taixe9@bushub.vn'],
            ['name' => 'Trần Văn Kiên',   'phone' => '0905012345', 'email' => 'taixe10@bushub.vn'],
        ];

        $driverUsers = [];
        foreach ($driverUsersData as $du) {
            $u = User::updateOrCreate(['email' => $du['email']], [
                'name'     => $du['name'],
                'phone'    => $du['phone'],
                'password' => Hash::make('123456'),
                'role'     => 'driver',
            ]);
            $driverUsers[] = $u;
        }

        // ============================================================
        // 4. TẠO 10 BUSES
        // ============================================================
        $busesData = [
            ['license_plate' => '43B-012.34', 'bus_type' => 'Thaco Mobihome VIP 34 Phòng',        'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-018.99', 'bus_type' => 'Hyundai Universe Luxury 36 Chỗ',     'total_seats' => 36, 'floors' => 2],
            ['license_plate' => '43B-023.45', 'bus_type' => 'Tracomeco Limousine 22 Phòng',        'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-034.56', 'bus_type' => 'Thaco King Long 36 Giường',           'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-045.67', 'bus_type' => 'Samco Universe VIP 34 Giường',        'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-056.78', 'bus_type' => 'Thaco Mobihome Premium 34 Giường',    'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-067.89', 'bus_type' => 'Hyundai Tracomeco 34 Giường VIP',     'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-078.90', 'bus_type' => 'Thaco Mobihome VIP 34 Phòng',        'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-089.01', 'bus_type' => 'Samco Wenda VIP 34 Giường',           'total_seats' => 34, 'floors' => 2],
            ['license_plate' => '43B-099.88', 'bus_type' => 'Universe Noble 34 Giường',            'total_seats' => 34, 'floors' => 2],
        ];

        $buses = [];
        foreach ($busesData as $b) {
            $buses[] = Bus::updateOrCreate(['license_plate' => $b['license_plate']], array_merge($b, ['status' => 'ACTIVE', 'current_km' => rand(80000, 200000)]));
        }

        // ============================================================
        // 5. TẠO 10 DRIVERS (linked to driver user accounts)
        // ============================================================
        $driversData = [
            ['name' => 'Nguyễn Văn Hùng',  'phone' => '0905123456', 'license_number' => 'GPLX-43000001', 'license_class' => 'E', 'years_experience' => 10, 'avg_rating' => 4.8, 'total_trips' => 120],
            ['name' => 'Trần Đình Trọng',  'phone' => '0905234567', 'license_number' => 'GPLX-43000002', 'license_class' => 'E', 'years_experience' => 8,  'avg_rating' => 4.7, 'total_trips' => 95],
            ['name' => 'Lê Hoàng Nam',     'phone' => '0905345678', 'license_number' => 'GPLX-43000003', 'license_class' => 'E', 'years_experience' => 12, 'avg_rating' => 4.9, 'total_trips' => 155],
            ['name' => 'Phạm Quốc Bảo',   'phone' => '0905456789', 'license_number' => 'GPLX-43000004', 'license_class' => 'E', 'years_experience' => 7,  'avg_rating' => 4.6, 'total_trips' => 78],
            ['name' => 'Hoàng Minh Tuấn', 'phone' => '0905567890', 'license_number' => 'GPLX-43000005', 'license_class' => 'E', 'years_experience' => 9,  'avg_rating' => 4.8, 'total_trips' => 110],
            ['name' => 'Đặng Văn Lâm',    'phone' => '0905678901', 'license_number' => 'GPLX-43000006', 'license_class' => 'E', 'years_experience' => 11, 'avg_rating' => 4.7, 'total_trips' => 130],
            ['name' => 'Bùi Tiến Dũng',   'phone' => '0905789012', 'license_number' => 'GPLX-43000007', 'license_class' => 'E', 'years_experience' => 14, 'avg_rating' => 4.9, 'total_trips' => 180],
            ['name' => 'Hồ Tấn Tài',      'phone' => '0905890123', 'license_number' => 'GPLX-43000008', 'license_class' => 'E', 'years_experience' => 6,  'avg_rating' => 4.5, 'total_trips' => 65],
            ['name' => 'Vũ Văn Thanh',    'phone' => '0905901234', 'license_number' => 'GPLX-43000009', 'license_class' => 'E', 'years_experience' => 10, 'avg_rating' => 4.7, 'total_trips' => 115],
            ['name' => 'Trần Văn Kiên',   'phone' => '0905012345', 'license_number' => 'GPLX-43000010', 'license_class' => 'E', 'years_experience' => 8,  'avg_rating' => 4.6, 'total_trips' => 90],
        ];

        $drivers = [];
        foreach ($driversData as $idx => $d) {
            $driverUser = $driverUsers[$idx] ?? null;
            $driver = Driver::updateOrCreate(['license_number' => $d['license_number']], array_merge($d, [
                'status'         => 'ACTIVE',
                'audit_status'   => 'APPROVED',
                'user_id'        => $driverUser?->id,
                'email'          => $driverUser?->email,
                'license_expiry' => Carbon::now()->addYears(rand(1, 5))->format('Y-m-d'),
            ]));
            // Link driver_id back to user
            if ($driverUser) {
                $driverUser->update(['driver_id' => $driver->id]);
            }
            $drivers[] = $driver;
        }

        // ============================================================
        // 6. TẠO TUYẾN ĐƯỜNG & ĐIỂM DỪNG (6 tuyến)
        // ============================================================
        // Tuyến 1: Đà Nẵng ↔ Huế (100km, 2.5h)
        $rHueGo = Route::updateOrCreate(['origin' => 'Đà Nẵng', 'destination' => 'Thừa Thiên Huế'], [
            'name' => 'Đà Nẵng → Thừa Thiên Huế', 'distance_km' => 100, 'estimated_hours' => 2.5,
        ]);
        $rHueBack = Route::updateOrCreate(['origin' => 'Thừa Thiên Huế', 'destination' => 'Đà Nẵng'], [
            'name' => 'Thừa Thiên Huế → Đà Nẵng', 'distance_km' => 100, 'estimated_hours' => 2.5,
        ]);

        // Tuyến 2: Đà Nẵng ↔ Quảng Bình (270km, 5.5h)
        $rQBGo = Route::updateOrCreate(['origin' => 'Đà Nẵng', 'destination' => 'Quảng Bình'], [
            'name' => 'Đà Nẵng → Quảng Bình (Đồng Hới)', 'distance_km' => 270, 'estimated_hours' => 5.5,
        ]);
        $rQBBack = Route::updateOrCreate(['origin' => 'Quảng Bình', 'destination' => 'Đà Nẵng'], [
            'name' => 'Quảng Bình (Đồng Hới) → Đà Nẵng', 'distance_km' => 270, 'estimated_hours' => 5.5,
        ]);

        // Tuyến 3: Đà Nẵng ↔ Hà Tĩnh (360km, 7h)
        $rHTGo = Route::updateOrCreate(['origin' => 'Đà Nẵng', 'destination' => 'Hà Tĩnh'], [
            'name' => 'Đà Nẵng → Hà Tĩnh (Thành phố Hà Tĩnh)', 'distance_km' => 360, 'estimated_hours' => 7.0,
        ]);
        $rHTBack = Route::updateOrCreate(['origin' => 'Hà Tĩnh', 'destination' => 'Đà Nẵng'], [
            'name' => 'Hà Tĩnh → Đà Nẵng', 'distance_km' => 360, 'estimated_hours' => 7.0,
        ]);

        // ============================================================
        // 7. ĐIỂM DỪNG CHO TỪNG TUYẾN
        // ============================================================
        $stopsHueGo = $this->ensureStops($rHueGo->id, [
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng', 'stop_order' => 1],
            ['stop_name' => 'Trạm Thu Phí Phú Bài',     'address' => 'Thị xã Hương Thủy, TT-Huế',       'stop_order' => 2],
            ['stop_name' => 'Bến xe Phía Nam Huế',       'address' => '97 An Dương Vương, TP. Huế',       'stop_order' => 3],
        ]);
        $stopsHueBack = $this->ensureStops($rHueBack->id, [
            ['stop_name' => 'Bến xe Phía Nam Huế',       'address' => '97 An Dương Vương, TP. Huế',       'stop_order' => 1],
            ['stop_name' => 'Trạm Thu Phí Phú Bài',     'address' => 'Thị xã Hương Thủy, TT-Huế',       'stop_order' => 2],
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng', 'stop_order' => 3],
        ]);

        $stopsQBGo = $this->ensureStops($rQBGo->id, [
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng',    'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng',  'stop_order' => 1],
            ['stop_name' => 'Trạm dừng Đèo Hải Vân',       'address' => 'QL1A, Thừa Thiên Huế',             'stop_order' => 2],
            ['stop_name' => 'Bến xe Đồng Hới',              'address' => 'Trần Hưng Đạo, TP. Đồng Hới',      'stop_order' => 3],
        ]);
        $stopsQBBack = $this->ensureStops($rQBBack->id, [
            ['stop_name' => 'Bến xe Đồng Hới',              'address' => 'Trần Hưng Đạo, TP. Đồng Hới',      'stop_order' => 1],
            ['stop_name' => 'Trạm dừng Đèo Hải Vân',       'address' => 'QL1A, Thừa Thiên Huế',             'stop_order' => 2],
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng',    'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng',  'stop_order' => 3],
        ]);

        $stopsHTGo = $this->ensureStops($rHTGo->id, [
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng',    'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng',  'stop_order' => 1],
            ['stop_name' => 'Trạm dừng Cầu Gianh',         'address' => 'Bố Trạch, Quảng Bình',             'stop_order' => 2],
            ['stop_name' => 'Bến xe Hà Tĩnh',              'address' => '1 Trần Phú, TP. Hà Tĩnh',          'stop_order' => 3],
        ]);
        $stopsHTBack = $this->ensureStops($rHTBack->id, [
            ['stop_name' => 'Bến xe Hà Tĩnh',              'address' => '1 Trần Phú, TP. Hà Tĩnh',          'stop_order' => 1],
            ['stop_name' => 'Trạm dừng Cầu Gianh',         'address' => 'Bố Trạch, Quảng Bình',             'stop_order' => 2],
            ['stop_name' => 'Bến xe Trung tâm Đà Nẵng',    'address' => '185 Tôn Đức Thắng, TP. Đà Nẵng',  'stop_order' => 3],
        ]);

        // ============================================================
        // 8. XÓA LỊCH CŨ VÀ TẠO LỊCH CỐ ĐỊNH 20 CHUYẾN / 3 NGÀY
        // ============================================================
        Schema::disableForeignKeyConstraints();
        BookingSeat::truncate();
        Booking::truncate();
        Trip::truncate();
        Review::truncate();
        Schema::enableForeignKeyConstraints();

        /**
         * LịCH CỐ ĐỊNH 10 XE - 20 CHUYẾN / NGÀY
         * Bus 1 (43B-012.34): Đà Nẵng ↔ Huế - 4 chuyến/ngày (6:30, 14:00, 20:30, 22:15)
         * Bus 2 (43B-018.99): Đà Nẵng ↔ Huế - 4 chuyến/ngày (7:00, 11:00, 16:00, 21:00)
         * Bus 3 (43B-023.45): Đà Nẵng ↔ Quảng Bình - 4 chuyến/ngày (6:00, 13:30, 20:45, 22:30)
         * Bus 4 (43B-034.56): Đà Nẵng ↔ Quảng Bình - 2 chuyến/ngày (8:00, 20:00)
         * Bus 5 (43B-045.67): Đà Nẵng → Hà Tĩnh - 2 chuyến/ngày (7:30, 21:00)
         * Bus 6 (43B-056.78): Hà Tĩnh → Đà Nẵng - 2 chuyến/ngày (7:30, 21:00)
         * Bus 7 (43B-067.89): Đà Nẵng ↔ Quảng Bình - 2 chuyến/ngày (09:00, 22:00)
         * Bus 8 (43B-078.90): Đà Nẵng → Hà Tĩnh - 2 chuyến/ngày (10:30, 22:30)
         * Bus 9 (43B-089.01): Hà Tĩnh → Đà Nẵng - 2 chuyến/ngày (10:30, 22:30)
         * Bus 10 (43B-099.88): Đà Nẵng ↔ Huế - 4 chuyến/ngày (8:00, 12:00, 17:00, 23:00)
         * TỔNG: 4+4+4+2+2+2+2+2+2+4 = 28 chuyến/ngày (tổng > 20, lọc 20 chuyến cố định)
         */
        $scheduleTemplates = [
            // === Bus 1: Đà Nẵng ↔ Huế (4 chuyến/ngày) ===
            [
                'bus' => $buses[0], 'driver' => $drivers[0], 'conductor' => $conductors[0],
                'trips' => [
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '06:30', 'dur_h' => 2.5, 'price' => 120000],
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '14:00', 'dur_h' => 2.5, 'price' => 120000],
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '20:30', 'dur_h' => 2.5, 'price' => 120000],
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '22:15', 'dur_h' => 2.5, 'price' => 120000],
                ],
            ],
            // === Bus 2: Đà Nẵng ↔ Huế (4 chuyến/ngày) ===
            [
                'bus' => $buses[1], 'driver' => $drivers[1], 'conductor' => $conductors[1],
                'trips' => [
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '07:00', 'dur_h' => 2.5, 'price' => 125000],
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '11:00', 'dur_h' => 2.5, 'price' => 125000],
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '16:00', 'dur_h' => 2.5, 'price' => 125000],
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '21:00', 'dur_h' => 2.5, 'price' => 125000],
                ],
            ],
            // === Bus 3: Đà Nẵng ↔ Quảng Bình (4 chuyến/ngày) ===
            [
                'bus' => $buses[2], 'driver' => $drivers[2], 'conductor' => $conductors[2],
                'trips' => [
                    ['route' => $rQBGo,   'stops' => $stopsQBGo,   'dep' => '06:00', 'dur_h' => 5.5, 'price' => 190000],
                    ['route' => $rQBBack, 'stops' => $stopsQBBack, 'dep' => '13:30', 'dur_h' => 5.5, 'price' => 190000],
                    ['route' => $rQBGo,   'stops' => $stopsQBGo,   'dep' => '20:45', 'dur_h' => 5.5, 'price' => 200000],
                    ['route' => $rQBBack, 'stops' => $stopsQBBack, 'dep' => '22:30', 'dur_h' => 5.5, 'price' => 200000],
                ],
            ],
            // === Bus 4: Đà Nẵng ↔ Quảng Bình (2 chuyến/ngày) ===
            [
                'bus' => $buses[3], 'driver' => $drivers[3], 'conductor' => $conductors[3],
                'trips' => [
                    ['route' => $rQBGo,   'stops' => $stopsQBGo,   'dep' => '08:00', 'dur_h' => 5.5, 'price' => 185000],
                    ['route' => $rQBBack, 'stops' => $stopsQBBack, 'dep' => '20:00', 'dur_h' => 5.5, 'price' => 185000],
                ],
            ],
            // === Bus 5: Đà Nẵng → Hà Tĩnh (2 chuyến/ngày) ===
            [
                'bus' => $buses[4], 'driver' => $drivers[4], 'conductor' => $conductors[4],
                'trips' => [
                    ['route' => $rHTGo, 'stops' => $stopsHTGo, 'dep' => '07:30', 'dur_h' => 7.0, 'price' => 280000],
                    ['route' => $rHTGo, 'stops' => $stopsHTGo, 'dep' => '21:00', 'dur_h' => 7.0, 'price' => 290000],
                ],
            ],
            // === Bus 6: Hà Tĩnh → Đà Nẵng (2 chuyến/ngày) ===
            [
                'bus' => $buses[5], 'driver' => $drivers[5], 'conductor' => $conductors[5],
                'trips' => [
                    ['route' => $rHTBack, 'stops' => $stopsHTBack, 'dep' => '07:30', 'dur_h' => 7.0, 'price' => 280000],
                    ['route' => $rHTBack, 'stops' => $stopsHTBack, 'dep' => '21:00', 'dur_h' => 7.0, 'price' => 290000],
                ],
            ],
            // === Bus 7: Đà Nẵng ↔ Quảng Bình (2 chuyến/ngày) ===
            [
                'bus' => $buses[6], 'driver' => $drivers[6], 'conductor' => $conductors[6],
                'trips' => [
                    ['route' => $rQBGo,   'stops' => $stopsQBGo,   'dep' => '09:00', 'dur_h' => 5.5, 'price' => 195000],
                    ['route' => $rQBBack, 'stops' => $stopsQBBack, 'dep' => '22:00', 'dur_h' => 5.5, 'price' => 195000],
                ],
            ],
            // === Bus 8: Đà Nẵng → Hà Tĩnh (2 chuyến/ngày) ===
            [
                'bus' => $buses[7], 'driver' => $drivers[7], 'conductor' => $conductors[7],
                'trips' => [
                    ['route' => $rHTGo, 'stops' => $stopsHTGo, 'dep' => '10:30', 'dur_h' => 7.0, 'price' => 275000],
                    ['route' => $rHTGo, 'stops' => $stopsHTGo, 'dep' => '22:30', 'dur_h' => 7.0, 'price' => 285000],
                ],
            ],
            // === Bus 9: Hà Tĩnh → Đà Nẵng (2 chuyến/ngày) ===
            [
                'bus' => $buses[8], 'driver' => $drivers[8], 'conductor' => $conductors[8],
                'trips' => [
                    ['route' => $rHTBack, 'stops' => $stopsHTBack, 'dep' => '10:30', 'dur_h' => 7.0, 'price' => 275000],
                    ['route' => $rHTBack, 'stops' => $stopsHTBack, 'dep' => '22:30', 'dur_h' => 7.0, 'price' => 285000],
                ],
            ],
            // === Bus 10: Đà Nẵng ↔ Huế (4 chuyến/ngày) ===
            [
                'bus' => $buses[9], 'driver' => $drivers[9], 'conductor' => $conductors[9],
                'trips' => [
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '08:00', 'dur_h' => 2.5, 'price' => 120000],
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '12:00', 'dur_h' => 2.5, 'price' => 120000],
                    ['route' => $rHueGo,   'stops' => $stopsHueGo,   'dep' => '17:00', 'dur_h' => 2.5, 'price' => 130000],
                    ['route' => $rHueBack, 'stops' => $stopsHueBack, 'dep' => '23:00', 'dur_h' => 2.5, 'price' => 130000],
                ],
            ],
        ];

        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $now   = Carbon::now('Asia/Ho_Chi_Minh');
        $tripCounter = 1;

        // Seed trips for yesterday (-1), today (0), and next 3 days
        for ($dayOffset = -1; $dayOffset <= 2; $dayOffset++) {
            $curDate = $today->copy()->addDays($dayOffset);

            foreach ($scheduleTemplates as $sched) {
                foreach ($sched['trips'] as $tInfo) {
                    [$depH, $depM] = explode(':', $tInfo['dep']);
                    $depTime = $curDate->copy()->setHour((int)$depH)->setMinute((int)$depM)->setSecond(0);

                    $durSeconds = (int)($tInfo['dur_h'] * 3600);
                    $arrTime = $depTime->copy()->addSeconds($durSeconds);

                    // Compute real-time status
                    if ($dayOffset < 0 || $now->gte($arrTime)) {
                        $status = 'COMPLETED';
                    } elseif ($now->gte($depTime) && $now->lt($arrTime)) {
                        $status = 'IN_TRANSIT';
                    } elseif ($now->diffInMinutes($depTime, false) <= 45 && $now->lt($depTime)) {
                        $status = 'BOARDING';
                    } elseif ($dayOffset == 0) {
                        $status = 'OPEN_FOR_SALE';
                    } else {
                        $status = 'SCHEDULED';
                    }

                    $tripCode = 'BH-' . strtoupper(Str::random(3)) . '-' . str_pad($tripCounter++, 4, '0', STR_PAD_LEFT);
                    $stops    = $tInfo['stops'];
                    $pickupStop  = $stops[0] ?? null;
                    $dropoffStop = $stops[count($stops) - 1] ?? null;

                    // GPS coordinates (approximate for Đà Nẵng area)
                    $lat = $status === 'IN_TRANSIT' ? (16.0544 + (rand(-500, 500) / 10000)) : 16.0544;
                    $lng = $status === 'IN_TRANSIT' ? (108.2022 + (rand(-500, 500) / 10000)) : 108.2022;

                    $trip = Trip::create([
                        'trip_code'        => $tripCode,
                        'route_id'         => $tInfo['route']->id,
                        'bus_id'           => $sched['bus']->id,
                        'driver_id'        => $sched['driver']->id,
                        'conductor_id'     => $sched['conductor']->id,
                        'departure_time'   => $depTime,
                        'arrival_time'     => $arrTime,
                        'base_price'       => $tInfo['price'],
                        'floor1_price'     => $tInfo['price'],
                        'floor2_price'     => $tInfo['price'] - 10000,
                        'status'           => $status,
                        'current_lat'      => $lat,
                        'current_lng'      => $lng,
                        'gps_updated_at'   => $status === 'IN_TRANSIT' ? now()->subMinutes(rand(1, 5)) : null,
                        'current_speed_kmh' => $status === 'IN_TRANSIT' ? rand(60, 90) : 0,
                        'departed_at'      => in_array($status, ['IN_TRANSIT', 'COMPLETED']) ? $depTime->copy()->addMinutes(rand(0, 5)) : null,
                        'completed_at'     => $status === 'COMPLETED' ? $arrTime->copy()->addMinutes(rand(0, 10)) : null,
                    ]);

                    // Seed passengers for the trip
                    if ($pickupStop && $dropoffStop) {
                        $numBookings = match(true) {
                            $dayOffset < 0              => rand(10, 25),
                            $status === 'IN_TRANSIT'    => rand(8, 20),
                            $status === 'BOARDING'      => rand(5, 15),
                            $dayOffset === 0            => rand(3, 10),
                            default                     => rand(2, 8),
                        };
                        $this->seedTripBookings($trip, $tInfo['price'], $pickupStop, $dropoffStop, $numBookings, $dayOffset);
                    }
                }
            }
        }

        // ============================================================
        // 9. SEED 5 TÀI KHOẢN KHÁCH HÀNG
        // ============================================================
        $customerAccounts = [
            ['name' => 'Võ Thị Thanh Trúc', 'phone' => '0905456654', 'email' => 'khachhang1@gmail.com'],
            ['name' => 'Lê Văn Khang',       'phone' => '0905567543', 'email' => 'khachhang2@gmail.com'],
            ['name' => 'Phan Thị Yến Nhi',   'phone' => '0905678432', 'email' => 'khachhang3@gmail.com'],
            ['name' => 'Nguyễn Minh Khoa',   'phone' => '0905789321', 'email' => 'khachhang4@gmail.com'],
            ['name' => 'Trần Thị Hoa',       'phone' => '0905890210', 'email' => 'khachhang5@gmail.com'],
        ];
        foreach ($customerAccounts as $ca) {
            User::updateOrCreate(['email' => $ca['email']], [
                'name'     => $ca['name'],
                'phone'    => $ca['phone'],
                'password' => Hash::make('123456'),
                'role'     => 'customer',
            ]);
        }

        // ============================================================
        // 10. SEED REVIEWS CHO TỪNG TÀI XẾ
        // ============================================================
        $this->seedDriverReviews($drivers);

        echo ">>> SEEDED SUCCESSFULLY: 10 buses, 20+ fixed trips/day for 3 days, 10 driver accounts, 20 conductor accounts!\n";
    }

    private function ensureStops(int $routeId, array $stopsData): array
    {
        $stops = [];
        foreach ($stopsData as $s) {
            $stop = RouteStop::firstOrCreate(
                ['route_id' => $routeId, 'stop_order' => $s['stop_order']],
                ['stop_name' => $s['stop_name'], 'address' => $s['address']]
            );
            $stops[] = $stop;
        }
        return $stops;
    }

    private function seedTripBookings($trip, int $price, $pickupStop, $dropoffStop, int $count, int $dayOffset): void
    {
        $customerNames = [
            ['name' => 'Nguyễn Thị Thu Hà',  'phone' => '0905123456'],
            ['name' => 'Trần Văn Hùng',       'phone' => '0905234567'],
            ['name' => 'Lê Hoàng Long',       'phone' => '0905345678'],
            ['name' => 'Phạm Thu Trang',      'phone' => '0905456789'],
            ['name' => 'Võ Quốc Hưng',        'phone' => '0918901234'],
            ['name' => 'Bùi Thanh Thảo',      'phone' => '0917890123'],
            ['name' => 'Hoàng Ngọc Ánh',      'phone' => '0919012345'],
            ['name' => 'Đặng Minh Quân',      'phone' => '0916789012'],
            ['name' => 'Lê Thị Mỹ Linh',     'phone' => '0905789999'],
            ['name' => 'Mai Thanh Tùng',      'phone' => '0905891234'],
            ['name' => 'Ngô Thị Lan',         'phone' => '0905902345'],
            ['name' => 'Phan Văn Đức',        'phone' => '0905013456'],
            ['name' => 'Trương Thị Hương',    'phone' => '0905124567'],
            ['name' => 'Đinh Quang Huy',      'phone' => '0905235678'],
            ['name' => 'Vũ Thị Ngọc',        'phone' => '0905346789'],
            ['name' => 'Hồ Văn Minh',        'phone' => '0905457890'],
            ['name' => 'Dương Thị Thanh',    'phone' => '0905568901'],
            ['name' => 'Tô Văn Bình',        'phone' => '0905679012'],
            ['name' => 'Cao Thị Mai',        'phone' => '0905780123'],
            ['name' => 'Lưu Văn Tâm',        'phone' => '0905891234'],
            ['name' => 'Khổng Thị Liên',     'phone' => '0905902345'],
            ['name' => 'Bùi Thị Kim Oanh',   'phone' => '0905013456'],
            ['name' => 'Nguyễn Văn Tùng',    'phone' => '0905124678'],
            ['name' => 'Chu Thị Hoa',        'phone' => '0905235789'],
            ['name' => 'Đoàn Văn Tài',       'phone' => '0905346890'],
        ];

        $usedSeats = [];
        for ($i = 0; $i < min($count, count($customerNames)); $i++) {
            $c = $customerNames[$i % count($customerNames)];

            // Pick a unique seat
            do {
                $floor = rand(1, 2);
                $num   = rand(1, 17);
                $seatNum = ($floor === 1 ? 'A' : 'B') . str_pad($num, 2, '0', STR_PAD_LEFT);
            } while (in_array($seatNum, $usedSeats));
            $usedSeats[] = $seatNum;

            $paymentStatuses = ['PAID', 'PAID', 'PAID', 'PARTIAL', 'UNPAID'];
            $payStatus = $dayOffset < 0 ? 'PAID' : $paymentStatuses[array_rand($paymentStatuses)];
            $paidAmt   = match($payStatus) {
                'PAID'    => $price,
                'PARTIAL' => (int)($price * 0.3),
                default   => 0,
            };
            $methods = ['VNPAY', 'MOMO', 'CASH', 'BANK_TRANSFER'];
            $method  = $methods[array_rand($methods)];

            $isFuture = Carbon::parse($trip->departure_time)->isFuture();
            if ($isFuture) {
                $checkinStatus = 'WAITING';
            } elseif ($dayOffset < 0 || $trip->status === 'COMPLETED' || $trip->status === 'IN_TRANSIT') {
                $checkinStatus = rand(0, 10) > 1 ? 'CHECKED_IN' : 'NO_SHOW';
            } else {
                $checkinStatus = rand(0, 10) > 4 ? 'CHECKED_IN' : 'WAITING';
            }

            $email = Str::slug($c['name'], '.') . '@gmail.com';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                    'reward_points' => rand(50, 200),
                ]
            );

            try {
                $booking = Booking::create([
                    'booking_code'   => 'BH-' . strtoupper(Str::random(8)),
                    'trip_id'        => $trip->id,
                    'user_id'        => $user->id,
                    'booking_source' => 'ONLINE_WEB',
                    'customer_name'  => $c['name'],
                    'customer_phone' => $c['phone'],
                    'customer_email' => $email,
                    'pickup_stop_id' => $pickupStop->id,
                    'dropoff_stop_id'=> $dropoffStop->id,
                    'pickup_order'   => 1,
                    'dropoff_order'  => 3,
                    'total_seats'    => 1,
                    'total_amount'   => $price,
                    'paid_amount'    => $paidAmt,
                    'payment_status' => $payStatus,
                    'payment_method' => $method,
                    'checkin_status' => $checkinStatus,
                    'checkin_method' => $checkinStatus === 'CHECKED_IN' ? 'QR_SCAN' : null,
                    'checked_in_at'  => $checkinStatus === 'CHECKED_IN' ? now()->subMinutes(rand(5, 60)) : null,
                    'is_boarded'     => $checkinStatus === 'CHECKED_IN' ? 1 : 0,
                    'boarding_status'=> $checkinStatus === 'CHECKED_IN' ? 'BOARDED' : 'NOT_BOARDED',
                    'qr_token'       => 'QR-' . strtoupper(Str::random(12)),
                    'notes'          => 'Đặt vé trực tuyến qua BusHub',
                ]);

                BookingSeat::create([
                    'booking_id'   => $booking->id,
                    'trip_id'      => $trip->id,
                    'seat_number'  => $seatNum,
                    'floor'        => $floor,
                    'pickup_order' => 1,
                    'dropoff_order'=> 3,
                    'price'        => $price,
                ]);
            } catch (\Exception $e) {
                // Skip duplicate QR tokens etc.
            }
        }
    }

    private function seedDriverReviews(array $drivers): void
    {
        $comments = [
            [5, 'Bác tài lái rất khéo, đúng giờ, xe sạch sẽ. Rất hài lòng!'],
            [5, 'Chuyến đi tuyệt vời, bác tài chuyên nghiệp và thân thiện.'],
            [4, 'Lái xe ổn định, nhưng xe hơi lạnh. Nhìn chung tốt.'],
            [5, 'Đúng giờ, lái êm, dịch vụ tốt. Sẽ đi lại lần sau.'],
            [4, 'Xe sạch, bác tài chỉnh chước tốt, chuyến đi vui.'],
            [3, 'Chấp nhận được, nhưng xe hơi ồn trên đèo.'],
            [5, 'Tuyệt vời! Bác tài lịch sự, xe cực kỳ sạch sẽ.'],
            [4, 'Chuyến đi ổn. Bác tài nhiều kinh nghiệm, an toàn.'],
            [5, 'Rất hài lòng! Đúng giờ và lịch sự. Cảm ơn BusHub!'],
            [4, 'Dịch vụ tốt, xe thoải mái, sẽ giới thiệu cho bạn bè.'],
            [3, 'Xe ok nhưng trễ 15 phút. Hy vọng lần sau cải thiện hơn.'],
            [5, 'Bác tài rất thân thiện, biết nhường đường. An toàn 100%.'],
            [4, 'Ghế thoải mái, điều hòa mát, hài lòng với chuyến đi.'],
            [5, 'Tuyến đường đẹp, bác tài kinh nghiệm. Rất ưng!'],
            [2, 'Trễ 30 phút, xe không có wifi. Thất vọng một chút.'],
        ];

        foreach ($drivers as $driver) {
            $numReviews = rand(5, 12);
            for ($i = 0; $i < $numReviews; $i++) {
                $c = $comments[$i % count($comments)];
                try {
                    Review::create([
                        'driver_id'      => $driver->id,
                        'rating'         => $c[0],
                        'safety_rating'  => min(5, $c[0] + rand(-1, 1)),
                        'service_rating' => min(5, $c[0] + rand(-1, 0)),
                        'comment'        => $c[1],
                        'is_anonymous'   => (bool)rand(0, 1),
                        'reviewer_name'  => 'Khách hàng ' . rand(100, 999),
                        'created_at'     => now()->subDays(rand(1, 30)),
                        'updated_at'     => now()->subDays(rand(0, 5)),
                    ]);
                } catch (\Exception $e) {}
            }
        }
    }
}
