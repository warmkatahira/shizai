<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 業者マスタの電話番号を「固定電話」と「携帯電話」の2つにする。
 *
 * 既存の `phone` に入っているのは固定電話なので、列は作り直さず
 * そのまま固定電話として使い、携帯電話ぶんだけを足す（データ移行が不要）。
 * 発注書に印字するのは固定電話とFAX。携帯電話は入っているときだけ添える。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('mobile_phone', 30)->nullable()->after('phone')
                ->comment('携帯電話番号');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('mobile_phone');
        });
    }
};
