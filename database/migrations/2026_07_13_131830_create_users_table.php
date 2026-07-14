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
            $table->string('email')->unique();
            $table->string('role')->default('sales')->comment('権限: admin/general_affairs/sales');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete()->comment('所属営業所');
            $table->boolean('is_manager')->default(false)->comment('所長フラグ');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
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
