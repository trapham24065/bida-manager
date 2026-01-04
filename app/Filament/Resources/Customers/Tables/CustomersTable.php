<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('phone')->label('SĐT')->searchable(),

                // === SỬA ĐOẠN NÀY ===
                TextColumn::make('rank.name') // Truy cập vào quan hệ 'rank' lấy cột 'name'
                ->label('Hạng')
                    ->badge()
                    // Lấy màu sắc từ bảng customer_ranks (thông qua quan hệ)
                    ->color(fn($record) => $record->rank?->color ?? 'gray')
                    ->placeholder('Chưa xếp hạng'), // Hiển thị nếu khách chưa có hạng
                // ====================

                TextColumn::make('points')->label('Điểm')->sortable(),
                TextColumn::make('total_spending')
                    ->label('Tổng chi')
                    ->money('VND')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

}
