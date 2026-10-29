<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add user_id to drivers so driver accounts can login
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('drivers', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('drivers', 'cccd_number')) {
                $table->string('cccd_number')->nullable()->after('email');
            }
            if (!Schema::hasColumn('drivers', 'license_expiry')) {
                $table->date('license_expiry')->nullable()->after('license_class');
            }
            if (!Schema::hasColumn('drivers', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('drivers', 'cccd_path')) {
                $table->string('cccd_path')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('drivers', 'gplx_path')) {
                $table->string('gplx_path')->nullable()->after('cccd_path');
            }
        });

        // Add user_id to drivers for conductors linkage (already in users table)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('id')->constrained('drivers')->nullOnDelete();
            }
        });

        // Add live tracking fields to trips
        Schema::table('trips', function (Blueprint $table) {
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
            if (!Schema::hasColumn('trips', 'second_driver_id')) {
                $table->foreignId('second_driver_id')->nullable()->after('conductor_id')->constrained('drivers')->nullOnDelete();
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

        // Add fields to bookings for user linkage & tracking
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('audited_at');
            }
            if (!Schema::hasColumn('bookings', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('cancelled_at');
            }
        });

        // Add extra pricing/fleet tables if not exist
        if (!Schema::hasTable('pricing_rules')) {
            Schema::create('pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type'); // PEAK_SEASON, WEEKEND, HOLIDAY, TIME_BASED
                $table->decimal('multiplier', 4, 2)->default(1.0);
                $table->boolean('is_active')->default(true);
                $table->json('conditions')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('type')->default('PERCENT'); // PERCENT, FIXED
                $table->decimal('value', 12, 0)->default(0);
                $table->decimal('min_order_amount', 12, 0)->default(0);
                $table->integer('usage_limit')->default(100);
                $table->integer('used_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cancellation_policies')) {
            Schema::create('cancellation_policies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('hours_before')->default(24); // hours before departure
                $table->integer('refund_percent')->default(100); // 0-100
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fleet_maintenance_records')) {
            Schema::create('fleet_maintenance_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // OIL_CHANGE, TIRE, BRAKE, etc.
                $table->string('part_name')->nullable();
                $table->decimal('cost', 12, 0)->default(0);
                $table->text('description')->nullable();
                $table->string('performed_by')->nullable();
                $table->date('performed_date');
                $table->integer('km_at_service')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fuel_logs')) {
            Schema::create('fuel_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('liters', 8, 2);
                $table->decimal('price_per_liter', 8, 0)->default(25000);
                $table->decimal('total_cost', 12, 0);
                $table->integer('km_at_fill')->nullable();
                $table->string('station_name')->nullable();
                $table->string('recorded_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fixed_fleet_expenses')) {
            Schema::create('fixed_fleet_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bus_id')->nullable()->constrained()->nullOnDelete();
                $table->string('expense_type'); // STATION_FEE, INSURANCE, BOT_TOLL, OTHER
                $table->string('name');
                $table->decimal('amount', 12, 0);
                $table->string('period')->default('MONTHLY'); // MONTHLY, YEARLY, ONE_TIME
                $table->date('due_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('action');
                $table->string('entity_type')->nullable();
                $table->string('entity_id')->nullable();
                $table->text('description');
                $table->string('performed_by')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'trip_id')) {
                    $table->foreignId('trip_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('audit_logs', 'performed_by')) {
                    $table->string('performed_by')->nullable()->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'email', 'cccd_number', 'license_expiry', 'photo_path', 'cccd_path', 'gplx_path']);
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['current_lat', 'current_lng', 'current_speed_kmh', 'gps_updated_at', 'delay_minutes', 'handover_notes', 'stop_status_logs', 'second_driver_id', 'accepted_at', 'vehicle_checked_at', 'actual_arrival_time']);
        });
    }
};
