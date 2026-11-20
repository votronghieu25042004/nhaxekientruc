<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Chỉ mục cho bảng chuyến xe (Trips) - tăng tốc tìm kiếm chuyến và lọc theo ngày/trạng thái
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                try {
                    $table->index(['departure_time', 'status'], 'idx_trips_dep_status');
                } catch (\Throwable $e) {}
                try {
                    $table->index('route_id', 'idx_trips_route_id');
                } catch (\Throwable $e) {}
                try {
                    $table->index('status', 'idx_trips_status');
                } catch (\Throwable $e) {}
            });
        }

        // 2. Chỉ mục cho bảng đặt vé (Bookings) - tăng tốc tra cứu theo SĐT, mã vé, chuyến và trạng thái thanh toán
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                try {
                    $table->index(['customer_phone'], 'idx_bookings_phone');
                } catch (\Throwable $e) {}
                try {
                    $table->index(['trip_id', 'payment_status'], 'idx_bookings_trip_pay');
                } catch (\Throwable $e) {}
                try {
                    $table->index(['created_at'], 'idx_bookings_created_at');
                } catch (\Throwable $e) {}
            });
        }

        // 3. Chỉ mục cho bảng ghế đặt (Booking Seats) - tăng tốc vẽ sơ đồ ghế và kiểm tra ghế trống
        if (Schema::hasTable('booking_seats')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                try {
                    $table->index(['trip_id', 'seat_number'], 'idx_booking_seats_trip_seat');
                } catch (\Throwable $e) {}
            });
        }

        // 4. Chỉ mục cho trạm dừng tuyến đường (Route Stops)
        if (Schema::hasTable('route_stops')) {
            Schema::table('route_stops', function (Blueprint $table) {
                try {
                    $table->index(['route_id', 'stop_order'], 'idx_route_stops_order');
                } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        // Dropping performance indexes safely
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                try { $table->dropIndex('idx_trips_dep_status'); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_trips_route_id'); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_trips_status'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                try { $table->dropIndex('idx_bookings_phone'); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_bookings_trip_pay'); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_bookings_created_at'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('booking_seats')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                try { $table->dropIndex('idx_booking_seats_trip_seat'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('route_stops')) {
            Schema::table('route_stops', function (Blueprint $table) {
                try { $table->dropIndex('idx_route_stops_order'); } catch (\Throwable $e) {}
            });
        }
    }
};
