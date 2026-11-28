<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\RouteStop;
use App\Models\RouteFare;
use App\Models\BookingSeat;

class SeatSegmentService
{
    /**
     * Tự động giải phóng ghế cho các đơn đặt vé giữ chỗ tạm thời (UNPAID) đã quá 5 phút
     * Áp dụng tự động, đồng bộ cho cả khách tự đặt ngoài website lẫn khách nhờ Trợ lý AI khóa ghế
     */
    public static function autoReleaseExpiredHolds(): void
    {
        try {
            $expiredThreshold = \Carbon\Carbon::now()->subMinutes(5);
            $expiredBookings = \App\Models\Booking::where('payment_status', 'UNPAID')
                ->whereNotIn('checkin_status', ['CANCELLED', 'NO_SHOW', 'REFUNDED'])
                ->where('created_at', '<', $expiredThreshold)
                ->get();

            foreach ($expiredBookings as $bk) {
                $bk->update([
                    'payment_status' => 'CANCELLED',
                    'checkin_status' => 'CANCELLED',
                    'notes' => trim(($bk->notes ?? '') . ' | [Hết hạn giữ chỗ 5 phút - Hệ thống tự động giải phóng ghế]')
                ]);
                // Giải phóng các ghế đã gán trong đơn
                \App\Models\BookingSeat::where('booking_id', $bk->id)->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Error in autoReleaseExpiredHolds: " . $e->getMessage());
        }
    }

    /**
     * Lấy toàn bộ danh sách ghế đã được đặt / chiếm trên chuyến xe
     * Đồng bộ chuẩn xác 100% cho cả 4 phân hệ: Khách Hàng, Admin, Lơ Xe, Tài Xế
     * Chỉ giải phóng khi vé bị HỦY (CANCELLED) hoặc ĐÃ HOÀN TIỀN (REFUNDED)
     * Đơn giữ chỗ chưa thanh toán (UNPAID) quá 5 phút sẽ tự động bị hủy và giải phóng ghế ngay lập tức.
     */
    public static function getAllBookedSeatsForTrip(int $tripId): array
    {
        self::autoReleaseExpiredHolds();

        $occupiedSeats = BookingSeat::where('trip_id', $tripId)
            ->whereHas('booking', function ($query) {
                $query->whereNotIn('checkin_status', ['CANCELLED', 'NO_SHOW'])
                      ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                      ->where(function ($sub) {
                          $sub->where('payment_status', '!=', 'UNPAID')
                              ->orWhere('created_at', '>=', \Carbon\Carbon::now()->subMinutes(5));
                      });
            })
            ->pluck('seat_number')
            ->toArray();

        return array_values(array_unique($occupiedSeats));
    }

    /**
     * Lấy danh sách các ghế bị trùng/chiếm trên đoạn đường từ pickupOrder đến dropoffOrder
     * Bỏ qua các vé đã bị Hủy hoặc Hoàn tiền (CANCELLED, REFUNDED) hoặc Bỏ chuyến (NO_SHOW)
     * Đơn giữ chỗ chưa thanh toán (UNPAID) quá 5 phút sẽ tự động bị hủy và giải phóng ghế.
     */
    public static function getOccupiedSeats(int $tripId, int $pickupOrder, int $dropoffOrder): array
    {
        self::autoReleaseExpiredHolds();

        if ($pickupOrder === 0 && $dropoffOrder >= 90) {
            return self::getAllBookedSeatsForTrip($tripId);
        }

        $occupiedSeats = BookingSeat::where('trip_id', $tripId)
            ->whereHas('booking', function ($query) {
                $query->whereNotIn('checkin_status', ['CANCELLED', 'NO_SHOW'])
                      ->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
                      ->where(function ($sub) {
                          $sub->where('payment_status', '!=', 'UNPAID')
                              ->orWhere('created_at', '>=', \Carbon\Carbon::now()->subMinutes(5));
                      });
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
