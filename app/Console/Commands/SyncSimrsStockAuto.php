<?php

namespace App\Console\Commands;

use App\Jobs\SyncSimrsStockJob;
use Illuminate\Console\Command;

class SyncSimrsStockAuto extends Command
{
    protected $signature = 'simrs:sync-stock';
    protected $description = 'Sinkronisasi otomatis harian data stok obat dari SIMRS via Scheduler (Queue-based)';

    public function handle(): void
    {
        $this->info('=== Mengirim Job Sinkronisasi SIMRS ke Queue ===');

        // User ID 1 = Admin utama (penerima notifikasi untuk job terjadwal)
        SyncSimrsStockJob::dispatch(1);

        $this->info('✅ Job SyncSimrsStockJob berhasil dikirim ke antrean.');
        $this->info('   Worker queue akan memproses scraping di latar belakang.');
    }
}
