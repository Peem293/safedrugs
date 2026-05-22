<?php

namespace App\Filament\Resources\Obats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class ObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_obat')
                    ->label('Kode Obat')
                    ->searchable(),
                TextColumn::make('nama_obat')
                    ->label('Nama Obat')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->label('Stock')
                    ->sortable(),
                    TextColumn::make('buffer_stock')
                    ->numeric()
                    ->label('Buffer Stock')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->numeric()
                    ->label('Min Stock')
                    ->sortable(),
                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->searchable(),
                TextColumn::make('last_buffer_calculated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Buffer Calculated at'),
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
                Filter::make('stok_kritis')
                    ->label('Stok Kritis (≤ Min Stock)')
                    ->query(fn (Builder $query) => $query->whereRaw('stock <= min_stock')),
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
