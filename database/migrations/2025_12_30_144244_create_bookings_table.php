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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete(); // Đặt bàn nào
            $table->string('customer_name'); // Tên khách
            $table->string('phone'); // SĐT để liên hệ
            $table->dateTime('booking_time'); // Giờ hẹn
            $table->integer('duration_minutes')->default(60); // Dự kiến chơi bao lâu (để giữ bàn)
            $table->enum('status', ['pending', 'checked_in', 'cancelled'])->default('pending'); // Trạng thái
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }

};
