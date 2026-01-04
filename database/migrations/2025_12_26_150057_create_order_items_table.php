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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Liên kết với Lượt chơi (để biết bàn nào gọi)
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            // Liên kết món ăn
            $table->foreignId('product_id')->constrained('products');

            $table->integer('quantity'); // Số lượng
            $table->decimal('price', 10, 2); // Giá tại thời điểm gọi
            $table->decimal('total', 10, 2); // Tổng tiền (sl * giá)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }

};
