<?php

namespace App\Filament\Resources\Prediksis\Pages;

use App\Filament\Resources\Prediksis\PrediksiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrediksi extends EditRecord
{
    protected static string $resource = PrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
