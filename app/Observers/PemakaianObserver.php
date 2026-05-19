<?php

namespace App\Observers;

use App\Models\Pemakaian;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PemakaianObserver
{
    /**
     * Handle the Pemakaian "created" event.
     */
    public function created(Pemakaian $pemakaian): void
    {
        $this->sinkronisasiRekapBulanan($pemakaian->obat_id, $pemakaian->tanggal);
    }

    /**
     * Handle the Pemakaian "updated" event.
     */
    public function updated(Pemakaian $pemakaian): void
    {
        $this->sinkronisasiRekapBulanan($pemakaian->obat_id, $pemakaian->tanggal);

        // Jika user mengubah tanggal ke bulan yang berbeda, sinkronkan juga bulan lamanya
        if ($pemakaian->isDirty('tanggal')) {
            $tanggalLama = $pemakaian->getOriginal('tanggal');
            $this->sinkronisasiRekapBulanan($pemakaian->obat_id, $tanggalLama);
        }

        // Jika user mengubah jenis obatnya, sinkronkan juga obat lamanya
        if ($pemakaian->isDirty('obat_id')) {
            $obatLamaId = $pemakaian->getOriginal('obat_id');
            $this->sinkronisasiRekapBulanan($obatLamaId, $pemakaian->tanggal);
        }
    }

    /**
     * Handle the Pemakaian "deleted" event.
     */
    public function deleted(Pemakaian $pemakaian): void
    {
        $this->sinkronisasiRekapBulanan($pemakaian->obat_id, $pemakaian->tanggal);
    }

    /**
     * Inti Logika Sinkronisasi Agregasi ke Tabel Rekap
     */
    private function sinkronisasiRekapBulanan($obatId, $tanggal): void
    {
        if (!$obatId || !$tanggal) {
            return;
        }

        // Parsing tanggal menggunakan Carbon untuk mengambil bulan dan tahun target
        $carbonDate = Carbon::parse($tanggal);
        $bulanTahun = $carbonDate->format('Y-m'); // Format: 2026-01
        $tahun = $carbonDate->year;
        $bulan = $carbonDate->month;

        // Hitung total jumlah pemakaian ril dari seluruh baris transaksi untuk obat & bulan tersebut
        // Ganti 'pemakaians' dengan nama tabel transaksi utama Anda jika berbeda
        $totalTerbaru = DB::table('pemakaians')
            ->where('obat_id', $obatId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');

        if ($totalTerbaru > 0) {
            // Jika data transaksi masih ada, perbarui atau masukkan ke tabel rekap
            DB::table('rekap_pemakaian_bulanan')->updateOrInsert(
                [
                    'obat_id' => $obatId,
                    'bulan_tahun' => $bulanTahun
                ],
                [
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'total_jumlah' => $totalTerbaru,
                    'updated_at' => now()
                ]
            );
        } else {
            // Jika setelah dihapus/diubah ternyata total pemakaian bulan itu kosong (0), hapus baris rekapnya
            DB::table('rekap_pemakaian_bulanan')
                ->where('obat_id', $obatId)
                ->where('bulan_tahun', $bulanTahun)
                ->delete();
        }
    }
}
