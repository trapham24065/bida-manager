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
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables'); // Liên kết với bàn nào
            $table->dateTime('start_time'); // Giờ bắt đầu
            $table->dateTime('end_time')->nullable(); // Giờ kết thúc (ban đầu sẽ null)
            $table->decimal('total_money', 15, 2)->default(0); // Tổng tiền
            $table->string('status')->default('running'); // running hoặc completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }

};
