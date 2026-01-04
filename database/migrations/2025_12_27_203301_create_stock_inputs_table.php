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
        Schema::create('stock_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Nhập cho món nào
            $table->integer('quantity'); // Số lượng nhập
            $table->decimal('import_price', 12, 0)->default(0); // Giá nhập vào (Giá vốn)
            $table->string('note')->nullable(); // Ghi chú (VD: Nhập của đại lý A)
            $table->timestamps(); // Lưu ngày giờ nhập
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inputs');
    }

};
