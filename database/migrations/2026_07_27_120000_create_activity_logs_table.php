<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 操作ログ（監査ログ）。管理者だけが閲覧する「誰が・いつ・何をしたか」の記録。
 *
 * ・ユーザーが後で削除・改名されてもログは読めるように user_name を控えておく
 *   （user_id は参照用。ユーザー削除時は null にして行は残す）
 * ・対象（発注・資材など）は subject_type + subject_id で緩く紐付ける（外部キーは張らない）
 * ・更新はしないので created_at のみ持つ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name');                 // 操作者名のスナップショット
            $table->string('action', 60);                // 例：order.manager_approved
            $table->string('description');               // 日本語の要約
            $table->string('subject_type', 40)->nullable(); // 対象の種別（Order など。class_basename）
            $table->unsignedBigInteger('subject_id')->nullable(); // 対象のID
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            // 一覧は日時の新しい順。種別・営業所・操作者で絞り込む
            $table->index('created_at');
            $table->index('action');
            $table->index(['office_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
