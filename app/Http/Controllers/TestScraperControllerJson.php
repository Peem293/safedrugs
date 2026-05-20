<?php

namespace App\Http\Controllers;

use App\Services\ScraperService;
//use Illuminate\Http\Request;

class TestScraperControllerJson extends Controller
{
    public function cekJson(ScraperService $scraperService)
    {
        $driverPath = 'D:/laragon/www/safedrugs/chromedriver.exe';

        $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] = $driverPath;
        putenv("PANTHER_CHROME_DRIVER_BINARY={$driverPath}");
        // 1. Masukkan kredensial login SIMRS kamu di sini
        $username = '020150702';
        $password = 'Nuel.1310';

        // 2. Jalankan service scraper yang kemarin kita buat
        $hasil = $scraperService->ambilDataSimrs($username, $password);

        // 3. Tampilkan hasilnya langsung di browser dalam format JSON rapi
        return response()->json($hasil, 200, [], JSON_PRETTY_PRINT);
    }
}
