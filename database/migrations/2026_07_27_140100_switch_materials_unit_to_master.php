<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 資材の単位を「文字列」から「単位マスタへの参照(unit_id)」に切り替える。
 * あわせて冗長だった最低ロットの単位(min_lot_unit)を廃止する
 * （発注は最低ロットの倍数で行うため、ロットの単位は資材の単位と必ず一致する）。
 *
 * 既存データは、既存の unit 文字列から単位マスタを作って紐付けし直す（欠損させない）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) 参照列を追加
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('height_mm')->constrained()->nullOnDelete();
        });

        // 2) 既存の unit 文字列 → 単位マスタを作って紐付け
        $names = DB::table('materials')->whereNotNull('unit')->where('unit', '<>', '')
            ->distinct()->orderBy('unit')->pluck('unit');

        foreach ($names as $i => $name) {
            $id = DB::table('units')->where('name', $name)->value('id')
                ?? DB::table('units')->insertGetId([
                    'name' => $name,
                    'sort_order' => $i,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('materials')->where('unit', $name)->update(['unit_id' => $id]);
        }

        // 3) 不要になった文字列カラムを削除
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['unit', 'min_lot_unit']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('min_lot_unit');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('unit', 20)->default('個')->after('height_mm');
            $table->string('min_lot_unit', 20)->nullable()->after('min_lot_qty');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('min_lot_unit', 20)->nullable()->after('min_lot_qty');
        });

        // 単位名を文字列カラムへ戻す
        $units = DB::table('units')->pluck('name', 'id');
        foreach ($units as $id => $name) {
            DB::table('materials')->where('unit_id', $id)->update(['unit' => $name]);
            DB::table('materials')->where('unit_id', $id)->update(['min_lot_unit' => DB::raw("CASE WHEN min_lot_qty IS NULL THEN NULL ELSE '{$name}' END")]);
        }

        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
