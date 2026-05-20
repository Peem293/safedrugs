<?php

namespace App\Filament\Resources\StockOnhands\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockOnhandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('obat_id')
                    ->relationship('obat', 'kode_obat')
                    ->label('Kode Obat')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->kode_obat} - {$record->nama_obat}")
                    ->required(),
                TextInput::make('batch_no')
                    ->required(),
                DatePicker::make('exp_date')
                    ->required(),
                TextInput::make('stock_on_hand')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('last_scraped_at'),
            ]);
    }
}
