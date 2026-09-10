<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 直送先テーブル。
     *
     * 発注申請の納入先は普段「申請した営業所」だが、客先や他社の倉庫など
     * **自営業所以外へ直接送りたい**ことがある。その送り先をここで管理する。
     * 全営業所で共通（どの営業所からでも選べる）。
     *
     * 住所は発注書の【納入先】欄にそのまま印字するので必須。
     */
    public function up(): void
    {
        Schema::create('shipping_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('直送先名（プルダウンに出る名前）');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->string('address')->comment('住所（発注書に印字するので必須）');
            $table->string('tel', 20)->nullable()->comment('電話番号');
            $table->string('fax', 20)->nullable()->comment('FAX番号');
            $table->unsignedInteger('sort_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });

        // orders.shipping_destination_id の外部キーはここで張る。
        // orders は shipping_destinations より先に作られるので、列だけあちらで用意してある
        // （このマイグレーションより前に作られた既存のDBには列が無いので、その場合はここで足す）。
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_destination_id')) {
                $table->unsignedBigInteger('shipping_destination_id')->nullable()->after('office_id')
                    ->comment('直送先（null＝発注元の営業所へ納入）');
            }

            $table->foreign('shipping_destination_id')->references('id')->on('shipping_destinations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_destination_id']);
        });

        Schema::dropIfExists('shipping_destinations');
    }
};
