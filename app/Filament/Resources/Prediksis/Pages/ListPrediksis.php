<?php

namespace App\Filament\Resources\Prediksis\Pages;

use App\Filament\Resources\Prediksis\PrediksiResource;
use App\Models\Obat;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ListPrediksis extends ListRecords
{
    protected static string $resource = PrediksiResource::class;

    protected string $view = 'filament.resources.prediksis.pages.proses-prediksi-list';

    /**
     * Membuat Form Kustom Menggunakan Fitur Resmi Header Actions Filament
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('prosesPrediksiModal')
                ->label('Mulai Proses Prediksi')
                ->icon('heroicon-m-cpu-chip')
                ->color('warning')
                ->modalHeading('Kalkulasi Prediksi Least Square & MAPE')
                ->modalDescription('Silakan tentukan bulan target yang ingin dihitung peramalannya.')
                ->modalSubmitActionLabel('Eksekusi Perhitungan')

                // Merender komponen Form resmi Filament di dalam Modal Popup
                ->form([
                    Select::make('bulan_target')
                        ->label('Pilih Bulan Target Prediksi')
                        ->options($this->getOpsiBulan())
                        ->placeholder('Pilih bulan target untuk perhitungan...')
                        ->required(),
                ])

                // Menangani eksekusi data setelah tombol 'Eksekusi Perhitungan' diklik
                ->action(function (array $data): void {
                    $this->eksekusiLogikaPrediksi($data['bulan_target']);
                }),
        ];
    }

    /**
     * Fungsi Generator Opsi Bulan
     */
    private function getOpsiBulan(): array
    {
        $options = [];

        // Kita kunci perulangan 12 bulan khusus untuk tahun 2026
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Membuat tanggal statis berdasarkan urutan bulan di tahun 2026
            $date = Carbon::create(2026, $bulan, 1);

            // Simpan format '2026-01' sebagai value database, dan 'Januari 2026' sebagai label dropdown
            $options[$date->format('Y-m')] = $date->translatedFormat('F Y');
        }

        return $options;
    }

    /**
     * Core Logika Hitung Least Square & MAPE (PostgreSQL Version)
     */
    private function eksekusiLogikaPrediksi(string $bulanTarget): void
    {
        // 1. Ambil data semua obat untuk diproses peramalannya
        $daftarObat = Obat::all();

        // Matriks X ganjil untuk data historis genap 6 bulan ke belakang dari bulan target
        $matriksX = [-5, -3, -1, 1, 3, 5];
        $nilaiXTarget = 7; // Nilai X untuk bulan ke-7 (Bulan Target Prediksi)

        DB::beginTransaction();

        try {
            foreach ($daftarObat as $obat) {

                // 2. Ambil 6 bulan data historis ke belakang dari Bulan Target
                $historiBulanan = DB::table('rekap_pemakaian_bulanan')
                    ->select('bulan_tahun', DB::raw("SUM(total_jumlah) as total_pakai"))
                    ->where('obat_id', $obat->id)
                    ->where('bulan_tahun', '<', $bulanTarget)
                    ->groupBy('bulan_tahun')
                    ->orderBy('bulan_tahun', 'desc') // Mengambil 6 bulan terbaru ke belakang
                    ->take(6)
                    ->get()
                    ->reverse() // Balikkan agar urut dari bulan terlama ke terbaru
                    ->values(); // Reset index menjadi 0, 1, 2, 3, 4, 5

                if ($historiBulanan->count() < 6) {
                    continue;
                }

                // 3. Kalkulasi Nilai Sigma Least Square Utama (6 Bulan Data Historis)
                $sumY = 0; $sumXY = 0; $sumX2 = 0;
                for ($i = 0; $i < 6; $i++) {
                    $y = (float) $historiBulanan[$i]->total_pakai;
                    $x = $matriksX[$i]; // Berpasangan sempurna dengan [-5, -3, -1, 1, 3, 5]

                    $sumY += $y;
                    $sumXY += ($x * $y);
                    $sumX2 += ($x * $x); // Hasil total akhir pasti 70
                }

                // Sesuai rumus manual: a = Total Y / n (n = 6)
                $a = $sumY / 6;

                // Sesuai rumus manual: b = Total XY / Total X^2 (Pembagi pasti 70)
                $b = $sumX2 > 0 ? ($sumXY / $sumX2) : 0;

                // Rumus Inti Least Square untuk Target Bulan Depan (April): Y = a + bX
                $proyeksiY = $a + ($b * $nilaiXTarget);
                $proyeksiY = $proyeksiY < 0 ? 0 : round($proyeksiY);


                // 4. FIX KALKULASI MAPE EVALUASI (Dinamis Berdasarkan 5 Bulan Ke Belakang Sebelum Bulan Penguji)
                // Bulan Evaluasi/Penguji berada pada indeks ke-5 (Bulan ke-6 dari histori data, contoh: Maret 2026)
                $yAktualEvaluasi = (float) $historiBulanan[5]->total_pakai; // Nilai At Maret = 1335

                // Isolasi murni data indeks 0 sampai 4 (Oktober s.d. Februari) untuk mencari a dan b evaluasi
                $dataHistoriMape = $historiBulanan->slice(0, 5)->values();

                $sumYEvaluasi = 0; $sumXYEvaluasi = 0; $sumX2Evaluasi = 0;
                $matriksXEvaluasi = [-2, -1, 0, 1, 2]; // Indeks X ganjil untuk data berjumlah 5 (Ganjil)

                for ($i = 0; $i < 5; $i++) {
                    $yEval = (float) $dataHistoriMape[$i]->total_pakai;
                    $xEval = $matriksXEvaluasi[$i];

                    $sumYEvaluasi += $yEval;
                    $sumXYEvaluasi += ($xEval * $yEval);
                    $sumX2Evaluasi += ($xEval * $xEval); // Total ∑X² pasti 10
                }

                // Mencari Nilai a dan b khusus simulasi pengujian MAPE (Hasil: a = 1279, b = 22.4)
                $aEvaluasi = $sumYEvaluasi / 5;
                $bEvaluasi = $sumX2Evaluasi > 0 ? ($sumXYEvaluasi / $sumX2Evaluasi) : 0;

                // Nilai X untuk bulan penguji (Maret) adalah lanjutan dari indeks 2 dengan interval 1 = 3
                $xTargetEvaluasi = 3;

                // Ft Evaluasi = a + bX = 1279 + (22.4 * 3) = 1346.2
                // Catatan: Jika ingin menggunakan nilai Ft bulat (1347) seperti hitungan Word Anda, gunakan: round($aEvaluasi + ($bEvaluasi * $xTargetEvaluasi))
                $yPrediksiEvaluasi = $aEvaluasi + ($bEvaluasi * $xTargetEvaluasi);

                if ($yAktualEvaluasi > 0) {
                    // Rumus Akurasi MAPE: (|At - Ft| / At) * 100%
                    // Hasil murni desimal komputer = 0.84%. Jika Ft dibulatkan manual (1347) = 0.90%
                    $nilaiMapeFix = round((abs($yAktualEvaluasi - $yPrediksiEvaluasi) / $yAktualEvaluasi) * 100, 2);
                } else {
                    $nilaiMapeFix = 0;
                }


                // Klasifikasi kriteria akurasi peramalan sesuai teks draf Word Anda
                if ($nilaiMapeFix < 10) {
                    $kriteria = 'Sangat Baik';
                } elseif ($nilaiMapeFix >= 10 && $nilaiMapeFix <= 20) {
                    $kriteria = 'Baik';
                } elseif ($nilaiMapeFix > 20 && $nilaiMapeFix <= 50) {
                    $kriteria = 'Cukup';
                } else {
                    $kriteria = 'Buruk';
                }

                // 5. Simpan atau perbarui langsung ke tabel prediksi
                DB::table('prediksi')->updateOrInsert(
                    [
                        'obat_id' => $obat->id,
                        'bulan_tahun_prediksi' => $bulanTarget
                    ],
                    [
                        'nilai_a' => $a,
                        'nilai_b' => $b,
                        'hasil_prediksi' => $proyeksiY,
                        'nilai_mape' => $nilaiMapeFix,
                        'kategori_mape' => $kriteria,
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            // Kirim notifikasi sukses pop-up di panel Filament
            Notification::make()
                ->title('Proses Prediksi Berhasil!')
                ->body('Kalkulasi Least Square & MAPE dinamis berhasil dijalankan secara konsisten.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Memproses Data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
