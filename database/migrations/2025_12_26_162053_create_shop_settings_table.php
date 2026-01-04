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
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('CLB Bida');
            $table->string('wifi_pass')->nullable();

            // Thông tin ngân hàng
            $table->string('bank_id')->default('MB'); // Mã NH (MB, VCB...)
            $table->string('bank_account')->nullable(); // Số tài khoản
            $table->string('bank_account_name')->nullable(); // Tên chủ TK

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }

};
