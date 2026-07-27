<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 操作ログの記録オン/オフ設定。action ごとに1行。
 *
 * 行が無い action は config/activity_log.php の default に従う（既定はオン）。
 * つまりこのテーブルは「既定から変えた分」だけを持つ。設定画面で編集する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log_settings', function (Blueprint $table) {
            $table->id();
            $table->string('action', 60)->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_settings');
    }
};
