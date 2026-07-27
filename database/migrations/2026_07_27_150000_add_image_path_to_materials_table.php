<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 資材の画像。public ディスク上の保存パスを持つ（例：materials/xxxx.jpg）。
 * 発注明細にはスナップショットしない（画像は「今のマスタの見た目」で十分なため）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
