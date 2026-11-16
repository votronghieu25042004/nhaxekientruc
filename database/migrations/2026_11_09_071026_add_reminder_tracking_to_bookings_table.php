<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // AI/admin reminder tracking
            if (!Schema::hasColumn('bookings', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('notes')
                    ->comment('Thời điểm gửi nhắc nhở 45p trước giờ chạy');
            }
            if (!Schema::hasColumn('bookings', 'reminder_sent_by')) {
                $table->string('reminder_sent_by', 20)->nullable()->after('reminder_sent_at')
                    ->comment('auto = tự động, manual = admin bấm tay');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumnIfExists('reminder_sent_at');
            $table->dropColumnIfExists('reminder_sent_by');
        });
    }
};
