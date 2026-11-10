<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'used_count')) {
                    $table->integer('used_count')->default(0);
                }
            });

            // Đồng bộ dữ liệu hiện có giữa usage_count và used_count
            DB::statement("UPDATE vouchers SET used_count = usage_count WHERE used_count = 0 AND usage_count > 0");
            DB::statement("UPDATE vouchers SET usage_count = used_count WHERE usage_count = 0 AND used_count > 0");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'used_count')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('used_count');
            });
        }
    }
};
