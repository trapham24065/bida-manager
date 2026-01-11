<?php

namespace App\Filament\Resources\WorkShifts;

use App\Filament\Resources\WorkShifts\Pages\CreateWorkShift;
use App\Filament\Resources\WorkShifts\Pages\EditWorkShift;
use App\Filament\Resources\WorkShifts\Pages\ListWorkShifts;
use App\Filament\Resources\WorkShifts\Schemas\WorkShiftForm;
use App\Filament\Resources\WorkShifts\Tables\WorkShiftsTable;
use App\Models\WorkShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkShiftResource extends Resource
{

    protected static ?string $model = WorkShift::class;

    protected static ?string $navigationLabel = 'Quản lý Ca & Quỹ';

    protected static ?string $pluralModelLabel = 'Quản lý Ca & Quỹ';

    protected static ?int $navigationSort = 99;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function form(Schema $schema): Schema
    {
        return WorkShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkShiftsTable::configure($table);
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
            'index'  => ListWorkShifts::route('/'),
            'create' => CreateWorkShift::route('/create'),
        ];
    }

}
