<?php

namespace App\Filament\Resources\ShopSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopSettingForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin quán')
                    ->schema([
                        TextInput::make('shop_name')
                            ->label('Tên quán')
                            ->required(),
                        TextInput::make('address')
                            ->label('Địa chỉ')
                            ->placeholder('VD: 123 Cầu Giấy, Hà Nội'),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel() // Bàn phím số
                            ->placeholder('VD: 0987.654.321'),
                        TextInput::make('wifi_pass')
                            ->label('Wifi / Mật khẩu')
                            ->placeholder('VD: Wifi: BidaSuper / Pass: 1234'),
                    ]),

                Section::make('Tài khoản nhận tiền (QR)')
                    ->schema([
                        // Danh sách ngân hàng phổ biến ở VN
                        Select::make('bank_id')
                            ->label('Ngân hàng')
                            ->options([
                                'MB'   => 'MB Bank (Quân Đội)',
                                'VCB'  => 'Vietcombank',
                                'TCB'  => 'Techcombank',
                                'ACB'  => 'ACB',
                                'BIDV' => 'BIDV',
                                'ICB'  => 'VietinBank',
                                'TPB'  => 'TPBank',
                                'VPB'  => 'VPBank',
                            ])
                            ->searchable()
                            ->required(),

                        TextInput::make('bank_account')
                            ->label('Số tài khoản')
                            ->required(),

                        TextInput::make('bank_account_name')
                            ->label('Tên chủ tài khoản (Không dấu)')
                            ->placeholder('NGUYEN VAN A')
                            ->required(),
                    ]),
            ]);
    }

}
