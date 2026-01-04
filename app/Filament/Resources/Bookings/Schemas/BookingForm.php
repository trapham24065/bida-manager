<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đặt trước')
                    ->schema([
                        // 1. Chọn Bàn
                        Select::make('table_id')
                            ->label('Chọn bàn')
                            ->relationship('bidaTable', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        // 2. Thông tin khách
                        TextInput::make('customer_name')
                            ->label('Tên khách')
                            ->required()
                            ->placeholder('VD: Anh Tuấn'),

                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->required(),

                        // 3. Thời gian
                        DateTimePicker::make('booking_time')
                            ->label('Giờ nhận bàn')
                            ->required()
                            ->seconds(false)
                            ->native(false), // Dùng widget lịch của Filament cho đẹp

                        TextInput::make('duration_minutes')
                            ->label('Dự kiến chơi (Phút)')
                            ->numeric()
                            ->default(60)
                            ->step(30)
                            ->helperText('Để hệ thống tính toán tránh trùng lịch'),

                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->columnSpanFull(),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'pending'    => 'Đang chờ khách đến',
                                'checked_in' => 'Khách đã vào chơi',
                                'cancelled'  => 'Đã hủy',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),
            ]);
    }

}
