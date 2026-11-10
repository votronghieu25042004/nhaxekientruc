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
            if (!Schema::hasColumn('bookings', 'ai_call_at')) {
                $table->timestamp('ai_call_at')->nullable()->after('reminder_sent_by');
            }
            if (!Schema::hasColumn('bookings', 'ai_call_by')) {
                $table->string('ai_call_by', 20)->nullable()->after('ai_call_at')
                    ->comment('auto = AI tự động gọi trước 45p, manual = admin bấm tay');
            }
            if (!Schema::hasColumn('bookings', 'ai_call_status')) {
                $table->string('ai_call_status', 30)->nullable()->after('ai_call_by')
                    ->comment('PENDING, COMPLETED, ANSWERED, FAILED');
            }
            if (!Schema::hasColumn('bookings', 'ai_call_audio_url')) {
                $table->string('ai_call_audio_url', 500)->nullable()->after('ai_call_status');
            }
            if (!Schema::hasColumn('bookings', 'ai_call_script')) {
                $table->text('ai_call_script')->nullable()->after('ai_call_audio_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumnIfExists('ai_call_at');
            $table->dropColumnIfExists('ai_call_by');
            $table->dropColumnIfExists('ai_call_status');
            $table->dropColumnIfExists('ai_call_audio_url');
            $table->dropColumnIfExists('ai_call_script');
        });
    }
};
