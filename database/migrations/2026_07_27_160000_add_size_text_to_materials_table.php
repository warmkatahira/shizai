<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 資材の自由入力サイズ表記（例：「粒外袋 W200×H300」）。
 * 縦横高（length/width/height）とは別に、規格名つきの表記を自由に書けるようにする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('size_text', 100)->nullable()->after('height_mm');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('size_text');
        });
    }
};
