<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Họ và tên')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                // QUAN TRỌNG: Ô nhập mật khẩu
                TextInput::make('password')
                    ->label('Mật khẩu')
                    ->password()
                    // Chỉ bắt buộc nhập khi TẠO MỚI (Sửa thì để trống là giữ nguyên pass cũ)
                    ->required(fn($livewire) => $livewire instanceof CreateUser)
                    // Chỉ lưu vào DB nếu người dùng có nhập gì đó
                    ->dehydrated(fn($state) => filled($state))
                    // Tự động mã hóa Hash trước khi lưu
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)),

                // Ô CHỌN QUYỀN (Role)
                Select::make('role')
                    ->label('Quyền hạn')
                    ->options([
                        'admin' => 'Chủ quán (Admin)',
                        'staff' => 'Nhân viên (Staff)',
                    ])
                    ->required()
                    ->default('staff'), // Mặc định là nhân viên cho an toàn
            ]);
    }

}
