<?php

/**
 * Bảng Nguyên liệu (Ingredients)
 * Dùng để quản lý nguyên liệu pha chế như: cà phê, sữa, đường, trà...
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // Tạo bảng nếu chưa tồn tại
        if (!Schema::hasTable('ingredients')) {
            Schema::create('ingredients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('unit')->default('g');
                $table->decimal('stock', 12, 2)->default(0);
                $table->decimal('cost_per_unit', 12, 0)->default(0);
                $table->decimal('min_stock', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            // Thêm các cột còn thiếu nếu bảng đã tồn tại
            Schema::table('ingredients', function (Blueprint $table) {
                if (!Schema::hasColumn('ingredients', 'min_stock')) {
                    $table->decimal('min_stock', 12, 2)->default(0)->after('cost_per_unit');
                }
                if (!Schema::hasColumn('ingredients', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('min_stock');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
