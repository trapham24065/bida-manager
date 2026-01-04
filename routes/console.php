<?php

use Illuminate\Foundation\Inspiring;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote');

// ============================================================
// 🔔 TỰ ĐỘNG KIỂM TRA THÔNG BÁO ĐẶT BÀN
// ============================================================
// Chạy mỗi phút để kiểm tra booking sắp tới và booking trễ
// (Có thể đổi thành everyFiveMinutes() để giảm tải)
Schedule::command('bookings:check-alerts')
    ->everyMinute()           // Chạy mỗi phút (đổi thành everyFiveMinutes() nếu muốn)
    ->withoutOverlapping()    // Tránh chạy trùng lặp nếu lần trước chưa xong
    ->runInBackground();      // Chạy background để không block
