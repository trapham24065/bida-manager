<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            // Thêm 2 cột created_at và updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }

};
