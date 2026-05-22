<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TrenPrediksiChart extends ChartWidget
{
    // Judul widget grafik di dashboard utama
    protected ?string $heading = 'Analisis Tren Pemakaian Total vs Proyeksi Peramalan (Least Square)';

    // Mengatur tinggi area grafik agar proporsional
    protected ?string $maxHeight = '320px';

    // Mengatur lebar widget agar memenuhi halaman dashboard (full span)
    protected int | string | array $columnSpan = 'full';

    /**
     * Menentukan tipe dasar chart.
     * Memilih 'bar' agar bisa dikombinasikan dengan 'line' (Mixed Chart).
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Memproses dan menyusun data analitis dari database secara dinamis.
     */
    protected function getData(): array
    {
        // 1. Mengambil data akumulasi pemakaian riil obat per bulan dari database
        $historiRiil = DB::table('rekap_pemakaian_bulanan')
            ->select('bulan_tahun', DB::raw("SUM(total_jumlah) as total"))
            ->groupBy('bulan_tahun')
            ->orderBy('bulan_tahun', 'asc')
            ->pluck('total', 'bulan_tahun')
            ->toArray();

        // 2. Mengambil total agregat hasil peramalan obat per periode secara dinamis
        $prediksiBulanan = DB::table('prediksi')
            ->select('bulan_tahun_prediksi', DB::raw("SUM(hasil_prediksi) as total"))
            ->groupBy('bulan_tahun_prediksi')
            ->pluck('total', 'bulan_tahun_prediksi')
            ->toArray();

        // 3. Menggabungkan semua periode (bulan_tahun) secara dinamis agar grafik otomatis bertambah
        $semuaPeriode = array_unique(array_merge(array_keys($historiRiil), array_keys($prediksiBulanan)));
        sort($semuaPeriode); // Urutkan kronologis dari bulan tertua ke terbaru

        $labels = [];
        $dataRiil = [];
        $dataPrediksi = [];

        // Mendapatkan key bulan terakhir pada data riil untuk titik sambung grafik
        $bulanRiilTerakhir = array_key_last($historiRiil);

        // 4. Lakukan perulangan untuk menyusun sumbu X (labels) dan sumbu Y (datasets)
        foreach ($semuaPeriode as $periode) {
            $timestamp = strtotime($periode . '-01');
            $namaBulanIndo = $this->getNamaBulanIndo(date('n', $timestamp));
            $tahun = date('Y', $timestamp);

            $adaRiil = isset($historiRiil[$periode]);
            $adaPrediksi = isset($prediksiBulanan[$periode]);

            if ($adaPrediksi && !$adaRiil) {
                // KONDISI A: Hanya ada data hasil peramalan (Masa Depan)
                $labels[] = $namaBulanIndo . ' ' . $tahun . ' (P)';
                $dataRiil[] = null; // Batang kosong
                $dataPrediksi[] = $prediksiBulanan[$periode];
            } else {
                // KONDISI B: Ada data historis riil (Masa Lalu / Sekarang)
                $labels[] = $namaBulanIndo . ' ' . $tahun;
                $dataRiil[] = $historiRiil[$periode] ?? null;

                // Logika Titik Temu: Garis tren akan menyambung tepat pada data riil terakhir
                if ($adaPrediksi) {
                    $dataPrediksi[] = $prediksiBulanan[$periode];
                } elseif ($periode === $bulanRiilTerakhir) {
                    $dataPrediksi[] = $historiRiil[$periode]; // Titik ikat awal garis line chart
                } else {
                    $dataPrediksi[] = null;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Realisasi Pemakaian Obat (Riil)',
                    'data' => $dataRiil,
                    'backgroundColor' => '#3b82f6', // Warna biru untuk data aktual
                    'borderColor' => '#3b82f6',
                    'order' => 2, // Merender balok di lapisan belakang
                ],
                [
                    'label' => 'Garis Tren Peramalan (Least Square)',
                    'data' => $dataPrediksi,
                    'type' => 'line', // Mengubah spesifik dataset ini menjadi garis
                    'borderColor' => '#f59e0b', // Warna amber/oranye untuk penanda tren peramalan
                    'backgroundColor' => 'rgba(245, 158, 11, 0.05)',
                    'borderDash' => [6, 4], // Membuat efek garis putus-putus standar akademis skripsi
                    'pointBackgroundColor' => '#d97706',
                    'pointRadius' => 5,
                    'fill' => true,
                    'order' => 1, // Merender garis di lapisan paling depan agar tidak tertutup balok
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Fungsi Helper untuk mengubah format angka bulan menjadi teks singkat Indonesia.
     */
    private function getNamaBulanIndo(int $bulan): string
    {
        $bulanIndo = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agst', 9 => 'Sept', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        return $bulanIndo[$bulan] ?? '';
    }
}
