<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameSessionForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
// === CỘT 1: THÔNG TIN CHUNG ===
Section::make('Thông tin chung')
    ->schema([
        Select::make('table_id')
            ->relationship('bidaTable', 'name')
            ->label('Bàn')
            ->disabled(), // Chỉ xem

        DateTimePicker::make('start_time')
            ->label('Giờ bắt đầu')
            ->disabled(),

        DateTimePicker::make('end_time')
            ->label('Giờ kết thúc')
            ->disabled(),

        TextInput::make('total_money')
            ->label('Tổng tiền thanh toán')
            ->formatStateUsing(fn($state) => number_format($state).' VNĐ')
            ->disabled(),
    ])->columns(2), // Chia 2 cột cho đẹp

// === CỘT 2: DANH SÁCH MÓN ĐÃ GỌI (QUAN TRỌNG) ===
Section::make('Chi tiết món ăn / thức uống')
    ->schema([
        Repeater::make('orderItems') // Quan hệ orderItems
        ->relationship()
            ->schema([
                // Hiển thị tên món
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Tên món')
                    ->disabled(), // Khóa không cho sửa

                // Hiển thị số lượng
                TextInput::make('quantity')
                    ->label('Số lượng')
                    ->disabled(),

                // Hiển thị thành tiền của món đó
                TextInput::make('total')
                    ->label('Thành tiền')
                    ->formatStateUsing(fn($state) => number_format($state).' VNĐ')
                    ->disabled(),
            ])
            ->columns(3) // Chia 3 cột trên 1 dòng
            ->addable(false)    // Ẩn nút Thêm
            ->deletable(false)  // Ẩn nút Xóa
            ->reorderable(false),// Ẩn nút Kéo thả,
    ]),
            ]);
    }

}
