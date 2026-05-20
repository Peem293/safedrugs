<?php

//use App\Http\Controllers\TestScraperControllerJson;
use App\Services\ScraperService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return filament('admin/login');
});

Route::get('/test-scraper', function (ScraperService $scraper) {
    // 1. Jalankan fungsi scraper dengan kredensial Anda
    $hasil = $scraper->ambilDataSimrs('020150702', 'Nuel.1310');

    // 2. Tampilkan langsung ke layar browser dalam format JSON murni
    return response()->json($hasil);
});
