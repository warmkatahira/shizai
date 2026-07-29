<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 業者マスタに「正式名称」を追加する。
 *
 * 発注書の「〜御中」だけは「株式会社」まで入った正式名称で出したいが、
 * 一覧・集計・明細では長すぎて邪魔になる。そこで name は短い表示名のまま残し、
 * 正式名称だけを別に持つ。未入力なら name をそのまま使う（Supplier::formalName）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('formal_name')->nullable()->after('name')
                ->comment('正式名称（発注書の宛名に使う。空なら name）');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('formal_name');
        });
    }
};
