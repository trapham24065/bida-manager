<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
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
                                // Nhớ thêm ô chọn nhóm khi tạo nhanh loại bàn
                                Select::make('category')
                                    ->options(['bida' => 'Bida', 'cafe' => 'Cafe'])
                                    ->default('bida')
                                    ->required(),
                            ])
                            // === THÊM ĐOẠN NÀY ===
                            ->live() // Lắng nghe thay đổi
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    return;
                                }

                                // Tìm loại bàn vừa chọn
                                $type = \App\Models\TableType::find($state);

                                // Nếu là Cafe -> Set giá về 0
                                if ($type && $type->category === 'cafe') {
                                    $set('price_per_hour', 0);
                                }
                            }),

                        // 3. Ô nhập giá tiền
                        TextInput::make('price_per_hour')
                            ->label('Giá mỗi giờ (VNĐ)')
                            ->numeric()
                            ->required()
                            ->default(50000)
                            ->suffix('VNĐ')
                            ->helperText('Nếu là bàn Cafe, giá sẽ tự động là 0.'),

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
