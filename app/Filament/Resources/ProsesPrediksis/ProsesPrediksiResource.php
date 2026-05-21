<?php

namespace App\Filament\Resources\ProsesPrediksis;

use App\Filament\Resources\ProsesPrediksis\Pages\ManageProsesPrediksis;
use App\Models\Prediksi;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class ProsesPrediksiResource extends Resource
{
    protected static ?string $model = Prediksi::class;

    protected static ?string $navigationLabel = 'Prediksi';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null $navigationGroup = 'Analitic';

    public static function table(Table $table): Table
    {
        // Menghapus data session pencarian bulan aktif setiap kali user me-refresh atau pertama kali masuk halaman ini
        if (!request()->ajax() && !request()->has('livewire')) {
            session()->forget('tampilkan_bulan_prediksi');
        }

        return $table
            ->columns([
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bulan_tahun_prediksi')
                    ->label('Bulan tahun prediksi'),
                TextColumn::make('nilai_a')
                    ->label('Nilai a'),
                TextColumn::make('nilai_b')
                    ->label('Nilai b'),
                TextColumn::make('hasil_prediksi')
                    ->label('Hasil prediksi'),
                TextColumn::make('nilai_mape')
                    ->label('Nilai mape')
                    ->state(fn ($record) => $record->nilai_mape . '%'),
                TextColumn::make('kategori_mape')
                    ->label('Kategori'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Mengambil bulan target dari Session klik tombol proses
                $bulanSesi = session('tampilkan_bulan_prediksi');

                // Kondisi awal halaman dibuka atau saat user berpindah menu halaman:
                // Sesi bernilai kosong, kunci query agar data tidak ditampilkan sama sekali (Tabel Kosong).
                if (empty($bulanSesi)) {
                    return $query->whereRaw('1 = 0');
                }

                // Jika user sudah memilih bulan lewat tombol proses, tampilkan data bulan tersebut
                return $query->where('bulan_tahun_prediksi', $bulanSesi);
            })
            ->filters([
                // Mengosongkan komponen filter bawaan tabel agar tampilan bersih tanpa tombol corong/filter dropdown
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProsesPrediksis::route('/'),
        ];
    }
}
