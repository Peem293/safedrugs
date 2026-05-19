<?php

namespace App\Filament\Resources\Obats\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ObatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_obat')
                    ->required(),
                TextInput::make('nama_obat')
                    ->required(),
                TextInput::make('stock')
                    ->required()
                    ->numeric(),
                TextInput::make('satuan')
                    ->required(),
            ]);
    }
}
