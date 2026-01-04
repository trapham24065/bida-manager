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
        Schema::create('table_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên loại (VD: Bàn VIP)
            $table->timestamps();
        });

        // Thêm cột liên kết vào bảng TABLES
        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('table_type_id')->nullable()->constrained('table_types')->nullOnDelete();
            // Bạn có thể xóa cột 'type' cũ nếu muốn, hoặc cứ để đó
        });

        // Thêm cột liên kết vào bảng PRICING_RULES
        Schema::table('pricing_rules', function (Blueprint $table) {
            // Xóa cột string cũ đi cho đỡ nhầm
            $table->dropColumn('table_type');
            // Thêm cột ID mới
            $table->foreignId('table_type_id')->nullable()->constrained('table_types')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_types');
    }

};
