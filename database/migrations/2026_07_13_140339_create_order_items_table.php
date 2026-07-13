<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注明細テーブル。申請時点の資材情報をスナップショット保存する
     * （マスタが後で変わっても過去の申請内容は変わらないように）。
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->comment('発注申請');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete()->comment('資材（参照用）');
            $table->string('material_name')->comment('申請時の品名');
            $table->string('unit')->comment('申請時の単位');
            $table->unsignedInteger('unit_price')->nullable()->comment('申請時の参考単価');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
