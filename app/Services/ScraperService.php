<?php

namespace App\Services;

use App\Models\Obat;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Symfony\Component\Panther\Client;

class ScraperService
{
    /**
     * Melakukan Otomatisasi Login SIMRS via Headless Browser (Panther)
     * dan mengambil data HTML halaman tujuan.
     */
    public function ambilDataSimrs(string $username, string $password): array
    {
        // 1. Definisikan letak file biner chromedriver.exe hasil unduhan manual Anda
        $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] = base_path('chromedriver.exe');

        // bypass kendala hak akses sandbox lokal Windows Laragon
        $_SERVER['PANTHER_NO_SANDBOX'] = 1;

        // 2. Inisialisasi Instance Headless Chrome Client
        //$client = Client::createChromeClient();
        // --- NON-HEADLESS CONFIGURATION ---
        // Dengan memaksa nilainya menjadi kosong, Panther tidak akan mengirimkan parameter '--headless' ke Chrome Driver
        $_SERVER['PANTHER_NO_HEADLESS'] = 1;

        // 2. Inisialisasi Instance Chrome Client dengan argumen penunjang visual
        // Parameter kedua dikosongkan agar mematuhi aturan penonaktifan headless di atas
        $client = Client::createChromeClient(null, [
            '--start-maximized',           // Membuka jendela Chrome langsung dalam posisi penuh (Maximize)
            '--disable-gpu',               // Mematikan akselerasi hardware grafis agar enteng
            '--ignore-certificate-errors'  // Abaikan jika sertifikat SSL lokal IP rumah sakit bermasalah
        ]);
        try {
            // 3. Buka URL Utama Portal Login SIMRS
            $client->request('GET', 'http://10.20.111.33/');

            // 4. Amankan antrean: Tunggu hingga komponen input username ter-render di browser
            $client->waitFor('#Content1_ASPxRoundPanel1_txtUser_I');

            // --- PROSES OTOMATISASI FORM LOGIN (XPATH) ---

            // Langkah A: Temukan input field Username dan ketikkan nilainya
            $usernameField = $client->getWebDriver()->findElement(
                WebDriverBy::xpath('//*[@id="Content1_ASPxRoundPanel1_txtUser_I"]')
            );
            $usernameField->sendKeys($username);

            // Langkah B: Temukan input field Password dan ketikkan nilainya
            $passwordField = $client->getWebDriver()->findElement(
                WebDriverBy::xpath('//*[@id="Content1_ASPxRoundPanel1_txtPassword_I"]')
            );
            $passwordField->sendKeys($password);

            // Langkah C: Temukan Elemen Tombol/Button Sign In kemudian picu aksi Klik
            $signInButton = $client->getWebDriver()->findElement(
                WebDriverBy::xpath('//*[@id="Content1_ASPxRoundPanel1_btnLogin_CD"]')
            );
            $signInButton->click();

            // 5. Beri jeda 4 detik agar browser menyelesaikan handshake session cookie di server
            sleep(4);

            // 2. NAVIGASI KE HALAMAN REKAP / FILTER DATA OBAT
            $client->request('GET', 'http://10.20.111.33/Drug/StokItem.aspx');

            // Tunggu sampai field filter kode item muncul di layar browser
            $client->waitFor('#Content1_Content1_panelFilter_txtKodeItemFilter_I', 10);

            // 3. AMBIL DATA SEMUA OBAT DARI DATABASE APLIKASI ANDA
            $daftarObat = Obat::all();

            $hasilScrapingSemuaObat = [];

            // 4. PERULANGAN UNTUK MENGISI KODE OBAT OTOMATIS
            foreach ($daftarObat as $obat) {
                // Ambil kode obat dari properti database Anda (misal kolomnya bernama 'kode_obat' atau 'kd_obat')
                // SESUAIKAN 'kode_obat' di bawah ini dengan nama kolom asli di tabel obat Anda
                $kodeObatDatabase = $obat->kode_obat;

                // Temukan field filter kode obat di browser
                $kdObatField = $client->getWebDriver()->findElement(
                    WebDriverBy::xpath('//*[@id="Content1_Content1_panelFilter_txtKodeItemFilter_I"]')
                );

                // Bersihkan field filter terlebih dahulu sebelum mengetik kode baru (agar tidak menumpuk)
                // Karena DevExpress kadang kebal terhadap ->clear(), kita gunakan trik backspace/select all
                $kdObatField->sendKeys(WebDriverKeys::CONTROL . 'a');
                $kdObatField->sendKeys(WebDriverKeys::DELETE);

                // ISI OTOMATIS field dengan kode obat dari database saat ini
                $kdObatField->sendKeys($kodeObatDatabase);

            // Langkah C: Temukan Elemen Tombol/Button Sign In kemudian picu aksi Klik
            $filterButton = $client->getWebDriver()->findElement(
                WebDriverBy::xpath('//*[@id="Content1_Content1_panelFilter_btnTampilkan_CD"]')
            );
            $filterButton->click();


                // Beri jeda 2 detik agar website SIMRS selesai memuat data filter obat tersebut
                sleep(2);

                // Ambil HTML yang sudah terfilter khusus untuk obat ini
                $htmlPerObat = $client->refreshCrawler()->html();

                // Simpan hasilnya ke dalam array berdasarkan id obat
                $hasilScrapingSemuaObat[$obat->id] = [
                    'kode' => $kodeObatDatabase,
                    'html' => $htmlPerObat
                ];

                // Catatan Akademis Bab IV: Di titik ini Anda bisa langsung menyisipkan fungsi DOM Parser
                // untuk mengambil angka pemakaian bulanan obat tersebut dan menyimpannya ke tabel 'rekap_pemakaian_bulanan'
            }

            return [
                'status' => 'sukses',
                'data'   => $hasilScrapingSemuaObat
            ];

        } catch (\Exception $e) {
            // Tangkap pesan error jika XPath tidak ditemukan atau koneksi drop
            return [
                'status' => 'gagal',
                'error'  => $e->getMessage()
            ];
        } finally {
            // PENTING: Selalu matikan background process chrome agar memory RAM tidak membengkak
            $client->quit();
        }
    }
}
