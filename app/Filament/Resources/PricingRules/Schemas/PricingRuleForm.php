<?php

namespace App\Filament\Resources\PricingRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PricingRuleForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('table_type_id')
                    ->label('Áp dụng cho loại bàn')
                    ->relationship('tableType', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Tên khung giờ')
                    ->required()
                    ->placeholder('VD: Giờ hành chính'),

                TimePicker::make('start_time')
                    ->label('Bắt đầu')
                    ->required()
                    ->seconds(false), // Ẩn giây cho gọn

                TimePicker::make('end_time')
                    ->label('Kết thúc')
                    ->required()
                    ->seconds(false),

                TextInput::make('price_per_hour')
                    ->label('Giá tiền / giờ')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Kích hoạt')
                    ->default(true),
            ]);
    }

}
