<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Booking;
use App\Models\BookingSeat;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class TripScheduleService
{
    /**
     * Danh sách hành khách mẫu phong phú (Sinh viên Kiến Trúc DAU, người cao tuổi, gia đình, dân công sở)
     */
    public static function getCustomerPool(): array
    {
        return [
            ['name' => 'Lê Phương Thảo', 'phone' => '0935123456', 'email' => 'le.phuongthao@dau.edu.vn', 'is_student' => true],
            ['name' => 'Nguyễn Hoàng Anh', 'phone' => '0905987654', 'email' => 'hoanganh.arch@dau.edu.vn', 'is_student' => true],
            ['name' => 'Phan Minh Tuấn', 'phone' => '0914555666', 'email' => 'tuanpm@dut.udn.vn', 'is_student' => true],
            ['name' => 'Trần Ngọc Bảo Thy', 'phone' => '0978333222', 'email' => 'baothy.arch@dau.edu.vn', 'is_student' => true],
            ['name' => 'Võ Trọng Hiếu', 'phone' => '0988250420', 'email' => 'votronghieu@dau.edu.vn', 'is_student' => true],
            ['name' => 'Bác Bùi Đình Trọng', 'phone' => '0903112233', 'email' => 'trong.bui68@gmail.com', 'is_elderly' => true],
            ['name' => 'Bác Nguyễn Thị Mai', 'phone' => '0913445566', 'email' => 'mainth.dn@gmail.com', 'is_elderly' => true],
            ['name' => 'Cô Trần Thị Cẩm Tú', 'phone' => '0905223344', 'email' => 'camtu.tran@gmail.com'],
            ['name' => 'Chú Lê Văn Cường', 'phone' => '0905778899', 'email' => 'cuong.le.dn@gmail.com', 'is_elderly' => true],
            ['name' => 'Gia đình anh Võ Minh Trí', 'phone' => '0912889900', 'email' => 'minhtri.family@gmail.com', 'is_family' => true],
            ['name' => 'Chị Nguyễn Thúy Vy', 'phone' => '0932111333', 'email' => 'thuyvy.dn@gmail.com', 'is_baby' => true],
            ['name' => 'Đoàn Văn Hậu', 'phone' => '0905667788', 'email' => 'vanhau.doan@gmail.com'],
            ['name' => 'Vũ Minh Quang', 'phone' => '0905990011', 'email' => 'quang.vm@fpt.com'],
            ['name' => 'Phạm Quỳnh Nga', 'phone' => '0918223344', 'email' => 'quynhnga.pham@gmail.com'],
            ['name' => 'Nguyễn Đức Thắng', 'phone' => '0905121314', 'email' => 'thang.nd@vnpay.vn'],
            ['name' => 'Trương Diệu Linh', 'phone' => '0972445566', 'email' => 'dieulinh.truong@gmail.com'],
            ['name' => 'Cao Bá Quát', 'phone' => '0905556677', 'email' => 'baquat.cao@gmail.com'],
            ['name' => 'Huỳnh Tấn Phát', 'phone' => '0919667788', 'email' => 'tanphat.huynh@gmail.com'],
            ['name' => 'Dương Thùy Trang', 'phone' => '0905778800', 'email' => 'thuytrang.duong@gmail.com'],
            ['name' => 'Hồ Ngọc Hà', 'phone' => '0935889911', 'email' => 'ngocha.ho@gmail.com'],
            ['name' => 'Trần Thế Anh', 'phone' => '0905221199', 'email' => 'theanh.tran@gmail.com'],
            ['name' => 'Lâm Thanh Nhã', 'phone' => '0905332211', 'email' => 'thanhnha.lam@gmail.com'],
            ['name' => 'Vũ Thu Uyên', 'phone' => '0914778822', 'email' => 'thuuyen.vu@gmail.com'],
            ['name' => 'Phạm Tuấn Kiệt', 'phone' => '0905881144', 'email' => 'tuankiet.p@gmail.com'],
            ['name' => 'Mai Phương Lan', 'phone' => '0905992277', 'email' => 'phuonglan.mai@gmail.com'],
            ['name' => 'Hoàng Kim Ngân', 'phone' => '0905333777', 'email' => 'ngan.kim@dau.edu.vn', 'is_student' => true],
            ['name' => 'Đinh Công Bằng', 'phone' => '0905444888', 'email' => 'bang.dc@gmail.com'],
            ['name' => 'Tạ Quang Bửu', 'phone' => '0905666999', 'email' => 'buu.tq@gmail.com'],
        ];
    }

    /**
     * Đảm bảo luôn luôn có đủ lịch xe chạy và ĐẦY ĐỦ HÀNH KHÁCH trên xe cho cả ngày quá khứ (-3 ngày: 01, 02, 03/11) 
     * lẫn hôm nay (04/11) và tương lai (+3 ngày: 05, 06, 07/11).
     */
    public static function ensureUpcomingTripsExist(int $daysAhead = 1): void
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $todayStr = $today->toDateString();
        $cacheKey = 'trips_ensured_schedule_' . $todayStr . '_' . $daysAhead;

        // Nếu đã kiểm tra lịch xe trong vòng 1 giờ gần nhất, bỏ qua ngay lập tức để tải trang siêu tốc
        if (Cache::has($cacheKey)) {
            return;
        }

        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Chỉ quét hôm nay và ngày mai khi có request web để trang web tải ngay trong 0.5s
        for ($i = 0; $i <= $daysAhead; $i++) {
            $targetDate = $today->copy()->addDays($i);
            $targetDateStr = $targetDate->toDateString();

            $existingCount = Trip::whereDate('departure_time', $targetDateStr)->count();

            // Nếu ngày này chưa có chuyến xe nào, sinh các chuyến chính
            if ($existingCount < 5) {
                self::generateTripsForDate($targetDate, $now);
            }
        }

        // Lưu vào Cache 2 giờ để không phải quét lại
        Cache::put($cacheKey, true, now()->addHours(2));
    }

    /**
     * Sinh khách đặt vé thực tế và lấp đầy chỗ ngồi trên xe cho một chuyến xe cụ thể
     */
    public static function seedPassengersForTrip(Trip $trip, ?Carbon $now = null): void
    {
        if (!$now) {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
        }

        // Nếu chuyến này đã có khách thì không sinh thêm
        if (Booking::where('trip_id', $trip->id)->exists()) {
            return;
        }

        $trip->loadMissing(['route.stops', 'bus']);
        $stops = $trip->route?->stops ? $trip->route->stops->sortBy('stop_order')->values() : collect();
        if ($stops->isEmpty()) {
            return;
        }

        $depTime = Carbon::parse($trip->departure_time);
        $arrTime = Carbon::parse($trip->arrival_time);
        $isPast = $now->gte($depTime);
        $isCompleted = $now->gte($arrTime);

        // Cập nhật trạng thái chuyến xe nếu cần
        if ($isCompleted && $trip->status !== 'COMPLETED') {
            $trip->update([
                'status' => 'COMPLETED',
                'departed_at' => $depTime,
                'completed_at' => $arrTime,
            ]);
        } elseif ($isPast && !$isCompleted && $trip->status !== 'IN_TRANSIT') {
            $trip->update([
                'status' => 'IN_TRANSIT',
                'departed_at' => $depTime,
                'current_speed_kmh' => rand(65, 80),
                'gps_updated_at' => $now,
            ]);
        }

        $customerPool = self::getCustomerPool();
        shuffle($customerPool);

        $notePool = [
            'elderly' => 'Hành khách cao tuổi giường tầng 1 cần hỗ trợ lên xuống khi tới trạm.',
            'seasick' => 'Hành khách say xe nặng, xin ưu tiên giường tầng 1 đón gió.',
            'student' => 'Sinh viên Đại học Kiến Trúc (DAU) về quê đón Tết có 2 kiện hành lý đồ án gửi hầm.',
            'baby'    => 'Có em bé 1 tuổi đi cùng mẹ, ưu tiên giường tầng 1 an toàn.',
            'luggage' => 'Hành khách mang theo 2 thùng hải sản đông lạnh gửi hầm xe.',
            'pickup'  => 'Đón khách tại Trạm Cửa Ngõ (gần Bến xe trung tâm Đà Nẵng).',
        ];

        // Số lượng khách trên chuyến: Chuyến đã chạy/đang chạy đông khách hơn (12 - 20 vé), chuyến tới (6 - 12 vé)
        $hour = (int)$depTime->format('H');
        $isPeak = in_array($hour, [6, 7, 8, 13, 14, 17, 18, 20]);

        if ($isCompleted) {
            $numBookings = $isPeak ? rand(14, 20) : rand(11, 16);
        } elseif ($trip->status === 'IN_TRANSIT') {
            $numBookings = $isPeak ? rand(13, 18) : rand(10, 15);
        } elseif ($trip->status === 'BOARDING') {
            $numBookings = rand(10, 16);
        } elseif ($depTime->isToday()) {
            $numBookings = rand(8, 14);
        } elseif ($depTime->isTomorrow()) {
            $numBookings = rand(6, 12);
        } else {
            $numBookings = rand(4, 9);
        }

        $usedSeats = [];
        $pickupStopDefault = $stops->first();
        $dropoffStopDefault = $stops->last();

        for ($k = 0; $k < $numBookings; $k++) {
            if ($k >= count($customerPool)) break;
            $cust = $customerPool[$k];

            // 20% khả năng đặt 2 vé cùng lúc (Gia đình / Bạn bè)
            $seatsInBooking = (rand(1, 100) > 80 && count($usedSeats) <= 30) ? 2 : 1;
            $bookingSeats = [];

            for ($s = 0; $s < $seatsInBooking; $s++) {
                $att = 0;
                do {
                    $att++;
                    if (!empty($cust['is_elderly']) || !empty($cust['is_baby'])) {
                        $floor = 1;
                        $seatIdx = rand(1, 8);
                    } else {
                        $floor = rand(1, 2);
                        $seatIdx = rand(1, 18);
                    }
                    $seatNum = ($floor === 1 ? 'A' : 'B') . str_pad($seatIdx, 2, '0', STR_PAD_LEFT);
                } while (in_array($seatNum, $usedSeats) && $att < 40);

                if (!in_array($seatNum, $usedSeats)) {
                    $usedSeats[] = $seatNum;
                    $seatPrice = $floor === 1 ? (float)$trip->floor1_price : (float)$trip->floor2_price;
                    if ($seatPrice <= 0) $seatPrice = (float)$trip->base_price;
                    $bookingSeats[] = [
                        'seat_number' => $seatNum,
                        'floor'       => $floor,
                        'price'       => $seatPrice,
                    ];
                }
            }

            if (empty($bookingSeats)) continue;

            $actualSeatCount = count($bookingSeats);
            $totalPrice = array_sum(array_column($bookingSeats, 'price'));

            // Gán điểm đón/trả thực tế từ lộ trình
            $pickupStop = $pickupStopDefault;
            $dropoffStop = $dropoffStopDefault;
            if ($stops->count() >= 4 && rand(0, 10) > 7) {
                $pickupStop = $stops->get(1) ?? $pickupStopDefault;
            }

            // Ghi chú đặc biệt cho tài xế và lơ xe
            $notes = 'Đặt vé trực tuyến qua nhaxekientruc';
            if (!empty($cust['is_elderly'])) {
                $notes = $notePool['elderly'];
            } elseif (!empty($cust['is_student'])) {
                $notes = $notePool['student'];
            } elseif (!empty($cust['is_baby'])) {
                $notes = $notePool['baby'];
            } elseif ($k % 4 === 0) {
                $notes = $notePool['seasick'];
            }

            // Trạng thái thanh toán: Chuyến cũ 100% PAID, chuyến mới 80% PAID, 20% PARTIAL
            $payStatus = ($isCompleted || $trip->status === 'IN_TRANSIT') ? 'PAID' : (rand(0, 10) > 2 ? 'PAID' : 'PARTIAL');
            $paidAmt = $payStatus === 'PAID' ? $totalPrice : (float)round($totalPrice * 0.3);

            // Trạng thái lên xe:
            // Tuyệt đối không để chuyến trong tương lai bị đánh dấu đã lên xe!
            $isFuture = $depTime->isFuture();
            if ($isCompleted || $trip->status === 'IN_TRANSIT') {
                $checkinStatus = 'CHECKED_IN';
            } elseif ($trip->status === 'BOARDING' && !$isFuture) {
                $checkinStatus = rand(0, 10) > 3 ? 'CHECKED_IN' : 'WAITING';
            } else {
                // Toàn bộ chuyến tương lai (ngày mai, ngày kia, 8/11, 9/11...) 100% là WAITING
                $checkinStatus = 'WAITING';
            }

            $bookedAt = $depTime->copy()->subHours(rand(2, 48))->subMinutes(rand(2, 58));
            if ($bookedAt->gt($now)) {
                $bookedAt = $now->copy()->subMinutes(rand(5, 180));
            }

            $custEmail = $cust['email'] ?? (Str::slug($cust['name'], '.') . '@gmail.com');
            $user = User::firstOrCreate(
                ['email' => $custEmail],
                [
                    'name' => $cust['name'],
                    'phone' => $cust['phone'],
                    'password' => bcrypt('password123'),
                    'role' => 'customer',
                    'is_student' => !empty($cust['is_student']) || str_contains($custEmail, '.edu.vn'),
                    'reward_points' => rand(50, 300),
                ]
            );

            $booking = Booking::create([
                'booking_code'    => 'KT-' . strtoupper(Str::random(8)),
                'trip_id'         => $trip->id,
                'user_id'         => $user->id,
                'booking_source'  => rand(0, 10) > 8 ? 'ROADSIDE_ON_BUS' : 'ONLINE_WEB',
                'customer_name'   => $cust['name'],
                'customer_phone'  => $cust['phone'],
                'customer_email'  => $custEmail,
                'pickup_stop_id'  => $pickupStop->id,
                'dropoff_stop_id' => $dropoffStop->id,
                'pickup_order'    => $pickupStop->stop_order ?? 1,
                'dropoff_order'   => $dropoffStop->stop_order ?? ($stops->count() ?: 3),
                'total_seats'     => $actualSeatCount,
                'total_amount'    => $totalPrice,
                'paid_amount'     => $paidAmt,
                'payment_status'  => $payStatus,
                'payment_method'  => rand(0, 1) ? 'VNPAY' : (rand(0, 1) ? 'MOMO' : 'CASH'),
                'checkin_status'  => $checkinStatus,
                'checkin_method'  => $checkinStatus === 'CHECKED_IN' ? 'QR_SCAN' : null,
                'checked_in_at'   => $checkinStatus === 'CHECKED_IN' ? $depTime->copy()->subMinutes(rand(5, 30)) : null,
                'is_boarded'      => $checkinStatus === 'CHECKED_IN' ? 1 : 0,
                'boarding_status' => $checkinStatus === 'CHECKED_IN' ? 'BOARDED' : 'NOT_BOARDED',
                'qr_token'        => 'QR-' . strtoupper(Str::random(12)),
                'notes'           => $notes,
                'created_at'      => $bookedAt,
                'updated_at'      => $bookedAt,
            ]);

            foreach ($bookingSeats as $bs) {
                BookingSeat::create([
                    'booking_id'    => $booking->id,
                    'trip_id'       => $trip->id,
                    'seat_number'   => $bs['seat_number'],
                    'floor'         => $bs['floor'],
                    'pickup_order'  => $pickupStop->stop_order ?? 1,
                    'dropoff_order' => $dropoffStop->stop_order ?? ($stops->count() ?: 3),
                    'price'         => $bs['price'],
                ]);
            }
        }
    }

    /**
     * Sinh lịch trình 20 chuyến chuẩn mực cho 10 xe trong 1 ngày cụ thể
     */
    public static function generateTripsForDate(Carbon $date, Carbon $now): void
    {
        $buses = Bus::where('status', 'ACTIVE')->orderBy('id', 'asc')->get();
        if ($buses->isEmpty()) {
            $buses = Bus::orderBy('id', 'asc')->get();
        }
        $drivers = Driver::all();
        $conductors = User::whereIn('role', ['conductor', 'staff'])->orderBy('id', 'asc')->get();

        $routes = Route::with('stops')->get();
        if ($routes->isEmpty() || $buses->isEmpty() || $drivers->isEmpty()) {
            return;
        }

        // Tuyến đường
        $rDN_Hue = $routes->firstWhere('start_point', 'Đà Nẵng') ?? $routes[0];
        $rHue_DN = $routes->firstWhere('end_point', 'Đà Nẵng') ?? ($routes[1] ?? $routes[0]);
        $rDN_QB  = $routes->firstWhere('end_point', 'Quảng Bình') ?? ($routes[2] ?? $routes[0]);
        $rQB_DN  = $routes->firstWhere('start_point', 'Quảng Bình') ?? ($routes[3] ?? $routes[0]);
        $rDN_QN  = $routes->firstWhere('end_point', 'Quy Nhơn') ?? ($routes[4] ?? $routes[0]);
        $rDN_BMT = $routes->firstWhere('end_point', 'Buôn Ma Thuột') ?? ($routes[5] ?? $routes[0]);

        // Cấu hình 10 xe x 2 chuyến/ngày = 20 chuyến/ngày
        $scheduleTemplates = [
            // Xe 1: Đà Nẵng ⇄ Huế (06:00 & 13:00)
            [
                'bus_idx' => 0, 'driver_idx' => 0, 'conductor_idx' => 0,
                'trips' => [
                    ['route' => $rDN_Hue, 'dep' => '06:00', 'dur' => 2.5, 'price' => 140000],
                    ['route' => $rHue_DN, 'dep' => '13:00', 'dur' => 2.5, 'price' => 140000],
                ]
            ],
            // Xe 2: Đà Nẵng ⇄ Huế (09:30 & 16:30)
            [
                'bus_idx' => 1, 'driver_idx' => 1, 'conductor_idx' => 1,
                'trips' => [
                    ['route' => $rDN_Hue, 'dep' => '09:30', 'dur' => 2.5, 'price' => 140000],
                    ['route' => $rHue_DN, 'dep' => '16:30', 'dur' => 2.5, 'price' => 140000],
                ]
            ],
            // Xe 3: Đà Nẵng ⇄ Huế (11:00 & 18:30)
            [
                'bus_idx' => 2, 'driver_idx' => 2, 'conductor_idx' => 2,
                'trips' => [
                    ['route' => $rDN_Hue, 'dep' => '11:00', 'dur' => 2.5, 'price' => 140000],
                    ['route' => $rHue_DN, 'dep' => '18:30', 'dur' => 2.5, 'price' => 140000],
                ]
            ],
            // Xe 4: Đà Nẵng ⇄ Quảng Bình (07:00 & 14:00)
            [
                'bus_idx' => 3, 'driver_idx' => 3, 'conductor_idx' => 3,
                'trips' => [
                    ['route' => $rDN_QB, 'dep' => '07:00', 'dur' => 5.5, 'price' => 195000],
                    ['route' => $rQB_DN, 'dep' => '14:00', 'dur' => 5.5, 'price' => 195000],
                ]
            ],
            // Xe 5: Đà Nẵng ⇄ Quảng Bình (08:30 & 15:30)
            [
                'bus_idx' => 4, 'driver_idx' => 4, 'conductor_idx' => 4,
                'trips' => [
                    ['route' => $rDN_QB, 'dep' => '08:30', 'dur' => 5.5, 'price' => 195000],
                    ['route' => $rQB_DN, 'dep' => '15:30', 'dur' => 5.5, 'price' => 195000],
                ]
            ],
            // Xe 6: Đà Nẵng ⇄ Quảng Bình (20:00 & 22:30)
            [
                'bus_idx' => 5, 'driver_idx' => 5, 'conductor_idx' => 5,
                'trips' => [
                    ['route' => $rDN_QB, 'dep' => '20:00', 'dur' => 5.5, 'price' => 210000],
                    ['route' => $rQB_DN, 'dep' => '22:30', 'dur' => 5.5, 'price' => 210000],
                ]
            ],
            // Xe 7: Đà Nẵng ⇄ Quy Nhơn (07:30 & 19:30)
            [
                'bus_idx' => 6, 'driver_idx' => 6, 'conductor_idx' => 6,
                'trips' => [
                    ['route' => $rDN_QN, 'dep' => '07:30', 'dur' => 6.0, 'price' => 240000],
                    ['route' => $rDN_QN, 'dep' => '19:30', 'dur' => 6.0, 'price' => 240000],
                ]
            ],
            // Xe 8: Đà Nẵng ⇄ Buôn Ma Thuột (08:00 & 20:30)
            [
                'bus_idx' => 7, 'driver_idx' => 7, 'conductor_idx' => 7,
                'trips' => [
                    ['route' => $rDN_BMT, 'dep' => '08:00', 'dur' => 10.0, 'price' => 320000],
                    ['route' => $rDN_BMT, 'dep' => '20:30', 'dur' => 10.0, 'price' => 330000],
                ]
            ],
            // Xe 9: Đà Nẵng ⇄ Huế (14:30 & 21:00)
            [
                'bus_idx' => 8, 'driver_idx' => 8, 'conductor_idx' => 8,
                'trips' => [
                    ['route' => $rDN_Hue, 'dep' => '14:30', 'dur' => 2.5, 'price' => 140000],
                    ['route' => $rHue_DN, 'dep' => '21:00', 'dur' => 2.5, 'price' => 140000],
                ]
            ],
            // Xe 10: Đà Nẵng ⇄ Huế (08:00 & 17:00)
            [
                'bus_idx' => 9, 'driver_idx' => 9, 'conductor_idx' => 9,
                'trips' => [
                    ['route' => $rDN_Hue, 'dep' => '08:00', 'dur' => 2.5, 'price' => 150000],
                    ['route' => $rHue_DN, 'dep' => '17:00', 'dur' => 2.5, 'price' => 150000],
                ]
            ],
        ];

        foreach ($scheduleTemplates as $tpl) {
            $bus = $buses[$tpl['bus_idx'] % $buses->count()];
            $driver = $drivers[$tpl['driver_idx'] % $drivers->count()];
            $conductor = $conductors[$tpl['conductor_idx'] % $conductors->count()];

            foreach ($tpl['trips'] as $tItem) {
                [$h, $m] = explode(':', $tItem['dep']);
                $depTime = $date->copy()->setHour((int)$h)->setMinute((int)$m)->setSecond(0);
                $arrTime = $depTime->copy()->addMinutes((int)($tItem['dur'] * 60));

                // Xác định trạng thái theo thời gian thực
                if ($now->gte($arrTime)) {
                    $status = 'COMPLETED';
                } elseif ($now->gte($depTime) && $now->lt($arrTime)) {
                    $status = 'IN_TRANSIT';
                } elseif ($now->diffInMinutes($depTime, false) <= 45 && $now->lt($depTime)) {
                    $status = 'BOARDING';
                } else {
                    $status = 'OPEN_FOR_SALE';
                }

                $tripCode = 'KT-' . $date->format('md') . '-' . str_pad($bus->id * 10 + (int)$h, 4, '0', STR_PAD_LEFT);

                // Kiểm tra xem chuyến này đã tồn tại chưa
                $trip = Trip::where('bus_id', $bus->id)
                    ->where('departure_time', $depTime)
                    ->first();

                if (!$trip) {
                    $trip = Trip::create([
                        'trip_code'         => $tripCode,
                        'route_id'          => $tItem['route']->id,
                        'bus_id'            => $bus->id,
                        'driver_id'         => $driver->id,
                        'conductor_id'      => $conductor->id,
                        'departure_time'    => $depTime,
                        'arrival_time'      => $arrTime,
                        'base_price'        => $tItem['price'],
                        'floor1_price'      => $tItem['price'],
                        'floor2_price'      => max(50000, $tItem['price'] - 10000),
                        'status'            => $status,
                        'current_lat'       => 16.0544 + (rand(-100, 100) / 10000),
                        'current_lng'       => 108.2022 + (rand(-100, 100) / 10000),
                        'current_speed_kmh' => $status === 'IN_TRANSIT' ? rand(65, 80) : 0,
                        'gps_updated_at'    => $status === 'IN_TRANSIT' ? $now : null,
                        'departed_at'       => in_array($status, ['IN_TRANSIT', 'COMPLETED']) ? $depTime : null,
                        'completed_at'      => $status === 'COMPLETED' ? $arrTime : null,
                        'handover_notes'    => null,
                    ]);
                }

                // Luôn đảm bảo chuyến có hành khách
                self::seedPassengersForTrip($trip, $now);
            }
        }
    }
}
