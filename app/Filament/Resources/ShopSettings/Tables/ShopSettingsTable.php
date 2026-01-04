<?php

namespace App\Filament\Resources\ShopSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShopSettingsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop_name')->label('Tên quán'),
                TextColumn::make('bank_id')->label('Ngân hàng'),
                TextColumn::make('bank_account')->label('STK'),
            ])
            ->actions([
                EditAction::make(),
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
