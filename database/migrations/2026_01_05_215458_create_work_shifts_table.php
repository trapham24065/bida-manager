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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Ai làm ca này
            $table->timestamp('start_time');             // Giờ bắt đầu
            $table->timestamp('end_time')->nullable();   // Giờ kết thúc

            $table->double('initial_cash')->default(0);  // Tiền đầu ca (Vốn)

            // Các con số hệ thống tự tính
            $table->double('total_cash_money')->default(0);     // Tổng tiền mặt thu được
            $table->double('total_transfer_money')->default(0); // Tổng chuyển khoản

            // Con số nhân viên nhập
            $table->double('reported_cash')->nullable();        // Tiền thực tế đếm được
            $table->double('difference')->default(0);           // Số tiền lệch

            $table->text('note')->nullable(); // Ghi chú
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }

};
