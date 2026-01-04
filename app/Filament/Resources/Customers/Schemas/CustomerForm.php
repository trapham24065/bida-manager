<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin khách hàng')->schema([
                    TextInput::make('name')
                        ->label('Tên khách')
                        ->required(),
                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->unique(ignoreRecord: true)
                        ->required(),
                    TextInput::make('email')
                        ->email(),
                    Textarea::make('note')
                        ->columnSpanFull(),
                ])->columns(2),

                Section::make('Hạng thành viên (Tự động)')
                    ->schema([
                        TextInput::make('total_spending')
                            ->label('Tổng chi tiêu')
                            ->default(0)
                            ->dehydrated()
                            ->disabled() // Không cho sửa tay, hệ thống tự cộng
                            ->formatStateUsing(fn($state) => number_format($state).' đ'),

                        TextInput::make('points')
                            ->label('Điểm hiện có')
                            ->default(0)
                            ->numeric(), // Cho phép sửa điểm nếu muốn tặng điểm

                        TextInput::make('rank')
                            ->label('Hạng hiện tại')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

}
