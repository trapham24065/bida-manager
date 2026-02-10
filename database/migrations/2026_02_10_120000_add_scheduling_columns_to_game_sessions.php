<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('game_sessions', 'scheduled_until')) {
                $table->timestamp('scheduled_until')->nullable()->after('total_paused_seconds');
            }
            if (!Schema::hasColumn('game_sessions', 'scheduled_minutes')) {
                $table->integer('scheduled_minutes')->nullable()->after('scheduled_until');
            }
            if (!Schema::hasColumn('game_sessions', 'scheduled_auto_pause')) {
                $table->boolean('scheduled_auto_pause')->default(false)->after('scheduled_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['scheduled_until', 'scheduled_minutes', 'scheduled_auto_pause']);
        });
    }
};
