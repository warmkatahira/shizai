<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * パスワードの変更を強制するフラグ。
     *
     * 立っているあいだは、パスワードを変えるまで他の画面を開けない
     * （App\Http\Middleware\EnsurePasswordChanged）。
     * 既存ユーザーは false（今までどおり使える）。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)
                ->after('is_active')
                ->comment('次回ログイン時にパスワードの変更を強制する');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
