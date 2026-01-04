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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ví dụ: "Giờ vàng", "Buổi tối"
            $table->time('start_time'); // Giờ bắt đầu (VD: 08:00)
            $table->time('end_time');   // Giờ kết thúc (VD: 17:00)
            $table->decimal('price_per_hour', 10, 0); // Giá trong khung giờ này
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }

};
