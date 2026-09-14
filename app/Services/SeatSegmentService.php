<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\BookingSeat;

class SeatSegmentService
{
    /**
     * Lấy danh sách các ghế bị trùng/chiếm trên đoạn đường từ pickupOrder đến dropoffOrder
     * Quy tắc Overlap: max(P1, P2) < min(D1, D2)
     */
    public static function getOccupiedSeats(int $tripId, int $pickupOrder, int $dropoffOrder): array
    {
        $occupiedSeats = BookingSeat::where('trip_id', $tripId)
            ->where(function ($query) use ($pickupOrder, $dropoffOrder) {
                // Điều kiện giao nhau giữa 2 đoạn [P1, D1] và [P2, D2]
                $query->where('pickup_order', '<', $dropoffOrder)
                      ->where('dropoff_order', '>', $pickupOrder);
            })
            ->pluck('seat_number')
            ->toArray();

        return array_unique($occupiedSeats);
    }

    /**
     * Tra cứu giá vé chuẩn theo cặp điểm đón và điểm trả
     */
    public static function getFare(int $routeId, int $pickupStopId, int $dropoffStopId, ?string $busType = null): float
    {
        $fare = RouteFare::where('route_id', $routeId)
            ->where('pickup_stop_id', $pickupStopId)
            ->where('dropoff_stop_id', $dropoffStopId)
            ->first();

        if ($fare) {
            return (float) $fare->fare_amount;
        }

        // Nếu chưa cấu hình giá cụ thể, tính theo khoảng cách trạm
        $pStop = RouteStop::find($pickupStopId);
        $dStop = RouteStop::find($dropoffStopId);

        if ($pStop && $dStop) {
            $dist = abs($dStop->distance_from_start_km - $pStop->distance_from_start_km);
            return max(50000, round($dist * 1200, -3));
        }

        return 180000;
    }
}
