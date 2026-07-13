<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注明細に業者情報をスナップショット保存する。
     * supplier_id は検索用の参照、supplier_name は申請当時の表示用。
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('material_name')
                ->constrained('suppliers')->nullOnDelete()->comment('申請時の業者（参照用）');
            $table->string('supplier_name')->nullable()->after('supplier_id')->comment('申請時の業者名');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_name']);
        });
    }
};
