<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('tenant:send-reminder')->dailyAt('08:00');

// Jalankan pengecekan status otomatis setiap menit sesuai aturan Retry DANA
Schedule::command('dana:retry-inquiry')->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
