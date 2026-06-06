<?php

namespace App\Filament\Resources\StockOnhands\Pages;

use App\Filament\Resources\StockOnhands\StockOnhandResource;
use App\Jobs\SyncSimrsStockJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

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
                ->modalDescription('Sistem akan mengirimkan perintah ke robot otomatisasi (Scraper) untuk mengambil data batch & stok terbaru dari SIMRS. Proses berjalan di latar belakang — Anda akan mendapat notifikasi saat selesai.')
                ->modalSubmitActionLabel('Ya, Mulai Sinkronisasi')
                ->action(function () {
                    // Dispatch ke queue — tidak memblokir browser/HTTP thread
                    SyncSimrsStockJob::dispatch(Auth::id());

                    Notification::make()
                        ->title('🔄 Sinkronisasi Dimulai!')
                        ->body('Robot scraper sedang berjalan di latar belakang. Anda akan mendapat notifikasi otomatis saat proses selesai.')
                        ->info()
                        ->duration(8000)
                        ->send();
                }),

            Action::make('cetak_report')
                ->label('Cetak Laporan Stock')
                ->color('danger')
                ->icon('heroicon-o-printer')
                ->url(function () {
                    \Carbon\Carbon::setLocale('id');
                    $tanggalCetak = \Carbon\Carbon::now()->translatedFormat('d F Y');
                    $filename = 'Stock On Hands Report ' . $tanggalCetak . '.pdf';
                    return route('admin.stock-onhands.cetak', ['filename' => $filename]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
