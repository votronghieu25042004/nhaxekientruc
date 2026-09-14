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
        $drivers = Driver::orderBy('id', 'asc')->get();
        
        // Chọn tài xế đang thao tác (mặc định lấy tài xế #1 hoặc theo param driver_id)
        $driverId = $request->input('driver_id', $drivers->first()->id ?? 1);
        $currentDriver = Driver::find($driverId) ?? $drivers->first();

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $next7Days = Carbon::today()->addDays(7);

        // Danh sách chuyến xe của tài xế này
        $query = Trip::with([
            'route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'),
            'bus',
            'conductor',
            'driver',
            'bookings.bookingSeats',
            'bookings.pickupStop',
            'bookings.dropoffStop',
            'expenses',
            'closing',
            'incidents',
            'lostFounds'
        ])
        ->where('driver_id', $currentDriver ? $currentDriver->id : 0)
        ->orderBy('departure_time', 'asc');

        $allDriverTrips = (clone $query)->get();

        // Phân loại chuyến
        $todayTrips = $allDriverTrips->filter(function($t) use ($today) {
            return Carbon::parse($t->departure_time)->isToday() || in_array($t->status, ['IN_TRANSIT', 'READY', 'DRIVER_CONFIRMED', 'OPEN_FOR_SALE', 'BOARDING']);
        })->values();

        $tomorrowTrips = $allDriverTrips->filter(function($t) use ($tomorrow) {
            return Carbon::parse($t->departure_time)->isTomorrow();
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
            : ($todayTrips->first() ?? $allDriverTrips->first());

        $occupiedSeats = [];
        $stops = [];
        if ($selectedTrip) {
            $stops = $selectedTrip->route?->stops ?? [];
            $curOrder = $selectedTrip->current_stop_order ?? 0;
            $nextOrder = min($curOrder + 1, count($stops));
            $occupiedSeats = SeatSegmentService::getOccupiedSeats($selectedTrip->id, $curOrder, $nextOrder);
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
            'drivers' => $drivers,
            'currentDriver' => $currentDriver,
            'selectedTrip' => $selectedTrip,
            'todayTrips' => $todayTrips,
            'tomorrowTrips' => $tomorrowTrips,
            'upcomingTrips' => $upcomingTrips,
            'historyTrips' => $historyTrips,
            'allTrips' => $allDriverTrips,
            'occupiedSeats' => $occupiedSeats,
            'stops' => $stops,
            'notifications' => $notifications,
            'driverReviews' => $driverReviews,
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

        return back()->with('success', 'Đã phát tín hiệu cảnh báo khẩn cấp tới Tổng đài điều hành FUTA!');
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
        $trip = Trip::findOrFail($id);
        $trip->status = 'COMPLETED';
        $trip->completed_at = now();
        $trip->actual_arrival_time = now();
        $trip->save();

        if ($trip->bus) {
            $trip->bus->status = 'ACTIVE';
            $trip->bus->save();
        }

        AuditLog::create([
            'trip_id' => $trip->id,
            'action' => 'TRIP_COMPLETED',
            'description' => "Tài xế {$trip->driver?->name} đã hoàn thành chuyến xe an toàn tại điểm cuối.",
            'performed_by' => $trip->driver?->name ?? 'Tài xế'
        ]);

        return back()->with('success', 'Chúc mừng bạn đã hoàn thành chuyến xe an toàn!');
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

}
