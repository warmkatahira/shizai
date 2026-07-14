<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注明細テーブル。申請時点の資材情報をスナップショット保存する
     * （マスタが後で変わっても過去の申請内容は変わらないように）。
     * *_id は検索用の参照、*_name は申請当時の表示用。
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->comment('発注申請');
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete()->comment('資材（参照用）');
            $table->string('material_name')->comment('申請時の品名');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->comment('申請時のカテゴリ（参照用）');
            $table->string('category_name')->nullable()->comment('申請時のカテゴリ名');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('申請時の業者（参照用）');
            $table->string('supplier_name')->nullable()->comment('申請時の業者名');
            $table->string('unit')->comment('申請時の単位');
            // 資材マスタの単価に合わせて decimal（整数だと 34.5 円が 34 円になってしまう）
            $table->decimal('unit_price', 10, 2)->nullable()->comment('申請時の参考単価');
            $table->unsignedInteger('quantity')->comment('数量');

            // 発注書に印字する項目。マスタが変わっても発注書を再発行できるようスナップショットする
            $table->unsignedInteger('length_mm')->nullable()->comment('申請時の縦（mm）');
            $table->unsignedInteger('width_mm')->nullable()->comment('申請時の横（mm）');
            $table->unsignedInteger('height_mm')->nullable()->comment('申請時の高さ（mm）');
            $table->unsignedInteger('min_lot_qty')->nullable()->comment('申請時の最低ロット数量');
            $table->string('min_lot_unit', 20)->nullable()->comment('申請時の最低ロットの単位');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
