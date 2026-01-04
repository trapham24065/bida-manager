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
        Schema::table('stock_inputs', function (Blueprint $table) {
            // 1. Thêm cột user_id (người nhập)
            if (!Schema::hasColumn('stock_inputs', 'user_id')) {
                // nullable() để tránh lỗi dữ liệu cũ, constrained() để liên kết bảng users
                $table->foreignId('user_id')->nullable()->after('id')->constrained();
            }

            // 2. Thêm cột old_stock (tồn cũ)
            if (!Schema::hasColumn('stock_inputs', 'old_stock')) {
                $table->integer('old_stock')->default(0)->after('quantity');
            }

            // 3. Thêm cột new_stock (tồn mới)
            if (!Schema::hasColumn('stock_inputs', 'new_stock')) {
                $table->integer('new_stock')->default(0)->after('old_stock');
            }

            // 4. Kiểm tra xem có cột import_price chưa (đề phòng)
            if (!Schema::hasColumn('stock_inputs', 'import_price')) {
                $table->decimal('import_price', 15, 0)->default(0)->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_inputs', function (Blueprint $table) {
            //
        });
    }

};
