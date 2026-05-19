<?php

namespace App\Filament\Resources\Prediksis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrediksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('obat_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bulan_tahun_prediksi')
                    ->required(),
                TextInput::make('nilai_a')
                    ->required()
                    ->numeric(),
                TextInput::make('nilai_b')
                    ->required()
                    ->numeric(),
                TextInput::make('hasil_prediksi')
                    ->required()
                    ->numeric(),
                TextInput::make('nilai_mape')
                    ->numeric(),
                TextInput::make('kategori_mape'),
                TextInput::make('user_id')
                    ->numeric(),
            ]);
    }
}
