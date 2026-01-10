<?php

namespace App\Filament\Resources\TableTypes;

use App\Filament\Resources\TableTypes\Pages\ManageTableTypes;
use App\Models\TableType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TableTypeResource extends Resource
{

    protected static ?string $model = TableType::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Loại Bàn';

    protected static ?string $pluralModelLabel = 'Loại Bàn';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên loại bàn')
                    ->required()
                    ->placeholder('VD: Bàn Lỗ VIP'),
                Select::make('category')
                    ->label('Phân nhóm')
                    ->options([
                        'bida' => 'Bida (Tính tiền giờ)',
                        'cafe' => 'Cafe / Đồ uống (Không tính giờ)',
                    ])
                    ->default('bida')
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên loại bàn'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTableTypes::route('/'),
        ];
    }

}
