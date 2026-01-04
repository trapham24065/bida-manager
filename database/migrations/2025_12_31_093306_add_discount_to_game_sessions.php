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
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->integer('discount_percent')->default(0); // Giảm theo % (VD: 10%)
            $table->decimal('discount_amount', 15, 0)->default(0); // Giảm tiền mặt (VD: 20.000)
            $table->text('note')->nullable(); // Ghi chú lý do giảm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            //
        });
    }

};
