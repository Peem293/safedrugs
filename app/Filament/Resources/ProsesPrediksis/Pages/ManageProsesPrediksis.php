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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageProsesPrediksis extends ListRecords
{
    protected static string $resource = ProsesPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. TOMBOL MULAI PROSES PREDIKSI (TETAP SAMA)
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
                    session(['tampilkan_bulan_prediksi' => $bulanTarget]);

                    $apakahSudahAda = Prediksi::where('bulan_tahun_prediksi', $bulanTarget)->exists();

                    if (! $apakahSudahAda) {
                        static::jalankanKalkulasiOtomatis($bulanTarget);
                    } else {
                        Notification::make()
                            ->title('Data Ditemukan!')
                            ->body("Menampilkan data prediksi periode " . Carbon::parse($bulanTarget)->translatedFormat('F Y') . " langsung dari database (Tanpa hitung ulang).")
                            ->success()
                            ->send();
                    }
                }),

            // 2. TOMBOL CETAK PDF (PERBAIKAN FITUR OPEN IN NEW TAB FILAMENT)
            Action::make('cetakPdf')
                ->label('Cetak Hasil Prediksi (PDF)')
                ->icon('heroicon-m-printer')
                ->color('danger')
                // Tombol hanya muncul jika data bulan aktif ada di database
                ->visible(function () {
                    $bulanActive = session('tampilkan_bulan_prediksi');
                    if (! $bulanActive) {
                        return false;
                    }
                    return Prediksi::where('bulan_tahun_prediksi', $bulanActive)->exists();
                })
                // LANGKAH AMAN: Mengarahkan langsung ke URL route khusus cetak
                ->url(function () {
                    $bulanActive = session('tampilkan_bulan_prediksi');
                    return $bulanActive ? route('admin.prediksi.cetak', ['bulan' => $bulanActive]) : '#';
                })
                // Mencegah halaman utama refresh/kosong kembali & memaksa buka tab baru
                ->openUrlInNewTab(),
        ];
    }

    /**
     * CORE LOGIKA MATEMATIKA LEAST SQUARE & MAPE (TETAP SAMA SEPERTI SEBELUMNYA)
     */
    public static function jalankanKalkulasiOtomatis(string $bulanTarget): void
    {
        $daftarObat = Obat::all();
        $bulanHistoriTerakhir = '2026-03';

        $start = Carbon::parse($bulanHistoriTerakhir . '-01');
        $end = Carbon::parse($bulanTarget . '-01');
        $selisihBulan = $start->diffInMonths($end);

        $nilaiXTarget = 5 + ($selisihBulan * 2);
        $matriksX = [-5, -3, -1, 1, 3, 5];

        DB::beginTransaction();

        try {
            foreach ($daftarObat as $obat) {
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

                $proyeksiY = $a + ($b * $nilaiXTarget);
                $proyeksiY = $proyeksiY < 0 ? 0 : round($proyeksiY);

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
