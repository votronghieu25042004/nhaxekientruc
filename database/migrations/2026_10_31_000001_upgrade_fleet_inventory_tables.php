<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_maintenance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_maintenance_records', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('bus_id');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'service_title')) {
                $table->string('service_title')->nullable()->after('plate_number');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'service_type')) {
                $table->string('service_type')->nullable()->after('service_title');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'garage_name')) {
                $table->string('garage_name')->nullable()->after('service_type');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'service_date')) {
                $table->date('service_date')->nullable()->after('garage_name');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'odo_km')) {
                $table->integer('odo_km')->nullable()->after('service_date');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'next_service_km')) {
                $table->integer('next_service_km')->nullable()->after('odo_km');
            }
            if (!Schema::hasColumn('fleet_maintenance_records', 'notes')) {
                $table->text('notes')->nullable()->after('next_service_km');
            }
        });

        Schema::table('fuel_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_logs', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('bus_id');
            }
            if (!Schema::hasColumn('fuel_logs', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('plate_number');
            }
            if (!Schema::hasColumn('fuel_logs', 'trip_code')) {
                $table->string('trip_code')->nullable()->after('driver_name');
            }
            if (!Schema::hasColumn('fuel_logs', 'distance_km')) {
                $table->decimal('distance_km', 8, 1)->nullable()->after('trip_code');
            }
            if (!Schema::hasColumn('fuel_logs', 'liters_filled')) {
                $table->decimal('liters_filled', 8, 2)->nullable()->after('distance_km');
            }
            if (!Schema::hasColumn('fuel_logs', 'fuel_rate_standard')) {
                $table->decimal('fuel_rate_standard', 5, 1)->default(22.0)->after('liters_filled');
            }
            if (!Schema::hasColumn('fuel_logs', 'fuel_rate_actual')) {
                $table->decimal('fuel_rate_actual', 5, 1)->nullable()->after('fuel_rate_standard');
            }
            if (!Schema::hasColumn('fuel_logs', 'discrepancy_liters')) {
                $table->decimal('discrepancy_liters', 6, 1)->nullable()->after('fuel_rate_actual');
            }
            if (!Schema::hasColumn('fuel_logs', 'log_date')) {
                $table->date('log_date')->nullable()->after('discrepancy_liters');
            }
            if (!Schema::hasColumn('fuel_logs', 'notes')) {
                $table->text('notes')->nullable()->after('log_date');
            }
        });

        Schema::table('fixed_fleet_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_fleet_expenses', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('bus_id');
            }
            if (!Schema::hasColumn('fixed_fleet_expenses', 'expense_category')) {
                $table->string('expense_category')->nullable()->after('plate_number');
            }
            if (!Schema::hasColumn('fixed_fleet_expenses', 'title')) {
                $table->string('title')->nullable()->after('expense_category');
            }
            if (!Schema::hasColumn('fixed_fleet_expenses', 'billing_period')) {
                $table->string('billing_period')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('fixed_fleet_expenses', 'provider_name')) {
                $table->string('provider_name')->nullable()->after('billing_period');
            }
            if (!Schema::hasColumn('fixed_fleet_expenses', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
    }
};
