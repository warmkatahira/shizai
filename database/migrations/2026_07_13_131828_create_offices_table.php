<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 営業所テーブル。発注元となる各営業所を管理する。
     */
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('営業所名');
            $table->string('code')->nullable()->unique()->comment('営業所コード');
            $table->string('short_name', 20)->nullable()->comment('略称（第1, ロジS など）');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->string('address')->nullable()->comment('住所');
            $table->string('tel', 20)->nullable()->comment('電話番号');
            $table->string('fax', 20)->nullable()->comment('FAX番号');
            $table->unsignedInteger('sort_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
