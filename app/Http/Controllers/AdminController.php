<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\TripExpense;
use App\Models\TripClosing;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\SeatSegmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // 1. Dashboard Tổng Quan
    public function dashboard()
    {
        $today = Carbon::today();

        $totalBuses = Bus::count();
        $runningBuses = Bus::where('status', 'RUNNING')->orWhere('status', 'ACTIVE')->count();
        $maintenanceBuses = Bus::where('status', 'MAINTENANCE')->count();
        $inactiveBuses = Bus::whereIn('status', ['BROKEN', 'SUSPENDED', 'WAITING'])->count();

        $todayTripsCount = Trip::whereDate('departure_time', $today)->count();
        $todayPassengers = Booking::whereDate('created_at', $today)->sum('total_seats');

        $todayRevenue = (float) Booking::whereDate('created_at', $today)->sum('total_amount');
        $todayCollected = (float) Booking::whereDate('created_at', $today)->sum('paid_amount');
        $todayPending = $todayRevenue - $todayCollected;

        $recentTrips = Trip::with(['route', 'bus', 'driver', 'conductor', 'bookings'])
            ->latest('departure_time')
            ->take(8)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_name' => $t->route ? $t->route->name : 'N/A',
                    'bus_plate' => $t->bus ? $t->bus->license_plate : 'N/A',
                    'driver_name' => $t->driver ? $t->driver->name : 'Chưa gán',
                    'departure_time' => $t->departure_time->format('H:i - d/m/Y'),
                    'status' => $t->status,
                    'total_passengers' => $t->bookings->sum('total_seats'),
                    'total_revenue' => (float) $t->bookings->sum('total_amount'),
                ];
            });

        $recentLogs = AuditLog::latest()->take(10)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_buses' => $totalBuses,
                'running_buses' => $runningBuses,
                'maintenance_buses' => $maintenanceBuses,
                'inactive_buses' => $inactiveBuses,
                'today_trips' => $todayTripsCount,
                'today_passengers' => $todayPassengers,
                'today_revenue' => $todayRevenue,
                'today_collected' => $todayCollected,
                'today_pending' => $todayPending,
            ],
            'recentTrips' => $recentTrips,
            'recentLogs' => $recentLogs,
            'user' => Auth::user(),
        ]);
    }

    // 2. Quản lý Tuyến đường & Trạm dừng
    public function routes()
    {
        $routes = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'fares'])
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'origin' => $r->origin,
                    'destination' => $r->destination,
                    'distance_km' => $r->distance_km,
                    'estimated_hours' => $r->estimated_hours,
                    'stops' => $r->stops,
                    'fares_count' => $r->fares->count(),
                    'trips_count' => Trip::where('route_id', $r->id)->count(),
                ];
            });

        return Inertia::render('Admin/Routes', [
            'routes' => $routes,
        ]);
    }

    public function storeRoute(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:1',
            'estimated_hours' => 'required|numeric|min:0.1',
            'stops' => 'required|array|min:2',
            'base_fare' => 'required|numeric|min:10000',
        ]);

        $route = Route::create([
            'name' => $validated['name'],
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'distance_km' => $validated['distance_km'],
            'estimated_hours' => $validated['estimated_hours'],
        ]);

        $createdStops = [];
        foreach ($validated['stops'] as $index => $stopName) {
            $createdStops[] = RouteStop::create([
                'route_id' => $route->id,
                'stop_name' => trim($stopName),
                'stop_order' => $index,
                'distance_from_start_km' => round(($validated['distance_km'] / (count($validated['stops']) - 1)) * $index, 1),
            ]);
        }

        $firstStop = $createdStops[0];
        $lastStop = end($createdStops);
        RouteFare::create([
            'route_id' => $route->id,
            'pickup_stop_id' => $firstStop->id,
            'dropoff_stop_id' => $lastStop->id,
            'fare_amount' => $validated['base_fare'],
        ]);

        AuditLog::log('CREATE_ROUTE', "Tạo tuyến mới: {$route->name} ({$route->distance_km} km, " . count($createdStops) . " trạm dừng)", 'Route', $route->id);

        return back()->with('success', "Đã tạo tuyến đường '{$route->name}' thành công!");
    }

    // 3. Quản lý Chuyến xe (Trips)
    public function trips()
    {
        $trips = Trip::with(['route.stops', 'bus', 'driver', 'conductor', 'bookings'])
            ->orderBy('departure_time', 'asc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_id' => $t->route_id,
                    'route_name' => $t->route ? $t->route->name : 'N/A',
                    'bus_plate' => $t->bus ? $t->bus->license_plate : 'N/A',
                    'bus_type' => $t->bus ? $t->bus->bus_type : 'N/A',
                    'driver_name' => $t->driver ? $t->driver->name : 'Chưa gán',
                    'driver_phone' => $t->driver ? $t->driver->phone : '',
                    'conductor_name' => $t->conductor ? $t->conductor->name : 'Chưa gán',
                    'departure_time' => $t->departure_time->format('H:i - d/m/Y'),
                    'arrival_time' => $t->arrival_time ? $t->arrival_time->format('H:i - d/m/Y') : null,
                    'base_price' => (float) $t->base_price,
                    'floor1_price' => (float) ($t->floor1_price ?? $t->base_price),
                    'floor2_price' => (float) ($t->floor2_price ?? $t->base_price),
                    'floor1_seat_type' => $t->floor1_seat_type ?? 'Giường Nằm VIP (Tầng 1)',
                    'floor2_seat_type' => $t->floor2_seat_type ?? 'Giường Nằm Tiêu Chuẩn (Tầng 2)',
                    'status' => $t->status,
                    'current_stop_order' => $t->current_stop_order,
                    'total_passengers' => $t->bookings->sum('total_seats'),
                    'total_seats' => $t->bus ? $t->bus->total_seats : 34,
                    'total_revenue' => (float) $t->bookings->sum('total_amount'),
                ];
            });

        $routesList = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc')])->get();
        $driversList = Driver::where('status', 'ACTIVE')->get();
        $conductorsList = User::where('role', 'staff')->get();
        $busesList = Bus::where('status', 'ACTIVE')->get();

        return Inertia::render('Admin/Trips', [
            'trips' => $trips,
            'routesList' => $routesList,
            'driversList' => $driversList,
            'conductorsList' => $conductorsList,
            'busesList' => $busesList,
        ]);
    }

    public function storeTrip(Request $request)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:drivers,id',
            'conductor_id' => 'nullable|exists:users,id',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'base_price' => 'required|numeric|min:10000',
            'floor1_price' => 'nullable|numeric|min:10000',
            'floor2_price' => 'nullable|numeric|min:10000',
            'floor1_seat_type' => 'nullable|string',
            'floor2_seat_type' => 'nullable|string',
        ]);

        $tripCode = 'TRIP-FUTA-' . strtoupper(Str::random(4));

        $floor1Price = $validated['floor1_price'] ?? $validated['base_price'];
        $floor2Price = $validated['floor2_price'] ?? $validated['base_price'];

        $trip = Trip::create([
            'trip_code' => $tripCode,
            'route_id' => $validated['route_id'],
            'bus_id' => $validated['bus_id'],
            'driver_id' => $validated['driver_id'],
            'conductor_id' => $validated['conductor_id'] ?? Auth::id(),
            'departure_time' => Carbon::parse($validated['departure_time']),
            'arrival_time' => Carbon::parse($validated['arrival_time']),
            'base_price' => $validated['base_price'],
            'floor1_price' => $floor1Price,
            'floor2_price' => $floor2Price,
            'floor1_seat_type' => $validated['floor1_seat_type'] ?? 'Giường Nằm VIP (Tầng 1)',
            'floor2_seat_type' => $validated['floor2_seat_type'] ?? 'Giường Nằm Tiêu Chuẩn (Tầng 2)',
            'status' => 'OPEN_FOR_SALE',
            'current_stop_order' => 0,
        ]);

        AuditLog::log('CREATE_TRIP', "Tạo chuyến xe mới {$trip->trip_code} (Tầng 1: {$floor1Price}đ, Tầng 2: {$floor2Price}đ)", 'Trip', $trip->id);

        return back()->with('success', "Đã tạo chuyến xe '{$trip->trip_code}' thành công!");
    }

    // Điều chỉnh giá vé chuyến xe (Dịp Lễ Tăng Giá / Hết Lễ Giảm Giá)
    public function updateTripPrice(Request $request, $id)
    {
        $validated = $request->validate([
            'floor1_price' => 'required|numeric|min:10000',
            'floor2_price' => 'required|numeric|min:10000',
            'floor1_seat_type' => 'nullable|string',
            'floor2_seat_type' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $trip = Trip::findOrFail($id);
        $oldF1 = $trip->floor1_price;
        $oldF2 = $trip->floor2_price;

        $trip->update([
            'base_price' => $validated['floor1_price'],
            'floor1_price' => $validated['floor1_price'],
            'floor2_price' => $validated['floor2_price'],
            'floor1_seat_type' => $validated['floor1_seat_type'] ?? $trip->floor1_seat_type,
            'floor2_seat_type' => $validated['floor2_seat_type'] ?? $trip->floor2_seat_type,
        ]);

        $reasonText = $validated['reason'] ? " (Lý do: {$validated['reason']})" : "";
        AuditLog::log('UPDATE_TRIP_PRICE', "Điều chỉnh giá vé chuyến {$trip->trip_code}: Tầng 1: {$oldF1}đ ➔ {$trip->floor1_price}đ, Tầng 2: {$oldF2}đ ➔ {$trip->floor2_price}đ{$reasonText}", 'Trip', $trip->id);

        return back()->with('success', "Đã cập nhật giá vé chuyến {$trip->trip_code} thành công!");
    }

    public function updateTripStatus(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $oldStatus = $trip->status;
        $newStatus = $request->input('status', 'OPEN_FOR_SALE');

        $trip->status = $newStatus;
        if ($newStatus === 'IN_TRANSIT' && !$trip->departed_at) {
            $trip->departed_at = Carbon::now();
        }
        if ($newStatus === 'COMPLETED' && !$trip->completed_at) {
            $trip->completed_at = Carbon::now();
        }
        $trip->save();

        AuditLog::log('UPDATE_TRIP_STATUS', "Chuyển trạng thái chuyến {$trip->trip_code} từ {$oldStatus} sang {$newStatus}", 'Trip', $trip->id);

        return back()->with('success', "Đã chuyển trạng thái chuyến xe sang: {$newStatus}");
    }

    public function assignDriver(Request $request, $tripId)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $trip = Trip::with(['route', 'bus'])->findOrFail($tripId);
        $driver = Driver::findOrFail($request->driver_id);

        $dep = \Carbon\Carbon::parse($trip->departure_time);
        $arr = $trip->arrival_time ? \Carbon\Carbon::parse($trip->arrival_time) : $dep->copy()->addMinutes(($trip->route?->estimated_hours ?? 3.5) * 60);

        // Chống trùng giờ: Kiểm tra xem tài xế đã có chuyến nào đang chạy hoặc được gán trong khoảng [dep - 1h, arr + 1h]
        $conflict = Trip::where('driver_id', $driver->id)
            ->where('id', '!=', $trip->id)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where(function ($q) use ($dep, $arr) {
                $q->whereBetween('departure_time', [$dep->copy()->subHours(1), $arr->copy()->addHours(1)])
                  ->orWhereBetween('arrival_time', [$dep->copy()->subHours(1), $arr->copy()->addHours(1)]);
            })
            ->first();

        if ($conflict) {
            $confDep = \Carbon\Carbon::parse($conflict->departure_time)->format('H:i - d/m');
            $confArr = $conflict->arrival_time ? \Carbon\Carbon::parse($conflict->arrival_time)->format('H:i') : 'Đến nơi';
            return back()->with('error', "TRÙNG LỊCH: Bác tài {$driver->name} đã có lịch chạy chuyến {$conflict->trip_code} ({$confDep} -> {$confArr}). Vui lòng chọn tài xế khác!");
        }

        $trip->driver_id = $driver->id;
        $trip->status = 'WAITING_DRIVER_CONFIRM'; // Chờ tài xế xác nhận nhận chuyến trên App
        $trip->save();

        AuditLog::log('ASSIGN_DRIVER', "Admin điều phối bác tài {$driver->name} cho chuyến {$trip->trip_code} ({$dep->format('H:i')} - {$arr->format('H:i')}). Chờ tài xế xác nhận.", 'Trip', $trip->id);

        return back()->with('success', "Đã gửi lệnh điều phối chuyến {$trip->trip_code} cho Bác tài {$driver->name}. Đang chờ tài xế xác nhận trên App!");
    }

    // 4. Bán Vé Tại Quầy (Thu đủ 100%)
    public function counterBook(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'pickup_stop_id' => 'required|exists:route_stops,id',
            'dropoff_stop_id' => 'required|exists:route_stops,id',
            'seat_number' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($request->trip_id);
        $pickupStop = RouteStop::findOrFail($request->pickup_stop_id);
        $dropoffStop = RouteStop::findOrFail($request->dropoff_stop_id);

        if ($pickupStop->stop_order >= $dropoffStop->stop_order) {
            return back()->with('error', 'Điểm trả phải nằm sau điểm đón!');
        }

        $occupiedSeats = SeatSegmentService::getOccupiedSeats($trip->id, $pickupStop->stop_order, $dropoffStop->stop_order);
        if (in_array($request->seat_number, $occupiedSeats)) {
            return back()->with('error', "Ghế {$request->seat_number} đã có khách trên đoạn này!");
        }

        $baseFare = SeatSegmentService::getFare($trip->route_id, $pickupStop->id, $dropoffStop->id, $trip->bus->bus_type);
        $isFloor2 = str_starts_with($request->seat_number, 'B');
        $fare = $isFloor2 ? ($trip->floor2_price ?? $baseFare) : ($trip->floor1_price ?? $baseFare);

        $bookingSource = Auth::user() && Auth::user()->role === 'admin' ? 'ADMIN' : 'STAFF';

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
            'paid_amount' => $fare, // Thu đủ 100% tại quầy
            'payment_status' => 'PAID',
            'payment_method' => $request->payment_method,
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'MANUAL',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => Auth::user() ? Auth::user()->name : 'Nhân viên quầy',
            'qr_token' => 'QR-' . Str::uuid()->toString(),
            'created_by' => Auth::user() ? Auth::user()->name : 'Nhân viên quầy',
        ]);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'trip_id' => $trip->id,
            'seat_number' => $request->seat_number,
            'floor' => $isFloor2 ? 2 : 1,
            'pickup_order' => $pickupStop->stop_order,
            'dropoff_order' => $dropoffStop->stop_order,
            'price' => $fare,
        ]);

        AuditLog::log('COUNTER_BOOKING', "Xuất vé tại quầy {$booking->booking_code} cho {$request->customer_name} (Ghế {$request->seat_number}, {$fare}đ)", 'Booking', $booking->id);

        return back()->with('success', "Xuất vé thành công! Mã vé: {$booking->booking_code}, Ghế: {$request->seat_number}, Đã thu: " . number_format($fare, 0, ',', '.') . "đ");
    }

    // 5. Quản lý Đội Xe
    public function buses()
    {
        $buses = Bus::withCount('trips')->get()->map(function ($b) {
            $totalRev = Booking::whereHas('trip', fn($q) => $q->where('bus_id', $b->id))->sum('total_amount');
            return [
                'id' => $b->id,
                'license_plate' => $b->license_plate,
                'bus_type' => $b->bus_type,
                'total_seats' => $b->total_seats,
                'floors' => $b->floors,
                'amenities' => $b->amenities ?? [],
                'status' => $b->status,
                'current_km' => $b->current_km,
                'maintenance_notes' => $b->maintenance_notes,
                'trips_count' => $b->trips_count,
                'total_revenue' => (float) $totalRev,
            ];
        });

        return Inertia::render('Admin/Buses', [
            'buses' => $buses,
        ]);
    }

    public function storeBus(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|unique:buses,license_plate',
            'bus_type' => 'required|string',
            'total_seats' => 'required|integer|min:10|max:60',
            'floors' => 'required|integer|min:1|max:2',
            'amenities' => 'nullable|array',
            'status' => 'required|string',
            'current_km' => 'nullable|integer',
        ]);

        $bus = Bus::create($validated);
        AuditLog::log('CREATE_BUS', "Thêm xe mới: {$bus->license_plate} ({$bus->bus_type})", 'Bus', $bus->id);

        return back()->with('success', "Đã thêm xe {$bus->license_plate} vào hệ thống!");
    }

    public function toggleBusStatus(Request $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $status = $request->input('status', 'ACTIVE');
        $notes = $request->input('maintenance_notes', null);

        $bus->status = $status;
        if ($notes) {
            $bus->maintenance_notes = $notes;
        }
        $bus->save();

        AuditLog::log('UPDATE_BUS_STATUS', "Cập nhật trạng thái xe {$bus->license_plate} thành {$status}", 'Bus', $bus->id);

        return back()->with('success', "Đã cập nhật trạng thái xe {$bus->license_plate} thành {$status}!");
    }

    public function deleteBus($id)
    {
        $bus = Bus::findOrFail($id);
        $plate = $bus->license_plate;
        $bus->delete();

        AuditLog::log('DELETE_BUS', "Xóa xe {$plate} khỏi hệ thống", 'Bus', $id);

        return back()->with('success', "Đã xóa xe {$plate}!");
    }

    // 6. Quản lý Tài xế
    public function drivers()
    {
        $drivers = Driver::withCount('trips')->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'phone' => $d->phone,
                'license_number' => $d->license_number,
                'license_class' => $d->license_class,
                'years_experience' => $d->years_experience,
                'avg_rating' => (float) $d->avg_rating,
                'total_trips' => $d->total_trips,
                'status' => $d->status,
                'notes' => $d->notes,
            ];
        });

        return Inertia::render('Admin/Drivers', [
            'drivers' => $drivers,
        ]);
    }

    public function storeDriver(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:drivers,phone',
            'license_number' => 'required|string|max:50',
            'license_class' => 'required|string|max:20',
            'years_experience' => 'required|integer|min:0',
        ]);

        $validated['status'] = 'ACTIVE';
        $driver = Driver::create($validated);

        AuditLog::log('CREATE_DRIVER', "Thêm hồ sơ tài xế: {$driver->name} ({$driver->phone})", 'Driver', $driver->id);

        return back()->with('success', "Đã thêm tài xế {$driver->name}!");
    }

    // 7. Quản lý Lơ Xe & Nhân Viên
    public function users()
    {
        $users = User::latest()->get()->map(function ($u) {
            $totalBookings = Booking::where('user_id', $u->id)->count();
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'total_bookings' => $totalBookings,
                'created_at' => $u->created_at->format('d/m/Y H:i'),
            ];
        });

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => 'staff',
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::log('CREATE_STAFF', "Cấp tài khoản lơ xe / nhân viên mới: {$user->name} ({$user->email})", 'User', $user->id);

        return back()->with('success', 'Cấp tài khoản Lơ xe / Nhân viên mới thành công!');
    }

    // 8. Báo cáo Tài chính
    public function finance(Request $request)
    {
        $totalRevenue = Booking::sum('total_amount');
        $totalPaid = Booking::sum('paid_amount');
        $totalExpenses = TripExpense::sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        $topBuses = Bus::withCount('trips')
            ->get()
            ->map(function ($b) {
                $rev = Booking::whereHas('trip', fn($q) => $q->where('bus_id', $b->id))->sum('total_amount');
                $passengers = BookingSeat::whereHas('booking.trip', fn($q) => $q->where('bus_id', $b->id))->count();

                return [
                    'id' => $b->id,
                    'license_plate' => $b->license_plate,
                    'bus_type' => $b->bus_type,
                    'trips_count' => $b->trips_count,
                    'passengers_count' => $passengers,
                    'total_revenue' => (float) $rev,
                    'status' => $b->status,
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        return Inertia::render('Admin/Finance', [
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_paid' => (float) $totalPaid,
                'total_expenses' => (float) $totalExpenses,
                'net_profit' => (float) $netProfit,
                'total_tickets' => Booking::count(),
            ],
            'topBuses' => $topBuses,
        ]);
    }
}
