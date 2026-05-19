<?php

namespace App\Filament\Resources\Pemakaians\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PemakaianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('obat_id')
                    ->label('Obat')
                    ->relationship('obat', 'nama_obat')
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        $record->kode_obat . ' - ' . $record->nama_obat
                    )
                    ->searchable(['kode_obat', 'nama_obat'])
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $obat = \App\Models\Obat::find($state);
                        if ($obat) {
                            $set('satuan', $obat->satuan);
                        }
                    })
                    ->required(),
                DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('jumlah')
                    ->required()
                    ->numeric(),
                TextInput::make('satuan')
                    ->required()
                    ->readOnly(),
            ]);
    }
}
