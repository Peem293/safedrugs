<?php

namespace App\Filament\Resources\Pemakaians\Pages;

use App\Filament\Resources\Pemakaians\PemakaianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
// WAJIB tambahkan baris import Eloquent Model di bawah ini:
use Illuminate\Database\Eloquent\Model;

class EditPemakaian extends EditRecord
{
    protected static string $resource = PemakaianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
        ];
    }

    /**
     * Mengambil kontrol pembaruan form Filament
     * dan menyimpannya murni menggunakan Eloquent
     * untuk memicu event 'updated' pada Observer.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Isi data baru dari form ke model
        $record->fill($data);

        // Simpan data secara eksplisit agar terbaca oleh PemakaianObserver
        $record->save();

        return $record;
    }
}
