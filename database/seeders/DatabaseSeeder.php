<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\Trip;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Core Users (Admin, 10 Conductors/Staff, Customer)
        $admin = User::firstOrCreate(['email' => 'admin@futa.vn'], [
            'name' => 'Tổng Quản Trị FUTA',
            'phone' => '0901112233',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        $customer = User::firstOrCreate(['email' => 'hieu@gmail.com'], [
            'name' => 'Võ Trọng Hiếu',
            'phone' => '0905123456',
            'password' => Hash::make('123456'),
            'role' => 'customer',
        ]);

        // SEED 10 REAL CONDUCTORS / LƠ XE
        $conductorsData = [
            ['name' => 'Trần Văn Lơ Xe', 'phone' => '0904445566', 'email' => 'staff@futa.vn'],
            ['name' => 'Nguyễn Văn Phúc', 'phone' => '0905221133', 'email' => 'phuc.futa@futa.vn'],
            ['name' => 'Lê Quốc Đạt', 'phone' => '0905334455', 'email' => 'dat.futa@futa.vn'],
            ['name' => 'Phạm Thành Long', 'phone' => '0905447788', 'email' => 'long.futa@futa.vn'],
            ['name' => 'Vũ Anh Tuấn', 'phone' => '0905558899', 'email' => 'tuan.futa@futa.vn'],
            ['name' => 'Hoàng Đình Khang', 'phone' => '0905669900', 'email' => 'khang.futa@futa.vn'],
            ['name' => 'Bùi Văn Hậu', 'phone' => '0905771122', 'email' => 'hau.futa@futa.vn'],
            ['name' => 'Trịnh Công Sơn', 'phone' => '0905882233', 'email' => 'son.futa@futa.vn'],
            ['name' => 'Đỗ Gia Huy', 'phone' => '0905993344', 'email' => 'huy.futa@futa.vn'],
            ['name' => 'Phan Minh Triết', 'phone' => '0905004455', 'email' => 'triet.futa@futa.vn'],
        ];

        $conductors = [];
        foreach ($conductorsData as $c) {
            $conductors[] = User::firstOrCreate(['email' => $c['email']], [
                'name' => $c['name'],
                'phone' => $c['phone'],
                'password' => Hash::make('123456'),
                'role' => 'staff',
            ]);
        }

        // 2. SEED 8 REAL DRIVERS / TÀI XẾ
        $driversData = [
            ['name' => 'Võ Trọng Hiếu', 'phone' => '0912345678', 'license' => 'B2-99887766', 'exp' => 8, 'rating' => 4.95, 'trips' => 142],
            ['name' => 'Nguyễn Văn Minh', 'phone' => '0987654321', 'license' => 'E-11223344', 'exp' => 10, 'rating' => 4.88, 'trips' => 268],
            ['name' => 'Trần Đình Hùng', 'phone' => '0905112233', 'license' => 'E-55667788', 'exp' => 12, 'rating' => 4.92, 'trips' => 310],
            ['name' => 'Lê Hoàng Nam', 'phone' => '0935445566', 'license' => 'E-99001122', 'exp' => 6, 'rating' => 4.85, 'trips' => 98],
            ['name' => 'Phạm Quốc Bảo', 'phone' => '0903778899', 'license' => 'E-33445566', 'exp' => 9, 'rating' => 4.90, 'trips' => 215],
            ['name' => 'Huỳnh Văn Thành', 'phone' => '0918223344', 'license' => 'E-77889900', 'exp' => 14, 'rating' => 4.97, 'trips' => 420],
            ['name' => 'Đặng Minh Tuấn', 'phone' => '0972556677', 'license' => 'E-22334455', 'exp' => 7, 'rating' => 4.86, 'trips' => 130],
            ['name' => 'Ngô Đức Trọng', 'phone' => '0981889900', 'license' => 'E-66778899', 'exp' => 11, 'rating' => 4.93, 'trips' => 290],
        ];

        $drivers = [];
        foreach ($driversData as $d) {
            $drivers[] = Driver::firstOrCreate(['phone' => $d['phone']], [
                'name' => $d['name'],
                'license_number' => $d['license'],
                'license_class' => 'Hạng E',
                'years_experience' => $d['exp'],
                'avg_rating' => $d['rating'],
                'total_trips' => $d['trips'],
                'status' => 'ACTIVE',
            ]);
        }

        // 3. SEED BUSES
        $bus1 = Bus::create([
            'license_plate' => '43B-012.34',
            'bus_type' => 'Giường nằm 34 chỗ',
            'total_seats' => 34,
            'floors' => 2,
            'amenities' => ['Wifi tốc độ cao', 'Điều hòa 2 chiều', 'Cổng sạc Type-C/USB', 'Chăn gối kháng khuẩn', 'Màn hình LCD'],
            'status' => 'ACTIVE',
            'current_km' => 45000,
        ]);

        $bus2 = Bus::create([
            'license_plate' => '43B-056.78',
            'bus_type' => 'Limousine 34 phòng VIP',
            'total_seats' => 34,
            'floors' => 2,
            'amenities' => ['Wifi 5G', 'Massage tự động', 'Điều hòa riêng', 'Toilet khép kín', 'Cổng sạc nhanh', 'Màn hình TV riêng'],
            'status' => 'ACTIVE',
            'current_km' => 15000,
        ]);

        $bus3 = Bus::create([
            'license_plate' => '43B-099.99',
            'bus_type' => 'Xe 2 Tầng: Tầng 1 Ghế Ngồi - Tầng 2 Giường Nằm',
            'total_seats' => 34,
            'floors' => 2,
            'amenities' => ['Wifi tốc độ cao', 'Điều hòa', 'Cổng sạc USB', 'Nước suối & khăn lạnh'],
            'status' => 'ACTIVE',
            'current_km' => 25000,
        ]);

        $bus4 = Bus::create([
            'license_plate' => '43B-077.88',
            'bus_type' => 'Giường nằm 34 chỗ',
            'total_seats' => 34,
            'floors' => 2,
            'amenities' => ['Wifi', 'Điều hòa', 'Cổng sạc USB'],
            'status' => 'MAINTENANCE',
            'current_km' => 120000,
            'maintenance_notes' => 'Bảo dưỡng định kỳ 20.000 km và thay dầu máy tại Garage FUTA',
        ]);

        // 4. Seed Route 1: Tuyến Đà Nẵng <-> Hội An (5 trạm)
        $routeDNHoiAn = Route::create([
            'name' => 'Tuyến Đà Nẵng ➔ Hội An',
            'origin' => 'Bến xe TT Đà Nẵng',
            'destination' => 'Bến xe Hội An',
            'distance_km' => 35,
            'estimated_hours' => 1.25,
        ]);

        $stop0 = RouteStop::create(['route_id' => $routeDNHoiAn->id, 'stop_name' => 'Bến xe TT Đà Nẵng', 'stop_order' => 0, 'distance_from_start_km' => 0]);
        $stop1 = RouteStop::create(['route_id' => $routeDNHoiAn->id, 'stop_name' => 'Ngũ Hành Sơn', 'stop_order' => 1, 'distance_from_start_km' => 10]);
        $stop2 = RouteStop::create(['route_id' => $routeDNHoiAn->id, 'stop_name' => 'Điện Ngọc (Quảng Nam)', 'stop_order' => 2, 'distance_from_start_km' => 18]);
        $stop3 = RouteStop::create(['route_id' => $routeDNHoiAn->id, 'stop_name' => 'Vĩnh Điện', 'stop_order' => 3, 'distance_from_start_km' => 26]);
        $stop4 = RouteStop::create(['route_id' => $routeDNHoiAn->id, 'stop_name' => 'Bến xe Hội An', 'stop_order' => 4, 'distance_from_start_km' => 35]);

        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop0->id, 'dropoff_stop_id' => $stop4->id, 'fare_amount' => 150000]);
        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop1->id, 'dropoff_stop_id' => $stop4->id, 'fare_amount' => 120000]);
        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop2->id, 'dropoff_stop_id' => $stop4->id, 'fare_amount' => 80000]);
        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop3->id, 'dropoff_stop_id' => $stop4->id, 'fare_amount' => 50000]);
        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop0->id, 'dropoff_stop_id' => $stop1->id, 'fare_amount' => 40000]);
        RouteFare::create(['route_id' => $routeDNHoiAn->id, 'pickup_stop_id' => $stop0->id, 'dropoff_stop_id' => $stop2->id, 'fare_amount' => 70000]);

        // 5. Seed Route 2: Tuyến Đà Nẵng -> Đông Hà
        $routeDNQT = Route::create([
            'name' => 'Tuyến Đà Nẵng ➔ Quảng Trị (Đông Hà)',
            'origin' => 'Bến xe TT Đà Nẵng',
            'destination' => 'Bến xe Đông Hà (Quảng Trị)',
            'distance_km' => 175,
            'estimated_hours' => 3.5,
        ]);
        $qtStop0 = RouteStop::create(['route_id' => $routeDNQT->id, 'stop_name' => 'Bến xe TT Đà Nẵng', 'stop_order' => 0, 'distance_from_start_km' => 0]);
        $qtStop1 = RouteStop::create(['route_id' => $routeDNQT->id, 'stop_name' => 'Lăng Cô (Huế)', 'stop_order' => 1, 'distance_from_start_km' => 35]);
        $qtStop2 = RouteStop::create(['route_id' => $routeDNQT->id, 'stop_name' => 'Bến xe Phía Nam Huế', 'stop_order' => 2, 'distance_from_start_km' => 100]);
        $qtStop3 = RouteStop::create(['route_id' => $routeDNQT->id, 'stop_name' => 'Bến xe Phía Bắc Huế', 'stop_order' => 3, 'distance_from_start_km' => 108]);
        $qtStop4 = RouteStop::create(['route_id' => $routeDNQT->id, 'stop_name' => 'Bến xe Đông Hà (Quảng Trị)', 'stop_order' => 4, 'distance_from_start_km' => 175]);

        RouteFare::create(['route_id' => $routeDNQT->id, 'pickup_stop_id' => $qtStop0->id, 'dropoff_stop_id' => $qtStop4->id, 'fare_amount' => 180000]);
        RouteFare::create(['route_id' => $routeDNQT->id, 'pickup_stop_id' => $qtStop2->id, 'dropoff_stop_id' => $qtStop4->id, 'fare_amount' => 110000]);

        // 6. Seed Scheduled Trips (100% Empty seats, 0 fake bookings)
        $trip1 = Trip::create([
            'trip_code' => 'TRIP-FUTA-101',
            'route_id' => $routeDNHoiAn->id,
            'bus_id' => $bus1->id,
            'driver_id' => $drivers[0]->id, // Võ Trọng Hiếu
            'conductor_id' => $conductors[0]->id, // Trần Văn Lơ Xe
            'departure_time' => Carbon::today()->setHour(8)->setMinute(0),
            'arrival_time' => Carbon::today()->setHour(9)->setMinute(15),
            'base_price' => 150000,
            'floor1_price' => 150000,
            'floor2_price' => 140000,
            'floor1_seat_type' => 'Giường Nằm VIP (Tầng Dưới)',
            'floor2_seat_type' => 'Giường Nằm Tiêu Chuẩn (Tầng Trên)',
            'status' => 'OPEN_FOR_SALE',
            'current_stop_order' => 0,
        ]);

        $trip2 = Trip::create([
            'trip_code' => 'TRIP-FUTA-102',
            'route_id' => $routeDNQT->id,
            'bus_id' => $bus2->id,
            'driver_id' => $drivers[1]->id, // Nguyễn Văn Minh
            'conductor_id' => $conductors[1]->id, // Nguyễn Văn Phúc
            'departure_time' => Carbon::today()->setHour(13)->setMinute(30),
            'arrival_time' => Carbon::today()->setHour(17)->setMinute(0),
            'base_price' => 180000,
            'floor1_price' => 180000,
            'floor2_price' => 170000,
            'floor1_seat_type' => 'Phòng Limousine VIP (Tầng 1)',
            'floor2_seat_type' => 'Phòng Limousine Tiêu Chuẩn (Tầng 2)',
            'status' => 'OPEN_FOR_SALE',
            'current_stop_order' => 0,
        ]);

        AuditLog::log('SYSTEM_INIT', 'Khởi tạo 8 tài xế, 10 lơ xe và các chuyến xe sẵn sàng vận hành.', 'System', 1, $admin);
    }
}
