<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users table additions
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'reward_points')) {
                    $table->integer('reward_points')->default(0)->after('role');
                }
                if (!Schema::hasColumn('users', 'loyalty_tier')) {
                    $table->string('loyalty_tier')->default('MEMBER')->after('reward_points');
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->string('status')->default('ACTIVE')->after('loyalty_tier');
                }
                if (!Schema::hasColumn('users', 'driver_id')) {
                    $table->foreignId('driver_id')->nullable()->after('id')->nullOnDelete();
                }
            });
        }

        // 2. Trips table additions (second_conductor_id, second_driver_id, etc.)
        if (Schema::hasTable('trips')) {
            Schema::table('trips', function (Blueprint $table) {
                if (!Schema::hasColumn('trips', 'second_conductor_id')) {
                    $table->foreignId('second_conductor_id')->nullable()->after('conductor_id')->nullOnDelete();
                }
                if (!Schema::hasColumn('trips', 'second_driver_id')) {
                    $table->foreignId('second_driver_id')->nullable()->after('driver_id')->nullOnDelete();
                }
                if (!Schema::hasColumn('trips', 'current_lat')) {
                    $table->decimal('current_lat', 10, 7)->nullable()->after('current_stop_order');
                }
                if (!Schema::hasColumn('trips', 'current_lng')) {
                    $table->decimal('current_lng', 10, 7)->nullable()->after('current_lat');
                }
                if (!Schema::hasColumn('trips', 'current_speed_kmh')) {
                    $table->integer('current_speed_kmh')->default(0)->after('current_lng');
                }
                if (!Schema::hasColumn('trips', 'gps_updated_at')) {
                    $table->timestamp('gps_updated_at')->nullable()->after('current_speed_kmh');
                }
                if (!Schema::hasColumn('trips', 'delay_minutes')) {
                    $table->integer('delay_minutes')->default(0)->after('gps_updated_at');
                }
                if (!Schema::hasColumn('trips', 'handover_notes')) {
                    $table->text('handover_notes')->nullable()->after('delay_minutes');
                }
                if (!Schema::hasColumn('trips', 'stop_status_logs')) {
                    $table->json('stop_status_logs')->nullable()->after('handover_notes');
                }
                if (!Schema::hasColumn('trips', 'accepted_at')) {
                    $table->timestamp('accepted_at')->nullable()->after('closed_at');
                }
                if (!Schema::hasColumn('trips', 'vehicle_checked_at')) {
                    $table->timestamp('vehicle_checked_at')->nullable()->after('accepted_at');
                }
                if (!Schema::hasColumn('trips', 'actual_arrival_time')) {
                    $table->timestamp('actual_arrival_time')->nullable()->after('vehicle_checked_at');
                }
            });
        }

        // 3. Bookings table additions (points_earned, points_awarded, cancel_reason, etc.)
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'points_earned')) {
                    $table->integer('points_earned')->default(0)->after('paid_amount');
                }
                if (!Schema::hasColumn('bookings', 'points_awarded')) {
                    $table->boolean('points_awarded')->default(false)->after('points_earned');
                }
                if (!Schema::hasColumn('bookings', 'boarding_status')) {
                    $table->string('boarding_status')->default('NOT_BOARDED')->after('checkin_status');
                }
                if (!Schema::hasColumn('bookings', 'is_boarded')) {
                    $table->boolean('is_boarded')->default(false)->after('boarding_status');
                }
                if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('audited_at');
                }
                if (!Schema::hasColumn('bookings', 'cancel_reason')) {
                    $table->string('cancel_reason')->nullable()->after('cancelled_at');
                }
                if (!Schema::hasColumn('bookings', 'refund_bank_name')) {
                    $table->string('refund_bank_name')->nullable()->after('cancel_reason');
                }
                if (!Schema::hasColumn('bookings', 'refund_account_number')) {
                    $table->string('refund_account_number')->nullable()->after('refund_bank_name');
                }
                if (!Schema::hasColumn('bookings', 'refund_account_name')) {
                    $table->string('refund_account_name')->nullable()->after('refund_account_number');
                }
                if (!Schema::hasColumn('bookings', 'refund_qr_code')) {
                    $table->text('refund_qr_code')->nullable()->after('refund_account_name');
                }
                if (!Schema::hasColumn('bookings', 'refund_amount')) {
                    $table->decimal('refund_amount', 12, 0)->nullable()->after('refund_qr_code');
                }
                if (!Schema::hasColumn('bookings', 'refund_status')) {
                    $table->string('refund_status')->nullable()->after('refund_amount');
                }
                if (!Schema::hasColumn('bookings', 'refund_reason')) {
                    $table->text('refund_reason')->nullable()->after('refund_status');
                }
                if (!Schema::hasColumn('bookings', 'refund_requested_at')) {
                    $table->timestamp('refund_requested_at')->nullable()->after('refund_reason');
                }
                if (!Schema::hasColumn('bookings', 'refund_processed_at')) {
                    $table->timestamp('refund_processed_at')->nullable()->after('refund_requested_at');
                }
                if (!Schema::hasColumn('bookings', 'refund_processed_by')) {
                    $table->string('refund_processed_by')->nullable()->after('refund_processed_at');
                }
                if (!Schema::hasColumn('bookings', 'refund_admin_notes')) {
                    $table->text('refund_admin_notes')->nullable()->after('refund_processed_by');
                }
            });
        }

        // 4. Booking Seats additions
        if (Schema::hasTable('booking_seats')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_seats', 'checkin_status')) {
                    $table->string('checkin_status')->default('pending')->after('dropoff_order');
                }
            });
        }
    }

    public function down(): void
    {
        // Safe down
    }
};
