<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ScraperService;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSimrsStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tidak ada batas waktu — proses Chrome bisa memakan waktu lama.
     */
    public int $timeout = 0;

    /**
     * Hanya 1 kali percobaan. Jika gagal, kirim notifikasi error.
     */
    public int $tries = 1;

    public function __construct(
        public readonly int $userId,
        public readonly string $usernameSimrs = '020150702',
        public readonly string $passwordSimrs = 'Nuel.1310'
    ) {}

    public function handle(ScraperService $scraperService): void
    {
        $recipient = User::find($this->userId);

        $respon = $scraperService->ambilDataSimrs($this->usernameSimrs, $this->passwordSimrs);

        $totalObat = count($respon['results'] ?? []);
        $errors    = $respon['errors'] ?? [];

        if ($respon['status'] === 'sukses' && empty($errors)) {
            Notification::make()
                ->title('✅ Sinkronisasi SIMRS Berhasil')
                ->body("Data batch & stok {$totalObat} obat berhasil diperbarui dari SIMRS.")
                ->success()
                ->actions([
                    Action::make('lihat')
                        ->label('Lihat Stock On Hands')
                        ->url('/admin/stock-onhands')
                        ->button(),
                ])
                ->sendToDatabase($recipient);
        } else {
            $msg = empty($errors)
                ? ($respon['error'] ?? 'Koneksi ke SIMRS terputus atau gagal.')
                : 'Sebagian data gagal diproses ('.count($errors).' error). Cek log untuk detail.';

            Notification::make()
                ->title('❌ Sinkronisasi SIMRS Gagal')
                ->body($msg)
                ->danger()
                ->sendToDatabase($recipient);
        }
    }

    /**
     * Tangani kegagalan job yang tidak terduga (exception tidak tertangkap).
     */
    public function failed(\Throwable $exception): void
    {
        $recipient = User::find($this->userId);

        if ($recipient) {
            Notification::make()
                ->title('❌ Proses Sinkronisasi Error')
                ->body('Job scraping SIMRS mengalami error tidak terduga: ' . $exception->getMessage())
                ->danger()
                ->sendToDatabase($recipient);
        }
    }
}
