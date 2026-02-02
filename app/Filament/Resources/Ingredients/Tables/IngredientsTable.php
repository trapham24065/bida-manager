<?php

namespace App\Filament\Resources\Ingredients\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;

class IngredientsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên nguyên liệu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-beaker'),

                TextColumn::make('unit')
                    ->label('Đơn vị')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('stock')
                    ->label('Tồn kho')
                    ->formatStateUsing(fn($record) => number_format($record->stock, 2).' '.$record->unit)
                    ->badge()
                    ->color(fn($record): string => match (true) {
                        $record->stock <= $record->min_stock => 'danger',
                        $record->stock <= $record->min_stock * 2 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('min_stock')
                    ->label('Tồn tối thiểu')
                    ->formatStateUsing(fn($record) => number_format($record->min_stock, 2).' '.$record->unit)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cost_per_unit')
                    ->label('Giá vốn/đơn vị')
                    ->money('VND')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Hoạt động'),

                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Trạng thái')
                    ->options([
                        '1' => 'Đang sử dụng',
                        '0' => 'Ngưng sử dụng',
                    ]),
            ])
            ->recordActions([
                // Nút nhập kho nhanh
                Action::make('add_stock')
                    ->label('Nhập kho')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Số lượng nhập')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                    ])
                    ->action(function ($record, array $data) {
                        $record->incrementStock($data['quantity']);
                        Notification::make()
                            ->title('Đã nhập kho thành công')
                            ->body("Đã thêm {$data['quantity']} {$record->unit} {$record->name}")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // Có thể thêm bulk actions nếu cần
            ])
            ->defaultSort('name', 'asc');
    }

}

