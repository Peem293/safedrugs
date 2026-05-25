<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stock On Hands SafeDrugs</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .text-center { text-align: center; }
        .judul { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .sub-judul { font-size: 12px; margin-bottom: 15px; color: #555; }
        hr { border: 0; border-top: 2px dashed #000; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #666; padding: 6px 8px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    <div class="text-center judul">LAPORAN MONITORING STOCK ON HANDS OBAT</div>
    <div class="text-center sub-judul">APLIKASI SAFEDRUGS — TANGGAL CETAK: {{ $tanggal }}</div>
    <hr>

    <table class="table">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="12%">Kode Obat</th>
                <th>Nama Obat</th>
                <th width="12%">Batch No</th>
                <th width="12%">Expired Date</th>
                <th width="12%">Exp Status</th>
                <th width="12%">Stock On Hands</th>
                <th width="15%">Last Sync</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                @php
                    // 1. Ambil data stok asli sesuai nama field struktur tabel Filament Anda
                    $stokOnHand = $item->stock_on_hand ?? 0;
                    $kodeObat   = $item->obat->kode_obat ?? '-';
                    $namaObat   = $item->obat->nama_obat ?? 'Data Obat Terhapus';

                    // 2. Logika Penentuan Exp Status (Disamakan persis dengan StockOnhandsTable)
                    if (! $item->exp_date) {
                        $status = 'Tidak Ada Data';
                        $warnaStatus = '#6c757d'; // Abu-abu
                    } else {
                        $expDate = \Carbon\Carbon::parse($item->exp_date);
                        $now = \Carbon\Carbon::now();

                        if ($expDate->isPast()) {
                            $status = 'Expired';
                            $warnaStatus = '#dc3545'; // Merah
                        } else {
                            $diffInMonths = $now->diffInMonths($expDate, false);
                            if ($diffInMonths <= 6) {
                                $status = 'Soon Expired';
                                $warnaStatus = '#ffc107'; // Kuning / Oranye
                            } else {
                                $status = 'Aman';
                                $warnaStatus = '#198754'; // Hijau
                            }
                        }
                    }

                    // 3. Format Tanggal Sinkronisasi Scraper
                    $lastSync = $item->last_scraped_at ? \Carbon\Carbon::parse($item->last_scraped_at)->translatedFormat('d M Y H:i') : '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $kodeObat }}</td>
                    <td>{{ $namaObat }}</td>
                    <td class="text-center">{{ $item->batch_no ?? '-' }}</td>
                    <td class="text-center">
                        {{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->translatedFormat('M d, Y') : '-' }}
                    </td>
                    <td class="text-center" style="font-weight: bold; color: {{ $warnaStatus }};">
                        {{ $status }}
                    </td>
                    <td class="text-center" style="font-weight: bold;">
                        {{ number_format($stokOnHand, 0, ',', '.') }}
                    </td>
                    <td class="text-center" style="color: #555;">{{ $lastSync }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
