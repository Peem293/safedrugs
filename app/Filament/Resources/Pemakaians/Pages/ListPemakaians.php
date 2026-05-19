<?php

namespace App\Filament\Resources\Pemakaians\Pages;

use App\Filament\Resources\Pemakaians\PemakaianResource;
use App\Imports\PemakaianImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification; // Import class Notification

class ListPemakaians extends ListRecords
{
    protected static string $resource = PemakaianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('import')
                ->label('Import Pemakaian')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ]),
                ])
                ->action(function (array $data) {
                    // 1. Bersihkan session sebelum memulai proses import baru
                    session()->forget(['import_success', 'import_duplicates']);

                    // 2. Eksekusi import file dengan konfigurasi kembalian asli (existing) Anda
                    Excel::import(new PemakaianImport, $data['file']);

                    // 3. Ambil hasil kalkulasi statistik dari session
                    $sukses = session('import_success', 0);
                    $duplikat = session('import_duplicates', 0);

                    // 4. Kondisi Logika Alert / Notifikasi Pengguna kustom
                    if ($duplikat > 0 && $sukses > 0) {
                        // Kondisi Campuran: Ada data baru masuk, ada data yang dilewati karena ganda
                        Notification::make()
                            ->title('Import Selesai dengan Catatan')
                            ->body("Berhasil menyimpan **{$sukses} data** data pemakaian baru.\n\nTerdapat **{$duplikat} data** data yang dilewati (*skip*) karena terdeteksi duplikat.")
                            ->warning() // Warna Kuning
                            ->duration(10000) // Tampil 10 detik
                            ->send();
                    } elseif ($duplikat > 0 && $sukses === 0) {
                        // Kondisi Semua Duplikat: Tidak ada data baru sama sekali
                        Notification::make()
                            ->title('Import Dibatalkan')
                            ->body("Sistem menolak seluruh data (**{$duplikat} data**) dalam file Excel ini karena **100% duplikat** dengan riwayat yang ada di database.")
                            ->danger() // Warna Merah
                            ->persistent() // Tidak hilang sampai di-klik silang oleh user
                            ->send();
                    } else {
                        // Kondisi Sukses Sempurna: Semua data baru masuk bersih
                        Notification::make()
                            ->title('Import Sukses Total')
                            ->body("Seluruh data (**{$sukses} data**) berhasil di-import dan rekap bulanan otomatis terupdate.")
                            ->success() // Warna Hijau
                            ->send();
                    }

                    // 5. Bersihkan kembali session setelah selesai digunakan
                    session()->forget(['import_success', 'import_duplicates']);
                }),
        ];
    }
}
