<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\StockOnhand;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class ScraperService
{
    /**
     * Mengambil data stok obat dari SIMRS Hermina Solo, menyimpannya per batch,
     * dan otomatis memperbarui total stok kumulatif di data master obat.
     * Mengatasi duplikasi nomor batch dengan akumulasi penjumlahan stok otomatis.
     */
    public function ambilDataSimrs(string $username, string $password): array
    {
        // Membebaskan batas waktu eksekusi PHP agar tidak timeout saat perulangan obat banyak
        set_time_limit(0);

        // 1. Jalankan ChromeDriver Laragon secara mandiri di background port 9515
        $chromeDriverPath = 'D:\\laragon\\www\\safedrugs\\chromedriver.exe';

        // Memastikan proses chromedriver lama ditutup terlebih dahulu agar tidak bentrok port
        exec('taskkill /F /IM chromedriver.exe 2>nul');

        // Jalankan biner driver baru di Windows background
        pclose(popen("start /B " . $chromeDriverPath . " --port=9515 > nul 2>&1", "r"));

        // Jeda 2 detik menjamin server driver siap menerima jabat tangan koneksi
        sleep(2);

        // 2. Susun konfigurasi opsi Google Chrome murni
        $options = new ChromeOptions();
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless=new',               // <--- INI KUNCI UTAMANYA: Berjalan di background tanpa GUI
            '--disable-gpu',                // Wajib di server Linux untuk menghemat resource
            '--ignore-certificate-errors',
            '--no-sandbox',                 // Wajib di Linux agar tidak error permission/root user
            '--disable-dev-shm-usage',      // Wajib di Linux agar Chrome tidak crash jika RAM server kecil
            '--disable-extensions',
            '--start-maximized'
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $hasilScrapingSemuaObat = [];

        try {
            // 3. Hubungkan RemoteWebDriver ke instansiasi ChromeDriver mandiri tadi
            $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

            // =================================================================
            // PROSES ALUR OTOMATISASI SIMRS HERMINA SOLO
            // =================================================================

            // A. Buka URL Utama Portal Login SIMRS
            $driver->get('http://10.20.111.33/');
            sleep(3);

            // B. Jalankan Pengisian Form Login
            $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_txtUser_I'))->sendKeys($username);
            $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_txtPassword_I'))->sendKeys($password);
            $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_btnLogin_CD'))->click();

            // Jeda tunggu pemrosesan session cookie server
            sleep(4);

            // C. Navigasi ke Halaman Manajemen Stok Item Obat
            $driver->get('http://10.20.111.33/Drug/StokItem.aspx');
            sleep(3);

            // D. Ambil Data Semua Obat Dari Database Lokal Aplikasi Anda
            $daftarObat = Obat::all();

            // E. Perulangan Otomatisasi Input Kode Obat
            foreach ($daftarObat as $obat) {
                $kodeObatDatabase = $obat->kode_obat;

                // Temukan field filter pencarian kode obat
                $kdObatField = $driver->findElement(WebDriverBy::id('Content1_Content1_panelFilter_txtKodeItemFilter_I'));

                // Bersihkan komponen filter bawaan DevExpress menggunakan trik keyboard aksi cepat
                $kdObatField->sendKeys(WebDriverKeys::CONTROL . 'a');
                $kdObatField->sendKeys(WebDriverKeys::DELETE);

                // Masukkan kode obat loop saat ini
                $kdObatField->sendKeys($kodeObatDatabase);

                // Klik Tombol Tampilkan data obat
                $driver->findElement(WebDriverBy::id('Content1_Content1_panelFilter_btnTampilkan_CD'))->click();

                // Jeda memberikan waktu bagi AJAX DevExpress untuk merender ulang baris tabel baru
                sleep(4);

                // F. Ekstraksi Data Tabel Menggunakan DomCrawler Symfony
                try {
                    $htmlSekarang = $driver->getPageSource();
                    $crawler = new Crawler($htmlSekarang);

                    // Menargetkan baris data (dxgvDataRow) yang berada di dalam kontainer #Content1_Content1_gridStok
                    $rows = $crawler->filter('#Content1_Content1_gridStok tr[id*="DXDataRow"]');

                    $batchDetails = [];

                    if ($rows->count() > 0) {

                        // REVISI: Bersihkan semua data batch lama khusus obat ini agar proses kalkulasi murni dimulai dari 0
                        StockOnhand::where('obat_id', $obat->id)->delete();

                        $rows->each(function ($row) use (&$batchDetails, $obat) {
                            $koloms = $row->filter('td');

                            // Pastikan jumlah kolom mencukupi sebelum membaca indeks kolom
                            if ($koloms->count() > 10) {
                                // Konversi Berbasis Kredensial XPath Riil (Indeks 0-based)
                                $stokRaw  = $koloms->eq(7)->text();  // td[8] -> Indeks 7 (On Hand Stock)
                                $expRaw   = $koloms->eq(9)->text();  // td[10] -> Indeks 9 (Expired Date)
                                $batchRaw = $koloms->eq(10)->text(); // td[11] -> Indeks 10 (Batch Number)

                                // --- PERBAIKAN FORMULASI ANGKA STOK DESIMAL ---
                                // 1. Hilangkan tanda koma pemisah ribuan (misal: 6,206.00 menjadi 6206.00)
                                $cleanStok = str_replace(',', '', trim($stokRaw));

                                // 2. Konversi ke float agar desimal terbaca benar, lalu bulatkan menjadi integer murni
                                $stokAngka = (int) floatval($cleanStok);

                                // Bersihkan string nomor batch dari spasi atau karakter kosong bawaan html
                                $cleanBatch = trim($batchRaw);

                                // Pastikan tidak memasukkan baris kosong atau teks petunjuk kosong
                                if ($cleanBatch !== '' && $cleanBatch !== 'Tidak ada data' && $cleanBatch !== '&nbsp;') {

                                    // 3. Konversi format tanggal SIMRS (DD/MM/YYYY) ke format standar database PostgreSQL (YYYY-MM-DD)
                                    $dateFormatted = null;
                                    try {
                                        $dateFormatted = Carbon::createFromFormat('d/m/Y', trim($expRaw))->format('Y-m-d');
                                    } catch (\Exception $e) {
                                        $dateFormatted = now()->format('Y-m-d'); // Fallback aman jika parsing error
                                    }

                                    // 4. REVISI UTAMA: Cek apakah pada baris iterasi sebelumnya di obat yang sama nomor batch ini sudah tersimpan
                                    $existingBatch = StockOnhand::where('obat_id', $obat->id)
                                        ->where('batch_no', $cleanBatch)
                                        ->first();

                                    if ($existingBatch) {
                                        // JIKA DUNEMUKAN DUPLIKASI BATCH: Lakukan Akumulasi Penjumlahan Stok
                                        $existingBatch->update([
                                            'stock_on_hand'   => $existingBatch->stock_on_hand + $stokAngka,
                                            'last_scraped_at' => now()
                                        ]);
                                    } else {
                                        // JIKA BATCH BARU: Jalankan insert baris record baru
                                        StockOnhand::create([
                                            'obat_id'         => $obat->id,
                                            'batch_no'        => $cleanBatch,
                                            'exp_date'        => $dateFormatted,
                                            'stock_on_hand'   => $stokAngka,
                                            'last_scraped_at' => now()
                                        ]);
                                    }

                                    $batchDetails[] = [
                                        'batch_number' => $cleanBatch,
                                        'expired_date' => trim($expRaw),
                                        'stok_simrs'   => $stokAngka
                                    ];
                                }
                            }
                        });

                        // 5. AUTOMATIC KUMULATIF UPDATE: Hitung total stok dari semua batch obat saat ini
                        $totalStokKumulatif = StockOnhand::where('obat_id', $obat->id)->sum('stock_on_hand');

                        // 6. Jalankan sinkronisasi update nilai ke tabel master obats
                        $obat->update([
                            'stock' => $totalStokKumulatif
                        ]);
                    } else {
                        // Jika obat tidak memiliki sisa batch aktif di SIMRS, hapus batch lokal dan set master lokal menjadi 0
                        StockOnhand::where('obat_id', $obat->id)->delete();
                        $obat->update([
                            'stock' => 0
                        ]);
                    }

                    // Tambahkan hasil iterasi obat saat ini ke array utama untuk return API / log
                    $hasilScrapingSemuaObat[] = [
                        'id_obat_lokal' => $obat->id,
                        'kode_obat'     => $kodeObatDatabase,
                        'nama_obat'     => $obat->nama_obat,
                        'jumlah_batch'  => count($batchDetails),
                        'data_batch'    => $batchDetails
                    ];

                } catch (\InvalidArgumentException $e) {
                    $hasilScrapingSemuaObat[] = [
                        'id_obat_lokal' => $obat->id,
                        'kode_obat'     => $kodeObatDatabase,
                        'nama_obat'     => $obat->nama_obat,
                        'status'        => 'Gagal Parsing',
                        'error'         => $e->getMessage()
                    ];
                }
            }

            // Tutup sesi browser secara bersih setelah seluruh perulangan rampung
            $driver->quit();
            exec('taskkill /F /IM chromedriver.exe 2>nul');

            return [
                'status'    => 'sukses',
                'timestamp' => now()->toIso8601String(),
                'results'   => $hasilScrapingSemuaObat
            ];

        } catch (\Exception $e) {
            // Amankan penutupan driver jika skrip mengalami kegagalan/interupsi di tengah jalan
            if (isset($driver)) {
                $driver->quit();
            }
            exec('taskkill /F /IM chromedriver.exe 2>nul');

            return [
                'status' => 'gagal',
                'error'  => $e->getMessage()
            ];
        }
    }
}