<?php

namespace App\Filament\Resources\Combos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CombosTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('public'),
                TextColumn::make('name')->label('Tên Combo')->searchable(),
                TextColumn::make('price')->label('Giá bán')->money('VND'),

                TextColumn::make('items_count')
                    ->label('Số món')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->comboItems->unique('id')->count().' món')
                    ->color('success'),

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

}
