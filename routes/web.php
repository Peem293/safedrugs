<?php

//use App\Http\Controllers\TestScraperControllerJson;
//use App\Services\ScraperService;
//use App\Filament\Resources\ProsesPrediksis\Pages\ManageProsesPrediksis;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Prediksi;
use App\Models\StockOnhand;


Route::get('/', function () {
    return filament('admin/login');
});

// Route untuk menangani cetak PDF secara Report Prediksi
Route::get('/admin/proses-prediksis/cetak/{bulan}', function ($bulan) {
    // 1. Ambil data prediksi beserta relasi tabel obat
    $hasilPrediksi = Prediksi::with('obat')
                        ->where('bulan_tahun_prediksi', $bulan)
                        ->get();

    if ($hasilPrediksi->isEmpty()) {
        return "Data prediksi untuk periode " . Carbon::parse($bulan)->translatedFormat('F Y') . " tidak ditemukan.";
    }

    // 2. Konversi format "2026-04" menjadi nama teks "April 2026"
    // set lokalisasi ke bahasa Indonesia agar nama bulan otomatis berformat Indonesia
    Carbon::setLocale('id');
    $bulanTeks = Carbon::parse($bulan)->translatedFormat('F Y');

    $data = [
        'bulan' => $bulanTeks, // refactor format tanggal "April 2026" ke template blade
        'data' => $hasilPrediksi
    ];

    // 3. Render HTML ke DomPDF dengan orientasi kertas Landscape (tidur)
    $pdf = Pdf::loadView('pdf.laporan-prediksi', $data)->setPaper('a4', 'landscape');

    // Format penamaan file otomatis saat user klik tombol Download di browser
    // Nilai variabel $bulan akan dikonversi menjadi nama bulan bahasa Inggris/Indonesia (Contoh: "April 2026")
    $namaFileOtomatis = 'forcasting Report ' . $bulanTeks;

    // 4. Stream langsung ke browser tab halaman cetak preview otomatis
    return response($pdf->output())
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="' . $namaFileOtomatis . '.pdf"');
})->name('admin.prediksi.cetak')->middleware(['auth']);

 // Route Cetak aporan StockOnHands
Route::get('/admin/stock-onhands/cetak', function () {
    //Ambil data stock on hand lengkap beserta relasi datatable obatnya
    $stockData = StockOnhand::with('obat')
                    ->orderBy('exp_date', 'asc')
                    ->get();

    if ($stockData->isEmpty()) {
        return "Data Stock On Hands tidak ditemukan atau masih kosong.";
    }

    Carbon::setLocale('id');
    $tanggalCetak = Carbon::now()->translatedFormat('d F Y');

    $data = [
        'tanggal' => $tanggalCetak,
        'data' => $stockData
    ];

    $pdf = Pdf::loadView('pdf.laporan-stock-onhands', $data)->setPaper('a4', 'landscape');

    $namaFileOtomatis = 'Stock On Hands Report ' . $tanggalCetak;

    return response($pdf->output())
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="' . $namaFileOtomatis . '.pdf"');
})->name('admin.stock-onhands.cetak')->middleware(['auth']);

// Route::get('/test-scraper', function (ScraperService $scraper) {
    // 1. Jalankan fungsi scraper dengan kredensial Anda
   // $hasil = $scraper->ambilDataSimrs('020150702', 'Nuel.1310');

    // 2. Tampilkan langsung ke layar browser dalam format JSON murni
   // return response()->json($hasil);
//});
