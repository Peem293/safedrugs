<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\StockOnhand;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScraperService
{
    /** Restart browser setiap N obat untuk menjaga RAM tetap stabil */
    private const CHUNK_SIZE = 50;

    /** Maksimum detik menunggu elemen di halaman (Explicit Wait) */
    private const WAIT_TIMEOUT = 15;

    /**
     * Entry point utama: proses semua obat dalam chunk
     * sehingga Chrome di‑restart secara berkala dan RAM tidak bocor.
     */
    public function ambilDataSimrs(string $username, string $password): array
    {
        set_time_limit(0);

        $daftarObat = Obat::all();
        $hasilSemua = [];
        $errors     = [];
        $chunks     = $daftarObat->chunk(self::CHUNK_SIZE);

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkResult = $this->prosesChunk($chunk, $username, $password, $chunkIndex);

            foreach ($chunkResult as $r) {
                if (isset($r['status'])) {
                    $errors[] = $r;
                }
            }

            $hasilSemua = array_merge($hasilSemua, $chunkResult);
        }

        $status = empty($errors) ? 'sukses' : 'gagal';

        return [
            'status'    => $status,
            'timestamp' => now()->toIso8601String(),
            'results'   => $hasilSemua,
            'errors'    => $errors,
        ];
    }

    // =========================================================================
    // PRIVATE: Proses satu chunk obat dengan satu instance browser
    // =========================================================================

    private function prosesChunk($obatChunk, string $username, string $password, int $chunkIndex): array
    {
        $hasil  = [];
        $driver = null;

        try {
            $driver = $this->bukaDriver();
            $this->loginDanNavigasi($driver, $username, $password);

            foreach ($obatChunk as $obat) {
                $hasil[] = $this->scraperSatuObat($driver, $obat);
            }
        } catch (\Throwable $e) {
            $hasil[] = [
                'status' => 'chunk_error',
                'chunk'  => $chunkIndex,
                'error'  => $e->getMessage(),
            ];
            Log::error('Chunk '.$chunkIndex.' gagal: ' . $e);
        } finally {
            if ($driver) {
                try { $driver->quit(); } catch (\Exception $ignored) {}
            }
            exec('taskkill /F /IM chromedriver.exe 2>nul');
            sleep(2);
        }

        return $hasil;
    }

    // =========================================================================
    // PRIVATE: Inisialisasi ChromeDriver baru
    // =========================================================================

    private function bukaDriver(): RemoteWebDriver
    {
        $chromeDriverPath = 'D:\\laragon\\www\\safedrugs\\chromedriver.exe';

        exec('taskkill /F /IM chromedriver.exe 2>nul');
        pclose(popen("start /B {$chromeDriverPath} --port=9515 > nul 2>&1", "r"));
        sleep(2); // tunggu chromedriver siap

        $options = new ChromeOptions();
        $options->addArguments([
            '--headless=new',
            '--disable-gpu',
            '--ignore-certificate-errors',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--window-size=1280,800',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        return RemoteWebDriver::create('http://localhost:9515', $capabilities, 10000, 30000);
    }

    // =========================================================================
    // PRIVATE: Login & navigasi ke halaman Stok Item SIMRS
    // =========================================================================

    private function loginDanNavigasi(RemoteWebDriver $driver, string $username, string $password): void
    {
        $driver->get('http://10.20.111.33/');

        $driver->wait(self::WAIT_TIMEOUT)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::id('Content1_ASPxRoundPanel1_txtUser_I')
            )
        );

        $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_txtUser_I'))->sendKeys($username);
        $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_txtPassword_I'))->sendKeys($password);
        $driver->findElement(WebDriverBy::id('Content1_ASPxRoundPanel1_btnLogin_CD'))->click();

        $driver->wait(self::WAIT_TIMEOUT)->until(
            WebDriverExpectedCondition::urlContains('10.20.111.33')
        );

        $driver->get('http://10.20.111.33/Drug/StokItem.aspx');

        $driver->wait(self::WAIT_TIMEOUT)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::id('Content1_Content1_panelFilter_txtKodeItemFilter_I')
            )
        );
    }

    // =========================================================================
    // PRIVATE: Scrape & simpan data satu obat
    // =========================================================================

    private function scraperSatuObat(RemoteWebDriver $driver, Obat $obat): array
    {
        $kodeObat = $obat->kode_obat;

        try {
            // Tunggu hingga field filter pencarian kode obat clickable
            $driver->wait(self::WAIT_TIMEOUT)->until(
                WebDriverExpectedCondition::elementToBeClickable(
                    WebDriverBy::id('Content1_Content1_panelFilter_txtKodeItemFilter_I')
                )
            );

            // 1️⃣ Temukan field filter pencarian kode obat
            $field = $driver->findElement(
                WebDriverBy::id('Content1_Content1_panelFilter_txtKodeItemFilter_I')
            );

            // Bersihkan komponen filter bawaan DevExpress menggunakan trik keyboard aksi cepat
            $field->sendKeys(WebDriverKeys::CONTROL . 'a');
            $field->sendKeys(WebDriverKeys::DELETE);
            $field->sendKeys($kodeObat);

            // 2️⃣ Klik tombol tampilkan
            $driver->findElement(
                WebDriverBy::id('Content1_Content1_panelFilter_btnTampilkan_CD')
            )->click();

            // 3️⃣ Tunggu AJAX selesai dengan validasi kode obat di tabel hasil
            //    Polling hingga baris pertama mengandung kode obat yang benar,
            //    atau tabel benar-benar kosong (empty row muncul).
            $maxAttempts = 4;
            $rows = null;
            $validated = false;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                // Jeda progresif: 4s, 3s, 3s, 3s
                sleep($attempt === 1 ? 4 : 3);

                $crawler = new Crawler($driver->getPageSource());
                $rows = $crawler->filter('#Content1_Content1_gridStok tr[id*="DXDataRow"]');

                if ($rows->count() === 0) {
                    // Tidak ada data → cek apakah empty row muncul (tabel benar-benar kosong)
                    $emptyRow = $crawler->filter('#Content1_Content1_gridStok_DXEmptyRow');
                    if ($emptyRow->count() > 0) {
                        $validated = true; // Tabel memang kosong untuk obat ini
                        break;
                    }
                    // Belum ada empty row dan belum ada data → AJAX mungkin belum selesai, retry
                    Log::info("Obat {$kodeObat}: Attempt {$attempt} - tabel belum siap, retry...");
                    continue;
                }

                // Ada baris data → validasi kode obat di kolom 1 (index 1)
                $firstRow = $rows->first();
                $cols = $firstRow->filter('td');
                if ($cols->count() > 1) {
                    $kodeObatDiTabel = trim($cols->eq(1)->text());
                    if ($kodeObatDiTabel === $kodeObat) {
                        $validated = true; // Data benar untuk obat ini
                        break;
                    }
                    // Kode obat tidak cocok → masih data lama, retry
                    Log::warning("Obat {$kodeObat}: Attempt {$attempt} - tabel masih menampilkan data obat lama ({$kodeObatDiTabel}), retry...");
                    continue;
                }

                // Fallback: kolom tidak cukup, anggap valid
                $validated = true;
                break;
            }

            if (!$validated) {
                Log::error("Obat {$kodeObat}: Gagal validasi setelah {$maxAttempts} percobaan, data mungkin tidak akurat.");
                // Skip obat ini agar tidak menyimpan data yang salah
                return [
                    'id_obat_lokal' => $obat->id,
                    'kode_obat'     => $kodeObat,
                    'nama_obat'     => $obat->nama_obat,
                    'status'        => 'Gagal Validasi',
                    'error'         => 'Tabel SIMRS tidak merespons dengan data yang benar setelah beberapa percobaan.',
                ];
            }

            // 4️⃣ Simpan data batch yang sudah tervalidasi
            $batchDetails = [];

            if ($rows !== null && $rows->count() > 0) {
                // Hapus data lama untuk obat ini (reset)
                StockOnhand::where('obat_id', $obat->id)->delete();

                $rows->each(function ($row) use (&$batchDetails, $obat, $kodeObat) {
                    $cols = $row->filter('td');
                    if ($cols->count() > 10) {
                        // Validasi tambahan: pastikan setiap baris benar-benar milik obat ini
                        $kodeObatBaris = trim($cols->eq(1)->text());
                        if ($kodeObatBaris !== $kodeObat) {
                            return; // Skip baris yang bukan milik obat ini
                        }

                        $stokRaw  = $cols->eq(7)->text();
                        $expRaw   = $cols->eq(9)->text();
                        $batchRaw = $cols->eq(10)->text();

                        $stokAngka  = (int) floatval(str_replace(',', '', trim($stokRaw)));
                        $cleanBatch = trim($batchRaw);

                        if ($cleanBatch !== '' && $cleanBatch !== 'Tidak ada data' && $cleanBatch !== '&nbsp;') {
                            try {
                                $expDate = Carbon::createFromFormat('d/m/Y', trim($expRaw))->format('Y-m-d');
                            } catch (\Exception $e) {
                                $expDate = now()->format('Y-m-d');
                            }

                            $existing = StockOnhand::where('obat_id', $obat->id)
                                ->where('batch_no', $cleanBatch)->first();

                            if ($existing) {
                                $existing->update([
                                    'stock_on_hand'   => $existing->stock_on_hand + $stokAngka,
                                    'last_scraped_at' => now(),
                                ]);
                            } else {
                                StockOnhand::create([
                                    'obat_id'         => $obat->id,
                                    'batch_no'        => $cleanBatch,
                                    'exp_date'        => $expDate,
                                    'stock_on_hand'   => $stokAngka,
                                    'last_scraped_at' => now(),
                                ]);
                            }

                            $batchDetails[] = [
                                'batch_number' => $cleanBatch,
                                'expired_date' => trim($expRaw),
                                'stok_simrs'   => $stokAngka,
                            ];
                        }
                    }
                });

                // Update total stok master
                $obat->update([
                    'stock' => StockOnhand::where('obat_id', $obat->id)->sum('stock_on_hand'),
                ]);
            } else {
                // Tidak ada baris – bersihkan data lama
                StockOnhand::where('obat_id', $obat->id)->delete();
                $obat->update(['stock' => 0]);
            }

            return [
                'id_obat_lokal' => $obat->id,
                'kode_obat'     => $kodeObat,
                'nama_obat'     => $obat->nama_obat,
                'jumlah_batch'  => count($batchDetails),
                'data_batch'    => $batchDetails,
            ];
        } catch (\Throwable $e) {
            Log::error('Scraper gagal untuk obat '.$kodeObat.': '.$e->getMessage());

            return [
                'id_obat_lokal' => $obat->id,
                'kode_obat'     => $kodeObat,
                'nama_obat'     => $obat->nama_obat,
                'status'        => 'Gagal Parsing',
                'error'         => $e->getMessage(),
            ];
        }
    }
}
