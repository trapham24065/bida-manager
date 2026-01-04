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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên món (Sting, Mì trứng...)
            $table->decimal('price', 10, 2); // Giá bán
            $table->integer('stock')->default(0); // Tồn kho
            $table->boolean('is_active')->default(true); // Còn bán hay không
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }

};
