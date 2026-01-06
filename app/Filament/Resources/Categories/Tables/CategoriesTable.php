<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // STT
                TextColumn::make('rowIndex')
                    ->label('#')
                    ->rowIndex(),

                // Tên nhóm
                TextColumn::make('name')
                    ->label('Tên nhóm')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-folder'),

                // Đếm số lượng sản phẩm trong nhóm (Cái này rất hay!)
                TextColumn::make('products_count')
                    ->counts('products') // Hàm counts tự đếm quan hệ hasMany
                    ->label('Số lượng món')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray'),

                // Bật tắt nhanh ngay trên bảng
                ToggleColumn::make('is_active')
                    ->label('Hiển thị'),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Ngày tạo')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Trạng thái')
                    ->trueLabel('Đang hiện')
                    ->falseLabel('Đang ẩn'),
            ])
            ->recordActions([
                EditAction::make()->label('Sửa'),
                DeleteAction::make()->label('Xóa'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

}
