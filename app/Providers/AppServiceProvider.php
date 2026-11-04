<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\Trip;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tự động đảm bảo luôn có lịch trình và dữ liệu cho ngày hôm nay (tránh bị trống dữ liệu khi sang ngày mới)
        try {
            if (Schema::hasTable('trips')) {
                $todayCount = Trip::whereDate('departure_time', Carbon::today('Asia/Ho_Chi_Minh'))->count();
                if ($todayCount === 0) {
                    Artisan::call('db:seed');
                } else {
                    Trip::syncRealTimeStatuses();
                }
            }
        } catch (\Throwable $e) {
            // bỏ qua khi đang migrate hoặc chạy console command khác
        }
    }
}
