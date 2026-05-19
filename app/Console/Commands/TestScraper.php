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
            $kumpulanDataObat = $hasil['data'];

            if (empty($kumpulanDataObat)) {
                $this->warn('Aman, namun database tabel obat Anda saat ini kosong (tidak ada yang di-loop).');
            } else {
                $this->line('Hasil perolehan looping kode obat:');

                // Tampilkan ringkasan hasil loop di terminal agar rapi
                foreach ($kumpulanDataObat as $obatId => $data) {
                    // Ambil 150 karakter pertama saja dari HTML obat ini untuk pratinjau log terminal
                    $cuplikanHtml = substr($data['html'], 0, 150);

                    $this->info(" -> [Obat ID: {$obatId}] Kode: {$data['kode']}");
                    $this->line("    HTML Snippet: " . trim(preg_replace('/\s+/', ' ', $cuplikanHtml)) . "...");
                }
            }

            $this->info('--------------------------------------------------');
            $this->info('Kesimpulan: Script otomasi & looping database berjalan 100% Sempurna.');
        } else {
            $this->error(' STATUS: [GAGAL] Terjadi kendala saat proses otomatisasi.');
            $this->error('Pesan Error: ' . $hasil['error']);
        }

        return Command::SUCCESS;
    }
}
