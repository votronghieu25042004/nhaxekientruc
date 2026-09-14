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
use App\Services\SeatSegmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConductorController extends Controller
{
    // 1. Dashboard chính của Lơ xe
    public function dashboard(Request $request)
    {
        $activeTrips = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver', 'bookings.bookingSeats', 'bookings.pickupStop', 'bookings.dropoffStop', 'expenses', 'closing', 'incidents', 'lostFounds'])
            ->orderBy('departure_time', 'asc')
            ->get();

        $tripId = $request->input('trip_id');
        $selectedTrip = $tripId 
            ? $activeTrips->firstWhere('id', $tripId) 
            : ($activeTrips->whereIn('status', ['IN_TRANSIT', 'BOARDING', 'OPEN_FOR_SALE'])->first() ?? $activeTrips->first());

        $occupiedSeats = [];
        $stops = [];

        if ($selectedTrip) {
            $stops = $selectedTrip->route->stops;
            $curOrder = $selectedTrip->current_stop_order ?? 0;
            $nextOrder = min($curOrder + 1, $stops->max('stop_order'));
            $occupiedSeats = SeatSegmentService::getOccupiedSeats($selectedTrip->id, $curOrder, $nextOrder);
        }

        return Inertia::render('Staff/Dashboard', [
            'trips' => $activeTrips,
            'selectedTrip' => $selectedTrip,
            'occupiedSeats' => $occupiedSeats,
            'stops' => $stops,
            'user' => Auth::user(),
        ]);
    }

    // 2. Chuyển trạng thái Chuyến xe (Khởi hành, Cập bến, v.v.)
    public function updateTripStatus(Request $request, $tripId)
    {
        $trip = Trip::with('bus')->findOrFail($tripId);
        $newStatus = $request->input('status');

        $trip->status = $newStatus;
        if ($newStatus === 'IN_TRANSIT' && !$trip->departed_at) {
            $trip->departed_at = Carbon::now();
        } elseif ($newStatus === 'ARRIVED' && !$trip->completed_at) {
            $trip->completed_at = Carbon::now();
        }
        $trip->save();

        AuditLog::log('UPDATE_STATUS', "Chuyển trạng thái chuyến {$trip->trip_code} thành {$newStatus}", 'Trip', $trip->id);

        return back()->with('success', "Đã chuyển trạng thái chuyến xe sang: {$newStatus}");
    }

    // 3. Cập nhật vị trí trạm xe hiện tại (Tiến độ xe chạy)
    public function updateCurrentStop(Request $request, $tripId)
    {
        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($tripId);
        $stopOrder = $request->input('stop_order', 0);

        $trip->current_stop_order = $stopOrder;
        $trip->save();

        $stop = $trip->route->stops->where('stop_order', $stopOrder)->first();
        $stopName = $stop ? $stop->stop_name : "Trạm #{$stopOrder}";

        AuditLog::log('UPDATE_LOCATION', "Xe {$trip->bus->license_plate} đã đến trạm: {$stopName}", 'Trip', $trip->id);

        return back()->with('success', "Đã cập nhật vị trí hiện tại của xe đến: {$stopName}");
    }

    // 4. Lơ xe / Admin Tạo vé nhanh cho khách đón dọc đường (Seat Inventory by Segment)
    public function createRoadsideTicket(Request $request, $tripId)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'pickup_stop_id' => 'required|exists:route_stops,id',
            'dropoff_stop_id' => 'required|exists:route_stops,id',
            'seat_number' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $trip = Trip::with('route.stops', 'bus')->findOrFail($tripId);
        $pickupStop = RouteStop::findOrFail($request->pickup_stop_id);
        $dropoffStop = RouteStop::findOrFail($request->dropoff_stop_id);

        if ($pickupStop->stop_order >= $dropoffStop->stop_order) {
            return back()->with('error', 'Điểm trả phải nằm sau điểm đón!');
        }

        // Rule 10: Không được chọn điểm đón phía sau xe
        if ($trip->status === 'IN_TRANSIT' && $pickupStop->stop_order < $trip->current_stop_order) {
            return back()->with('error', 'Không thể tạo vé tại điểm đón mà xe đã đi qua!');
        }

        // Kiểm tra ghế có bị trùng trên đoạn [pickup_order, dropoff_order]
        $occupiedSeats = SeatSegmentService::getOccupiedSeats($trip->id, $pickupStop->stop_order, $dropoffStop->stop_order);
        if (in_array($request->seat_number, $occupiedSeats)) {
            return back()->with('error', "Ghế {$request->seat_number} đã có khách chiếm trên đoạn đường này!");
        }

        $fare = SeatSegmentService::getFare($trip->route_id, $pickupStop->id, $dropoffStop->id, $trip->bus->bus_type);
        $bookingSource = Auth::user() && Auth::user()->role === 'admin' ? 'ADMIN' : 'STAFF_ON_BUS';

        $booking = Booking::create([
            'booking_code' => 'FUTA-' . strtoupper(Str::random(5)),
            'trip_id' => $trip->id,
            'booking_source' => $bookingSource,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'pickup_stop_id' => $pickupStop->id,
            'dropoff_stop_id' => $dropoffStop->id,
            'pickup_order' => $pickupStop->stop_order,
            'dropoff_order' => $dropoffStop->stop_order,
            'total_seats' => 1,
            'total_amount' => $fare,
            'paid_amount' => $fare,
            'payment_status' => 'PAID',
            'payment_method' => $request->payment_method,
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'MANUAL',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
            'qr_token' => 'QR-' . Str::uuid()->toString(),
            'created_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
        ]);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'trip_id' => $trip->id,
            'seat_number' => $request->seat_number,
            'floor' => str_starts_with($request->seat_number, 'B') ? 2 : 1,
            'pickup_order' => $pickupStop->stop_order,
            'dropoff_order' => $dropoffStop->stop_order,
            'price' => $fare,
        ]);

        AuditLog::log('CREATE_ROADSIDE_TICKET', "Tạo vé {$booking->booking_code} cho khách dọc đường {$request->customer_name} (Ghế {$request->seat_number}, {$pickupStop->stop_name} ➔ {$dropoffStop->stop_name}, {$fare}đ)", 'Booking', $booking->id);

        return back()->with('success', "Đã tạo vé thành công cho khách {$request->customer_name} (Ghế {$request->seat_number})!");
    }

    // 5. Đổi Ghế cho Khách (Seat Swap)
    public function changeSeat(Request $request, $bookingId)
    {
        $request->validate([
            'new_seat_number' => 'required|string',
        ]);

        $booking = Booking::with('bookingSeats')->findOrFail($bookingId);
        $newSeat = $request->new_seat_number;

        // Check availability on this segment
        $occupiedSeats = SeatSegmentService::getOccupiedSeats($booking->trip_id, $booking->pickup_order, $booking->dropoff_order);
        if (in_array($newSeat, $occupiedSeats)) {
            return back()->with('error', "Ghế {$newSeat} đã có khách trên đoạn đường này!");
        }

        $oldSeat = $booking->bookingSeats->first()->seat_number ?? 'N/A';

        // Update booking seat
        BookingSeat::where('booking_id', $booking->id)->update([
            'seat_number' => $newSeat,
            'floor' => str_starts_with($newSeat, 'B') ? 2 : 1,
        ]);

        AuditLog::log('CHANGE_SEAT', "Đổi ghế cho khách {$booking->customer_name} (Vé {$booking->booking_code}) từ {$oldSeat} sang {$newSeat}", 'Booking', $booking->id);

        return back()->with('success', "Đã đổi ghế thành công từ {$oldSeat} sang {$newSeat}!");
    }

    // 6. Check-in Thủ công hoặc Thu nốt tiền
    public function checkinManual(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        $booking->update([
            'paid_amount' => $booking->total_amount,
            'payment_status' => 'PAID',
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'MANUAL',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
        ]);

        AuditLog::log('CHECKIN_MANUAL', "Soát vé thủ công & thu tiền cho khách {$booking->customer_name} (Vé {$booking->booking_code})", 'Booking', $booking->id);

        return back()->with('success', "Đã xác nhận check-in và hoàn tất thanh toán cho khách {$booking->customer_name}!");
    }

    // 7. Nhập Chi phí phát sinh của Chuyến xe
    public function storeExpense(Request $request, $tripId)
    {
        $validated = $request->validate([
            'expense_type' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'description' => 'required|string|max:255',
        ]);

        $validated['trip_id'] = $tripId;
        $validated['created_by'] = Auth::user() ? Auth::user()->name : 'Lơ xe';

        TripExpense::create($validated);

        AuditLog::log('ADD_EXPENSE', "Thêm chi phí {$validated['expense_type']}: {$validated['amount']}đ ({$validated['description']})", 'Trip', $tripId);

        return back()->with('success', 'Đã ghi nhận chi phí vào chuyến xe!');
    }

    // 8. Báo Sự Cố Chuyến Xe (Incidents)
    public function reportIncident(Request $request, $tripId)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
            'severity' => 'required|string',
        ]);

        $trip = Trip::findOrFail($tripId);
        $incident = Incident::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => 'REPORTED',
            'reported_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
        ]);

        AuditLog::log('REPORT_INCIDENT', "Báo sự cố [{$validated['type']}]: {$validated['description']}", 'Trip', $trip->id);

        return back()->with('success', 'Đã gửi báo cáo sự cố về trung tâm điều hành!');
    }

    // 9. Ghi Nhận Đồ Thất Lạc (Lost & Found)
    public function storeLostFound(Request $request, $tripId)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'seat_number' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $lostFound = LostFound::create([
            'trip_id' => $tripId,
            'item_name' => $validated['item_name'],
            'seat_number' => $validated['seat_number'],
            'description' => $validated['description'],
            'found_by' => Auth::user() ? Auth::user()->name : 'Lơ xe',
            'status' => 'FOUND',
        ]);

        AuditLog::log('LOST_FOUND', "Ghi nhận đồ thất lạc: {$validated['item_name']} tại ghế {$validated['seat_number']}", 'Trip', $tripId);

        return back()->with('success', 'Đã lưu thông tin đồ thất lạc vào hệ thống Lost & Found!');
    }

    // 10. Chốt Chuyến & Đối soát Tiền mặt
    public function closeTrip(Request $request, $tripId)
    {
        $trip = Trip::with('bookings', 'expenses')->findOrFail($tripId);

        $totalRevenue = $trip->bookings->sum('total_amount');
        $totalExpenses = $trip->expenses->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        $expectedCash = $trip->bookings->where('payment_method', 'CASH')->where('payment_status', 'PAID')->sum('paid_amount');
        $actualCash = $request->input('actual_cash_submitted', $expectedCash);
        $discrepancy = $actualCash - $expectedCash;

        TripClosing::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'expected_cash_from_conductor' => $expectedCash,
                'actual_cash_submitted' => $actualCash,
                'cash_discrepancy' => $discrepancy,
                'closed_by' => Auth::user() ? Auth::user()->name : 'Lơ xe / Admin',
                'notes' => $request->notes,
            ]
        );

        $trip->update([
            'status' => 'CLOSED',
            'closed_at' => Carbon::now(),
            'closed_by' => Auth::user() ? Auth::user()->name : 'Lơ xe / Admin',
        ]);

        AuditLog::log('CLOSE_TRIP', "Chốt chuyến {$trip->trip_code}: Doanh thu {$totalRevenue}đ, Chi phí {$totalExpenses}đ, Lợi nhuận {$netProfit}đ, Tiền thực nộp {$actualCash}đ", 'Trip', $trip->id);

        return back()->with('success', "Chốt chuyến thành công! Lợi nhuận chuyến xe đạt " . number_format($netProfit, 0, ',', '.') . "đ.");
    }
}
