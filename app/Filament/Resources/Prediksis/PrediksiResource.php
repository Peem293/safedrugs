<?php

namespace App\Filament\Resources\Prediksis;

use App\Filament\Resources\Prediksis\Pages\CreatePrediksi;
use App\Filament\Resources\Prediksis\Pages\EditPrediksi;
use App\Filament\Resources\Prediksis\Pages\ListPrediksis;
use App\Filament\Resources\Prediksis\Schemas\PrediksiForm;
use App\Filament\Resources\Prediksis\Tables\PrediksisTable;
use App\Models\Prediksi;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
//use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrediksiResource extends Resource
{
    protected static ?string $model = Prediksi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Prediksi';
    protected static string|UnitEnum|null $navigationGroup = 'Analitic';

    protected static ?string $recordTitleAttribute = 'Prediksi';

    public static function form(Schema $schema): Schema
    {
        return PrediksiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrediksisTable::configure($table);
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
            'index' => ListPrediksis::route('/'),
            'create' => CreatePrediksi::route('/create'),
            'edit' => EditPrediksi::route('/{record}/edit'),
        ];
    }
}
