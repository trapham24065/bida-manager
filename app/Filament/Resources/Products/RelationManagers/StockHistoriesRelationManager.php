<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockHistoriesRelationManager extends RelationManager
{

    protected static string $relationship = 'stockInputs';

    protected static ?string $title = 'Lịch sử nhập hàng';

    protected static string|null|\BackedEnum $icon = 'heroicon-o-clock';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quantity')
                    ->label('Số lượng')
                    ->formatStateUsing(fn($state) => '+'.$state),

                TextInput::make('import_price')
                    ->label('Giá nhập')
                    ->money('VND'),

                Textarea::make('note')
                    ->label('Ghi chú')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
// 1. Thời gian nhập
TextColumn::make('created_at')
    ->label('Thời gian')
    ->dateTime('H:i d/m/Y')
    ->sortable(),

// 2. Người thực hiện (User)
// Lưu ý: Đảm bảo Model StockInput có hàm user()
TextColumn::make('user.name')
    ->label('Người nhập')
    ->icon('heroicon-o-user')
    ->sortable(),

// 3. Số lượng nhập
TextColumn::make('quantity')
    ->label('SL Nhập')
    ->badge()
    ->color('success')
    ->formatStateUsing(fn($state) => '+'.$state), // Thêm dấu cộng cho đẹp

// 4. Giá nhập
TextColumn::make('import_price')
    ->label('Giá vốn')
    ->money('VND'),

// 5. Tồn kho sau khi nhập
TextColumn::make('new_stock')
    ->label('Tồn sau nhập')
    ->weight('bold'),

// 6. Ghi chú
TextColumn::make('note')
    ->label('Ghi chú')
    ->limit(30)
    ->tooltip(fn(TextColumn $column): ?string => $column->getState()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->recordActions([

            ])
            ->toolbarActions([

            ]);
    }

}
