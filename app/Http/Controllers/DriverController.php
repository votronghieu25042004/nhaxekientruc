<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Bus;
use App\Models\Route;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Incident;
use App\Models\TripExpense;
use App\Models\TripClosing;
use App\Models\LostFound;
use App\Models\AuditLog;
use App\Models\Review;
use App\Services\SeatSegmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    /**
     * Màn hình chính App Tài xế (Đầy đủ nghiệp vụ lái xe + Sơ đồ ghế + Đón khách + Check-in như Lơ xe)
     */
    public function index(Request $request)
    {
        \App\Services\TripScheduleService::ensureUpcomingTripsExist(3);
        Trip::syncRealTimeStatuses();
        $user = Auth::user();
        // Xác định quyền hiển thị menu chuyển đổi cổng & chọn tài khoản:
        // - Mặc định luôn là TRUE (để Admin/Dev trải nghiệm, đổi cổng thông tin và chọn các acc để test).
        // - Chỉ chuyển thành FALSE khi: tài khoản đăng nhập có role === 'driver' VÀ được đánh dấu 'is_real_driver_login' từ form /login VÀ không có cờ admin.
        $isAdminViewing = true;
        if ($user && $user->role === 'driver' && session('is_real_driver_login') === true && !session('admin_mode') && !$request->has('admin_view')) {
            $isAdminViewing = false;
        }

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $next7Days = Carbon::today()->addDays(7);

        $drivers = Driver::orderBy('id', 'asc')->get();
        
        if ($isAdminViewing) {
            // Admin có quyền chọn xem bất kỳ tài xế nào (Lưu session để không bị reset khi đổi ngày/tab)
            if ($request->filled('driver_id')) {
                session(['admin_selected_driver_id' => $request->input('driver_id')]);
            }
            $driverId = $request->input('driver_id') ?: session('admin_selected_driver_id');
            if ($driverId) {
                $currentDriver = Driver::find($driverId);
            } else {
                $currentDriver = $drivers->firstWhere('name', 'Bùi Tiến Dũng') ?? $drivers->first();
            }
            if (!$currentDriver) {
                $currentDriver = $drivers->first();
            }
            if ($currentDriver) {
                session(['admin_selected_driver_id' => $currentDriver->id]);
            }
        } else {
            // Tài xế đăng nhập thật: Khóa cứng chỉ xem đúng tài khoản của mình
            $currentDriver = Driver::where('user_id', $user->id)->first()
                ?? Driver::where('phone', $user->phone)->first()
                ?? Driver::where('email', $user->email)->first()
                ?? $drivers->first();
        }

        // Bổ sung thông tin số chuyến và biển số xe cho danh sách dropdown chọn tài xế
        $driversWithStats = $drivers->map(function ($d) {
            $busPlate = Trip::where('driver_id', $d->id)->with('bus')->latest()->first()?->bus?->license_plate;
            $todayTripsCount = Trip::where('driver_id', $d->id)->whereDate('departure_time', Carbon::today())->count();
            return array_merge($d->toArray(), [
                'bus_plate' => $busPlate,
                'today_trips_count' => $todayTripsCount,
            ]);
        });

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $next7Days = Carbon::today()->addDays(7);

        // Danh sách chuyến xe của tài xế này
        $query = Trip::with([
            'route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'),
            'bus',
            'conductor',
            'driver',
            'secondDriver',
            'bookings.bookingSeats',
            'bookings.pickupStop',
            'bookings.dropoffStop',
            'expenses',
            'closing',
            'incidents',
            'lostFounds'
        ])
        ->where(function($q) use ($currentDriver) {
            $did = $currentDriver ? $currentDriver->id : 0;
            $q->where('driver_id', $did)->orWhere('second_driver_id', $did);
        })
        ->orderBy('departure_time', 'asc');

        $allDriverTrips = (clone $query)->get();

        // Gắn thống kê tải trọng thực tế từ danh sách vé đặt (Bảo đảm đồng bộ 100% giữa Dashboard và Sơ đồ ghế)
        $attachTripStats = function($t) {
            $stats = $t->calculateAccurateStats();
            foreach ($stats as $k => $v) {
                $t->{$k} = $v;
            }
            return $t;
        };

        $allDriverTrips->each($attachTripStats);

        // Tối ưu payload: Ẩn các quan hệ nặng khi serialize sang frontend (giảm ~2.5MB mỗi lần tải trang).
        // Dữ liệu chi tiết của chuyến được chọn đã được tính sẵn ở seatMap/stationPickups/checkinHistory...
        $allDriverTrips->each(function ($t) {
            $t->makeHidden(['bookings', 'expenses', 'closing', 'incidents', 'lostFounds']);
        });

        // Phân loại chuyến: Chuẩn xác theo ngày khởi hành thực tế
        $todayTrips = $allDriverTrips->filter(function($t) use ($today) {
            return Carbon::parse($t->departure_time)->isSameDay($today);
        })->values();

        $tomorrowTrips = $allDriverTrips->filter(function($t) use ($tomorrow) {
            return Carbon::parse($t->departure_time)->isSameDay($tomorrow);
        })->values();

        $upcomingTrips = $allDriverTrips->filter(function($t) use ($today, $next7Days) {
            $dep = Carbon::parse($t->departure_time);
            return $dep->gt($today) && $dep->lte($next7Days);
        })->values();

        $historyTrips = $allDriverTrips->filter(function($t) {
            return in_array($t->status, ['COMPLETED', 'ARRIVED', 'CLOSED']) || Carbon::parse($t->departure_time)->lt(Carbon::today());
        })->values();

        // Selected Trip chi tiết
        $selectedTripId = $request->input('trip_id');
        $selectedTrip = $selectedTripId 
            ? $allDriverTrips->firstWhere('id', $selectedTripId) 
            : ($todayTrips->firstWhere('status', 'IN_TRANSIT') ?? $todayTrips->firstWhere('status', 'BOARDING') ?? $todayTrips->firstWhere('status', 'OPEN_FOR_SALE') ?? $todayTrips->first() ?? $allDriverTrips->first());

        $occupiedSeats = [];
        $stops = [];
        $seatMap = [];
        $stationPickups = [];
        $roadsidePickups = [];
        $stationDropoffs = [];
        $checkinHistory = [];
        $checkinStats = [];
        $financeSummary = [];

        if ($selectedTrip) {
            $stops = $selectedTrip->route ? $selectedTrip->route->stops->sortBy('stop_order')->values() : collect();
            $curOrder = $selectedTrip->current_stop_order ?? 0;
            $nextOrder = min($curOrder + 1, $stops->max('stop_order') ?? 1);
            $occupiedSeats = SeatSegmentService::getAllBookedSeatsForTrip($selectedTrip->id);

            $bookings = $selectedTrip->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])->whereNotIn('checkin_status', ['CANCELLED']);

            // 1.1 Gom nhóm đón tại Điểm cố định
            foreach ($stops as $stop) {
                $stopBookings = $bookings->where('pickup_stop_id', $stop->id);
                $paxList = [];
                $boardedCount = 0;
                $waitingCount = 0;

                foreach ($stopBookings as $b) {
                    $seatNums = $b->bookingSeats->pluck('seat_number')->toArray();
                    $isBoarded = ($b->checkin_status === 'CHECKED_IN');
                    $isNoShow = ($b->checkin_status === 'NO_SHOW' || $b->checkin_status === 'CANCELLED');

                    if ($isBoarded) {
                        $boardedCount += count($seatNums) ?: 1;
                    } elseif (!$isNoShow) {
                        $waitingCount += count($seatNums) ?: 1;
                    }

                    $totPrice = (float)($b->total_amount ?? $b->total_price ?? 0);
                    if ($isBoarded) {
                        $paidPrice = $totPrice;
                        $remainingAmount = 0;
                        $paymentStatus = 'PAID';
                    } else {
                        $paidPrice = (float)($b->paid_amount ?? 0);
                        $remainingAmount = max(0, $totPrice - $paidPrice);
                        $paymentStatus = $b->payment_status;
                    }

                    $paxList[] = [
                        'id' => $b->id,
                        'booking_code' => $b->booking_code,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'seats' => $seatNums,
                        'total_amount' => $totPrice,
                        'paid_amount' => $paidPrice,
                        'remaining_amount' => $remainingAmount,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $b->payment_method ?? 'ONLINE',
                        'checkin_status' => $b->checkin_status,
                        'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                        'notes' => $b->notes,
                        'pickup_stop_name' => $stop->stop_name,
                        'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                        'is_roadside' => (str_contains(mb_strtolower($b->notes ?? ''), 'dọc đường') || str_contains(mb_strtolower($b->notes ?? ''), 'kcn') || str_contains(mb_strtolower($b->notes ?? ''), 'cây xăng') || $b->booking_source === 'ROADSIDE_ON_BUS')
                    ];
                }

                $stationPickups[] = [
                    'stop_id' => $stop->id,
                    'stop_name' => $stop->stop_name,
                    'stop_order' => $stop->stop_order,
                    'address' => $stop->address,
                    'planned_time' => $stop->planned_time,
                    'passengers' => $paxList,
                    'total_pax' => count($paxList),
                    'boarded_count' => $boardedCount,
                    'waiting_count' => $waitingCount,
                ];
            }

            // 1.2 Lọc riêng khách Đón Dọc Đường
            foreach ($bookings as $b) {
                $noteLower = mb_strtolower($b->notes ?? '');
                $isRoadside = str_contains($noteLower, 'dọc đường') 
                    || str_contains($noteLower, 'kcn') 
                    || str_contains($noteLower, 'cây xăng') 
                    || str_contains($noteLower, 'cầu vượt') 
                    || str_contains($noteLower, 'ngã ba')
                    || $b->booking_source === 'ROADSIDE_ON_BUS';

                if ($isRoadside) {
                    $seatNums = $b->bookingSeats->pluck('seat_number')->toArray();
                    $isBoarded = ($b->checkin_status === 'CHECKED_IN');
                    $totPrice = (float)($b->total_amount ?? $b->total_price ?? 0);
                    if ($isBoarded) {
                        $paidPrice = $totPrice;
                        $remainingAmount = 0;
                        $paymentStatus = 'PAID';
                    } else {
                        $paidPrice = (float)($b->paid_amount ?? 0);
                        $remainingAmount = max(0, $totPrice - $paidPrice);
                        $paymentStatus = $b->payment_status;
                    }

                    $roadsidePickups[] = [
                        'id' => $b->id,
                        'booking_code' => $b->booking_code,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'seats' => $seatNums,
                        'total_amount' => $totPrice,
                        'paid_amount' => $paidPrice,
                        'remaining_amount' => $remainingAmount,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $b->payment_method ?? 'ONLINE',
                        'checkin_status' => $b->checkin_status,
                        'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                        'notes' => $b->notes ?: 'Đón dọc đường theo lộ trình',
                        'pickup_stop_name' => $b->pickupStop ? $b->pickupStop->stop_name : 'Dọc đường',
                        'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                    ];
                }
            }

            // 1.3 Thống kê Lộ trình 3 Trạm Cố Định Chuẩn Mực cho Bác tài:
            // Trạm 1: Khởi Hành (Bến xe xuất bến)
            // Trạm 2: Trạm Dừng Nghỉ Chân Dọc Tuyến (Nghỉ 20-30 phút ăn uống, vệ sinh & trả khách dọc đường nếu có)
            // Trạm 3: BẾN XE ĐÍCH ĐẾN CUỐI CÙNG (100% Khách Xuống Xe tại Bến Xe)
            $firstStop = $stops->first();
            $lastStop = $stops->last();

            // Khách trả tại bến xe đích đến cuối cùng
            $finalDropBookings = $bookings->filter(function($b) use ($lastStop) {
                return empty($b->dropoff_stop_id) || $b->dropoff_stop_id == $lastStop?->id || ($b->dropoff_order ?? 99) >= 3;
            });
            $finalDropPax = [];
            foreach ($finalDropBookings as $b) {
                $finalDropPax[] = [
                    'id' => $b->id,
                    'customer_name' => $b->customer_name,
                    'customer_phone' => $b->customer_phone,
                    'seats' => $b->bookingSeats->pluck('seat_number')->toArray(),
                    'checkin_status' => $b->checkin_status,
                ];
            }

            // Khách trả dọc đường trung gian (nếu có khách đăng ký xuống trước bến)
            $midDropBookings = $bookings->filter(function($b) use ($firstStop, $lastStop) {
                return !empty($b->dropoff_stop_id) && $b->dropoff_stop_id != $firstStop?->id && $b->dropoff_stop_id != $lastStop?->id && ($b->dropoff_order ?? 0) < 3;
            });
            $midDropPax = [];
            foreach ($midDropBookings as $b) {
                $midDropPax[] = [
                    'id' => $b->id,
                    'customer_name' => $b->customer_name,
                    'customer_phone' => $b->customer_phone,
                    'seats' => $b->bookingSeats->pluck('seat_number')->toArray(),
                    'checkin_status' => $b->checkin_status,
                ];
            }

            // Tên trạm dừng nghỉ chân ăn uống phù hợp tuyến
            $midStopName = 'Trạm Dừng Nghỉ Chân Ăn Uống & Vệ Sinh (20-30p)';
            $destLower = mb_strtolower($selectedTrip->route?->destination ?? '');
            if (str_contains($destLower, 'huế')) {
                $midStopName = 'Trạm Dừng Nghỉ Đèo Hải Vân / Lăng Cô (20-30p)';
            } elseif (str_contains($destLower, 'quảng trị')) {
                $midStopName = 'Trạm Dừng Nghỉ Cửa Ngõ Nam Đông Hà (20-30p)';
            } elseif (str_contains($destLower, 'quảng bình') || str_contains($destLower, 'đồng hới')) {
                $midStopName = 'Trạm Dừng Nghỉ Đông Hà / Lệ Thủy (20-30p)';
            } elseif (str_contains($destLower, 'quy nhơn')) {
                $midStopName = 'Trạm Dừng Nghỉ Sa Huỳnh / Quảng Ngãi (20-30p)';
            } elseif (str_contains($destLower, 'buôn ma thuột') || str_contains($destLower, 'đắk lắk')) {
                $midStopName = 'Trạm Dừng Nghỉ Đèo An Khê / QL14 (20-30p)';
            }

            $totalDist = (float)($selectedTrip->route?->distance_km ?? 180);
            $totalMins = (int)round(($selectedTrip->route?->estimated_hours ?? 3.5) * 60);

            $stationDropoffs = [
                [
                    'stop_id' => $firstStop?->id ?? 1,
                    'stop_order' => 1,
                    'type' => 'ORIGIN',
                    'stop_name' => $firstStop?->stop_name ?? ($selectedTrip->route?->origin ?? 'Bến xe Trung tâm Đà Nẵng'),
                    'address' => $firstStop?->address ?? 'Điểm xuất phát ban đầu của xe',
                    'distance_km' => 0,
                    'estimated_minutes' => 0,
                    'passengers' => [],
                    'total_pax' => ($selectedTrip->occupied_count ?? count($finalDropPax)),
                ],
                [
                    'stop_id' => 999,
                    'stop_order' => 2,
                    'type' => 'REST_STOP',
                    'stop_name' => $midStopName,
                    'address' => 'Trạm dừng chân ăn uống, nghỉ ngơi & vệ sinh (Nghỉ 20-30 phút)',
                    'distance_km' => round($totalDist * 0.45),
                    'estimated_minutes' => round($totalMins * 0.45),
                    'passengers' => $midDropPax,
                    'total_pax' => count($midDropPax),
                ],
                [
                    'stop_id' => $lastStop?->id ?? 3,
                    'stop_order' => 3,
                    'type' => 'DESTINATION_BUS_STATION',
                    'stop_name' => $lastStop?->stop_name ?? ('Bến xe ' . ($selectedTrip->route?->destination ?? 'Đích Đến')),
                    'address' => $lastStop?->address ?? 'Bến xe đích đến cuối cùng kết thúc hành trình',
                    'distance_km' => $totalDist,
                    'estimated_minutes' => $totalMins,
                    'passengers' => $finalDropPax,
                    'total_pax' => count($finalDropPax),
                ]
            ];

            // 1.4 Minh bạch Tài chính & Chốt ca (Settlement)
            $activeBookings = $bookings->whereNotIn('checkin_status', ['NO_SHOW', 'CANCELLED']);
            $totalPaxCount = $activeBookings->sum('total_seats') ?: $activeBookings->count();

            $webPaxCount = $activeBookings->where('booking_source', '!=', 'ROADSIDE_ON_BUS')->count();
            $roadsidePaxCount = $activeBookings->where('booking_source', 'ROADSIDE_ON_BUS')->count();

            $onlineBookings = $activeBookings->where('payment_method', '!=', 'CASH')->where('payment_status', 'PAID');
            $onlinePaidAmount = $onlineBookings->sum('paid_amount');
            $onlinePaidCount = $onlineBookings->count();

            $cashBookings = $activeBookings->where('payment_method', 'CASH')->where('payment_status', 'PAID');
            $cashCollectedAmount = $cashBookings->sum('paid_amount');
            $cashCollectedCount = $cashBookings->count();

            // 3. Tiền mặt CÒN THIẾU cần thu (gồm khách chưa trả + khách mới cọc 30-50%, không tính khách đã lên xe vì đã thanh toán 100%)
            $uncollectedBookings = $activeBookings->where('payment_status', '!=', 'PAID')->where('checkin_status', '!=', 'CHECKED_IN');
            $uncollectedAmount = $uncollectedBookings->reduce(fn($carry, $b) => $carry + max(0, (float)($b->total_amount ?? $b->total_price ?? 0) - (float)($b->paid_amount ?? 0)), 0);
            $uncollectedCount = $uncollectedBookings->count();

            $totalExpenses = (float)$selectedTrip->expenses->sum('amount');
            $netCashToRemit = max(0, $cashCollectedAmount - $totalExpenses);

            $financeSummary = [
                'total_passengers' => $selectedTrip->booked_seats_count ?? $totalPaxCount,
                'booked_seats_count' => $selectedTrip->booked_seats_count ?? $totalPaxCount,
                'customer_count' => $selectedTrip->customer_count ?? $activeBookings->count(),
                'boarded_count' => $selectedTrip->boarded_seats_count ?? 0,
                'boarded_seats_count' => $selectedTrip->boarded_seats_count ?? 0,
                'boarded_passengers_count' => $selectedTrip->boarded_passengers_count ?? 0,
                'waiting_count' => $selectedTrip->waiting_seats_count ?? 0,
                'no_show_count' => $selectedTrip->no_show_seats_count ?? 0,
                'web_passengers' => $webPaxCount,
                'roadside_passengers' => $roadsidePaxCount,
                'online_paid_amount' => $onlinePaidAmount,
                'online_paid_count' => $onlinePaidCount,
                'cash_collected_amount' => $cashCollectedAmount,
                'cash_collected_count' => $cashCollectedCount,
                'uncollected_amount' => $uncollectedAmount,
                'uncollected_count' => $uncollectedCount,
                'total_expenses' => $totalExpenses,
                'net_cash_to_remit' => $netCashToRemit,
                'is_closed' => ($selectedTrip->status === 'CLOSED'),
                'closing' => $selectedTrip->closing,
            ];

            // 1.5 Xây dựng Sơ đồ ghế 2 tầng chuẩn (A01-A18 & B01-B18)
            $seatBookingMap = [];
            foreach ($selectedTrip->bookings as $b) {
                // Khách đã Hủy (CANCELLED), Hoàn tiền (REFUNDED) hoặc checkin CANCELLED -> Lập tức giải phóng thành GHẾ TRỐNG (AVAILABLE)
                if ($b->payment_status === 'CANCELLED' || $b->payment_status === 'REFUNDED' || $b->checkin_status === 'CANCELLED') {
                    continue;
                }

                foreach ($b->bookingSeats as $bs) {
                    $seatStatus = 'WEB_BOOKED';
                    if ($b->checkin_status === 'CHECKED_IN') {
                        $seatStatus = 'CHECKED_IN';
                    } elseif ($b->checkin_status === 'NO_SHOW') {
                        $seatStatus = 'NO_SHOW';
                    }

                    $totPrice = (float)($b->total_amount ?? $b->total_price ?? 0);
                    $isBoarded = ($b->checkin_status === 'CHECKED_IN');

                    $seatBookingMap[$bs->seat_number] = [
                        'booking_id' => $b->id,
                        'booking_code' => $b->booking_code,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'status' => $seatStatus,
                        'checkin_status' => $b->checkin_status,
                        'booking_source' => $b->booking_source,
                        'payment_status' => $isBoarded ? 'PAID' : $b->payment_status,
                        'payment_method' => $b->payment_method,
                        'total_amount' => $totPrice,
                        'paid_amount' => $isBoarded ? $totPrice : (float)($b->paid_amount ?? 0),
                        'remaining_amount' => $isBoarded ? 0 : max(0, $totPrice - (float)($b->paid_amount ?? 0)),
                        'pickup_name' => $b->pickupStop ? $b->pickupStop->stop_name : 'Trạm đón',
                        'dropoff_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm trả',
                        'notes' => $b->notes,
                    ];
                }
            }

            for ($i = 1; $i <= 18; $i++) {
                $codeA = 'A' . ($i < 10 ? '0' . $i : $i);
                $codeB = 'B' . ($i < 10 ? '0' . $i : $i);

                $seatMap[$codeA] = isset($seatBookingMap[$codeA])
                    ? array_merge(['seat_number' => $codeA, 'floor' => 1], $seatBookingMap[$codeA])
                    : ['seat_number' => $codeA, 'floor' => 1, 'status' => 'AVAILABLE', 'customer_name' => null];

                $seatMap[$codeB] = isset($seatBookingMap[$codeB])
                    ? array_merge(['seat_number' => $codeB, 'floor' => 2], $seatBookingMap[$codeB])
                    : ['seat_number' => $codeB, 'floor' => 2, 'status' => 'AVAILABLE', 'customer_name' => null];
            }

            // 1.6 Xem Lịch Sử Check-in & Bán Vé Dọc Đường
            $checkedInBookings = $selectedTrip->bookings
                ->whereIn('checkin_status', ['CHECKED_IN', 'NO_SHOW'])
                ->sortByDesc('updated_at');

            $totalQrScanned = 0;
            $totalManualChecked = 0;
            $totalRoadsideSold = 0;

            foreach ($checkedInBookings as $cb) {
                $isRoadside = ($cb->booking_source === 'ROADSIDE_ON_BUS');
                $isQr = ($cb->checkin_method === 'QR_SCAN');
                $isNoShow = ($cb->checkin_status === 'NO_SHOW' || $cb->checkin_status === 'CANCELLED');

                if ($isNoShow) {
                    $type = 'NO_SHOW';
                    $typeLabel = 'Đánh Dấu Bỏ Chuyến';
                } elseif ($isRoadside) {
                    $type = 'ROADSIDE_SALE';
                    $typeLabel = 'Bán Vé Dọc Đường';
                    $totalRoadsideSold++;
                } elseif ($isQr) {
                    $type = 'QR_SCAN';
                    $typeLabel = 'Quét Mã QR';
                    $totalQrScanned++;
                } else {
                    $type = 'MANUAL';
                    $typeLabel = 'Check-in Thủ Công';
                    $totalManualChecked++;
                }

                $checkinHistory[] = [
                    'id' => $cb->id,
                    'booking_code' => $cb->booking_code,
                    'customer_name' => $cb->customer_name,
                    'customer_phone' => $cb->customer_phone,
                    'seats' => $cb->bookingSeats->pluck('seat_number')->toArray(),
                    'type' => $type,
                    'type_label' => $typeLabel,
                    'payment_method' => $cb->payment_method ?? 'CASH',
                    'paid_amount' => (float)($cb->paid_amount ?? 0),
                    'time' => $cb->checked_in_at ? Carbon::parse($cb->checked_in_at)->format('H:i:s d/m') : Carbon::parse($cb->updated_at)->format('H:i:s d/m'),
                ];
            }

            $checkinStats = [
                'total_qr_scanned' => $totalQrScanned,
                'total_manual' => $totalManualChecked,
                'total_roadside_sold' => $totalRoadsideSold,
                'total_cash_collected' => $cashCollectedAmount,
                'total_online_collected' => $onlinePaidAmount,
            ];
        }

        // Lấy danh sách đánh giá của khách hàng dành cho tài xế này
        $driverReviews = Review::where('driver_id', $currentDriver ? $currentDriver->id : 0)
            ->with('booking.trip.route')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hệ thống thông báo thông minh
        $notifications = [
            [
                'id' => 1,
                'type' => 'TRIP_ASSIGNED',
                'title' => 'Chuyến xe mới được phân công',
                'content' => 'Bạn được phân công chuyến Đà Nẵng → Hội An (08:00). Xe 43B-012.34.',
                'time' => '30 phút trước',
                'read' => false
            ],
            [
                'id' => 2,
                'type' => 'REMINDER',
                'title' => 'Nhắc nhở giờ khởi hành',
                'content' => 'Còn 60 phút nữa chuyến xe tiếp theo của bạn sẽ khởi hành. Vui lòng kiểm tra xe!',
                'time' => '1 giờ trước',
                'read' => false
            ],
            [
                'id' => 3,
                'type' => 'LICENSE_WARNING',
                'title' => 'Hạn giấy phép lái xe Hạng E',
                'content' => 'GPLX số ' . ($currentDriver->license_number ?? 'B2-99887766') . ' còn hạn đến 12/2027.',
                'time' => '1 ngày trước',
                'read' => true
            ]
        ];

        return Inertia::render('Driver/Index', [
            'drivers' => $driversWithStats,
            'currentDriver' => $currentDriver,
            'selectedTrip' => $selectedTrip,
            'todayTrips' => $todayTrips,
            'tomorrowTrips' => $tomorrowTrips,
            'upcomingTrips' => $upcomingTrips,
            'historyTrips' => $historyTrips,
            'allTrips' => $allDriverTrips,
            'occupiedSeats' => $occupiedSeats,
            'stops' => $stops,
            'seatMap' => $seatMap,
            'stationPickups' => $stationPickups,
            'roadsidePickups' => $roadsidePickups,
            'stationDropoffs' => $stationDropoffs,
            'checkinHistory' => $checkinHistory,
            'checkinStats' => $checkinStats,
            'financeSummary' => $financeSummary,
            'notifications' => $notifications,
            'driverReviews' => $driverReviews,
            'lastHandoverLog' => ($selectedTrip?->bus_id ? Trip::where('bus_id', $selectedTrip->bus_id)->whereNotNull('handover_notes')->latest('id')->value('handover_notes') : null)
                ?: 'Xe vận hành an toàn, đã kiểm tra kỹ thuật đạt chuẩn 100% trước khi giao ca.',
            'tripExpenses' => $selectedTrip ? $selectedTrip->expenses()->orderBy('created_at', 'desc')->get() : collect([]),
            'user' => $user,
        ]);
    }

    /**
     * 1. Xác nhận nhận chuyến
     */
    public function acceptTrip(Request $request, $id)
    {
        $trip = Trip::with(['route', 'driver'])->findOrFail($id);
        $trip->status = 'DRIVER_CONFIRMED';
        $trip->accepted_at = now();
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'DRIVER_ACCEPTED',
            'description' => "Tài xế {$trip->driver?->name} đã XÁC NHẬN NHẬN CHUYẾN #{$trip->trip_code} ({$trip->route?->name}). Sẵn sàng làm thủ tục kiểm tra an toàn xe.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', "Bác tài đã xác nhận nhận chuyến {$trip->trip_code} thành công! Hãy kiểm tra an toàn xe trước giờ xuất bến.");
    }

    /**
     * 2. Báo không thể nhận chuyến
     */
    public function rejectTrip(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string',
            'note' => 'nullable|string'
        ]);

        $trip = Trip::findOrFail($id);
        $oldDriverName = $trip->driver?->name ?? 'Tài xế';
        
        $trip->driver_id = null;
        $trip->status = 'OPEN_FOR_SALE';
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'DRIVER_REJECTED',
            'description' => "Tài xế {$oldDriverName} báo không thể nhận chuyến. Lý do: {$request->reason}. Ghi chú: {$request->note}",
            'performed_by' => $oldDriverName
        ]);

        return back()->with('success', 'Đã gửi thông báo từ chối chuyến đến Ban điều hành!');
    }

    /**
     * 3. Kiểm tra xe trước chuyến & Xác nhận sẵn sàng (READY)
     */
    public function verifyVehicle(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->status = 'READY';
        $trip->vehicle_checked_at = now();
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'VEHICLE_CHECKLIST_PASSED',
            'description' => "Tài xế {$trip->driver?->name} đã hoàn tất kiểm tra an toàn xe ({$trip->bus?->plate_number}) - Sẵn sàng khởi hành.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'Đã xác nhận xe đủ điều kiện an toàn & Sẵn sàng khởi hành!');
    }

    /**
     * 4. Báo lỗi xe
     */
    public function reportVehicleIssue(Request $request, $id)
    {
        $request->validate([
            'issue_type' => 'required|string',
            'severity' => 'required|string',
            'description' => 'required|string'
        ]);

        $trip = Trip::findOrFail($id);

        Incident::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'type' => 'VEHICLE_ISSUE',
            'severity' => $request->severity,
            'description' => "[Báo lỗi xe] {$request->issue_type}: {$request->description}",
            'reported_by' => $trip->driver?->name ?? 'Tài xế',
            'status' => 'PENDING'
        ]);

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'VEHICLE_ISSUE_REPORTED',
            'description' => "Tài xế báo lỗi xe: {$request->issue_type} ({$request->severity}) - {$request->description}",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'Đã gửi báo cáo lỗi xe tới bộ phận Kỹ thuật & Quản trị!');
    }

    /**
     * 5. Báo sự cố khẩn cấp
     */
    public function reportIncident(Request $request, $id)
    {
        $request->validate([
            'incident_type' => 'required|string',
            'severity' => 'required|string',
            'location' => 'nullable|string',
            'description' => 'required|string'
        ]);

        $trip = Trip::findOrFail($id);

        Incident::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'type' => $request->incident_type,
            'severity' => $request->severity,
            'description' => "[SỰ CỐ KHẨN CẤP] Vị trí: {$request->location}. Mô tả: {$request->description}",
            'reported_by' => $trip->driver?->name ?? 'Tài xế',
            'status' => 'PENDING'
        ]);

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'INCIDENT_REPORTED',
            'description' => "Tài xế báo sự cố khẩn cấp: {$request->incident_type} tại {$request->location} - {$request->description}",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'Đã phát tín hiệu cảnh báo khẩn cấp tới Tổng đài điều hành Nhà xe Kiến Trúc (nhaxekientruc)!');
    }

    /**
     * 6. Báo trễ chuyến
     */
    public function reportDelay(Request $request, $id)
    {
        $request->validate([
            'delay_minutes' => 'required|integer|min:5|max:180',
            'reason' => 'required|string'
        ]);

        $trip = Trip::findOrFail($id);
        $trip->delay_minutes = ($trip->delay_minutes ?? 0) + $request->delay_minutes;
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'DELAY_REPORTED',
            'description' => "Tài xế báo trễ chuyến {$request->delay_minutes} phút. Lý do: {$request->reason}",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', "Đã ghi nhận báo trễ {$request->delay_minutes} phút!");
    }

    /**
     * 7. Bắt đầu chuyến / Khởi hành (DEPARTED / IN_TRANSIT)
     */
    public function startTrip(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->status = 'IN_TRANSIT';
        $trip->departed_at = now();
        $trip->save();

        if ($trip->bus) {
            $trip->bus->status = 'RUNNING';
            $trip->bus->save();
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_DEPARTED',
            'description' => "Tài xế {$trip->driver?->name} đã cho xe khởi hành lăn bánh rời bến.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'Chuyến xe đã chính thức khởi hành lăn bánh!');
    }

    /**
     * 8. Kết thúc chuyến xe / Cập bến (ARRIVED / COMPLETED)
     */
    public function finishTrip(Request $request, $id)
    {
        $trip = Trip::with('bus')->findOrFail($id);
        $trip->status = 'COMPLETED';
        $trip->completed_at = now();
        $trip->actual_arrival_time = now();
        
        $handoverNotes = $request->input('handover_notes');
        if ($handoverNotes) {
            $trip->handover_notes = $handoverNotes;
        }
        $trip->save();

        if ($trip->bus) {
            $trip->bus->status = 'ACTIVE';
            $trip->bus->save();
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_COMPLETED',
            'description' => "Tài xế {$trip->driver?->name} đã hoàn thành chuyến xe an toàn tại điểm cuối." . ($handoverNotes ? " [Sổ bàn giao kỹ thuật: {$handoverNotes}]" : ""),
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        // Tự động tích lũy điểm thưởng vào tài khoản hành khách
        Trip::awardLoyaltyPointsForCompletedTrips();

        return back()->with('success', 'Chúc mừng bạn đã hoàn thành chuyến xe an toàn! Điểm thưởng đã được tích lũy vào tài khoản của toàn bộ hành khách.');
    }

    /**
     * 8b. Bác tài bấm: ĐÃ ĐẾN TRẠM DỪNG NGHỈ
     */
    public function restStop(Request $request, $id)
    {
        $trip = Trip::with('bus', 'driver')->findOrFail($id);
        $trip->status = 'REST_STOP';
        $trip->rest_started_at = now();
        $trip->save();

        if ($trip->bus) {
            $trip->bus->status = 'REST_STOP';
            $trip->bus->save();
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_REST_STOP',
            'description' => "Xe {$trip->bus?->license_plate} (Bác tài {$trip->driver?->name}) đã đến trạm dừng chân nghỉ ngơi, tạm dừng để hành khách thư giãn.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', '✓ Đã cập nhật: Xe đã đến trạm dừng chân nghỉ ngơi! Hệ thống đã thông báo tới Quản trị viên & Lơ xe.');
    }

    /**
     * 8c. Bác tài bấm: TIẾP TỤC CHẠY XE (Hành khách đã lên đủ xe)
     */
    public function resumeTrip(Request $request, $id)
    {
        $trip = Trip::with('bus', 'driver')->findOrFail($id);
        $trip->status = 'IN_TRANSIT';
        $trip->rest_resumed_at = now();
        $trip->save();

        if ($trip->bus) {
            $trip->bus->status = 'RUNNING';
            $trip->bus->save();
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_RESUMED',
            'description' => "Xe {$trip->bus?->license_plate} (Bác tài {$trip->driver?->name}) đã kiểm đủ khách lên xe và tiếp tục hành trình lăn bánh.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', '✓ Đã cập nhật: Xe tiếp tục hành trình lăn bánh! Quản trị viên và hành khách đã nhận thông báo.');
    }

    /**
     * 9. Cập nhật trạm dừng dọc đường (Chỉ tài xế có quyền báo đến trạm hoặc bỏ qua trạm)
     */
    public function updateCurrentStop(Request $request, $id)
    {
        $request->validate([
            'stop_order' => 'required|integer',
            'action' => 'required|in:ARRIVE,SKIP',
            'stop_name' => 'nullable|string'
        ]);

        $trip = Trip::with('route.stops')->findOrFail($id);
        $stopOrder = (int) $request->stop_order;
        $stopName = $request->stop_name ?? "Trạm số {$stopOrder}";

        $logs = $trip->stop_status_logs ?? [];
        if (!is_array($logs)) {
            $logs = (array) $logs;
        }

        if ($request->action === 'ARRIVE') {
            $trip->current_stop_order = $stopOrder;
            $trip->status = 'IN_TRANSIT';
            $logs[$stopOrder] = [
                'status' => 'ARRIVED',
                'time' => now()->format('H:i - d/m/Y'),
                'stop_name' => $stopName,
            ];
            $trip->stop_status_logs = $logs;
            $trip->save();

            AuditLog::create([
                'trip_id' => $trip->id,
                'action' => 'DRIVER_ARRIVED_STOP',
                'description' => "Tài xế {$trip->driver?->name} báo xe đã ĐẾN TRẠM: {$stopName} (Trạm thứ {$stopOrder}). Đang dừng đón/trả khách.",
                'performed_by' => $trip->driver?->name ?? 'Tài xế'
            ]);

            return back()->with('success', "Đã ghi nhận xe ĐẾN TRẠM: {$stopName}!");
        } else {
            // Bỏ qua trạm (Không có khách lên/xuống)
            $trip->current_stop_order = max((int)($trip->current_stop_order ?? 1), $stopOrder + 1);
            $logs[$stopOrder] = [
                'status' => 'SKIPPED',
                'time' => now()->format('H:i - d/m/Y'),
                'stop_name' => $stopName,
            ];
            $trip->stop_status_logs = $logs;
            $trip->save();

            AuditLog::create([
                'trip_id' => $trip->id,
                'action' => 'DRIVER_SKIPPED_STOP',
                'description' => "Tài xế {$trip->driver?->name} báo BỎ QUA TRẠM: {$stopName} (Không có khách đón/trả). Xe đang tiến thẳng đến trạm kế tiếp.",
                'performed_by' => $trip->driver?->name ?? 'Tài xế'
            ]);

            return back()->with('success', "Đã ghi nhận BỎ QUA trạm {$stopName}!");
        }
    }

    /**
     * 10. Chốt chuyến và Bàn giao doanh thu (Tách riêng Chuyến Đi & Chuyến Về)
     */
    public function closeTrip(Request $request, $id)
    {
        $request->validate([
            'actual_cash_submitted' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $trip = Trip::with(['bookings.bookingSeats', 'expenses', 'route'])->findOrFail($id);

        // Tính toán doanh thu và chi phí
        $totalRevenue = $trip->bookings->sum('total_price') ?: $trip->bookings->sum('total_amount') ?: 0;
        $onlinePaid = $trip->bookings->where('payment_status', 'PAID')->sum('deposit_amount') ?: 0;
        $roadsideAndRemainingCash = $totalRevenue - $onlinePaid;
        $totalExpenses = $trip->expenses->sum('amount') ?: 0;
        $netProfit = $totalRevenue - $totalExpenses;
        $expectedCash = max(0, $roadsideAndRemainingCash - $totalExpenses);

        $actualCash = (float) $request->actual_cash_submitted;
        $discrepancy = $actualCash - $expectedCash;

        $tripDirection = str_contains($trip->route?->name ?? '', 'Đà Nẵng ➔') || str_contains($trip->route?->name ?? '', 'Đà Nẵng ->') 
            ? 'Chuyến Đi (' . ($trip->route?->name ?? 'Chuyến Đi') . ')'
            : 'Chuyến Về (' . ($trip->route?->name ?? 'Chuyến Về') . ')';

        $closing = TripClosing::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'expected_cash_from_conductor' => $expectedCash,
                'actual_cash_submitted' => $actualCash,
                'cash_discrepancy' => $discrepancy,
                'closed_by' => $trip->driver?->name ?? 'Tài xế & Lơ xe',
                'notes' => $request->notes,
                'trip_direction' => $tripDirection,
                'audit_status' => 'PENDING_AUDIT',
            ]
        );

        $trip->status = 'COMPLETED';
        $trip->closed_at = now();
        $trip->save();

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_CLOSED_AND_SUBMITTED',
            'description' => "Hồ sơ bàn giao tiền {$tripDirection}: App tính nộp {$expectedCash} đ, Thực nộp {$actualCash} đ (Lệch {$discrepancy} đ). Đã gửi Ban Quản Trị Admin phê duyệt.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', "Đã chốt chuyến và gửi hồ sơ bàn giao tiền về Ban Quản Trị Admin đối soát!");
    }


    /**
     * 11. Khai báo Chi phí chuyến đi (Dầu, BOT, ăn uống, sửa xe vặt)
     */
    public function storeExpense(Request $request, $id)
    {
        $request->validate([
            'expense_type' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string',
            'receipt_image' => 'nullable|string',
        ]);

        $trip = Trip::findOrFail($id);
        $receiptImage = $request->receipt_image;
        if ($request->hasFile('receipt_file')) {
            $file = $request->file('receipt_file');
            $fileName = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/receipts'), $fileName);
            $receiptImage = '/uploads/receipts/' . $fileName;
        }

        $expense = TripExpense::create([
            'trip_id' => $trip->id,
            'expense_type' => $request->expense_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'receipt_image' => $receiptImage,
            'created_by' => Auth::user()->name ?? ($trip->driver?->name ?? 'Bác tài'),
        ]);

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'EXPENSE_RECORDED',
            'description' => "Tài xế đã khai báo chi phí: " . number_format($request->amount, 0, ',', '.') . "đ ({$request->expense_type}) - " . ($request->description ?? ''),
            'performed_by' => Auth::user()->name ?? ($trip->driver?->name ?? 'Bác tài')
        ]);

        return back()->with('success', 'Đã ghi nhận chi phí ' . number_format($request->amount, 0, ',', '.') . 'đ vào hồ sơ chuyến đi!');
    }

    /**
     * 12. Phát tín hiệu SOS khẩn cấp (Panic Button)
     */
    public function triggerSos(Request $request, $id)
    {
        $trip = Trip::with(['route', 'driver', 'bus'])->findOrFail($id);
        $location = $request->input('location', 'Km 125 Quốc Lộ 1A');
        $reason = $request->input('reason', 'Tín hiệu SOS Báo Động Khẩn Cấp từ cabin tài xế');

        $incident = Incident::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'type' => 'SOS_PANIC',
            'severity' => 'CRITICAL',
            'status' => 'URGENT',
            'description' => "[SOS KHẨN CẤP] Xe {$trip->bus?->license_plate} - Chuyến #{$trip->trip_code} ({$trip->route?->name}). Tài xế: {$trip->driver?->name}. Vị trí: {$location}. Lý do: {$reason}",
            'reported_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'SOS_TRIGGERED',
            'description' => "🚨 [BÁO ĐỘNG SOS KHẨN CẤP] Tài xế {$trip->driver?->name} kích hoạt nút SOS khẩn cấp tại vị trí {$location}! Đã gửi cảnh báo toàn hệ thống Điều hành Admin.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'ĐÃ PHÁT TÍN HIỆU SOS KHẨN CẤP! Bộ phận điều hành và cứu hộ đã nhận được tọa độ vị trí xe của bạn!');
    }

    /**
     * 13. Đổi tài xế sau 4 tiếng lái xe liên tục (Luật GTĐB)
     */
    public function changeShift(Request $request, $id)
    {
        $trip = Trip::with(['route', 'driver', 'secondDriver'])->findOrFail($id);
        
        if ($trip->driver_id && $trip->second_driver_id) {
            $oldDriverId = $trip->driver_id;
            $trip->driver_id = $trip->second_driver_id;
            $trip->second_driver_id = $oldDriverId;
            $trip->save();
            $newDriver = Driver::find($trip->driver_id)?->name ?? 'Tài xế mới';
        } else {
            $newDriver = $request->input('second_driver_name', 'Tài xế phụ');
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'DRIVER_SHIFT_CHANGED',
            'description' => "Tuân thủ quy định Luật GTĐB (lái xe liên tục không quá 4 tiếng): Đã thực hiện đổi tài xế trên chuyến #{$trip->trip_code}. Người cầm lái tiếp theo: {$newDriver}.",
            'performed_by' => Auth::user()->name ?? 'Bác tài'
        ]);

        return back()->with('success', "Đã ghi nhận đổi ca tài xế sang {$newDriver} thành công! Đồng hồ lái xe 4 tiếng đã được đặt lại.");
    }
}
