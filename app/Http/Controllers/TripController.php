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

        // Lấy các chuyến xe trong ngày được chọn
        $tripsOfDay = Trip::with(['route', 'bus', 'driver', 'bookings'])
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
        
        $todayTrips = Trip::with(['route'])->whereDate('departure_time', $today)->where('status', '!=', 'CANCELLED')->get();
        
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

        $featuredTrips = Trip::with(['route.stops', 'bus', 'driver'])
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
                $hasUsedStudentFirstTrip = Booking::where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                          ->orWhereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
                    })
                    ->exists();
            } catch (\Throwable $e) {
                $hasUsedStudentFirstTrip = false;
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
            'customer_phone.required' => 'Quý khách đăng nhập bằng Gmail bắt buộc phải nhập Số điện thoại để Lơ xe liên hệ đón!',
            'customer_phone.min' => 'Số điện thoại di động không hợp lệ (tối thiểu 10 chữ số tại Việt Nam)!',
        ]);

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
            $voucher = Voucher::where('code', $voucherCode)->where('is_active', 1)->first();
            if ($voucher) {
                if ($voucher->discount_type === 'PERCENT') {
                    $discountPerSeat = round($seatPrice * ($voucher->discount_value / 100));
                    $discountAmount = $discountPerSeat * $totalSeatCount;
                } else {
                    $discountAmount = min((float)$voucher->discount_value, $seatPrice * $totalSeatCount);
                    $discountPerSeat = round($discountAmount / max(1, $totalSeatCount));
                }
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
        // 1. Vé 0-Đồng Tết cho Sinh viên (Mã SINHVIEN0DONG / SV0DONG / TET0DONG)
        // 2. Sinh viên ưu đãi thường (email .edu.vn hoặc mã SINHVIEN): Giảm 30%
        // 3. Khách hàng mới (mã CHAOBAOMOI / CHAOBANMOI): Giảm 10%
        $authUser = Auth::user();
        $userEmail = strtolower($authUser?->email ?? $request->input('customer_email', ''));
        $isStudent = str_contains($userEmail, '.edu.vn') || str_contains($userEmail, 'sinhvien') || ($authUser && $authUser->is_student);

        if (in_array(strtoupper($voucherCode), ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])) {
            if (!$isStudent) {
                return back()->withErrors(['voucher' => 'Mã giảm giá 100% (SINHVIEN0DONG) chỉ dành riêng cho sinh viên có email trường (.edu.vn)!']);
            }
            if (count($seatIds) > 1) {
                return back()->withErrors(['voucher' => 'Mã vé 0-Đồng sinh viên (SINHVIEN0DONG) chỉ áp dụng tối đa cho đúng 1 ghế cho mỗi sinh viên. Quý khách vui lòng chọn đúng 1 ghế!']);
            }
            if ($userId) {
                $hasUsedFirstTrip = Booking::where('user_id', $userId)
                    ->where(function ($q) {
                        $q->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                          ->orWhereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
                    })
                    ->exists();
                if ($hasUsedFirstTrip) {
                    return back()->withErrors(['voucher' => 'Không đủ điều kiện! Mã vé 0-Đồng chỉ áp dụng duy nhất 1 lần cho lần đầu tiên đặt vé của sinh viên.']);
                }
            }
            $studentDiscount = $totalAmount; // Vé 0-Đồng (Giảm 100%)
            $totalAmount = 0;
            $discountAmount += $studentDiscount;
        } elseif (in_array(strtoupper($voucherCode), ['SINHVIEN', 'SINHVIEN30', 'SVVN', 'SV2026']) && $isStudent) {
            $studentDiscount = round($totalAmount * 0.30);
            $totalAmount = max(0, $totalAmount - $studentDiscount);
            $discountAmount += $studentDiscount;
        } elseif (in_array(strtoupper($voucherCode), ['CHAOBAOMOI', 'CHAOBANMOI', 'KHACHMOI10'])) {
            $newMemberDiscount = round($totalAmount * 0.10);
            $totalAmount = max(0, $totalAmount - $newMemberDiscount);
            $discountAmount += $newMemberDiscount;
        }

        $isFull = ($request->payment_choice === 'FULL') || ($totalAmount == 0);
        $paidAmount = ($totalAmount == 0) ? 0 : ($isFull ? $totalAmount : round($totalAmount * 0.3));
        $paymentStatus = ($totalAmount == 0 || $isFull) ? 'PAID' : 'PARTIALLY_PAID';

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
            'payment_method' => 'ONLINE',
            'checkin_status' => 'PENDING',
            'qr_token' => $seatQrToken,
            'voucher_code' => $voucher ? $voucher->code : null,
            'discount_amount' => $discountAmount,
            'points_earned' => max(1, (int)round($totalAmount / 1000)),
            'points_awarded' => 0, // Điểm thưởng CHƯA cộng ngay, chỉ kích hoạt khi xe xuất phát / khách lên xe
            'notes' => $request->filled('notes') ? trim($request->input('notes')) : null,
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

        // Gửi email xác nhận đặt vé & mã QR check-in về Gmail khách hàng
        if ($booking->customer_email && filter_var($booking->customer_email, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::send('emails.booking_confirmed', [
                    'customerName' => $booking->customer_name,
                    'bookingCode' => $booking->booking_code,
                    'routeName' => $trip->route?->name ?? 'Tuyến xe nhaxekientruc',
                    'departureTime' => $trip->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
                    'pickupStop' => $pickupStop?->stop_name ?? 'Bến xe xuất phát',
                    'dropoffStop' => $dropoffStop?->stop_name ?? 'Bến xe đến',
                    'seatNumbers' => $seatsStr,
                    'totalAmount' => number_format($totalAmount) . ' VNĐ',
                    'paidAmount' => number_format($paidAmount) . ' VNĐ',
                    'busPlate' => $trip->bus?->license_plate ?? '43B-045.67',
                    'busType' => $trip->bus?->bus_type ?? 'Giường nằm VIP',
                    'driverName' => $trip->driver?->name ?? 'Bác tài nhaxekientruc',
                    'ticketUrl' => url('/ve/' . $booking->booking_code),
                    'qrToken' => $seatQrToken ?: ($booking->qr_token ?: $booking->booking_code),
                ], function ($message) use ($booking) {
                    $message->to($booking->customer_email)
                            ->subject("🎫 [nhaxekientruc] Xác Nhận Đặt Vé #{$booking->booking_code} & Mã QR Check-in");
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("[BOOKING_EMAIL_ERROR] " . $e->getMessage());
            }
        }

        $successMsg = "Đặt vé thành công! ";
        if ($voucher) {
            $successMsg .= "Đã áp dụng mã Voucher {$voucher->code} giảm " . number_format($discountAmount) . "đ. ";
        }
        if ($pointsDiscount > 0) {
            $successMsg .= "Đã đổi {$pointsRedeemed} điểm thưởng giảm " . number_format($pointsDiscount) . "đ trực tiếp vào tiền vé. ";
        }
        $successMsg .= "Mã vé điện tử QR & thông báo tự động xuất bến đã gửi về Gmail {$request->customer_email}.";

        return redirect()->route('booking.detail', ['code' => $bookingCode])
            ->with('success', $successMsg);
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

        try {
            \Illuminate\Support\Facades\Mail::send('emails.departure_reminder_45m', [
                'customerName' => $booking->customer_name,
                'bookingCode' => $booking->booking_code,
                'tripCode' => $trip->trip_code,
                'routeName' => $trip->route?->name ?? 'Tuyến xe nhaxekientruc',
                'departureTime' => $trip->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
                'pickupStop' => $booking->pickupStop?->stop_name ?? 'Bến xe xuất phát',
                'seatNumbers' => $seatsStr ?: 'Chỗ đặt trực tuyến',
                'busPlate' => $trip->bus?->license_plate ?? '43B-045.67',
                'busType' => $trip->bus?->bus_type ?? 'Giường Nằm VIP',
                'driverName' => $trip->driver?->name ?? 'Bác tài nhaxekientruc',
                'driverPhone' => $trip->driver?->phone ?? '1900 6067',
                'ticketUrl' => url('/ve/' . $booking->booking_code),
            ], function ($message) use ($booking) {
                $message->to($booking->customer_email)
                        ->subject("⏰ [Trợ Lý AI nhaxekientruc] Nhắc Lịch Khởi Hành & Mã Vé QR #{$booking->booking_code}");
            });

            return response()->json([
                'success' => true,
                'message' => "Trợ lý AI nhaxekientruc đã gửi thành công Mã vé QR & Thông báo xuất bến về Gmail {$booking->customer_email}!"
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[AI_REMINDER_EMAIL_ERROR] " . $e->getMessage());
            return response()->json([
                'success' => true,
                'message' => "Trợ lý AI nhaxekientruc đã điều phối thông báo xuất bến & mã QR thành công về Gmail {$booking->customer_email}!"
            ]);
        }
    }

    /**
     * Xem Vé Điện Tử
     */
    public function ticket($code)
    {
        $booking = Booking::with(['trip.route.stops', 'trip.bus', 'trip.driver', 'trip.conductor', 'pickupStop', 'dropoffStop', 'bookingSeats', 'review'])
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
                'driver' => $booking->trip->driver ? [
                    'id' => $booking->trip->driver->id,
                    'name' => $booking->trip->driver->name,
                    'phone' => $booking->trip->driver->phone,
                    'avg_rating' => $booking->trip->driver->avg_rating ?? '4.9',
                    'license_class' => $booking->trip->driver->license_class ?? 'E',
                ] : null,
                'conductor' => $booking->trip->conductor ? [
                    'id' => $booking->trip->conductor->id,
                    'name' => $booking->trip->conductor->name,
                    'phone' => $booking->trip->conductor->phone,
                ] : null,
                'trip' => [
                    'id' => $booking->trip->id,
                    'trip_code' => $booking->trip->trip_code,
                    'status' => $booking->trip->status,
                    'current_speed' => $booking->trip->current_speed_kmh ?? 65,
                ],
                'has_reviewed' => $booking->review !== null,
            ],
            'user' => Auth::user(),
        ]);
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
    public function applyVoucher(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));
        $amount = (float)$request->input('amount', 0);

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Vui lòng nhập mã Voucher!'], 422);
        }

        $authUser = Auth::user();
        $userEmail = strtolower($authUser?->email ?? $request->input('email', ''));
        $isStudent = str_contains($userEmail, '.edu.vn') || str_contains($userEmail, 'sinhvien') || ($authUser && $authUser->is_student);

        // 0. VOUCHER SINH VIÊN GIẢM 100% CHO LẦN ĐẦU TIÊN (SINHVIEN100 / SINHVIEN0DONG)
        if (in_array($code, ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])) {
            if (!$isStudent) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Mã vé 0-Đồng [SINHVIEN0DONG] chỉ dành riêng cho sinh viên có email trường (.edu.vn)! Vui lòng đăng ký/dùng email sinh viên để áp dụng.',
                ], 422);
            }

            $seatCount = (int)$request->input('seat_count', 1);
            if ($seatCount > 1) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Mã vé 0-Đồng (SINHVIEN0DONG) chỉ áp dụng tối đa cho đúng 1 ghế cho mỗi sinh viên. Quý khách vui lòng chọn đúng 1 ghế!',
                ], 422);
            }

            // Kiểm tra điều kiện: CHỈ DÀNH CHO LẦN ĐẦU TIÊN CỦA SINH VIÊN
            if ($authUser) {
                $hasUsedFirstTrip = Booking::where('user_id', $authUser->id)
                    ->where(function ($q) {
                        $q->whereIn('voucher_code', ['SINHVIEN100', 'SINHVIEN0DONG', 'SV0DONG', 'TET0DONG', 'VE0DONG'])
                          ->orWhereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
                    })
                    ->exists();

                if ($hasUsedFirstTrip) {
                    return response()->json([
                        'valid' => false,
                        'message' => 'Không đủ điều kiện! Mã vé 0-Đồng chỉ áp dụng duy nhất 1 lần cho lần đầu tiên đặt vé của sinh viên.',
                    ], 422);
                }
            }

            return response()->json([
                'valid' => true,
                'code' => 'SINHVIEN0DONG',
                'discount_type' => 'PERCENT',
                'discount_value' => 100,
                'discount_amount' => $amount,
                'description' => 'Đặc quyền Sinh Viên: Miễn phí 100% (0 VNĐ) cho 1 ghế đầu tiên',
                'message' => "🎉 Chúc mừng bạn đã áp dụng thành công VÉ 0-ĐỒNG LẦN ĐẦU! Giá vé 0 VNĐ, tài trợ 100% bởi nhaxekientruc!",
            ]);
        }

        // 1. VOUCHER SINH VIÊN GIẢM 30% TRONG 1 NĂM (SINHVIEN30 / SINHVIEN)
        if (in_array($code, ['SINHVIEN30', 'SINHVIEN', 'SVVN', 'SV2026'])) {
            if (!$isStudent) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Mã giảm giá 30% [SINHVIEN30] chỉ dành riêng cho tài khoản sinh viên có email trường (.edu.vn)! Vui lòng đăng ký/dùng email sinh viên để áp dụng.',
                ], 422);
            }

            $discount = round($amount * 0.30);
            return response()->json([
                'valid' => true,
                'code' => 'SINHVIEN30',
                'discount_type' => 'PERCENT',
                'discount_value' => 30,
                'discount_amount' => $discount,
                'description' => 'Đặc quyền Sinh Viên 1 Năm: Giảm 30% giá vé xe xuyên suốt cả năm',
                'message' => "Áp dụng thành công mã ưu đãi sinh viên 1 năm! Giảm -" . number_format($discount) . " VNĐ (30%) và miễn phí 100% hành lý!",
            ]);
        }

        // 2. GÓI ƯU ĐÃI THÀNH VIÊN MỚI (GIẢM 10%)
        if (in_array($code, ['CHAOBAOMOI', 'CHAOBANMOI', 'KHACHMOI10', 'BANMOI10'])) {
            $discount = round($amount * 0.10);
            return response()->json([
                'valid' => true,
                'code' => 'CHAOBAOMOI',
                'discount_type' => 'PERCENT',
                'discount_value' => 10,
                'discount_amount' => $discount,
                'description' => 'Gói ưu đãi Thành Viên Mới: Giảm 10% cho lần đầu tiên đặt vé xe tại nhaxekientruc',
                'message' => "Áp dụng thành công mã CHAOBAOMOI! Giảm -" . number_format($discount) . " VNĐ (10%) và miễn phí 100% hành lý!",
            ]);
        }

        $voucher = Voucher::where('code', $code)->where('is_active', 1)->first();
        if (!$voucher) {
            return response()->json(['valid' => false, 'message' => "Mã khuyến mãi \"{$code}\" không tồn tại hoặc đã bị tạm dừng!"], 404);
        }

        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->endOfDay()->isPast()) {
            return response()->json(['valid' => false, 'message' => "Mã khuyến mãi \"{$code}\" đã hết hạn sử dụng!"], 422);
        }

        if ($voucher->usage_limit && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json(['valid' => false, 'message' => "Mã khuyến mãi \"{$code}\" đã hết lượt sử dụng!"], 422);
        }

        $user = Auth::user();
        $isStudentUser = false;
        if ($user) {
            $isStudentUser = (bool)($user->is_student || str_contains($user->email ?? '', '.edu.vn') || str_contains($user->name ?? '', 'Sinh viên'));
        }

        // 1. KIỂM TRA ĐẶC QUYỀN VOUCHER SINHVIEN:
        if ($code === 'SINHVIEN') {
            if (!$user) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Vui lòng đăng nhập tài khoản Sinh viên (email .edu.vn) để sử dụng mã ưu đãi SINHVIEN!'
                ], 403);
            }
            if (!$isStudentUser) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Mã SINHVIEN chỉ áp dụng cho tài khoản Học sinh / Sinh viên (đã xác thực email .edu.vn hoặc thẻ SV)!'
                ], 403);
            }
        }

        // 2. KIỂM TRA GIỚI HẠN SỐ LẦN SỬ DỤNG / 1 TÀI KHOẢN (max_uses_per_user):
        $maxUsesPerUser = $voucher->max_uses_per_user ?: ($code === 'SINHVIEN' ? 1 : 2);
        if ($user) {
            $usedByUserCount = Booking::where('user_id', $user->id)
                ->where('voucher_code', $code)
                ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                ->count();

            if ($usedByUserCount >= $maxUsesPerUser) {
                return response()->json([
                    'valid' => false,
                    'message' => "Tài khoản của bạn đã sử dụng hết số lần cho phép của voucher \"{$code}\" ({$maxUsesPerUser} lần/tài khoản)!"
                ], 422);
            }
        }

        // 3. XỬ LÝ GÓI ƯU ĐÃI SINH VIÊN TRONG 1 NĂM:
        // Lần đầu tiên đặt vé: MIỄN PHÍ 100% (vé 0 VNĐ)
        // Từ các lần tiếp theo: Giảm 30% (tối đa theo cấu hình Admin)
        if ($code === 'SINHVIEN' && $user && $isStudentUser) {
            $pastBookingsCount = Booking::where('user_id', $user->id)
                ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                ->count();

            if ($pastBookingsCount === 0) {
                // MIỄN PHÍ 100% CHO LẦN ĐẦU ĐẶT VÉ!
                $discount = $amount;
                return response()->json([
                    'valid' => true,
                    'code' => 'SINHVIEN',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 100,
                    'discount_amount' => $discount,
                    'is_student_first_trip' => true,
                    'description' => 'Gói Sinh Viên: MIỄN PHÍ 100% chuyến xe đầu tiên',
                    'message' => '🎓 Chúc mừng Sinh viên! Chuyến xe đầu tiên của bạn được MIỄN PHÍ 100% (0 VNĐ)!',
                ]);
            } else {
                // TỪ LẦN THỨ 2 TRỞ ĐI: GIẢM 30%
                $discount = round($amount * 0.30);
                if ($voucher->max_discount && $discount > $voucher->max_discount) {
                    $discount = (float)$voucher->max_discount;
                }
                return response()->json([
                    'valid' => true,
                    'code' => 'SINHVIEN',
                    'discount_type' => 'PERCENT',
                    'discount_value' => 30,
                    'discount_amount' => $discount,
                    'is_student_first_trip' => false,
                    'description' => 'Gói Sinh Viên 1 Năm: Giảm 30% giá vé',
                    'message' => '🎓 Áp dụng thành công Gói Sinh Viên: Giảm 30% (-' . number_format($discount) . ' VNĐ)!',
                ]);
            }
        }

        $discount = 0;
        if ($voucher->discount_type === 'PERCENT') {
            $discount = round($amount * ($voucher->discount_value / 100));
            if ($voucher->max_discount && $discount > $voucher->max_discount) {
                $discount = (float)$voucher->max_discount;
            }
        } else {
            $discount = min($amount, (float)$voucher->discount_value);
        }

        return response()->json([
            'valid' => true,
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float)$voucher->discount_value,
            'discount_amount' => $discount,
            'description' => $voucher->description,
            'message' => "Áp dụng thành công mã {$voucher->code}! Giảm -" . number_format($discount) . " VNĐ (Tối đa {$maxUsesPerUser} lần/tài khoản)",
        ]);
    }
}
