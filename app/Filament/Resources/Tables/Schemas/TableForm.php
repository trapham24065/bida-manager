<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TableForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên bàn')
                    ->required()
                    ->placeholder('Ví dụ: Bàn Vip 1'),

                // 2. Ô chọn loại bàn
                Select::make('table_type_id') // Lưu vào cột ID mới
                ->label('Loại bàn')
                    ->relationship('tableType', 'name') // Lấy tên từ bảng TableType
                    ->required()
                    ->createOptionForm([ // Cho phép tạo nóng loại bàn ngay tại đây luôn
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
            ]);
    }

}
