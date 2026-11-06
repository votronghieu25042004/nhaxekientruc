<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\Trip;
use Carbon\Carbon;

use Illuminate\Support\Facades\Mail;
use App\Mail\Transport\WebhookTransport;

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
        // Đăng ký custom Mail driver 'webhook' để gửi email qua HTTPS port 443 trên Cloud (bỏ qua tường lửa Render)
        Mail::extend('webhook', function (array $config = []) {
            $url = $config['url'] ?? env('EMAIL_WEBHOOK_URL', '');
            return new WebhookTransport($url);
        });
        // Tự động đảm bảo luôn có lịch trình và dữ liệu khi chạy artisan command hoặc an toàn
        try {
            if ($this->app->runningInConsole() && Schema::hasTable('trips')) {
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
