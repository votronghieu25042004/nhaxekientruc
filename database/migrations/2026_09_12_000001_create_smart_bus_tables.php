<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drivers (Tài xế)
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('license_number');
            $table->string('license_class')->default('Hạng E');
            $table->integer('years_experience')->default(5);
            $table->string('avatar')->nullable();
            $table->decimal('avg_rating', 3, 1)->default(5.0);
            $table->integer('total_trips')->default(0);
            $table->string('status')->default('ACTIVE'); // ACTIVE, ON_LEAVE, SUSPENDED
            $table->text('notes')->nullable();
            $table->string('trip_direction')->default('Chuyến Đi');
            $table->string('audit_status')->default('PENDING_AUDIT');
            $table->text('audit_note')->nullable();
            $table->string('audited_by')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();
        });

        // 2. Buses (Đội xe)
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate')->unique();
            $table->string('bus_type'); // Limousine 34 Phòng VIP, Giường Nằm 40 Chỗ, Cung Điện 22 Phòng
            $table->integer('total_seats')->default(34);
            $table->integer('floors')->default(2);
            $table->json('amenities')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, RUNNING, WAITING, MAINTENANCE, BROKEN, SUSPENDED
            $table->integer('current_km')->default(120000);
            $table->text('maintenance_notes')->nullable();
            $table->timestamps();
        });

        // 3. Routes & Ordered Stops (Tuyến đường & Danh sách điểm dừng theo thứ tự)
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tuyến Đà Nẵng - Hội An, Đà Nẵng - Đông Hà
            $table->string('origin');
            $table->string('destination');
            $table->integer('distance_km')->default(175);
            $table->decimal('estimated_hours', 4, 1)->default(3.5);
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->string('stop_name'); // Đà Nẵng, Ngũ Hành Sơn, Điện Ngọc, Vĩnh Điện, Hội An
            $table->string('address')->nullable();
            $table->integer('stop_order')->default(0); // 0, 1, 2, 3, 4
            $table->integer('distance_from_start_km')->default(0);
            $table->integer('estimated_minutes_from_start')->default(0);
            $table->timestamps();
        });

        // 4. Segment Fares (Bảng giá theo từng cặp Điểm đón - Điểm trả)
        Schema::create('route_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->foreignId('dropoff_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->decimal('fare_amount', 12, 0);
            $table->string('bus_type')->nullable();
            $table->timestamps();
        });

        // 5. Trips (Chuyến xe thực tế độc lập)
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_code')->unique(); // FUTA-TRIP-001
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('conductor_id')->nullable()->constrained('users')->nullOnDelete(); // Lơ xe phụ trách
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
            $table->decimal('base_price', 12, 0)->default(150000);
            $table->decimal('floor1_price', 12, 0)->default(150000);
            $table->decimal('floor2_price', 12, 0)->default(140000);
            $table->string('floor1_seat_type')->default('Giường Nằm VIP (Tầng Dưới)');
            $table->string('floor2_seat_type')->default('Giường Nằm Tiêu Chuẩn (Tầng Trên)');
            $table->string('status')->default('SCHEDULED'); // SCHEDULED, OPEN_FOR_SALE, BOARDING, DEPARTED, IN_TRANSIT, ARRIVED, COMPLETED, CLOSED, CANCELLED
            $table->integer('current_stop_order')->default(0); // Vị trí hiện tại của xe (0: Bến đi, 1: Trạm 1...)
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->string('closed_by')->nullable();
            $table->timestamps();
        });

        // 6. Bookings (Universal Ticket Record)
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique(); // FUTA-XXXXX
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('booking_source')->default('ONLINE'); // ONLINE, STAFF, STAFF_ON_BUS, ADMIN
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->foreignId('pickup_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->foreignId('dropoff_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->integer('pickup_order')->default(0);
            $table->integer('dropoff_order')->default(1);
            $table->integer('total_seats')->default(1);
            $table->decimal('total_amount', 12, 0);
            $table->decimal('paid_amount', 12, 0)->default(0);
            $table->string('payment_status')->default('UNPAID'); // PAID, PARTIALLY_PAID, UNPAID, REFUNDED
            $table->string('payment_method')->default('CASH'); // CASH, TRANSFER, ONLINE, QR
            $table->string('checkin_status')->default('PENDING'); // PENDING, CHECKED_IN, NO_SHOW
            $table->string('checkin_method')->nullable(); // QR, MANUAL
            $table->dateTime('checked_in_at')->nullable();
            $table->string('checked_in_by')->nullable();
            $table->string('qr_token')->unique();
            $table->string('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('trip_direction')->default('Chuyến Đi');
            $table->string('audit_status')->default('PENDING_AUDIT');
            $table->text('audit_note')->nullable();
            $table->string('audited_by')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();
        });

        // 7. Booking Seats with Segment Info (Seat Inventory by Segment)
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('seat_number'); // A01, A02, B01...
            $table->integer('floor')->default(1);
            $table->integer('pickup_order')->default(0);
            $table->integer('dropoff_order')->default(1);
            $table->decimal('price', 12, 0);
            $table->timestamps();
        });

        // 8. Trip Expenses (Chi phí từng chuyến xe)
        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('expense_type'); // FUEL, TOLL, PARKING, ALLOWANCE, REPAIR, OTHER
            $table->decimal('amount', 12, 0);
            $table->string('description')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // 9. Trip Closings (Chốt chuyến & Đối soát tiền mặt)
        Schema::create('trip_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_revenue', 12, 0);
            $table->decimal('total_expenses', 12, 0);
            $table->decimal('net_profit', 12, 0);
            $table->decimal('expected_cash_from_conductor', 12, 0); // Tiền lơ xe phải nộp
            $table->decimal('actual_cash_submitted', 12, 0); // Tiền lơ xe thực nộp
            $table->decimal('cash_discrepancy', 12, 0)->default(0); // Chênh lệch (thừa/thiếu)
            $table->string('closed_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('trip_direction')->default('Chuyến Đi');
            $table->string('audit_status')->default('PENDING_AUDIT');
            $table->text('audit_note')->nullable();
            $table->string('audited_by')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();
        });

        // 10. Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->cascadeOnDelete();
            $table->integer('rating')->default(5);
            $table->integer('safety_rating')->default(5);
            $table->integer('service_rating')->default(5);
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('reviewer_name')->nullable();
            $table->timestamps();
        });

        // 11. Incidents & Lost and Found (Báo sự cố & Đồ thất lạc)
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // Hỏng xe, Trễ chuyến, Điều hòa hỏng, Khách gặp vấn đề, Sự cố giao thông
            $table->text('description');
            $table->string('severity')->default('MEDIUM'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->string('status')->default('REPORTED'); // REPORTED, PROCESSING, RESOLVED
            $table->string('reported_by')->nullable();
            $table->timestamps();
        });

        Schema::create('lost_founds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('seat_number')->nullable();
            $table->text('description')->nullable();
            $table->string('found_by')->nullable();
            $table->string('status')->default('FOUND'); // FOUND, RETURNED, DISPOSED
            $table->timestamps();
        });

        // 12. Audit Logs (Nhật ký hoạt động toàn hệ thống)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action'); // CREATE_TICKET, CHECKIN, DEPART_TRIP, CLOSE_TRIP, ASSIGN_DRIVER
            $table->string('entity_type')->nullable(); // Trip, Booking, Bus, Driver
            $table->string('entity_id')->nullable();
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('lost_founds');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('trip_closings');
        Schema::dropIfExists('trip_expenses');
        Schema::dropIfExists('booking_seats');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('route_fares');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('buses');
        Schema::dropIfExists('drivers');
    }
};
