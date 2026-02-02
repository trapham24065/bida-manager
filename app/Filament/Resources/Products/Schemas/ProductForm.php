<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Ingredient;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')->schema([
                    Select::make('category_id')
                        ->label('Nhóm sản phẩm')
                        ->relationship('category', 'name')
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
                        ->default(10)
                        ->suffix('%')
                        ->required(),

                    // Toggle sản phẩm pha chế
                    Toggle::make('is_recipe')
                        ->label('Là sản phẩm pha chế')
                        ->helperText('Bật nếu đây là đồ uống pha chế (cà phê, trà sữa...). Tồn kho sẽ tính từ nguyên liệu.')
                        ->default(false)
                        ->live(),

                    // Tồn kho (chỉ hiện khi KHÔNG phải sản phẩm pha chế)
                    TextInput::make('stock')
                        ->label('Tồn kho hiện tại')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Để tăng số lượng, bấm nút "Nhập hàng" trong danh sách.')
                        ->required()
                        ->hidden(fn(Get $get): bool => $get('is_recipe') === true),

                    Toggle::make('is_active')
                        ->label('Đang mở bán')
                        ->default(true),
                ]),

                // Section công thức (chỉ hiện khi là sản phẩm pha chế)
                Section::make('Công thức pha chế')
                    ->description('Nhập danh sách nguyên liệu cần để pha 1 phần')
                    ->schema([
                        Repeater::make('ingredients')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Select::make('ingredient_id')
                                    ->label('Nguyên liệu')
                                    ->options(Ingredient::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TextInput::make('quantity')
                                    ->label('Số lượng cần')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix(fn(Get $get) => Ingredient::find($get('ingredient_id'))?->unit ?? ''),
                            ])
                            ->columns(2)
                            ->addActionLabel('Thêm nguyên liệu')
                            ->reorderable(false)
                            ->defaultItems(0),
                    ])
                    ->hidden(fn(Get $get): bool => $get('is_recipe') !== true)
                    ->collapsible(),
            ]);
    }
}
