<?php

namespace App\Filament\Widgets;

use App\Models\Obat;
use App\Models\Prediksi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    // Mengatur agar barisan widget pas memenuhi halaman dashboard
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // 1. Menghitung Total Jenis Master Data Obat
        $totalObat = Obat::count();

        // 2. Menghitung Rata-rata Akurasi MAPE untuk Hasil Prediksi Periode Juni 2026
        $rataMape = Prediksi::where('bulan_tahun_prediksi', '2026-06')->avg('nilai_mape');
        $displayMape = $rataMape ? round($rataMape, 2) . '%' : '0%';

        $warnaMape = 'gray';
        $deskripsiMape = 'Belum Ada Data Kalkulasi';
        if ($rataMape) {
            if ($rataMape < 10) {
                $warnaMape = 'success';
                $deskripsiMape = 'Kriteria Performa: Sangat Baik';
            } elseif ($rataMape <= 20) {
                $warnaMape = 'info';
                $deskripsiMape = 'Kriteria Performa: Baik';
            } else {
                $warnaMape = 'warning';
                $deskripsiMape = 'Kriteria Performa: Cukup';
            }
        }

        // 3. Menghitung Jumlah Item Obat Kritis (< 50) dari Tabel Obats
        // Disesuaikan kembali menggunakan nama kolom asli di database Anda: 'stock_on_hand'
        $stokKritis = Obat::whereRaw('stock < min_stock')->count();

        // 4. Menghitung Sediaan yang Expired & Mendekati Expired dari Tabel stock_onhand
        $hariIni = Carbon::today();
        $enamBulanKeDepan = Carbon::today()->addMonths(6);

        $jumlahExpired = DB::table('stock_onhand')
            ->whereDate('exp_date', '<=', $hariIni)
            ->count();

        $jumlahMendekatiExpired = DB::table('stock_onhand')
            ->whereDate('exp_date', '>', $hariIni)
            ->whereDate('exp_date', '<=', $enamBulanKeDepan)
            ->count();

        // Mengatur Warna Card Berdasarkan Risiko Kedaluwarsa
        $warnaExp = 'success';
        if ($jumlahExpired > 0) {
            $warnaExp = 'danger';
        } elseif ($jumlahMendekatiExpired > 0) {
            $warnaExp = 'warning';
        }

        return [
            Stat::make('Total Item Obat', $totalObat . ' Produk')
                ->description('Lihat semua daftar master data terdaftar')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info')
                ->url(url('/admin/obats')), // Tautan ke halaman master obat

            Stat::make('Obat Stok Kritis', $stokKritis . ' Item')
                ->description($stokKritis > 0 ? 'Segera lakukan pengadaan sediaan! ⚠' : 'Semua kuantitas sediaan aman')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stokKritis > 0 ? 'danger' : 'success')
                /**
                 * PERBAIKAN NAVIGASI FILTER:
                 * Mengarahkan ke halaman daftar obat dengan memanfaatkan filter URL query string bawaan Filament.
                 * Menggunakan format tableFilters[nama_filter][value]=true agar tabel langsung terpotong hanya menampilkan yang kritis.
                 */
                ->url(url('/admin/obats?tableFilters[stok_kritis][value]=true&filters[stok_kritis][isActive]=true')),

            Stat::make('Sediaan Kedaluwarsa', $jumlahExpired . ' Item Expired')
                ->description($jumlahMendekatiExpired . ' Item mendekati kedaluwarsa (< 6 bulan)')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($warnaExp)
                ->url(url('/admin/stock-onhands')), // Navigasi tombol klik langsung ke detail tabel Stock On Hands

            Stat::make('Rata-Rata Akurasi MAPE', $displayMape)
                ->description($deskripsiMape)
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($warnaMape)
                ->url(url('/admin/prediksis')), // Tautan ke histori komparasi nilai peramalan
        ];
    }
}
