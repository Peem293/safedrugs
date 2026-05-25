<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Prediksi SafeDrugs</title>
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

    <div class="text-center judul">LAPORAN HASIL PREDIKSI KEBUTUHAN OBAT</div>
    <div class="text-center sub-judul">APLIKASI SAFEDRUGS — PERIODE PREDIKSI: {{ $bulan }}</div>
    <hr>

    <table class="table">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th>Nama Obat</th>
                <th width="12%">Hasil Prediksi (Unit)</th>
                <th width="12%">Stok Saat Ini</th>
                <th width="14%">Stok Minimum (Buffer)</th>
                <th width="15%">Rekomendasi Pengadaan</th>
                <th width="12%">Status Stok</th>
                <th width="15%">Akurasi (MAPE)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                @php
                    // 1. Ambil data stok langsung dari nama field tabel master obatmu
                    $stokSekarang = $item->obat->stock ?? 0;
                    $stokMin      = $item->obat->min_stock ?? 0;

                    $hasilPrediksi = $item->hasil_prediksi;

                    // 2. PERBAIKAN RUMUS: Hasil Prediksi dikurangi Stok Riil saat ini (Kekurangan Stok)
                    $rekomendasi = max(0, $hasilPrediksi - $stokSekarang);

                    // 3. Logika Warna & Label Status Stok Peringatan
                    if ($stokSekarang <= 0) {
                        $status = 'Stock Out';
                        $warnaStatus = '#dc3545'; // Merah
                    } elseif ($stokSekarang <= $stokMin) {
                        $status = 'Limit';
                        $warnaStatus = '#fd7e14'; // Oranye
                    } else {
                        $status = 'Stok Aman';
                        $warnaStatus = '#198754'; // Hijau
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->obat->nama_obat ?? 'Data Obat Terhapus' }}</td>
                    <td class="text-center">{{ number_format($hasilPrediksi, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($stokSekarang, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($stokMin, 0, ',', '.') }}</td>
                    <td class="text-center" style="font-weight: bold; color: #0d6efd;">{{ number_format($rekomendasi, 0, ',', '.') }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $warnaStatus }};">{{ $status }}</td>
                    <td class="text-center">{{ $item->nilai_mape }}% ({{ $item->kategori_mape }})</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
