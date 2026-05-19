<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput; // Import komponen TextInput Filament

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_role')
                    ->label('Role ID')
                    ->placeholder('Contoh: kepala_farmasi atau petugas_gudang')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'roles', ignoreRecord: true), // Mencegah duplikasi nama role
                TextInput::make('display_name')
                    ->label('Nama Role')
                    ->placeholder('Contoh: Kepala Farmasi atau Petugas Gudang')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
