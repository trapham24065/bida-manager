<?php

/**
 * Bảng Công thức (Product Ingredients)
 * Liên kết sản phẩm pha chế với nguyên liệu cần dùng
 * VD: Cà phê sữa = 20g cà phê + 30ml sữa đặc + 10g đường
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // Chỉ tạo bảng nếu chưa tồn tại
        if (!Schema::hasTable('product_ingredients')) {
            Schema::create('product_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 10, 2);
                $table->timestamps();
                $table->unique(['product_id', 'ingredient_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
