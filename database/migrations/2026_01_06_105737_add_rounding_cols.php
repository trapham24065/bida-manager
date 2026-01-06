<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cấu hình vào bảng cài đặt
        Schema::table('shop_settings', function (Blueprint $table) {
            // mode: none (tắt), down (xuống - 43.200 -> 43.000), up (lên - 43.200 -> 44.000), auto (thường)
            $table->string('rounding_mode')->default('none');
        });

        // 2. Thêm cột lưu tiền lệch vào hóa đơn
        Schema::table('game_sessions', function (Blueprint $table) {
            // Ví dụ: Làm tròn từ 43.200 xuống 43.000 -> rounding_amount = -200
            $table->integer('rounding_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

};
