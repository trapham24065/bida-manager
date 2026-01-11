<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;

class ProductForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')->schema([
                    Select::make('category_id')
                        ->label('Nhóm sản phẩm')
                        ->relationship('category', 'name') // Load tên từ bảng categories
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->label('Tên nhóm mới'),
                        ])
                        ->required(),
                    FileUpload::make('image')
                        ->label('Hình ảnh')
                        ->image()
                        ->disk('public')
                        ->directory('products')
                        ->columnSpanFull(),
                    TextInput::make('name')
                        ->label('Tên món')
                        ->required(),

                    TextInput::make('price')
                        ->label('Giá bán')
                        ->numeric()
                        ->suffix('VNĐ')
                        ->required(),
                    TextInput::make('tax_rate')
                        ->label('Thuế VAT (%)')
                        ->numeric()
                        ->default(10) // Mặc định 10%
                        ->suffix('%')
                        ->required(),
                    TextInput::make('stock')
                        ->label('Tồn kho hiện tại')
                        ->numeric()
                        ->default(0) // Mặc định là 0 khi tạo mới
                        ->disabled() // KHÓA: Không cho phép nhập tay
                        ->dehydrated(
                        ) // QUAN TRỌNG: Giúp gửi giá trị 0 lên server khi tạo mới (nếu không có dòng này sẽ bị lỗi)
                        ->helperText(
                            'Để tăng số lượng, vui lòng tạo sản phẩm xong và bấm nút "Nhập hàng" bên ngoài danh sách.'
                        )
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Đang mở bán')
                        ->default(true),
                ]),
            ]);
    }

}
