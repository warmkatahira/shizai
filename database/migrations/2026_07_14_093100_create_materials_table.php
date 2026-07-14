<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 資材マスタ。全社共通で発注できる品目を管理する。
     * 項目は社内の「資材発注 詳細確認リスト」に対応。
     * 担当者名・連絡先・発注方法は業者ごとに決まるので suppliers 側に持つ。
     * ※ categories / suppliers を参照するため、それらより後に実行する。
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('品名');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->comment('商品カテゴリ');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('発注業者');

            $table->unsignedInteger('length_mm')->nullable()->comment('縦（mm）');
            $table->unsignedInteger('width_mm')->nullable()->comment('横（mm）');
            $table->unsignedInteger('height_mm')->nullable()->comment('高さ（mm）');

            $table->string('unit')->default('個')->comment('単位（個/箱/本など）');
            // 実データに 34.5円 / 6.07円 のような小数があるため decimal
            $table->decimal('unit_price', 10, 2)->nullable()->comment('参考単価（円）');

            $table->unsignedInteger('min_lot_qty')->nullable()->comment('最低ロット数量');
            $table->string('min_lot_unit', 20)->nullable()->comment('最低ロットの単位（枚/ケース/本 など）');

            $table->boolean('has_imprint')->default(false)->comment('名入れフラグ');
            $table->text('note')->nullable()->comment('備考');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
