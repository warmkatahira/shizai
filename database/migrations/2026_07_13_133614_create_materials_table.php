<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 資材マスタ。全社共通で発注できる品目を管理する。
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('品名');
            $table->string('category')->nullable()->comment('カテゴリ');
            $table->string('unit')->default('個')->comment('単位（個/箱/本など）');
            $table->unsignedInteger('unit_price')->nullable()->comment('参考単価（円）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
