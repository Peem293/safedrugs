<?php

namespace App\Console\Commands;

use App\Services\ScraperService;
use Illuminate\Console\Command;

class TestScraper extends Command
{
    protected $signature = 'simrs:test-scraper';
    protected $description = 'Melakukan uji coba login dan scraping otomatisasi berbasis database';

    public function handle(ScraperService $scraper)
    {
        $this->info('==================================================');
        $this->info(' Memulai Uji Coba Scraping SIMRS via Panther...   ');
        $this->info('==================================================');

        $username = '020150702';
        $password = 'Nuel.1310'; // Sesuaikan password riil Anda

        $this->warn("Mencoba login dengan Username: {$username}...");

        // Mengeksekusi service scraper
        $hasil = $scraper->ambilDataSimrs($username, $password);

        if ($hasil['status'] === 'sukses') {
            $this->info(' STATUS: [SUKSES] Browser berhasil login dan memfilter data obat!');
            $this->info('--------------------------------------------------');

            // Mengambil hasil array perulangan data obat
            $kumpulanDataObat = $hasil['results'] ?? [];

            if (empty($kumpulanDataObat)) {
                $this->warn('Aman, namun database tabel obat Anda saat ini kosong (tidak ada yang di-loop).');
            } else {
                $this->line('Hasil perolehan looping kode obat:');

                // Tampilkan ringkasan hasil loop di terminal agar rapi
foreach ($kumpulanDataObat as $data) {
                    // Skip entries that represent an error (contain a 'status' key)
                    if (isset($data['status'])) {
                        $this->warn('⚠️ Obat gagal diproses: ' . ($data['error'] ?? 'Tidak diketahui'));
                        continue;
                    }

                    $obatId = $data['id_obat_lokal'] ?? ($data['obat_id'] ?? 'N/A');
                    $kode   = $data['kode_obat'] ?? 'N/A';
                    $nama   = $data['nama_obat'] ?? 'N/A';

                    $this->info(" -> [Obat ID: {$obatId}] Kode: {$kode} | Nama: {$nama}");
                    $this->line('    Jumlah Batch: ' . ($data['jumlah_batch'] ?? 0));
                    if (!empty($data['data_batch'])) {
                        foreach ($data['data_batch'] as $batch) {
                            $this->line("      - Batch: {$batch['batch_number']} | Exp: {$batch['expired_date']} | Stok: {$batch['stok_simrs']}");
                        }
                    }
                }
            }

            $this->info('--------------------------------------------------');
            $this->info('Kesimpulan: Script otomasi & looping database berjalan 100% Sempurna.');
        } else {
            $this->error(' STATUS: [GAGAL] Terjadi kendala saat proses otomatisasi.');
            if (!empty($hasil['errors'])) {
                $this->error('Detail error:');
                foreach ($hasil['errors'] as $err) {
                    $this->error(' - ' . ($err['error'] ?? json_encode($err)));
                }
            } elseif (!empty($hasil['error'])) {
                $this->error('Pesan Error: ' . $hasil['error']);
            } else {
                $this->error('Pesan Error: (tidak tersedia)');
            }
        }

        return Command::SUCCESS;
    }
}
