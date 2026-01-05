<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TableForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin bàn')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên bàn')
                            ->required()
                            ->placeholder('Ví dụ: Bàn Vip 1'),

                        // 2. Ô chọn loại bàn
                        Select::make('table_type_id')
                            ->label('Loại bàn')
                            ->relationship('tableType', 'name')
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),

                        // 3. Ô nhập giá tiền
                        TextInput::make('price_per_hour')
                            ->label('Giá mỗi giờ (VNĐ)')
                            ->numeric()
                            ->required()
                            ->default(50000)
                            ->suffix('VNĐ'),

                        // 4. Công tắc Bật/Tắt bàn
                        Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true),
                    ])
                    ->columns(2),

                // 5. Vị trí trên sơ đồ
                Section::make('Vị trí trên sơ đồ')
                    ->description('Vị trí hiển thị trên bản đồ bàn. Có thể kéo thả trực tiếp tại trang Sơ đồ bàn.')
                    ->schema([
                        TextInput::make('position_x')
                            ->label('Vị trí X (px)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Khoảng cách từ bên trái'),

                        TextInput::make('position_y')
                            ->label('Vị trí Y (px)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Khoảng cách từ trên xuống'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
