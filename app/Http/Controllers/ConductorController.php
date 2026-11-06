<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\RouteStop;
use App\Models\TripExpense;
use App\Models\TripClosing;
use App\Models\Incident;
use App\Models\LostFound;
use App\Models\AuditLog;
use App\Models\Bus;
use App\Services\SeatSegmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConductorController extends Controller
{
    /**
     * 1. Dashboard chính của Lơ xe / Phụ xe
     */
    public function dashboard(Request $request)
    {
        \App\Services\TripScheduleService::ensureUpcomingTripsExist(3);
        Trip::syncRealTimeStatuses();
        // 1.1 Lấy danh sách tất cả các xe đang hoạt động để làm bộ chuyển đổi xe
        $buses = Bus::orderBy('license_plate', 'asc')->get();

        // 1.2 Lấy tất cả các chuyến xe kèm quan hệ
        $allTrips = Trip::with([
            'route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'),
            'bus',
            'driver',
            'secondDriver',
            'conductor',
            'secondConductor',
            'bookings.bookingSeats',
            'bookings.pickupStop',
            'bookings.dropoffStop',
            'expenses',
            'closing',
            'incidents',
            'lostFounds'
        ])
            ->orderBy('departure_time', 'asc')
            ->get();

        $user = Auth::user();
        // Xác định quyền hiển thị menu chuyển đổi cổng & chọn tài khoản:
        // - Mặc định luôn là TRUE (để Admin/Dev trải nghiệm, đổi cổng thông tin và chọn các acc để test).
        // - Chỉ chuyển thành FALSE khi: tài khoản đăng nhập có role === 'conductor' VÀ được đánh dấu 'is_real_conductor_login' từ form /login VÀ không có cờ admin.
        $isAdminViewing = true;
        if ($user && in_array($user->role, ['conductor', 'staff']) && session('is_real_conductor_login') === true && !session('admin_mode') && !$request->has('admin_view')) {
            $isAdminViewing = false;
        }

        // Lấy danh sách tài khoản Lơ xe và gắn kèm biển số xe được phân công thực tế từ trips (Loại bỏ trùng lặp tên)
        $conductorAccounts = \App\Models\User::where('role', 'conductor')
            ->where(function ($q) {
                $q->where('email', 'like', '%loxe%')
                    ->orWhere('email', 'like', '%staff%');
            })
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'email', 'phone'])
            ->unique('name')
            ->values()
            ->map(function ($acc) use ($allTrips, $buses) {
                $assignedTrip = $allTrips->first(function ($t) use ($acc) {
                    return $t->conductor_id == $acc->id || ($t->second_conductor_id ?? null) == $acc->id;
                });
                $acc->vehicle_plate = $assignedTrip?->bus?->license_plate ?? '43B-012.34';
                return $acc;
            });

        $activeConductor = null;
        if ($isAdminViewing) {
            if ($request->filled('conductor_id')) {
                session(['admin_selected_conductor_id' => $request->input('conductor_id')]);
            }
            $conductorId = $request->input('conductor_id') ?: session('admin_selected_conductor_id');
            if ($conductorId) {
                $activeConductor = $conductorAccounts->firstWhere('id', $conductorId);
            }
            if (!$activeConductor) {
                $activeConductor = $conductorAccounts->first(); // Mặc định Lơ xe 1
            }
            if ($activeConductor) {
                session(['admin_selected_conductor_id' => $activeConductor->id]);
            }
        } else {
            $activeConductor = $user;
        }

        // Lấy tất cả các chuyến mà Lơ xe này được phân công phụ trách thực tế
        $assignedTrips = $allTrips->filter(function ($t) use ($activeConductor) {
            if (!$activeConductor) return false;
            return $t->conductor_id == $activeConductor->id 
                || ($t->second_conductor_id ?? null) == $activeConductor->id;
        });

        // 1.3 Xác định xe đang hoạt động
        $busId = $request->input('bus_id');
        if ($busId) {
            $currentBus = $buses->firstWhere('id', $busId) ?? $buses->first();
        } else {
            // Tự động gán xe theo chuyến xe được phân công của Lơ xe
            $firstAssigned = $assignedTrips->first();
            if ($firstAssigned && $firstAssigned->bus) {
                $currentBus = $firstAssigned->bus;
            } elseif ($activeConductor) {
                $currentBus = $buses->firstWhere('license_plate', $activeConductor->vehicle_plate) ?? $buses->first();
            } else {
                $currentBus = $buses->first();
            }
        }

        // 1.4 Xác định chuyến xe đang chọn
        $tripId = $request->input('trip_id');
        if ($tripId) {
            $selectedTrip = $allTrips->firstWhere('id', $tripId);
            if ($selectedTrip && !$request->has('bus_id') && $selectedTrip->bus) {
                $currentBus = $selectedTrip->bus;
            }
        } else {
            // Mặc định ưu tiên chuyến được phân công của Lơ xe hôm nay
            $todayAssigned = $assignedTrips->filter(function ($t) {
                return Carbon::parse($t->departure_time)->isToday();
            });

            $todayTripsOfBus = $allTrips->where('bus_id', $currentBus->id)->filter(function ($t) {
                return Carbon::parse($t->departure_time)->isToday();
            });

            $selectedTrip = $todayAssigned->whereIn('status', ['IN_TRANSIT', 'BOARDING', 'OPEN_FOR_SALE'])->first()
                ?? $todayAssigned->first()
                ?? $todayTripsOfBus->whereIn('status', ['IN_TRANSIT', 'BOARDING', 'OPEN_FOR_SALE'])->first()
                ?? $assignedTrips->first()
                ?? $todayTripsOfBus->first()
                ?? $allTrips->first();

            if ($selectedTrip && $selectedTrip->bus && !$busId) {
                $currentBus = $selectedTrip->bus;
            }
        }

        // Danh sách chuyến hiển thị trên thanh chọn chuyến:
        // ĐẢM BẢO gom toàn bộ chuyến được phân công của Lơ xe (bao gồm cả Đà Nẵng - Quảng Bình) + các chuyến của xe
        $busTrips = $assignedTrips->merge($allTrips->where('bus_id', $currentBus->id))
            ->unique('id')
            ->sortBy('departure_time')
            ->values();

        // Định dạng thông tin ngày giờ rõ ràng cho danh sách chuyến của xe (Hỗ trợ lịch ca chạy liên tục từ 3 ngày trở lên)
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $formattedTrips = $busTrips->map(function ($tr) use ($today) {
            $stats = $tr->calculateAccurateStats();
            $dep = Carbon::parse($tr->departure_time);
            $arr = Carbon::parse($tr->arrival_time);
            $dateStr = $dep->format('Y-m-d');

            if ($dep->isToday()) {
                $dayCategory = 'today';
                $dayLabel = 'Hôm nay (' . $dep->format('d/m') . ')';
            } elseif ($dep->isTomorrow()) {
                $dayCategory = 'tomorrow';
                $dayLabel = 'Ngày mai (' . $dep->format('d/m') . ')';
            } elseif ($dep->lt($today)) {
                $dayCategory = 'past';
                $dayLabel = $dep->format('d/m/Y');
            } else {
                $dayCategory = $dateStr;
                $dayLabel = $dep->format('d/m');
            }

            $isReturn = str_contains(mb_strtolower($tr->route->name ?? ''), 'đà nẵng') && !str_starts_with(mb_strtolower($tr->route->name ?? ''), 'đà nẵng');

            $timeOfDay = 'Sáng';
            $hour = (int) $dep->format('H');
            if ($hour >= 12 && $hour < 18) {
                $timeOfDay = 'Chiều';
            } elseif ($hour >= 18) {
                $timeOfDay = 'Tối';
            }

            // Tối ưu payload: danh sách chuyến chỉ cần thông tin tóm tắt, KHÔNG gửi toàn bộ vé/ghế/sự cố (giảm ~1.9MB)
            $trArr = (clone $tr)->makeHidden(['bookings', 'expenses', 'closing', 'incidents', 'lostFounds'])->toArray();
            return array_merge($trArr, $stats, [
                'day_category' => $dayCategory,
                'date_key' => $dateStr,
                'departure_date_formatted' => $dep->format('d/m/Y'),
                'departure_day_label' => $dayLabel,
                'time_of_day_label' => $timeOfDay,
                'departure_time_formatted' => $dep->format('H:i'),
                'arrival_time_formatted' => $arr->format('H:i'),
                'full_datetime_title' => $dep->isoFormat('dddd, DD/MM/YYYY') . ' • ' . $dep->format('H:i') . ' ➔ ' . $arr->format('H:i'),
                'is_today' => $dep->isToday(),
                'is_return_trip' => $isReturn,
            ]);
        });

        // Tạo danh sách các tab ngày ca chạy (Hôm nay 30, Ngày mai 31, 01/11, 02/11, 03/11...)
        $availableDateTabs = [];
        $uniqueDates = $busTrips->map(function ($t) {
            return Carbon::parse($t->departure_time)->format('Y-m-d');
        })->unique()->sort()->values();

        foreach ($uniqueDates as $dStr) {
            $cDate = Carbon::parse($dStr);
            if ($cDate->lt($today))
                continue;
            if ($cDate->isToday()) {
                $cat = 'today';
                $lbl = '☀️ Hôm Nay (' . $cDate->format('d/m') . ')';
            } elseif ($cDate->isTomorrow()) {
                $cat = 'tomorrow';
                $lbl = '📅 Ngày Mai (' . $cDate->format('d/m') . ')';
            } else {
                $cat = $dStr;
                $lbl = '📅 ' . $cDate->format('d/m');
            }
            $availableDateTabs[] = [
                'category' => $cat,
                'date' => $dStr,
                'label' => $lbl,
                'count' => $busTrips->filter(fn($t) => Carbon::parse($t->departure_time)->format('Y-m-d') === $dStr)->count(),
            ];
        }

        $occupiedSeats = [];
        $stops = [];
        $stationPickups = [];
        $roadsidePickups = [];
        $stationDropoffs = [];
        $financeSummary = [];
        $seatMap = [];
        $checkinHistory = [];
        $checkinStats = [];

        if ($selectedTrip) {
            $stops = $selectedTrip->route ? $selectedTrip->route->stops->sortBy('stop_order')->values() : collect();
            $curOrder = $selectedTrip->current_stop_order ?? 0;
            $nextOrder = min($curOrder + 1, $stops->max('stop_order') ?? 1);
            $occupiedSeats = SeatSegmentService::getAllBookedSeatsForTrip($selectedTrip->id);

            $bookings = $selectedTrip->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])->whereNotIn('checkin_status', ['CANCELLED']);

            // 1.1 Gom nhóm đón tại Điểm cố định (CHỈ LẤY KHÁCH ĐÓN CỐ ĐỊNH, KHÔNG LẪN KHÁCH ĐÓN DỌC ĐƯỜNG)
            foreach ($stops as $stop) {
                $stopBookings = $bookings->where('pickup_stop_id', $stop->id)->filter(function ($b) {
                    $noteLower = mb_strtolower($b->notes ?? '');
                    $isRoadside = (
                        $b->booking_source === 'ROADSIDE_ON_BUS' ||
                        str_contains($noteLower, 'dọc đường') ||
                        str_contains($noteLower, 'kcn') ||
                        str_contains($noteLower, 'cây xăng') ||
                        str_contains($noteLower, 'cầu vượt') ||
                        str_contains($noteLower, 'ngã ba') ||
                        str_contains($noteLower, 'trạm thu phí') ||
                        str_contains($noteLower, 'vị trí hiện tại')
                    );
                    return !$isRoadside;
                });

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

                    $totalAmount = (float) $b->total_amount;
                    if ($isBoarded) {
                        $paidAmount = $totalAmount;
                        $remainingAmount = 0;
                        $depositPercent = 100;
                        $paymentBadge = '✓ Đã thanh toán 100%';
                    } else {
                        $paidAmount = (float) $b->paid_amount;
                        $remainingAmount = max(0, $totalAmount - $paidAmount);
                        $depositPercent = ($totalAmount > 0) ? round(($paidAmount / $totalAmount) * 100) : 0;

                        // Nhãn thanh toán chuẩn xác (Tuyệt đối không nhầm lẫn Online với Cash)
                        if ($b->payment_status === 'PAID') {
                            if ($b->payment_method === 'CASH') {
                                $paymentBadge = '✓ Đã thu tiền mặt';
                            } else {
                                $methodName = in_array(strtoupper($b->payment_method), ['VNPAY', 'MOMO', 'BANKING', 'ONLINE', 'VIETQR', 'ONLINE_CARD']) ? strtoupper($b->payment_method) : '100% Online';
                                $paymentBadge = "✓ Đã thanh toán ({$methodName})";
                            }
                        } elseif ($paidAmount > 0) {
                            $remText = number_format($remainingAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Cọc {$depositPercent}% - Cần thu: {$remText}";
                        } else {
                            $remText = number_format($totalAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Chưa thanh toán (Tiền mặt: {$remText})";
                        }
                    }

                    // Logic: 1 ghế = 1 thẻ, 1 vé = 1 QR. Nếu khách đặt nhiều ghế, phân rã thành các thẻ riêng biệt
                    if (count($seatNums) > 1) {
                        $countSeats = count($seatNums);
                        $perSeatTotal = round($totalAmount / $countSeats);
                        $perSeatPaid = round($paidAmount / $countSeats);
                        $perSeatRemaining = max(0, $perSeatTotal - $perSeatPaid);

                        foreach ($seatNums as $sIdx => $sNum) {
                            $paxList[] = [
                                'id' => $b->id,
                                'sub_id' => $b->id . '_' . $sNum,
                                'booking_code' => $b->booking_code . ' (' . $sNum . ')',
                                'customer_name' => $b->customer_name,
                                'customer_phone' => $b->customer_phone,
                                'seats' => [$sNum],
                                'seat_number' => $sNum,
                                'group_info' => 'Vé ' . ($sIdx + 1) . '/' . $countSeats . ' (Nhóm)',
                                'total_amount' => $perSeatTotal,
                                'paid_amount' => $perSeatPaid,
                                'remaining_amount' => $perSeatRemaining,
                                'deposit_percent' => $depositPercent,
                                'payment_badge' => $paymentBadge,
                                'payment_status' => $b->payment_status,
                                'payment_method' => $b->payment_method ?? 'ONLINE',
                                'checkin_status' => $b->checkin_status,
                                'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                                'notes' => $b->notes,
                                'pickup_stop_name' => $stop->stop_name,
                                'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                                'is_roadside' => false
                            ];
                        }
                    } else {
                        $paxList[] = [
                            'id' => $b->id,
                            'sub_id' => $b->id . '_' . ($seatNums[0] ?? '1'),
                            'booking_code' => $b->booking_code,
                            'customer_name' => $b->customer_name,
                            'customer_phone' => $b->customer_phone,
                            'seats' => $seatNums,
                            'seat_number' => $seatNums[0] ?? null,
                            'group_info' => null,
                            'total_amount' => $totalAmount,
                            'paid_amount' => $paidAmount,
                            'remaining_amount' => $remainingAmount,
                            'deposit_percent' => $depositPercent,
                            'payment_badge' => $paymentBadge,
                            'payment_status' => $b->payment_status,
                            'payment_method' => $b->payment_method ?? 'ONLINE',
                            'checkin_status' => $b->checkin_status,
                            'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                            'notes' => $b->notes,
                            'pickup_stop_name' => $stop->stop_name,
                            'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                            'is_roadside' => false
                        ];
                    }
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

            // 1.2 Lọc riêng khách Đón Dọc Đường (Khách đặt qua Web có ghi chú đứng đợi)
            foreach ($bookings as $b) {
                $noteLower = mb_strtolower($b->notes ?? '');
                $isRoadside = str_contains($noteLower, 'dọc đường')
                    || str_contains($noteLower, 'kcn')
                    || str_contains($noteLower, 'cây xăng')
                    || str_contains($noteLower, 'cầu vượt')
                    || str_contains($noteLower, 'ngã ba')
                    || str_contains($noteLower, 'trạm thu phí')
                    || $b->booking_source === 'ROADSIDE_ON_BUS';

                if ($isRoadside) {
                    $seatNums = $b->bookingSeats->pluck('seat_number')->toArray();
                    $totalAmount = (float) $b->total_amount;
                    if ($b->checkin_status === 'CHECKED_IN') {
                        $paidAmount = $totalAmount;
                        $remainingAmount = 0;
                        $depositPercent = 100;
                        $paymentBadge = '✓ Đã thanh toán 100%';
                    } else {
                        $paidAmount = (float) $b->paid_amount;
                        $remainingAmount = max(0, $totalAmount - $paidAmount);
                        $depositPercent = ($totalAmount > 0) ? round(($paidAmount / $totalAmount) * 100) : 0;
                        if ($b->payment_status === 'PAID') {
                            if ($b->payment_method === 'CASH') {
                                $paymentBadge = '✓ Đã thu tiền mặt';
                            } else {
                                $methodName = in_array(strtoupper($b->payment_method), ['VNPAY', 'MOMO', 'BANKING', 'ONLINE', 'VIETQR', 'ONLINE_CARD']) ? strtoupper($b->payment_method) : '100% Online';
                                $paymentBadge = "✓ Đã thanh toán ({$methodName})";
                            }
                        } elseif ($paidAmount > 0) {
                            $remText = number_format($remainingAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Cọc {$depositPercent}% - Cần thu: {$remText}";
                        } else {
                            $remText = number_format($totalAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Chưa thanh toán (Tiền mặt: {$remText})";
                        }
                    }

                    if (count($seatNums) > 1) {
                        $countSeats = count($seatNums);
                        $perSeatTotal = round($totalAmount / $countSeats);
                        $perSeatPaid = round($paidAmount / $countSeats);
                        $perSeatRemaining = max(0, $perSeatTotal - $perSeatPaid);

                        foreach ($seatNums as $sIdx => $sNum) {
                            $roadsidePickups[] = [
                                'id' => $b->id,
                                'sub_id' => $b->id . '_' . $sNum,
                                'booking_code' => $b->booking_code . ' (' . $sNum . ')',
                                'customer_name' => $b->customer_name,
                                'customer_phone' => $b->customer_phone,
                                'seats' => [$sNum],
                                'seat_number' => $sNum,
                                'group_info' => 'Vé ' . ($sIdx + 1) . '/' . $countSeats . ' (Nhóm)',
                                'total_amount' => $perSeatTotal,
                                'paid_amount' => $perSeatPaid,
                                'remaining_amount' => $perSeatRemaining,
                                'deposit_percent' => $depositPercent,
                                'payment_badge' => $paymentBadge,
                                'payment_status' => $b->payment_status,
                                'payment_method' => $b->payment_method ?? 'ONLINE',
                                'checkin_status' => $b->checkin_status,
                                'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                                'notes' => $b->notes ?: 'Đón dọc đường theo lộ trình',
                                'pickup_stop_name' => $b->pickupStop ? $b->pickupStop->stop_name : 'Dọc đường',
                                'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                                'is_roadside' => true
                            ];
                        }
                    } else {
                        $roadsidePickups[] = [
                            'id' => $b->id,
                            'sub_id' => $b->id . '_' . ($seatNums[0] ?? '1'),
                            'booking_code' => $b->booking_code,
                            'customer_name' => $b->customer_name,
                            'customer_phone' => $b->customer_phone,
                            'seats' => $seatNums,
                            'seat_number' => $seatNums[0] ?? null,
                            'group_info' => null,
                            'total_amount' => $totalAmount,
                            'paid_amount' => $paidAmount,
                            'remaining_amount' => $remainingAmount,
                            'deposit_percent' => $depositPercent,
                            'payment_badge' => $paymentBadge,
                            'payment_status' => $b->payment_status,
                            'payment_method' => $b->payment_method ?? 'ONLINE',
                            'checkin_status' => $b->checkin_status,
                            'checked_in_at' => $b->checked_in_at ? Carbon::parse($b->checked_in_at)->format('H:i d/m') : null,
                            'notes' => $b->notes ?: 'Đón dọc đường theo lộ trình',
                            'pickup_stop_name' => $b->pickupStop ? $b->pickupStop->stop_name : 'Dọc đường',
                            'dropoff_stop_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm kết thúc',
                            'is_roadside' => true
                        ];
                    }
                }
            }

            // 1.3 Thống kê Điểm Trả Khách cho toàn bộ các trạm trên lộ trình (3 Trạm)
            $lastStopId = $stops->last()?->id;
            $maxStopOrder = $stops->max('stop_order') ?? 3;

            foreach ($stops as $stop) {
                $isLastStop = ($stop->id === $lastStopId) || ($stop->stop_order == $maxStopOrder);

                $dropBookings = $bookings->filter(function($b) use ($stop, $isLastStop) {
                    if ($b->dropoff_stop_id == $stop->id) {
                        return true;
                    }
                    if ($isLastStop && empty($b->dropoff_stop_id)) {
                        return true;
                    }
                    return false;
                });

                $dropPax = [];
                foreach ($dropBookings as $b) {
                    $dropPax[] = [
                        'id' => $b->id,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'seats' => $b->bookingSeats->pluck('seat_number')->toArray(),
                        'checkin_status' => $b->checkin_status,
                    ];
                }

                $stationDropoffs[] = [
                    'stop_id' => $stop->id,
                    'stop_name' => $stop->stop_name,
                    'stop_order' => $stop->stop_order,
                    'address' => $stop->address ?? '',
                    'distance_km' => $stop->distance_from_start_km ?? 0,
                    'estimated_minutes' => $stop->estimated_minutes_from_start ?? 0,
                    'passengers' => $dropPax,
                    'total_pax' => count($dropPax),
                ];
            }

            // 1.4 Minh bạch Tài chính & Chốt ca (Settlement)
            $activeBookings = $bookings->whereNotIn('checkin_status', ['NO_SHOW', 'CANCELLED']);
            $totalPaxCount = $activeBookings->sum('total_seats') ?: $activeBookings->count();

            // Khách đặt web vs Khách bắt dọc đường
            $webPaxCount = $activeBookings->where('booking_source', '!=', 'ROADSIDE_ON_BUS')->count();
            $roadsidePaxCount = $activeBookings->where('booking_source', 'ROADSIDE_ON_BUS')->count();

            // 1. Tiền thanh toán Online / Đã cọc Web (VNPAY, Chuyển khoản, Momo, ONLINE_DEPOSIT)
            $onlineBookings = $activeBookings->where('payment_method', '!=', 'CASH');
            $onlinePaidAmount = (float) $onlineBookings->sum('paid_amount');
            $onlinePaidCount = $onlineBookings->where('paid_amount', '>', 0)->count();

            // 2. Tiền mặt Lơ xe ĐÃ THU thực tế trên xe (vé bắt dọc đường + tiền mặt đã thu khi lên xe)
            $cashBookings = $activeBookings->where('payment_method', 'CASH')->where('payment_status', 'PAID');
            $cashCollectedAmount = (float) $cashBookings->sum('paid_amount');
            $cashCollectedCount = $cashBookings->count();

            // 3. Tiền mặt CÒN THIẾU cần thu (gồm khách chưa trả + khách mới cọc 30-50%, tuyệt đối không tính khách đã lên xe vì đã thanh toán 100%)
            $uncollectedBookings = $activeBookings->filter(function ($b) {
                if ($b->checkin_status === 'CHECKED_IN')
                    return false;
                return (float) $b->total_amount > (float) $b->paid_amount;
            });
            $uncollectedAmount = (float) $uncollectedBookings->reduce(fn($carry, $b) => $carry + max(0, (float) $b->total_amount - (float) $b->paid_amount), 0);
            $uncollectedCount = $uncollectedBookings->count();

            // Tổng giá trị vé đã đặt
            $totalBookedAmount = (float) $activeBookings->sum('total_amount');
            $totalCollectedSoFar = $onlinePaidAmount + $cashCollectedAmount;

            $tripStats = $selectedTrip->calculateAccurateStats();
            foreach ($tripStats as $k => $v) {
                $selectedTrip->{$k} = $v;
            }

            // Chi phí chuyến đi đã chi
            $totalExpenses = (float) $selectedTrip->expenses->sum('amount');
            $netCashToRemit = max(0, $cashCollectedAmount - $totalExpenses);

            $financeSummary = [
                'total_passengers' => $tripStats['booked_seats_count'],
                'booked_seats_count' => $tripStats['booked_seats_count'],
                'customer_count' => $tripStats['customer_count'],
                'boarded_count' => $tripStats['boarded_seats_count'],
                'boarded_seats_count' => $tripStats['boarded_seats_count'],
                'boarded_passengers_count' => $tripStats['boarded_passengers_count'],
                'waiting_count' => $tripStats['waiting_seats_count'],
                'no_show_count' => $tripStats['no_show_seats_count'],
                'web_passengers' => $webPaxCount,
                'roadside_passengers' => $roadsidePaxCount,
                'total_booked_amount' => $totalBookedAmount,
                'total_collected_so_far' => $totalCollectedSoFar,
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

            // 1.5 Xây dựng Sơ đồ ghế 2 tầng (A01-A18 & B01-B18)
            $seatBookingMap = [];
            foreach ($selectedTrip->bookings as $b) {
                // Khách đã Hủy (CANCELLED), Hoàn tiền (REFUNDED) hoặc checkin CANCELLED -> Lập tức giải phóng thành GHẾ TRỐNG (AVAILABLE) để bán vé dọc đường!
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

                    $totalAmount = (float) $b->total_amount;
                    if ($b->checkin_status === 'CHECKED_IN') {
                        $paidAmount = $totalAmount;
                        $remainingAmount = 0;
                        $depositPercent = 100;
                        $paymentBadge = '✓ Đã thanh toán 100%';
                    } else {
                        $paidAmount = (float) $b->paid_amount;
                        $remainingAmount = max(0, $totalAmount - $paidAmount);
                        $depositPercent = ($totalAmount > 0) ? round(($paidAmount / $totalAmount) * 100) : 0;

                        if ($b->payment_status === 'PAID') {
                            if ($b->payment_method === 'CASH') {
                                $paymentBadge = '✓ Đã thu tiền mặt';
                            } else {
                                $methodName = in_array(strtoupper($b->payment_method), ['VNPAY', 'MOMO', 'BANKING', 'ONLINE', 'VIETQR', 'ONLINE_CARD']) ? strtoupper($b->payment_method) : '100% Online';
                                $paymentBadge = "✓ Đã thanh toán ({$methodName})";
                            }
                        } elseif ($paidAmount > 0) {
                            $remText = number_format($remainingAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Cọc {$depositPercent}% - Cần thu: {$remText}";
                        } else {
                            $remText = number_format($totalAmount, 0, ',', '.') . 'đ';
                            $paymentBadge = "⚠️ Chưa thanh toán (Tiền mặt: {$remText})";
                        }
                    }

                    $seatBookingMap[$bs->seat_number] = [
                        'booking_id' => $b->id,
                        'booking_code' => $b->booking_code,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'status' => $seatStatus,
                        'checkin_status' => $b->checkin_status,
                        'booking_source' => $b->booking_source,
                        'payment_status' => $b->payment_status,
                        'payment_method' => $b->payment_method,
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $remainingAmount,
                        'deposit_percent' => $depositPercent,
                        'payment_badge' => $paymentBadge,
                        'pickup_name' => $b->pickupStop ? $b->pickupStop->stop_name : 'Trạm đón',
                        'dropoff_name' => $b->dropoffStop ? $b->dropoffStop->stop_name : 'Trạm trả',
                        'notes' => $b->notes,
                    ];
                }
            }

            // Tạo danh sách 36 ghế (18 ghế Tầng 1 và 18 ghế Tầng 2)
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
                    'paid_amount' => (float) $cb->paid_amount,
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

        // Định dạng ngày giờ cụ thể cho selectedTrip
        $selectedTripFormatted = null;
        if ($selectedTrip) {
            $sDep = Carbon::parse($selectedTrip->departure_time);
            $sArr = Carbon::parse($selectedTrip->arrival_time);
            $daysOfWeek = [
                0 => 'Chủ Nhật',
                1 => 'Thứ Hai',
                2 => 'Thứ Ba',
                3 => 'Thứ Tư',
                4 => 'Thứ Năm',
                5 => 'Thứ Sáu',
                6 => 'Thứ Bảy',
            ];
            $dayOfWeekName = $daysOfWeek[$sDep->dayOfWeek] ?? 'Thứ Sáu';
            $fullDateTitle = "{$dayOfWeekName}, ngày " . $sDep->format('d/m/Y');

            if ($sDep->isToday()) {
                $sDayCat = 'today';
                $sDayLabel = 'Hôm nay (' . $sDep->format('d/m') . ')';
            } elseif ($sDep->isTomorrow()) {
                $sDayCat = 'tomorrow';
                $sDayLabel = 'Ngày mai (' . $sDep->format('d/m') . ')';
            } elseif ($sDep->isYesterday()) {
                $sDayCat = 'past';
                $sDayLabel = 'Hôm qua (' . $sDep->format('d/m') . ')';
            } elseif ($sDep->lt($today)) {
                $sDayCat = 'past';
                $sDayLabel = $sDep->format('d/m/Y');
            } else {
                $sDayCat = 'future';
                $sDayLabel = $sDep->format('d/m/Y');
            }

            $selectedTripFormatted = array_merge((clone $selectedTrip)->makeHidden(['bookings', 'expenses', 'incidents', 'lostFounds'])->toArray(), [
                'day_category' => $sDayCat,
                'departure_day_label' => $sDayLabel,
                'departure_date_formatted' => $sDep->format('d/m/Y'),
                'departure_time_formatted' => $sDep->format('H:i'),
                'arrival_time_formatted' => $sArr->format('H:i'),
                'full_date_title' => $fullDateTitle,
                'duration_label' => $sDep->diff($sArr)->format('%h giờ %i phút'),
                'is_today' => $sDep->isToday(),
            ]);
        }

        // 1.7 Lịch sử ca chạy cũ (Chốt ca & Đối soát doanh thu bàn giao)
        $pastShifts = Trip::with(['route.stops', 'bus', 'driver', 'closing', 'bookings.bookingSeats'])
            ->whereIn('status', ['COMPLETED', 'CLOSED'])
            ->orderBy('departure_time', 'desc')
            ->take(30)
            ->get()
            ->map(function ($t) {
                $paxCount = $t->bookings->where('payment_status', '!=', 'REFUNDED')->sum('total_seats');
                $cashColl = $t->closing ? (float) $t->closing->actual_cash_submitted : (float) $t->bookings->where('payment_method', 'CASH')->where('payment_status', 'PAID')->sum('paid_amount');
                $totalExp = $t->closing ? (float) $t->closing->total_expenses : 0;
                $netRemitted = $t->closing ? (float) $t->closing->actual_cash_submitted : max(0, $cashColl - $totalExp);

                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_name' => $t->route?->name ?? 'Tuyến liên tỉnh',
                    'origin' => $t->route?->origin ?? 'Đà Nẵng',
                    'destination' => $t->route?->destination ?? 'Điểm đến',
                    'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                    'driver_name' => $t->driver?->name ?? 'Bác tài',
                    'departure_time' => Carbon::parse($t->departure_time)->format('H:i - d/m/Y'),
                    'dep_date' => Carbon::parse($t->departure_time)->format('d/m/Y'),
                    'dep_time' => Carbon::parse($t->departure_time)->format('H:i'),
                    'status' => $t->status,
                    'total_passengers' => $paxCount,
                    'cash_collected' => $cashColl,
                    'total_expenses' => $totalExp,
                    'net_remitted' => $netRemitted,
                    'audit_status' => $t->closing?->audit_status ?: 'AUDITED',
                    'audited_by' => $t->closing?->audited_by ?: 'Kế toán trung tâm',
                    'notes' => $t->closing?->notes ?: 'Hành trình hoàn thành an toàn & đủ khách',
                ];
            });

        return Inertia::render('Staff/Dashboard', [
            'trips' => $formattedTrips,
            'availableDateTabs' => $availableDateTabs,
            'allBuses' => $buses,
            'currentBus' => $currentBus,
            'selectedTrip' => $selectedTripFormatted,
            'occupiedSeats' => $occupiedSeats,
            'stops' => $stops,
            'stationPickups' => $stationPickups,
            'roadsidePickups' => $roadsidePickups,
            'stationDropoffs' => $stationDropoffs,
            'financeSummary' => $financeSummary,
            'seatMap' => $seatMap,
            'checkinHistory' => $checkinHistory,
            'checkinStats' => $checkinStats,
            'conductorAccounts' => $conductorAccounts,
            'activeConductor' => $activeConductor,
            'isAdminViewing' => $isAdminViewing,
            'user' => $activeConductor ?? $user,
            'pastShifts' => $pastShifts,
        ]);
    }

    /**
     * 2. Quét mã QR Soát Vé (Instant Validation & Auto Check-in)
     * QUY TẮC: Nếu khách chưa trả đủ tiền, BẮT BUỘC phải thu tiền trước khi cho lên xe!
     */
    public function scanQr(Request $request)
    {
        $code = trim($request->input('code_or_token') ?? $request->input('qr_token') ?? '');
        $tripId = $request->input('trip_id');
        $collectCash = $request->boolean('collect_cash', false);

        if (!$code) {
            return response()->json([
                'success' => false,
                'error_type' => 'EMPTY_CODE',
                'message' => 'Vui lòng cung cấp mã QR hoặc mã vé để kiểm tra.'
            ], 400);
        }

        // Tìm vé theo qr_token, booking_code, URL, hoặc số điện thoại với độ linh hoạt cao
        $booking = Booking::findByQrOrCode(
            $code, 
            $tripId ? (int)$tripId : null, 
            ['trip.route', 'trip.bus', 'pickupStop', 'dropoffStop', 'bookingSeats']
        );

        // 2.1 Vé Không Hợp Lệ
        if (!$booking) {
            return response()->json([
                'success' => false,
                'error_type' => 'INVALID_TICKET',
                'message' => 'CẢNH BÁO: Vé không tồn tại trong hệ thống hoặc mã QR giả mạo!'
            ], 404);
        }

        // 2.2 Kiểm tra Nhầm chuyến / Nhầm ngày
        if ($tripId && $booking->trip_id != $tripId) {
            $expectedTrip = $booking->trip;
            $depTime = $expectedTrip && $expectedTrip->departure_time
                ? Carbon::parse($expectedTrip->departure_time)->format('H:i d/m/Y')
                : 'Khác';
            $routeName = $expectedTrip && $expectedTrip->route ? $expectedTrip->route->name : 'Tuyến khác';

            return response()->json([
                'success' => false,
                'error_type' => 'WRONG_TRIP',
                'message' => "CẢNH BÁO: KHÁCH ĐI NHẦM CHUYẾN! Vé này thuộc chuyến [{$expectedTrip->trip_code} - {$routeName}] khởi hành lúc {$depTime}.",
                'booking' => [
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name,
                    'customer_phone' => $booking->customer_phone,
                    'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                    'trip_code' => $expectedTrip->trip_code ?? 'N/A',
                    'route_name' => $routeName,
                    'departure_time' => $depTime,
                ]
            ]);
        }

        // 2.3 Vé đã quét rồi (Tránh vé quét 2 lần / vé lậu)
        if ($booking->checkin_status === 'CHECKED_IN') {
            $checkedAt = $booking->checked_in_at ? Carbon::parse($booking->checked_in_at)->format('H:i - d/m/Y') : 'trước đó';
            $checkedBy = $booking->checked_in_by ?: 'Lơ xe';

            return response()->json([
                'success' => false,
                'error_type' => 'ALREADY_CHECKED_IN',
                'message' => "CẢNH BÁO: Vé này ĐÃ LÊN XE trước đó lúc {$checkedAt} (Soát bởi {$checkedBy})!",
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name,
                    'customer_phone' => $booking->customer_phone,
                    'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                    'checked_in_at' => $checkedAt,
                    'checked_in_by' => $checkedBy,
                ]
            ]);
        }

        // 2.4 Khách đã bị đánh dấu Bỏ Chuyến (No-show)
        if ($booking->checkin_status === 'NO_SHOW' || $booking->checkin_status === 'CANCELLED') {
            return response()->json([
                'success' => false,
                'error_type' => 'NO_SHOW',
                'message' => 'CẢNH BÁO: Vé này đã bị ĐÁNH DẤU BỎ CHUYẾN (Ghế đã được giải phóng để bán cho khách khác)!',
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name,
                ]
            ]);
        }

        // 2.5 KIỂM TRA QUY TẮC THU TIỀN: NẾU CÒN THIẾU TIỀN -> BẮT BUỘC PHẢI THU TIỀN MỚI CHO LÊN XE
        $remainingAmount = max(0, (float) $booking->total_amount - (float) $booking->paid_amount);

        if ($remainingAmount > 0 && !$collectCash) {
            return response()->json([
                'success' => false,
                'error_type' => 'PAYMENT_REQUIRED',
                'needs_cash_collection' => true,
                'can_board' => false,
                'remaining_amount' => $remainingAmount,
                'message' => "⚠️ CẢNH BÁO: KHÁCH CHƯA TRẢ ĐỦ TIỀN! Còn thiếu: " . number_format($remainingAmount, 0, ',', '.') . "đ. Phải thu tiền rồi mới được cho lên xe!",
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name,
                    'customer_phone' => $booking->customer_phone,
                    'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                    'total_amount' => (float) $booking->total_amount,
                    'paid_amount' => (float) $booking->paid_amount,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $booking->payment_status,
                    'payment_method' => $booking->payment_method,
                ]
            ], 422);
        }

        // Nếu còn thiếu tiền và đã bấm xác nhận thu tiền mặt:
        if ($remainingAmount > 0 && $collectCash) {
            $booking->paid_amount = $booking->total_amount;
            $booking->payment_status = 'PAID';
            $booking->payment_method = 'CASH';
            $booking->notes = ($booking->notes ? $booking->notes . ' | ' : '') . 'Đã thu ' . number_format($remainingAmount, 0, ',', '.') . 'đ tiền mặt khi lên xe';
        }

        // 2.6 HỢP LỆ VÀ ĐÃ THU ĐỦ TIỀN -> ĐỔI TRẠNG THÁI SANG "ĐÃ LÊN XE"
        $booking->checkin_status = 'CHECKED_IN';
        $booking->checkin_method = 'QR_SCAN';
        $booking->checked_in_at = Carbon::now();
        $booking->checked_in_by = Auth::user() ? Auth::user()->name : 'Lơ xe';
        $booking->save();

        AuditLog::log('SCAN_QR_CHECKIN', "Soát vé QR thành công cho khách {$booking->customer_name} (Vé {$booking->booking_code}, Ghế " . implode(', ', $booking->bookingSeats->pluck('seat_number')->toArray()) . ")" . ($remainingAmount > 0 ? " - Đã thu " . number_format($remainingAmount, 0, ',', '.') . "đ tiền mặt" : ""), 'Booking', $booking->id);

        return response()->json([
            'success' => true,
            'message' => ($remainingAmount > 0 && $collectCash)
                ? "ĐÃ THU ĐỦ " . number_format($remainingAmount, 0, ',', '.') . "đ TIỀN MẶT VÀ XÁC NHẬN LÊN XE THÀNH CÔNG!"
                : "VÉ HỢP LỆ! Khách đã thanh toán đủ Online ({$booking->payment_method}). Check-in thành công!",
            'needs_cash_collection' => false,
            'remaining_amount' => 0,
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                'total_amount' => (float) $booking->total_amount,
                'paid_amount' => (float) $booking->paid_amount,
                'remaining_amount' => 0,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'checkin_status' => 'CHECKED_IN',
                'checked_in_at' => Carbon::now()->format('H:i - d/m/Y'),
                'pickup_stop' => $booking->pickupStop ? $booking->pickupStop->stop_name : 'Trạm đón',
                'dropoff_stop' => $booking->dropoffStop ? $booking->dropoffStop->stop_name : 'Trạm trả',
                'notes' => $booking->notes,
            ]
        ]);
    }

    /**
     * 3. Check-in Thủ Công
     * QUY TẮC: Khách chưa trả đủ tiền thì PHẢI THU TIỀN rồi mới được lên xe!
     */
    public function checkinManual(Request $request, $bookingId)
    {
        $booking = Booking::with('bookingSeats')->findOrFail($bookingId);
        $remainingAmount = max(0, (float) $booking->total_amount - (float) $booking->paid_amount);
        $collectCash = $request->boolean('collect_cash', false);

        // BẮT BUỘC: Nếu còn thiếu tiền mà không xác nhận thu tiền -> Chặn không cho lên xe!
        if ($remainingAmount > 0 && !$collectCash) {
            $msg = "CẢNH BÁO: Khách còn thiếu " . number_format($remainingAmount, 0, ',', '.') . "đ. Phải thu tiền mặt rồi mới được cho lên xe!";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error_type' => 'PAYMENT_REQUIRED', 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // Thu tiền mặt đủ
        if ($remainingAmount > 0 && $collectCash) {
            $booking->paid_amount = $booking->total_amount;
            $booking->payment_status = 'PAID';
            $booking->payment_method = 'CASH';
            $booking->notes = ($booking->notes ? $booking->notes . ' | ' : '') . 'Đã thu ' . number_format($remainingAmount, 0, ',', '.') . 'đ tiền mặt khi lên xe';
        }

        $booking->checkin_status = 'CHECKED_IN';
        $booking->boarding_status = 'BOARDED';
        $booking->is_boarded = 1;
        $booking->checkin_method = 'MANUAL';
        $booking->checked_in_at = Carbon::now();
        $booking->checked_in_by = Auth::user() ? Auth::user()->name : 'Lơ xe';
        $booking->save();

        $booking->bookingSeats()->update(['checkin_status' => 'boarded']);

        AuditLog::log('CHECKIN_MANUAL', "Soát vé thủ công cho khách {$booking->customer_name} (Vé {$booking->booking_code})" . ($remainingAmount > 0 ? " - Đã thu " . number_format($remainingAmount, 0, ',', '.') . "đ tiền mặt" : ""), 'Booking', $booking->id);

        $successMsg = ($remainingAmount > 0)
            ? "Đã thu " . number_format($remainingAmount, 0, ',', '.') . "đ tiền mặt và xác nhận lên xe thành công cho khách {$booking->customer_name}!"
            : "Đã xác nhận khách {$booking->customer_name} lên xe thành công (Đã thanh toán đủ)!";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * 4. Đánh Dấu Khách Bỏ Chuyến (No-show)
     */
    public function markNoShow(Request $request, $bookingId)
    {
        $booking = Booking::with('bookingSeats')->findOrFail($bookingId);
        $seatsStr = implode(', ', $booking->bookingSeats->pluck('seat_number')->toArray());

        $booking->update([
            'checkin_status' => 'NO_SHOW',
            'notes' => ($booking->notes ? $booking->notes . ' | ' : '') . 'Đã đánh dấu bỏ chuyến (No-show) lúc ' . Carbon::now()->format('H:i d/m/Y') . ' bởi ' . (Auth::user()->name ?? 'Lơ xe') . ' [Ghế ' . $seatsStr . ' lập tức chuyển thành ghế trống để bán vé dọc đường]',
        ]);

        AuditLog::log('MARK_NO_SHOW', "Đánh dấu khách {$booking->customer_name} (Vé {$booking->booking_code}) BỎ CHUYẾN (No-show). Đã giải phóng ghế [{$seatsStr}] thành ghế trống!", 'Booking', $booking->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã đánh dấu bỏ chuyến cho khách {$booking->customer_name}. Ghế {$seatsStr} đã lập tức chuyển thành Ghế Trống để bán dọc đường và không còn tính vào tiền Còn Thiếu!"
            ]);
        }

        return back()->with('success', "Đã đánh dấu khách {$booking->customer_name} BỎ CHUYẾN! Ghế {$seatsStr} đã lập tức chuyển thành Ghế Trống để bán vé dọc đường.");
    }

    /**
     * 4.1 Giải Phóng Ghế Bỏ Chuyến Thành Ghế Trống
     */
    public function releaseNoShowSeat(Request $request, $bookingId)
    {
        $booking = Booking::with('bookingSeats')->findOrFail($bookingId);
        $seatsStr = implode(', ', $booking->bookingSeats->pluck('seat_number')->toArray());

        BookingSeat::where('booking_id', $booking->id)->delete();
        $booking->update([
            'checkin_status' => 'CANCELLED',
            'notes' => ($booking->notes ? $booking->notes . ' | ' : '') . 'Đã giải phóng ghế trống lúc ' . Carbon::now()->format('H:i d/m/Y') . ' bởi ' . (Auth::user()->name ?? 'Lơ xe'),
        ]);

        AuditLog::log('RELEASE_SEAT', "Giải phóng ghế {$seatsStr} của khách bỏ chuyến {$booking->customer_name} thành ghế trống", 'Booking', $booking->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã giải phóng ghế {$seatsStr} thành ghế trống thành công!"]);
        }

        return back()->with('success', "Đã giải phóng ghế {$seatsStr} thành ghế trống thành công! Bây giờ có thể bán cho khách dọc đường.");
    }

    /**
     * 5. Xác Nhận Thu Đủ Tiền Vé Đặt Trước (Tiền Mặt khi lên xe)
     */
    public function collectPayment(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        $booking->update([
            'payment_status' => 'PAID',
            'payment_method' => 'CASH',
            'paid_amount' => $booking->total_amount,
        ]);

        AuditLog::log('COLLECT_CASH', "Lơ xe đã thu đủ tiền mặt " . number_format($booking->total_amount, 0, ',', '.') . "đ từ khách {$booking->customer_name} (Vé {$booking->booking_code})", 'Booking', $booking->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã thu đủ tiền mặt từ khách {$booking->customer_name}!"]);
        }

        return back()->with('success', "Đã xác nhận thu đủ " . number_format($booking->total_amount, 0, ',', '.') . "đ tiền mặt từ khách {$booking->customer_name}!");
    }

    /**
     * 6. Lơ xe Tạo Vé Nhanh Dọc Đường (Bán vé dọc đường)
     */
    public function createRoadsideTicket(Request $request, $tripId)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'pickup_stop_id' => 'nullable|string',
            'dropoff_stop_id' => 'required|exists:route_stops,id',
            'seat_number' => 'required|string',
            'price' => 'nullable|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($tripId);
        $stops = $trip->route ? $trip->route->stops->sortBy('stop_order') : collect();
        $pickupStopId = $request->input('pickup_stop_id');
        $isCurrentLocation = ($pickupStopId === 'current_location' || $pickupStopId === '0' || empty($pickupStopId));

        if ($isCurrentLocation) {
            $curOrder = $trip->current_stop_order ?? 1;
            $pickupStop = $stops->firstWhere('stop_order', $curOrder) ?? $stops->first();
            $roadsideNote = '📍 Đón dọc đường (Vị trí hiện tại)';
        } else {
            $pickupStop = RouteStop::find($pickupStopId) ?? $stops->first();
            $roadsideNote = 'Đón tại ' . ($pickupStop ? $pickupStop->stop_name : 'Bến xe');
        }

        $dropoffStop = RouteStop::findOrFail($request->input('dropoff_stop_id'));

        if ($pickupStop && $dropoffStop && $dropoffStop->stop_order <= $pickupStop->stop_order) {
            return back()->with('error', "Trạm trả ({$dropoffStop->stop_name}) phải nằm sau trạm đón ({$pickupStop->stop_name}) theo chiều xe chạy!");
        }

        $seatNumber = trim($request->input('seat_number'));

        // Kiểm tra ghế có bị trùng không (đồng bộ 100% với cả 4 bên)
        $isOccupied = in_array($seatNumber, SeatSegmentService::getAllBookedSeatsForTrip($trip->id));
        if ($isOccupied) {
            return back()->with('error', "Ghế {$seatNumber} đã có người đặt trên chuyến xe này! Vui lòng chọn ghế khác.");
        }

        $basePrice = (float) ($trip->base_price ?: 150000);
        $price = ($request->filled('price') && (float) $request->input('price') > 0)
            ? (float) $request->input('price')
            : $basePrice;

        $bookingCode = 'BH-ROAD-' . strtoupper(Str::random(6));

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'trip_id' => $trip->id,
            'booking_source' => 'ROADSIDE_ON_BUS',
            'customer_name' => $request->input('customer_name'),
            'customer_phone' => $request->input('customer_phone'),
            'pickup_stop_id' => $pickupStop->id,
            'dropoff_stop_id' => $dropoffStop->id,
            'pickup_order' => $pickupStop->stop_order,
            'dropoff_order' => $dropoffStop->stop_order,
            'total_seats' => 1,
            'total_amount' => $price,
            'paid_amount' => $price,
            'payment_status' => 'PAID',
            'payment_method' => $request->input('payment_method', 'CASH'),
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'MANUAL',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
            'notes' => ($isCurrentLocation ? '📍 Đón dọc đường (Vị trí hiện tại)' : $roadsideNote) . ($price < $basePrice ? " [Lơ xe linh động giảm: -" . number_format($basePrice - $price, 0, ',', '.') . "đ theo chặng]" : ""),
        ]);

        // Xóa sạch BookingSeat cũ của bất kỳ vé nào đã bị Hủy hoặc Bỏ Chuyến trên ghế này (nếu còn)
        BookingSeat::where('trip_id', $trip->id)
            ->where('seat_number', $seatNumber)
            ->whereHas('booking', function ($q) {
                $q->whereIn('checkin_status', ['NO_SHOW', 'CANCELLED']);
            })
            ->delete();

        BookingSeat::create([
            'booking_id' => $booking->id,
            'trip_id' => $trip->id,
            'seat_number' => $seatNumber,
            'floor' => str_starts_with($seatNumber, 'B') ? 2 : 1,
            'pickup_order' => $pickupStop->stop_order,
            'dropoff_order' => $dropoffStop->stop_order,
            'price' => $price,
        ]);

        $busPlate = $trip->bus?->license_plate ?? '43B-012.34';
        AuditLog::log(
            'ROADSIDE_TICKET',
            "Lơ xe bán vé dọc đường cho khách {$booking->customer_name} (Ghế {$seatNumber}, thực thu: " . number_format($price, 0, ',', '.') . "đ / giá niêm yết: " . number_format($basePrice, 0, ',', '.') . "đ)",
            'Booking',
            $booking->id,
            $trip->id,
            $busPlate,
            'conductor'
        );

        return back()->with('success', "Đã bán vé dọc đường cho khách {$booking->customer_name} (Ghế {$seatNumber}, thu " . number_format($price, 0, ',', '.') . "đ) thành công!");
    }

    /**
     * 7. Tình Huống 3: Khách đổi chỗ cho người nhà (Seat Swap)
     */
    public function changeSeat(Request $request, $bookingId)
    {
        $request->validate([
            'new_seat_number' => 'required|string',
        ]);

        $booking = Booking::with('bookingSeats')->findOrFail($bookingId);
        $newSeat = trim($request->input('new_seat_number'));

        // Kiểm tra xem ghế mới có trống không (đồng bộ 100% với cả 4 bên)
        $allOccupied = SeatSegmentService::getAllBookedSeatsForTrip($booking->trip_id);
        $currentBookingSeats = $booking->bookingSeats->pluck('seat_number')->toArray();
        $isOccupied = in_array($newSeat, $allOccupied) && !in_array($newSeat, $currentBookingSeats);

        if ($isOccupied) {
            return back()->with('error', "Ghế {$newSeat} hiện đang có khách ngồi! Vui lòng chọn ghế khác.");
        }

        $oldSeat = $booking->bookingSeats->first() ? $booking->bookingSeats->first()->seat_number : 'N/A';

        // Cập nhật số ghế
        if ($booking->bookingSeats->isNotEmpty()) {
            $seatRecord = $booking->bookingSeats->first();
            $seatRecord->update([
                'seat_number' => $newSeat,
                'floor' => str_starts_with($newSeat, 'B') ? 2 : 1,
            ]);
        }

        $booking->update([
            'notes' => ($booking->notes ? $booking->notes . ' | ' : '') . "Đổi từ ghế {$oldSeat} sang {$newSeat} bởi " . (Auth::user()->name ?? 'Lơ xe'),
        ]);

        AuditLog::log('CHANGE_SEAT', "Lơ xe đổi chỗ cho khách {$booking->customer_name} từ ghế {$oldSeat} sang {$newSeat}", 'Booking', $booking->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã đổi khách {$booking->customer_name} sang ghế {$newSeat} thành công!"]);
        }

        return back()->with('success', "Đã đổi khách {$booking->customer_name} từ ghế {$oldSeat} sang {$newSeat} thành công!");
    }

    /**
     * 8. Cập nhật Trạng thái Chuyến đi
     */
    public function updateTripStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SCHEDULED,BOARDING,IN_TRANSIT,COMPLETED,CANCELLED',
        ]);

        $trip = Trip::findOrFail($id);
        $trip->update(['status' => $request->input('status')]);

        AuditLog::log('UPDATE_TRIP_STATUS', "Cập nhật trạng thái chuyến sang {$request->input('status')}", 'Trip', $trip->id);

        return back()->with('success', 'Đã cập nhật trạng thái chuyến xe!');
    }

    /**
     * 9. Cập nhật Trạm hiện tại
     */
    public function updateCurrentStop(Request $request, $id)
    {
        $request->validate([
            'stop_order' => 'required|integer',
        ]);

        $trip = Trip::findOrFail($id);
        $trip->update(['current_stop_order' => $request->input('stop_order')]);

        AuditLog::log('UPDATE_TRIP_STOP', "Cập nhật xe đã đến trạm số {$request->input('stop_order')}", 'Trip', $trip->id);

        return back()->with('success', 'Đã cập nhật trạm xe đến!');
    }

    /**
     * 10. Ghi nhận Chi phí phát sinh (Cầu đường, bến bãi, rửa xe, xăng dầu)
     */
    public function storeExpense(Request $request, $tripId)
    {
        $validated = $request->validate([
            'expense_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expense = TripExpense::create([
            'trip_id' => $tripId,
            'expense_type' => $validated['expense_type'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'],
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('ADD_EXPENSE', "Ghi chi phí {$validated['expense_type']}: " . number_format($validated['amount']) . "đ", 'TripExpense', $expense->id);

        return back()->with('success', 'Đã lưu chi phí chuyến xe!');
    }

    /**
     * 11. Báo cáo Sự cố kỹ thuật / Va quẹt trên đường
     */
    public function reportIncident(Request $request, $tripId)
    {
        $trip = Trip::findOrFail($tripId);

        $validated = $request->validate([
            'type' => 'required|string',
            'severity' => 'required|string',
            'description' => 'required|string',
            'estimated_delay_minutes' => 'nullable|integer',
        ]);

        $incident = Incident::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'driver_id' => $trip->driver_id,
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'description' => $validated['description'],
            'estimated_delay_minutes' => $validated['estimated_delay_minutes'] ?? 0,
            'status' => 'PENDING',
        ]);

        AuditLog::log('REPORT_INCIDENT', "Báo sự cố [{$validated['type']}]: {$validated['description']}", 'Trip', $trip->id);

        return back()->with('success', 'Đã gửi báo cáo sự cố về ban điều hành bến xe!');
    }

    /**
     * 12. Ghi nhận Đồ đạc thất lạc (Lost & Found)
     */
    public function storeLostFound(Request $request, $tripId)
    {
        $validated = $request->validate([
            'item_name' => 'required|string',
            'seat_number' => 'nullable|string',
            'description' => 'nullable|string',
            'found_location' => 'nullable|string',
        ]);

        $item = LostFound::create([
            'trip_id' => $tripId,
            'item_name' => $validated['item_name'],
            'seat_number' => $validated['seat_number'],
            'description' => $validated['description'],
            'found_location' => $validated['found_location'] ?? 'Trên xe',
            'status' => 'FOUND',
            'recorded_by' => Auth::id(),
        ]);

        AuditLog::log('LOST_FOUND', "Ghi nhận đồ để quên: {$validated['item_name']}", 'LostFound', $item->id);

        return back()->with('success', 'Đã lưu vật phẩm khách để quên!');
    }

    /**
     * 13. Chốt ca Chuyến Đi (Settlement & Remittance)
     */
    public function closeTrip(Request $request, $tripId)
    {
        $trip = Trip::with(['bookings', 'expenses'])->findOrFail($tripId);

        $validated = $request->validate([
            'actual_cash_submitted' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $activeBookings = $trip->bookings->whereNotIn('checkin_status', ['NO_SHOW', 'CANCELLED']);
        $cashCollected = $activeBookings->where('payment_method', 'CASH')->where('payment_status', 'PAID')->sum('paid_amount');
        $totalExpenses = $trip->expenses->sum('amount');
        $expectedCash = max(0, $cashCollected - $totalExpenses);
        $actualCash = (float) $validated['actual_cash_submitted'];
        $diff = $actualCash - $expectedCash;

        $closing = TripClosing::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'total_revenue' => $cashCollected,
                'total_expenses' => $totalExpenses,
                'net_profit' => max(0, $cashCollected - $totalExpenses),
                'expected_cash_from_conductor' => $expectedCash,
                'actual_cash_submitted' => $actualCash,
                'cash_discrepancy' => $diff,
                'closed_by' => Auth::user()?->name ?? 'Lơ xe',
                'trip_direction' => str_contains($trip->route?->name ?? '', 'Đà Nẵng ⇄') ? 'Chuyến Đi' : 'Chuyến Về',
                'audit_status' => 'PENDING_AUDIT',
                'notes' => $validated['notes'],
            ]
        );

        $trip->update([
            'status' => 'CLOSED',
            'closed_at' => Carbon::now(),
            'closed_by' => Auth::id(),
        ]);

        AuditLog::log('CLOSE_TRIP', "Chốt ca chuyến {$trip->trip_code}. Thực nộp: " . number_format($actualCash) . "đ (Chênh lệch: " . number_format($diff) . "đ)", 'TripClosing', $closing->id);

        return back()->with('success', 'Chốt ca chuyến xe thành công! Dữ liệu đã chuyển sang phòng kế toán.');
    }
}
