<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'boarding_status')) {
                $table->string('boarding_status')->default('NOT_BOARDED')->after('checkin_status');
            }
            if (!Schema::hasColumn('bookings', 'is_boarded')) {
                $table->boolean('is_boarded')->default(false)->after('boarding_status');
            }
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_seats', 'checkin_status')) {
                $table->string('checkin_status')->default('pending')->after('dropoff_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'boarding_status')) {
                $table->dropColumn('boarding_status');
            }
            if (Schema::hasColumn('bookings', 'is_boarded')) {
                $table->dropColumn('is_boarded');
            }
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            if (Schema::hasColumn('booking_seats', 'checkin_status')) {
                $table->dropColumn('checkin_status');
            }
        });
    }
};
