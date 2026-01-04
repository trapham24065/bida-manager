<?php

namespace App\Filament\Resources\CustomerRanks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerRanksTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên hạng')
                    ->weight('bold')
                    ->badge()
                    ->color(fn($record) => $record->color),

                TextColumn::make('min_spending')
                    ->label('Mốc chi tiêu')
                    ->money('VND')
                    ->sortable(),

                TextColumn::make('discount_percent')
                    ->label('Giảm giá')
                    ->suffix('%')
                    ->color('success')
                    ->weight('bold'),
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
