<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reward_points', 'loyalty_tier', 'status']);
        });
    }
};
