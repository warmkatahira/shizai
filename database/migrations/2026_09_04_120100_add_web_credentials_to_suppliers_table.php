<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 発注方法が「web」の業者ぶんの、発注サイトのURL・ログインID・パスワードを持つ。
 *
 * サイボウズ・ロジレスなどの専用システムは業者ごとにアカウントが違い、
 * 発注する人がその都度どこかを探している状態なので業者マスタに置く。
 * すべて任意（分かっているものだけ入れる）。
 * パスワードは「後で見て入力するもの」なのでハッシュ化せずそのまま保存する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('web_url')->nullable()->after('order_method')
                ->comment('発注サイトのURL（発注方法がwebの業者）');
            $table->string('web_login_id', 100)->nullable()->after('web_url')
                ->comment('発注サイトのログインID');
            $table->string('web_password', 100)->nullable()->after('web_login_id')
                ->comment('発注サイトのパスワード');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['web_url', 'web_login_id', 'web_password']);
        });
    }
};
