<?php

namespace App\Filament\Resources\ProsesPrediksis\Pages;

use App\Filament\Resources\ProsesPrediksis\ProsesPrediksiResource;
use App\Models\Obat;
use App\Models\Prediksi;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
//use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageProsesPrediksis extends ListRecords
{
    protected static string $resource = ProsesPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prosesPrediksiModal')
                ->label('Mulai Proses Prediksi')
                ->icon('heroicon-m-cpu-chip')
                ->color('warning')
                ->modalHeading('Kalkulasi Prediksi Least Square & MAPE')
                ->modalDescription('Silakan tentukan bulan target yang ingin dihitung atau ditampilkan peramalannya.')
                ->form([
                    Select::make('bulan_target')
                        ->label('Pilih Bulan Target')
                        ->options(function () {
                            $options = [];
                            for ($bulan = 1; $bulan <= 12; $bulan++) {
                                $date = Carbon::create(2026, $bulan, 1);
                                $options[$date->format('Y-m')] = $date->translatedFormat('F Y');
                            }
                            return $options;
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $bulanTarget = $data['bulan_target'];

                    // Simpan pilihan bulan saat ini ke dalam Session agar tabel tahu apa yang harus ditampilkan
                    session(['tampilkan_bulan_prediksi' => $bulanTarget]);

                    // Cek apakah data bulan tersebut sudah pernah dihitung sebelumnya di database
                    $apakahSudahAda = Prediksi::where('bulan_tahun_prediksi', $bulanTarget)->exists();

                    if (! $apakahSudahAda) {
                        // Jika BELUM ADA, jalankan core logika matematika utama
                        static::jalankanKalkulasiOtomatis($bulanTarget);
                    } else {
                        // Jika SUDAH ADA, lewati perhitungan dan langsung beri notifikasi sukses muat data
                        Notification::make()
                            ->title('Data Ditemukan!')
                            ->body("Menampilkan data prediksi periode " . Carbon::parse($bulanTarget)->translatedFormat('F Y') . " langsung dari database (Tanpa hitung ulang).")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * CORE LOGIKA MATEMATIKA LEAST SQUARE & MAPE (DINAMIS AKUMULASI X)
     */
    public static function jalankanKalkulasiOtomatis(string $bulanTarget): void
    {
        $daftarObat = Obat::all();

        // 1. TENTUKAN BATAS AKHIR DATA HISTORIS RIIL (Maret 2026)
        // Dikunci pada data riil terakhir agar titik pusat (origin) analisis tidak rusak oleh data prediksi
        $bulanHistoriTerakhir = '2026-03';

        // 2. HITUNG JARAK BULAN SECARA DINAMIS (AKUMULASI +2 KARENA MATRIKS GANJIL KELIPATAN 2)
        $start = Carbon::parse($bulanHistoriTerakhir . '-01');
        $end = Carbon::parse($bulanTarget . '-01');
        $selisihBulan = $start->diffInMonths($end);

        // Pola Perubahan Nilai X Target:
        // Jika target April (selisih 1 bulan dari Maret) -> X = 5 + (1 * 2) = 7
        // Jika target Mei   (selisih 2 bulan dari Maret) -> X = 5 + (2 * 2) = 9
        // Jika target Juni  (selisih 3 bulan dari Maret) -> X = 5 + (3 * 2) = 11
        $nilaiXTarget = 5 + ($selisihBulan * 2);

        $matriksX = [-5, -3, -1, 1, 3, 5];

        DB::beginTransaction();

        try {
            foreach ($daftarObat as $obat) {
                // Ambil 6 bulan data historis murni (Mundur dari Maret 2026 ke belakang)
                $historiBulanan = DB::table('rekap_pemakaian_bulanan')
                    ->select('bulan_tahun', DB::raw("SUM(total_jumlah) as total_pakai"))
                    ->where('obat_id', $obat->id)
                    ->where('bulan_tahun', '<=', $bulanHistoriTerakhir)
                    ->groupBy('bulan_tahun')
                    ->orderBy('bulan_tahun', 'desc')
                    ->take(6)
                    ->get()
                    ->reverse()
                    ->values();

                if ($historiBulanan->count() < 6) {
                    continue;
                }

                // Kalkulasi Nilai Sigma Least Square
                $sumY = 0; $sumXY = 0; $sumX2 = 0;
                for ($i = 0; $i < 6; $i++) {
                    $y = (float) $historiBulanan[$i]->total_pakai;
                    $x = $matriksX[$i];

                    $sumY += $y;
                    $sumXY += ($x * $y);
                    $sumX2 += ($x * $x);
                }

                $a = $sumY / 6;
                $b = $sumX2 > 0 ? ($sumXY / $sumX2) : 0;

                // Hasil Prediksi menggunakan Nilai X Target dinamis yang telah berakumulasi (+2 setiap bulan)
                $proyeksiY = $a + ($b * $nilaiXTarget);
                $proyeksiY = $proyeksiY < 0 ? 0 : round($proyeksiY);

                // KALKULASI MAPE EVALUASI (Menguji keakuratan internal pada data riil Maret)
                $yAktualEvaluasi = (float) $historiBulanan[5]->total_pakai;
                $dataHistoriMape = $historiBulanan->slice(0, 5)->values();

                $sumYEvaluasi = 0; $sumXYEvaluasi = 0; $sumX2Evaluasi = 0;
                $matriksXEvaluasi = [-2, -1, 0, 1, 2];

                for ($i = 0; $i < 5; $i++) {
                    $yEval = (float) $dataHistoriMape[$i]->total_pakai;
                    $xEval = $matriksXEvaluasi[$i];

                    $sumYEvaluasi += $yEval;
                    $sumXYEvaluasi += ($xEval * $yEval);
                    $sumX2Evaluasi += ($xEval * $xEval);
                }

                $aEvaluasi = $sumYEvaluasi / 5;
                $bEvaluasi = $sumX2Evaluasi > 0 ? ($sumXYEvaluasi / $sumX2Evaluasi) : 0;
                $xTargetEvaluasi = 3;

                $yPrediksiEvaluasi = $aEvaluasi + ($bEvaluasi * $xTargetEvaluasi);

                if ($yAktualEvaluasi > 0) {
                    $nilaiMapeFix = round((abs($yAktualEvaluasi - $yPrediksiEvaluasi) / $yAktualEvaluasi) * 100, 2);
                } else {
                    $nilaiMapeFix = 0;
                }

                if ($nilaiMapeFix < 10) {
                    $kriteria = 'Sangat Baik';
                } elseif ($nilaiMapeFix >= 10 && $nilaiMapeFix <= 20) {
                    $kriteria = 'Baik';
                } elseif ($nilaiMapeFix > 20 && $nilaiMapeFix <= 50) {
                    $kriteria = 'Cukup';
                } else {
                    $kriteria = 'Buruk';
                }

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
                        'user_id' => Auth::id() ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            Notification::make()
                ->title('Kalkulasi Selesai!')
                ->body("Sistem berhasil memproses peramalan akumulatif untuk periode " . Carbon::parse($bulanTarget)->translatedFormat('F Y') . ".")
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Melakukan Prediksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
