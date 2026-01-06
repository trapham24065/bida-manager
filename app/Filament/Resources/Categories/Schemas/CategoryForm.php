<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chung')
                    ->schema([
                        // Tên danh mục (Vd: Nước ngọt, Thuốc lá...)
                        TextInput::make('name')
                            ->label('Tên nhóm')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true) // Không cho trùng tên
                            ->placeholder('VD: Nước giải khát, Đồ ăn vặt...'),

                        // Nút bật tắt trạng thái
                        Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true)
                            ->helperText('Tắt nếu muốn tạm ẩn nhóm này khỏi thực đơn.'),
                    ])
                    ->columns(1) // Gom thành 1 cột cho gọn
                    ->maxWidth('md'), // Form nhỏ gọn vừa phải
            ]);
    }

}
