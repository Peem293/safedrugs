<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Obat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoCalculateBuffer extends Command
{
    protected $signature = 'buffer:automate';
    protected $description = 'Otomatisasi kalkulasi buffer stock obat berdasarkan tren 6 bulan terakhir';

    public function handle()
    {
        $obats = Obat::all();
        $zFactor = 1.64; // Service level 95%
        $leadTime = 0.25; // Waktu tunggu kirim (1 minggu = 0.25 bulan)

        // 1. Cari bulan maksimal yang tersedia di database rekap
        $bulanTerakhirRaw = DB::table('rekap_pemakaian_bulanan')->max('bulan_tahun');

        if (!$bulanTerakhirRaw) {
            // Jika database kosong, default jangkar langsung ke bulan kemarin
            $anchorBulan = Carbon::now()->subMonth();
        } else {
            $maxBulanDatabase = Carbon::createFromFormat('Y-m', $bulanTerakhirRaw);
            $bulanKemarinSistem = Carbon::now()->subMonth();

            /**
             * LOGIC PERLINDUNGAN DATA BULAN BERJALAN:
             * Jika max bulan di database >= bulan sekarang (masuk bulan berjalan),
             * kunci jangkar maksimal di Bulan Kemarin agar data tidak bias/prematur.
             * Jika max database lebih jadul (ada gap), gunakan max database tersebut.
             */
            if ($maxBulanDatabase->format('Y-m') >= Carbon::now()->format('Y-m')) {
                $anchorBulan = $bulanKemarinSistem;
            } else {
                $anchorBulan = $maxBulanDatabase;
            }
        }

        // Tentukan window rentang 6 bulan ke belakang secara presisi berdasarkan anchorBulan
        $rentangAwal = $anchorBulan->copy()->subMonths(5)->format('Y-m'); // Batas bawah
        $rentangAkhir = $anchorBulan->format('Y-m'); // Batas atas (Maksimal bulan lalu)

        foreach ($obats as $obat) {
            // 2. Ambil data standard deviasi tepat di dalam rentang window aman (whereBetween)
            $stats = $obat->rekapBulanans()
                ->whereBetween('bulan_tahun', [$rentangAwal, $rentangAkhir])
                ->selectRaw('AVG(total_jumlah) as rata_rata, STDDEV(total_jumlah) as standar_deviasi')
                ->first();

            // Jika data historis belum mencukupi, beri nilai default / fallback aman
            $stdDev = $stats->standar_deviasi ?? 10;
            $avgKonsumsi = $stats->rata_rata ?? 50;

            // 3. Eksekusi Rumus Matematika Statistik
            $bufferStock = $zFactor * $stdDev * sqrt($leadTime);

            // 4. Hitung Minimum Stock untuk batas Reorder Point (Kritis)
            $minStock = ($avgKonsumsi * $leadTime) + $bufferStock;

            // 5. Sinkronisasi otomatis nilai ke dalam tabel master obat
            $obat->update([
                'buffer_stock' => ceil($bufferStock), // Dibulatkan ke atas demi keamanan sediaan
                'min_stock' => ceil($minStock),
                'last_buffer_calculated_at' => Carbon::now()
            ]);
        }

        $this->info("Otomatisasi sukses! Menghitung tren menggunakan window aman: {$rentangAwal} s/d {$rentangAkhir}");
    }
}
