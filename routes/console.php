<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task Schedule: Sinkronisasi Otomatis Buffer Stock (Setiap tgl 1 setiap bulan)
Schedule::command('buffer:automate')->monthlyOn(1, '00:00');

// Task Schedule: Sinkronisasi Otomatis Stok SIMRS (Setiap Hari Jam 7 Pagi)
Schedule::command('simrs:sync-stock')->dailyAt('07:00');
