<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\BookingSeat;

class SeatSegmentService
{
    /**
     * Lấy toàn bộ danh sách ghế đã được đặt / chiếm trên chuyến xe
     * Đồng bộ chuẩn xác 100% cho cả 4 phân hệ: Khách Hàng, Admin, Lơ Xe, Tài Xế
     * Chỉ giải phóng khi vé bị HỦY (CANCELLED) hoặc ĐÃ HOÀN TIỀN (REFUNDED)
     * Vé đang chờ hoàn tiền (PENDING) vẫn giữ nguyên ghế cho tới khi Admin duyệt.
     */
    public static function getAllBookedSeatsForTrip(int $tripId): array
    {
        $occupiedSeats = BookingSeat::where('trip_id', $tripId)
            ->whereHas('booking', function ($query) {
                $query->whereNotIn('checkin_status', ['CANCELLED'])
                      ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
            })
            ->pluck('seat_number')
            ->toArray();

        return array_values(array_unique($occupiedSeats));
    }

    /**
     * Lấy danh sách các ghế bị trùng/chiếm trên đoạn đường từ pickupOrder đến dropoffOrder
     * Bỏ qua các vé đã bị Hủy hoặc Hoàn tiền (CANCELLED, REFUNDED)
     */
    public static function getOccupiedSeats(int $tripId, int $pickupOrder, int $dropoffOrder): array
    {
        if ($pickupOrder === 0 && $dropoffOrder >= 90) {
            return self::getAllBookedSeatsForTrip($tripId);
        }

        $occupiedSeats = BookingSeat::where('trip_id', $tripId)
            ->whereHas('booking', function ($query) {
                $query->whereNotIn('checkin_status', ['CANCELLED'])
                      ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
            })
            ->where(function ($query) use ($pickupOrder, $dropoffOrder) {
                // Điều kiện giao nhau giữa 2 đoạn [P1, D1] và [P2, D2] hoặc toàn bộ chuyến
                $query->where(function ($sub) use ($pickupOrder, $dropoffOrder) {
                    $sub->where('pickup_order', '<', $dropoffOrder)
                        ->where('dropoff_order', '>', $pickupOrder);
                })
                ->orWhereNull('dropoff_order')
                ->orWhere('dropoff_order', '<=', 0);
            })
            ->pluck('seat_number')
            ->toArray();

        return array_values(array_unique($occupiedSeats));
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
