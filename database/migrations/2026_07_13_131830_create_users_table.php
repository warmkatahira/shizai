<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ユーザーテーブル。
     * role: admin=管理者 / general_affairs=総務 / sales=営業所
     * 営業所ユーザーのうち is_manager=true は所長（自営業所の一次承認者）。
     * ※ office_id が offices を参照するため、offices テーブルより後に実行する。
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // ログインはメールではなく login_id で行う。
            // 営業所の申請用アカウントは共通で使い回すため、実在のメールアドレスを持たないことがある。
            $table->string('login_id')->unique()->comment('ログインID');
            // メールは通知の宛先。無ければ通知を送らないだけで、ログインには影響しない
            $table->string('email')->nullable()->unique()->comment('通知先メールアドレス（任意）');
            $table->string('role')->default('sales')->comment('権限: admin/general_affairs/sales');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete()->comment('所属営業所');
            $table->boolean('is_manager')->default(false)->comment('所長フラグ');
            // アカウントごとに閲覧・編集を許可するマスタ（管理者・総務は常に全部なので参照しない）。
            // 判定は User::canViewMaster / canEditMaster
            $table->json('visible_masters')->nullable()->comment('閲覧を許可するマスタのキー');
            $table->json('editable_masters')->nullable()->comment('編集を許可するマスタのキー（閲覧も含む）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            // 立っているあいだは、パスワードを変えるまで他の画面を開けない
            // （App\Http\Middleware\EnsurePasswordChanged）
            $table->boolean('must_change_password')->default(false)
                ->comment('次回ログイン時にパスワードの変更を強制する');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
