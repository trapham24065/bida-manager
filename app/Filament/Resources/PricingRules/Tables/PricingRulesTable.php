<?php

namespace App\Filament\Resources\PricingRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PricingRulesTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('table_type')
                    ->label('Loại bàn')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pool' => 'info',
                        'carom' => 'warning',
                        'snooker' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('name')->label('Tên'),
                TextColumn::make('start_time')->label('Từ')->time('H:i'),
                TextColumn::make('end_time')->label('Đến')->time('H:i'),
                TextColumn::make('price_per_hour')->label('Giá')->money('VND'),
                ToggleColumn::make('is_active')->label('Bật/Tắt'),
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
