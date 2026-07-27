<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 発注明細に、申請時点の資材の自由入力サイズ（size_text）を焼き付ける。
 * 発注書の「寸法」欄はこれを引用する（マスタが後で変わっても発注書は不変）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size_text', 100)->nullable()->after('height_mm');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('size_text');
        });
    }
};
