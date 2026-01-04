<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                // Hiển thị quyền đẹp mắt
                TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',  // Admin màu Đỏ
                        'staff' => 'info',    // Staff màu Xanh
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Ngày tạo'),
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
