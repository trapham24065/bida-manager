<?php

namespace App\Filament\Resources\Combos;

use App\Filament\Resources\Combos\Pages\CreateCombo;
use App\Filament\Resources\Combos\Pages\EditCombo;
use App\Filament\Resources\Combos\Pages\ListCombos;
use App\Filament\Resources\Combos\Schemas\ComboForm;
use App\Filament\Resources\Combos\Tables\CombosTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComboResource extends Resource
{

    protected static ?string $model = Product::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-gift'; // Icon hộp quà

    protected static ?string $navigationLabel = 'Quản lý Combo';

    protected static string|null|\UnitEnum $navigationGroup = 'Quản lí sản phẩm';

    protected static ?string $modelLabel = 'Combo';

    protected static ?string $slug = 'combos';

    public static function form(Schema $schema): Schema
    {
        return ComboForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CombosTable::configure($table);
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
            'index'  => ListCombos::route('/'),
            'create' => CreateCombo::route('/create'),
            'edit'   => EditCombo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_combo', true);
    }

}
