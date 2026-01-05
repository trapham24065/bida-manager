<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo tài khoản ADMIN (Quản lý cấp cao)
        // Dùng updateOrCreate để chạy lại seed nhiều lần không bị lỗi trùng email
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // Điều kiện tìm kiếm
            [
                'name'              => 'Admin Quản Trị',
                'password'          => Hash::make('password'), // Mật khẩu: 12345678
                'role'              => 'admin', // Đảm bảo role khớp với logic phân quyền của bạn
                'email_verified_at' => now(),
            ]
        );

        // 2. Tạo tài khoản NHÂN VIÊN (Để test giới hạn quyền)
        User::updateOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name'              => 'Nhân Viên Bàn 1',
                'password'          => Hash::make('password'),
                'role'              => 'staff',
                'email_verified_at' => now(),
            ]
        );

        // 3. (Tùy chọn) Tạo thêm 10 user ảo ngẫu nhiên nếu cần
        // User::factory(10)->create();
    }

}
