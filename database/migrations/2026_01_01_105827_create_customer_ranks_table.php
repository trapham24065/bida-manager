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
        Schema::create('customer_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên hạng (Vàng, Bạc...)
            $table->decimal('min_spending', 15, 0)->default(0); // Mức chi tiêu tối thiểu để đạt hạng
            $table->integer('discount_percent')->default(0); // % Giảm giá hưởng
            $table->string('color')->default('info'); // Màu sắc hiển thị (primary, danger, warning...)
            $table->timestamps();
        });

        // Bổ sung cột rank_id vào bảng customers để liên kết
        Schema::table('customers', function (Blueprint $table) {
            // Xóa cột rank cũ (dạng chữ) nếu muốn dùng rank động hoàn toàn,
            // hoặc giữ lại để tham khảo. Ở đây mình thêm cột mới chuẩn hơn.
            $table->foreignId('customer_rank_id')->nullable()->constrained('customer_ranks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ranks');
    }

};
