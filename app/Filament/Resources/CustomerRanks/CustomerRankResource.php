<?php

namespace App\Filament\Resources\CustomerRanks;

use App\Filament\Resources\CustomerRanks\Pages\CreateCustomerRank;
use App\Filament\Resources\CustomerRanks\Pages\EditCustomerRank;
use App\Filament\Resources\CustomerRanks\Pages\ListCustomerRanks;
use App\Filament\Resources\CustomerRanks\Schemas\CustomerRankForm;
use App\Filament\Resources\CustomerRanks\Tables\CustomerRanksTable;
use App\Models\CustomerRank;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerRankResource extends Resource
{

    protected static ?string $model = CustomerRank::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Quản lí xếp hạng';

    protected static ?string $pluralModelLabel = 'Xếp hạng';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CustomerRankForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerRanksTable::configure($table);
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
            'index'  => ListCustomerRanks::route('/'),
            'create' => CreateCustomerRank::route('/create'),
            'edit'   => EditCustomerRank::route('/{record}/edit'),
        ];
    }

}
