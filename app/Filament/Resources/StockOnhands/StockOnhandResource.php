<?php

namespace App\Filament\Resources\StockOnhands;

use App\Filament\Resources\StockOnhands\Pages\CreateStockOnhand;
use App\Filament\Resources\StockOnhands\Pages\EditStockOnhand;
use App\Filament\Resources\StockOnhands\Pages\ListStockOnhands;
use App\Filament\Resources\StockOnhands\Schemas\StockOnhandForm;
use App\Filament\Resources\StockOnhands\Tables\StockOnhandsTable;
use App\Models\StockOnhand;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
//use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockOnhandResource extends Resource
{
    protected static ?string $model = StockOnhand::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Stock On Hands';
    protected static string|UnitEnum|null $navigationGroup = 'Analitic';

    protected static ?string $recordTitleAttribute = 'obat_id';

    public static function form(Schema $schema): Schema
    {
        return StockOnhandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockOnhandsTable::configure($table);
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
            'index' => ListStockOnhands::route('/'),
            'create' => CreateStockOnhand::route('/create'),
            'edit' => EditStockOnhand::route('/{record}/edit'),
        ];
    }
}
