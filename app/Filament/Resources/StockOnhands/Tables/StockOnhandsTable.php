<?php

namespace App\Filament\Resources\StockOnhands\Tables;

use Carbon\Carbon;
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
                    ->label('Batch No')
                    ->searchable(),
                TextColumn::make('exp_date')
                    ->date()
                    ->label('Exp Date')
                    ->sortable(),
                TextColumn::make('exp_status')
                    ->label('Exp Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (! $record->exp_date) {
                            return 'Tidak Ada Data';
                        }

                        $expDate = Carbon::parse($record->exp_date);
                        $now = Carbon::now();

                        if ($expDate->isPast()) {
                            return 'Expired';
                        }

                        // Menghitung sisa bulan dari sekarang ke tanggal expired
                        $diffInMonths = $now->diffInMonths($expDate, false);

                        if ($diffInMonths <= 6) {
                            return 'Soon Expired';
                        }

                        return 'Aman';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Expired' => 'danger',       // Warna Merah
                        'Soon Expired' => 'warning', // Warna Kuning
                        'Aman' => 'success',         // Warna Hijau
                        default => 'gray',
                    })
                    ->icons([
                        'heroicon-m-x-circle' => 'Expired',
                        'heroicon-m-exclamation-triangle' => 'Soon Expired',
                        'heroicon-m-check-circle' => 'Aman',
                    ]),
                TextColumn::make('stock_on_hand')
                    ->numeric()
                    ->label('Stock On Hands')
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
                //EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
