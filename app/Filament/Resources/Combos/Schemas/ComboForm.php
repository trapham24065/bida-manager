<?php

namespace App\Filament\Resources\Combos\Schemas;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms;

class ComboForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sử dụng Forms\Components\Section (Chuẩn)
                Section::make('Thông tin Combo')->schema([

                    // 1. Tên Combo
                    TextInput::make('name')
                        ->label('Tên Combo')
                        ->required()
                        ->placeholder('VD: Combo Nhậu Vui'),

                    // 2. Giá bán
                    TextInput::make('price')
                        ->label('Giá bán trọn gói')
                        ->numeric()
                        ->required()
                        ->suffix('VNĐ'),

                    // 3. Ảnh
                    FileUpload::make('image')
                        ->disk('public')
                        ->label('Ảnh Combo')
                        ->image()
                        ->directory('combos'),

                    // === CÁC TRƯỜNG ẨN (QUAN TRỌNG) ===
                    Forms\Components\Hidden::make('is_combo')->default(true),
                    Forms\Components\Hidden::make('stock')->default(9999),
                    Forms\Components\Hidden::make('is_active')->default(true),
                    Forms\Components\Hidden::make('cost_price')->default(0),

                ])->columns(2),

                // 4. Chọn món (Repeater)
                Section::make('Thành phần')
                    ->description('Combo này bao gồm những món gì?')
                    ->schema([
                        Repeater::make('combo_items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Chọn món')
                                    ->options(
                                        Product::where('is_combo', false)->pluck('name', 'id')
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->native(false),

                                TextInput::make('quantity')
                                    ->label('Số lượng')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Thêm món vào Combo')
                            ->minItems(1),

                    ]),
            ]);
    }

}
