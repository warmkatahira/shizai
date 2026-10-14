<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 既存のDB（本番）に、所長承認のコメント・希望納期の変更・「最短」の列を足す。
     *
     * 列の定義そのものは create_orders_table にある（1テーブル1ファイルの規約）。
     * あちらは既存のDBではもう実行済みで効かないので、列が無いときだけここで足す。
     * 新しく作ったDBでは create_orders_table が列を作っているので、ここでは何もしない。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'manager_comment')) {
                $table->text('manager_comment')->nullable()->after('manager_approved_at')
                    ->comment('所長承認時のコメント（この発注に対するもの）');
            }
            if (! Schema::hasColumn('orders', 'manager_delivery_change')) {
                $table->string('manager_delivery_change')->nullable()->after('manager_comment')
                    ->comment('所長による希望納期の変更内容');
            }
            if (! Schema::hasColumn('orders', 'is_delivery_asap')) {
                $table->boolean('is_delivery_asap')->default(false)->after('desired_delivery_date')
                    ->comment('希望納期＝最短');
            }
        });
    }

    /**
     * 戻さない。列は create_orders_table の定義なので、ここで消すと
     * 新しいDBでも create_orders_table が作った列まで消えてしまう。
     */
    public function down(): void
    {
        //
    }
};
