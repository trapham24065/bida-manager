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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Ai nhập?
            $table->integer('quantity'); // Số lượng nhập thêm
            $table->integer('old_stock'); // Tồn kho trước khi nhập
            $table->integer('new_stock'); // Tồn kho sau khi nhập
            $table->decimal('cost_price', 15, 0); // Giá vốn lúc nhập
            $table->text('note')->nullable(); // Ghi chú
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }

};
