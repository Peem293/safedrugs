<?php

namespace App\Filament\Resources\StockOnhands\Pages;

use App\Filament\Resources\StockOnhands\StockOnhandResource;
use App\Services\ScraperService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListStockOnhands extends ListRecords
{
    protected static string $resource = StockOnhandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncSimrs')
                ->label('Sync SIMRS')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Data SIMRS')
                ->modalDescription('Sistem akan menjalankan robot otomatisasi (Scraper) untuk mengambil data batch dan stok terbaru langsung dari SIMRS. Proses ini mungkin memakan waktu beberapa saat. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Mulai Sinkronisasi')
                ->action(function (ScraperService $scraperService) {
                    $usernameSimrs = '020150702';
                    $passwordSimrs = 'Nuel.1310';
                    $respon = $scraperService->ambilDataSimrs($usernameSimrs, $passwordSimrs);

                    // Memeriksa status akhir eksekusi scraper
                    // Memeriksa status akhir eksekusi scraper
                    if (isset($respon['status']) && $respon['status'] === 'sukses') {
                        Notification::make()
                            ->title('Sinkronisasi Berhasil!')
                            ->body('Seluruh data batch obat telah diperbarui, dan total stok master obat otomatis dikalkulasi ulang.') // GANTI DISINI (description -> body)
                            ->success()
                            ->send();
                    }else {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->description('Terjadi kendala: ' . ($respon['error'] ?? 'Koneksi ke SIMRS terputus.'))
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

        Action::make('cetak_report')
            ->label('Cetak Laporan Stock')
            ->color('danger')
            ->icon('heroicon-o-printer')
            ->url(route('admin.stock-onhands.cetak'))
            ->openUrlInNewTab(),
        ];
    }
}
