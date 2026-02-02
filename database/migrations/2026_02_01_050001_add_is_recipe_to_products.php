<?php

/**
 * Thêm cột is_recipe vào bảng products
 * Đánh dấu sản phẩm là đồ pha chế (tồn kho tính theo nguyên liệu)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // is_recipe = true: Sản phẩm pha chế (tồn kho tính từ nguyên liệu)
            // is_recipe = false: Sản phẩm thường (tồn kho trực tiếp)
            if (!Schema::hasColumn('products', 'is_recipe')) {
                $table->boolean('is_recipe')->default(false)->after('is_combo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_recipe');
        });
    }

};

