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
            $table->string('refund_bank_name')->nullable()->after('cancel_reason');
            $table->string('refund_account_number')->nullable()->after('refund_bank_name');
            $table->string('refund_account_name')->nullable()->after('refund_account_number');
            $table->text('refund_qr_code')->nullable()->after('refund_account_name');
            $table->decimal('refund_amount', 12, 0)->nullable()->after('refund_qr_code');
            $table->string('refund_status')->nullable()->after('refund_amount'); // PENDING, APPROVED, REJECTED
            $table->text('refund_reason')->nullable()->after('refund_status');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_reason');
            $table->timestamp('refund_processed_at')->nullable()->after('refund_requested_at');
            $table->string('refund_processed_by')->nullable()->after('refund_processed_at');
            $table->text('refund_admin_notes')->nullable()->after('refund_processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'refund_bank_name',
                'refund_account_number',
                'refund_account_name',
                'refund_qr_code',
                'refund_amount',
                'refund_status',
                'refund_reason',
                'refund_requested_at',
                'refund_processed_at',
                'refund_processed_by',
                'refund_admin_notes',
            ]);
        });
    }
};
