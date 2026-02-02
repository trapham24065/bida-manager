<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IngredientForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin nguyên liệu')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên nguyên liệu')
                            ->required()
                            ->placeholder('VD: Cà phê, Sữa đặc, Đường...')
                            ->maxLength(255),

                        Select::make('unit')
                            ->label('Đơn vị tính')
                            ->options([
                                'g'    => 'gram (g)',
                                'kg'   => 'kilogram (kg)',
                                'ml'   => 'mililit (ml)',
                                'l'    => 'lít (l)',
                                'viên' => 'viên',
                                'gói'  => 'gói',
                                'cái'  => 'cái',
                            ])
                            ->default('g')
                            ->required()
                            ->native(false),

                        TextInput::make('cost_per_unit')
                            ->label('Giá vốn mỗi đơn vị')
                            ->numeric()
                            ->default(0)
                            ->suffix('VNĐ')
                            ->helperText('VD: 200 VNĐ/g nghĩa là 1 gram cà phê giá 200đ'),

                        TextInput::make('min_stock')
                            ->label('Mức tồn tối thiểu')
                            ->numeric()
                            ->default(0)
                            ->helperText('Khi tồn kho dưới mức này sẽ cảnh báo'),

                        TextInput::make('stock')
                            ->label('Tồn kho hiện tại')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Sử dụng chức năng Nhập kho để thay đổi số lượng'),

                        Toggle::make('is_active')
                            ->label('Đang sử dụng')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

}

