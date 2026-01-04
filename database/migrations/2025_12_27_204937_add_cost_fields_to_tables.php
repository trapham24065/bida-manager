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
        Schema::table('tables', function (Blueprint $table) {
            // 1. Thêm giá vốn hiện tại vào bảng Sản phẩm
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('cost_price', 15, 2)->default(0)->after('price'); // Giá vốn trung bình
            });

            // 2. Thêm giá vốn và lợi nhuận vào chi tiết đơn hàng (Lịch sử)
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('cost', 15, 2)->default(0)->after('price'); // Giá vốn tại thời điểm bán
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }

};
