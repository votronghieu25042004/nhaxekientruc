<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Seat;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Driver;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BusApiController extends Controller
{
    // 1. API Lấy danh sách điểm đi và điểm đến
    public function getLocations()
    {
        $origins = Route::select('origin')->distinct()->pluck('origin');
        $destinations = Route::select('destination')->distinct()->pluck('destination');

        return response()->json([
            'success' => true,
            'data' => [
                'origins' => $origins,
                'destinations' => $destinations,
            ]
        ]);
    }

    // 2. API Lấy danh sách tuyến đường
    public function getRoutes()
    {
        $routes = Route::with(['stops' => fn($q) => $q->orderBy('stop_order', 'asc')])->withCount('trips')->get();
        return response()->json([
            'success' => true,
            'data' => $routes,
        ]);
    }

    // 3. API Tìm kiếm Chuyến xe FUTA (có lọc điểm đi, điểm đến, ngày)
    public function getTrips(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $date = $request->input('date');
        $busType = $request->input('bus_type');

        $query = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver'])
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED', 'CLOSED']);

        if ($date) {
            $query->whereDate('departure_time', $date);
        }

        if ($from) {
            $query->where(function ($sub) use ($from) {
                $sub->whereHas('route', fn($q) => $q->where('origin', 'like', "%{$from}%")->orWhere('name', 'like', "%{$from}%"))
                    ->orWhereHas('route.stops', fn($q) => $q->where('stop_name', 'like', "%{$from}%"));
            });
        }
        if ($to) {
            $query->where(function ($sub) use ($to) {
                $sub->whereHas('route', fn($q) => $q->where('destination', 'like', "%{$to}%")->orWhere('name', 'like', "%{$to}%"))
                    ->orWhereHas('route.stops', fn($q) => $q->where('stop_name', 'like', "%{$to}%"));
            });
        }
        if ($busType) {
            $query->whereHas('bus', fn($q) => $q->where('bus_type', 'like', "%{$busType}%"));
        }

        $trips = $query->orderBy('departure_time', 'asc')->get()->map(function ($trip) {
            $basePrice = (float) ($trip->base_price ?? 180000);
            $totalSeats = $trip->bus?->total_seats ?? 36;
            $bookedSeats = $trip->bookings()->whereNotIn('checkin_status', ['CANCELLED'])->sum('total_seats') ?: 0;

            return [
                'id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'route_name' => $trip->route?->name,
                'from_location' => $trip->route?->origin,
                'to_location' => $trip->route?->destination,
                'departure_time' => Carbon::parse($trip->departure_time)->format('H:i'),
                'departure_date' => Carbon::parse($trip->departure_time)->format('d/m/Y'),
                'arrival_time' => $trip->arrival_time ? Carbon::parse($trip->arrival_time)->format('H:i') : null,
                'status' => $trip->status,
                'floor1_price' => (float) ($trip->floor1_price ?? $basePrice),
                'floor2_price' => (float) ($trip->floor2_price ?? round($basePrice * 0.95)),
                'deposit_30_percent' => (float) round(($trip->floor1_price ?? $basePrice) * 0.3),
                'bus_type' => $trip->bus?->bus_type ?? 'Giường Nằm VIP',
                'license_plate' => $trip->bus?->license_plate ?? '43B-012.34',
                'driver' => $trip->driver ? [
                    'id' => $trip->driver->id,
                    'name' => $trip->driver->name,
                    'phone' => $trip->driver->phone,
                    'rating' => (float) $trip->driver->avg_rating,
                ] : null,
                'available_seats' => max(1, $totalSeats - $bookedSeats),
                'total_seats' => $totalSeats,
                'stops' => $trip->route?->stops ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $trips->count(),
            'data' => $trips,
        ]);
    }

    // 4. API Chi tiết chuyến xe & Sơ đồ ghế
    public function getTripDetail($id)
    {
        $trip = Trip::with(['route.stops' => fn($q) => $q->orderBy('stop_order', 'asc'), 'bus', 'driver', 'bookings.bookingSeats'])->findOrFail($id);

        $basePrice = (float) ($trip->base_price ?? 180000);
        $floor1Price = (float) ($trip->floor1_price ?? $basePrice);
        $floor2Price = (float) ($trip->floor2_price ?? round($basePrice * 0.95));

        // Get booked seat numbers
        $bookedSeats = [];
        foreach ($trip->bookings as $b) {
            if ($b->checkin_status !== 'CANCELLED') {
                foreach ($b->bookingSeats as $bs) {
                    $bookedSeats[] = $bs->seat_number;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'route_name' => $trip->route?->name,
                'from' => $trip->route?->origin,
                'to' => $trip->route?->destination,
                'departure_time' => Carbon::parse($trip->departure_time)->format('H:i - d/m/Y'),
                'arrival_time' => $trip->arrival_time ? Carbon::parse($trip->arrival_time)->format('H:i - d/m/Y') : null,
                'floor1_price' => $floor1Price,
                'floor2_price' => $floor2Price,
                'floor1_seat_type' => $trip->floor1_seat_type ?? 'Giường Nằm VIP (Tầng 1)',
                'floor2_seat_type' => $trip->floor2_seat_type ?? 'Giường Nằm Tiêu Chuẩn (Tầng 2)',
                'status' => $trip->status,
                'bus' => [
                    'license_plate' => $trip->bus?->license_plate,
                    'bus_type' => $trip->bus?->bus_type,
                    'total_seats' => $trip->bus?->total_seats ?? 36,
                ],
                'driver' => $trip->driver,
                'booked_seats' => $bookedSeats,
                'stops' => $trip->route?->stops ?? [],
            ]
        ]);
    }

    // 5. API Đặt vé & Tính cọc 30%
    public function createBooking(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'required|email|max:255',
            'seat_numbers' => 'required|array|min:1',
            'seat_numbers.*' => 'string',
            'pickup_stop_name' => 'nullable|string',
            'dropoff_stop_name' => 'nullable|string',
            'payment_choice' => 'nullable|in:FULL,DEPOSIT',
            'has_insurance' => 'nullable|boolean',
        ]);

        $trip = Trip::with(['route.stops', 'bus'])->findOrFail($validated['trip_id']);
        
        // Calculate amount
        $insuranceFee = !empty($validated['has_insurance']) ? (count($validated['seat_numbers']) * 10000) : 0;
        $seatPrice = (float) ($trip->base_price ?? 180000);
        $totalAmount = 0;
        foreach ($validated['seat_numbers'] as $sNum) {
            $isFloor2 = str_starts_with($sNum, 'B');
            $totalAmount += $isFloor2 ? (float)($trip->floor2_price ?? round($seatPrice * 0.95)) : (float)($trip->floor1_price ?? $seatPrice);
        }
        $totalAmount += $insuranceFee;

        $isFull = (($validated['payment_choice'] ?? 'DEPOSIT') === 'FULL');
        $paidAmount = $isFull ? $totalAmount : round($totalAmount * 0.3);
        $paymentStatus = $isFull ? 'PAID' : 'PARTIALLY_PAID';

        $bookingCode = 'FUTA-' . strtoupper(Str::random(5));
        $qrToken = 'QR-' . Str::uuid()->toString();

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'trip_id' => $trip->id,
            'booking_source' => 'API',
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'total_seats' => count($validated['seat_numbers']),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'payment_method' => 'ONLINE',
            'checkin_status' => 'PENDING',
            'qr_token' => $qrToken,
            'notes' => "Đón: " . ($validated['pickup_stop_name'] ?? $trip->route?->origin) . " | Trả: " . ($validated['dropoff_stop_name'] ?? $trip->route?->destination),
        ]);

        foreach ($validated['seat_numbers'] as $sNum) {
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

        return response()->json([
            'success' => true,
            'message' => 'Đặt vé thành công!',
            'data' => [
                'booking_code' => $booking->booking_code,
                'qr_token' => $booking->qr_token,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $totalAmount - $paidAmount,
                'seats' => $validated['seat_numbers'],
            ]
        ]);
    }

    // 6. API Tra cứu vé & QR Token
    public function getTicket($code)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'bookingSeats', 'review'])
            ->where('booking_code', $code)
            ->orWhere('qr_token', $code)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'seats' => $booking->bookingSeats->pluck('seat_number'),
                'total_amount' => (float) $booking->total_amount,
                'paid_amount' => (float) $booking->paid_amount,
                'remaining_amount' => (float) ($booking->total_amount - $booking->paid_amount),
                'payment_status' => $booking->payment_status,
                'checkin_status' => $booking->checkin_status,
                'qr_token' => $booking->qr_token,
                'trip' => [
                    'trip_code' => $booking->trip->trip_code,
                    'departure_time' => Carbon::parse($booking->trip->departure_time)->format('H:i - d/m/Y'),
                    'from' => $booking->trip->route?->origin,
                    'to' => $booking->trip->route?->destination,
                    'driver' => $booking->trip->driver ? $booking->trip->driver->name : 'Đang điều phối',
                    'license_plate' => $booking->trip->bus?->license_plate,
                ],
                'review' => $booking->review,
            ]
        ]);
    }

    // 7. API Danh sách Tài xế & Đánh giá năng lực
    public function getDrivers()
    {
        $drivers = Driver::with('reviews')->withCount('trips')->get();
        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }
}
