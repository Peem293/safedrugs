<?php

namespace App\Filament\Resources\StockOnhands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockOnhandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obat.kode_obat')
                    ->searchable()
                    ->sortable()
                    ->label('Kode Obat'),
                TextColumn::make('obat.nama_obat')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->label('Nama Obat'),
                TextColumn::make('batch_no')
                    ->searchable(),
                TextColumn::make('exp_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('stock_on_hand')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_scraped_at')
                    ->dateTime()
                    ->label('Last Sync')
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
