<?php

namespace App\Filament\Resources\CustomerRanks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerRankForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cấu hình Hạng')->schema([
                    TextInput::make('name')
                        ->label('Tên hạng')
                        ->required()
                        ->placeholder('VD: Thành viên Vàng'),

                    TextInput::make('min_spending')
                        ->label('Chi tiêu tối thiểu')
                        ->numeric()
                        ->suffix('VNĐ')
                        ->required()
                        ->helperText('Khách đạt mốc này sẽ tự động lên hạng'),

                    TextInput::make('discount_percent')
                        ->label('Ưu đãi giảm giá')
                        ->numeric()
                        ->suffix('%')
                        ->maxValue(100)
                        ->required(),

                    Select::make('color')
                        ->label('Màu sắc nhãn')
                        ->options([
                            'gray'    => 'Xám (Mặc định)',
                            'info'    => 'Xanh dương',
                            'warning' => 'Vàng (Gold)',
                            'danger'  => 'Đỏ (VIP)',
                            'success' => 'Xanh lá',
                        ])
                        ->required(),
                ])->columns(2),
            ]);
    }

}
