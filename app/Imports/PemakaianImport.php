<?php

namespace App\Imports;

use App\Models\Obat;
use App\Models\Pemakaian;
use App\Models\RekapPemakaianBulanan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;

class PemakaianImport implements ToModel, WithHeadingRow, WithEvents
{
    // Tambahkan properti counter
    private $successCount = 0;
    private $duplicateCount = 0;

    /**
    * STAGE 1: EXTRACT & LOAD DENGAN COUNTER DE-DUPLIKASI
    */
    public function model(array $row)
    {
        $obat = Obat::where('kode_obat', $row['item_code'])->first();

        if (!$obat) {
            return null;
        }

        $tanggalFix = is_numeric($row['dispensed_date'])
            ? Date::excelToDateTimeObject($row['dispensed_date'])->format('Y-m-d')
            : Carbon::parse($row['dispensed_date'])->format('Y-m-d');

        $jumlahFix = $row['dispensed_qty'];

        // Cek duplikasi di database
        $dataDuplikat = Pemakaian::where('obat_id', $obat->id)
            ->whereDate('tanggal', $tanggalFix)
            ->where('jumlah', $jumlahFix)
            ->exists();

        if ($dataDuplikat) {
            $this->duplicateCount++; // Hitung sebagai data duplikat

            // Simpan statistik sementara ke session agar bisa dibaca setelah import selesai
            session(['import_duplicates' => $this->duplicateCount]);
            return null;
        }

        $this->successCount++;
        session(['import_success' => $this->successCount]);

        return new Pemakaian([
            'obat_id' => $obat->id,
            'tanggal' => $tanggalFix,
            'jumlah'  => $jumlahFix,
            'satuan'  => $obat->satuan,
        ]);
    }

    /**
     * STAGE 2: TRANSFORM & LOAD (Pre-Aggregation Bulanan - PostgreSQL)
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {

                $periodeTeridentifikasi = Pemakaian::select(
                        'obat_id',
                        DB::raw("TO_CHAR(tanggal, 'YYYY-MM') as bulan_tahun")
                    )
                    ->groupBy('obat_id', 'bulan_tahun')
                    ->get();

                foreach ($periodeTeridentifikasi as $item) {

                    $totalJumlahKeluar = Pemakaian::where('obat_id', $item->obat_id)
                        ->whereRaw("TO_CHAR(tanggal, 'YYYY-MM') = ?", [$item->bulan_tahun])
                        ->sum('jumlah');

                    $parts = explode('-', $item->bulan_tahun);
                    $tahun = $parts[0];
                    $bulan = $parts[1];

                    RekapPemakaianBulanan::updateOrCreate(
                        [
                            'obat_id'     => $item->obat_id,
                            'bulan_tahun' => $item->bulan_tahun,
                        ],
                        [
                            'bulan'        => $bulan,
                            'tahun'        => $tahun,
                            'total_jumlah' => $totalJumlahKeluar,
                        ]
                    );
                }
            },
        ];
    }
}
