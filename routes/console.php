<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Tự động quét và gửi email nhắc nhở khởi hành trước 45 phút mỗi 5 phút
Schedule::command('bus:send-reminders')->everyFiveMinutes();
