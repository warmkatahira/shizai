<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 単位マスタ（枚・ケース・本 など）。資材の単位をここで一元管理する。
 * カテゴリ・業者と同じく、資材からは unit_id で参照し、明細には名前を焼き付ける。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique()->comment('単位名（枚・ケース・本 など）');
            $table->unsignedInteger('sort_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
