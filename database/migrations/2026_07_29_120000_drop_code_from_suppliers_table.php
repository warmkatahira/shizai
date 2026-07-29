<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 業者マスタの「業者コード」を廃止する。
 *
 * 作ったものの結局どこからも参照していなかった（資材は supplier_id で紐づき、
 * CSV取り込みの突合も業者名で行う）。全業者で未入力のままだったので、データの欠損は無い。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // 列に unique が付いているので、先にインデックスを落とす
            $table->dropUnique('suppliers_code_unique');
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name')->comment('業者コード');
        });
    }
};
