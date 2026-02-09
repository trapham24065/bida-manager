<?php

/**
 * Thêm cột hỗ trợ tạm dừng/tiếp tục bàn
 * - paused_at: Thời điểm bắt đầu tạm dừng (null = đang chạy)
 * - total_paused_seconds: Tổng thời gian đã tạm dừng (tính bằng giây)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('game_sessions', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('game_sessions', 'total_paused_seconds')) {
                $table->integer('total_paused_seconds')->default(0)->after('paused_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'total_paused_seconds']);
        });
    }
};

