<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScraperService; // Mengimpor Service Scraper Anda yang sudah ada

class SyncSimrsStockAuto extends Command
{
    // Nama perintah terminal yang akan dipanggil oleh scheduler di routes/console.php
    protected $signature = 'simrs:sync-stock';
    protected $description = 'Sinkronisasi otomatis harian data stok obat dari SIMRS via Scheduler';

    /**
     * Menginjeksikan ScraperService ke dalam fungsi handle()
     */
    public function handle(ScraperService $scraperService)
    {
        $this->info('=== Memulai Sinkronisasi Otomatis SIMRS via Scheduler ===');

        // Menggunakan kredensial riil yang sama persis dengan tombol manual Anda
        $usernameSimrs = '020150702';
        $passwordSimrs = 'Nuel.1310';

        // Mengeksekusi robot scraper yang sudah Anda bangun di Service Class
        $respon = $scraperService->ambilDataSimrs($usernameSimrs, $passwordSimrs);

        // Memberikan output log/feedback di terminal server atau log cron job
        if (isset($respon['status']) && $respon['status'] === 'sukses') {
            $this->info('Sinkronisasi otomatis berhasil! Seluruh data stok terupdate.');
        } else {
            $errorMsg = $respon['error'] ?? 'Koneksi ke SIMRS terputus.';
            $this->error('Sinkronisasi otomatis gagal. Kendala: ' . $errorMsg);
        }
    }
}
