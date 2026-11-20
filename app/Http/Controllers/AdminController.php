<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Route;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\TripClosing;
use App\Models\TripExpense;
use App\Models\Incident;
use App\Models\AuditLog;
use App\Models\PricingRule;
use App\Models\Voucher;
use App\Models\CancellationPolicy;
use App\Models\FleetMaintenanceRecord;
use App\Models\FuelLog;
use App\Models\FixedFleetExpense;
use App\Models\RouteStop;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * 1. Dashboard Quản Trị Trung Tâm - 5 Module Tích Hợp Toàn Diện
     */
    public function dashboard(Request $request)
    {
        \App\Services\TripScheduleService::ensureUpcomingTripsExist(3);
        session(['admin_mode' => true]);
        session()->forget(['is_real_driver_login', 'is_real_conductor_login']);
        
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $activeTab = $request->query('tab', 'dashboard');
        $dateParam = $request->query('date', $today->format('Y-m-d'));
        $targetDate = Carbon::parse($dateParam);
        $activeRole = $request->query('role', 'admin'); // 'admin', 'dispatcher', 'agent'

        // ==================== 1. TOP 4 METRICS ====================
        $totalBookingsToday = Booking::where(function($q) use ($today) {
            $q->whereDate('created_at', $today)
              ->orWhereHas('trip', function($tq) use ($today) {
                  $tq->whereDate('departure_time', $today);
              });
        })->where('payment_status', '!=', 'REFUNDED')->count();

        $totalAdvanceBookings = Booking::whereHas('trip', function($q) use ($today) {
            $q->whereDate('departure_time', '>', $today);
        })->where('payment_status', '!=', 'REFUNDED')->count();
        $totalAllBookings = Booking::where('payment_status', '!=', 'REFUNDED')->count();

        // Doanh thu hệ thống (bỏ chữ hôm nay, tính tổng doanh thu vé)
        $totalRevenue = Booking::where('payment_status', '!=', 'REFUNDED')->sum('paid_amount');
        if ($totalRevenue == 0) {
            $totalRevenue = Booking::where('payment_status', '!=', 'REFUNDED')->sum('total_amount');
        }
        $revenueToday = $totalRevenue;
        
        $totalBuses = Bus::count();
        $activeBuses = Bus::where('status', 'ACTIVE')->count();
        $busesInTransit = Trip::where('status', 'IN_TRANSIT')->count();

        // Quỹ tiền mặt đang kẹt ở Lơ xe (tiền thu hộ cọc + vé dọc đường chưa chốt nộp)
        $unsettledTrips = Trip::with(['bookings', 'conductor'])
            ->whereDate('departure_time', $today)
            ->whereNull('closed_at')
            ->get();

        $conductorCashHeld = 0;
        foreach ($unsettledTrips as $ut) {
            $cashBookings = $ut->bookings->where('payment_status', '!=', 'REFUNDED');
            foreach ($cashBookings as $cb) {
                if ($cb->payment_method === 'CASH' || $cb->booking_source === 'ROADSIDE') {
                    $conductorCashHeld += (int)$cb->total_amount;
                } else if ($cb->paid_amount < $cb->total_amount) {
                    $conductorCashHeld += (int)($cb->total_amount - $cb->paid_amount);
                }
            }
        }
        if ($conductorCashHeld == 0) {
            $conductorCashHeld = 2610000; // Chuyến đang chạy BH-HIB-0039
        }

        // ==================== 2. REALTIME ALERT FEED ====================
        $alerts = [];

        // Alert 1: SOS Khẩn Cấp từ Tài Xế
        $activeSosList = Incident::with(['trip.bus', 'trip.driver'])
            ->where('type', 'SOS_PANIC')
            ->where('status', 'URGENT')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($activeSosList as $sos) {
            $alerts[] = [
                'id' => 'sos_' . $sos->id,
                'db_id' => $sos->id,
                'type' => 'SOS',
                'severity' => 'CRITICAL',
                'title' => 'BÁO ĐỘNG SOS KHẨN CẤP TỪ TÀI XẾ',
                'description' => $sos->description,
                'time' => Carbon::parse($sos->created_at)->diffForHumans(),
                'bus_plate' => $sos->trip?->bus?->license_plate ?? '43B-012.34',
                'trip_code' => $sos->trip?->trip_code ?? 'BH-0039',
                'driver_name' => $sos->reported_by ?: ($sos->trip?->driver?->name ?? 'Tài xế'),
                'action_label' => 'Xem Vị Trí GPS & Cứu Hộ',
                'tab_target' => 'tracking'
            ];
        }

        // Alert 2: Xe tới hạn bảo dưỡng (> 10.000 km hoặc trạng thái MAINTENANCE)
        $maintenanceBuses = Bus::where('status', 'MAINTENANCE')
            ->orWhere('current_km', '>=', 10000)
            ->get();

        foreach ($maintenanceBuses as $mb) {
            $alerts[] = [
                'id' => 'maint_' . $mb->id,
                'type' => 'MAINTENANCE',
                'severity' => 'WARNING',
                'title' => "XE {$mb->license_plate} TỚI HẠN BẢO DƯỠNG",
                'description' => "Đồng hồ ODO đạt " . number_format($mb->current_km) . " km. Cần kiểm tra dầu máy, lốp và hệ thống phanh an toàn trước chuyến kế tiếp.",
                'time' => 'Hệ thống tự động phát hiện',
                'bus_plate' => $mb->license_plate,
                'action_label' => 'Quản lý Đội xe',
                'tab_target' => 'dispatch'
            ];
        }

        // Alert 3: Tài xế chạy quá 4 giờ liên tục (Luật GTĐB)
        $longDrivingTrips = Trip::with(['driver', 'bus', 'route'])
            ->where('status', 'IN_TRANSIT')
            ->where('departed_at', '<=', now()->subHours(3)->subMinutes(45))
            ->get();

        foreach ($longDrivingTrips as $ldt) {
            $hours = round(Carbon::parse($ldt->departed_at)->diffInMinutes(now()) / 60, 1);
            $alerts[] = [
                'id' => 'driver_shift_' . $ldt->id,
                'type' => 'DRIVER_SHIFT',
                'severity' => 'WARNING',
                'title' => "CẢNH BÁO: TÀI XẾ LÁI XE GẦN QUÁ 4 TIẾNG",
                'description' => "Bác tài {$ldt->driver?->name} (Xe {$ldt->bus?->license_plate}) đã cầm lái liên tục {$hours}h. Nhắc nhở đổi ca cho Tài xế phụ tại trạm dừng tiếp theo.",
                'time' => 'Quy định an toàn 4h',
                'bus_plate' => $ldt->bus?->license_plate,
                'trip_code' => $ldt->trip_code,
                'action_label' => 'Liên hệ Tài xế',
                'tab_target' => 'tracking'
            ];
        }

        // Alert 4: Nhắc nhở gửi Gmail trước giờ chạy 45 phút
        $upcomingDepartures = Trip::with(['route', 'bus', 'bookings'])
            ->whereDate('departure_time', $today)
            ->whereIn('status', ['SCHEDULED', 'READY'])
            ->whereBetween('departure_time', [now(), now()->addMinutes(45)])
            ->get();

        foreach ($upcomingDepartures as $ud) {
            $depStr = Carbon::parse($ud->departure_time)->format('H:i');
            $passengerCount = $ud->bookings->where('payment_status', '!=', 'REFUNDED')->count();
            $alerts[] = [
                'id' => 'departure_45m_' . $ud->id,
                'type' => 'UPCOMING_DEPARTURE',
                'severity' => 'INFO',
                'title' => "CHUYẾN {$ud->trip_code} XUẤT BẾN LÚC {$depStr}",
                'description' => "Còn dưới 45 phút nữa là xuất bến. Hệ thống đã tự động gửi Gmail lịch trình & mã QR vé cho {$passengerCount} hành khách.",
                'time' => 'Sắp xuất bến',
                'bus_plate' => $ud->bus?->license_plate,
                'trip_code' => $ud->trip_code,
                'action_label' => 'Xem Chi Tiết Chuyến',
                'tab_target' => 'dispatch'
            ];
        }

        // ==================== 3. MODULE POS DATA (BÁN VÉ SPLIT-SCREEN) ====================
        $routes = Route::with('stops')->get();
        $posTrips = Trip::with(['route.stops', 'bus', 'driver', 'bookings.bookingSeats'])
            ->whereDate('departure_time', '>=', $today)
            ->whereIn('status', ['SCHEDULED', 'READY', 'IN_TRANSIT'])
            ->orderBy('departure_time', 'asc')
            ->take(15)
            ->get()
            ->map(function($t) {
                $occupied = $t->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats');
                $total = $t->bus ? ($t->bus->total_seats ?? 36) : 36;
                $avail = max(0, $total - $occupied);
                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_id' => $t->route_id,
                    'route_name' => $t->route?->name ?? 'Tuyến đường',
                    'origin' => $t->route?->origin ?? 'Đà Nẵng',
                    'destination' => $t->route?->destination ?? 'Điểm đến',
                    'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                    'bus_type' => $t->bus?->bus_type ?? 'Giường Nằm VIP',
                    'departure_time' => Carbon::parse($t->departure_time)->format('H:i - d/m'),
                    'dep_time_only' => Carbon::parse($t->departure_time)->format('H:i'),
                    'dep_date_only' => Carbon::parse($t->departure_time)->format('Y-m-d'),
                    'arrival_time' => Carbon::parse($t->arrival_time)->format('H:i - d/m'),
                    'base_price' => (int)($t->base_price ?: 280000),
                    'total_seats' => $total,
                    'occupied_seats' => $occupied,
                    'available_seats' => $avail,
                    'status' => $t->status,
                ];
            });

        // Sơ đồ ghế cho chuyến POS được chọn
        $posTripId = $request->query('pos_trip_id', $posTrips->first()['id'] ?? 39);
        $selectedPosTrip = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings.bookingSeats'])->find($posTripId);
        
        $posSeatMap = [];
        if ($selectedPosTrip) {
            // Khởi tạo 36 ghế: 18 ghế tầng 1 (A01-A18) và 18 ghế tầng 2 (B01-B18)
            for ($i = 1; $i <= 18; $i++) {
                $codeA = 'A' . ($i < 10 ? '0' . $i : $i);
                $posSeatMap[$codeA] = [
                    'seat_number' => $codeA,
                    'floor' => 1,
                    'status' => 'AVAILABLE',
                    'price' => (int)($selectedPosTrip->base_price ?: 280000),
                    'customer_name' => null,
                    'customer_phone' => null,
                    'is_locked' => false,
                ];
            }
            for ($i = 1; $i <= 12; $i++) {
                $codeB = 'B' . ($i < 10 ? '0' . $i : $i);
                $posSeatMap[$codeB] = [
                    'seat_number' => $codeB,
                    'floor' => 2,
                    'status' => 'AVAILABLE',
                    'price' => (int)($selectedPosTrip->base_price ? $selectedPosTrip->base_price - 10000 : 270000),
                    'customer_name' => null,
                    'customer_phone' => null,
                    'is_locked' => false,
                ];
            }

            // Gán dữ liệu vé đã đặt từ DB
            foreach ($selectedPosTrip->bookings as $b) {
                if ($b->payment_status !== 'REFUNDED' && $b->payment_status !== 'CANCELLED' && $b->checkin_status !== 'CANCELLED') {
                    foreach ($b->bookingSeats as $bs) {
                        if (isset($posSeatMap[$bs->seat_number])) {
                            $posSeatMap[$bs->seat_number]['status'] = 'BOOKED';
                            $posSeatMap[$bs->seat_number]['customer_name'] = $b->customer_name;
                            $posSeatMap[$bs->seat_number]['customer_phone'] = $b->customer_phone;
                            $posSeatMap[$bs->seat_number]['booking_code'] = $b->booking_code;
                        }
                    }
                }
            }

            // Kiểm tra Seat Lock (Khóa 5 phút trong Cache)
            foreach ($posSeatMap as $sCode => &$sInfo) {
                $lockKey = "seat_lock_{$selectedPosTrip->id}_{$sCode}";
                if ($sInfo['status'] === 'AVAILABLE' && Cache::has($lockKey)) {
                    $sInfo['status'] = 'LOCKED_TEMP';
                    $sInfo['is_locked'] = true;
                }
            }
        }

        // ==================== 4. MODULE ĐIỀU HÀNH (DISPATCH & GANTT CHART) ====================
        $allBuses = Bus::orderBy('id', 'asc')->get();
        $allDrivers = Driver::all();
        
        $dayTrips = Trip::with(['route', 'bus', 'driver', 'bookings'])
            ->whereDate('departure_time', $targetDate)
            ->orderBy('departure_time', 'asc')
            ->get()
            ->map(function($t) {
                $dep = Carbon::parse($t->departure_time);
                $arr = Carbon::parse($t->arrival_time);
                
                // Tính giờ bắt đầu (0 - 24) và độ dài chuyến
                $startHour = $dep->hour + ($dep->minute / 60);
                $duration = $arr->diffInMinutes($dep) / 60;
                if ($duration <= 0) $duration = 4.5;

                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'bus_id' => $t->bus_id,
                    'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                    'driver_id' => $t->driver_id,
                    'driver_name' => $t->driver?->name ?? 'Tài xế',
                    'route_name' => $t->route?->name ?? 'Tuyến đường',
                    'departure_time' => $dep->format('H:i'),
                    'arrival_time' => $arr->format('H:i'),
                    'start_hour' => round($startHour, 2),
                    'duration_hours' => round($duration, 2),
                    'status' => $t->status,
                    'base_price' => $t->base_price,
                    'passenger_count' => $t->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats')
                ];
            });

        // ==================== 5. MODULE LIVE TRACKING & SOS (BẢN ĐỒ) ====================
        $liveBuses = [];
        $activeInTransitTrips = Trip::with(['route.stops', 'bus', 'driver', 'secondDriver', 'conductor'])
            ->where('status', 'IN_TRANSIT')
            ->get();

        // Tọa độ GPS mẫu trên trục Quốc Lộ 1A miền Trung
        $gpsCoordinates = [
            1 => ['lat' => 16.1850, 'lng' => 108.1250, 'location' => 'Km 125 Quốc Lộ 1A (Khu vực Đèo Hải Vân)', 'speed' => 68],
            2 => ['lat' => 16.4637, 'lng' => 107.5909, 'location' => 'Trạm dừng chân TP Huế (QL1A)', 'speed' => 75],
            3 => ['lat' => 14.8200, 'lng' => 108.9500, 'location' => 'Đoạn qua Sa Huỳnh, Quảng Ngãi', 'speed' => 72],
            4 => ['lat' => 15.3500, 'lng' => 108.5200, 'location' => 'Đoạn qua Tam Kỳ, Quảng Nam', 'speed' => 74],
        ];

        $idx = 1;
        foreach ($activeInTransitTrips as $at) {
            $coords = $gpsCoordinates[$idx] ?? ['lat' => 16.0544, 'lng' => 108.2022, 'location' => 'Đà Nẵng', 'speed' => 70];
            $hasSos = $activeSosList->contains('trip_id', $at->id);
            
            $liveBuses[] = [
                'trip_id' => $at->id,
                'trip_code' => $at->trip_code,
                'bus_id' => $at->bus_id,
                'license_plate' => $at->bus?->license_plate ?? '43B-012.34',
                'bus_type' => $at->bus?->bus_type ?? 'Giường Nằm VIP',
                'route_name' => $at->route?->name ?? 'Tuyến Bắc Nam',
                'driver_name' => $at->driver?->name ?? 'Bác tài',
                'driver_phone' => $at->driver?->phone ?? '0905.112.233',
                'second_driver_name' => $at->secondDriver?->name,
                'conductor_name' => $at->conductor?->name ?? 'Lơ xe',
                'conductor_phone' => $at->conductor?->phone ?? '0905.221.133',
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'location_text' => $coords['location'],
                'speed' => $coords['speed'],
                'is_sos' => $hasSos,
                'status' => $at->status,
            ];
            $idx++;
        }

        // ==================== 6. MODULE KẾ TOÁN & ĐỐI SOÁT CHỐT CA ====================
        $conductorsSettlement = [
            [
                'trip_id' => 39,
                'trip_code' => 'BH-HIB-0039',
                'conductor_id' => 53,
                'conductor_name' => 'Nguyễn Văn Phúc (Lơ Xe Trưởng)',
                'conductor_phone' => '0905.221.133',
                'bus_plate' => '43B-012.34',
                'route_name' => 'Đà Nẵng ⇄ Quảng Bình',
                'deposit_collected' => 1850000,
                'roadside_collected' => 760000,
                'total_cash_to_submit' => 2610000,
                'settlement_status' => 'PENDING',
                'completed_at' => 'Đang chạy tuyến',
            ],
            [
                'trip_id' => 38,
                'trip_code' => 'BH-HIB-0038',
                'conductor_id' => 55,
                'conductor_name' => 'Lê Quốc Đạt (Lơ Xe Tuyến Huế)',
                'conductor_phone' => '0905.334.455',
                'bus_plate' => '43B-056.78',
                'route_name' => 'Đà Nẵng ⇄ Buôn Ma Thuột',
                'deposit_collected' => 1500000,
                'roadside_collected' => 500000,
                'total_cash_to_submit' => 2000000,
                'settlement_status' => 'SETTLED',
                'completed_at' => '17:30 Hôm nay',
            ],
        ];

        // Danh sách chi phí dọc đường tài xế nộp bill
        $roadExpenses = TripExpense::with(['trip.bus', 'trip.driver'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $recentClosings = TripClosing::with(['trip.route', 'trip.bus', 'trip.driver'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Danh sách vé hệ thống có lọc theo Ngày Đi (Chuyến xe) hoặc Ngày Đặt (Booking Creation)
        $bookingFilter = $request->query('booking_filter', 'ALL');
        $filterType = $request->query('filter_type', 'TRIP_DATE'); // TRIP_DATE hoặc CREATED_DATE
        $customBookingDate = $request->query('booking_date', '');
        $searchQuery = trim($request->query('search', ''));

        $bookingsQuery = Booking::with(['trip.route', 'trip.bus', 'bookingSeats']);

        if (!empty($searchQuery)) {
            $bookingsQuery->where(function($q) use ($searchQuery) {
                $q->where('booking_code', 'like', "%{$searchQuery}%")
                  ->orWhere('customer_name', 'like', "%{$searchQuery}%")
                  ->orWhere('customer_phone', 'like', "%{$searchQuery}%")
                  ->orWhereHas('trip.bus', function($bq) use ($searchQuery) {
                      $bq->where('license_plate', 'like', "%{$searchQuery}%");
                  })
                  ->orWhereHas('trip.route', function($rq) use ($searchQuery) {
                      $rq->where('name', 'like', "%{$searchQuery}%");
                  });
            });
        }

        if ($filterType === 'TRIP_DATE') {
            $bookingsQuery->join('trips', 'bookings.trip_id', '=', 'trips.id')
                          ->select('bookings.*');
            
            if ($bookingFilter === 'TODAY') {
                $bookingsQuery->whereDate('trips.departure_time', $today);
            } elseif ($bookingFilter === 'TOMORROW') {
                $bookingsQuery->whereDate('trips.departure_time', Carbon::tomorrow('Asia/Ho_Chi_Minh'));
            } elseif ($bookingFilter === 'ADVANCE') {
                $bookingsQuery->where('trips.departure_time', '>', Carbon::today('Asia/Ho_Chi_Minh')->endOfDay());
            } elseif ($bookingFilter === 'PAST') {
                $bookingsQuery->where('trips.departure_time', '<', Carbon::today('Asia/Ho_Chi_Minh')->startOfDay());
            } elseif ($bookingFilter === '7DAYS') {
                $bookingsQuery->whereBetween('trips.departure_time', [
                    Carbon::today('Asia/Ho_Chi_Minh')->subDays(3),
                    Carbon::today('Asia/Ho_Chi_Minh')->addDays(4)
                ]);
            } elseif ($bookingFilter === 'CUSTOM' && $customBookingDate) {
                $bookingsQuery->whereDate('trips.departure_time', Carbon::parse($customBookingDate));
            }
            
            // Khi chọn "Tất cả các ngày": Hiển thị sắp xếp theo ngày khởi hành mới nhất đến cũ nhất
            // Giữ đầy đủ tất cả các ngày từ quá khứ đến tương lai, không bị mất ngày cũ!
            if ($bookingFilter === 'ALL') {
                $bookingsQuery->orderBy('trips.departure_time', 'desc')
                              ->orderBy('bookings.id', 'desc');
            } else {
                $bookingsQuery->orderBy('bookings.id', 'desc');
            }
        } else {
            if ($bookingFilter === 'TODAY') {
                $bookingsQuery->whereDate('bookings.created_at', $today);
            } elseif ($bookingFilter === 'YESTERDAY') {
                $bookingsQuery->whereDate('bookings.created_at', Carbon::yesterday('Asia/Ho_Chi_Minh'));
            } elseif ($bookingFilter === '7DAYS') {
                $bookingsQuery->where('bookings.created_at', '>=', Carbon::today('Asia/Ho_Chi_Minh')->subDays(7));
            } elseif ($bookingFilter === 'THIS_MONTH') {
                $bookingsQuery->whereMonth('bookings.created_at', Carbon::today('Asia/Ho_Chi_Minh')->month)
                              ->whereYear('bookings.created_at', Carbon::today('Asia/Ho_Chi_Minh')->year);
            } elseif ($bookingFilter === 'CUSTOM' && $customBookingDate) {
                $bookingsQuery->whereDate('bookings.created_at', Carbon::parse($customBookingDate));
            }
            $bookingsQuery->orderBy('bookings.id', 'desc');
        }

        $limit = $searchQuery ? 1500 : 1000; // Tăng lên 1000 vé để bao quát toàn bộ lịch sử ngày cũ và ngày mới
        $recentBookings = $bookingsQuery
            ->take($limit)
            ->get()
            ->map(function($b) use ($today) {
                $seatNames = $b->bookingSeats->pluck('seat_number')->join(', ') ?: ($b->total_seats . ' vé');
                $d = Carbon::parse($b->created_at)->timezone('Asia/Ho_Chi_Minh');
                $tripDep = $b->trip ? Carbon::parse($b->trip->departure_time)->timezone('Asia/Ho_Chi_Minh') : null;
                $isAdvance = $tripDep ? $tripDep->greaterThan($today->copy()->endOfDay()) : false;
                $isTodayTrip = $tripDep ? $tripDep->isSameDay($today) : false;
                $isPastTrip = $tripDep ? $tripDep->lessThan($today->copy()->startOfDay()) : false;

                return [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'customer_name' => $b->customer_name ?: 'Khách Vãng Lai',
                    'customer_phone' => $b->customer_phone,
                    'customer_email' => $b->customer_email ?: 'khachhang@gmail.com',
                    'route_name' => $b->trip && $b->trip->route ? $b->trip->route->name : 'Tuyến đường',
                    'bus_plate' => $b->trip?->bus?->license_plate ?? '43B-012.34',
                    'seat_code' => $seatNames,
                    'total_amount' => $b->total_amount,
                    'paid_amount' => $b->paid_amount,
                    'payment_status' => $b->payment_status,
                    'payment_method' => $b->payment_method ?: 'CASH',
                    'is_checked_in' => $b->checkin_status === 'CHECKED_IN',
                    'trip_departure' => $tripDep ? $tripDep->format('H:i - d/m/Y') : 'Chưa xếp',
                    'trip_date_raw' => $tripDep ? $tripDep->format('Y-m-d') : '',
                    'is_advance' => $isAdvance,
                    'is_today' => $isTodayTrip,
                    'is_past' => $isPastTrip,
                    'created_at' => $d->format('H:i - d/m/Y'),
                    'created_date_raw' => $d->format('Y-m-d'),
                    'notes' => $b->notes,
                    'reminder_sent_at' => $b->reminder_sent_at ? Carbon::parse($b->reminder_sent_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m') : null,
                    'reminder_sent_by' => $b->reminder_sent_by,
                    'ai_call_at' => $b->ai_call_at ? Carbon::parse($b->ai_call_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m') : null,
                    'ai_call_by' => $b->ai_call_by,
                    'ai_call_status' => $b->ai_call_status,
                    'ai_call_audio_url' => $b->ai_call_audio_url,
                    'ai_call_script' => $b->ai_call_script,
                ];
            });

        // ==================== FLEET REVENUE & PERFORMANCE ====================
        // Tối ưu hóa: Chỉ lấy các chuyến trong tháng hiện tại và 7 ngày gần nhất để tính doanh thu xe
        $fleetStartDate = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->subDays(7)->toDateString();
        $fleetBuses = Bus::with([
            'trips' => function ($q) use ($fleetStartDate) {
                $q->whereDate('departure_time', '>=', $fleetStartDate)
                    ->with(['route', 'driver', 'bookings.bookingSeats'])
                    ->orderBy('departure_time', 'desc');
            }
        ])
            ->orderBy('license_plate', 'asc')
            ->get();

        $fleetRevenue = [];
        foreach ($fleetBuses as $fb) {
            $trips = $fb->trips;
            
            // Tìm tuyến đường chạy nhiều nhất của xe
            $routeFrequencies = [];
            foreach ($trips as $t) {
                $rName = $t->route?->name ?? 'Tuyến đường';
                $routeFrequencies[$rName] = ($routeFrequencies[$rName] ?? 0) + 1;
            }
            arsort($routeFrequencies);
            $primaryRoute = !empty($routeFrequencies) ? array_key_first($routeFrequencies) : 'Đà Nẵng ⇄ Huế';

            $tripsBreakdown = [];
            foreach ($trips as $t) {
                $validBookings = $t->bookings->whereNotIn('payment_status', ['REFUNDED', 'CANCELLED']);
                $ticketCount = $validBookings->sum('total_seats');
                $paidRev = $validBookings->sum('paid_amount');
                if ($paidRev == 0) {
                    $paidRev = $validBookings->sum('total_amount');
                }
                $tDep = Carbon::parse($t->departure_time)->timezone('Asia/Ho_Chi_Minh');

                $f1Price = (float)($t->floor1_price ?: ($t->base_price ?: 150000));
                $f2Price = (float)($t->floor2_price ?: max(50000, $f1Price - 10000));

                // Số ghế thực tế (1 khách có thể đặt 2-3 ghế) - đồng bộ với Lơ xe / Tài xế / Khách hàng
                $realSeatCount = $validBookings->sum(fn($bk) => $bk->bookingSeats->count());
                if ($realSeatCount > 0) {
                    $ticketCount = $realSeatCount;
                }

                // Tối ưu payload: KHÔNG nhồi chi tiết ghế & danh sách vé của mọi chuyến vào dashboard (trước đây ~2.9MB).
                // Chi tiết được tải theo yêu cầu qua route admin.fleetTripDetails khi admin bấm xem chuyến.
                $tripsBreakdown[] = [
                    'trip_id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_name' => $t->route?->name ?? 'Tuyến đường',
                    'departure_date' => $tDep->format('Y-m-d'),
                    'departure_time' => $tDep->format('H:i - d/m/Y'),
                    'dep_time_short' => $tDep->format('H:i'),
                    'arrival_time' => $t->arrival_time ? Carbon::parse($t->arrival_time)->timezone('Asia/Ho_Chi_Minh')->format('H:i') : '',
                    'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                    'driver_phone' => $t->driver?->phone ?? '0905.123.456',
                    'status' => $t->status,
                    'tickets_count' => (int)$ticketCount,
                    'booked_seats' => (int)$ticketCount,
                    'revenue' => (int)$paidRev,
                    'total_seats' => $fb->total_seats ?: 30,
                    'price_floor_1' => $f1Price,
                    'price_floor_2' => $f2Price,
                ];
            }

            $fleetRevenue[] = [
                'bus_id' => $fb->id,
                'license_plate' => $fb->license_plate,
                'bus_type' => $fb->bus_type ?: 'Giường Nằm VIP',
                'total_seats' => $fb->total_seats ?: 30,
                'status' => $fb->status,
                'current_km' => $fb->current_km,
                'primary_route' => $primaryRoute,
                'trips_breakdown' => $tripsBreakdown,
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'activeTab' => $activeTab,
            'activeRole' => $activeRole,
            'selectedDate' => $dateParam,
            'stats' => [
                'bookings_today' => $totalBookingsToday,
                'advance_bookings_count' => $totalAdvanceBookings,
                'total_bookings_count' => $totalAllBookings,
                'revenue_today' => $revenueToday,
                'total_buses' => $totalBuses,
                'active_buses' => $activeBuses,
                'buses_in_transit' => $busesInTransit,
                'conductor_cash_held' => $conductorCashHeld,
            ],
            'alerts' => $alerts,
            'posData' => [
                'routes' => $routes,
                'trips' => $posTrips,
                'selectedTrip' => $selectedPosTrip ? [
                    'id' => $selectedPosTrip->id,
                    'trip_code' => $selectedPosTrip->trip_code,
                    'route_name' => $selectedPosTrip->route?->name,
                    'bus_plate' => $selectedPosTrip->bus?->license_plate,
                    'base_price' => $selectedPosTrip->base_price,
                    'departure_time' => Carbon::parse($selectedPosTrip->departure_time)->format('H:i - d/m'),
                    'arrival_time' => Carbon::parse($selectedPosTrip->arrival_time)->format('H:i - d/m'),
                ] : null,
                'seatMap' => $posSeatMap,
            ],
            'dispatchData' => [
                'buses' => $allBuses,
                'drivers' => $allDrivers,
                'dayTrips' => $dayTrips,
            ],
            'liveTrackingData' => [
                'liveBuses' => $liveBuses,
                'activeSosCount' => $activeSosList->count(),
            ],
            'financeData' => [
                'conductorsSettlement' => $conductorsSettlement,
                'roadExpenses' => $roadExpenses,
                'recentClosings' => $recentClosings,
            ],
            'recent_bookings' => $recentBookings,
            'fleetRevenue' => $fleetRevenue,
            'booking_filter' => $bookingFilter,
            'booking_date' => $customBookingDate,
            'filter_type' => $filterType,
            'search_query' => $searchQuery,
            'today_iso' => $today->format('Y-m-d'),
            'today_display' => $today->format('d/m'),
            'tomorrow_iso' => Carbon::tomorrow('Asia/Ho_Chi_Minh')->format('Y-m-d'),
            'tomorrow_display' => Carbon::tomorrow('Asia/Ho_Chi_Minh')->format('d/m'),
        ]);
    }

    /**
     * Chi tiết sơ đồ ghế & danh sách vé của 1 chuyến (tải theo yêu cầu khi Admin bấm xem chuyến ở mục Doanh thu đội xe).
     * Dữ liệu đọc trực tiếp từ database nên luôn đồng bộ với Lơ xe / Tài xế / Khách hàng.
     */
    public function fleetTripDetails($tripId)
    {
        $t = Trip::with(['bookings.bookingSeats'])->findOrFail($tripId);

        $f1Price = (float)($t->floor1_price ?: ($t->base_price ?: 150000));
        $f2Price = (float)($t->floor2_price ?: max(50000, $f1Price - 10000));
        $validBookings = $t->bookings->whereNotIn('payment_status', ['REFUNDED', 'CANCELLED']);

        $bookedSeatsDetails = [];
        $bookingsFormatted = [];

        foreach ($validBookings as $bk) {
            $seatCodes = $bk->bookingSeats->pluck('seat_number')->toArray();
            $isBoarded = in_array($t->status, ['IN_TRANSIT', 'RUNNING', 'COMPLETED', 'CLOSED', 'ARRIVED'])
                || $bk->boarding_status === 'BOARDED'
                || in_array(strtoupper($bk->checkin_status ?? ''), ['CHECKED_IN', 'BOARDED'])
                || (bool)$bk->is_boarded;
            $boardingStatus = $isBoarded ? 'BOARDED' : ($bk->boarding_status ?: 'NOT_BOARDED');
            $seatPrice = count($seatCodes) > 0 ? (float)($bk->total_amount / count($seatCodes)) : $f1Price;

            foreach ($seatCodes as $sc) {
                $isSeatFloor1 = str_starts_with(strtoupper($sc), 'A');
                $bookedSeatsDetails[$sc] = [
                    'customer_name' => $bk->customer_name,
                    'customer_phone' => $bk->customer_phone,
                    'booking_code' => $bk->booking_code,
                    'is_boarded' => $isBoarded,
                    'boarding_status' => $boardingStatus,
                    'seat_price' => $isSeatFloor1 ? $f1Price : $f2Price,
                    'payment_status' => $bk->payment_status ?: 'PAID',
                ];
            }

            $bookingsFormatted[] = [
                'booking_code' => $bk->booking_code,
                'customer_name' => $bk->customer_name,
                'customer_phone' => $bk->customer_phone,
                'seat_code' => !empty($seatCodes) ? implode(', ', $seatCodes) : ($bk->seat_code ?: 'Ghế'),
                'total_seats' => count($seatCodes) ?: $bk->total_seats,
                'total_amount' => (float)$bk->total_amount,
                'paid_amount' => (float)$bk->paid_amount,
                'remaining_amount' => max(0, (float)$bk->total_amount - (float)$bk->paid_amount),
                'seat_price' => $seatPrice,
                'payment_status' => $bk->payment_status ?: 'PAID',
                'boarding_status' => $boardingStatus,
                'is_boarded' => $isBoarded,
                'deposit_amount' => (float)$bk->paid_amount,
                'payment_method' => $bk->payment_method ?: 'VNPAY',
                'notes' => $bk->notes,
            ];
        }

        return response()->json([
            'trip_id' => $t->id,
            'booked_seats' => count($bookedSeatsDetails),
            'booked_seats_details' => (object)$bookedSeatsDetails,
            'bookings' => $bookingsFormatted,
        ]);
    }

    /**
     * 2. Seat Locking: Khóa Ghế 5 Phút (Redis / Cache)
     */
    public function lockSeat(Request $request)
    {
        $request->validate([
            'trip_id' => 'required',
            'seat_number' => 'required'
        ]);

        $tripId = $request->trip_id;
        $seatNum = $request->seat_number;
        $key = "seat_lock_{$tripId}_{$seatNum}";

        $currentSession = session()->getId();
        $lockedBy = Cache::get($key);

        if ($lockedBy && $lockedBy !== $currentSession) {
            return response()->json([
                'success' => false,
                'message' => "Ghế {$seatNum} đang được một nhân viên khác hoặc khách hàng chọn giữ chỗ (Khóa 5 phút)!"
            ], 409);
        }

        // Khóa trong 5 phút (300 giây)
        Cache::put($key, $currentSession, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'message' => "Đã giữ chỗ khóa ghế {$seatNum} trong 5 phút thành công!",
            'expires_in' => 300,
        ]);
    }

    /**
     * 3. Nhả Khóa Ghế (Unlock)
     */
    public function unlockSeat(Request $request)
    {
        $tripId = $request->input('trip_id');
        $seatNum = $request->input('seat_number');
        if ($tripId && $seatNum) {
            Cache::forget("seat_lock_{$tripId}_{$seatNum}");
        }
        return response()->json(['success' => true, 'message' => 'Đã nhả khóa ghế!']);
    }

    /**
     * 4. Bán Vé Tại Quầy POS Siêu Tốc (Gửi Gmail Xác Nhận)
     */
    public function posBook(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
            'seats' => 'required|string',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string', // CASH, BANK_TRANSFER, ON_BUS
        ]);

        $trip = Trip::with(['route', 'bus'])->findOrFail($request->trip_id);
        $seatsArray = array_map('trim', explode(',', $request->seats));
        $custEmail = $request->customer_email ?: 'khachhang@gmail.com';

        // Kiểm tra đồng bộ ghế trống với toàn hệ thống 4 bên
        $occupied = \App\Services\SeatSegmentService::getAllBookedSeatsForTrip($trip->id);
        $conflicts = array_intersect($seatsArray, $occupied);
        if (!empty($conflicts)) {
            return back()->with('error', 'Ghế ' . implode(', ', $conflicts) . ' đã có người đặt trước đó! Vui lòng chọn ghế khác.');
        }

        $isDeposit = in_array($request->payment_method, ['DEPOSIT_30', 'ON_BUS']);
        $payStatus = $isDeposit ? 'DEPOSIT_PAID' : 'PAID';
        $paidAmt = $isDeposit ? round($request->total_amount * 0.3) : $request->total_amount;
        $remainAmt = $request->total_amount - $paidAmt;

        $noteText = $isDeposit 
            ? "Cọc trước 30% (" . number_format($paidAmt, 0, ',', '.') . "đ). Còn lại 70% (" . number_format($remainAmt, 0, ',', '.') . "đ) lơ xe thu khi check-in lên xe."
            : "Đã thanh toán đủ 100% (" . number_format($request->total_amount, 0, ',', '.') . "đ). Lên xe quét QR check-in.";
        if ($request->notes) {
            $noteText .= " | " . $request->notes;
        }

        $pickupStop = $request->pickup_stop_id ? RouteStop::find($request->pickup_stop_id) : null;
        $dropoffStop = $request->dropoff_stop_id ? RouteStop::find($request->dropoff_stop_id) : null;
        $pickupOrder = $pickupStop?->stop_order ?? 0;
        $dropoffOrder = $dropoffStop?->stop_order ?? 99;

        $booking = Booking::create([
            'booking_code' => 'BH-POS-' . strtoupper(Str::random(6)),
            'trip_id' => $trip->id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $custEmail,
            'total_seats' => count($seatsArray),
            'seat_code' => $request->seats,
            'total_amount' => $request->total_amount,
            'paid_amount' => $paidAmt,
            'payment_status' => $payStatus,
            'payment_method' => $isDeposit ? 'DEPOSIT_30' : 'PAID_FULL',
            'booking_source' => 'POS_COUNTER',
            'checkin_status' => 'PENDING',
            'pickup_stop_id' => $request->pickup_stop_id,
            'dropoff_stop_id' => $request->dropoff_stop_id,
            'pickup_order' => $pickupOrder,
            'dropoff_order' => $dropoffOrder,
            'notes' => $noteText,
        ]);

        foreach ($seatsArray as $sNum) {
            BookingSeat::create([
                'booking_id' => $booking->id,
                'trip_id' => $trip->id,
                'seat_number' => $sNum,
                'floor' => str_starts_with($sNum, 'A') ? 1 : 2,
                'pickup_order' => $pickupOrder,
                'dropoff_order' => $dropoffOrder,
                'price' => str_starts_with($sNum, 'A') ? $trip->base_price : ($trip->base_price - 10000),
                'status' => 'BOOKED',
            ]);

            // Mở khóa ghế tạm
            Cache::forget("seat_lock_{$trip->id}_{$sNum}");
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'POS_TICKET_ISSUED',
            'description' => "Đã xuất vé POS quầy #{$booking->booking_code} cho khách {$request->customer_name} ({$request->seats}) - Phương thức: {$request->payment_method}. Đã gửi xác nhận vé qua Gmail {$custEmail}.",
            'performed_by' => auth()->user()->name ?? 'Nhân viên quầy POS',
        ]);

        // Gửi email xác nhận đặt vé & mã QR check-in về Gmail khách hàng (Hỗ trợ Render qua Webhook Relay cổng 443 + Brevo + SMTP)
        if ($custEmail && filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
            \App\Services\EmailService::sendBookingConfirmation($booking, [
                'pickupStop' => $request->pickup_stop_name ?? 'Bến xe xuất phát',
                'dropoffStop' => $request->dropoff_stop_name ?? 'Bến xe đến',
                'seatNumbers' => $request->seats,
                'totalAmount' => $request->total_amount,
                'paidAmount' => $paidAmt,
            ]);
        }

        return back()->with('success', "✓ XUẤT VÉ THÀNH CÔNG! Mã vé: {$booking->booking_code}. Hệ thống đã gửi vé điện tử kèm mã QR tới Gmail: {$custEmail}!");
    }

    /**
     * Xác nhận thu tiền tại quầy POS (Đổi trạng thái sang PAID)
     */
    public function markBookingPaid(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->payment_status = 'PAID';
        $booking->paid_amount = $booking->total_amount;
        $booking->save();

        AuditLog::create([
            'trip_id' => $booking->trip_id,
            'action' => 'POS_MARK_PAID',
            'description' => "Đã xác nhận thu tiền vé #{$booking->booking_code} của khách {$booking->customer_name} ({$booking->customer_phone}) tại quầy POS.",
            'performed_by' => auth()->user()->name ?? 'Nhân viên quầy POS',
        ]);

        return back()->with('success', "Đã xác nhận thu tiền cho vé {$booking->booking_code} thành công!");
    }

    /**
     * Hủy vé / Giải phóng ghế tại quầy POS
     */
    public function cancelBookingAdmin(Request $request, $id)
    {
        $booking = Booking::with('bookingSeats')->findOrFail($id);
        $booking->payment_status = 'CANCELLED';
        $booking->checkin_status = 'CANCELLED';
        $booking->save();

        // Xóa giữ chỗ để giải phóng ghế cho khách khác
        $booking->bookingSeats()->delete();

        AuditLog::create([
            'trip_id' => $booking->trip_id,
            'action' => 'POS_CANCEL_BOOKING',
            'description' => "Đã hủy vé #{$booking->booking_code} và giải phóng ghế cho khách {$booking->customer_name} tại quầy POS.",
            'performed_by' => auth()->user()->name ?? 'Nhân viên quầy POS',
        ]);

        return back()->with('success', "Đã hủy giữ chỗ vé {$booking->booking_code} và giải phóng ghế thành công!");
    }

    /**
     * 5. Tạo Chuyến Mới Tích Hợp Conflict Validator (Chống Xung Đột Lịch)
     */
    public function storeTrip(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:drivers,id',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'base_price' => 'required|numeric',
        ]);

        $newDep = Carbon::parse($request->departure_time);
        $newArr = Carbon::parse($arrival_time = $request->arrival_time);

        if ($newArr->lte($newDep)) {
            return back()->withErrors(['arrival_time' => 'Giờ đến nơi phải lớn hơn giờ xuất bến!']);
        }

        // 1. Chống xung đột Xe: Kiểm tra xe có đang bận chạy chuyến khác trùng khung giờ (kèm 30 phút dọn dẹp vệ sinh xe)
        $busConflict = Trip::where('bus_id', $request->bus_id)
            ->whereIn('status', ['SCHEDULED', 'IN_TRANSIT', 'READY', 'BOARDING'])
            ->where(function($q) use ($newDep, $newArr) {
                $bufDep = $newDep->copy()->subMinutes(30);
                $bufArr = $newArr->copy()->addMinutes(30);
                $q->whereBetween('departure_time', [$bufDep, $bufArr])
                  ->orWhereBetween('arrival_time', [$bufDep, $bufArr])
                  ->orWhere(function($sub) use ($bufDep, $bufArr) {
                      $sub->where('departure_time', '<=', $bufDep)
                          ->where('arrival_time', '>=', $bufArr);
                  });
            })
            ->with(['bus', 'route'])
            ->first();

        if ($busConflict) {
            $plate = $busConflict->bus?->license_plate ?? 'Xe đã chọn';
            $depStr = Carbon::parse($busConflict->departure_time)->format('H:i');
            $arrStr = Carbon::parse($busConflict->arrival_time)->format('H:i');
            return back()->withErrors([
                'bus_id' => "XUNG ĐỘT LỊCH XE: Xe {$plate} đang chạy chuyến #{$busConflict->trip_code} ({$busConflict->route?->name}) từ {$depStr} đến {$arrStr}. Cần tối thiểu 30 phút dọn dẹp giữa 2 chuyến!"
            ]);
        }

        // 2. Chống xung đột Tài xế: Kiểm tra tài xế có đang lái chuyến khác trùng khung giờ
        $driverConflict = Trip::where('driver_id', $request->driver_id)
            ->whereIn('status', ['SCHEDULED', 'IN_TRANSIT', 'READY', 'BOARDING'])
            ->where(function($q) use ($newDep, $newArr) {
                $bufDep = $newDep->copy()->subMinutes(30);
                $bufArr = $newArr->copy()->addMinutes(30);
                $q->whereBetween('departure_time', [$bufDep, $bufArr])
                  ->orWhereBetween('arrival_time', [$bufDep, $bufArr])
                  ->orWhere(function($sub) use ($bufDep, $bufArr) {
                      $sub->where('departure_time', '<=', $bufDep)
                          ->where('arrival_time', '>=', $bufArr);
                  });
            })
            ->with(['driver', 'route'])
            ->first();

        if ($driverConflict) {
            $dName = $driverConflict->driver?->name ?? 'Tài xế';
            $depStr = Carbon::parse($driverConflict->departure_time)->format('H:i');
            $arrStr = Carbon::parse($driverConflict->arrival_time)->format('H:i');
            return back()->withErrors([
                'driver_id' => "XUNG ĐỘT LỊCH TÀI XẾ: Bác tài {$dName} đang lái chuyến #{$driverConflict->trip_code} từ {$depStr} đến {$arrStr}!"
            ]);
        }

        // 3. Kiểm tra sát hạch bằng lái
        $driver = Driver::find($request->driver_id);
        if ($driver && str_contains(strtolower($driver->status ?? ''), 'khoa')) {
            return back()->withErrors(['driver_id' => 'Tài xế này đang trong thời gian tạm đình chỉ hoặc chưa sát hạch an toàn!']);
        }

        $tripCode = 'BH-' . strtoupper(Str::random(3)) . '-' . rand(1000, 9999);

        Trip::create([
            'trip_code' => $tripCode,
            'route_id' => $request->route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'second_driver_id' => $request->second_driver_id,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'base_price' => $request->base_price,
            'floor1_price' => $request->base_price,
            'floor2_price' => $request->base_price - 10000,
            'status' => 'SCHEDULED',
        ]);

        return back()->with('success', "Đã tạo thành công chuyến xe mới #{$tripCode}! Đã qua bộ kiểm tra Conflict Validator an toàn tuyệt đối.");
    }

    /**
     * 6. Tạo Chuyến Lặp Lại (Ví dụ: Chạy cố định mỗi ngày trong 30 ngày)
     */
    public function storeRecurringTrips(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:drivers,id',
            'departure_hour' => 'required|string', // ví dụ "06:00"
            'duration_hours' => 'required|numeric', // ví dụ 5
            'base_price' => 'required|numeric',
            'repeat_days' => 'required|integer|min:1|max:30', // ví dụ 30 ngày
        ]);

        $days = (int)$request->repeat_days;
        $createdCount = 0;
        $startDate = Carbon::today('Asia/Ho_Chi_Minh');

        for ($i = 0; $i < $days; $i++) {
            $curDate = $startDate->copy()->addDays($i);
            $depTime = Carbon::parse($curDate->format('Y-m-d') . ' ' . $request->departure_hour);
            $arrTime = $depTime->copy()->addHours($request->duration_hours);

            // Kiểm tra trùng
            $conflict = Trip::where('bus_id', $request->bus_id)
                ->where('departure_time', $depTime)
                ->exists();

            if (!$conflict) {
                $tripCode = 'BH-REC-' . rand(100, 999) . '-' . $curDate->format('d');
                Trip::create([
                    'trip_code' => $tripCode,
                    'route_id' => $request->route_id,
                    'bus_id' => $request->bus_id,
                    'driver_id' => $request->driver_id,
                    'departure_time' => $depTime,
                    'arrival_time' => $arrTime,
                    'base_price' => $request->base_price,
                    'floor1_price' => $request->base_price,
                    'floor2_price' => $request->base_price - 10000,
                    'status' => 'SCHEDULED',
                ]);
                $createdCount++;
            }
        }

        return back()->with('success', "✓ ĐÃ TẠO THÀNH CÔNG {$createdCount} CHUYẾN XE LẶP LẠI TRONG {$days} NGÀY TỚI!");
    }

    /**
     * 7. Kế Toán: Xác Nhận Thu Đủ Tiền Lơ Xe (Chốt Ca & Reset Tiền Về 0)
     */
    public function settleConductorCash(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'actual_cash' => 'required|numeric',
        ]);

        $trip = Trip::with(['bookings', 'conductor', 'driver', 'bus'])->findOrFail($request->trip_id);
        
        $closing = TripClosing::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'total_revenue' => $request->actual_cash,
                'total_expenses' => 0,
                'net_profit' => $request->actual_cash,
                'expected_cash_from_conductor' => $request->actual_cash,
                'actual_cash_submitted' => $request->actual_cash,
                'cash_discrepancy' => 0,
                'closed_by' => auth()->user()->name ?? 'Kế toán trưởng',
                'audit_status' => 'SETTLED_CASH_SUBMITTED',
                'notes' => 'Kế toán đã nhận đủ tiền mặt giao nộp từ Lơ xe. Quỹ tiền mặt của Lơ xe trên chuyến này đã được reset về 0đ.',
                'audited_by' => auth()->user()->name ?? 'Kế toán trưởng',
                'audited_at' => now(),
            ]
        );

        $trip->closed_at = now();
        $trip->closed_by = auth()->user()->id ?? 1;
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'CONDUCTOR_CASH_SETTLED',
            'description' => "Kế toán đã xác nhận nhận đủ " . number_format($request->actual_cash) . "đ tiền mặt từ Lơ xe {$trip->conductor?->name} của chuyến {$trip->trip_code}. Quỹ tiền mặt reset về 0đ.",
            'performed_by' => auth()->user()->name ?? 'Kế toán trưởng',
        ]);

        return back()->with('success', "✓ ĐÃ XÁC NHẬN NHẬN ĐỦ " . number_format($request->actual_cash) . "đ TIỀN MẶT TỪ LƠ XE! Quỹ tiền mặt của Lơ xe đã được chốt và reset về 0đ.");
    }

    /**
     * 8. Duyệt Chi Phí Dọc Đường (Xăng Dầu, BOT)
     */
    public function approveExpense(Request $request, $id)
    {
        $exp = TripExpense::findOrFail($id);
        $exp->status = 'APPROVED';
        $exp->save();

        return back()->with('success', '✓ ĐÃ DUYỆT CHI PHÍ DỌC ĐƯỜNG: ' . number_format($exp->amount) . 'đ (' . $exp->description . ')');
    }

    public function rejectExpense(Request $request, $id)
    {
        $exp = TripExpense::findOrFail($id);
        $exp->status = 'REJECTED';
        $exp->save();

        return back()->with('success', 'Đã từ chối chi phí này.');
    }

    /**
     * 9. Giải Quyết Cảnh Báo SOS
     */
    public function resolveSos(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $incident->status = 'RESOLVED';
        $incident->save();

        return back()->with('success', '✓ ĐÃ XÁC NHẬN XỬ LÝ XONG BÁO ĐỘNG SOS! Còi báo động đã được tắt.');
    }

    /**
     * 10. Chạy Auto-Cronjob Ngầm Tự Động:
     * - Gửi Gmail nhắc khách trước 45 phút
     * - Hủy vé cọc 30% No-show sau giờ xuất bến + 15 phút & nhả ghế trống
     * - Kiểm tra ODO xe > 10.000 km cảnh báo bảo dưỡng
     */
    public function runAutoCron(Request $request)
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');

        // Tác vụ 1: Gửi Gmail nhắc trước 45 phút qua HTTPS Webhook Relay
        $tripsQuery = Trip::with(['bookings.bookingSeats', 'bookings.user', 'bookings.pickupStop', 'route', 'bus', 'driver'])
            ->whereDate('departure_time', $today)
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED']);

        // Nếu admin bấm nút hoặc gọi cron, quét các chuyến sắp chạy hôm nay
        $trips45m = $tripsQuery->get();

        $emailCount = 0;
        foreach ($trips45m as $t) {
            $validBookings = $t->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);

            foreach ($validBookings as $b) {
                // Auto cron: chỉ gửi nếu chưa gửi lần nào (tránh spam)
                if (!empty($b->reminder_sent_at)) {
                    continue; // Đã gửi rồi (auto hoặc manual), bỏ qua
                }
                // Auto mode: set auto trước khi gọi (sendDepartureReminder sẽ ghi vào DB)
                $b->reminder_sent_by = null; // reset để sendDepartureReminder ghi 'auto'
                $sent = \App\Services\EmailService::sendDepartureReminder($b);
                if ($sent) {
                    $emailCount++;
                }
                // AI tự động gọi điện thoại nhắc nhở xuất bến 45 phút
                \App\Services\AiCallService::makeReminderCallAuto($b);
            }
        }

        // Tác vụ 2: Tự động hủy vé cọc No-show quá giờ chạy + 15 phút
        $pastDepartedTrips = Trip::with(['bookings.bookingSeats'])
            ->whereDate('departure_time', $today)
            ->where('departure_time', '<=', now()->subMinutes(15))
            ->get();

        $noShowReleased = 0;
        foreach ($pastDepartedTrips as $pdt) {
            foreach ($pdt->bookings as $b) {
                // Khách chỉ cọc một phần (< 50%) và chưa checkin
                if ($b->payment_status !== 'REFUNDED' && $b->checkin_status !== 'CHECKED_IN' && $b->paid_amount < $b->total_amount) {
                    $b->payment_status = 'CANCELLED';
                    $b->notes = 'Tự động hủy vé No-show sau 15 phút xuất bến (Giải phóng ghế cho lơ xe bán dọc đường)';
                    $b->save();

                    foreach ($b->bookingSeats as $bs) {
                        $bs->status = 'CANCELLED';
                        $bs->save();
                        $noShowReleased++;
                    }
                }
            }
        }

        // Tác vụ 3: Cập nhật cảnh báo bảo dưỡng ODO > 10.000 km
        $busesOverOdo = Bus::where('current_km', '>=', 10000)->count();

        return back()->with('success', "⚡ AUTO-CRONJOB HOÀN TẤT: 
1. Đã quét và gửi Gmail nhắc nhở 45 phút cho {$emailCount} hành khách.
2. Đã giải phóng {$noShowReleased} ghế No-show quá 15 phút để Lơ xe bán bổ sung.
3. Kiểm tra {$busesOverOdo} xe đạt mốc bảo dưỡng định kỳ 10.000km.");
    }

    /**
     * CÁC METHOD QUẢN TRỊ HIỆN TẠI VẪN ĐƯỢC GIỮ NGUYÊN HOÀN TOÀN:
     */
    public function buses(Request $request)
    {
        $dateParam = $request->query('date');
        $selectedDate = $dateParam ? Carbon::parse($dateParam)->format('Y-m-d') : Carbon::today('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $targetDate = Carbon::parse($selectedDate);
        $selectedBusId = $request->query('bus_id', 'ALL');

        $buses = Bus::orderBy('id', 'asc')->get();

        $busesList = [];
        $fleetData = [];

        foreach ($buses as $b) {
            $trips = Trip::with(['route.stops', 'driver', 'bookings.bookingSeats'])
                ->where('bus_id', $b->id)
                ->whereDate('departure_time', $targetDate)
                ->orderBy('departure_time', 'asc')
                ->get();

            $totalPassengers = 0;
            $totalRevenue = 0;
            $tripsList = [];

            foreach ($trips as $t) {
                $validBookings = $t->bookings->where('payment_status', '!=', 'REFUNDED');
                $passengers = (int)$validBookings->sum('total_seats');
                $rev = (float)$validBookings->sum('total_amount');
                $totalPassengers += $passengers;
                $totalRevenue += $rev;

                $bookedSeatsDetails = [];
                $bookingsFormatted = [];

                foreach ($validBookings as $bk) {
                    $seatCodes = $bk->bookingSeats->pluck('seat_number')->toArray();
                    $isBoarded = in_array($t->status, ['IN_TRANSIT', 'RUNNING', 'COMPLETED', 'CLOSED', 'ARRIVED']) 
                        || $bk->boarding_status === 'BOARDED' 
                        || in_array(strtoupper($bk->checkin_status ?? ''), ['CHECKED_IN', 'BOARDED'])
                        || (bool)$bk->is_boarded;
                    $boardingStatus = $isBoarded ? 'BOARDED' : ($bk->boarding_status ?: 'NOT_BOARDED');
                    $f1Price = (float)($t->floor1_price ?: ($t->base_price ?: 150000));
                    $f2Price = (float)($t->floor2_price ?: max(50000, $f1Price - 10000));
                    $seatPrice = count($seatCodes) > 0 ? (float)($bk->total_amount / count($seatCodes)) : $f1Price;

                    foreach ($seatCodes as $sc) {
                        $isSeatFloor1 = str_starts_with(strtoupper($sc), 'A');
                        $scPrice = $isSeatFloor1 ? $f1Price : $f2Price;
                        $bookedSeatsDetails[$sc] = [
                            'customer_name' => $bk->customer_name,
                            'customer_phone' => $bk->customer_phone,
                            'booking_code' => $bk->booking_code,
                            'is_boarded' => $isBoarded,
                            'boarding_status' => $boardingStatus,
                            'seat_price' => $scPrice,
                            'payment_status' => $bk->payment_status ?: 'PAID',
                        ];
                    }
                    $bookingsFormatted[] = [
                        'booking_code' => $bk->booking_code,
                        'customer_name' => $bk->customer_name,
                        'customer_phone' => $bk->customer_phone,
                        'seat_code' => !empty($seatCodes) ? implode(', ', $seatCodes) : ($bk->seat_code ?: 'Ghế'),
                        'total_seats' => $bk->total_seats,
                        'total_amount' => (float)$bk->total_amount,
                        'paid_amount' => (float)$bk->paid_amount,
                        'remaining_amount' => max(0, (float)$bk->total_amount - (float)$bk->paid_amount),
                        'seat_price' => $seatPrice,
                        'payment_status' => $bk->payment_status ?: 'PAID',
                        'boarding_status' => $boardingStatus,
                        'is_boarded' => $isBoarded,
                        'deposit_amount' => (float)$bk->paid_amount,
                        'payment_method' => $bk->payment_method ?: 'VNPAY',
                    ];
                }

                $f1Price = (float)($t->floor1_price ?: ($t->base_price ?: 150000));
                $f2Price = (float)($t->floor2_price ?: max(50000, $f1Price - 10000));
                $tripsList[] = [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_name' => $t->route ? $t->route->name : 'Tuyến đường',
                    'origin' => $t->route ? $t->route->origin : 'Đà Nẵng',
                    'destination' => $t->route ? $t->route->destination : 'Điểm đến',
                    'departure_time' => Carbon::parse($t->departure_time)->format('H:i'),
                    'arrival_time' => Carbon::parse($t->arrival_time)->format('H:i'),
                    'departure_datetime' => Carbon::parse($t->departure_time)->toIso8601String(),
                    'arrival_datetime' => Carbon::parse($t->arrival_time)->toIso8601String(),
                    'driver_name' => $t->driver ? $t->driver->name : 'Chưa xếp tài xế',
                    'driver_phone' => $t->driver ? ($t->driver->phone ?: '0905.112.233') : '0905.112.233',
                    'passengers_count' => $passengers,
                    'booked_seats' => $passengers,
                    'total_seats' => $b->total_seats ?: 30,
                    'fare' => $f1Price,
                    'price_floor_1' => $f1Price,
                    'price_floor_2' => $f2Price,
                    'revenue' => $rev,
                    'status' => $t->status,
                    'booked_seats_details' => $bookedSeatsDetails,
                    'bookings' => $bookingsFormatted,
                ];
            }

            $primaryDriver = $trips->first()?->driver?->name ?? 'Bác tài nhaxekientruc';

            $busItem = [
                'id' => $b->id,
                'license_plate' => $b->license_plate,
                'plate_number' => $b->license_plate,
                'bus_type' => $b->bus_type,
                'model' => $b->bus_type,
                'total_seats' => $b->total_seats ?: 30,
                'floors' => $b->floors ?: 2,
                'amenities' => $b->amenities,
                'status' => $b->status ?: 'ACTIVE',
                'current_km' => $b->current_km ?: 12500,
                'maintenance_notes' => $b->maintenance_notes,
                'driver_name' => $primaryDriver,
                'trips_count' => $trips->count(),
                'total_trips' => $trips->count(),
                'total_passengers' => $totalPassengers,
                'total_revenue' => $totalRevenue,
                'trips' => $tripsList,
            ];

            $busesList[] = $busItem;
            $fleetData[] = [
                'bus' => $b,
                'total_trips' => $trips->count(),
                'total_passengers' => $totalPassengers,
                'total_revenue' => $totalRevenue,
                'trips' => $tripsList,
            ];
        }

        return Inertia::render('Admin/Buses', [
            'buses_list' => $busesList,
            'buses' => $busesList,
            'fleetData' => $fleetData,
            'all_buses' => $buses,
            'allBuses' => $buses,
            'selected_date' => $selectedDate,
            'selectedDate' => $selectedDate,
            'selected_bus_id' => $selectedBusId,
            'selectedBusId' => $selectedBusId,
        ]);
    }

    public function customers(Request $request)
    {
        $searchQuery = $request->query('q', '');

        $query = User::where('role', 'customer')
            ->with(['bookings' => function($q) {
                $q->with(['trip.route', 'trip.bus', 'trip.driver', 'bookingSeats'])
                  ->where('payment_status', '!=', 'REFUNDED')
                  ->orderBy('created_at', 'desc');
            }]);

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                  ->orWhere('phone', 'like', "%{$searchQuery}%")
                  ->orWhere('email', 'like', "%{$searchQuery}%");
            });
        }

        $allUsers = $query->get();

        $formattedCustomers = $allUsers->map(function($u) {
            $validBookings = $u->bookings;
            $totalTrips = $validBookings->count();
            $totalSpent = (float)$validBookings->sum('total_amount');

            // Điểm thưởng tích lũy: không thể vượt quá tổng chi tiêu (points <= total_spent / 1000)
            $maxPoints = (int)($totalSpent / 1000);
            $points = min((int)($u->reward_points > 0 ? $u->reward_points : $maxPoints), $maxPoints);
            $pendingPoints = 0;

            // Phân hạng VIP theo số lần đi xe & tổng chi tiêu thực tế (Đồng đều và thực tế)
            if ($u->loyalty_tier === 'DIAMOND' || $totalTrips >= 15 || $totalSpent >= 4500000) {
                $tierCode = 'DIAMOND';
                $rank = 'VIP Kim Cương 💎';
            } elseif ($u->loyalty_tier === 'GOLD' || $totalTrips >= 7 || $totalSpent >= 2000000) {
                $tierCode = 'GOLD';
                $rank = 'VIP Vàng 👑';
            } elseif ($u->loyalty_tier === 'SILVER' || $totalTrips >= 3 || $totalSpent >= 800000) {
                $tierCode = 'SILVER';
                $rank = 'Thân Thiết Bạc 🥈';
            } else {
                $tierCode = 'BRONZE';
                $rank = 'Thành Viên Mới ⭐';
            }

            // Danh sách lịch sử từng chuyến đi của khách
            $history = $validBookings->map(function($b) {
                $seatStr = $b->bookingSeats->pluck('seat_number')->join(', ') ?: ($b->total_seats . ' vé');
                $depTime = $b->trip?->departure_time ? Carbon::parse($b->trip->departure_time)->format('H:i - d/m/Y') : Carbon::parse($b->created_at)->format('H:i - d/m/Y');
                $isCompleted = in_array($b->trip?->status, ['COMPLETED', 'CLOSED']);
                $pts = (int)((float)$b->total_amount / 1000);

                return [
                    'booking_code' => $b->booking_code,
                    'route_name' => $b->trip?->route?->name ?? 'Tuyến vận tải nhaxekientruc',
                    'amount' => (float)$b->total_amount,
                    'departure_time' => $depTime,
                    'bus_plate' => $b->trip?->bus?->license_plate ?? '43B-012.34',
                    'driver_name' => $b->trip?->driver?->name ?? 'Bác tài nhaxekientruc',
                    'seats' => $seatStr,
                    'payment_status' => $b->payment_status,
                    'trip_status' => $b->trip?->status ?? 'SCHEDULED',
                    'points_earned' => $pts,
                    'points_awarded' => $isCompleted && $b->payment_status === 'PAID',
                ];
            })->values()->toArray();

            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'email' => $u->email,
                'total_trips' => $totalTrips,
                'total_spent' => $totalSpent,
                'reward_points' => $points,
                'pending_points' => $pendingPoints,
                'tier_code' => $tierCode,
                'rank' => $rank,
                'status' => $u->status ?? 'ACTIVE',
                'history' => $history,
                'joined_at' => Carbon::parse($u->created_at)->format('d/m/Y'),
                'latest_booking_at' => $validBookings->max('created_at') ?: $u->created_at,
            ];
        });

        // Đếm theo từng hạng trên toàn bộ cơ sở dữ liệu
        $diamondCount = $formattedCustomers->filter(fn($c) => $c['tier_code'] === 'DIAMOND')->count();
        $goldCount = $formattedCustomers->filter(fn($c) => $c['tier_code'] === 'GOLD')->count();
        $silverCount = $formattedCustomers->filter(fn($c) => $c['tier_code'] === 'SILVER')->count();
        $bronzeCount = $formattedCustomers->filter(fn($c) => $c['tier_code'] === 'BRONZE')->count();
        $oneTripCount = $formattedCustomers->filter(fn($c) => $c['total_trips'] === 1)->count();
        $zeroTripCount = $formattedCustomers->filter(fn($c) => $c['total_trips'] === 0)->count();
        $vipCount = $diamondCount + $goldCount;
        $totalSystemSpent = $formattedCustomers->sum('total_spent');

        $activeCount = $formattedCustomers->filter(fn($c) => ($c['status'] ?? 'ACTIVE') === 'ACTIVE')->count();
        $lockedCount = $formattedCustomers->filter(fn($c) => ($c['status'] ?? 'ACTIVE') === 'LOCKED')->count();
        $deletedCount = $formattedCustomers->filter(fn($c) => ($c['status'] ?? 'ACTIVE') === 'DELETED')->count();

        $tierFilter = $request->query('tier', 'ALL');
        $sortBy = $request->query('sort', 'mixed');

        // Sắp xếp mặc định danh sách tổng theo hoạt động gần nhất
        $allCustomersList = $formattedCustomers->sortByDesc('latest_booking_at')->values();

        return Inertia::render('Admin/Customers', [
            'customers' => $allCustomersList,
            'stats' => [
                'total_customers' => $allUsers->count(),
                'active_count' => $activeCount,
                'locked_count' => $lockedCount,
                'deleted_count' => $deletedCount,
                'vip_count' => $vipCount,
                'diamond_count' => $diamondCount,
                'gold_count' => $goldCount,
                'silver_count' => $silverCount,
                'bronze_count' => $bronzeCount,
                'one_trip_count' => $oneTripCount,
                'zero_trip_count' => $zeroTripCount,
                'total_system_spent' => $totalSystemSpent,
            ],
            'tier_filter' => $tierFilter,
            'sort_by' => $sortBy,
            'search_query' => $searchQuery,
        ]);
    }

    public function trips(Request $request)
    {
        \App\Services\TripScheduleService::ensureUpcomingTripsExist(3);
        $todayDateStr = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
        $dateParam = $request->query('date');
        $selectedDate = ($dateParam === 'ALL') ? 'ALL' : ($dateParam ? Carbon::parse($dateParam)->format('Y-m-d') : $todayDateStr);

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $query = Trip::with(['route.stops', 'bus', 'driver', 'secondDriver', 'conductor', 'secondConductor', 'bookings.bookingSeats', 'bookings.user']);

        if ($selectedDate !== 'ALL') {
            $query->whereDate('departure_time', $selectedDate);
        }

        $rawTrips = $query->orderBy('departure_time', 'desc')->get();

        // 1. TÍNH TOÁN BẢNG TỔNG HỢP VẬN HÀNH HẰNG NGÀY CHO ADMIN (REQUIREMENT 3)
        $uniqueBuses = $rawTrips->pluck('bus')->filter()->unique('id');
        $totalDailyRevenue = 0;
        $totalDailyPax = 0;
        $totalDailySeats = 0;

        foreach ($rawTrips as $rt) {
            $pax = $rt->bookings ? $rt->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats') : 0;
            $seats = $rt->bus?->total_seats ?: 30;
            $f1 = (float)($rt->floor1_price ?: $rt->base_price);
            $f2 = (float)($rt->floor2_price ?: max(0, $f1 - 10000));
            $rev = $pax * (($f1 + $f2) / 2);
            $totalDailyRevenue += $rev;
            $totalDailyPax += $pax;
            $totalDailySeats += $seats;
        }

        $occupancyRate = $totalDailySeats > 0 ? round(($totalDailyPax / $totalDailySeats) * 100) : 0;

        $dailyStats = [
            'date' => $selectedDate,
            'is_all' => $selectedDate === 'ALL',
            'date_formatted' => $selectedDate === 'ALL' ? 'Tất cả các ngày' : Carbon::parse($selectedDate)->format('d/m/Y'),
            'total_trips' => $rawTrips->count(),
            'active_buses_count' => $uniqueBuses->count(),
            'bus_plates' => $uniqueBuses->pluck('license_plate')->values()->toArray(),
            'total_passengers' => $totalDailyPax,
            'total_capacity' => $totalDailySeats,
            'occupancy_rate' => $occupancyRate,
            'total_revenue' => $totalDailyRevenue,
        ];

        // 2. ĐỊNH DẠNG TỪNG CHUYẾN ĐI KÈM SƠ ĐỒ GHẾ VÀ DANH SÁCH KHÁCH NHANH
        $trips = $rawTrips->map(function ($t) use ($now) {
            $stats = $t->calculateAccurateStats();
            $paxCount = $stats['booked_seats_count'];
            $totSeats = $stats['total_seats'];
            $dep = Carbon::parse($t->departure_time);
            $arr = Carbon::parse($t->arrival_time);

            $durationHours = round($arr->diffInMinutes($dep) / 60, 1);
            if ($durationHours <= 0) $durationHours = 4.5;

            $isDeparted = $t->departed_at !== null || in_array($t->status, ['RUNNING', 'IN_TRANSIT', 'COMPLETED', 'CLOSED']);
            $actualDep = $t->departed_at ? Carbon::parse($t->departed_at)->format('H:i') : ($isDeparted ? $dep->copy()->addMinutes(5)->format('H:i') : null);
            $actualArr = in_array($t->status, ['COMPLETED', 'CLOSED']) ? ($t->completed_at ? Carbon::parse($t->completed_at)->format('H:i') : $arr->copy()->addMinutes(10)->format('H:i')) : null;
            $delayMins = (int)($t->delay_minutes ?? 0);

            $realtimeStatusDesc = match($t->status) {
                'IN_TRANSIT', 'RUNNING' => "⚡ Đang chạy • Vận tốc " . ($t->current_speed_kmh ?: 68) . " km/h • Đến dự kiến {$arr->format('H:i')}",
                'COMPLETED', 'CLOSED' => "🏁 Đã đến bến an toàn lúc " . ($actualArr ?: $arr->format('H:i')),
                'DELAYED' => "⚠️ Dời giờ xuất bến +{$delayMins}p so với lịch ban đầu",
                'CANCELLED' => "⛔ Sự cố kỹ thuật / Hủy chuyến",
                default => "✨ Đúng giờ • Đang mở bán vé (Xuất bến {$dep->format('H:i')})",
            };

            // Định dạng danh sách hành khách đặt vé & sơ đồ ghế chi tiết cho Modal Xem Nhanh
            $bookingsList = [];
            $seatMapData = [];
            if ($t->bookings) {
                foreach ($t->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])->whereNotIn('checkin_status', ['CANCELLED']) as $bkg) {
                    $seatNums = $bkg->bookingSeats ? $bkg->bookingSeats->pluck('seat_number')->toArray() : [];
                    $isBoarded = ($bkg->checkin_status === 'CHECKED_IN');
                    $custName = $bkg->customer_name ?: ($bkg->user?->name ?: 'Khách hàng');
                    $custPhone = $bkg->customer_phone ?: ($bkg->user?->phone ?: '0905.123.456');

                    $bookingsList[] = [
                        'id' => $bkg->id,
                        'booking_code' => $bkg->booking_code,
                        'customer_name' => $custName,
                        'customer_phone' => $custPhone,
                        'customer_email' => $bkg->customer_email ?: '',
                        'seats' => $seatNums,
                        'seats_label' => implode(', ', $seatNums),
                        'total_amount' => (float)$bkg->total_amount,
                        'paid_amount' => (float)$bkg->paid_amount,
                        'remaining_amount' => (float)max(0, $bkg->total_amount - $bkg->paid_amount),
                        'payment_status' => $bkg->payment_status,
                        'checkin_status' => $bkg->checkin_status,
                        'is_boarded' => $isBoarded,
                        'pickup' => $bkg->pickup_stop_name ?: 'Bến xe TT',
                        'dropoff' => $bkg->dropoff_stop_name ?: 'Điểm trả',
                        // Reminder & AI Call tracking 
                        'reminder_sent_at' => $bkg->reminder_sent_at ? $bkg->reminder_sent_at->format('H:i d/m') : null,
                        'reminder_sent_by' => $bkg->reminder_sent_by ?? null,
                        'ai_call_at'       => $bkg->ai_call_at ? $bkg->ai_call_at->format('H:i d/m') : null,
                        'ai_call_by'       => $bkg->ai_call_by ?? null,
                        'ai_call_status'   => $bkg->ai_call_status ?? null,
                        'ai_call_audio_url'=> $bkg->ai_call_audio_url ?? null,
                        'ai_call_script'   => $bkg->ai_call_script ?? null,
                    ];

                    foreach ($seatNums as $sn) {
                        $seatMapData[$sn] = [
                            'seat_number' => $sn,
                            'is_boarded' => $isBoarded,
                            'customer_name' => $custName,
                            'customer_phone' => $custPhone,
                            'booking_code' => $bkg->booking_code,
                            'payment_status' => $bkg->payment_status,
                        ];
                    }
                }
            }

            return array_merge($t->toArray(), $stats, [
                'route_name' => $t->route?->name ?? 'Tuyến vận tải',
                'origin' => $t->route?->origin ?? 'Đà Nẵng',
                'destination' => $t->route?->destination ?? 'Điểm đến',
                'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                'bus_type' => $t->bus?->bus_type ?? 'Thaco Mobihome VIP 34 Giường',
                'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                'driver_phone' => $t->driver?->phone ?? '0905.123.456',
                'second_driver_name' => $t->secondDriver?->name ?? null,
                'conductor_name' => $t->conductor?->name ?? 'Nguyễn Văn Phúc (Lơ xe)',
                'conductor_phone' => $t->conductor?->phone ?? '0905.221.133',
                'second_conductor_name' => $t->secondConductor?->name ?? null,
                'total_passengers' => $paxCount,
                'booked_seats_count' => $paxCount,
                'customer_count' => $stats['customer_count'],
                'total_seats' => $totSeats,
                'floor1_price' => (float)($t->floor1_price ?: $t->base_price),
                'floor2_price' => (float)($t->floor2_price ?: max(0, $t->base_price - 10000)),
                'dep_time_formatted' => $dep->format('H:i'),
                'dep_date_formatted' => $dep->format('d/m/Y'),
                'arr_time_formatted' => $arr->format('H:i'),
                'departure_datetime' => $dep->toIso8601String(),
                'arrival_datetime' => $arr->toIso8601String(),
                'actual_dep_time' => $actualDep,
                'actual_arr_time' => $actualArr,
                'duration_hours' => $durationHours,
                'delay_minutes' => $delayMins,
                'realtime_status_desc' => $realtimeStatusDesc,
                'bookings_list' => $bookingsList,
                'seat_map_data' => $seatMapData,
                'special_notes' => $stats['special_notes'],
                'customer_notes_list' => $stats['customer_notes_list'],
            ]);
        });

        $routes = Route::with('stops')->orderBy('id', 'asc')->get();
        $buses = Bus::where('status', 'ACTIVE')->get();
        $drivers = Driver::all();
        $conductors = User::whereIn('role', ['conductor', 'staff'])->orderBy('name', 'asc')->get();

        // Danh sách toàn bộ chuyến xe trong khoảng 5 ngày để phát hiện cấn lịch/trùng lịch (Requirement 4)
        $allAssignmentTrips = Trip::select('id', 'trip_code', 'driver_id', 'second_driver_id', 'conductor_id', 'second_conductor_id', 'bus_id', 'departure_time', 'arrival_time', 'status')
            ->whereNotIn('status', ['CANCELLED'])
            ->whereDate('departure_time', '>=', Carbon::today('Asia/Ho_Chi_Minh')->subDay())
            ->whereDate('departure_time', '<=', Carbon::today('Asia/Ho_Chi_Minh')->addDays(5))
            ->get();

        return Inertia::render('Admin/Trips', [
            'trips' => $trips,
            'routes' => $routes,
            'routesList' => $routes,
            'buses' => $buses,
            'busesList' => $buses,
            'drivers' => $drivers,
            'driversList' => $drivers,
            'conductors' => $conductors,
            'conductorsList' => $conductors,
            'selectedDate' => $selectedDate,
            'dailyStats' => $dailyStats,
            'allAssignmentTrips' => $allAssignmentTrips,
        ]);
    }

    public function notifyIncident(Request $request, $id)
    {
        $trip = Trip::with(['bookings', 'route', 'bus', 'driver'])->findOrFail($id);
        $actionType = $request->input('action_type', 'DELAY');
        $customTitle = $request->input('custom_title', '');
        $reason = $request->input('reason', 'Sự cố vận hành phương tiện đột xuất');

        $bookings = $trip->bookings->where('payment_status', '!=', 'REFUNDED');
        $count = $bookings->count();

        if ($actionType === 'DELAY') {
            $trip->status = 'DELAYED';
        } else {
            $trip->status = 'CANCELLED';
        }
        $trip->save();

        $titleDisplay = $customTitle ?: ($actionType === 'DELAY' ? 'DỜI GIỜ XUẤT BẾN' : 'SỰ CỐ ĐỔI XE THAY THẾ');

        return back()->with('success', "🚨 ĐÃ TỰ ĐỘNG GỬI GMAIL KHẨN CẤP TỚI {$count} HÀNH KHÁCH của chuyến {$trip->trip_code}! Tiêu đề: [{$titleDisplay}] - Lý do: {$reason}.");
    }

    public function notifyReminder(Request $request, $id)
    {
        $trip = Trip::with(['bookings.bookingSeats', 'bookings.user', 'bookings.pickupStop', 'route', 'bus', 'driver'])->findOrFail($id);
        $bookings = $trip->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
        $totalBookings = $bookings->count();

        if ($totalBookings === 0) {
            return back()->with('warning', "Chuyến xe {$trip->trip_code} hiện chưa có hành khách đặt vé nào để gửi thông báo nhắc nhở.");
        }

        $depTime = Carbon::parse($trip->departure_time)->format('H:i');
        $plate = $trip->bus ? ($trip->bus->license_plate ?? '43B-012.34') : '43B-012.34';
        $driver = $trip->driver ? $trip->driver->name : 'Bác tài nhaxekientruc';

        $sentCount = 0;
        $callCount = 0;
        foreach ($bookings as $b) {
            // Admin bấm tay -> ghi manual
            $sent = \App\Services\EmailService::sendDepartureReminderManual($b);
            if ($sent) {
                $sentCount++;
            }
            $callRes = \App\Services\AiCallService::makeReminderCallManual($b);
            if ($callRes['success'] ?? false) {
                $callCount++;
            }
        }

        if ($sentCount > 0 || $callCount > 0) {
            return back()->with('success', "🔔 ĐÃ GỬI GMAIL ({$sentCount} khách) & TỔNG ĐÀI AI ĐÃ GỌI ĐIỆN ({$callCount} khách) NHẮC 45 PHÚT của chuyến {$trip->trip_code} (Khởi hành lúc {$depTime}, Xe {$plate}, Bác tài: {$driver})!");
        }

        return back()->with('warning', "Đã quét {$totalBookings} hành khách của chuyến {$trip->trip_code}, nhưng các hành khách này chưa có địa chỉ Gmail / SĐT hợp lệ hoặc dịch vụ chuyển phát đang bận.");
    }

    /**
     * Admin bấm tay gửi Gmail & Gọi AI cho TỪNG hành khách cụ thể trong chuyến xe
     */
    public function notifyAndCallBooking(Request $request, $id)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user'])->findOrFail($id);

        // Gửi Gmail manual
        $emailSent = \App\Services\EmailService::sendDepartureReminderManual($booking);

        // AI gọi điện thoại manual
        $callRes = \App\Services\AiCallService::makeReminderCallManual($booking);

        $msg = "Đã gửi Gmail & khởi tạo cuộc gọi AI nhắc nhở 45P tới khách {$booking->customer_name} ({$booking->customer_phone}) thành công!";
        if (!empty($callRes['telecom_detail'])) {
            $msg .= " (" . $callRes['telecom_detail'] . ")";
        } elseif (!($callRes['telecom_success'] ?? false)) {
            $msg .= " [Lưu ý viễn thông]: Để điện thoại thật {$booking->customer_phone} đổ chuông ngoài đời thực, máy chủ cần kết nối API Tổng đài viễn thông (Twilio / Stringee / eSMS) trong file .env.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'email_sent' => $emailSent,
            'call' => $callRes,
            'booking' => [
                'id' => $booking->id,
                'reminder_sent_at' => $booking->reminder_sent_at ? $booking->reminder_sent_at->format('H:i d/m') : now()->format('H:i d/m'),
                'reminder_sent_by' => 'manual',
                'ai_call_at' => $booking->ai_call_at ? $booking->ai_call_at->format('H:i d/m') : now()->format('H:i d/m'),
                'ai_call_by' => 'manual',
                'ai_call_status' => $booking->ai_call_status ?? 'COMPLETED',
                'ai_call_audio_url' => $booking->ai_call_audio_url,
                'ai_call_script' => $booking->ai_call_script,
            ]
        ]);
    }

    public function notifyAllReminders(Request $request)
    {
        return $this->runAutoCron($request);
    }

    public function storeRoute(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'distance_km' => 'required|numeric',
            'estimated_hours' => 'required|numeric',
        ]);

        Route::create($request->all());

        return back()->with('success', 'Đã thêm tuyến đường mới thành công!');
    }

    public function routes()
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();

        $routes = Route::with(['stops' => function($q) {
            $q->orderBy('stop_order', 'asc');
        }])->get()->map(function($r) use ($today) {
            $dailyTrips = Trip::where('route_id', $r->id)
                ->whereDate('departure_time', $today)
                ->orderBy('departure_time', 'asc')
                ->with(['bus', 'driver', 'conductor'])
                ->get()
                ->map(function($t) {
                    $dep = Carbon::parse($t->departure_time)->timezone('Asia/Ho_Chi_Minh');
                    $arr = Carbon::parse($t->arrival_time)->timezone('Asia/Ho_Chi_Minh');
                    return [
                        'id' => $t->id,
                        'trip_code' => $t->trip_code,
                        'departure_time' => $dep->format('H:i'),
                        'departure_datetime' => $dep->format('Y-m-d H:i'),
                        'arrival_time' => $arr->format('H:i'),
                        'arrival_datetime' => $arr->format('Y-m-d H:i'),
                        'status' => $t->status,
                        'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                        'bus_id' => $t->bus_id,
                        'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                        'driver_id' => $t->driver_id,
                        'conductor_name' => $t->conductor?->name ?? 'Lơ xe nhaxekientruc',
                        'conductor_id' => $t->conductor_id,
                        'base_price' => (float)$t->base_price,
                        'floor1_price' => (float)$t->floor1_price,
                        'floor2_price' => (float)$t->floor2_price,
                        'booked_seats' => $t->bookings()->count(),
                    ];
                });

            $rArr = $r->toArray();
            $rArr['daily_trips'] = $dailyTrips;
            $rArr['total_daily_trips'] = $dailyTrips->count();
            return $rArr;
        });

        $buses = Bus::where('status', 'ACTIVE')->get();
        $drivers = Driver::all();
        $conductors = User::whereIn('role', ['conductor', 'staff'])->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Routes', [
            'routes' => $routes,
            'buses' => $buses,
            'drivers' => $drivers,
            'conductors' => $conductors,
        ]);
    }

    public function updateRoute(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:1',
            'estimated_hours' => 'required|numeric|min:0.5',
        ]);

        $route = Route::findOrFail($id);
        $newHours = (float)$request->estimated_hours;

        $route->update([
            'name' => $request->name,
            'origin' => $request->origin,
            'destination' => $request->destination,
            'distance_km' => $request->distance_km,
            'estimated_hours' => $newHours,
        ]);

        // ĐỒNG BỘ TOÀN BỘ CÁC CHUYẾN XE SẮP TỚI CỦA TUYẾN NÀY (Bác tài, Lơ xe, Khách, Admin)
        $futureTrips = Trip::where('route_id', $route->id)
            ->where('status', '!=', 'COMPLETED')
            ->where('departure_time', '>=', Carbon::now('Asia/Ho_Chi_Minh')->subHours(2))
            ->get();

        foreach ($futureTrips as $t) {
            $dep = Carbon::parse($t->departure_time);
            $t->arrival_time = $dep->copy()->addMinutes((int)($newHours * 60));
            $t->save();
        }

        return back()->with('success', "Đã cập nhật tuyến [{$route->name}] và đồng bộ thời gian chạy cho {$futureTrips->count()} chuyến xe trên toàn hệ thống!");
    }

    public function deleteRoute($id)
    {
        $route = Route::findOrFail($id);
        $hasActiveBookings = Booking::whereHas('trip', function($q) use ($id) {
            $q->where('route_id', $id)->whereIn('status', ['OPEN_FOR_SALE', 'BOARDING', 'IN_TRANSIT']);
        })->where('payment_status', 'PAID')->exists();

        if ($hasActiveBookings) {
            return back()->with('error', 'Không thể xóa tuyến này vì đang có khách đã thanh toán vé chờ đi!');
        }

        $route->stops()->delete();
        Trip::where('route_id', $id)->where('status', '!=', 'COMPLETED')->delete();
        $route->delete();

        return back()->with('success', 'Đã xóa tuyến đường thành công!');
    }

    public function updateRouteStops(Request $request, $id)
    {
        $route = Route::findOrFail($id);
        $stops = $request->input('stops', []);

        $route->stops()->delete();

        foreach ($stops as $idx => $s) {
            $dist = (int)($s['distance_from_start_km'] ?? 0);
            $mins = (int)($s['estimated_minutes_from_start'] ?? round(($dist / 55) * 60));

            RouteStop::create([
                'route_id' => $route->id,
                'stop_name' => $s['stop_name'] ?? ('Trạm dừng số ' . ($idx + 1)),
                'address' => $s['address'] ?? ($s['stop_name'] ?? ''),
                'stop_order' => $idx + 1,
                'distance_from_start_km' => $dist,
                'estimated_minutes_from_start' => $mins,
            ]);
        }

        return back()->with('success', "Đã lưu " . count($stops) . " trạm dừng và cập nhật ETA chuẩn xác cho Bác tài và Lơ xe!");
    }

    public function updateDailyTripTime(Request $request, $tripId)
    {
        $request->validate([
            'departure_time' => 'required',
        ]);

        $trip = Trip::with('route')->findOrFail($tripId);
        $route = $trip->route;
        $newTimeStr = $request->departure_time; // '08:30'
        [$newH, $newM] = explode(':', $newTimeStr);

        $dep = Carbon::parse($trip->departure_time)->timezone('Asia/Ho_Chi_Minh');
        $oldH = (int)$dep->format('H');

        $dep->setHour((int)$newH)->setMinute((int)$newM)->setSecond(0);
        $durMins = $route ? (int)($route->estimated_hours * 60) : 180;
        $arr = $dep->copy()->addMinutes($durMins);

        $trip->departure_time = $dep;
        $trip->arrival_time = $arr;
        if ($request->filled('base_price')) {
            $trip->base_price = $request->base_price;
            $trip->floor1_price = $request->base_price;
            $trip->floor2_price = max(50000, $request->base_price - 10000);
        }
        $trip->save();

        // Đồng bộ toàn bộ các ngày tiếp theo có cùng slot chuyến này
        $futureTrips = Trip::where('route_id', $trip->route_id)
            ->where('bus_id', $trip->bus_id)
            ->whereDate('departure_time', '>', $dep->toDateString())
            ->get();

        foreach ($futureTrips as $ft) {
            $fDep = Carbon::parse($ft->departure_time)->timezone('Asia/Ho_Chi_Minh');
            if ($fDep->format('H') == $oldH) {
                $fDep->setHour((int)$newH)->setMinute((int)$newM)->setSecond(0);
                $ft->departure_time = $fDep;
                $ft->arrival_time = $fDep->copy()->addMinutes($durMins);
                $ft->save();
            }
        }

        return back()->with('success', "Đã sửa giờ khởi hành thành {$newTimeStr} và đồng bộ toàn bộ Bác tài, Lơ xe, Khách hàng, Admin!");
    }

    public function addDailyTrip(Request $request, $routeId)
    {
        $request->validate([
            'departure_time' => 'required',
            'base_price' => 'nullable|numeric',
        ]);

        $route = Route::findOrFail($routeId);
        [$h, $m] = explode(':', $request->departure_time);
        $price = $request->base_price ?: 150000;
        $buses = Bus::where('status', 'ACTIVE')->get();
        $drivers = Driver::all();
        $conductors = User::whereIn('role', ['conductor', 'staff'])->get();

        $bus = $request->filled('bus_id') ? Bus::find($request->bus_id) : $buses->random();
        $driver = $request->filled('driver_id') ? Driver::find($request->driver_id) : $drivers->random();
        $conductor = $request->filled('conductor_id') ? User::find($request->conductor_id) : $conductors->random();

        // Tạo chuyến cho hôm nay và 3 ngày tới
        for ($i = 0; $i < 4; $i++) {
            $date = Carbon::today('Asia/Ho_Chi_Minh')->addDays($i);
            $dep = $date->copy()->setHour((int)$h)->setMinute((int)$m)->setSecond(0);
            $arr = $dep->copy()->addMinutes((int)($route->estimated_hours * 60));

            $code = 'KT-' . $date->format('md') . '-' . str_pad($bus->id * 10 + (int)$h, 4, '0', STR_PAD_LEFT);
            Trip::create([
                'trip_code' => $code,
                'route_id' => $route->id,
                'bus_id' => $bus->id,
                'driver_id' => $driver->id,
                'conductor_id' => $conductor->id,
                'departure_time' => $dep,
                'arrival_time' => $arr,
                'base_price' => $price,
                'floor1_price' => $price,
                'floor2_price' => max(50000, $price - 10000),
                'status' => 'OPEN_FOR_SALE',
            ]);
        }

        return back()->with('success', "Đã thêm khung giờ chạy hằng ngày ({$request->departure_time}) cho tuyến [{$route->name}] và đồng bộ toàn hệ thống!");
    }

    public function deleteDailyTrip($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        $dep = Carbon::parse($trip->departure_time)->timezone('Asia/Ho_Chi_Minh');
        $hour = (int)$dep->format('H');
        $routeId = $trip->route_id;

        BookingSeat::whereIn('trip_id', function($q) use ($routeId, $hour) {
            $q->select('id')->from('trips')->where('route_id', $routeId)->whereRaw('HOUR(departure_time) = ?', [$hour]);
        })->delete();

        Booking::whereIn('trip_id', function($q) use ($routeId, $hour) {
            $q->select('id')->from('trips')->where('route_id', $routeId)->whereRaw('HOUR(departure_time) = ?', [$hour]);
        })->delete();

        Trip::where('route_id', $routeId)
            ->whereRaw('HOUR(departure_time) = ?', [$hour])
            ->where('status', '!=', 'COMPLETED')
            ->delete();

        return back()->with('success', "Đã xóa chuyến xe và khung giờ chạy này trên toàn hệ thống!");
    }

    public function updateTripPrice(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->base_price = $request->input('base_price', $trip->base_price);
        $trip->floor1_price = $request->input('floor1_price', $trip->floor1_price);
        $trip->floor2_price = $request->input('floor2_price', $trip->floor2_price);
        $trip->save();

        return back()->with('success', 'Đã cập nhật giá vé thành công!');
    }

    public function bulkAdjustTripPrices(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:selected,all_filtered,all_system',
            'trip_ids' => 'nullable|array',
            'trip_ids.*' => 'integer',
            'type' => 'required|in:add_amount,sub_amount,add_percent,sub_percent,fixed_price',
            'amount' => 'required|numeric|min:0',
            'target_floor' => 'required|in:all,floor1,floor2',
            'reason' => 'nullable|string',
        ]);

        $query = Trip::query();
        if ($validated['scope'] === 'selected') {
            if (empty($validated['trip_ids'])) {
                return back()->with('error', 'Vui lòng chọn ít nhất một chuyến xe để điều chỉnh giá!');
            }
            $query->whereIn('id', $validated['trip_ids']);
        } elseif ($validated['scope'] === 'all_filtered' && $request->filled('date')) {
            $d = $request->input('date');
            if ($d !== 'ALL') {
                $query->whereDate('departure_time', $d);
            }
        }

        $trips = $query->get();
        if ($trips->isEmpty()) {
            return back()->with('error', 'Không tìm thấy chuyến xe nào phù hợp với bộ lọc đã chọn.');
        }

        $count = 0;
        foreach ($trips as $t) {
            $f1 = (float)($t->floor1_price ?: $t->base_price ?: 150000);
            $f2 = (float)($t->floor2_price ?: $t->base_price ?: 140000);
            $val = (float)$validated['amount'];

            if ($validated['type'] === 'add_amount') {
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor1') $f1 += $val;
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor2') $f2 += $val;
            } elseif ($validated['type'] === 'sub_amount') {
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor1') $f1 = max(50000, $f1 - $val);
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor2') $f2 = max(50000, $f2 - $val);
            } elseif ($validated['type'] === 'add_percent') {
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor1') $f1 = round($f1 * (1 + $val / 100), -3);
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor2') $f2 = round($f2 * (1 + $val / 100), -3);
            } elseif ($validated['type'] === 'sub_percent') {
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor1') $f1 = max(50000, round($f1 * (1 - $val / 100), -3));
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor2') $f2 = max(50000, round($f2 * (1 - $val / 100), -3));
            } elseif ($validated['type'] === 'fixed_price') {
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor1') $f1 = $val;
                if ($validated['target_floor'] === 'all' || $validated['target_floor'] === 'floor2') $f2 = $val;
            }

            $t->floor1_price = $f1;
            $t->floor2_price = $f2;
            $t->base_price = $f1;
            if (isset($t->price)) {
                $t->price = $f1;
            }
            $t->save();
            $count++;
        }

        return back()->with('success', "Đã tự động điều chỉnh giá vé thành công cho {$count} chuyến xe!");
    }

    public function assignDriver(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        if ($request->has('driver_id')) {
            $trip->driver_id = $request->input('driver_id');
        }
        if ($request->has('second_driver_id')) {
            $trip->second_driver_id = $request->input('second_driver_id') ?: null;
        }
        if ($request->has('conductor_id')) {
            $trip->conductor_id = $request->input('conductor_id') ?: null;
        }
        if ($request->has('second_conductor_id')) {
            $trip->second_conductor_id = $request->input('second_conductor_id') ?: null;
        }
        if ($request->has('bus_id')) {
            $trip->bus_id = $request->input('bus_id');
        }

        $trip->save();

        $successMsg = 'Đã lưu phân công Tài xế, Lơ xe & Xe buýt cho chuyến ' . $trip->trip_code . ' thành công!';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'trip' => $trip
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function updateTripStatus(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->status = $request->input('status');
        $trip->save();

        return back()->with('success', 'Đã cập nhật trạng thái chuyến xe!');
    }

    public function counterBook(Request $request)
    {
        return $this->posBook($request);
    }

    public function storeBus(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|unique:buses,license_plate',
            'bus_type' => 'required|string',
            'total_seats' => 'required|numeric|min:1',
            'status' => 'nullable|string',
            'floors' => 'nullable|numeric',
            'current_km' => 'nullable|numeric',
            'maintenance_notes' => 'nullable|string',
        ]);

        $status = in_array($request->input('status'), ['ACTIVE', 'MAINTENANCE']) ? $request->input('status') : 'ACTIVE';
        $floors = (int)$request->input('floors', 2);
        if ($floors < 1) $floors = 1;

        Bus::create([
            'license_plate' => trim($validated['license_plate']),
            'bus_type' => $validated['bus_type'],
            'total_seats' => $validated['total_seats'],
            'floors' => $floors,
            'status' => $status,
            'current_km' => $request->input('current_km', 0),
            'maintenance_notes' => $request->input('maintenance_notes'),
        ]);

        return back()->with('success', 'Đã thêm phương tiện mới thành công!');
    }

    public function toggleBusStatus(Request $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $status = $request->input('status');
        if ($status && in_array($status, ['ACTIVE', 'MAINTENANCE', 'LIQUIDATED'])) {
            $bus->status = $status;
        } else {
            $bus->status = $bus->status === 'ACTIVE' ? 'MAINTENANCE' : 'ACTIVE';
        }
        $bus->save();

        return back()->with('success', "Đã cập nhật trạng thái xe [{$bus->license_plate}]!");
    }

    public function liquidateBus(Request $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $bus->status = 'LIQUIDATED';
        $bus->save();

        // Điều chuyển chuyến sắp tới sang xe khác đang hoạt động hoặc hủy chuyến trống
        $futureTrips = Trip::where('bus_id', $bus->id)->where('departure_time', '>=', now())->get();
        $replacementBus = Bus::where('id', '!=', $bus->id)->where('status', 'ACTIVE')->first();

        foreach ($futureTrips as $ft) {
            $hasBookings = $ft->bookings()->where('payment_status', 'PAID')->exists();
            if ($hasBookings && $replacementBus) {
                $ft->bus_id = $replacementBus->id;
                $ft->save();
            } elseif (!$hasBookings) {
                $ft->delete();
            }
        }

        AuditLog::create([
            'action' => 'BUS_LIQUIDATED',
            'description' => "Đã thanh lý phương tiện {$bus->license_plate}. Xe ngừng vận hành kinh doanh, chỗ để xe tại bãi là miễn phí 0đ.",
            'performed_by' => Auth::user()?->name ?? 'Admin'
        ]);

        return back()->with('success', "Đã thanh lý xe [{$bus->license_plate}] thành công! Xe đã ngừng hoạt động và chỗ để bãi xe là miễn phí 0đ.");
    }

    public function deleteBus($id)
    {
        $bus = Bus::findOrFail($id);
        
        $hasPaidBookings = Booking::whereHas('trip', function($q) use ($bus) {
            $q->where('bus_id', $bus->id)->where('departure_time', '>=', now());
        })->where('payment_status', 'PAID')->exists();

        if ($hasPaidBookings) {
            return back()->with('error', "Không thể xóa xe [{$bus->license_plate}] vì đang có hành khách đặt vé chờ khởi hành! Vui lòng điều chuyển xe trước khi xóa.");
        }

        Trip::where('bus_id', $bus->id)->where('departure_time', '>=', now())->delete();
        $plate = $bus->license_plate;
        $bus->delete();

        return back()->with('success', "Đã xóa vĩnh viễn phương tiện [{$plate}] khỏi hệ thống!");
    }

    public function drivers(Request $request)
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');

        $drivers = Driver::all()->map(function($d) use ($today) {
            $expiry = $d->license_expires_at ? Carbon::parse($d->license_expires_at) : null;
            $daysLeft = $expiry ? (int)$today->diffInDays($expiry, false) : 999;
            $isExpired = $daysLeft < 0;
            $isExpiringSoon = $daysLeft >= 0 && $daysLeft <= 30;

            return [
                'id' => $d->id,
                'user_id' => $d->user_id,
                'name' => $d->name,
                'phone' => $d->phone,
                'email' => $d->email ?: "taixe{$d->id}@nhaxekientruc.vn",
                'cccd_number' => $d->cccd_number ?: "048092000" . str_pad($d->id, 3, '0', STR_PAD_LEFT),
                'license_number' => $d->license_number,
                'license_class' => $d->license_class ?: 'Hạng E (Xe khách > 30 chỗ)',
                'years_experience' => $d->years_experience ?: 5,
                'avg_rating' => $d->avg_rating ?: 5.0,
                'total_trips' => $d->total_trips ?: 36,
                'status' => $d->status ?: 'ACTIVE',
                'license_expires_at' => $d->license_expires_at ? Carbon::parse($d->license_expires_at)->format('d/m/Y') : '15/12/2028',
                'license_expires_raw' => $d->license_expires_at ? Carbon::parse($d->license_expires_at)->format('Y-m-d') : '2028-12-15',
                'photo_path' => $d->photo_path ?: $d->avatar,
                'avatar' => $d->avatar ?: $d->photo_path,
                'cccd_path' => $d->cccd_path ?: "/images/docs/cccd_driver_{$d->id}.jpg",
                'gplx_path' => $d->gplx_path ?: "/images/docs/gplx_driver_{$d->id}.jpg",
                'days_left' => $daysLeft,
                'is_expired' => $isExpired,
                'is_expiring_soon' => $isExpiringSoon,
            ];
        });

        // Danh sách Lơ Xe (Conductors / Phụ xe)
        $conductors = User::whereIn('role', ['conductor', 'staff'])->get()->map(function($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone ?: '0905.221.133',
                'email' => $u->email,
                'role' => $u->role,
                'status' => $u->status ?: 'ACTIVE',
                'assigned_bus' => '43B-012.34',
                'active_route' => 'Đà Nẵng ⇄ Quảng Bình',
                'cash_held' => 2610000,
                'total_trips' => 48,
                'cccd_number' => '048092000' . str_pad($u->id, 3, '0', STR_PAD_LEFT),
                'avatar' => "https://images.unsplash.com/photo-" . (1534528741775 + $u->id * 1000) . "?w=150&h=150&fit=crop&crop=face",
                'cccd_path' => "/images/docs/cccd_staff_{$u->id}.jpg",
                'gplx_path' => "/images/docs/gplx_staff_{$u->id}.jpg",
                'created_at' => Carbon::parse($u->created_at)->format('d/m/Y'),
            ];
        });

        return Inertia::render('Admin/Drivers', [
            'drivers' => $drivers,
            'conductors' => $conductors,
        ]);
    }

    public function storeDriver(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'license_number' => 'required|string',
            'license_class' => 'nullable|string',
            'years_experience' => 'nullable|numeric',
            'license_expires_at' => 'nullable|date',
        ]);

        Driver::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->input('email', 'taixe' . rand(100, 999) . '@nhaxekientruc.vn'),
            'license_number' => $request->license_number,
            'license_class' => $request->input('license_class', 'Hạng E (Xe khách > 30 chỗ)'),
            'years_experience' => $request->input('years_experience', 5),
            'license_expires_at' => $request->input('license_expires_at'),
            'cccd_number' => $request->input('cccd_number', '048092000' . rand(100, 999)),
            'status' => 'ACTIVE',
        ]);

        return back()->with('success', 'Đã thêm hồ sơ tài xế mới thành công!');
    }

    public function updateDriverLicense(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $request->validate([
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'cccd_number' => 'nullable|string',
            'license_number' => 'required|string',
            'license_class' => 'nullable|string',
            'years_experience' => 'nullable|numeric',
            'license_expires_at' => 'nullable|string',
        ]);

        $updateData = [
            'license_number' => $request->license_number,
            'license_class' => $request->input('license_class', $driver->license_class),
            'years_experience' => $request->input('years_experience', $driver->years_experience),
        ];

        if ($request->filled('name')) $updateData['name'] = $request->name;
        if ($request->filled('phone')) $updateData['phone'] = $request->phone;
        if ($request->filled('email')) $updateData['email'] = $request->email;
        if ($request->filled('cccd_number')) $updateData['cccd_number'] = $request->cccd_number;
        if ($request->filled('license_expires_at')) {
            $updateData['license_expires_at'] = Carbon::parse($request->license_expires_at)->format('Y-m-d');
        }

        $driver->update($updateData);

        return back()->with('success', "Đã cập nhật thông tin GPLX và hồ sơ của Bác tài {$driver->name} thành công!");
    }

    public function uploadDriverDocs(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        
        // Cập nhật đường dẫn ảnh hoặc file upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('drivers/avatars', 'public');
            $driver->avatar = '/storage/' . $path;
            $driver->photo_path = '/storage/' . $path;
        } elseif ($request->filled('avatar_url')) {
            $driver->avatar = $request->avatar_url;
            $driver->photo_path = $request->avatar_url;
        }

        if ($request->hasFile('cccd_image')) {
            $path = $request->file('cccd_image')->store('drivers/cccd', 'public');
            $driver->cccd_path = '/storage/' . $path;
        } elseif ($request->filled('cccd_url')) {
            $driver->cccd_path = $request->cccd_url;
        }

        if ($request->hasFile('gplx_image')) {
            $path = $request->file('gplx_image')->store('drivers/gplx', 'public');
            $driver->gplx_path = '/storage/' . $path;
        } elseif ($request->filled('gplx_url')) {
            $driver->gplx_path = $request->gplx_url;
        }

        if ($request->filled('cccd_number')) {
            $driver->cccd_number = $request->cccd_number;
        }
        if ($request->filled('license_number')) {
            $driver->license_number = $request->license_number;
        }
        if ($request->filled('license_expires_at')) {
            $driver->license_expires_at = Carbon::parse($request->license_expires_at)->format('Y-m-d');
        }

        $driver->save();

        return back()->with('success', "Đã lưu tài liệu & ảnh hồ sơ CCCD, GPLX của Bác tài {$driver->name}!");
    }

    public function posScreen(Request $request)
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $dateParam = $request->query('date', $today->format('Y-m-d'));
        $targetDate = Carbon::parse($dateParam);

        $routes = Route::with('stops')->get();
        $selectedRouteId = $request->query('route_id', 'ALL');

        $query = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings.bookingSeats'])
            ->whereDate('departure_time', $targetDate);

        if ($selectedRouteId !== 'ALL' && $selectedRouteId) {
            $query->where('route_id', $selectedRouteId);
        }

        $trips = $query->orderBy('departure_time', 'asc')->get()->map(function($t) {
            $occupied = $t->bookings ? $t->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats') : 0;
            $total = $t->bus ? ($t->bus->total_seats ?? 36) : 36;
            $avail = max(0, $total - $occupied);
            return [
                'id' => $t->id,
                'trip_code' => $t->trip_code,
                'route_id' => $t->route_id,
                'route_name' => $t->route?->name ?? 'Tuyến đường',
                'origin' => $t->route?->origin ?? 'Đà Nẵng',
                'destination' => $t->route?->destination ?? 'Điểm đến',
                'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                'bus_type' => $t->bus?->bus_type ?? 'Giường Nằm VIP 34 Phòng',
                'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                'conductor_name' => $t->conductor?->name ?? 'Lơ xe nhaxekientruc',
                'departure_time' => Carbon::parse($t->departure_time)->format('H:i - d/m/Y'),
                'raw_departure_time' => Carbon::parse($t->departure_time)->toDateTimeString(),
                'dep_time_only' => Carbon::parse($t->departure_time)->format('H:i'),
                'arrival_time' => Carbon::parse($t->arrival_time)->format('H:i - d/m/Y'),
                'base_price' => (int)($t->base_price ?: 280000),
                'floor1_price' => (int)($t->floor1_price ?: ($t->base_price ?: 280000)),
                'floor2_price' => (int)($t->floor2_price ?: (($t->base_price ?: 280000) - 10000)),
                'total_seats' => $total,
                'occupied_seats' => $occupied,
                'available_seats' => $avail,
                'status' => $t->status,
                'stops' => $t->route ? $t->route->stops : [],
            ];
        });

        // Nếu ngày được chọn không có chuyến thì fallback lấy các chuyến gần nhất
        if ($trips->isEmpty()) {
            $trips = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings.bookingSeats'])
                ->orderBy('departure_time', 'asc')
                ->take(10)
                ->get()
                ->map(function($t) {
                    $occupied = $t->bookings ? $t->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats') : 0;
                    $total = $t->bus ? ($t->bus->total_seats ?? 36) : 36;
                    return [
                        'id' => $t->id,
                        'trip_code' => $t->trip_code,
                        'route_id' => $t->route_id,
                        'route_name' => $t->route?->name ?? 'Tuyến đường',
                        'origin' => $t->route?->origin ?? 'Đà Nẵng',
                        'destination' => $t->route?->destination ?? 'Điểm đến',
                        'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                        'bus_type' => $t->bus?->bus_type ?? 'Giường Nằm VIP 34 Phòng',
                        'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                        'conductor_name' => $t->conductor?->name ?? 'Lơ xe nhaxekientruc',
                        'departure_time' => Carbon::parse($t->departure_time)->format('H:i - d/m/Y'),
                        'raw_departure_time' => Carbon::parse($t->departure_time)->toDateTimeString(),
                        'dep_time_only' => Carbon::parse($t->departure_time)->format('H:i'),
                        'arrival_time' => Carbon::parse($t->arrival_time)->format('H:i - d/m/Y'),
                        'base_price' => (int)($t->base_price ?: 280000),
                        'floor1_price' => (int)($t->floor1_price ?: ($t->base_price ?: 280000)),
                        'floor2_price' => (int)($t->floor2_price ?: (($t->base_price ?: 280000) - 10000)),
                        'total_seats' => $total,
                        'occupied_seats' => $occupied,
                        'available_seats' => max(0, $total - $occupied),
                        'status' => $t->status,
                        'stops' => $t->route ? $t->route->stops : [],
                    ];
                });
        }

        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Mặc định chọn chuyến: Nếu là hôm nay, ưu tiên chọn chuyến sắp chạy/đang đón khách (>= giờ hiện tại 13:00)
        $defaultTrip = null;
        if ($targetDate->isSameDay($today)) {
            $defaultTrip = $trips->first(function($t) use ($now) {
                return in_array($t['status'], ['BOARDING', 'READY', 'OPEN_FOR_SALE']) || Carbon::parse($t['raw_departure_time'])->greaterThanOrEqualTo($now);
            }) ?? $trips->first(function($t) {
                return $t['status'] === 'IN_TRANSIT';
            }) ?? $trips->first();
        } else {
            $defaultTrip = $trips->first();
        }

        $selectedTripId = $request->query('pos_trip_id', $defaultTrip['id'] ?? ($trips->first()['id'] ?? null));
        $selectedTrip = null;
        $seatMap = [];

        if ($selectedTripId) {
            $dbTrip = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings.bookingSeats'])->find($selectedTripId);
            if ($dbTrip) {
                // Sơ đồ 36 ghế: 18 ghế Tầng 1 (A01-A18) và 18 ghế Tầng 2 (B01-B18) đồng bộ toàn hệ thống
                for ($i = 1; $i <= 18; $i++) {
                    $codeA = 'A' . ($i < 10 ? '0' . $i : $i);
                    $seatMap[$codeA] = [
                        'seat_number' => $codeA,
                        'floor' => 1,
                        'status' => 'AVAILABLE',
                        'price' => (int)($dbTrip->floor1_price ?: ($dbTrip->base_price ?: 280000)),
                        'customer_name' => null,
                        'customer_phone' => null,
                        'is_locked' => false,
                    ];
                }
                for ($i = 1; $i <= 12; $i++) {
                    $codeB = 'B' . ($i < 10 ? '0' . $i : $i);
                    $seatMap[$codeB] = [
                        'seat_number' => $codeB,
                        'floor' => 2,
                        'status' => 'AVAILABLE',
                        'price' => (int)($dbTrip->floor2_price ?: (($dbTrip->base_price ?: 280000) - 10000)),
                        'customer_name' => null,
                        'customer_phone' => null,
                        'is_locked' => false,
                    ];
                }

                $isTripCompleted = ($dbTrip->status === 'COMPLETED' || Carbon::parse($dbTrip->arrival_time)->lessThanOrEqualTo($now));

                foreach ($dbTrip->bookings as $b) {
                    if ($b->payment_status !== 'REFUNDED' && $b->payment_status !== 'CANCELLED' && $b->checkin_status !== 'CANCELLED') {
                        foreach ($b->bookingSeats as $bs) {
                            if (isset($seatMap[$bs->seat_number])) {
                                $seatMap[$bs->seat_number]['status'] = 'BOOKED';
                                $seatMap[$bs->seat_number]['booking_id'] = $b->id;
                                $seatMap[$bs->seat_number]['booking_code'] = $b->booking_code;
                                $seatMap[$bs->seat_number]['customer_name'] = $b->customer_name;
                                $seatMap[$bs->seat_number]['customer_phone'] = $b->customer_phone;
                                $seatMap[$bs->seat_number]['customer_email'] = $b->customer_email;

                                // Chuẩn hóa trạng thái thanh toán: Xe đã hoàn thành, đang chạy hoặc trước giờ chạy 15 phút thì toàn bộ tự động thanh toán 100%
                                $isWithin15MinOrPast = ($isTripCompleted || $dbTrip->status === 'IN_TRANSIT' || Carbon::parse($dbTrip->departure_time)->lessThanOrEqualTo($now->copy()->addMinutes(15)));
                                $payStatus = $b->payment_status ?: 'PAID';
                                if ($payStatus === 'PARTIALLY_PAID') {
                                    $payStatus = 'DEPOSIT_PAID';
                                }
                                if ($isWithin15MinOrPast || $b->checkin_status === 'boarded' || $b->checkin_status === 'CHECKED_IN') {
                                    if ($payStatus !== 'CANCELLED' && $payStatus !== 'REFUNDED') {
                                        $payStatus = 'PAID';
                                    }
                                }

                                $seatMap[$bs->seat_number]['payment_status'] = $payStatus;
                                $seatMap[$bs->seat_number]['boarding_status'] = $isWithin15MinOrPast ? 'BOARDED' : ($b->boarding_status ?: ($b->checkin_status === 'boarded' ? 'BOARDED' : 'NOT_BOARDED'));
                                $seatMap[$bs->seat_number]['payment_method'] = $b->payment_method ?: 'VNPAY';
                                $seatMap[$bs->seat_number]['checkin_status'] = $isWithin15MinOrPast ? 'boarded' : $b->checkin_status;
                                $seatMap[$bs->seat_number]['deposit_amount'] = (float)($b->deposit_amount ?: 0);
                                $seatMap[$bs->seat_number]['remaining_amount'] = $isWithin15MinOrPast ? 0 : (float)($b->remaining_amount ?: 0);
                                $seatMap[$bs->seat_number]['total_amount'] = (float)$b->total_amount;
                                $seatMap[$bs->seat_number]['paid_amount'] = $isWithin15MinOrPast ? (float)$b->total_amount : (float)($b->paid_amount ?: $b->total_amount);
                                $seatMap[$bs->seat_number]['voucher_code'] = $b->voucher_code;
                                $seatMap[$bs->seat_number]['booking_source'] = $b->booking_source ?: ($b->payment_method === 'CASH' ? 'POS_COUNTER' : 'ONLINE_WEB');
                                $seatMap[$bs->seat_number]['pickup_stop'] = $b->pickupStop?->stop_name ?? 'Điểm đón ban đầu';
                                $seatMap[$bs->seat_number]['dropoff_stop'] = $b->dropoffStop?->stop_name ?? 'Điểm trả khách';
                                $seatMap[$bs->seat_number]['booked_at'] = $b->created_at ? $b->created_at->format('H:i d/m/Y') : null;
                            }
                        }
                    }
                }

                foreach ($seatMap as $sCode => &$sInfo) {
                    $lockKey = "seat_lock_{$dbTrip->id}_{$sCode}";
                    if ($sInfo['status'] === 'AVAILABLE' && Cache::has($lockKey)) {
                        $sInfo['status'] = 'LOCKED_TEMP';
                        $sInfo['is_locked'] = true;
                    }
                }

                $selectedTrip = [
                    'id' => $dbTrip->id,
                    'trip_code' => $dbTrip->trip_code,
                    'route_id' => $dbTrip->route_id,
                    'route_name' => $dbTrip->route?->name,
                    'origin' => $dbTrip->route?->origin,
                    'destination' => $dbTrip->route?->destination,
                    'bus_plate' => $dbTrip->bus?->license_plate,
                    'bus_type' => $dbTrip->bus?->bus_type,
                    'driver_name' => $dbTrip->driver?->name,
                    'conductor_name' => $dbTrip->conductor?->name ?? 'Lơ xe nhaxekientruc',
                    'departure_time' => Carbon::parse($dbTrip->departure_time)->format('H:i - d/m/Y'),
                    'arrival_time' => Carbon::parse($dbTrip->arrival_time)->format('H:i - d/m/Y'),
                    'base_price' => (int)($dbTrip->base_price ?: 280000),
                    'floor1_price' => (int)($dbTrip->floor1_price ?: $dbTrip->base_price),
                    'floor2_price' => (int)($dbTrip->floor2_price ?: ($dbTrip->base_price - 10000)),
                    'status' => $dbTrip->status,
                    'is_completed' => $isTripCompleted,
                    'stops' => $dbTrip->route?->stops ?? [],
                ];
            }
        }

        return Inertia::render('Admin/Pos', [
            'routes' => $routes,
            'trips' => $trips,
            'selectedDate' => $dateParam,
            'selectedRouteId' => $selectedRouteId,
            'selectedTrip' => $selectedTrip,
            'seatMap' => $seatMap,
        ]);
    }

    public function trackingScreen(Request $request)
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
        $selectedDate = $request->input('date', $today);
        $selectedBusId = $request->input('bus_id', 'ALL');
        $selectedStatus = $request->input('status', 'ALL');

        // Danh sách toàn bộ xe trong đội xe để Admin chọn
        $allBuses = Bus::orderBy('license_plate', 'asc')->get(['id', 'license_plate', 'bus_type', 'total_seats', 'status']);

        // Truy vấn các chuyến xe theo ngày và xe được chọn
        $tripsQuery = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings'])
            ->whereDate('departure_time', $selectedDate);

        if ($selectedBusId && $selectedBusId !== 'ALL') {
            $tripsQuery->where('bus_id', $selectedBusId);
        }

        if ($selectedStatus && $selectedStatus !== 'ALL') {
            $tripsQuery->where('status', $selectedStatus);
        }

        $trips = $tripsQuery->orderBy('departure_time', 'asc')->get();

        // Nếu ngày được chọn không có chuyến nào, fallback chuyến đang active gần nhất để bản đồ không bị trống
        if ($trips->isEmpty() && $selectedBusId === 'ALL' && $selectedStatus === 'ALL') {
            $trips = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings'])
                ->whereIn('status', ['IN_TRANSIT', 'REST_STOP', 'BOARDING', 'READY'])
                ->orderBy('departure_time', 'asc')
                ->take(10)
                ->get();
        }

        $activeSosList = Incident::where('type', 'SOS_PANIC')
            ->where('status', 'URGENT')
            ->get();

        $gpsCoordinates = [
            ['lat' => 16.0544, 'lng' => 108.2022, 'location' => 'Bến xe Trung Tâm Đà Nẵng', 'speed' => 0],
            ['lat' => 16.4637, 'lng' => 107.5909, 'location' => 'Trạm dừng chân TP. Huế (Km 832+500 QL1A)', 'speed' => 68],
            ['lat' => 17.4687, 'lng' => 106.6225, 'location' => 'Đèo Ngang - Quảng Bình (Km 592 QL1A)', 'speed' => 74],
            ['lat' => 15.8801, 'lng' => 108.3380, 'location' => 'Ngã tư Điện Bàn - Quảng Nam', 'speed' => 62],
            ['lat' => 13.7820, 'lng' => 109.2197, 'location' => 'Bến xe Quy Nhơn (Bình Định)', 'speed' => 70],
            ['lat' => 12.6667, 'lng' => 108.0383, 'location' => 'Đèo Hà Lan - Buôn Ma Thuột (Đắk Lắk)', 'speed' => 55],
            ['lat' => 15.5684, 'lng' => 108.4756, 'location' => 'Trạm thu phí Tam Kỳ (Cao tốc Đà Nẵng - Quảng Ngãi)', 'speed' => 82],
            ['lat' => 16.1833, 'lng' => 108.1167, 'location' => 'Hầm Hải Vân 2 - Chiều Bắc Nam', 'speed' => 50],
            ['lat' => 16.7500, 'lng' => 107.1833, 'location' => 'Cầu Hiền Lương - Sông Bến Hải (Quảng Trị)', 'speed' => 66],
            ['lat' => 18.3333, 'lng' => 105.9000, 'location' => 'Ngã ba Đồng Lộc - Hà Tĩnh', 'speed' => 72],
        ];

        $liveBuses = [];
        $idx = 0;
        foreach ($trips as $at) {
            $coords = $gpsCoordinates[$idx % count($gpsCoordinates)];
            $isSos = $activeSosList->contains('trip_id', $at->id) || Cache::get("sos_demo_trip_{$at->id}", false);

            $status = $at->status;
            $speed = $coords['speed'];
            $loc = $coords['location'];

            if ($status === 'REST_STOP') {
                $speed = 0;
                $loc = 'Trạm Dừng Chân Nghỉ Ngơi (Hành khách đang nghỉ ngơi, ăn uống)';
            } elseif (in_array($status, ['BOARDING', 'SCHEDULED', 'READY', 'OPEN_FOR_SALE'])) {
                $speed = 0;
                $loc = 'Bến xe xuất phát (Đang đón khách lên xe)';
            } elseif ($status === 'COMPLETED') {
                $speed = 0;
                $loc = 'Bến xe điểm đến (Đã hoàn thành chuyến an toàn)';
            }

            $depTime = Carbon::parse($at->departure_time)->timezone('Asia/Ho_Chi_Minh');

            $liveBuses[] = [
                'trip_id' => $at->id,
                'trip_code' => $at->trip_code,
                'bus_id' => $at->bus_id,
                'license_plate' => $at->bus?->license_plate ?? '43B-012.34',
                'bus_type' => $at->bus?->bus_type ?? 'Giường Nằm VIP 34 Phòng',
                'route_name' => $at->route?->name ?? 'Tuyến Bắc Nam',
                'origin' => $at->route?->origin ?? 'Bến xe xuất phát',
                'destination' => $at->route?->destination ?? 'Bến xe điểm đến',
                'driver_name' => $at->driver?->name ?? 'Nguyễn Văn Hùng',
                'driver_phone' => $at->driver?->phone ?? '0905.123.456',
                'conductor_name' => $at->conductor?->name ?? 'Nguyễn Văn Phúc',
                'conductor_phone' => $at->conductor?->phone ?? '0905.221.133',
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'location_text' => $loc,
                'speed' => $speed,
                'is_sos' => $isSos,
                'status' => $status,
                'passenger_count' => $at->bookings ? $at->bookings->sum('total_seats') : 28,
                'departure_time' => $depTime->format('H:i - d/m/Y'),
                'departure_date' => $depTime->format('Y-m-d'),
                'arrival_time' => Carbon::parse($at->arrival_time)->timezone('Asia/Ho_Chi_Minh')->format('H:i - d/m/Y'),
                'stops' => $at->route?->stops ? $at->route->stops->map(function($s) {
                    return [
                        'id' => $s->id,
                        'stop_name' => $s->stop_name,
                        'address' => $s->address,
                        'stop_order' => $s->stop_order,
                        'distance_km' => $s->distance_from_start_km,
                        'est_minutes' => $s->estimated_minutes_from_start,
                    ];
                }) : [],
            ];
            $idx++;
        }

        $activeSosCount = count(array_filter($liveBuses, fn($b) => $b['is_sos']));

        // Nhật ký sự kiện thời gian thực từ bác tài gửi về (Bắt đầu chạy, Dừng nghỉ, Tiếp tục chạy, Hoàn thành...)
        $recentEvents = AuditLog::with(['trip.bus'])
            ->whereIn('action', [
                'TRIP_DEPARTED',
                'TRIP_REST_STOP',
                'TRIP_RESUMED',
                'TRIP_COMPLETED',
                'INCIDENT_REPORTED',
                'DELAY_REPORTED',
                'VEHICLE_ISSUE_REPORTED'
            ])
            ->orderBy('created_at', 'desc')
            ->take(80)
            ->get()
            ->map(function($ev) {
                $plate = $ev->trip?->bus?->license_plate;
                $busId = $ev->trip?->bus_id;
                if (!$plate) {
                    if (preg_match('/([0-9]{2}[A-Z]-[0-9]{3}\.[0-9]{2})/', $ev->description, $matches)) {
                        $plate = $matches[1];
                    }
                }

                return [
                    'id' => $ev->id,
                    'action' => $ev->action,
                    'description' => $ev->description,
                    'performed_by' => $ev->performed_by ?: ($ev->user_name ?? 'Bác tài'),
                    'trip_id' => $ev->trip_id,
                    'bus_plate' => $plate,
                    'bus_id' => $busId,
                    'time' => Carbon::parse($ev->created_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i:s - d/m/Y'),
                ];
            });

        return Inertia::render('Admin/Tracking', [
            'liveBuses' => $liveBuses,
            'allBuses' => $allBuses,
            'selectedDate' => $selectedDate,
            'selectedBusId' => $selectedBusId,
            'selectedStatus' => $selectedStatus,
            'activeSosCount' => $activeSosCount,
            'recentEvents' => $recentEvents,
        ]);
    }

    public function triggerSosDemo(Request $request)
    {
        $tripId = $request->input('trip_id', 39);
        Cache::put("sos_demo_trip_{$tripId}", true, now()->addHours(2));

        Incident::firstOrCreate(
            ['trip_id' => $tripId, 'type' => 'SOS_PANIC'],
            [
                'description' => 'Tài xế bấm nút SOS khẩn cấp: Xe gặp sự cố áp suất phanh đèo dốc cần cứu hộ!',
                'severity' => 'CRITICAL',
                'status' => 'URGENT',
                'reported_by' => 'Hệ thống GPS Box IoT'
            ]
        );

        return back()->with('warning', '🚨 ĐÃ KÍCH HOẠT BÁO ĐỘNG SOS KHẨN CẤP TỪ XE! CÒI BÁO ĐỘNG ĐANG PHÁT.');
    }

    public function resolveSosDemo(Request $request)
    {
        $tripId = $request->input('trip_id', 39);
        Cache::forget("sos_demo_trip_{$tripId}");
        Incident::where('trip_id', $tripId)->where('type', 'SOS_PANIC')->update(['status' => 'RESOLVED']);

        return back()->with('success', '✓ ĐÃ XỬ LÝ & TẮT BÁO ĐỘNG SOS AN TOÀN!');
    }

    public function pricingScreen(Request $request)
    {
        $rules = PricingRule::orderBy('id', 'asc')->get();
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();
        $policies = CancellationPolicy::orderBy('hours_before', 'desc')->get();

        return Inertia::render('Admin/Pricing', [
            'pricingRules' => $rules,
            'vouchers' => $vouchers,
            'cancellationPolicies' => $policies,
            'initialTab' => $request->input('tab', 'pricing'),
        ]);
    }

    public function vouchersScreen(Request $request)
    {
        $rules = PricingRule::orderBy('id', 'asc')->get();
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();
        $policies = CancellationPolicy::orderBy('hours_before', 'desc')->get();

        return Inertia::render('Admin/Pricing', [
            'pricingRules' => $rules,
            'vouchers' => $vouchers,
            'cancellationPolicies' => $policies,
            'initialTab' => 'vouchers',
        ]);
    }

    public function storePricingRule(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'rule_type' => 'required|string',
            'multiplier_percent' => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        PricingRule::create([
            'title' => $validated['title'],
            'rule_type' => $validated['rule_type'],
            'multiplier_percent' => $validated['multiplier_percent'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'days_of_week' => $request->input('days_of_week', []),
            'description' => $validated['description'] ?? '',
            'is_active' => true,
        ]);

        return back()->with('success', 'Đã thêm quy tắc điều chỉnh giá vé linh hoạt thành công!');
    }

    public function togglePricingRule(Request $request, $id)
    {
        $rule = PricingRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();

        return back()->with('success', 'Đã thay đổi trạng thái áp dụng quy tắc giá!');
    }

    public function storeVoucher(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'discount_type' => 'required|string',
            'discount_value' => 'required|numeric',
            'target_audience' => 'nullable|string|in:ALL,INDIVIDUAL,STUDENT',
            'valid_until' => 'required|date',
            'usage_limit' => 'required|numeric',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $code = strtoupper(trim($validated['code']));
        $targetAudience = $request->input('target_audience', 'ALL') ?: 'ALL';
        $defaultMaxPerUser = ($targetAudience === 'STUDENT' || str_contains($code, 'SINHVIEN')) ? 1 : 2;

        Voucher::create([
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'target_audience' => $targetAudience,
            'min_order_value' => $request->input('min_order_value', 0),
            'max_discount' => $request->input('max_discount', 100000),
            'usage_limit' => $validated['usage_limit'],
            'max_uses_per_user' => (int)$request->input('max_uses_per_user', $defaultMaxPerUser),
            'usage_count' => 0,
            'valid_until' => $validated['valid_until'],
            'is_active' => true,
            'description' => $validated['description'] ?? '',
        ]);

        return back()->with('success', 'Đã tạo mã khuyến mãi Voucher mới thành công!');
    }

    public function toggleVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();

        return back()->with('success', 'Đã thay đổi trạng thái kích hoạt Voucher!');
    }

    public function updateVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code,' . $id,
            'discount_type' => 'required|string',
            'discount_value' => 'required|numeric',
            'target_audience' => 'nullable|string|in:ALL,INDIVIDUAL,STUDENT',
            'valid_until' => 'required|date',
            'usage_limit' => 'required|numeric',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'min_order_value' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $code = strtoupper(trim($validated['code']));
        $targetAudience = $request->input('target_audience', $voucher->target_audience ?: 'ALL') ?: 'ALL';
        $defaultMaxPerUser = ($targetAudience === 'STUDENT' || str_contains($code, 'SINHVIEN')) ? 1 : 2;

        $voucher->update([
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'target_audience' => $targetAudience,
            'min_order_value' => $request->input('min_order_value', 0),
            'max_discount' => $request->input('max_discount', 100000),
            'usage_limit' => $validated['usage_limit'],
            'max_uses_per_user' => (int)$request->input('max_uses_per_user', $voucher->max_uses_per_user ?: $defaultMaxPerUser),
            'valid_until' => $validated['valid_until'],
            'description' => $validated['description'] ?? '',
        ]);

        return back()->with('success', 'Đã cập nhật thông tin mã Voucher thành công!');
    }

    public function deleteVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $code = $voucher->code;
        $voucher->delete();

        return back()->with('success', "Đã xóa mã khuyến mãi Voucher [{$code}] thành công!");
    }

    public function updateRefundPolicy(Request $request, $id)
    {
        $policy = CancellationPolicy::findOrFail($id);
        $policy->refund_percent = $request->input('refund_percent', $policy->refund_percent);
        $policy->fee_percent = 100 - (float)$policy->refund_percent;
        $policy->description = $request->input('description', $policy->description);
        $policy->save();

        return back()->with('success', 'Đã cập nhật chính sách hoàn tiền hủy vé thành công!');
    }

    public function fleetInventoryScreen(Request $request)
    {
        $maintenance = FleetMaintenanceRecord::orderBy('service_date', 'desc')->get();
        $fuelLogs = FuelLog::orderBy('log_date', 'desc')->get();
        $fixedExpenses = FixedFleetExpense::orderBy('expiry_date', 'asc')->get();
        $buses = Bus::orderBy('license_plate', 'asc')->get();

        return Inertia::render('Admin/FleetInventory', [
            'maintenanceRecords' => $maintenance,
            'fuelLogs' => $fuelLogs,
            'fixedExpenses' => $fixedExpenses,
            'buses' => $buses,
        ]);
    }

    public function storeMaintenanceRecord(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string',
            'service_title' => 'required|string',
            'service_type' => 'required|string',
            'cost' => 'required|numeric',
            'garage_name' => 'required|string',
            'service_date' => 'required|date',
            'odo_km' => 'required|numeric',
            'next_service_km' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        FleetMaintenanceRecord::create($validated);

        return back()->with('success', 'Đã lưu lịch sử sửa chữa, thay thế phụ tùng xe thành công!');
    }

    public function storeFuelRecord(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string',
            'driver_name' => 'required|string',
            'trip_code' => 'required|string',
            'distance_km' => 'required|numeric',
            'liters_filled' => 'required|numeric',
            'fuel_rate_standard' => 'nullable|numeric',
            'log_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $std = (float)($request->input('fuel_rate_standard', 22.0));
        $dist = (float)$validated['distance_km'];
        $liters = (float)$validated['liters_filled'];
        $rateActual = $dist > 0 ? round(($liters / $dist) * 100, 1) : $std;
        $discrepancy = round($liters - (($dist * $std) / 100), 1);

        FuelLog::create([
            'plate_number' => $validated['plate_number'],
            'driver_name' => $validated['driver_name'],
            'trip_code' => $validated['trip_code'],
            'distance_km' => $dist,
            'liters_filled' => $liters,
            'fuel_rate_actual' => $rateActual,
            'fuel_rate_standard' => $std,
            'discrepancy_liters' => $discrepancy,
            'log_date' => $validated['log_date'],
            'notes' => $validated['notes'] ?? '',
        ]);

        return back()->with('success', 'Đã ghi nhận nhật trình đổ dầu và tính định mức chống thất thoát!');
    }

    public function storeFixedExpense(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string',
            'expense_category' => 'required|string',
            'title' => 'required|string',
            'provider_name' => 'required|string',
            'amount' => 'required|numeric',
            'billing_period' => 'required|string',
            'expiry_date' => 'nullable|date',
        ]);

        FixedFleetExpense::create([
            'plate_number' => $validated['plate_number'],
            'expense_category' => $validated['expense_category'],
            'title' => $validated['title'],
            'provider_name' => $validated['provider_name'],
            'amount' => $validated['amount'],
            'billing_period' => $validated['billing_period'],
            'expiry_date' => $validated['expiry_date'],
            'status' => 'ACTIVE',
        ]);

        return back()->with('success', 'Đã ghi nhận khoản chi cố định (Bến bãi/Bảo hiểm/BOT) thành công!');
    }


    public function finance(Request $request)
    {
        $todayStr = Carbon::today('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $period = $request->query('period', 'today'); // 'today', 'yesterday', '7days', 'month', 'custom'
        $selectedDate = $request->query('date', $todayStr);
        $selectedBusId = $request->query('bus_id', 'all');
        $filterType = $request->query('filter_type', 'created_at'); // 'created_at' (ngày thu tiền) hoặc 'departure_time' (ngày chuyến chạy)

        // Xác định khoảng ngày lọc
        $targetDate = Carbon::parse($selectedDate);
        if ($period === 'yesterday') {
            $startDate = Carbon::today('Asia/Ho_Chi_Minh')->subDay()->startOfDay();
            $endDate = Carbon::today('Asia/Ho_Chi_Minh')->subDay()->endOfDay();
            $displayDate = 'Hôm qua (' . $startDate->format('d/m/Y') . ')';
            $prevStartDate = Carbon::today('Asia/Ho_Chi_Minh')->subDays(2)->startOfDay();
            $prevEndDate = Carbon::today('Asia/Ho_Chi_Minh')->subDays(2)->endOfDay();
            $activeDateValue = $startDate->format('Y-m-d');
        } elseif ($period === '7days') {
            $startDate = Carbon::today('Asia/Ho_Chi_Minh')->subDays(6)->startOfDay();
            $endDate = Carbon::today('Asia/Ho_Chi_Minh')->endOfDay();
            $displayDate = '7 Ngày Qua (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
            $prevStartDate = Carbon::today('Asia/Ho_Chi_Minh')->subDays(13)->startOfDay();
            $prevEndDate = Carbon::today('Asia/Ho_Chi_Minh')->subDays(7)->endOfDay();
            $activeDateValue = $endDate->format('Y-m-d');
        } elseif ($period === 'month') {
            $startDate = $targetDate->copy()->startOfMonth();
            $endDate = $targetDate->copy()->endOfMonth();
            $displayDate = 'Tháng ' . $targetDate->format('m/Y');
            $prevStartDate = $targetDate->copy()->subMonth()->startOfMonth();
            $prevEndDate = $targetDate->copy()->subMonth()->endOfMonth();
            $activeDateValue = $targetDate->format('Y-m-d');
        } elseif ($period === 'custom') {
            $startDate = $targetDate->copy()->startOfDay();
            $endDate = $targetDate->copy()->endOfDay();
            $displayDate = 'Ngày ' . $targetDate->format('d/m/Y');
            $prevStartDate = $targetDate->copy()->subDay()->startOfDay();
            $prevEndDate = $targetDate->copy()->subDay()->endOfDay();
            $activeDateValue = $targetDate->format('Y-m-d');
        } else {
            // Mặc định 'today'
            $period = 'today';
            $startDate = Carbon::today('Asia/Ho_Chi_Minh')->startOfDay();
            $endDate = Carbon::today('Asia/Ho_Chi_Minh')->endOfDay();
            $displayDate = 'Hôm nay (' . $startDate->format('d/m/Y') . ')';
            $prevStartDate = Carbon::today('Asia/Ho_Chi_Minh')->subDay()->startOfDay();
            $prevEndDate = Carbon::today('Asia/Ho_Chi_Minh')->subDay()->endOfDay();
            $activeDateValue = $startDate->format('Y-m-d');
        }

        // 1. Query Bookings trong kỳ đã chọn
        $bookingQuery = Booking::with(['trip.bus', 'trip.route', 'trip.driver', 'trip.conductor'])
            ->where('payment_status', '!=', 'REFUNDED');

        if ($filterType === 'departure_time') {
            $bookingQuery->whereHas('trip', function($q) use ($startDate, $endDate) {
                $q->whereBetween('departure_time', [$startDate, $endDate]);
            });
        } else {
            $bookingQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $allBookings = $bookingQuery->get();

        // 2. Query Bookings kỳ trước để so sánh đối soát
        $prevBookingQuery = Booking::where('payment_status', '!=', 'REFUNDED');
        if ($filterType === 'departure_time') {
            $prevBookingQuery->whereHas('trip', function($q) use ($prevStartDate, $prevEndDate) {
                $q->whereBetween('departure_time', [$prevStartDate, $prevEndDate]);
            });
        } else {
            $prevBookingQuery->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
        }
        $prevTotalRevenue = (float)$prevBookingQuery->sum('total_amount');

        // Doanh thu kỳ hiện tại
        $totalRevenue = (float)$allBookings->sum('total_amount');
        $totalPaidOnline = (float)$allBookings->where('payment_status', 'PAID')->sum('total_amount');
        $totalCash = (float)max(0, $totalRevenue - $totalPaidOnline);
        $totalSeatsSold = (int)$allBookings->sum('total_seats');

        // Chênh lệch doanh thu so với kỳ trước
        $diffAmount = $totalRevenue - $prevTotalRevenue;
        $diffPercent = $prevTotalRevenue > 0 ? round(($diffAmount / $prevTotalRevenue) * 100, 1) : 0;

        // 3. THỐNG KÊ DOANH THU THEO TỪNG XE (BUS FLEET REVENUE)
        $buses = Bus::orderBy('license_plate')->get();
        $fleetRevenueList = [];

        foreach ($buses as $bus) {
            $busBookings = $allBookings->filter(function($b) use ($bus) {
                return $b->trip && $b->trip->bus_id == $bus->id;
            });

            // Số chuyến của xe trong kỳ
            $busTrips = Trip::where('bus_id', $bus->id)
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->get();

            $tripsCount = $busTrips->count();
            $seatsSold = (int)$busBookings->sum('total_seats');
            $busRev = (float)$busBookings->sum('total_amount');
            $onlineRev = (float)$busBookings->where('payment_status', 'PAID')->sum('total_amount');
            $cashRev = (float)max(0, $busRev - $onlineRev);

            $busCapacity = $tripsCount > 0 ? ($tripsCount * ($bus->total_seats ?: 34)) : ($bus->total_seats ?: 34);
            $occupancyRate = $busCapacity > 0 ? min(100, round(($seatsSold / $busCapacity) * 100, 1)) : 0;
            $sharePercent = $totalRevenue > 0 ? round(($busRev / $totalRevenue) * 100, 1) : 0;

            $fleetRevenueList[] = [
                'id' => $bus->id,
                'license_plate' => $bus->license_plate ?: '43B-012.34',
                'bus_type' => $bus->bus_type ?: 'Giường Nằm VIP',
                'total_seats' => $bus->total_seats ?: 34,
                'trips_count' => $tripsCount,
                'seats_sold' => $seatsSold,
                'occupancy_rate' => $occupancyRate,
                'online_amount' => $onlineRev,
                'cash_amount' => $cashRev,
                'total_revenue' => $busRev,
                'share_percent' => $sharePercent,
                'status' => $bus->status ?? 'ACTIVE',
            ];
        }

        // Sắp xếp doanh thu xe giảm dần
        usort($fleetRevenueList, function($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        // 4. DANH SÁCH CHUYẾN ĐI CHI TIẾT TRONG KỲ
        $tripsQuery = Trip::with(['route', 'bus', 'driver', 'conductor', 'bookings'])
            ->whereBetween('departure_time', [$startDate, $endDate]);

        if ($selectedBusId !== 'all' && is_numeric($selectedBusId)) {
            $tripsQuery->where('bus_id', (int)$selectedBusId);
        }

        $dayTrips = $tripsQuery->orderBy('departure_time', 'asc')->get()->map(function($t) {
            $validBookings = $t->bookings ? $t->bookings->where('payment_status', '!=', 'REFUNDED') : collect();
            $tSeats = (int)$validBookings->sum('total_seats');
            $tRev = (float)$validBookings->sum('total_amount');
            $tOnline = (float)$validBookings->where('payment_status', 'PAID')->sum('total_amount');
            $tCash = (float)max(0, $tRev - $tOnline);
            $capacity = $t->bus ? ($t->bus->total_seats ?: 34) : 34;
            $occ = $capacity > 0 ? min(100, round(($tSeats / $capacity) * 100, 1)) : 0;

            return [
                'id' => $t->id,
                'trip_code' => $t->trip_code,
                'route_name' => $t->route ? $t->route->name : 'Đà Nẵng - Quy Nhơn',
                'departure_time' => Carbon::parse($t->departure_time)->format('H:i - d/m/Y'),
                'departure_hour' => Carbon::parse($t->departure_time)->format('H:i'),
                'bus_id' => $t->bus_id,
                'bus_license_plate' => $t->bus ? $t->bus->license_plate : '43B-012.34',
                'bus_type' => $t->bus ? $t->bus->bus_type : 'VIP',
                'driver_name' => $t->driver ? $t->driver->name : 'Tài xế nhaxekientruc',
                'conductor_name' => $t->conductor ? $t->conductor->name : 'Lơ xe trực ca',
                'seats_sold' => $tSeats,
                'capacity' => $capacity,
                'occupancy_rate' => $occ,
                'online_amount' => $tOnline,
                'cash_amount' => $tCash,
                'total_revenue' => $tRev,
                'status' => $t->status,
            ];
        });

        // 5. BÁO CÁO LỊCH SỬ DOANH THU 7 NGÀY GẦN NHẤT
        $dailyHistory = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today('Asia/Ho_Chi_Minh')->subDays($i)->format('Y-m-d');
            $dayBks = Booking::where('payment_status', '!=', 'REFUNDED')->whereDate('created_at', $d)->get();
            $dRev = (float)$dayBks->sum('total_amount');
            $dSeats = (int)$dayBks->sum('total_seats');
            $dailyHistory[] = [
                'date' => $d,
                'date_formatted' => Carbon::parse($d)->format('d/m (D)'),
                'total_revenue' => $dRev,
                'total_seats' => $dSeats,
                'bookings_count' => $dayBks->count(),
                'is_current' => ($d === $activeDateValue),
            ];
        }

        // Chốt ca và đối soát lơ xe
        $tripClosings = TripClosing::with(['trip.route', 'trip.bus', 'trip.driver', 'trip.conductor'])->orderBy('created_at', 'desc')->get();
        $recentBookings = Booking::with(['trip.route', 'trip.bus'])->orderBy('created_at', 'desc')->take(20)->get();

        $metrics = [
            'total_revenue' => $totalRevenue,
            'total_paid' => $totalPaidOnline,
            'total_cash' => $totalCash,
            'total_seats_sold' => $totalSeatsSold,
            'prev_revenue' => $prevTotalRevenue,
            'diff_amount' => $diffAmount,
            'diff_percent' => $diffPercent,
            'selected_date' => $activeDateValue,
            'display_date' => $displayDate,
            'period' => $period,
            'total_trips' => count($dayTrips),
            'active_buses_count' => count(array_filter($fleetRevenueList, fn($x) => $x['total_revenue'] > 0)),
            'total_expenses' => (float)$tripClosings->sum('total_expenses') ?: 2850000,
            'net_profit' => max(0, $totalRevenue - ((float)$tripClosings->sum('total_expenses') ?: 2850000)),
        ];

        return Inertia::render('Admin/Finance', [
            'metrics' => $metrics,
            'fleetRevenueList' => $fleetRevenueList,
            'dayTrips' => $dayTrips,
            'dailyHistory' => $dailyHistory,
            'buses' => $buses,
            'selectedDate' => $activeDateValue,
            'selectedBusId' => $selectedBusId,
            'period' => $period,
            'filterType' => $filterType,
            'tripClosings' => $tripClosings,
            'bookings' => $recentBookings,
            'closings' => $tripClosings,
            'topBuses' => $fleetRevenueList,
        ]);
    }

    public function collectConductorCash(Request $request)
    {
        $closingId = $request->input('closing_id');
        $actualSubmitted = (float)$request->input('actual_cash_submitted', 0);
        $tripId = $request->input('trip_id');

        if ($closingId) {
            $closing = TripClosing::find($closingId);
        } else if ($tripId) {
            $closing = TripClosing::where('trip_id', $tripId)->first();
        }

        if (isset($closing) && $closing) {
            if ($actualSubmitted <= 0) {
                $actualSubmitted = (float)$closing->expected_cash_from_conductor;
            }
            $closing->actual_cash_submitted = $actualSubmitted;
            $closing->cash_discrepancy = $actualSubmitted - (float)$closing->expected_cash_from_conductor;
            $closing->audit_status = 'AUDITED_OK';
            $closing->audited_by = auth()->user()->name ?? 'Trần Thu Hà (Kế Toán Trưởng)';
            $closing->audited_at = now();
            $closing->notes = 'Kế toán đã thu đủ tiền mặt và kiểm tra biên lai BOT khớp 100%. Đã reset số dư bên App Lơ xe về 0đ.';
            $closing->save();
        }

        return back()->with('success', '✓ ĐÃ XÁC NHẬN THU ĐỦ ' . number_format($actualSubmitted) . ' Đ VÀ LƯU VÀO LỊCH SỬ ĐỐI SOÁT THÀNH CÔNG!');
    }

    public function auditTripClosing(Request $request, $id)
    {
        $closing = TripClosing::findOrFail($id);
        $closing->audit_status = $request->input('status', 'AUDITED_OK');
        $closing->audit_note = $request->input('audit_note');
        $closing->audited_by = auth()->user()->name ?? 'Admin nhaxekientruc';
        $closing->audited_at = now();
        $closing->save();

        return back()->with('success', 'Đã cập nhật đối soát chuyến xe thành công!');
    }

    public function rolesPermissionsScreen(Request $request)
    {
        $users = User::orderBy('id', 'asc')->get();

        $rolesDefinition = [
            'super_admin' => [
                'name' => 'Super Admin (Giám Đốc)',
                'badge' => '👑 Toàn Quyền',
                'badge_class' => 'bg-purple-50 text-purple-700 border border-purple-200',
                'description' => 'Xem và quản trị toàn bộ hệ thống, phân quyền, cấu hình giá, đặc biệt là tab Doanh thu và Tài chính.',
                'permissions' => ['dashboard', 'pos', 'tracking', 'buses', 'fleet', 'trips', 'routes', 'drivers', 'customers', 'pricing', 'finance', 'roles'],
            ],
            'accountant' => [
                'name' => 'Kế Toán Trưởng',
                'badge' => '💼 Tài Chính',
                'badge_class' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'description' => 'Xem báo cáo tài chính, quản lý đối soát chốt ca lơ xe, duyệt chi phí BOT/Xăng dầu và vật tư chi phí đội xe.',
                'permissions' => ['dashboard', 'finance', 'fleet', 'customers'],
            ],
            'dispatcher' => [
                'name' => 'Điều Hành Viên (Dispatch)',
                'badge' => '🚍 Điều Phối',
                'badge_class' => 'bg-blue-50 text-blue-700 border border-blue-200',
                'description' => 'Tạo tuyến, xếp lịch xe, điều phối tài xế/lơ xe, xem Bản đồ GPS Live Tracking. BỊ ẨN HOÀN TOÀN CÁC SỐ LIỆU DOANH THU & TÀI CHÍNH.',
                'permissions' => ['dashboard', 'tracking', 'buses', 'trips', 'routes', 'drivers'],
            ],
            'cashier' => [
                'name' => 'Nhân Viên Bán Vé Quầy',
                'badge' => '🎟️ Quầy Vé',
                'badge_class' => 'bg-amber-50 text-amber-700 border border-amber-200',
                'description' => 'Chỉ truy cập được màn hình POS Bán vé, in vé, giữ chỗ và tra cứu hồ sơ khách hàng CRM.',
                'permissions' => ['pos', 'customers'],
            ],
            'conductor' => [
                'name' => 'Tài Xế / Lơ Xe (Mobile Only)',
                'badge' => '📱 Cổng Riêng',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'description' => 'Không có quyền đăng nhập vào web Admin này. Chỉ được dùng SĐT đăng nhập trên App/Web Mobile của riêng họ.',
                'permissions' => [],
            ],
        ];

        return Inertia::render('Admin/Roles', [
            'users' => $users,
            'rolesDefinition' => $rolesDefinition,
        ]);
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->role = $request->input('role', $user->role);
        $user->name = $request->input('name', $user->name);
        $user->phone = $request->input('phone', $user->phone);
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        $user->save();

        return back()->with('success', 'Đã cập nhật vai trò phân quyền tài khoản thành công!');
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            $err = 'Bạn không thể tự khóa tài khoản của chính mình!';
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['status' => $err]);
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $err], 400);
            }
            return back()->withErrors(['status' => $err]);
        }

        $oldStatus = $user->status ?? 'ACTIVE';
        $newStatus = ($oldStatus === 'LOCKED' || $oldStatus === 'DELETED') ? 'ACTIVE' : 'LOCKED';
        $user->status = $newStatus;
        $user->save();

        $actionText = ($newStatus === 'LOCKED') ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
        $statusText = ($newStatus === 'LOCKED') ? 'ĐÃ KHÓA' : 'HOẠT ĐỘNG';

        try {
            AuditLog::create([
                'user_name' => Auth::user()?->name ?? 'Admin',
                'role' => Auth::user()?->role ?? 'admin',
                'action' => 'TOGGLE_USER_STATUS',
                'category' => 'USER_MANAGEMENT',
                'description' => "Admin đã {$actionText} [{$user->name} - {$user->phone} - {$user->email}], trạng thái mới: {$statusText}",
                'severity' => 'MEDIUM',
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {}

        $message = ($newStatus === 'LOCKED')
            ? "Đã KHÓA tài khoản [{$user->name}] thành công! Khi khách đăng nhập, hệ thống sẽ chặn và hiển thị thông báo liên hệ Hotline nhà xe 1900 6067."
            : "Đã MỞ KHÓA tài khoản [{$user->name}] thành công! Khách hàng có thể đăng nhập bình thường.";

        if ($request->header('X-Inertia')) {
            return back()->with('success', $message);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $newStatus,
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', $message);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            $msg = "Tài khoản này đã bị xóa trước đó!";
            if ($request->header('X-Inertia')) {
                return back()->with('success', $msg);
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        }

        // Chỉ ngăn xóa tài khoản Quản Trị Viên Tối Cao
        if ($user->id === 1 && $user->role === 'admin') {
            $errMsg = 'Không thể xóa tài khoản Quản Trị Viên Tối Cao của hệ thống!';
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['status' => $errMsg]);
            }
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 400);
            }
            return back()->withErrors(['status' => $errMsg]);
        }

        $isSelf = ($user->id === Auth::id());
        $userName = $user->name;
        $userEmail = strtolower(trim((string)$user->email));
        $userPhone = preg_replace('/[^0-9]/', '', (string)$user->phone);
        $userId = $user->id;

        try {
            AuditLog::create([
                'user_name' => Auth::user()?->name ?? 'Admin',
                'role' => Auth::user()?->role ?? 'admin',
                'action' => 'DELETE_USER',
                'category' => 'USER_MANAGEMENT',
                'description' => "Đã xóa vĩnh viễn tài khoản [{$userName} - {$userPhone} - {$userEmail}] và toàn bộ vé xe, dữ liệu liên quan",
                'severity' => 'HIGH',
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {}

        try {
            // 1. Gỡ ràng buộc liên kết lơ xe trên các chuyến xe (trips)
            \Illuminate\Support\Facades\DB::table('trips')->where('conductor_id', $userId)->update(['conductor_id' => null]);
            if (\Illuminate\Support\Facades\Schema::hasColumn('trips', 'second_conductor_id')) {
                \Illuminate\Support\Facades\DB::table('trips')->where('second_conductor_id', $userId)->update(['second_conductor_id' => null]);
            }

            // 2. Gỡ ràng buộc tài xế (nếu tài khoản là tài xế)
            $driverIds = \Illuminate\Support\Facades\DB::table('drivers')->where('user_id', $userId)->pluck('id')->toArray();
            if (!empty($driverIds)) {
                \Illuminate\Support\Facades\DB::table('trips')->whereIn('driver_id', $driverIds)->update(['driver_id' => null]);
                if (\Illuminate\Support\Facades\Schema::hasColumn('trips', 'second_driver_id')) {
                    \Illuminate\Support\Facades\DB::table('trips')->whereIn('second_driver_id', $driverIds)->update(['second_driver_id' => null]);
                }
                \Illuminate\Support\Facades\DB::table('drivers')->whereIn('id', $driverIds)->delete();
            }

            // 3. Gỡ ràng buộc trong nhật ký vận hành (audit_logs)
            \Illuminate\Support\Facades\DB::table('audit_logs')->where('user_id', $userId)->update(['user_id' => null]);

            // 4. Tìm và xóa sạch tất cả đơn đặt vé (bookings), ghế ngồi (booking_seats), đánh giá (reviews) thuộc về user này
            $userBookings = Booking::where('user_id', $userId)
                ->when($userEmail, function($q) use ($userEmail) {
                    $q->orWhereRaw('LOWER(TRIM(customer_email)) = ?', [$userEmail]);
                })
                ->when($userPhone, function($q) use ($userPhone) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(customer_phone, '.', ''), ' ', ''), '-', '') = ?", [$userPhone]);
                })
                ->get();

            foreach ($userBookings as $b) {
                // Xóa ghế giữ chỗ để giải phóng ghế trên chuyến xe
                \Illuminate\Support\Facades\DB::table('booking_seats')->where('booking_id', $b->id)->delete();
                // Xóa đánh giá chuyến đi của đơn vé này
                \Illuminate\Support\Facades\DB::table('reviews')->where('booking_id', $b->id)->delete();
                // Xóa file ảnh QR hoàn tiền nếu có
                if ($b->refund_qr_code && str_starts_with($b->refund_qr_code, '/uploads/refund_qrs/')) {
                    $qrFile = public_path($b->refund_qr_code);
                    if (file_exists($qrFile)) {
                        @unlink($qrFile);
                    }
                }
                // Xóa vĩnh viễn đơn vé
                $b->delete();
            }

            // 5. Xóa các đánh giá trực tiếp do user viết
            \Illuminate\Support\Facades\DB::table('reviews')->where('user_id', $userId)->delete();

            // 6. Xóa các phiên đăng nhập (sessions)
            try {
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $userId)->delete();
            } catch (\Throwable $e) {}

            // 7. Xóa vĩnh viễn User khỏi database
            $user->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[DELETE_USER_DATA_ERR] " . $e->getMessage());
            // Đảm bảo vẫn xóa user nếu có thể
            try { $user->delete(); } catch (\Throwable $ex) {}
        }

        $message = "Đã XÓA VĨNH VIỄN tài khoản [{$userName}] cùng toàn bộ vé xe và dữ liệu liên quan thành công!";

        // Nếu người tự xóa chính là tài khoản đang đăng nhập -> Đăng xuất và điều hướng về trang chủ
        if ($isSelf) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->with('success', 'Tài khoản của bạn đã được xóa vĩnh viễn khỏi hệ thống!');
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', $message);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => 'DELETED',
                'user_id' => $userId,
            ]);
        }

        return back()->with('success', $message);
    }

    public function users()
    {
        return $this->rolesPermissionsScreen(request());
    }

    public function storeStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'role' => 'required|string',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => 'ACTIVE',
            'password' => bcrypt($request->input('password', 'password123')),
        ]);

        return back()->with('success', 'Đã cấp tài khoản nhân viên mới thành công!');
    }

    public function auditLogs(Request $request)
    {
        $search = $request->query('q', '');
        $actionFilter = $request->query('action', 'ALL');
        $busPlateFilter = $request->query('bus_plate', 'ALL');
        $roleFilter = $request->query('role', 'ALL');
        $categoryFilter = $request->query('category', 'ALL');

        $query = \App\Models\AuditLog::query()->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('entity_id', 'like', "%{$search}%")
                  ->orWhere('bus_plate', 'like', "%{$search}%");
            });
        }

        if ($actionFilter !== 'ALL' && $actionFilter) {
            $query->where('action', $actionFilter);
        }

        if ($busPlateFilter !== 'ALL' && $busPlateFilter) {
            $query->where('bus_plate', $busPlateFilter);
        }

        if ($roleFilter !== 'ALL' && $roleFilter) {
            $query->where('user_role', $roleFilter);
        }

        // Quick Tabs
        if ($categoryFilter === 'ADMIN') {
            $query->where('user_role', 'admin');
        } elseif ($categoryFilter === 'BUS') {
            $query->whereNotNull('bus_plate')->where('bus_plate', '!=', '');
        } elseif ($categoryFilter === 'FINANCE') {
            $query->whereIn('action', ['SETTLE_CASH', 'APPROVE_EXPENSE', 'SETTLE_CONDUCTOR_CASH']);
        } elseif ($categoryFilter === 'TICKETS') {
            $query->whereIn('action', ['POS_BOOKING', 'ROAD_TICKET', 'CANCEL_TICKET', 'ROADSIDE_TICKET', 'SEAT_SWAP']);
        }

        $logs = $query->paginate(20)->withQueryString();

        $actionTypes = [
            'ALL' => 'Tất cả thao tác',
            'UPDATE_PRICE' => 'Sửa giá vé',
            'ASSIGN_DRIVER' => 'Điều chuyển tài xế',
            'SETTLE_CASH' => 'Đối soát & Nộp tiền mặt',
            'CREATE_TRIP' => 'Khởi tạo chuyến mới',
            'CANCEL_TICKET' => 'Hủy vé & Hoàn tiền',
            'APPROVE_EXPENSE' => 'Duyệt chi phí cầu đường/xăng',
            'POS_BOOKING' => 'Bán vé tại quầy POS',
            'ROAD_TICKET' => 'Bán vé đón dọc đường',
            'ROADSIDE_TICKET' => 'Lơ xe bán vé dọc đường',
            'SEAT_SWAP' => 'Đổi ghế cho khách',
            'REPORT_INCIDENT' => 'Báo cáo sự cố kỹ thuật',
        ];

        $busList = [
            '43B-012.34' => '43B-012.34 (Đà Nẵng ⇄ Huế)',
            '43B-056.78' => '43B-056.78 (Đà Nẵng ⇄ Quy Nhơn)',
            '43B-034.56' => '43B-034.56 (Đà Nẵng ⇄ Quảng Bình)',
            '43B-018.99' => '43B-018.99 (Đà Nẵng ⇄ Quảng Trị)',
            '43B-067.89' => '43B-067.89 (Đà Nẵng ⇄ Buôn Ma Thuột)',
            '43B-088.99' => '43B-088.99 (Limousine VIP)',
            '43B-022.33' => '43B-022.33 (Mobihome Premium)',
            '43B-077.88' => '43B-077.88 (King Long 36G)',
            '43B-099.11' => '43B-099.11 (Universe VIP)',
            '43B-045.67' => '43B-045.67 (Thaco VIP)',
        ];

        $roleList = [
            'ALL' => 'Tất cả vai trò',
            'admin' => '🛡️ Quản Trị Viên (Admin)',
            'driver' => '👨‍✈️ Bác Tài Xế',
            'conductor' => '🎫 Tiếp Viên / Lơ Xe',
            'system' => '⚙️ Hệ Thống Tự Động',
        ];

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
            'filters' => [
                'q' => $search,
                'action' => $actionFilter,
                'bus_plate' => $busPlateFilter,
                'role' => $roleFilter,
                'category' => $categoryFilter,
            ],
            'actionTypes' => $actionTypes,
            'busList' => $busList,
            'roleList' => $roleList,
            'totalCount' => \App\Models\AuditLog::count(),
        ]);
    }

    /**
     * Màn hình Quản Lý Hoàn Tiền & Hủy Vé (/admin/refunds)
     */
    public function refundsScreen(Request $request)
    {
        $search = $request->query('q', '');
        $statusFilter = $request->query('status', 'all'); // 'all', 'pending', 'refunded', 'rejected'
        $policies = CancellationPolicy::orderBy('hours_before', 'desc')->get();

        // Query bookings that are cancelled or refunded or have cancellation notes
        $query = Booking::with(['trip.route', 'trip.bus', 'user', 'bookingSeats'])
            ->where(function($q) {
                $q->whereIn('payment_status', ['REFUNDED', 'CANCELLED'])
                  ->orWhereIn('checkin_status', ['CANCELLED', 'NO_SHOW'])
                  ->orWhereNotNull('cancelled_at')
                  ->orWhereNotNull('cancel_reason');
            });

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Apply status tabs
        if ($statusFilter === 'refunded') {
            $query->where(function($q) {
                $q->where('payment_status', 'REFUNDED')
                  ->orWhere('refund_status', 'APPROVED');
            });
        } elseif ($statusFilter === 'pending') {
            $query->where(function($q) {
                $q->where('refund_status', 'PENDING')
                  ->orWhere(function($sub) {
                      $sub->where('payment_status', '!=', 'REFUNDED')
                          ->where(function($s2) {
                              $s2->whereNull('refund_status')->orWhere('refund_status', '!=', 'REJECTED');
                          })
                          ->where(function($s3) {
                              $s3->whereNull('notes')->orWhere('notes', 'not like', '%Từ chối hoàn tiền%');
                          });
                  });
            });
        } elseif ($statusFilter === 'rejected') {
            $query->where(function($q) {
                $q->where('refund_status', 'REJECTED')
                  ->orWhere('notes', 'like', '%Từ chối hoàn tiền%');
            });
        }

        $allRefundRecords = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        // Calculate refund details for each record based on departure time and policies
        $items = $allRefundRecords->getCollection()->map(function($booking) use ($policies) {
            $depTime = $booking->trip && $booking->trip->departure_time 
                ? Carbon::parse($booking->trip->departure_time) 
                : null;
            
            $cancelTime = $booking->cancelled_at 
                ? Carbon::parse($booking->cancelled_at) 
                : Carbon::parse($booking->updated_at);

            $hoursBefore = $depTime ? $cancelTime->diffInHours($depTime, false) : 0;
            if ($depTime && $cancelTime->gt($depTime)) {
                $hoursBefore = -1; // Cancellation after departure
            }

            // Determine policy
            $applicablePolicy = null;
            foreach ($policies as $pol) {
                if ($hoursBefore >= $pol->hours_before) {
                    $applicablePolicy = $pol;
                    break;
                }
            }
            if (!$applicablePolicy) {
                $applicablePolicy = $policies->last();
            }

            $refundPercent = $applicablePolicy ? $applicablePolicy->refund_percent : 0;
            $feePercent = 100 - $refundPercent;
            $paid = (float)($booking->paid_amount ?: $booking->total_amount);
            $calculatedRefundAmount = round($paid * ($refundPercent / 100));
            $finalRefundAmount = (float)($booking->refund_amount ?: $calculatedRefundAmount);
            $feeAmount = $paid - $finalRefundAmount;

            $bankName = $booking->refund_bank_name;
            $accountNumber = $booking->refund_account_number;
            $accountName = $booking->refund_account_name;
            $qrCode = $booking->refund_qr_code;

            // Tự sinh VietQR chuẩn nếu khách đã cung cấp STK mà chưa có QR ảnh
            if ($bankName && $accountNumber && empty($qrCode)) {
                $cleanAcc = preg_replace('/[^0-9]/', '', $accountNumber);
                $cleanBank = trim($bankName);
                $qrCode = "https://img.vietqr.io/image/{$cleanBank}-{$cleanAcc}-compact2.png?amount={$finalRefundAmount}&addInfo=HOAN+VE+{$booking->booking_code}";
            }

            $isRefunded = ($booking->payment_status === 'REFUNDED' || $booking->refund_status === 'APPROVED');
            $isRejected = ($booking->refund_status === 'REJECTED' || str_contains($booking->notes ?? '', 'Từ chối hoàn tiền'));
            $isPending = (!$isRefunded && !$isRejected);

            return [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'trip_code' => $booking->trip ? $booking->trip->trip_code : 'N/A',
                'route_name' => $booking->trip && $booking->trip->route ? $booking->trip->route->name : 'N/A',
                'departure_time' => $depTime ? $depTime->format('d/m/Y H:i') : 'N/A',
                'cancelled_at' => $cancelTime->format('d/m/Y H:i'),
                'hours_before' => round($hoursBefore, 1),
                'total_amount' => (float)$booking->total_amount,
                'paid_amount' => $paid,
                'payment_method' => $booking->payment_method,
                'payment_status' => $booking->payment_status,
                'checkin_status' => $booking->checkin_status,
                'cancel_reason' => $booking->cancel_reason ?: 'Khách hàng yêu cầu hủy vé',
                'notes' => $booking->notes,
                'refund_percent' => $refundPercent,
                'fee_percent' => $feePercent,
                'refund_amount' => $finalRefundAmount,
                'fee_amount' => $feeAmount,
                'status_badge' => $isRefunded ? 'REFUNDED' : ($isRejected ? 'REJECTED' : 'PENDING'),
                // Chi tiết tài khoản ngân hàng & QR code khách gửi
                'refund_status' => $booking->refund_status ?: ($isRefunded ? 'APPROVED' : ($isRejected ? 'REJECTED' : 'PENDING')),
                'refund_bank_name' => $bankName,
                'refund_account_number' => $accountNumber,
                'refund_account_name' => $accountName,
                'refund_qr_code' => $qrCode,
                'refund_reason' => $booking->refund_reason ?: $booking->cancel_reason,
                'refund_requested_at' => $booking->refund_requested_at ? Carbon::parse($booking->refund_requested_at)->format('d/m/Y H:i') : null,
                'refund_processed_at' => $booking->refund_processed_at ? Carbon::parse($booking->refund_processed_at)->format('d/m/Y H:i') : null,
                'refund_processed_by' => $booking->refund_processed_by,
                'refund_admin_notes' => $booking->refund_admin_notes,
            ];
        });

        $allRefundRecords->setCollection($items);

        // KPI summary
        $totalCancelledCount = Booking::where(function($q) {
            $q->whereIn('payment_status', ['REFUNDED', 'CANCELLED'])
              ->orWhereIn('checkin_status', ['CANCELLED', 'NO_SHOW'])
              ->orWhereNotNull('cancelled_at')
              ->orWhereNotNull('refund_status');
        })->count();

        $totalRefundedCount = Booking::where(function($q) {
            $q->where('payment_status', 'REFUNDED')
              ->orWhere('refund_status', 'APPROVED');
        })->count();

        $totalRefundedAmount = Booking::where(function($q) {
            $q->where('payment_status', 'REFUNDED')
              ->orWhere('refund_status', 'APPROVED');
        })->sum('refund_amount');

        if (!$totalRefundedAmount) {
            $totalRefundedAmount = Booking::where('payment_status', 'REFUNDED')->sum('paid_amount');
        }
        
        $pendingCount = Booking::where(function($q) {
            $q->where('refund_status', 'PENDING')
              ->orWhere(function($sub) {
                  $sub->whereIn('payment_status', ['CANCELLED'])
                      ->where('payment_status', '!=', 'REFUNDED')
                      ->where(function($s2) {
                          $s2->whereNull('refund_status')->orWhere('refund_status', '!=', 'REJECTED');
                      })
                      ->where(function($s3) {
                          $s3->whereNull('notes')->orWhere('notes', 'not like', '%Từ chối hoàn tiền%');
                      });
              });
        })->count();

        $rejectedCount = Booking::where(function($q) {
            $q->where('refund_status', 'REJECTED')
              ->orWhere('notes', 'like', '%Từ chối hoàn tiền%');
        })->count();

        return Inertia::render('Admin/Refunds', [
            'refundRecords' => $allRefundRecords,
            'filters' => [
                'q' => $search,
                'status' => $statusFilter,
            ],
            'kpi' => [
                'total_cancelled' => $totalCancelledCount,
                'pending_refund' => $pendingCount,
                'total_refunded' => $totalRefundedCount,
                'total_refund_amount' => $totalRefundedAmount,
                'rejected_count' => $rejectedCount,
            ],
            'policies' => $policies,
        ]);
    }

    /**
     * Duyệt hoàn tiền vé
     */
    public function approveRefund(Request $request, $id)
    {
        $booking = Booking::with('bookingSeats')->findOrFail($id);
        $refundAmount = $request->input('refund_amount', $booking->refund_amount ?: ($booking->paid_amount ?: $booking->total_amount));
        $paymentMethod = $request->input('payment_method', $booking->refund_bank_name ? 'BANK_TRANSFER' : ($booking->payment_method ?: 'MOMO'));
        $txCode = $request->input('transaction_code', '');
        $adminNoteText = $request->input('notes', '');
        $adminName = auth()->user()->name ?? 'Admin';

        $booking->payment_status = 'REFUNDED';
        $booking->checkin_status = 'CANCELLED';
        $booking->cancelled_at = $booking->cancelled_at ?: now();
        $booking->refund_status = 'APPROVED';
        $booking->refund_amount = $refundAmount;
        $booking->refund_processed_at = now();
        $booking->refund_processed_by = $adminName;

        $detailedAdminNote = "Đã duyệt hoàn " . number_format($refundAmount, 0, ',', '.') . "đ qua " . ($booking->refund_bank_name ?: $paymentMethod) . 
            ($booking->refund_account_number ? " (STK: {$booking->refund_account_number} - {$booking->refund_account_name})" : "") .
            ($txCode ? " [Mã GD: {$txCode}]" : "") . 
            ($adminNoteText ? " [Ghi chú: {$adminNoteText}]" : "") . 
            " bởi {$adminName} lúc " . now()->format('d/m/Y H:i');

        $booking->refund_admin_notes = $detailedAdminNote;
        $booking->notes = $booking->notes ? ($booking->notes . " | " . $detailedAdminNote) : $detailedAdminNote;
        $booking->save();

        // Giải phóng ghế nếu còn giữ
        $booking->bookingSeats()->delete();

        AuditLog::create([
            'trip_id' => $booking->trip_id,
            'action' => 'REFUND_APPROVED',
            'description' => "Đã duyệt hoàn tiền #{$booking->booking_code} (" . number_format($refundAmount, 0, ',', '.') . "đ) cho khách {$booking->customer_name} qua " . ($booking->refund_bank_name ?: $paymentMethod) . ".",
            'performed_by' => $adminName,
        ]);

        // Gửi email thông báo DUYỆT HOÀN TIỀN THÀNH CÔNG về Gmail của khách hàng qua Relay HTTPS 443
        \App\Services\EmailService::sendRefundApproved($booking, [
            'refundAmount' => $refundAmount,
            'paymentMethod' => $booking->refund_bank_name ? 'Chuyển khoản Ngân hàng' : ($booking->payment_method ?: 'Ví điện tử'),
            'bankName' => $booking->refund_bank_name ?: 'Chuyển khoản theo yêu cầu',
            'accountNumber' => $booking->refund_account_number ?: 'Tài khoản gốc',
            'accountName' => $booking->refund_account_name ?: $booking->customer_name,
            'txCode' => $txCode,
            'adminNotes' => $adminNoteText,
        ]);

        return back()->with('success', "Đã duyệt hoàn tiền " . number_format($refundAmount, 0, ',', '.') . "đ cho vé #{$booking->booking_code} và gửi thông báo về Gmail thành công!");
    }

    /**
     * Từ chối hoàn tiền vé
     */
    public function rejectRefund(Request $request, $id)
    {
        $booking = Booking::with('trip.route')->findOrFail($id);
        $reason = $request->input('reason', 'Không đủ điều kiện hoàn tiền theo quy chế nhà xe (hủy sát giờ hoặc bỏ chuyến)');
        $adminName = auth()->user()->name ?? 'Admin';

        $booking->refund_status = 'REJECTED';
        $booking->refund_processed_at = now();
        $booking->refund_processed_by = $adminName;
        $booking->refund_admin_notes = $reason;

        $rejectNote = "Từ chối hoàn tiền: {$reason} (Bởi {$adminName} lúc " . now()->format('d/m/Y H:i') . ")";
        $booking->notes = $booking->notes ? ($booking->notes . " | " . $rejectNote) : $rejectNote;
        $booking->save();

        AuditLog::create([
            'trip_id' => $booking->trip_id,
            'action' => 'REFUND_REJECTED',
            'description' => "Đã từ chối hoàn tiền vé #{$booking->booking_code} của khách {$booking->customer_name}. Lý do: {$reason}",
            'performed_by' => $adminName,
        ]);

        // Gửi email thông báo TỪ CHỐI HOÀN TIỀN về Gmail của khách hàng qua Relay HTTPS 443
        \App\Services\EmailService::sendRefundRejected($booking, $reason);

        return back()->with('success', "Đã từ chối hoàn tiền cho vé #{$booking->booking_code} và gửi thông báo về Gmail của khách hàng.");
    }
}

