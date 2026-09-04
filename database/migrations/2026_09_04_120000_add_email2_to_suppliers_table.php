<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 業者マスタのメールアドレスを2つにする。
 *
 * 発注のメールを担当者と事務所の両方に送りたい業者があるため。
 * 既存の `email` はそのまま1つめとして使い、2つめだけを足す（データ移行が不要）。
 * どちらも任意。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email2')->nullable()->after('email')
                ->comment('メールアドレス2');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('email2');
        });
    }
};
