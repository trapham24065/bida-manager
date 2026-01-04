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
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            // Sản phẩm cha (Là cái Combo)
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Sản phẩm con (Là bia, mực...)
            $table->foreignId('related_product_id')->constrained('products');

            // Số lượng trong combo (VD: 5 lon)
            $table->integer('quantity');
            $table->timestamps();
        });

        // Thêm cột đánh dấu vào bảng products xem món này có phải là Combo không
        if (!Schema::hasColumn('products', 'is_combo')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_combo')->default(false)->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_combos');
    }

};
