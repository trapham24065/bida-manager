<?php

namespace App\Filament\Resources\WorkShifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkShiftsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Nhân viên'),
                TextColumn::make('start_time')->dateTime('H:i d/m')->label('Bắt đầu'),
                TextColumn::make('end_time')->dateTime('H:i d/m')->label('Kết thúc'),
                TextColumn::make('initial_cash')->money('VND')->label('Vốn đầu ca'),
                TextColumn::make('total_cash_money')->money('VND')->label('Thu Tiền mặt')->color('success'),
                TextColumn::make('reported_cash')->money('VND')->label('Thực tế'),
                TextColumn::make('difference')
                    ->label('Chênh lệch')
                    ->money('VND')
                    ->badge()
                    ->color(fn($state) => $state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

}
