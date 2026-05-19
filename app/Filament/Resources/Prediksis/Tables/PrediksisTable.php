<?php

namespace App\Filament\Resources\Prediksis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrediksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bulan_tahun_prediksi')
                    ->searchable(),
                TextColumn::make('nilai_a')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nilai_b')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hasil_prediksi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nilai_mape')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kategori_mape')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
