<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Route;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Seat;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\SeatSegmentService;
use App\Models\AuditLog;
use App\Models\Voucher;
use App\Models\User;
use App\Models\CancellationPolicy;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    /**
     * Lịch Trình Chuyến Xe Hàng Ngày & Mạng Lưới Tuyến Cố Định
     */
    public function schedules(Request $request)
    {
        $dateParam = $request->query('date');
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $selectedDate = $dateParam ? Carbon::parse($dateParam) : $today;
        $dateString = $selectedDate->format('Y-m-d');
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        try {
            \App\Services\TripScheduleService::ensureTripsForDate($selectedDate, $now);
            \App\Services\TripScheduleService::ensureUpcomingTripsExist(7);
            Trip::syncRealTimeStatuses();
        } catch (\Throwable $e) {}

        // Lấy 10 tuyến đường cố định cốt lõi kèm trạm dừng
        $routes = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc')])
            ->orderBy('id', 'asc')
            ->get();

        // Lấy các chuyến xe trong ngày được chọn kèm ghế đã đặt (tránh N+1)
        $tripsOfDay = Trip::with(['route', 'bus', 'driver', 'bookings.bookingSeats'])
            ->whereDate('departure_time', $dateString)
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('departure_time', 'asc')
            ->get();

        $busTypeByRoute = [
            1 => 'Ghế ngồi cao cấp VIP',
            2 => 'Ghế ngồi cao cấp VIP',
            3 => 'Giường nằm VIP 34 Phòng',
            4 => 'Giường nằm VIP 34 Phòng',
            5 => 'Giường nằm VIP 34 Phòng',
            6 => 'Giường nằm VIP 34 Phòng',
            7 => 'Limousine VIP 34 Phòng',
            8 => 'Limousine VIP 34 Phòng',
            9 => 'Cung Điện Di Động VIP',
            10 => 'Cung Điện Di Động VIP',
        ];

        $routesWithTrips = $routes->map(function ($route) use ($tripsOfDay, $now, $busTypeByRoute) {
            $matchingTrips = $tripsOfDay->where('route_id', $route->id)->values();

            $formattedTrips = $matchingTrips->map(function ($t) use ($now) {
                $dep = Carbon::parse($t->departure_time);
                $isPast = $dep->lessThanOrEqualTo($now);
                $bookedCount = 0;
                if ($t->bookings) {
                    foreach ($t->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']) as $bk) {
                        $bsCount = $bk->relationLoaded('bookingSeats') ? $bk->bookingSeats->count() : 0;
                        $bookedCount += ($bsCount > 0 ? $bsCount : (int)($bk->total_seats ?: 1));
                    }
                }
                $totalSeats = $t->bus ? ($t->bus->total_seats ?? 34) : 34;
                $avail = max(0, $totalSeats - $bookedCount);

                return [
                    'id' => $t->id,
                    'trip_code' => $t->trip_code,
                    'departure_time' => $dep->format('H:i'),
                    'departure_date' => $dep->format('d/m/Y'),
                    'arrival_time' => Carbon::parse($t->arrival_time)->format('H:i'),
                    'base_price' => (float)$t->base_price,
                    'available_seats' => $avail,
                    'bus_plate' => $t->bus?->license_plate ?? '43B-012.34',
                    'bus_type' => $t->bus?->bus_type ?? 'Giường Nằm VIP',
                    'driver_name' => $t->driver?->name ?? 'Bác tài nhaxekientruc',
                    'status' => $t->status,
                    'can_book' => !$isPast && $avail > 0,
                ];
            });

            $minPrice = $matchingTrips->min('base_price') ?: match((int)$route->id) {
                1, 2 => 120000,
                3, 4 => 140000,
                5, 6 => 200000,
                7, 8 => 240000,
                9, 10 => 320000,
                default => 150000
            };

            $cleanName = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $route->name);
            $cleanOrigin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $route->origin);
            $cleanDest = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $route->destination);

            return [
                'id' => $route->id,
                'name' => $cleanName,
                'origin' => $cleanOrigin,
                'destination' => $cleanDest,
                'distance_km' => $route->distance_km,
                'estimated_hours' => $route->estimated_hours,
                'bus_type' => $busTypeByRoute[$route->id] ?? 'Giường nằm VIP',
                'min_price' => (float)$minPrice,
                'daily_frequency' => $matchingTrips->count() . ' chuyến/ngày',
                'stops' => $route->stops,
                'trips' => $formattedTrips,
            ];
        });

        $sanitizedRoutes = $routes->map(function ($r) {
            $r->name = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->name);
            $r->origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->origin);
            $r->destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->destination);
            return $r;
        });

        return Inertia::render('Schedules/Index', [
            'routes' => $sanitizedRoutes,
            'routes_with_trips' => $routesWithTrips,
            'selected_date' => $dateString,
            'today_date' => $today->format('Y-m-d'),
        ]);
    }

    /**
     * Trang Chủ
     */
    public function home()
    {
        try {
            \App\Services\TripScheduleService::ensureUpcomingTripsExist(7);
        } catch (\Throwable $e) {
            // bỏ qua để trang chủ luôn mở siêu tốc
        }
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $routes = Route::with(['stops'])->get();
        
        $todayTrips = Trip::select(['id', 'route_id'])
            ->whereDate('departure_time', $today)
            ->where('status', '!=', 'CANCELLED')
            ->get();
        
        $destinationsToday = $routes->map(function($r) use ($todayTrips) {
            $count = $todayTrips->where('route_id', $r->id)->count();
            return [
                'id' => $r->id,
                'name' => str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->name),
                'origin' => str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->origin),
                'destination' => str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->destination),
                'bus_type' => $r->bus_type ?? 'Giường nằm VIP',
                'distance' => $r->distance_km ? ($r->distance_km . 'km') : '175km',
                'duration' => $r->estimated_hours ? ($r->estimated_hours . ' giờ') : '3.5 giờ',
                'trips_count' => $count > 0 ? $count : 4,
            ];
        });

        $featuredTrips = Trip::with(['route.stops', 'bus', 'driver', 'bookings.bookingSeats'])
            ->whereDate('departure_time', '>=', $today)
            ->where('status', '!=', 'CANCELLED')
            ->orderBy('departure_time', 'asc')
            ->take(6)
            ->get();

        $sanitizedHomeRoutes = $routes->map(function($r) {
            $r->name = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->name);
            $r->origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->origin);
            $r->destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->destination);
            return $r;
        });

        return Inertia::render('Home', [
            'routes' => $sanitizedHomeRoutes,
            'destinations_today' => $destinationsToday,
            'featured_trips' => $featuredTrips,
        ]);
    }

    /**
     * Tìm Chuyến Xe (Linh hoạt 2 chiều: Điểm đi, Điểm đến, Ngày đi, Khứ hồi, Từ khóa)
     * ĐÃ XỬ LÝ CHÍNH XÁC GIỜ THẬT VIỆT NAM (ASIA/HO_CHI_MINH) - XE QUÁ GIỜ ĐƯỢC ĐƯA XUỐNG DƯỚI & KHÓA ĐẶT
     */
    public function search(Request $request)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $date = $request->input('date');
        $targetDate = !empty($date) ? Carbon::parse($date) : Carbon::today('Asia/Ho_Chi_Minh');

        try {
            \App\Services\TripScheduleService::ensureTripsForDate($targetDate, $now);
            \App\Services\TripScheduleService::ensureUpcomingTripsExist(7);
            Trip::syncRealTimeStatuses();
        } catch (\Throwable $e) {}

        $q = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', trim($request->input('q', '')));
        $origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', trim($request->input('origin', '')));
        $destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', trim($request->input('destination', '')));
        $returnDate = $request->input('return_date');
        $tripType = $request->input('trip_type', 'one_way');

        $query = Trip::with(['route.stops', 'bus', 'driver', 'bookings.bookingSeats']);

        // 1. Tìm kiếm tổng quát theo từ khóa
        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->whereHas('route', function($rq) use ($q) {
                    $rq->where('name', 'like', "%{$q}%")
                       ->orWhere('origin', 'like', "%{$q}%")
                       ->orWhere('destination', 'like', "%{$q}%");
                })
                ->orWhereHas('bus', function($bq) use ($q) {
                    $bq->where('license_plate', 'like', "%{$q}%");
                })
                ->orWhereHas('driver', function($dq) use ($q) {
                    $dq->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
                });
            });
        }

        // 2 & 3. Lọc chính xác Điểm Đi & Điểm Đến (Loại bỏ triệt để việc nhầm lẫn chiều khứ hồi ⇄)
        if (!empty($origin) && !empty($destination)) {
            $query->whereHas('route', function($rq) use ($origin, $destination) {
                $rq->where(function($q1) use ($origin, $destination) {
                    // Chiều chuẩn: origin khớp nơi đi VÀ destination khớp nơi đến
                    $q1->where('origin', 'like', "%{$origin}%")
                       ->where('destination', 'like', "%{$destination}%");
                })->orWhere(function($q2) use ($origin, $destination) {
                    // Hoặc điểm đi là origin và điểm đến nằm trong trạm dừng
                    $q2->where('origin', 'like', "%{$origin}%")
                       ->whereHas('stops', function($sq) use ($destination) {
                           $sq->where('stop_name', 'like', "%{$destination}%")
                              ->orWhere('address', 'like', "%{$destination}%");
                       });
                })->orWhere(function($q3) use ($origin, $destination) {
                    // Hoặc điểm đến là destination và điểm đi nằm trong trạm dừng
                    $q3->where('destination', 'like', "%{$destination}%")
                       ->whereHas('stops', function($sq) use ($origin) {
                           $sq->where('stop_name', 'like', "%{$origin}%")
                              ->orWhere('address', 'like', "%{$origin}%");
                       });
                });
            });
        } elseif (!empty($origin)) {
            $query->whereHas('route', function($rq) use ($origin) {
                $rq->where('origin', 'like', "%{$origin}%")
                   ->orWhereHas('stops', function($sq) use ($origin) {
                       $sq->where('stop_name', 'like', "%{$origin}%")
                          ->orWhere('address', 'like', "%{$origin}%");
                   });
            });
        } elseif (!empty($destination)) {
            $query->whereHas('route', function($rq) use ($destination) {
                $rq->where('destination', 'like', "%{$destination}%")
                   ->orWhereHas('stops', function($sq) use ($destination) {
                       $sq->where('stop_name', 'like', "%{$destination}%")
                          ->orWhere('address', 'like', "%{$destination}%");
                   });
            });
        }

        // 4. Lọc theo ngày khởi hành: mặc định hiển thị đúng chuyến xe ngày hôm nay (hoặc ngày khách chọn)
        $targetDate = !empty($date) ? Carbon::parse($date) : Carbon::today('Asia/Ho_Chi_Minh');
        $query->whereDate('departure_time', $targetDate);

        $allTrips = $query->get()->map(function($trip) use ($now) {
            $depTime = Carbon::parse($trip->departure_time);
            $isPast = $depTime->lessThanOrEqualTo($now);
            
            // Tính số ghế còn trống thực tế
            $bookedCount = 0;
            if ($trip->bookings) {
                foreach ($trip->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']) as $bk) {
                    $bsCount = $bk->relationLoaded('bookingSeats') ? $bk->bookingSeats->count() : 0;
                    $bookedCount += ($bsCount > 0 ? $bsCount : (int)($bk->total_seats ?: 1));
                }
            }
            $totalSeats = $trip->bus->total_seats ?? 34;
            $availableSeats = max(0, $totalSeats - $bookedCount);
            $trip->available_seats = $availableSeats;

            // Nếu giờ khởi hành đã qua giờ hiện tại Việt Nam hoặc đã về bến -> đánh dấu xe đã chạy
            $isTerminalDone = in_array($trip->status, ['COMPLETED', 'CLOSED', 'CANCELLED']);
            if ($isTerminalDone || ($isPast && !in_array($trip->status, ['BOARDING', 'OPEN_FOR_SALE']))) {
                $trip->is_expired = true;
                $trip->can_book = false;
                $trip->effective_status = $trip->status === 'COMPLETED' ? 'COMPLETED' : 'RUNNING';
            } else {
                $trip->is_expired = false;
                $trip->can_book = ($availableSeats > 0);
                $trip->effective_status = in_array($trip->status, ['BOARDING', 'READY']) ? 'BOARDING' : 'SCHEDULED';
            }
            if ($trip->route) {
                $trip->route->name = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->name);
                $trip->route->origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->origin);
                $trip->route->destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->destination);
            }
            return $trip;
        });

        // Sắp xếp: Chuyến xe CHƯA CHẠY lên đầu (theo giờ đi tăng dần), Chuyến xe ĐÃ CHẠY xuống dưới
        $sortedTrips = $allTrips->sort(function($a, $b) {
            if ($a->can_book !== $b->can_book) {
                return $a->can_book ? -1 : 1;
            }
            return strcmp($a->departure_time, $b->departure_time);
        })->values();

        $routes = Route::with(['stops'])->get()->map(function($r) {
            $r->name = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->name);
            $r->origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->origin);
            $r->destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $r->destination);
            return $r;
        });

        return Inertia::render('Trips/Index', [
            'trips' => [
                'data' => $sortedTrips,
                'total' => $sortedTrips->count(),
            ],
            'routes' => $routes,
            'current_time' => $now->toIso8601String(),
            'filters' => [
                'q' => $q,
                'origin' => $origin,
                'destination' => $destination,
                'date' => $date ?: Carbon::today('Asia/Ho_Chi_Minh')->format('Y-m-d'),
                'return_date' => $returnDate,
                'trip_type' => $tripType
            ]
        ]);
    }

    /**
     * Chi Tiết Chuyến Xe & Sơ Đồ Chỗ Ngồi Đặt Vé
     */
    public function show(Request $request, $id)
    {
        $trip = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])->findOrFail($id);

        if ($trip->route) {
            $trip->route->name = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->name);
            $trip->route->origin = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->origin);
            $trip->route->destination = str_replace(['Thừa Thiên - Huế', 'Thừa Thiên – Huế', 'Thừa Thiên — Huế'], 'Thừa Thiên Huế', $trip->route->destination);
        }

        $allStops = $trip->route ? $trip->route->stops : collect();
        $totalStopsCount = $allStops->count();

        // Build dynamic pickup stops (Origin + intermediate pickup stops)
        $pickupStopsList = [];
        // Build dynamic dropoff stops (Intermediate dropoff stops + Destination)
        $dropoffStopsList = [];

        $depTime = $trip->departure_time;

        foreach ($allStops as $idx => $stop) {
            $mins = (int) ($stop->estimated_minutes_from_start ?? ($idx * 30));
            $stopTime = $depTime->copy()->addMinutes($mins)->format('H:i');

            $stopItem = [
                'id' => $stop->id,
                'unique_key' => 'stop_' . $stop->id . '_' . $idx,
                'stop_order' => $stop->stop_order,
                'time' => $stopTime,
                'name' => $stop->stop_name,
                'address' => $stop->address ?: "Trạm dừng số " . ($idx + 1) . " trên tuyến " . ($trip->route?->name ?? ''),
            ];

            $stopNameLower = mb_strtolower($stop->stop_name ?? '');
            $isDropKeyword = str_contains($stopNameLower, 'trả khách') || str_contains($stopNameLower, 'điểm trả') || str_contains($stopNameLower, 'bến xe đến');
            $isPickupKeyword = str_contains($stopNameLower, 'đón khách') || str_contains($stopNameLower, 'trạm đón') || str_contains($stopNameLower, 'điểm đón');
            $distFromStart = $stop->distance_from_start_km !== null ? (float)$stop->distance_from_start_km : ($idx * 40);

            // Điểm Đón: Thuộc khu vực xuất phát (cự ly <= 35km hoặc có từ khóa đón khách, và không phải trạm trả khách)
            if (($idx === 0 || $distFromStart <= 35 || $isPickupKeyword || $idx < ($totalStopsCount / 2)) && !$isDropKeyword && $idx < $totalStopsCount - 1) {
                $pickupStopsList[] = $stopItem;
            }

            // Điểm Trả: Thuộc khu vực điểm đến (cự ly > 35km hoặc nửa sau hành trình, hoặc có từ khóa trả khách)
            if ($idx > 0 && ($distFromStart > 35 || $isDropKeyword || $idx >= ceil($totalStopsCount / 2))) {
                $dropoffStopsList[] = $stopItem;
            }
        }

        // ĐẢM BẢO MỖI CHUYẾN LUÔN CÓ ÍT NHẤT 2 ĐIỂM ĐÓN KHÁCH
        if (count($pickupStopsList) < 2 && $allStops->isNotEmpty()) {
            if (empty($pickupStopsList)) {
                $first = $allStops->first();
                $pickupStopsList[] = [
                    'id' => $first->id,
                    'unique_key' => 'stop_' . $first->id . '_first',
                    'stop_order' => 1,
                    'time' => $depTime->format('H:i'),
                    'name' => $first->stop_name,
                    'address' => $first->address ?: ($trip->route?->origin ?? 'Bến xe xuất phát'),
                ];
            }
            if (count($pickupStopsList) < 2) {
                $first = $pickupStopsList[0];
                $pickupStopsList[] = [
                    'id' => $first['id'],
                    'unique_key' => 'stop_' . $first['id'] . '_sub2',
                    'stop_order' => 2,
                    'time' => $depTime->copy()->addMinutes(20)->format('H:i'),
                    'name' => 'Trạm đón Trung chuyển Nội thành ' . ($trip->route?->origin ?? ''),
                    'address' => 'Văn phòng Trung chuyển nội thành ' . ($trip->route?->origin ?? ''),
                ];
            }
        }

        // ĐẢM BẢO MỖI CHUYẾN LUÔN CÓ ÍT NHẤT 2 ĐIỂM TRẢ KHÁCH
        if (count($dropoffStopsList) < 2 && $allStops->isNotEmpty()) {
            $last = $allStops->last();
            $arrTimeStr = $trip->arrival_time ? $trip->arrival_time->format('H:i') : $depTime->copy()->addMinutes(($trip->route?->estimated_hours ?? 3.5) * 60)->format('H:i');
            $arrTimeObj = $trip->arrival_time ? $trip->arrival_time : $depTime->copy()->addMinutes(($trip->route?->estimated_hours ?? 3.5) * 60);

            if (empty($dropoffStopsList)) {
                $dropoffStopsList[] = [
                    'id' => $last->id,
                    'unique_key' => 'stop_' . $last->id . '_last',
                    'stop_order' => 4,
                    'time' => $arrTimeStr,
                    'name' => $last->stop_name,
                    'address' => $last->address ?: ($trip->route?->destination ?? 'Bến xe đến'),
                ];
            }
            if (count($dropoffStopsList) < 2) {
                $existing = $dropoffStopsList[0];
                $dropoff1 = [
                    'id' => $existing['id'],
                    'unique_key' => 'stop_' . $existing['id'] . '_sub1',
                    'stop_order' => 3,
                    'time' => $arrTimeObj->copy()->subMinutes(25)->format('H:i'),
                    'name' => 'Trạm trả khách Cửa ngõ ' . ($trip->route?->destination ?? ''),
                    'address' => 'Trục đường chính / Cửa ngõ thành phố ' . ($trip->route?->destination ?? ''),
                ];
                array_unshift($dropoffStopsList, $dropoff1);
            }
        }


        $occupiedSeats = SeatSegmentService::getAllBookedSeatsForTrip($trip->id);

        $baseFare = (float) ($trip->base_price ?? 180000);
        $isSeatOnly = str_contains(strtolower($trip->bus?->bus_type ?? ''), 'ngồi');
        $floor1Price = (float) ($trip->floor1_price ?? $baseFare);
        $floor2Price = (float) ($trip->floor2_price ?? $floor1Price);

        // Floor 1 Rows (A01 - A18: 6 hàng x 3 ghế)
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
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi' : 'Giường nằm',
                    'is_window' => true,
                    'position' => 'window_left',
                    'position_label' => 'Cửa sổ (Trái)',
                    'status' => in_array($numLeft, $occupiedSeats) ? 'booked' : 'available',
                ],
                'middle' => [
                    'seat_number' => $numMid,
                    'floor' => 1,
                    'price' => $floor1Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi' : 'Giường nằm',
                    'is_window' => false,
                    'position' => 'middle',
                    'position_label' => 'Dãy giữa',
                    'status' => in_array($numMid, $occupiedSeats) ? 'booked' : 'available',
                ],
                'right' => [
                    'seat_number' => $numRight,
                    'floor' => 1,
                    'price' => $floor1Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi' : 'Giường nằm',
                    'is_window' => true,
                    'position' => 'window_right',
                    'position_label' => 'Cửa sổ (Phải)',
                    'status' => in_array($numRight, $occupiedSeats) ? 'booked' : 'available',
                ]
            ];
        }

        // Floor 2 Rows (B01 - B12: 12 ghế, 6 hàng x 2 dãy cửa sổ, dãy giữa là lối đi thông tầng)
        // Chuẩn cấu trúc xe khách 30 giường: Tầng 1 (18 giường: A01-A18) và Tầng 2 (12 giường: B01-B12)
        $floor2Rows = [];
        for ($i = 1; $i <= 6; $i++) {
            $numLeft = sprintf('B%02d', ($i - 1) * 2 + 1);
            $numRight = sprintf('B%02d', ($i - 1) * 2 + 2);

            $floor2Rows[] = [
                'left' => [
                    'seat_number' => $numLeft,
                    'floor' => 2,
                    'price' => $floor2Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi' : 'Giường nằm',
                    'is_window' => true,
                    'position' => 'window_left',
                    'position_label' => 'Cửa sổ (Trái)',
                    'status' => in_array($numLeft, $occupiedSeats) ? 'booked' : 'available',
                ],
                'middle' => null, // Lối đi thông tầng VIP
                'right' => [
                    'seat_number' => $numRight,
                    'floor' => 2,
                    'price' => $floor2Price,
                    'seat_type' => $isSeatOnly ? 'Ghế ngồi' : 'Giường nằm',
                    'is_window' => true,
                    'position' => 'window_right',
                    'position_label' => 'Cửa sổ (Phải)',
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

        $user = Auth::user();
        $hasUsedStudentFirstTrip = false;
        if ($user) {
            try {
                $hasUsedStudentFirstTrip = (bool)$user->student_first_trip_used || Booking::where('user_id', $user->id)
                    ->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                    ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                    ->exists();
            } catch (\Throwable $e) {
                $hasUsedStudentFirstTrip = (bool)($user->student_first_trip_used ?? false);
            }
        }

        return Inertia::render('Trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'operator_name' => 'nhaxekientruc Terminal',
                'route' => $trip->route,
                'route_name' => $trip->route ? $trip->route->name : 'Tuyến xe nhaxekientruc',
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
                'bus' => $trip->bus,
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
            'user' => $user,
            'hasUsedStudentFirstTrip' => $hasUsedStudentFirstTrip,
            'vouchers' => Voucher::orderBy('is_active', 'desc')
                ->where(function ($q) {
                    $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString());
                })
                ->orderBy('id', 'asc')
                ->get(),
        ]);
    }

    /**
     * Đặt vé & Thanh toán Cọc 30% / 100%
     */
    public function book(Request $request, $id)
    {
        // Yêu cầu bắt buộc đăng nhập mới được đặt vé xe
        if (!Auth::check()) {
            return back()->withErrors(['auth' => 'Quý khách vui lòng đăng nhập tài khoản trước khi tiến hành đặt vé xe.']);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|min:9|max:20',
            'customer_email' => 'required|email|max:255',
            'seat_numbers' => 'required|array|min:1',
            'pickup_stop_name' => 'required|string',
            'dropoff_stop_name' => 'required|string',
            'has_insurance' => 'nullable|boolean',
            'payment_choice' => 'required|in:FULL,DEPOSIT',
            'voucher_code' => 'nullable|string',
            'points_to_redeem' => 'nullable|integer|min:0',
        ], [
            'customer_phone.required' => 'Quý khách bắt buộc phải nhập và xác minh Số điện thoại để Lơ xe liên hệ đón!',
            'customer_phone.min' => 'Số điện thoại di động không hợp lệ (tối thiểu 10 chữ số tại Việt Nam)!',
            'customer_email.required' => 'Quý khách bắt buộc phải nhập và xác minh Gmail để nhận vé điện tử!',
        ]);

        $currentUser = Auth::user();
        if ($currentUser) {
            $userNeedSave = false;
            if (empty($currentUser->phone) && !empty($request->customer_phone)) {
                $currentUser->phone = trim($request->customer_phone);
                $userNeedSave = true;
            }
            if ((empty($currentUser->email) || str_ends_with(strtolower($currentUser->email), '@nhaxekientruc.vn')) && !empty($request->customer_email)) {
                $currentUser->email = strtolower(trim($request->customer_email));
                $userNeedSave = true;
            }
            if ($userNeedSave) {
                $currentUser->save();
            }
        }

        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($id);

        // Kiểm tra đồng bộ ghế trống: nếu ghế đã bị đặt (ở bất kỳ phân hệ nào), từ chối ngay lập tức
        $occupied = SeatSegmentService::getAllBookedSeatsForTrip($trip->id);
        $conflicted = array_intersect($request->seat_numbers, $occupied);
        if (!empty($conflicted)) {
            return back()->withErrors(['seats' => 'Ghế ' . implode(', ', $conflicted) . ' đã có khách đặt trước đó. Vui lòng chọn ghế khác!']);
        }

        // Kiểm tra ghế có đang bị nhân viên giữ chỗ tạm tại quầy vé (POS lock) hay không
        $lockedAtPos = [];
        foreach ($request->seat_numbers as $sNum) {
            if (\Illuminate\Support\Facades\Cache::has("seat_lock_{$trip->id}_{$sNum}")) {
                $lockedAtPos[] = $sNum;
            }
        }
        if (!empty($lockedAtPos)) {
            return back()->withErrors(['seats' => 'Ghế ' . implode(', ', $lockedAtPos) . ' đang được giữ chỗ tạm tại quầy vé. Vui lòng chọn ghế khác hoặc thử lại sau ít phút!']);
        }

        $insuranceFee = $request->boolean('has_insurance') ? (count($request->seat_numbers) * 10000) : 0;
        $totalSeatCount = count($request->seat_numbers);
        $seatPrice = (float) ($trip->base_price ?? 180000);

        // Xử lý mã giảm giá Voucher
        $voucherCode = strtoupper(trim($request->input('voucher_code', '')));
        $voucher = null;
        $discountAmount = 0;
        $discountPerSeat = 0;

        $hasVoucher = !empty($voucherCode);
        $pointsToRedeemInput = max(0, (int)$request->input('points_to_redeem', 0));

        // NGUYÊN TẮC LOẠI TRỪ TƯƠNG HỖ: Đã dùng Voucher thì KHÔNG được dùng Điểm thưởng
        if ($hasVoucher && $pointsToRedeemInput > 0) {
            $pointsToRedeemInput = 0; // Hủy dùng điểm thưởng nếu có voucher
        }

        if ($voucherCode) {
            $valResult = $this->checkVoucherEligibility(
                $voucherCode,
                $currentUser,
                $request->input('customer_email'),
                $seatPrice * $totalSeatCount,
                $request->seat_numbers,
                $trip
            );
            if (!$valResult['valid']) {
                return back()->withErrors(['voucher' => $valResult['message']]);
            }
            $voucher = $valResult['voucher'];
            $discountAmount = $valResult['discount_amount'];
            $discountPerSeat = $valResult['discount_per_seat'];

            $voucher->increment('usage_count');
            if (\Illuminate\Support\Facades\Schema::hasColumn('vouchers', 'used_count')) {
                $voucher->increment('used_count');
            }
        }

        $totalAmount = 0;
        foreach ($request->seat_numbers as $sNum) {
            $isFloor2 = str_starts_with($sNum, 'B');
            $fare = $isFloor2 ? (float)($trip->floor2_price ?? round($seatPrice * 0.95)) : (float)($trip->floor1_price ?? $seatPrice);
            $fare = max(0, $fare - $discountPerSeat);
            $totalAmount += $fare;
        }
        $totalAmount += $insuranceFee;

        // Liên kết tài khoản người dùng
        $userId = Auth::id();

        // Đổi điểm thưởng thành tiền giảm giá: 100 điểm = 1.000 VNĐ (1 điểm = 10 VNĐ)
        // Chỉ áp dụng khi KHÔNG dùng voucher
        $pointsRedeemed = 0;
        $pointsDiscount = 0;
        $pointsDiscountPerSeat = 0;
        if (!$voucher && $userId && $pointsToRedeemInput >= 100) {
            $u = User::find($userId);
            if ($u && ($u->reward_points ?? 0) >= 100) {
                $pointsRedeemed = min($u->reward_points, $pointsToRedeemInput);
                $pointsDiscount = round($pointsRedeemed * 10); // 100 điểm = 1.000 VNĐ
                $pointsDiscount = min($pointsDiscount, $totalAmount);
                $pointsDiscountPerSeat = round($pointsDiscount / max(1, $totalSeatCount));

                // Trừ điểm ngay trong tài khoản
                $u->reward_points = max(0, $u->reward_points - $pointsRedeemed);
                $u->save();
            }
        }

        $totalAmount = max(0, $totalAmount - $pointsDiscount);

        // Xử lý gói ưu đãi:
        // 1. Vé Sinh viên chuyến đầu tiên: Giảm 100% trong 350.000đ (Mã SINHVIEN100 / SINHVIEN0DONG / SV0DONG / TET0DONG)
        // 2. Gói sinh viên 1 năm giảm 30% (hoặc nâng cấp Eco 20%, Pro 25%, Ultra 40%): Mã SINHVIEN / SINHVIEN30
        // 3. Khách hàng mới (mã CHAOBAOMOI / CHAOBANMOI): Giảm 10%
        $authUser = Auth::user();
        $userEmail = strtolower($authUser?->email ?? $request->input('customer_email', ''));
        $isStudent = str_contains($userEmail, '.edu.vn') || str_contains($userEmail, 'sinhvien') || ($authUser && $authUser->is_student);

        if (in_array(strtoupper($voucherCode), ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG']) || ($isStudent && $authUser && !$authUser->student_first_trip_used && in_array(strtoupper($voucherCode), ['SINHVIEN', 'SINHVIEN30']))) {
            if (!$isStudent) {
                return back()->withErrors(['voucher' => 'Mã giảm giá 100% chuyến đầu chỉ dành riêng cho sinh viên đã xác minh!']);
            }
            if ($totalSeatCount > 1) {
                return back()->withErrors(['voucher' => 'Ưu đãi chuyến xe đầu tiên chỉ áp dụng cho tối đa 1 ghế. Quý khách vui lòng chọn đúng 1 ghế!']);
            }
            if ($userId) {
                $hasUsedFirstTrip = (bool)$authUser->student_first_trip_used || Booking::where('user_id', $userId)
                    ->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                    ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                    ->exists();
                if ($hasUsedFirstTrip && !in_array(strtoupper($voucherCode), ['SINHVIEN', 'SINHVIEN30', 'SINHVIEN10', 'SV10', 'PRO30'])) {
                    return back()->withErrors(['voucher' => 'Tài khoản đã sử dụng chuyến xe miễn phí 100% lần đầu tiên rồi. Vui lòng sử dụng mã SINHVIEN30 (Giảm 30%) hoặc SINHVIEN10 (Giảm 10%) cho các chuyến tiếp theo!']);
                }
            }

            // Giảm 100% nhưng tối đa trong 350.000 VNĐ
            $studentDiscount = min($totalAmount, 350000);
            $totalAmount = max(0, $totalAmount - $studentDiscount);
            $discountAmount += $studentDiscount;

            if ($authUser) {
                $authUser->student_first_trip_used = true;
                $authUser->save();
            }
        } elseif (in_array(strtoupper($voucherCode), ['SINHVIEN', 'SINHVIEN30', 'SVVN', 'SV2026', 'PRO30', 'ULTRA40', 'ECO20']) || ($isStudent && empty($voucherCode) && $authUser?->is_membership_active)) {
            $isCancelled = strtoupper((string)($authUser?->membership_plan ?? '')) === 'CANCELLED';
            if ($isCancelled) {
                return back()->withErrors(['voucher' => 'Gói ưu đãi hội viên của bạn đã bị hủy, mã này không còn khả dụng!']);
            }
            $isStudentExpired = $authUser?->student_discount_expires_at && Carbon::parse($authUser->student_discount_expires_at)->isPast();
            $hasActiveMembership = $authUser?->membership_expires_at && Carbon::parse($authUser->membership_expires_at)->isFuture();

            if ($isStudentExpired && !$hasActiveMembership && !$authUser?->is_membership_active) {
                return back()->withErrors(['voucher' => 'Gói ưu đãi 1 năm giảm 30% của bạn đã hết hạn! Vui lòng nâng cấp gói Eco 20%, Pro 25% hoặc Ultra 40% để tiếp tục nhận ưu đãi.']);
            }

            // Tỷ lệ giảm: mặc định 30%, hoặc nâng cấp lên Ultra 40%, Pro 25%, Eco 20%
            $discountPercent = 30;
            if (strtoupper($voucherCode) === 'ULTRA40' || ($authUser && str_contains(strtoupper((string)$authUser->membership_plan), 'ULTRA'))) {
                $discountPercent = 40;
            } elseif (strtoupper($voucherCode) === 'ECO20' || ($authUser && str_contains(strtoupper((string)$authUser->membership_plan), 'ECO'))) {
                $discountPercent = 20;
            } elseif ($hasActiveMembership && $authUser->membership_discount_percent) {
                $discountPercent = (int)$authUser->membership_discount_percent;
            }

            $studentDiscount = round($totalAmount * ($discountPercent / 100));
            $totalAmount = max(0, $totalAmount - $studentDiscount);
            $discountAmount += $studentDiscount;
        } elseif (in_array(strtoupper($voucherCode), ['SINHVIEN10', 'SV10'])) {
            if (!$isStudent) {
                return back()->withErrors(['voucher' => 'Mã [SINHVIEN10] dành riêng cho đối tượng Sinh viên (email .edu.vn)!']);
            }
            $studentDiscount = round($totalAmount * 0.10);
            if ($studentDiscount > 50000) {
                $studentDiscount = 50000;
            }
            $totalAmount = max(0, $totalAmount - $studentDiscount);
            $discountAmount += $studentDiscount;
        } elseif (in_array(strtoupper($voucherCode), ['CHAOBAOMOI', 'CHAOBANMOI', 'KHACHMOI10'])) {
            $newMemberDiscount = round($totalAmount * 0.10);
            $totalAmount = max(0, $totalAmount - $newMemberDiscount);
            $discountAmount += $newMemberDiscount;
        }

        $isDeposit = ($request->input('payment_choice') === 'DEPOSIT') && empty($voucher);
        $depositTargetAmount = $isDeposit ? (float) round($totalAmount * 0.3) : (float) $totalAmount;
        $remainingAmount = max(0, $totalAmount - $depositTargetAmount);

        // Đặt vé trực tuyến: ban đầu trạng thái là UNPAID và paid_amount = 0 (trừ khi vé 0đ được tài trợ)
        // Khách hàng phải quét mã QR VietQR MBBank / MoMo chuyển khoản thì mới xác nhận PAID hoặc DEPOSIT_PAID!
        if ($totalAmount == 0) {
            $paidAmount = 0;
            $paymentStatus = 'PAID';
        } else {
            $paidAmount = 0;
            $paymentStatus = 'UNPAID';
        }

        $bookingCode = 'KT-' . strtoupper(Str::random(6));

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

        $seatQrToken = 'QR-' . Str::uuid()->toString();
        $seatsStr = implode(', ', $request->seat_numbers);

        $notes = $request->filled('notes') ? trim($request->input('notes')) : '';
        if ($isDeposit) {
            $depositNote = "Cọc trước 30% (" . number_format($depositTargetAmount, 0, ',', '.') . "đ). Còn lại 70% (" . number_format($remainingAmount, 0, ',', '.') . "đ) lơ xe thu khi lên xe.";
            $notes = $notes ? ($depositNote . ' | ' . $notes) : $depositNote;
        }

        // TẠO 1 ĐƠN ĐẶT VÉ (BOOKING) DUY NHẤT CHO TOÀN BỘ ĐƠN (KHÔNG LƯU 2 CÁI TRÙNG LẶP)
        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'user_id' => $userId,
            'trip_id' => $trip->id,
            'booking_source' => 'ONLINE',
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'pickup_stop_id' => $pickupStopId,
            'dropoff_stop_id' => $dropoffStopId,
            'pickup_order' => $pickupOrder,
            'dropoff_order' => $dropoffOrder,
            'total_seats' => $totalSeatCount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'payment_method' => $isDeposit ? 'DEPOSIT_30' : $request->input('payment_method', 'VIETQR'),
            'payment_ref_code' => 'VE' . $bookingCode,
            'checkin_status' => 'PENDING',
            'qr_token' => $seatQrToken,
            'voucher_code' => $voucher ? $voucher->code : null,
            'discount_amount' => $discountAmount,
            'points_earned' => max(1, (int)round($totalAmount / 1000)),
            'points_awarded' => 0, // Điểm thưởng CHƯA cộng ngay, chỉ kích hoạt khi xe xuất phát / khách lên xe
            'notes' => $notes ?: null,
        ]);

        // Tạo chi tiết BookingSeat cho từng ghế
        foreach ($request->seat_numbers as $idx => $sNum) {
            $isFloor2 = str_starts_with($sNum, 'B');
            $seatFare = $isFloor2 ? (float)($trip->floor2_price ?? round($seatPrice * 0.95)) : (float)($trip->floor1_price ?? $seatPrice);
            $seatFare = max(0, $seatFare - $discountPerSeat - $pointsDiscountPerSeat);

            BookingSeat::create([
                'booking_id' => $booking->id,
                'trip_id' => $trip->id,
                'seat_number' => $sNum,
                'floor' => $isFloor2 ? 2 : 1,
                'pickup_order' => $pickupOrder,
                'dropoff_order' => $dropoffOrder,
                'price' => $seatFare,
            ]);
        }

        // Tự động lưu SĐT vào hồ sơ người dùng nếu trước đó chưa có (đặc biệt khi đăng ký bằng Gmail)
        if ($userId && $request->filled('customer_phone')) {
            $authUser = User::find($userId);
            if ($authUser && (empty($authUser->phone) || strlen($authUser->phone) < 9)) {
                $authUser->phone = trim($request->customer_phone);
                $authUser->save();
            }
        }

        AuditLog::log('CREATE_ONLINE_BOOKING', "Khách {$request->customer_name} ({$request->customer_phone}) đặt vé {$booking->booking_code} ({$totalSeatCount} ghế: {$seatsStr}, {$totalAmount}đ, Cọc: {$paidAmount}đ" . ($voucher ? ", Voucher {$voucher->code}" : "") . "). Mã QR: {$seatQrToken}", 'Booking', $booking->id);

        // CHÚ Ý: KHÔNG gửi email khi đơn vé đang UNPAID (chưa thanh toán).
        // Email vé điện tử & mã QR check-in sẽ CHỈ được gửi khi khách quét mã chuyển khoản thành công (payment_status = PAID).

        $successMsg = "Đặt chỗ thành công! ";
        if ($voucher) {
            $successMsg .= "Đã áp dụng mã Voucher {$voucher->code} giảm " . number_format($discountAmount) . "đ. ";
        }
        if ($pointsDiscount > 0) {
            $successMsg .= "Đã đổi {$pointsRedeemed} điểm thưởng giảm " . number_format($pointsDiscount) . "đ trực tiếp vào tiền vé. ";
        }
        $successMsg .= "Vui lòng quét mã QR để thanh toán. Vé điện tử và mã QR lên xe sẽ được gửi về Gmail {$request->customer_email} ngay khi thanh toán thành công.";

        return redirect()->route('booking.payment', ['code' => $bookingCode])
            ->with('success', $successMsg);
    }

    /**
     * Màn hình Thanh toán VietQR Napas 247
     */
    public function paymentScreen(Request $request, $code)
    {
        $booking = Booking::with([
            'trip.route.stops',
            'trip.bus',
            'trip.driver',
            'pickupStop',
            'dropoffStop',
            'bookingSeats',
        ])->where('booking_code', $code)->firstOrFail();

        if (in_array($booking->payment_status, ['PAID', 'DEPOSIT_PAID', 'PARTIALLY_PAID'])) {
            try {
                \App\Services\PdfTicketService::saveTicketToPdfFolder($booking);
            } catch (\Throwable $e) {}
        }

        $isDeposit = ($booking->payment_method === 'DEPOSIT_30' || str_contains($booking->notes ?? '', 'Cọc trước 30%'));
        $targetPayAmount = $isDeposit ? (float) round($booking->total_amount * 0.3) : (float) $booking->total_amount;
        $remainingAmount = max(0, (float) $booking->total_amount - $targetPayAmount);

        $vietQr = \App\Services\VietQrService::generatePaymentData($booking, $targetPayAmount);

        $trip = $booking->trip;
        $seatsArr = $booking->bookingSeats->pluck('seat_number')->toArray();

        return Inertia::render('Bookings/Payment', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'total_amount' => (float)$booking->total_amount,
                'paid_amount' => (float)$booking->paid_amount,
                'target_pay_amount' => $targetPayAmount,
                'remaining_amount' => $remainingAmount,
                'is_deposit' => $isDeposit,
                'deposit_percent' => $isDeposit ? 30 : 100,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'payment_confirmed_at' => $booking->payment_confirmed_at ? $booking->payment_confirmed_at->format('H:i d/m/Y') : null,
                'seats' => $seatsArr,
                'seats_label' => implode(', ', $seatsArr),
                'pickup' => $booking->pickupStop?->stop_name ?? ($trip?->route?->origin ?? 'Bến xe TT'),
                'dropoff' => $booking->dropoffStop?->stop_name ?? ($trip?->route?->destination ?? 'Bến đến'),
                'departure_time' => $trip?->departure_time ? Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
                'route_name' => $trip?->route?->name ?? 'Tuyến xe nhaxekientruc',
                'bus_plate' => $trip?->bus?->license_plate ?? '43B-012.34',
                'trip_id' => $trip?->id,
                'ticket_url' => url('/ve/' . $booking->booking_code),
            ],
            'vietQr' => $vietQr,
            'is_admin' => (bool)(session('admin_mode') || Auth::user()?->role === 'ADMIN'),
        ]);
    }

    /**
     * API kiểm tra trạng thái thanh toán theo thời gian thực (Polling)
     * CHỈ xác nhận thanh toán khi thực sự có Webhook tiền vào MBBank 09025042004 hoặc SePay API ghi nhận
     */
    public function checkPaymentStatus(Request $request, $code)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user'])
            ->where('booking_code', $code)
            ->firstOrFail();

        $isDeposit = ($booking->payment_method === 'DEPOSIT_30' || str_contains($booking->notes ?? '', 'Cọc trước 30%'));
        $targetPayAmount = $isDeposit ? (float) round($booking->total_amount * 0.3) : (float) $booking->total_amount;
        $isPaidStatus = in_array($booking->payment_status, ['PAID', 'DEPOSIT_PAID', 'PARTIALLY_PAID']);

        // 1. Tự động kiểm tra API SePay nếu có cấu hình SEPAY_API_TOKEN
        $sepayToken = env('SEPAY_API_TOKEN');
        if (!empty($sepayToken) && !$isPaidStatus) {
            try {
                $accNo = env('VIETQR_ACCOUNT_NO', '09025042004');
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Apikey ' . $sepayToken])
                    ->get('https://my.sepay.vn/userapi/transactions/list', [
                        'account_number' => $accNo,
                        'limit' => 20,
                    ]);

                if ($response->successful()) {
                    $transactions = $response->json('transactions') ?? [];
                    foreach ($transactions as $tx) {
                        $txContent = strtoupper($tx['transaction_content'] ?? '');
                        $rawBookingCode = strtoupper($booking->booking_code); // KT-2QFWP1
                        $cleanBookingCode = preg_replace('/[^A-Z0-9]/', '', $rawBookingCode); // KT2QFWP1
                        $cleanTx = preg_replace('/[^A-Z0-9]/', '', $txContent);
                        $shortCode = preg_replace('/^KT/', '', $cleanBookingCode); // 2QFWP1

                        $isMatched = str_contains($txContent, $rawBookingCode)
                            || str_contains($cleanTx, $cleanBookingCode)
                            || str_contains($cleanTx, 'VE' . $cleanBookingCode)
                            || (strlen($shortCode) >= 4 && str_contains($cleanTx, $shortCode));

                        if ($isMatched) {
                            $booking->payment_status = $isDeposit ? 'DEPOSIT_PAID' : 'PAID';
                            $booking->paid_amount = $targetPayAmount;
                            $booking->payment_confirmed_at = now();
                            $booking->payment_method = $isDeposit ? 'DEPOSIT_30' : 'VIETQR';
                            $booking->payment_ref_code = $tx['reference_number'] ?? ('SEPAY-' . ($tx['id'] ?? time()));
                            $booking->save();

                            AuditLog::log('SEPAY_AUTO_PAID', "Tự động nhận diện giao dịch SePay MBBank 09025042004 cho vé {$booking->booking_code} (" . ($isDeposit ? "Cọc 30%: " . number_format($targetPayAmount) . "đ" : "100%: " . number_format($booking->total_amount) . "đ") . ")", 'Booking', $booking->id);

                            // GỬI EMAIL VÉ ĐIỆN TỬ VỀ GMAIL NGAY KHI THANH TOÁN THÀNH CÔNG
                            if ($booking->customer_email && filter_var($booking->customer_email, FILTER_VALIDATE_EMAIL)) {
                                try {
                                    \App\Services\EmailService::sendBookingConfirmation($booking);
                                } catch (\Throwable $mailEx) {
                                    \Illuminate\Support\Facades\Log::warning("[EMAIL_SEND_ERR] " . $mailEx->getMessage());
                                }
                            }
                            $isPaidStatus = true;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[SEPAY_CHECK_ERROR] " . $e->getMessage());
            }
        }



        return response()->json([
            'booking_code' => $booking->booking_code,
            'payment_status' => $booking->payment_status,
            'is_paid' => in_array($booking->payment_status, ['PAID', 'DEPOSIT_PAID', 'PARTIALLY_PAID']),
            'is_deposit' => $isDeposit,
            'paid_amount' => (float)$booking->paid_amount,
            'remaining_amount' => max(0, (float)$booking->total_amount - (float)$booking->paid_amount),
            'payment_confirmed_at' => $booking->payment_confirmed_at ? $booking->payment_confirmed_at->format('H:i d/m/Y') : null,
        ]);
    }

    /**
     * Xác nhận thanh toán VietQR thành công (Khách bấm hoặc mô phỏng Webhook ngân hàng)
     */
    public function confirmPayment(Request $request, $code)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user'])
            ->where('booking_code', $code)
            ->firstOrFail();

        $isDeposit = ($booking->payment_method === 'DEPOSIT_30' || str_contains($booking->notes ?? '', 'Cọc trước 30%'));
        $targetPayAmount = $isDeposit ? (float) round($booking->total_amount * 0.3) : (float) $booking->total_amount;

        // Kiểm tra xem giao dịch đã thực sự được thanh toán qua Webhook hoặc SePay hay chưa
        $isPaid = in_array($booking->payment_status, ['PAID', 'DEPOSIT_PAID', 'PARTIALLY_PAID']);

        // Thử kiểm tra SePay API nếu có token
        $sepayToken = env('SEPAY_API_TOKEN');
        if (!empty($sepayToken) && !$isPaid) {
            try {
                $accNo = env('VIETQR_ACCOUNT_NO', '09025042004');
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Apikey ' . $sepayToken])
                    ->get('https://my.sepay.vn/userapi/transactions/list', [
                        'account_number' => $accNo,
                        'limit' => 20,
                    ]);

                if ($response->successful()) {
                    $transactions = $response->json('transactions') ?? [];
                    foreach ($transactions as $tx) {
                        $txContent = strtoupper($tx['transaction_content'] ?? '');
                        $rawBookingCode = strtoupper($booking->booking_code);
                        $cleanBookingCode = preg_replace('/[^A-Z0-9]/', '', $rawBookingCode);
                        $cleanTx = preg_replace('/[^A-Z0-9]/', '', $txContent);
                        $shortCode = preg_replace('/^KT/', '', $cleanBookingCode);

                        $isMatched = str_contains($txContent, $rawBookingCode)
                            || str_contains($cleanTx, $cleanBookingCode)
                            || str_contains($cleanTx, 'VE' . $cleanBookingCode)
                            || (strlen($shortCode) >= 4 && str_contains($cleanTx, $shortCode));

                        if ($isMatched) {
                            $booking->payment_status = $isDeposit ? 'DEPOSIT_PAID' : 'PAID';
                            $booking->paid_amount = $targetPayAmount;
                            $booking->payment_confirmed_at = now();
                            $booking->payment_method = $isDeposit ? 'DEPOSIT_30' : 'VIETQR';
                            $booking->payment_ref_code = $tx['reference_number'] ?? ('SEPAY-' . ($tx['id'] ?? time()));
                            $booking->save();
                            $isPaid = true;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        if (!$isPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Hệ thống ngân hàng chưa nhận được giao dịch chuyển khoản cho đơn vé ' . $booking->booking_code . '. Quý khách vui lòng kiểm tra lại giao dịch trên App ngân hàng!',
            ], 400);
        }

        try {
            \App\Services\PdfTicketService::saveTicketToPdfFolder($booking);
        } catch (\Throwable $e) {}

        AuditLog::log('CONFIRM_VIETQR_PAYMENT', "Giao dịch thanh toán VietQR cho đơn vé {$booking->booking_code} đã được xác thực.", 'Booking', $booking->id);

        return response()->json([
            'success' => true,
            'message' => 'Thanh toán thành công! Vé điện tử đã sẵn sàng.',
            'redirect_url' => url('/ve/' . $booking->booking_code),
        ]);
    }

    /**
     * Webhook tự động nhận thông báo tiền thật về tài khoản MBBank từ SePay / Casso / Ngân hàng
     * Khớp lệnh khi tiền thật vào tài khoản 09025042004 - VO TRONG HIEU
     */
    public function handlePaymentWebhook(Request $request)
    {
        // SePay payload: content, transferAmount, transactionDate, referenceCode, id...
        // Casso payload: data: [{ description, amount, ... }]
        $content = $request->input('content') 
            ?? $request->input('description') 
            ?? $request->input('data.0.description') 
            ?? '';

        $amount = (float)($request->input('transferAmount') 
            ?? $request->input('amount') 
            ?? $request->input('data.0.amount') 
            ?? 0);

        if (empty($content)) {
            return response()->json(['success' => false, 'message' => 'No transfer content found in payload'], 400);
        }

        $normalizedContent = preg_replace('/[^A-Z0-9]/', '', strtoupper($content));
        $booking = null;

        // 1. Thử khớp dạng KT-XXXX hoặc KTXXXX
        if (preg_match('/(?:VE)?(KT[A-Z0-9]+)/i', $normalizedContent, $matches)) {
            $rawCode = strtoupper($matches[1]);
            $cleanCode = 'KT-' . substr($rawCode, 2);
            $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user'])
                ->where('booking_code', $cleanCode)
                ->orWhere('booking_code', $rawCode)
                ->first();
        }

        // 2. Nếu chưa tìm thấy, quét tất cả đơn vé đang chờ thanh toán
        if (!$booking) {
            $pendingBookings = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user'])
                ->whereIn('payment_status', ['UNPAID', 'PENDING'])
                ->where('created_at', '>=', now()->subHours(48))
                ->get();

            foreach ($pendingBookings as $pb) {
                $cleanPb = preg_replace('/[^A-Z0-9]/', '', strtoupper($pb->booking_code));
                $shortPb = preg_replace('/^KT/', '', $cleanPb);
                if (str_contains($normalizedContent, $cleanPb) || (strlen($shortPb) >= 4 && str_contains($normalizedContent, $shortPb))) {
                    $booking = $pb;
                    break;
                }
            }
        }

        if ($booking) {
            $isDeposit = ($booking->payment_method === 'DEPOSIT_30' || str_contains($booking->notes ?? '', 'Cọc trước 30%'));
            $targetPayAmount = $isDeposit ? (float) round($booking->total_amount * 0.3) : (float) $booking->total_amount;

            $booking->payment_status = $isDeposit ? 'DEPOSIT_PAID' : 'PAID';
            $booking->paid_amount = $amount > 0 ? $amount : $targetPayAmount;
            $booking->payment_confirmed_at = now();
            $booking->payment_method = $isDeposit ? 'DEPOSIT_30' : 'VIETQR';
            $booking->payment_ref_code = $request->input('referenceCode') ?? ('MB-' . now()->format('YmdHis'));
            $booking->save();
                        try {
                            \App\Services\PdfTicketService::saveTicketToPdfFolder($booking);
                        } catch (\Throwable $e) {}

            AuditLog::log('WEBHOOK_VIETQR_PAID', "Tự động nhận tiền thật từ MBBank 09025042004 (VO TRONG HIEU) cho vé {$booking->booking_code}. Số tiền: " . number_format($amount ?: $targetPayAmount) . "đ (" . ($isDeposit ? "Cọc 30%" : "Đủ 100%") . "). Nội dung: {$content}", 'Booking', $booking->id);

            // Tự động gửi email xác nhận kèm file vé PDF
            if ($booking->customer_email && filter_var($booking->customer_email, FILTER_VALIDATE_EMAIL)) {
                \App\Services\EmailService::sendBookingConfirmation($booking);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tự động xác nhận thanh toán vé {$booking->booking_code} thành công!",
                'booking_code' => $booking->booking_code,
            ]);
        }

        // 3. Khớp chuyển khoản Gói Hội Viên (Nâng cấp VIP): HOIVIEN {userId} [PLAN]
        if (preg_match('/HOIVIEN\s*(\d+)/i', $content, $mUser)) {
            $targetUserId = (int)$mUser[1];
            $targetUser = \App\Models\User::find($targetUserId);
            if ($targetUser) {
                $isUltra = str_contains(strtoupper($content), 'ULTRA');
                $plan = $isUltra ? 'ULTRA' : 'PRO';
                $discountPercent = $isUltra ? 40 : 30;
                $voucherCode = $isUltra ? 'ULTRA40' : 'PRO30';
                $planName = $isUltra ? 'Gói ULTRA (Giảm 40%)' : ($targetUser->is_student ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)');
                $isCancelled = strtoupper((string)($targetUser->membership_plan ?? '')) === 'CANCELLED';
                $price = $isUltra 
                    ? ($targetUser->is_student ? 239000 : 599000) 
                    : ($targetUser->is_student ? ($isCancelled ? 149000 : 0) : 349000);

                $targetUser->membership_plan = $plan;
                $targetUser->membership_discount_percent = $discountPercent;
                $targetUser->student_registered_at = $targetUser->student_registered_at ?? now();
                $targetUser->membership_expires_at = now()->addYear();
                $targetUser->student_discount_expires_at = now()->addYear();
                $targetUser->save();

                try {
                    \Illuminate\Support\Facades\DB::table('membership_subscriptions')->insert([
                        'user_id' => $targetUser->id,
                        'plan' => $plan,
                        'discount_percent' => $discountPercent,
                        'price' => $amount > 0 ? $amount : $price,
                        'duration_months' => 12,
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'payment_status' => 'COMPLETED',
                        'payment_method' => 'SEPAY_WEBHOOK_MBBANK',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {}

                try {
                    \App\Services\PdfTicketService::saveMembershipInvoiceToPdfFolder($targetUser, $planName, $amount > 0 ? $amount : $price, $voucherCode);
                } catch (\Throwable $e) {}

                AuditLog::log('WEBHOOK_MEMBERSHIP_PAID', "Tự động kích hoạt {$planName} cho hội viên #{$targetUser->id} ({$targetUser->name}) qua SePay webhook MBBank. Số tiền: " . number_format($amount ?: $price) . "đ. Nội dung: {$content}", 'User', $targetUser->id);

                return response()->json([
                    'success' => true,
                    'message' => "Đã tự động xác nhận và kích hoạt {$planName} cho tài khoản {$targetUser->name} thành công!",
                    'user_id' => $targetUser->id,
                    'plan' => $plan,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "Không tìm thấy đơn vé hoặc mã hội viên tương ứng với nội dung chuyển khoản: {$content}",
        ], 404);
    }

    /**
     * AI Nhắc Lịch Khởi Hành & Gửi Mã QR Về Gmail Cho Khách
     */
    public function sendAiReminderEmail(Request $request, $code)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats'])
            ->where('booking_code', $code)
            ->firstOrFail();

        $trip = $booking->trip;
        $seatsStr = $booking->bookingSeats->pluck('seat_number')->join(', ');

        if (!$booking->customer_email || !filter_var($booking->customer_email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn vé chưa có địa chỉ Gmail hợp lệ để gửi thông báo.'
            ], 422);
        }

        $sent = \App\Services\EmailService::sendDepartureReminder($booking);
        // AI Gọi điện thoại thông báo
        \App\Services\AiCallService::makeReminderCallAuto($booking);

        return response()->json([
            'success' => true,
            'message' => "Trợ lý AI nhaxekientruc đã gửi thành công Mã vé QR về Gmail {$booking->customer_email} & thực hiện cuộc gọi nhắc xuất bến!"
        ]);
    }

    /**
     * Xem Vé Điện Tử & Quản Lý Vé (Đồng bộ cùng 1 trang Quản Lý Vé duy nhất với Bookings/Detail.vue)
     */
    public function ticket(Request $request, $code)
    {
        return $this->bookingDetail($request, $code);
    }

    /**
     * Chi tiết đặt vé kèm thông tin tài xế/lơ xe + GPS tracking
     */
    public function bookingDetail(Request $request, $code)
    {
        $booking = Booking::with([
            'trip.route.stops',
            'trip.bus',
            'trip.driver',
            'trip.conductor',
            'bookingSeats',
            'pickupStop',
            'dropoffStop',
            'review',
        ])->where('booking_code', $code)->firstOrFail();

        $trip = $booking->trip;
        $isTripCompleted = in_array($trip?->status, ['COMPLETED', 'CLOSED']);
        $isAwarded = (bool)($booking->points_awarded || ($isTripCompleted && $booking->payment_status === 'PAID'));
        $earnedPoints = (int)($booking->points_earned ?: ($booking->paid_amount / 1000));
        if ($earnedPoints <= 0) $earnedPoints = (int)($booking->total_amount / 1000);

        $customerUser = $booking->user_id ? User::find($booking->user_id) : User::where('phone', $booking->customer_phone)->first();

        // Tính toán điều kiện hoàn tiền & chính sách hủy vé
        $depTime = $trip && $trip->departure_time 
            ? Carbon::parse($trip->departure_time)->timezone('Asia/Ho_Chi_Minh')
            : null;
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $hoursUntilDep = $depTime ? $now->diffInHours($depTime, false) : 0;
        if ($depTime && $now->gt($depTime)) {
            $hoursUntilDep = -1;
        }

        $minutesSinceBooking = $booking->created_at ? $booking->created_at->diffInMinutes($now) : 999;
        // Ân hạn đặt vé: Nếu mới đặt vé trong vòng 2 giờ (120 phút) và chuyến chưa xong -> Hoàn 100% không mất phí!
        $isGracePeriod = ($minutesSinceBooking <= 120 && !$isTripCompleted && $hoursUntilDep > 0);

        $policies = CancellationPolicy::where('is_active', true)->orderBy('hours_before', 'desc')->get();
        $applicablePolicy = null;
        foreach ($policies as $pol) {
            if ($hoursUntilDep >= $pol->hours_before) {
                $applicablePolicy = $pol;
                break;
            }
        }
        if (!$applicablePolicy && $policies->isNotEmpty()) {
            $applicablePolicy = $policies->last();
        }

        if ($isGracePeriod) {
            $refundPercent = 100;
            $feePercent = 0;
            $isEligible = true;
        } else {
            $refundPercent = $applicablePolicy ? (int)$applicablePolicy->refund_percent : 70;
            if ($refundPercent <= 0 && $hoursUntilDep >= 0 && !$isTripCompleted) {
                $refundPercent = 70;
            }
            $feePercent = 100 - $refundPercent;
            $isEligible = ($hoursUntilDep >= 0 && !$isTripCompleted && $refundPercent > 0);
        }

        $paid = (float)($booking->paid_amount ?: $booking->total_amount);
        $estimatedRefund = round($paid * ($refundPercent / 100));

        return Inertia::render('Bookings/Detail', [
            'booking' => [
                'id'             => $booking->id,
                'booking_code'   => $booking->booking_code,
                'customer_name'  => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'seats'          => $booking->bookingSeats->pluck('seat_number')->toArray(),
                'pickup_stop'    => $booking->pickupStop,
                'dropoff_stop'   => $booking->dropoffStop,
                'total_amount'   => (float)$booking->total_amount,
                'paid_amount'    => (float)$booking->paid_amount,
                'remaining_amount' => max(0, (float)$booking->total_amount - (float)$booking->paid_amount),
                'is_deposit'     => in_array($booking->payment_status, ['DEPOSIT_PAID', 'PARTIALLY_PAID']) || ($booking->payment_method === 'DEPOSIT_30') || str_contains($booking->notes ?? '', 'Cọc trước 30%'),
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'checkin_status' => $booking->checkin_status,
                'checked_in_at'  => $booking->checked_in_at?->format('H:i d/m/Y'),
                'qr_token'       => $booking->qr_token,
                'voucher_code'   => $booking->voucher_code,
                'discount_amount'=> (float)($booking->discount_amount ?? 0),
                'points_earned'  => $earnedPoints,
                'points_awarded' => $isAwarded,
                'trip_completed' => $isTripCompleted,
                'has_reviewed'   => $booking->review !== null,
                'created_at'     => $booking->created_at->format('H:i d/m/Y'),
                'notes'          => $booking->notes,
                'cancelled_at'   => $booking->cancelled_at ? Carbon::parse($booking->cancelled_at)->format('H:i d/m/Y') : null,
                'cancel_reason'  => $booking->cancel_reason,
                'refund_status'  => $booking->refund_status,
                'refund_bank_name' => $booking->refund_bank_name,
                'refund_account_number' => $booking->refund_account_number,
                'refund_account_name' => $booking->refund_account_name,
                'refund_qr_code' => $booking->refund_qr_code,
                'refund_amount'  => (float)($booking->refund_amount ?: $estimatedRefund),
                'refund_reason'  => $booking->refund_reason,
                'refund_requested_at' => $booking->refund_requested_at ? Carbon::parse($booking->refund_requested_at)->format('H:i d/m/Y') : null,
                'refund_processed_at' => $booking->refund_processed_at ? Carbon::parse($booking->refund_processed_at)->format('H:i d/m/Y') : null,
                'refund_processed_by' => $booking->refund_processed_by,
                'refund_admin_notes' => $booking->refund_admin_notes,
                'policy_check'   => [
                    'hours_until_departure' => round($hoursUntilDep, 1),
                    'minutes_since_booking' => $minutesSinceBooking,
                    'is_grace_period' => $isGracePeriod,
                    'is_eligible' => $isEligible,
                    'refund_percent' => $refundPercent,
                    'fee_percent' => $feePercent,
                    'estimated_refund_amount' => $estimatedRefund,
                    'is_cancellable' => !in_array($booking->payment_status, ['REFUNDED']) && !$isTripCompleted && empty($booking->refund_status),
                ],
            ],
            'policies' => $policies,
            'customer_account' => $customerUser ? [
                'name'          => $customerUser->name,
                'reward_points' => (int)($customerUser->reward_points ?? 0),
                'loyalty_tier'  => $customerUser->loyalty_tier ?? 'MEMBER',
            ] : null,
            'trip' => $trip ? [
                'id'             => $trip->id,
                'trip_code'      => $trip->trip_code,
                'status'         => $trip->status,
                'departure_time' => $trip->departure_time->format('H:i'),
                'departure_date' => $trip->departure_time->format('d/m/Y'),
                'arrival_time'   => $trip->arrival_time->format('H:i'),
                'route_name'     => $trip->route?->name,
                'origin'         => $trip->route?->origin,
                'destination'    => $trip->route?->destination,
                'stops'          => $trip->route?->stops->sortBy('stop_order')->values(),
                'bus_type'       => $trip->bus?->bus_type,
                'license_plate'  => $trip->bus?->license_plate ?: '43B-012.34',
                'current_lat'    => (float)($trip->current_lat ?: 16.0544),
                'current_lng'    => (float)($trip->current_lng ?: 108.2022),
                'current_speed'  => $trip->current_speed_kmh ?: ($trip->status === 'IN_TRANSIT' ? 68 : 0),
                'gps_updated_at' => $trip->gps_updated_at ? $trip->gps_updated_at->diffForHumans() : 'Vừa mới đây',
                'delay_minutes'  => $trip->delay_minutes ?? 0,
                'current_stop_order' => $trip->current_stop_order ?? 0,
            ] : null,
            'driver' => $trip?->driver ? [
                'id'               => $trip->driver->id,
                'name'             => $trip->driver->name,
                'phone'            => $trip->driver->phone,
                'license_number'   => $trip->driver->license_number,
                'license_class'    => $trip->driver->license_class,
                'years_experience' => $trip->driver->years_experience,
                'avg_rating'       => $trip->driver->avg_rating,
                'total_trips'      => $trip->driver->total_trips,
                'avatar'           => $trip->driver->avatar,
            ] : null,
            'conductor' => $trip?->conductor ? [
                'id'    => $trip->conductor->id,
                'name'  => $trip->conductor->name,
                'phone' => $trip->conductor->phone,
            ] : null,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Khách hàng gửi đơn yêu cầu hủy vé & hoàn tiền kèm STK + mã QR
     */
    public function requestBookingRefund(Request $request, $code)
    {
        $booking = Booking::with(['trip.bus', 'trip.route', 'bookingSeats'])->where('booking_code', $code)->firstOrFail();

        if ($booking->payment_status === 'REFUNDED' || $booking->refund_status === 'APPROVED') {
            return back()->with('error', 'Vé này đã được hoàn tiền trước đó.');
        }

        if ($booking->refund_status === 'PENDING') {
            return back()->with('error', 'Yêu cầu hoàn tiền của bạn đang được Ban quản trị xử lý.');
        }

        $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'reason' => 'required|string|max:500',
            'qr_image' => 'nullable|image|max:5120',
            'qr_url' => 'nullable|string',
            'insist_exception' => 'nullable|boolean',
            'exception_note' => 'nullable|string|max:1000',
        ]);

        $depTime = $booking->trip && $booking->trip->departure_time 
            ? Carbon::parse($booking->trip->departure_time)->timezone('Asia/Ho_Chi_Minh')
            : null;

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $hoursUntilDep = $depTime ? $now->diffInHours($depTime, false) : 0;
        if ($depTime && $now->gt($depTime)) {
            $hoursUntilDep = -1;
        }

        $isTripCompleted = in_array($booking->trip?->status, ['COMPLETED', 'CLOSED']);
        $minutesSinceBooking = $booking->created_at ? $booking->created_at->diffInMinutes($now) : 999;
        $isGracePeriod = ($minutesSinceBooking <= 120 && !$isTripCompleted && $hoursUntilDep > 0);

        $policies = CancellationPolicy::where('is_active', true)->orderBy('hours_before', 'desc')->get();
        
        $applicablePolicy = null;
        foreach ($policies as $pol) {
            if ($hoursUntilDep >= $pol->hours_before) {
                $applicablePolicy = $pol;
                break;
            }
        }
        if (!$applicablePolicy && $policies->isNotEmpty()) {
            $applicablePolicy = $policies->last();
        }

        if ($isGracePeriod) {
            $refundPercent = 100;
            $isEligible = true;
        } else {
            $refundPercent = $applicablePolicy ? (int)$applicablePolicy->refund_percent : 70;
            if ($refundPercent <= 0 && $hoursUntilDep >= 0 && !$isTripCompleted) {
                $refundPercent = 70;
            }
            $isEligible = ($hoursUntilDep >= 0 && !$isTripCompleted && $refundPercent > 0);
        }

        $isInsist = (bool)$request->input('insist_exception', true);

        $paidAmount = (float)($booking->paid_amount ?: $booking->total_amount);
        $refundAmount = round($paidAmount * ($refundPercent / 100));

        // Xử lý mã QR ngân hàng
        $qrPath = null;
        if ($request->hasFile('qr_image')) {
            $file = $request->file('qr_image');
            $fileName = 'refund_qr_' . $booking->booking_code . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destDir = public_path('uploads/refund_qrs');
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }
            $file->move($destDir, $fileName);
            $qrPath = '/uploads/refund_qrs/' . $fileName;
        } elseif ($request->filled('qr_url')) {
            $qrPath = $request->qr_url;
        } else {
            // Tự sinh VietQR chuẩn chuyển khoản ngân hàng
            $cleanAcc = preg_replace('/[^0-9]/', '', $request->account_number);
            $cleanBank = trim($request->bank_name);
            $transferAmount = $refundAmount > 0 ? $refundAmount : $paidAmount;
            $qrPath = "https://img.vietqr.io/image/{$cleanBank}-{$cleanAcc}-compact2.png?amount={$transferAmount}&addInfo=HOAN+VE+{$booking->booking_code}";
        }

        $fullReason = $request->reason;
        if ($isGracePeriod) {
            $fullReason .= " [ÂN HẠN VỪA ĐẶT VÉ TRONG 2 GIỜ: HOÀN 100%]";
        } elseif (!$isEligible) {
            $fullReason .= " [KIẾN NGHỊ NGOẠI LỆ (XE ĐÃ CHẠY): " . ($request->exception_note ?: 'Khách hàng gửi yêu cầu hủy vé & hoàn tiền qua hệ thống') . "]";
        }

        // Cập nhật thông tin yêu cầu hoàn tiền - Ghế VẪN ĐƯỢC GIỮ NGUYÊN cho đến khi Admin duyệt
        $booking->refund_bank_name = $request->bank_name;
        $booking->refund_account_number = $request->account_number;
        $booking->refund_account_name = mb_strtoupper($request->account_name, 'UTF-8');
        $booking->refund_qr_code = $qrPath;
        $booking->refund_amount = $refundAmount;
        $booking->refund_status = 'PENDING';
        $booking->refund_reason = $fullReason;
        $booking->refund_requested_at = now();
        // Ghế vẫn giữ chỗ, không set CANCELLED và không giải phóng ghế ở đây
        // Ghế chỉ được giải phóng khi Admin duyệt đơn hoàn tiền trong AdminController::approveRefund

        $policyTag = $isGracePeriod ? " [Ân hạn hủy nhanh sau khi đặt: 100%]" : " [Chính sách: {$refundPercent}%]";
        $actionNote = "Khách gửi đơn hủy vé & yêu cầu hoàn tiền (Đang chờ Admin duyệt) lúc " . now()->format('d/m/Y H:i') . 
                      ". STK: {$request->account_number} ({$request->bank_name}) - Chủ TK: " . mb_strtoupper($request->account_name, 'UTF-8') . 
                      " - Số tiền dự kiến hoàn: " . number_format($refundAmount, 0, ',', '.') . "đ ({$refundPercent}%)." . $policyTag;
        $booking->notes = $booking->notes ? ($booking->notes . " | " . $actionNote) : $actionNote;
        $booking->save();

        AuditLog::create([
            'trip_id' => $booking->trip_id,
            'action' => 'REFUND_REQUESTED',
            'description' => "Khách {$booking->customer_name} gửi yêu cầu hủy vé #{$booking->booking_code} và hoàn tiền " . number_format($refundAmount, 0, ',', '.') . "đ qua STK {$request->account_number} ({$request->bank_name}).",
            'performed_by' => $booking->customer_name ?: 'Khách hàng',
        ]);

        return back()->with('success', 'Đơn yêu cầu hủy vé & hoàn tiền đã được gửi thành công! Quý khách vui lòng chờ Ban quản trị kiểm tra thông tin và thực hiện lệnh chuyển khoản.');
    }

    /**
     * Live GPS Tracking API cho chuyến xe (JSON response)
     */
    public function liveTracking(Request $request, $id)
    {
        $trip = Trip::with(['route.stops', 'bus', 'driver', 'conductor'])->findOrFail($id);

        return response()->json([
            'trip_id'       => $trip->id,
            'status'        => $trip->status,
            'current_lat'   => $trip->current_lat ?? 16.0544,
            'current_lng'   => $trip->current_lng ?? 108.2022,
            'current_speed' => $trip->current_speed_kmh ?? 0,
            'gps_updated_at'=> $trip->gps_updated_at?->toIso8601String(),
            'current_stop_order' => $trip->current_stop_order ?? 0,
            'delay_minutes' => $trip->delay_minutes ?? 0,
            'stops'         => $trip->route?->stops->sortBy('stop_order')->values(),
            'driver_name'   => $trip->driver?->name,
            'conductor_name'=> $trip->conductor?->name,
            'bus_plate'     => $trip->bus?->license_plate,
        ]);
    }

    /**
     * Update GPS position (called by driver app)
     */
    public function updateGps(Request $request, $id)
    {
        $request->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric', 'speed' => 'nullable|integer']);
        $trip = Trip::findOrFail($id);
        $trip->current_lat = $request->lat;
        $trip->current_lng = $request->lng;
        $trip->current_speed_kmh = $request->speed ?? 0;
        $trip->gps_updated_at = now();
        $trip->save();
        return response()->json(['success' => true]);
    }

    /**
     * Lịch sử đặt vé của khách hàng (kèm thống kê điểm tích lũy & phân hạng)
     */
    public function myBookings(Request $request)
    {
        $phone   = $request->input('phone', '');
        $email   = $request->input('email', '');
        $dateFrom= $request->input('date_from');
        $dateTo  = $request->input('date_to');
        $period  = $request->input('period', 'all');

        $query = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'bookingSeats', 'review']);

        $user = Auth::user();

        // Nếu khách chưa đăng nhập và chưa nhập SĐT/Email tra cứu -> Trả về giao diện tra cứu vé yêu cầu nhập SĐT/Email
        if (!$user && empty($phone) && empty($email)) {
            return Inertia::render('Bookings/MyBookings', [
                'bookings' => [],
                'filters' => ['phone' => '', 'email' => '', 'period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo],
                'total_count' => 0,
                'user' => null,
                'customer_loyalty' => [
                    'reward_points' => 0,
                    'loyalty_tier' => 'MEMBER',
                    'total_points_earned' => 0,
                    'pending_points' => 0,
                    'total_spent' => 0,
                    'completed_trips' => 0,
                ],
            ]);
        }

        $query->where(function ($q) use ($phone, $email, $user) {
            if ($phone) $q->orWhere('customer_phone', 'like', "%{$phone}%");
            if ($email) $q->orWhere('customer_email', 'like', "%{$email}%");
            if ($user) {
                $q->orWhere('user_id', $user->id)
                  ->orWhere('customer_email', $user->email)
                  ->orWhere('customer_phone', $user->phone);
            }
        });

        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        if (!$dateFrom && !$dateTo) {
            switch ($period) {
                case 'today':  $query->whereDate('created_at', Carbon::today()); break;
                case 'week':   $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]); break;
                case 'month':  $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year); break;
                case 'year':   $query->whereYear('created_at', Carbon::now()->year); break;
            }
        }

        $policies = CancellationPolicy::where('is_active', true)->orderBy('hours_before', 'desc')->get();

        $bookings = $query->orderBy('created_at', 'desc')->get()->map(function ($b) use ($policies) {
            $isTripDone = in_array($b->trip?->status, ['COMPLETED', 'CLOSED']);
            $isAwarded = (bool)($b->points_awarded || ($isTripDone && $b->payment_status === 'PAID'));
            $pts = (int)($b->points_earned ?: ($b->paid_amount / 1000));
            if ($pts <= 0) $pts = (int)($b->total_amount / 1000);

            $depTime = $b->trip?->departure_time ? Carbon::parse($b->trip->departure_time)->timezone('Asia/Ho_Chi_Minh') : null;
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            $hoursUntilDep = $depTime ? $now->diffInHours($depTime, false) : 0;
            if ($depTime && $now->gt($depTime)) {
                $hoursUntilDep = -1;
            }

            $minutesSinceBooking = $b->created_at ? $b->created_at->diffInMinutes($now) : 999;
            $isGracePeriod = ($minutesSinceBooking <= 120 && !$isTripDone && $hoursUntilDep > 0);

            if ($isGracePeriod) {
                $refundPercent = 100;
                $isEligible = true;
            } else {
                $applicablePolicy = null;
                foreach ($policies as $pol) {
                    if ($hoursUntilDep >= $pol->hours_before) {
                        $applicablePolicy = $pol;
                        break;
                    }
                }
                if (!$applicablePolicy && $policies->isNotEmpty()) {
                    $applicablePolicy = $policies->last();
                }
                $refundPercent = $applicablePolicy ? (int)$applicablePolicy->refund_percent : 70;
                if ($refundPercent <= 0 && $hoursUntilDep >= 0 && !$isTripDone) {
                    $refundPercent = 70;
                }
                $isEligible = ($hoursUntilDep >= 0 && !$isTripDone && $refundPercent > 0);
            }

            $paid = (float)($b->paid_amount ?: $b->total_amount);
            $estimatedRefund = round($paid * ($refundPercent / 100));

            return [
                'id'             => $b->id,
                'booking_code'   => $b->booking_code,
                'customer_name'  => $b->customer_name,
                'customer_phone' => $b->customer_phone,
                'seats'          => $b->bookingSeats->pluck('seat_number')->toArray(),
                'route_name'     => $b->trip?->route?->name,
                'origin'         => $b->trip?->route?->origin,
                'destination'    => $b->trip?->route?->destination,
                'departure_time' => $b->trip?->departure_time?->format('H:i d/m/Y'),
                'departure_raw'  => $b->trip?->departure_time?->toIso8601String(),
                'arrival_time'   => $b->trip?->arrival_time?->format('H:i d/m/Y'),
                'trip_status'    => $b->trip?->status,
                'trip_id'        => $b->trip?->id,
                'bus_type'       => $b->trip?->bus?->bus_type,
                'bus_plate'      => $b->trip?->bus?->license_plate ?? '43B-012.34',
                'driver_name'    => $b->trip?->driver?->name,
                'driver_phone'   => $b->trip?->driver?->phone,
                'current_lat'    => $b->trip?->current_lat ?? 16.0544,
                'current_lng'    => $b->trip?->current_lng ?? 108.2022,
                'current_speed'  => $b->trip?->current_speed_kmh ?: 68,
                'delay_minutes'  => $b->trip?->delay_minutes ?? 0,
                'is_round_trip'  => (bool)($b->is_round_trip ?? (str_contains(mb_strtolower($b->trip?->route?->name ?? ''), 'khứ hồi') || str_contains(mb_strtolower($b->notes ?? ''), 'khứ hồi'))),
                'round_trip_leg' => $b->round_trip_leg ?? (str_contains(mb_strtolower($b->trip?->route?->origin ?? ''), 'đà nẵng') ? 'CHIỀU ĐI' : 'CHIỀU VỀ'),
                'total_amount'   => (float)$b->total_amount,
                'paid_amount'    => $paid,
                'payment_status' => $b->payment_status,
                'checkin_status' => $b->checkin_status,
                'voucher_code'   => $b->voucher_code,
                'discount_amount'=> (float)($b->discount_amount ?? 0),
                'points_earned'  => $pts,
                'points_awarded' => $isAwarded,
                'has_reviewed'   => $b->review !== null,
                'created_at'     => $b->created_at->format('d/m/Y H:i'),
                'cancelled_at'   => $b->cancelled_at ? Carbon::parse($b->cancelled_at)->format('d/m/Y H:i') : null,
                'refund_status'  => $b->refund_status,
                'refund_amount'  => (float)($b->refund_amount ?: $estimatedRefund),
                'refund_bank_name' => $b->refund_bank_name,
                'refund_account_number' => $b->refund_account_number,
                'refund_account_name' => $b->refund_account_name,
                'refund_qr_code' => $b->refund_qr_code,
                'refund_admin_notes' => $b->refund_admin_notes,
                'hours_until_departure' => round($hoursUntilDep, 1),
                'is_eligible_for_refund' => $isEligible,
                'refund_percent' => $refundPercent,
                'is_cancellable' => !in_array($b->payment_status, ['REFUNDED']) && !$isTripDone && empty($b->refund_status),
            ];
        });

        $activeCustUser = $user ?: ($phone ? User::where('phone', $phone)->first() : null);
        $customerLoyalty = [
            'reward_points' => (int)($activeCustUser?->reward_points ?? 0),
            'loyalty_tier' => $activeCustUser?->loyalty_tier ?? 'MEMBER',
            'total_points_earned' => $bookings->where('points_awarded', true)->sum('points_earned'),
            'pending_points' => $bookings->where('points_awarded', false)->sum('points_earned'),
            'total_spent' => (float)$bookings->where('payment_status', 'PAID')->sum('paid_amount'),
            'completed_trips' => $bookings->where('points_awarded', true)->count(),
        ];

        return Inertia::render('Bookings/MyBookings', [
            'bookings' => $bookings,
            'filters' => ['phone' => $phone, 'email' => $email, 'period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo],
            'total_count' => $bookings->count(),
            'user' => $user,
            'customer_loyalty' => $customerLoyalty,
        ]);
    }

    /**
     * My bookings JSON API (for AJAX)
     */
    public function myBookingsApi(Request $request)
    {
        $phone = $request->input('phone', '');
        $email = $request->input('email', '');
        $query = Booking::with(['trip.route', 'trip.bus', 'bookingSeats', 'review']);
        if ($phone) $query->where('customer_phone', 'like', "%{$phone}%");
        if ($email) $query->orWhere('customer_email', 'like', "%{$email}%");
        $user = Auth::user();
        if ($user && !$phone && !$email) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('customer_email', $user->email)->orWhere('customer_phone', $user->phone);
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $bookings = $query->orderBy('created_at', 'desc')->get();
        return response()->json(['bookings' => $bookings, 'total' => $bookings->count()]);
    }

    /**
     * Xác thực và tính giá chiết khấu Voucher trực tiếp từ Database
     */
    /**
     * API: Lấy danh sách Voucher đang kích hoạt từ Database
     */
    public function activeVouchersApi(Request $request)
    {
        $vouchers = Voucher::orderBy('is_active', 'desc')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'vouchers' => $vouchers,
        ]);
    }

    /**
     * Xác thực và tính giá chiết khấu Voucher trực tiếp từ Database
     * Hỗ trợ phân quyền đối tượng: Sinh Viên (STUDENT), Cá Nhân (INDIVIDUAL), Tất Cả (ALL)
     */
    /**
     * Xác thực toàn diện tính hợp lệ của Voucher trước khi áp dụng hoặc thanh toán vé
     */
    public function checkVoucherEligibility($inputCode, $authUser, $customerEmail, $amount, $seatNumbers = [], $trip = null)
    {
        $inputCode = strtoupper(trim((string)$inputCode));
        if (!$inputCode) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mã Voucher!'];
        }

        // Ánh xạ các alias phổ biến về mã chuẩn trong Database
        $codeMap = [
            'SINHVIEN100' => 'SINHVIEN0DONG',
            'SV0DONG' => 'SINHVIEN0DONG',
            'VE0DONG' => 'SINHVIEN0DONG',
            'SVVN' => 'SINHVIEN30',
            'SV2026' => 'SINHVIEN30',
            'SV10' => 'SINHVIEN10',
            'BANMOI10' => 'CHAOBAOMOI',
            'KHACHMOI10' => 'CHAOBAOMOI',
            'CHAOBANMOI' => 'CHAOBANMOI',
        ];
        $code = $codeMap[$inputCode] ?? $inputCode;

        $voucher = Voucher::where('code', $code)->first();
        if (!$voucher && isset($codeMap[$inputCode])) {
            $voucher = Voucher::where('code', $inputCode)->first();
        }

        if (!$voucher) {
            return [
                'valid' => false,
                'message' => "Mã khuyến mãi \"{$inputCode}\" không tồn tại trên hệ thống nhà xe!"
            ];
        }

        if (!$voucher->is_active) {
            return [
                'valid' => false,
                'message' => "Mã khuyến mãi \"{$voucher->code}\" hiện đang tạm dừng áp dụng!"
            ];
        }

        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->endOfDay()->isPast()) {
            return [
                'valid' => false,
                'message' => "Mã khuyến mãi \"{$voucher->code}\" đã hết hạn sử dụng!"
            ];
        }

        if ($voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit) {
            return [
                'valid' => false,
                'message' => "Mã khuyến mãi \"{$voucher->code}\" đã hết lượt sử dụng toàn sàn!"
            ];
        }

        $userEmail = strtolower($authUser?->email ?? $customerEmail ?? '');
        $isStudent = str_contains($userEmail, '.edu.vn') || str_contains($userEmail, 'sinhvien') || (bool)($authUser && $authUser->is_student);
        $userPlan = strtoupper((string)($authUser?->membership_plan ?? ''));

        // 1. KIỂM TRA HỦY GÓI: Nếu người dùng đã hủy gói hội viên
        if ($authUser && $userPlan === 'CANCELLED') {
            if (in_array(strtoupper($voucher->code), ['ULTRA40', 'PRO30', 'ECO20', 'SINHVIEN30', 'SINHVIEN', 'SINHVIEN0DONG', 'SINHVIEN100'])) {
                return [
                    'valid' => false,
                    'message' => "Gói hội viên của bạn đã bị hủy, mã ưu đãi độc quyền [{$voucher->code}] không còn khả dụng!"
                ];
            }
        }

        // 2. KIỂM TRA ĐANG DÙNG ULTRA: Không được dùng mã 30%
        if ($authUser && str_contains($userPlan, 'ULTRA')) {
            if (str_contains(strtoupper($voucher->code), '30') || in_array(strtoupper($voucher->code), ['SINHVIEN', 'SINHVIEN30', 'PRO30'])) {
                return [
                    'valid' => false,
                    'message' => 'Bạn đang sử dụng Gói ULTRA VIP (-40%), mã giảm 30% không khả dụng! Vui lòng sử dụng mã ULTRA40.'
                ];
            }
        }

        // 3. KIỂM TRA MÃ ULTRA40: Chỉ cho phép người dùng đang có gói ULTRA còn hạn
        if (strtoupper($voucher->code) === 'ULTRA40') {
            if (!$authUser || !str_contains($userPlan, 'ULTRA')) {
                return [
                    'valid' => false,
                    'message' => 'Mã [ULTRA40] chỉ áp dụng cho tài khoản đang sở hữu Gói ULTRA (-40%)!'
                ];
            }
            if ($authUser->membership_expires_at && Carbon::parse($authUser->membership_expires_at)->isPast()) {
                return [
                    'valid' => false,
                    'message' => 'Gói ULTRA của bạn đã hết hạn vào ngày ' . Carbon::parse($authUser->membership_expires_at)->format('d/m/Y') . '! Vui lòng gia hạn gói để tiếp tục sử dụng.'
                ];
            }
        }

        // 4. KIỂM TRA MÃ PRO30: Chỉ cho phép tài khoản gói PRO còn hạn
        if (strtoupper($voucher->code) === 'PRO30') {
            if (!$authUser || (!str_contains($userPlan, 'PRO') && !$authUser->is_student)) {
                return [
                    'valid' => false,
                    'message' => 'Mã [PRO30] chỉ áp dụng cho tài khoản đăng ký Gói Hội Viên PRO (-30%)!'
                ];
            }
            $expDate = $authUser->membership_expires_at ?? $authUser->student_discount_expires_at;
            if ($expDate && Carbon::parse($expDate)->isPast()) {
                return [
                    'valid' => false,
                    'message' => 'Gói PRO của bạn đã hết hạn vào ngày ' . Carbon::parse($expDate)->format('d/m/Y') . '! Vui lòng gia hạn để tiếp tục sử dụng.'
                ];
            }
        }

        // 5. KIỂM TRA ĐỐI TƯỢNG ÁP DỤNG (target_audience)
        if ($voucher->target_audience === 'STUDENT') {
            if (!$isStudent) {
                return [
                    'valid' => false,
                    'message' => "Mã [{$voucher->code}] dành riêng cho đối tượng Sinh viên (email .edu.vn)! Vui lòng đăng nhập bằng tài khoản sinh viên."
                ];
            }
        }

        // 6. KIỂM TRA MÃ SINH VIÊN 30% (SINHVIEN30, SINHVIEN)
        if (in_array(strtoupper($voucher->code), ['SINHVIEN', 'SINHVIEN30', 'SVVN'])) {
            if (!$isStudent) {
                return [
                    'valid' => false,
                    'message' => 'Mã [SINHVIEN30] dành riêng cho Sinh viên có email trường (.edu.vn)!'
                ];
            }
            if ($authUser) {
                $isStudentExpired = $authUser->student_discount_expires_at && Carbon::parse($authUser->student_discount_expires_at)->isPast();
                $hasActiveMembership = $authUser->membership_expires_at && Carbon::parse($authUser->membership_expires_at)->isFuture();
                if ($isStudentExpired && !$hasActiveMembership) {
                    return [
                        'valid' => false,
                        'message' => 'Gói ưu đãi 1 năm giảm 30% sinh viên của bạn đã hết hạn (kết thúc vào ' . Carbon::parse($authUser->student_discount_expires_at)->format('d/m/Y') . ')! Vui lòng nâng cấp gói mới để tiếp tục nhận ưu đãi.'
                    ];
                }
            }
        }

        // 7. KIỂM TRA MÃ VÉ 0 ĐỒNG SINH VIÊN (SINHVIEN0DONG, SINHVIEN100)
        if (($voucher->discount_type === 'PERCENT' && (float)$voucher->discount_value >= 100) || in_array($voucher->code, ['SINHVIEN0DONG', 'SINHVIEN100', 'SV0DONG'])) {
            if (!$isStudent) {
                return [
                    'valid' => false,
                    'message' => 'Mã vé 0đ dành riêng cho Sinh viên các trường đại học (.edu.vn)!'
                ];
            }
            $seatCount = count($seatNumbers);
            if ($seatCount > 1) {
                return [
                    'valid' => false,
                    'message' => 'Mã vé chuyến đầu tiên chỉ áp dụng cho tối đa 1 ghế. Quý khách vui lòng chọn đúng 1 ghế!'
                ];
            }
            if ($authUser) {
                $hasUsedFirstTrip = (bool)$authUser->student_first_trip_used || Booking::where('user_id', $authUser->id)
                    ->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                    ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                    ->exists();

                if ($hasUsedFirstTrip) {
                    return [
                        'valid' => false,
                        'message' => 'Bạn đã sử dụng gói miễn phí 100% chuyến đầu tiên rồi. Vui lòng sử dụng mã SINHVIEN30 (Giảm 30%) hoặc SINHVIEN10 (Giảm 10%) cho chuyến đi này!'
                    ];
                }
            }
        }

        // 8. KIỂM TRA KHOẢNG GIÁ GHẾ (min_seat_price & max_seat_price)
        $minSeat = (float)($voucher->min_seat_price ?? 0);
        $maxSeat = $voucher->max_seat_price !== null ? (float)$voucher->max_seat_price : null;
        if (($minSeat > 0 || $maxSeat !== null) && $trip && !empty($seatNumbers)) {
            foreach ($seatNumbers as $sNum) {
                $isFloor2 = str_starts_with($sNum, 'B');
                $seatRawFare = $isFloor2 ? (float)($trip->floor2_price ?? round($trip->base_price * 0.95)) : (float)($trip->floor1_price ?? $trip->base_price);
                if ($seatRawFare < $minSeat || ($maxSeat !== null && $seatRawFare > $maxSeat)) {
                    $rangeLabel = number_format($minSeat) . " VNĐ" . ($maxSeat ? " đến " . number_format($maxSeat) . " VNĐ" : " trở lên");
                    return [
                        'valid' => false,
                        'message' => "Mã [{$voucher->code}] chỉ áp dụng cho ghế có giá từ {$rangeLabel}! Ghế {$sNum} có giá " . number_format($seatRawFare) . " VNĐ không nằm trong khoảng giá áp dụng."
                    ];
                }
            }
        }

        // 9. KIỂM TRA ĐƠN HÀNG TỐI THIỂU
        if ($voucher->min_order_value && $amount < $voucher->min_order_value) {
            return [
                'valid' => false,
                'message' => "Mã [{$voucher->code}] chỉ áp dụng cho đơn vé từ " . number_format($voucher->min_order_value) . " VNĐ trở lên! (Hiện tại: " . number_format($amount) . " VNĐ)"
            ];
        }

        // 10. KIỂM TRA GIỚI HẠN SỬ DỤNG / TÀI KHOẢN
        $maxUsesPerUser = $voucher->max_uses_per_user ?: 2;
        if ($authUser) {
            $usedByUserCount = Booking::where('user_id', $authUser->id)
                ->where(function ($q) use ($voucher, $code) {
                    $q->where('voucher_code', $voucher->code)->orWhere('voucher_code', $code);
                })
                ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                ->count();

            if ($usedByUserCount >= $maxUsesPerUser) {
                return [
                    'valid' => false,
                    'message' => "Tài khoản của bạn đã sử dụng hết số lần cho phép của mã [{$voucher->code}] ({$maxUsesPerUser} lần/tài khoản)!"
                ];
            }
        }

        // Tính toán chiết khấu
        $totalSeats = max(1, count($seatNumbers));
        $discountAmount = 0;
        $discountPerSeat = 0;

        // Trường hợp vé 0đ chuyến đầu
        if (($voucher->discount_type === 'PERCENT' && (float)$voucher->discount_value >= 100) || in_array($voucher->code, ['SINHVIEN0DONG', 'SINHVIEN100', 'SV0DONG'])) {
            $discountAmount = min($amount, 350000);
            $discountPerSeat = $discountAmount;
            return [
                'valid' => true,
                'voucher' => $voucher,
                'code' => $voucher->code,
                'target_audience' => $voucher->target_audience,
                'discount_type' => 'FIXED',
                'discount_value' => $discountAmount,
                'discount_amount' => $discountAmount,
                'discount_per_seat' => $discountPerSeat,
                'is_student_first_trip' => true,
                'description' => 'Ưu đãi Sinh Viên: Giảm 100% chuyến đầu tiên (tối đa 350k)',
                'message' => "🎉 Chúc mừng Sinh viên! Chuyến xe đầu tiên được giảm 100% tối đa 350.000 VNĐ!",
            ];
        }

        // Tính theo % hoặc số tiền cố định
        if ($voucher->discount_type === 'PERCENT') {
            $percent = (float)$voucher->discount_value;
            if (in_array($voucher->code, ['SINHVIEN', 'SINHVIEN30', 'SVVN']) && $authUser && $authUser->membership_discount_percent) {
                $percent = (int)$authUser->membership_discount_percent;
            }
            $discountAmount = round($amount * ($percent / 100));
            if ($voucher->max_discount && $discountAmount > $voucher->max_discount) {
                $discountAmount = (float)$voucher->max_discount;
            }
            $discountPerSeat = round($discountAmount / $totalSeats);
        } else {
            $discountAmount = min($amount, (float)$voucher->discount_value);
            $discountPerSeat = round($discountAmount / $totalSeats);
        }

        $audienceLabel = match ($voucher->target_audience) {
            'STUDENT' => 'Sinh Viên',
            'INDIVIDUAL' => 'Cá Nhân',
            default => 'Tất Cả Khách Hàng',
        };

        return [
            'valid' => true,
            'voucher' => $voucher,
            'code' => $voucher->code,
            'target_audience' => $voucher->target_audience,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float)$voucher->discount_value,
            'discount_amount' => $discountAmount,
            'discount_per_seat' => $discountPerSeat,
            'description' => $voucher->description,
            'message' => "Áp dụng thành công mã {$voucher->code} (Dành cho {$audienceLabel})! Giảm -" . number_format($discountAmount) . " VNĐ",
        ];
    }

    /**
     * Xác thực và tính giá chiết khấu Voucher trực tiếp từ Database
     */
    public function applyVoucher(Request $request)
    {
        $inputCode = $request->input('code', '');
        $amount = (float)$request->input('amount', 0);
        $tripId = $request->input('trip_id');
        $trip = $tripId ? Trip::find($tripId) : null;
        $seatNumbers = (array)$request->input('seat_numbers', []);
        $email = $request->input('email', '');

        $result = $this->checkVoucherEligibility($inputCode, Auth::user(), $email, $amount, $seatNumbers, $trip);
        if (!$result['valid']) {
            return response()->json($result, 422);
        }
        return response()->json($result);
    }

    /**
     * Nâng cấp / Gia hạn gói thành viên (Eco 20%, Pro 25%, Ultra 40%)
     * Cho cả Sinh viên (trợ giá) và Người thường (chuẩn)
     */
    public function subscribeMembership(Request $request)
    {
        $request->validate([
            'plan' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để nâng cấp gói thành viên!'], 401);
            }
            return back()->withErrors(['auth' => 'Vui lòng đăng nhập để nâng cấp gói thành viên!']);
        }

        $rawPlan = strtoupper(trim($request->input('plan')));
        $plan = str_contains($rawPlan, 'ULTRA') ? 'ULTRA' : 'PRO';
        $isStudent = (bool)($user->is_student || str_contains(strtolower($user->email ?? ''), '.edu.vn') || str_contains(strtolower($user->email ?? ''), 'sinhvien'));

        $discountPercent = $plan === 'ULTRA' ? 40 : 30;
        $planName = $plan === 'ULTRA' ? 'Gói ULTRA (Giảm 40%)' : ($isStudent ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)');
        $voucherCode = $plan === 'ULTRA' ? 'ULTRA40' : ($isStudent ? 'SINHVIEN30' : 'PRO30');
        $price = $plan === 'ULTRA' ? ($isStudent ? 239000 : 599000) : ($isStudent ? 0 : 349000);

        $user->membership_plan = $plan;
        $user->membership_discount_percent = $discountPercent;
        $user->student_registered_at = $user->student_registered_at ?? now();
        $user->membership_expires_at = now()->addYear();
        $user->student_discount_expires_at = now()->addYear();
        $user->save();

        try {
            \Illuminate\Support\Facades\DB::table('membership_subscriptions')->insert([
                'user_id' => $user->id,
                'plan' => $plan,
                'discount_percent' => $discountPercent,
                'price' => $price,
                'duration_months' => 12,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'payment_status' => 'COMPLETED',
                'payment_method' => 'VIETQR_MBBANK',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        try {
            \App\Services\PdfTicketService::saveMembershipInvoiceToPdfFolder(
                $user,
                $planName,
                $price,
                $voucherCode
            );
        } catch (\Throwable $e) {}

        $roleText = $isStudent ? 'Sinh viên' : 'Hội viên';
        $successMsg = "🎉 Chúc mừng {$user->name}! Bạn đã kích hoạt thành công {$planName} dành cho {$roleText}! Giảm ngay {$discountPercent}% toàn bộ vé xe suốt 365 ngày.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'plan' => $plan,
                'plan_name' => $planName,
                'voucher_code' => $voucherCode,
                'discount_percent' => $discountPercent,
                'registered_at' => now()->format('d/m/Y'),
                'expires_at' => now()->addYear()->format('d/m/Y'),
                'days_remaining' => 365,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Tự động kiểm tra trạng thái thanh toán gói hội viên qua VietQR MBBank
     */
    public function checkMembershipPayment(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['paid' => false, 'message' => 'Unauthenticated'], 401);
        }

        $rawPlan = strtoupper(trim($request->input('plan', 'PRO')));
        $plan = str_contains($rawPlan, 'ULTRA') ? 'ULTRA' : 'PRO';
        $discountPercent = $plan === 'ULTRA' ? 40 : 30;
        $isStudent = (bool)($user->is_student || str_contains(strtolower($user->email ?? ''), '.edu.vn') || str_contains(strtolower($user->email ?? ''), 'sinhvien'));
        $voucherCode = $plan === 'ULTRA' ? 'ULTRA40' : ($isStudent ? 'SINHVIEN30' : 'PRO30');
        $planName = $plan === 'ULTRA' ? 'Gói ULTRA (Giảm 40%)' : ($isStudent ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)');
        $isCancelled = strtoupper((string)($user->membership_plan ?? '')) === 'CANCELLED';
        $isRebuy = $isCancelled;
        $price = $plan === 'ULTRA' 
            ? ($isStudent ? 239000 : 599000) 
            : ($isStudent ? ($isRebuy ? 149000 : 0) : 349000);

        $isPaid = false;

        // 0. Nếu gói này đã được Webhook hoặc giao dịch trước đó kích hoạt
        if ($user->membership_plan === $plan && (int)$user->membership_discount_percent === $discountPercent) {
            $isPaid = true;
        }

        // 1. Kiểm tra SePay API đọc biến động số dư thực tế từ tài khoản MBBank 09025042004
        $sepayToken = env('SEPAY_API_TOKEN');
        if (!empty($sepayToken) && !$isPaid) {
            try {
                $accNo = env('VIETQR_ACCOUNT_NO', '09025042004');
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders(['Authorization' => 'Apikey ' . $sepayToken])
                    ->get('https://my.sepay.vn/userapi/transactions/list', [
                        'account_number' => $accNo,
                        'limit' => 20,
                    ]);
                if ($response->successful()) {
                    $transactions = $response->json('transactions') ?? [];
                    foreach ($transactions as $tx) {
                        $txContent = strtoupper($tx['transaction_content'] ?? '');
                        if (str_contains($txContent, 'HOIVIEN') && str_contains($txContent, (string)$user->id)) {
                            $isPaid = true;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Chỉ xác nhận thanh toán khi thực sự có Webhook tiền vào MBBank 09025042004 hoặc SePay API ghi nhận
        if (!$isPaid) {
            return response()->json([
                'paid' => false,
                'message' => 'Hệ thống đang chờ giao dịch chuyển khoản từ MBBank...',
            ]);
        }

        // Kích hoạt gói hội viên tự động
        $user->membership_plan = $plan;
        $user->membership_discount_percent = $discountPercent;
        $user->student_registered_at = $user->student_registered_at ?? now();
        $user->membership_expires_at = now()->addYear();
        $user->student_discount_expires_at = now()->addYear();
        $user->save();

        try {
            \Illuminate\Support\Facades\DB::table('membership_subscriptions')->insert([
                'user_id' => $user->id,
                'plan' => $plan,
                'discount_percent' => $discountPercent,
                'price' => $price,
                'duration_months' => 12,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'payment_status' => 'COMPLETED',
                'payment_method' => 'VIETQR_MBBANK',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'paid' => true,
            'plan' => $plan,
            'plan_name' => $planName,
            'voucher_code' => $voucherCode,
            'discount_percent' => $discountPercent,
            'registered_at' => $user->student_registered_at ? $user->student_registered_at->format('d/m/Y') : now()->format('d/m/Y'),
            'expires_at' => $user->membership_expires_at ? $user->membership_expires_at->format('d/m/Y') : now()->addYear()->format('d/m/Y'),
            'days_remaining' => 365,
            'message' => "Hệ thống MBBank đã tự động xác nhận chuyển khoản thành công! {$planName} đã được kích hoạt.",
        ]);
    }

    /**
     * Hủy gói hội viên đang sử dụng để cho phép mua gói khác
     */
    public function cancelMembership(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập'], 401);
        }

        try {
            // Hủy gói hội viên, đặt lại về trạng thái CANCELLED
            $user->membership_plan = 'CANCELLED';
            $user->membership_discount_percent = 0;
            $user->membership_expires_at = null;
            $user->student_discount_expires_at = null;
            $user->save();

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('membership_subscriptions')) {
                    \Illuminate\Support\Facades\DB::table('membership_subscriptions')
                        ->where('user_id', $user->id)
                        ->where('payment_status', 'COMPLETED')
                        ->update(['payment_status' => 'CANCELLED', 'updated_at' => now()]);
                }
            } catch (\Throwable $subEx) {}

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy gói dịch vụ thành công! Bây giờ bạn có thể chọn và đăng ký gói mới.',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[CANCEL_MEMBERSHIP_ERR] ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy gói: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Diagnostic Route (100% Cacheable)
     */
    public function debugCheck()
    {
        $results = [
            'php_version' => PHP_VERSION,
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'db_connection' => config('database.default'),
        ];
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $results['db_status'] = 'CONNECTED';
        } catch (\Throwable $e) {
            $results['db_status'] = 'FAILED: ' . $e->getMessage();
        }
        return response()->json($results);
    }
}
