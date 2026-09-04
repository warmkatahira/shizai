<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 業者マスタ。資材の仕入先を管理する。
     *
     * 名前は2つ持つ。name＝短い表示名（一覧・集計・明細はすべてこちら）、
     * formal_name＝正式名称（発注書の宛名「〜御中」だけに使う。空なら name）。
     * 担当者名・連絡先・発注方法は業者ごとに決まるので、資材ではなくここに持つ。
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('業者名');
            $table->string('formal_name')->nullable()->comment('正式名称（発注書の宛名に使う。空なら name）');
            $table->string('contact_person')->nullable()->comment('担当者名');
            // 電話は2つ。担当者が外に出ている業者にかけるため（発注書に印字するのは固定電話とFAX）
            $table->string('phone')->nullable()->comment('電話番号（固定）');
            $table->string('mobile_phone', 30)->nullable()->comment('携帯電話番号');
            $table->string('fax')->nullable()->comment('FAX番号（発注書に印字）');
            // メールは2つ。担当者と事務所の両方に送りたい業者があるため。どちらも任意
            $table->string('email')->nullable()->comment('メールアドレス');
            $table->string('email2')->nullable()->comment('メールアドレス2');
            // 発注方法は業者ごとに決まる（mail/phone/fax/web）
            $table->string('order_method', 20)->nullable()->comment('発注方法: mail/phone/fax/web');
            // 発注方法が web の業者ぶん。発注する人が毎回探さずに済むようここに置く。
            // パスワードは「後で見て入力するもの」なのでハッシュ化せずそのまま保存する
            $table->string('web_url')->nullable()->comment('発注サイトのURL（発注方法がwebの業者）');
            $table->string('web_login_id', 100)->nullable()->comment('発注サイトのログインID');
            $table->string('web_password', 100)->nullable()->comment('発注サイトのパスワード');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
