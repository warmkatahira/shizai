<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 資材マスタに「発送時サイズ」を追加する。
 *
 * その資材（主に段ボール・袋）で実際に発送したときに運送会社で測られるサイズ
 * （「60サイズ」「80サイズ」など）。3辺計から機械的に決まるものではなく、
 * 実際の運用で分かる値なので**手入力**。分からないうちは空でよい（nullable）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('shipping_size', 10)->nullable()->after('height_mm')
                ->comment('発送時サイズ（60サイズ・80サイズ など。実際に測られるサイズ）');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('shipping_size');
        });
    }
};
