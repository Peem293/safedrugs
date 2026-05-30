<?php

namespace App\Filament\Resources\Obats;

use App\Filament\Resources\Obats\Pages\CreateObat;
use App\Filament\Resources\Obats\Pages\EditObat;
use App\Filament\Resources\Obats\Pages\ListObats;
use App\Filament\Resources\Obats\Schemas\ObatForm;
use App\Filament\Resources\Obats\Tables\ObatsTable;
use App\Models\Obat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
//use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ObatResource extends Resource
{
    protected static ?string $model = Obat::class;

    //protected static string|BackedEnum|null $navigationIcon = 'bootstrap-bi-capsule';
    protected static string|BackedEnum|null $navigationIcon = 'bi-capsule';

    protected static ?string $recordTitleAttribute = 'kode_obat';

    protected static ?string $navigationLabel = 'Obats';
    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    public static function form(Schema $schema): Schema
    {
        return ObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObatsTable::configure($table);
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
            'index' => ListObats::route('/'),
            'create' => CreateObat::route('/create'),
            'edit' => EditObat::route('/{record}/edit'),
        ];
    }
}
