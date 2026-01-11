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
        // 1. Thêm thuế cho Sản phẩm
        Schema::table('products', function (Blueprint $table) {
            $table->integer('tax_rate')->default(0); // VD: 0, 8, 10
        });

        // 2. Thêm thuế cho Loại bàn (Để tính thuế giờ chơi)
        Schema::table('table_types', function (Blueprint $table) {
            $table->integer('tax_rate')->default(0);
        });

        // 3. (Quan trọng) Lưu snapshot thuế vào OrderItem
        // Để sau này lỡ sản phẩm đổi thuế, hóa đơn cũ không bị sai
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('tax_rate')->default(0); // Lưu lúc order
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
