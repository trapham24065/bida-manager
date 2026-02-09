<?php

/**
 * Thêm cột hỗ trợ tính năng ghép hóa đơn và đổi bàn
 * - merged_into_session_id: Khi ghép, phiên này sẽ chuyển hết dữ liệu sang phiên khác
 * - transferred_from_session_id: Khi đổi bàn, lưu phiên chơi trước đó
 * - transfer_reason: Lý do đổi bàn (vd: "Bàn cũ bị hỏng")
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // Hỗ trợ ghép hóa đơn
            if (!Schema::hasColumn('game_sessions', 'merged_into_session_id')) {
                $table->foreignId('merged_into_session_id')
                    ->nullable()
                    ->after('total_paused_seconds')
                    ->constrained('game_sessions')
                    ->onDelete('set null');
            }

            // Hỗ trợ đổi bàn
            if (!Schema::hasColumn('game_sessions', 'transferred_from_session_id')) {
                $table->foreignId('transferred_from_session_id')
                    ->nullable()
                    ->after('merged_into_session_id')
                    ->constrained('game_sessions')
                    ->onDelete('set null');
            }

            // Lý do đổi bàn
            if (!Schema::hasColumn('game_sessions', 'transfer_reason')) {
                $table->string('transfer_reason')->nullable()->after('transferred_from_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_session_id');
            $table->dropConstrainedForeignId('transferred_from_session_id');
            $table->dropColumn('transfer_reason');
        });
    }
};
