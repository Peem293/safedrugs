<?php

namespace App\Filament\Resources\Pemakaians;

use App\Filament\Resources\Pemakaians\Pages\CreatePemakaian;
use App\Filament\Resources\Pemakaians\Pages\EditPemakaian;
use App\Filament\Resources\Pemakaians\Pages\ListPemakaians;
use App\Filament\Resources\Pemakaians\Schemas\PemakaianForm;
use App\Filament\Resources\Pemakaians\Tables\PemakaiansTable;
use App\Models\Pemakaian;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
//use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PemakaianResource extends Resource
{
    protected static ?string $model = Pemakaian::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $recordTitleAttribute = 'kode_obat';
    protected static ?string $navigationLabel = 'Histori Transaksi';
    protected static string|UnitEnum|null $navigationGroup = 'Analitic';

    public static function form(Schema $schema): Schema
    {
        return PemakaianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PemakaiansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPemakaians::route('/'),
            'create' => CreatePemakaian::route('/create'),
            'edit' => EditPemakaian::route('/{record}/edit'),
        ];
    }
}
