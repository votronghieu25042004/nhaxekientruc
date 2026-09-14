<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\AuditLog;
use App\Services\SeatSegmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TripController extends Controller
{
    /**
     * Trang chủ FUTA Bus Lines
     */
    public function home()
    {
        $routes = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc')])->get();
        
        $origins = collect();
        $destinations = collect();
        foreach ($routes as $r) {
            $origins->push($r->origin);
            $destinations->push($r->destination);
            foreach ($r->stops as $s) {
                if ($s->stop_order == 0 || $s->stop_order == 1) {
                    $origins->push($s->stop_name);
                } else {
                    $destinations->push($s->stop_name);
                }
            }
        }

        $trips = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED', 'CLOSED'])
            ->orderBy('departure_time', 'asc')
            ->take(8)
            ->get()
            ->map(function ($t) {
                $totalSeats = $t->bus?->total_seats ?? 36;
                $bookedCount = $t->bookings->whereNotIn('checkin_status', ['CANCELLED'])->sum('total_seats') ?: 0;
                $availableSeats = max(1, $totalSeats - $bookedCount);

                $hours = $t->route?->estimated_hours ?? 3.5;
                $dist = $t->route?->distance_km ?? 175;
                $durStr = sprintf('%02d:%02d h - %dKm', floor($hours), ($hours - floor($hours)) * 60, $dist);

                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'route_name' => $t->route ? $t->route->name : '',
                    'origin' => $t->route ? $t->route->origin : 'Bến xe TT Đà Nẵng',
                    'destination' => $t->route ? $t->route->destination : 'Bến xe Đông Hà (Quảng Trị)',
                    'departure_time' => $t->departure_time->format('H:i'),
                    'departure_date' => $t->departure_time->format('d/m/Y'),
                    'arrival_time' => $t->arrival_time ? $t->arrival_time->format('H:i') : $t->departure_time->copy()->addMinutes($hours * 60)->format('H:i'),
                    'duration' => $durStr,
                    'distance' => $dist . ' Km',
                    'price' => (float) $t->base_price,
                    'bus_type' => str_contains(strtolower($t->bus?->bus_type ?? ''), 'ngồi') ? 'Xe ghế ngồi VIP' : 'Xe giường nằm VIP',
                    'license_plate' => $t->bus ? $t->bus->license_plate : '43B-012.34',
                    'available_seats' => $availableSeats,
                    'total_seats' => $totalSeats,
                    'rating' => 4.6,
                    'status' => $t->status,
                ];
            });

        return Inertia::render('Home', [
            'routes' => $routes,
            'origins' => $origins->filter()->unique()->values(),
            'destinations' => $destinations->filter()->unique()->values(),
            'featuredTrips' => $trips,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Tìm kiếm và Lọc chuyến xe (Sidebar Filter + Trip Result Cards)
     */
    public function search(Request $request)
    {
        $origin = trim((string) $request->input('origin', ''));
        $destination = trim((string) $request->input('destination', ''));
        $date = $request->input('date');
        $isRoundtrip = $request->boolean('is_roundtrip');
        $returnDate = $request->input('return_date');
        $ticketCount = (int) $request->input('ticket_count', 1);

        $timeSlot = $request->input('time_slot'); // 'morning', 'afternoon', 'evening', 'night'
        $busTypeFilter = $request->input('bus_type'); // 'giuong_nam', 'ghe_ngoi'
        $homeDropoff = $request->boolean('home_dropoff');

        $baseQuery = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED', 'CLOSED']);

        if (!empty($origin)) {
            $baseQuery->where(function ($sub) use ($origin) {
                $sub->whereHas('route', function ($q) use ($origin) {
                    $q->where('origin', 'like', "%{$origin}%")
                      ->orWhere('name', 'like', "%{$origin}%");
                })->orWhereHas('route.stops', function ($q) use ($origin) {
                    $q->where('stop_name', 'like', "%{$origin}%");
                });
            });
        }

        if (!empty($destination)) {
            $baseQuery->where(function ($sub) use ($destination) {
                $sub->whereHas('route', function ($q) use ($destination) {
                    $q->where('destination', 'like', "%{$destination}%")
                      ->orWhere('name', 'like', "%{$destination}%");
                })->orWhereHas('route.stops', function ($q) use ($destination) {
                    $q->where('stop_name', 'like', "%{$destination}%");
                });
            });
        }

        if (!empty($date)) {
            try {
                $parsedDate = Carbon::parse($date)->toDateString();
                $baseQuery->whereDate('departure_time', $parsedDate);
            } catch (\Exception $e) {}
        }

        // Clone query for calculating dynamic time-slot counts for the searched route
        $matchedTrips = (clone $baseQuery)->get();
        $isFallback = false;

        if ($matchedTrips->isEmpty()) {
            $matchedTrips = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])
                ->whereNotIn('status', ['CANCELLED', 'COMPLETED', 'CLOSED'])
                ->orderBy('departure_time', 'asc')
                ->get();
            $isFallback = true;
        }

        $countMorning = 0;   // 06:00 - 12:00
        $countAfternoon = 0; // 12:00 - 18:00
        $countEvening = 0;   // 18:00 - 24:00
        $countNight = 0;     // 00:00 - 06:00

        foreach ($matchedTrips as $tr) {
            $hour = (int) $tr->departure_time->format('H');
            if ($hour >= 6 && $hour < 12) $countMorning++;
            elseif ($hour >= 12 && $hour < 18) $countAfternoon++;
            elseif ($hour >= 18 && $hour < 24) $countEvening++;
            else $countNight++;
        }

        // Filter matched trips in memory or query
        $filteredList = $matchedTrips->filter(function ($t) use ($timeSlot, $busTypeFilter) {
            $hour = (int) $t->departure_time->format('H');
            if ($timeSlot === 'morning' && !($hour >= 6 && $hour < 12)) return false;
            if ($timeSlot === 'afternoon' && !($hour >= 12 && $hour < 18)) return false;
            if ($timeSlot === 'evening' && !($hour >= 18 && $hour < 24)) return false;
            if ($timeSlot === 'night' && !($hour < 6)) return false;

            $isSeat = str_contains(strtolower($t->bus?->bus_type ?? ''), 'ngồi');
            if ($busTypeFilter === 'ghe_ngoi' && !$isSeat) return false;
            if ($busTypeFilter === 'giuong_nam' && $isSeat) return false;

            return true;
        })->values();

        // If time slot filter resulted in 0 items, keep fallback so user can see available trips with a notice
        if ($filteredList->isEmpty() && !empty($timeSlot)) {
            $filteredList = $matchedTrips;
            $isFallback = true;
        }

        $trips = $filteredList->map(function ($t) {
            $totalSeats = $t->bus?->total_seats ?? 36;
            $bookedCount = $t->bookings->whereNotIn('checkin_status', ['CANCELLED'])->sum('total_seats') ?: 0;
            $availableSeats = max(1, $totalSeats - $bookedCount);

            $hours = (float) ($t->route?->estimated_hours ?? 3.5);
            $dist = (int) ($t->route?->distance_km ?? 175);
            $durStr = sprintf('%02d:%02d h - %dKm', floor($hours), round(($hours - floor($hours)) * 60), $dist);

            $depH = $t->departure_time->format('H:i');
            $arrH = $t->arrival_time ? $t->arrival_time->format('H:i') : $t->departure_time->copy()->addMinutes($hours * 60)->format('H:i');

            return [
                'id' => $t->id,
                'trip_code' => $t->trip_code,
                'route_name' => $t->route ? $t->route->name : 'Tuyến liên tỉnh',
                'origin' => $t->route ? $t->route->origin : 'Bến xe TT Đà Nẵng',
                'destination' => $t->route ? $t->route->destination : 'Bến xe Đông Hà (Quảng Trị)',
                'stops' => $t->route ? $t->route->stops : [],
                'departure_time' => $depH,
                'departure_date' => $t->departure_time->format('d/m/Y'),
                'arrival_time' => $arrH,
                'duration' => $durStr,
                'distance' => $dist . ' Km',
                'price' => (float) $t->base_price,
                'bus_type' => str_contains(strtolower($t->bus?->bus_type ?? ''), 'ngồi') ? 'Xe ghế ngồi VIP' : 'Xe giường nằm VIP',
                'license_plate' => $t->bus ? $t->bus->license_plate : '43B-012.34',
                'amenities' => $t->bus?->amenities ?? ['WIFI 5G', 'Nước đóng chai', 'Chăn/mền', 'Ổ sạc', 'Gối'],
                'available_seats' => $availableSeats,
                'total_seats' => $totalSeats,
                'status' => $t->status,
                'driver_name' => $t->driver ? $t->driver->name : 'Bác tài FUTA',
                'rating' => 4.6,
                'notice' => "Lưu ý: Tuyến xe {$t->route?->name}. Lộ trình {$dist}km. Quý khách lưu ý có mặt tại điểm đón trước 15 phút.",
            ];
        });

        // Collect specific route pickup & dropoff stops for the filter sidebar
        $routeStops = collect();
        foreach ($matchedTrips as $tr) {
            if ($tr->route && $tr->route->stops) {
                foreach ($tr->route->stops as $st) {
                    $routeStops->push($st);
                }
            }
        }
        $routeStops = $routeStops->unique('id');

        $pickupOptions = $routeStops->where('stop_order', '<=', 2)->pluck('stop_name')->unique()->values();
        $dropoffOptions = $routeStops->where('stop_order', '>=', 2)->pluck('stop_name')->unique()->values();

        if ($pickupOptions->isEmpty()) {
            $pickupOptions = collect(['Bến xe TT Đà Nẵng', 'Trạm Nam Ô', 'Bến xe Miền Tây']);
        }
        if ($dropoffOptions->isEmpty()) {
            $dropoffOptions = collect(['Bến xe Đông Hà (Quảng Trị)', 'Bến xe Phía Nam Huế', 'Bến xe TT Cần Thơ']);
        }

        return Inertia::render('Trips/Index', [
            'trips' => $trips,
            'isFallback' => $isFallback,
            'origins' => $pickupOptions,
            'destinations' => $dropoffOptions,
            'counts' => [
                'morning' => $countMorning,
                'afternoon' => $countAfternoon,
                'evening' => $countEvening,
                'night' => $countNight,
            ],
            'filters' => [
                'origin' => $origin,
                'destination' => $destination,
                'date' => $date,
                'is_roundtrip' => $isRoundtrip,
                'return_date' => $returnDate,
                'ticket_count' => $ticketCount,
                'time_slot' => $timeSlot,
                'bus_type' => $busTypeFilter,
                'home_dropoff' => $homeDropoff,
            ],
            'user' => Auth::user(),
        ]);
    }

    /**
     * Chi tiết chuyến xe & Chọn chỗ ngồi 3 bước (100% Dynamic từ Route Stops)
     */
    public function show(Request $request, $id)
    {
        $trip = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])->findOrFail($id);

        $allStops = $trip->route->stops;
        $totalStopsCount = $allStops->count();

        // Build dynamic pickup stops (Origin + intermediate pickup stops)
        $pickupStopsList = [];
        // Build dynamic dropoff stops (Intermediate dropoff stops + Destination)
        $dropoffStopsList = [];

        $depTime = $trip->departure_time;

        foreach ($allStops as $idx => $stop) {
            $mins = (int) ($stop->estimated_minutes_from_start ?? ($idx * 35));
            $stopTime = $depTime->copy()->addMinutes($mins)->format('H:i');

            $stopItem = [
                'id' => $stop->id,
                'stop_order' => $stop->stop_order,
                'time' => $stopTime,
                'name' => $stop->stop_name,
                'address' => $stop->address ?: "Trạm dừng số " . ($idx + 1) . " trên tuyến " . ($trip->route?->name ?? ''),
            ];

            // If not the very last stop, it can be a pickup stop
            if ($idx < $totalStopsCount - 1) {
                $pickupStopsList[] = $stopItem;
            }

            // If not the very first stop, it can be a dropoff stop
            if ($idx > 0) {
                $dropoffStopsList[] = $stopItem;
            }
        }

        // In case route has few stops, ensure first is pickup and last is dropoff
        if (empty($pickupStopsList) && $allStops->isNotEmpty()) {
            $pickupStopsList[] = [
                'id' => $allStops->first()->id,
                'time' => $depTime->format('H:i'),
                'name' => $allStops->first()->stop_name,
                'address' => $allStops->first()->address ?: $trip->route?->origin,
            ];
        }
        if (empty($dropoffStopsList) && $allStops->isNotEmpty()) {
            $dropoffStopsList[] = [
                'id' => $allStops->last()->id,
                'time' => $trip->arrival_time ? $trip->arrival_time->format('H:i') : $depTime->copy()->addHours(3)->format('H:i'),
                'name' => $allStops->last()->stop_name,
                'address' => $allStops->last()->address ?: $trip->route?->destination,
            ];
        }

        $occupiedSeats = SeatSegmentService::getOccupiedSeats($trip->id, 0, 99);
        if (empty($occupiedSeats)) {
            $occupiedSeats = ['A01', 'A02', 'A05', 'A08'];
        }

        $baseFare = (float) ($trip->base_price ?? 180000);
        $isSeatOnly = str_contains(strtolower($trip->bus?->bus_type ?? ''), 'ngồi');
        $floor1Price = (float) ($trip->floor1_price ?? $baseFare);
        $floor2Price = (float) ($trip->floor2_price ?? round($baseFare * 0.95));

        // Floor 1 Rows (A01 - A18)
        $floor1Rows = [];
        for ($i = 1; $i <= 6; $i++) {
            $numLeft = sprintf('A%02d', ($i - 1) * 3 + 1);
            $numMid = sprintf('A%02d', ($i - 1) * 3 + 2);
            $numRight = sprintf('A%02d', ($i - 1) * 3 + 3);

            $floor1Rows[] = [
                'left' => [
                    'seat_number' => $numLeft,
                    'floor' => 1,
                    'price' => $floor1Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi VIP' : 'Giường nằm Tầng dưới',
                    'status' => in_array($numLeft, $occupiedSeats) ? 'booked' : 'available',
                ],
                'middle' => [
                    'seat_number' => $numMid,
                    'floor' => 1,
                    'price' => $floor1Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi VIP' : 'Giường nằm Tầng dưới',
                    'status' => in_array($numMid, $occupiedSeats) ? 'booked' : 'available',
                ],
                'right' => [
                    'seat_number' => $numRight,
                    'floor' => 1,
                    'price' => $floor1Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi VIP' : 'Giường nằm Tầng dưới',
                    'status' => in_array($numRight, $occupiedSeats) ? 'booked' : 'available',
                ]
            ];
        }

        // Floor 2 Rows (B01 - B18)
        $floor2Rows = [];
        for ($i = 1; $i <= 6; $i++) {
            $numLeft = sprintf('B%02d', ($i - 1) * 3 + 1);
            $numMid = sprintf('B%02d', ($i - 1) * 3 + 2);
            $numRight = sprintf('B%02d', ($i - 1) * 3 + 3);

            $floor2Rows[] = [
                'left' => [
                    'seat_number' => $numLeft,
                    'floor' => 2,
                    'price' => $floor2Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi Tiêu chuẩn' : 'Giường nằm Tầng trên',
                    'status' => in_array($numLeft, $occupiedSeats) ? 'booked' : 'available',
                ],
                'middle' => [
                    'seat_number' => $numMid,
                    'floor' => 2,
                    'price' => $floor2Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi Tiêu chuẩn' : 'Giường nằm Tầng trên',
                    'status' => in_array($numMid, $occupiedSeats) ? 'booked' : 'available',
                ],
                'right' => [
                    'seat_number' => $numRight,
                    'floor' => 2,
                    'price' => $floor2Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi Tiêu chuẩn' : 'Giường nằm Tầng trên',
                    'status' => in_array($numRight, $occupiedSeats) ? 'booked' : 'available',
                ]
            ];
        }

        // Dynamic cancellation schedule based on departure time
        $tMinus24 = $depTime->copy()->subHours(24)->format('d/m H:i');
        $tMinus4 = $depTime->copy()->subHours(4)->format('d/m H:i');
        $depTimeStr = $depTime->format('d/m H:i');

        $cancellationSchedule = [
            ['time_range' => "Trước {$tMinus24} (Trước 24h)", 'fee' => number_format($baseFare * 0.1, 0, ',', '.') . ' đ', 'fee_num' => round($baseFare * 0.1)],
            ['time_range' => "Từ {$tMinus24} đến {$tMinus4}", 'fee' => number_format($baseFare * 0.3, 0, ',', '.') . ' đ', 'fee_num' => round($baseFare * 0.3)],
            ['time_range' => "Từ {$tMinus4} đến {$depTimeStr} (Sát giờ)", 'fee' => number_format($baseFare, 0, ',', '.') . ' đ', 'fee_num' => $baseFare],
        ];

        return Inertia::render('Trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'operator_name' => 'Phương Trang',
                'route_name' => $trip->route ? $trip->route->name : 'Tuyến liên tỉnh FUTA',
                'origin' => $trip->route ? $trip->route->origin : 'Bến xuất phát',
                'destination' => $trip->route ? $trip->route->destination : 'Bến đến',
                'departure_time' => $trip->departure_time->format('H:i'),
                'departure_date' => $trip->departure_time->format('d/m/Y'),
                'departure_day_label' => $trip->departure_time->isoFormat('dddd, DD/MM'),
                'arrival_time' => $trip->arrival_time ? $trip->arrival_time->format('H:i') : $depTime->copy()->addMinutes(($trip->route?->estimated_hours ?? 3.5) * 60)->format('H:i'),
                'base_price' => $baseFare,
                'floor1_price' => $floor1Price,
                'floor2_price' => $floor2Price,
                'bus_type' => $isSeatOnly ? 'Xe ghế ngồi VIP' : 'Xe giường nằm VIP',
                'license_plate' => $trip->bus ? $trip->bus->license_plate : '43B-012.34',
                'rating' => 4.6,
                'amenities' => ['WIFI 5G', 'Nước đóng chai', 'Chăn/mền', 'Ổ sạc Type-C', 'Gối nằm'],
                'driver' => $trip->driver,
                'status' => $trip->status,
            ],
            'pickupStops' => $pickupStopsList,
            'dropoffStops' => $dropoffStopsList,
            'floor1Rows' => $floor1Rows,
            'floor2Rows' => $floor2Rows,
            'occupiedSeats' => $occupiedSeats,
            'cancellationSchedule' => $cancellationSchedule,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Bảng Lịch Trình Toàn Bộ Tuyến Xe (Tự động lấy từ bảng routes)
     */
    public function schedules()
    {
        $allRoutes = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc')])->get();

        $schedulesList = $allRoutes->map(function ($r) {
            $hours = (float) $r->estimated_hours;
            $durStr = sprintf('%d giờ %s', floor($hours), ($hours - floor($hours) > 0 ? round(($hours - floor($hours)) * 60) . ' phút' : ''));

            return [
                'route' => $r->name,
                'origin' => $r->origin,
                'destination' => $r->destination,
                'bus_type' => 'Giường nằm VIP / Ghế ngồi VIP',
                'distance' => ($r->distance_km ?? 175) . 'km',
                'duration' => $durStr,
            ];
        });

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedulesList,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Đặt vé & Thanh toán Cọc 30% / 100%
     */
    public function book(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'seat_numbers' => 'required|array|min:1',
            'pickup_stop_name' => 'required|string',
            'dropoff_stop_name' => 'required|string',
            'has_insurance' => 'nullable|boolean',
            'payment_choice' => 'required|in:FULL,DEPOSIT',
        ]);

        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($id);
        $insuranceFee = $request->boolean('has_insurance') ? (count($request->seat_numbers) * 10000) : 0;

        $seatPrice = (float) ($trip->base_price ?? 180000);
        $totalAmount = 0;
        foreach ($request->seat_numbers as $sNum) {
            $isFloor2 = str_starts_with($sNum, 'B');
            $totalAmount += $isFloor2 ? (float)($trip->floor2_price ?? round($seatPrice * 0.95)) : (float)($trip->floor1_price ?? $seatPrice);
        }
        $totalAmount += $insuranceFee;

        $isFull = ($request->payment_choice === 'FULL');
        $paidAmount = $isFull ? $totalAmount : round($totalAmount * 0.3);
        $paymentStatus = $isFull ? 'PAID' : 'PARTIALLY_PAID';

        $bookingCode = 'FUTA-' . strtoupper(Str::random(5));
        $qrToken = 'QR-' . Str::uuid()->toString();

        $pickupStop = null;
        if ($request->filled('pickup_stop_id')) {
            $pickupStop = $trip->route?->stops?->firstWhere('id', $request->pickup_stop_id);
        }
        if (!$pickupStop && $request->filled('pickup_stop_name')) {
            $pickupStop = $trip->route?->stops?->firstWhere('stop_name', $request->pickup_stop_name);
        }
        if (!$pickupStop) {
            $pickupStop = $trip->route?->stops?->first();
        }

        $dropoffStop = null;
        if ($request->filled('dropoff_stop_id')) {
            $dropoffStop = $trip->route?->stops?->firstWhere('id', $request->dropoff_stop_id);
        }
        if (!$dropoffStop && $request->filled('dropoff_stop_name')) {
            $dropoffStop = $trip->route?->stops?->firstWhere('stop_name', $request->dropoff_stop_name);
        }
        if (!$dropoffStop) {
            $dropoffStop = $trip->route?->stops?->last();
        }

        $pickupStopId = $pickupStop?->id ?? ($trip->route?->stops?->first()?->id ?? 1);
        $dropoffStopId = $dropoffStop?->id ?? ($trip->route?->stops?->last()?->id ?? 2);
        $pickupOrder = $pickupStop?->stop_order ?? 0;
        $dropoffOrder = $dropoffStop?->stop_order ?? 1;

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'user_id' => Auth::id(),
            'trip_id' => $trip->id,
            'booking_source' => 'ONLINE',
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'pickup_stop_id' => $pickupStopId,
            'dropoff_stop_id' => $dropoffStopId,
            'pickup_order' => $pickupOrder,
            'dropoff_order' => $dropoffOrder,
            'total_seats' => count($request->seat_numbers),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'payment_method' => 'ONLINE',
            'checkin_status' => 'PENDING',
            'qr_token' => $qrToken,
            'notes' => "Đón: {$request->pickup_stop_name} | Trả: {$request->dropoff_stop_name}" . ($insuranceFee > 0 ? ' | Có BH Saladin' : ''),
        ]);

        foreach ($request->seat_numbers as $sNum) {
            $isFloor2 = str_starts_with($sNum, 'B');
            $seatFare = $isFloor2 ? (float)($trip->floor2_price ?? round($seatPrice * 0.95)) : (float)($trip->floor1_price ?? $seatPrice);

            BookingSeat::create([
                'booking_id' => $booking->id,
                'trip_id' => $trip->id,
                'seat_number' => $sNum,
                'floor' => $isFloor2 ? 2 : 1,
                'pickup_order' => 1,
                'dropoff_order' => 5,
                'price' => $seatFare,
            ]);
        }

        AuditLog::log('CREATE_ONLINE_BOOKING', "Khách {$request->customer_name} đặt online vé {$booking->booking_code} (" . implode(',', $request->seat_numbers) . ", {$totalAmount}đ, Cọc: {$paidAmount}đ). Gửi mã QR về {$request->customer_email}", 'Booking', $booking->id);

        return redirect()->route('booking.ticket', ['code' => $booking->booking_code])
            ->with('success', "Đặt vé thành công! Mã QR vé điện tử đã được gửi về Gmail {$request->customer_email}.");
    }

    /**
     * Xem Vé Điện Tử
     */
    public function ticket($code)
    {
        $booking = Booking::with(['trip.route.stops', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'review'])
            ->where('booking_code', $code)
            ->firstOrFail();

        return Inertia::render('Bookings/Ticket', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                'from_location' => $booking->trip->route?->origin ?? 'Bến xuất phát',
                'to_location' => $booking->trip->route?->destination ?? 'Bến đến',
                'notes' => $booking->notes,
                'total_amount' => (float) $booking->total_amount,
                'paid_amount' => (float) $booking->paid_amount,
                'remaining_due' => (float) ($booking->total_amount - $booking->paid_amount),
                'payment_status' => $booking->payment_status,
                'checkin_status' => $booking->checkin_status,
                'checked_in_at' => $booking->checked_in_at ? $booking->checked_in_at->format('H:i - d/m/Y') : null,
                'qr_token' => $booking->qr_token,
                'departure_time' => $booking->trip->departure_time->format('H:i'),
                'departure_date' => $booking->trip->departure_time->format('d/m/Y'),
                'bus_type' => str_contains(strtolower($booking->trip->bus?->bus_type ?? ''), 'ngồi') ? 'Ghế ngồi VIP' : 'Xe giường nằm VIP',
                'license_plate' => $booking->trip->bus?->license_plate ?? '43B-012.34',
                'driver' => $booking->trip->driver,
                'has_reviewed' => $booking->review !== null,
            ],
            'user' => Auth::user(),
        ]);
    }
}
