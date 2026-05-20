<?php

namespace App\Filament\Resources\StockOnhands\Pages;

use App\Filament\Resources\StockOnhands\StockOnhandResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockOnhand extends EditRecord
{
    protected static string $resource = StockOnhandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
