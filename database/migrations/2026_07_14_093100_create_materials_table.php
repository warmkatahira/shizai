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
     *
     * 単位は単位マスタへの参照（unit_id）。units はこのテーブルより後に作られるので、
     * ここでは列だけ用意し、外部キーは create_units_table 側で張る。
     * 3辺計は列に持たない（縦横高から求まる。DescribesMaterial::girthMm）。
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
            // 実際に運送会社で測られるサイズ（60サイズ・80サイズ など）。運用で分かる値なので手入力
            $table->string('shipping_size', 10)->nullable()->comment('発送時サイズ（60サイズ・80サイズ など）');
            // 縦横高とは別に、規格名つきの表記を自由に書ける（例：「粒外袋 W200×H300」）
            $table->string('size_text', 100)->nullable()->comment('自由入力のサイズ表記');

            // 単位マスタへの参照。外部キーは create_units_table で張る（units は後で作られるため）
            $table->foreignId('unit_id')->nullable()->index()->comment('単位（単位マスタ）');
            // 実データに 34.5円 / 6.07円 のような小数があるため decimal
            $table->decimal('unit_price', 10, 2)->nullable()->comment('参考単価（円）');

            // 最低ロットは「下限」であって単位ではない（ロット以上なら端数でよい）。
            // ロットの単位は資材の単位と必ず一致するので別に持たない
            $table->unsignedInteger('min_lot_qty')->nullable()->comment('最低ロット数量');

            $table->boolean('has_imprint')->default(false)->comment('名入れフラグ');
            $table->text('note')->nullable()->comment('備考');
            // public ディスク上の保存パス（例：materials/xxxx.jpg）。
            // 発注明細にはスナップショットしない（画像は「今のマスタの見た目」で十分なため）
            $table->string('image_path')->nullable()->comment('画像の保存パス');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
