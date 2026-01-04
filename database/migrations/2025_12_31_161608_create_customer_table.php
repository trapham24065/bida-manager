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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique(); // Số điện thoại là định danh duy nhất
            $table->string('email')->nullable();

            // Các cột tích điểm
            $table->decimal('total_spending', 15, 0)->default(0); // Tổng tiền đã tiêu
            $table->integer('points')->default(0); // Điểm tích lũy hiện tại
            $table->string('rank')->default('member'); // Hạng: member, silver, gold, vip

            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Bổ sung cột customer_id vào bảng game_sessions để biết hóa đơn này của ai
        if (!Schema::hasColumn('game_sessions', 'customer_id')) {
            Schema::table('game_sessions', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }

};
