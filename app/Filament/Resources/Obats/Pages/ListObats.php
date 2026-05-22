<?php

namespace App\Filament\Resources\Obats\Pages;

use App\Filament\Resources\Obats\ObatResource;
use App\Imports\ObatImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
//use Illuminate\Support\Facades\Http;
//use App\Models\Obat;

class ListObats extends ListRecords
{
    protected static string $resource = ObatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // 1. TOMBOL IMPORT EXCEL (Aktif Utama)
            Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->required()
                        ->preserveFilenames()
                        ->disk('local')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ]),
                ])
                ->action(function (array $data) {
                    $filename = $data['file'];
                    if (Storage::disk('local')->exists($filename)) {
                        $filePath = Storage::disk('local')->path($filename);
                        Excel::import(new ObatImport, $filePath);
                        Storage::disk('local')->delete($filename);
                    }
                })
                ->successNotificationTitle('Import data obat berhasil!')
                ->after(function () {
                    $this->redirect(static::$resource::getUrl('index'));
                }),

            // 2. TOMBOL KALKULASI MANUAL BUFFER STOCK (Baru untuk Pengujian/Sidang)
            Action::make('kalkulasiBuffer')
                ->label('Kalkulasi Otomatis Buffer')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->requiresConfirmation() // Memberikan pop-up konfirmasi sebelum eksekusi
                ->modalHeading('Kalkulasi Ulang Buffer & Min Stock')
                ->modalDescription('Sistem akan menghitung ulang nilai Safety Stock dan Reorder Point secara dinamis berdasarkan tren pemakaian 6 bulan terakhir. Lanjutkan?')
                ->modalSubmitActionLabel('Mulai Hitung')
                ->action(function () {
                    try {
                        // Memanggil console command 'buffer:automate' yang sudah kita buat sebelumnya
                        Artisan::call('buffer:automate');

                        // Memunculkan notifikasi sukses pop-up di kanan atas Filament
                        Notification::make()
                            ->title('Kalkulasi Berhasil!')
                            ->body('Nilai buffer stock dan min stock seluruh produk telah disesuaikan dengan tren pemakaian terbaru.')
                            ->success()
                            ->send();

                        // Refresh halaman agar angka barunya langsung muncul di tabel master obat
                        $this->redirect(static::$resource::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Kalkulasi Gagal')
                            ->body('Terjadi kendala sistem: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            /* ====================================================================
            2. TOMBOL SINKRONISASI SCRAPING (DI-NONAKTIFKAN UNTUK PEMBARUAN FITUR NANTI)
            ====================================================================
            Action::make('syncSimrs')
                ->label('Sinkronisasi SIMRS (Scraping)')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Web Scraping SIMRS')
                ->modalDescription('Sistem akan langsung mengekstrak data dari tampilan halaman web SIMRS Hermina tanpa dependensi tambahan. Lanjutkan?')
                ->modalSubmitActionLabel('Mulai Scraping')
                ->action(function () {
                    try {
                        // --- LANGKAH 1: SIMULASI LOGIN & RETRIEVE COOKIE ---
                        $loginResponse = Http::asForm()->post('http://10.20.111.33/', [
                            'username' => '02',
                            'password' => '',
                        ]);

                        $cookies = $loginResponse->cookies();

                        // --- LANGKAH 2: REQUEST HALAMAN MASTER STOK ---
                        $webResponse = Http::withCookies($cookies, 'simrs.hermina.local')
                            ->get('http://10.20.111.33/Drug/StokItem.aspx');

                        if ($webResponse->failed()) {
                            throw new \Exception('Gagal memuat halaman data sediaan SIMRS.');
                        }

                        $htmlContent = $webResponse->body();

                        // --- LANGKAH 3: DOM PARSING HTML MURNI ---
                        $dom = new \DOMDocument();
                        @$dom->loadHTML($htmlContent);
                        $xpath = new \DOMXPath($dom);

                        $rows = $xpath->query('//table/tbody/tr');

                        $countUpsert = 0;
                        $processedCodes = [];

                        foreach ($rows as $row) {
                            $cols = $xpath->query('.//td', $row);

                            $itemCode = $cols->item(1) ? trim($cols->item(1)->nodeValue) : null;
                            $itemName = $cols->item(2) ? trim($cols->item(2)->nodeValue) : null;
                            $unit     = $cols->item(3) ? trim($cols->item(3)->nodeValue) : '-';
                            $stockRaw = $cols->item(7) ? trim($cols->item(7)->nodeValue) : '0';

                            if (empty($itemCode) || empty($itemName)) {
                                continue;
                            }

                            if (in_array($itemCode, $processedCodes)) {
                                continue;
                            }
                            $processedCodes[] = $itemCode;

                            $cleanStock = str_replace('.', '', $stockRaw);
                            $cleanStock = str_replace(',', '.', $cleanStock);

                            Obat::updateOrCreate(
                                ['kode_obat' => $itemCode],
                                [
                                    'nama_obat' => $itemName,
                                    'satuan'    => $unit,
                                    'stock'     => (float) $cleanStock,
                                ]
                            );

                            $countUpsert++;
                        }

                        Notification::make()
                            ->title('Scraping HTML Berhasil')
                            ->body("Berhasil menyelaraskan {$countUpsert} data master obat dari web SIMRS.")
                            ->success()
                            ->send();

                        $this->redirect(static::$resource::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Ekstraksi')
                            ->body('Terjadi kendala parsing dokumen: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ====================================================================
            */
        ];
    }
}
