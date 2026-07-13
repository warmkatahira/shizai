<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注申請テーブル（ヘッダー）。
     * status: pending=申請中 / ordered=発注済 / rejected=却下
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->comment('発注元の営業所');
            $table->foreignId('requested_by')->constrained('users')->comment('申請者');
            $table->string('status')->default('pending')->comment('状態: pending/ordered/rejected');
            $table->text('note')->nullable()->comment('申請者メモ');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->comment('総務の確認者');
            $table->timestamp('reviewed_at')->nullable()->comment('確認日時');
            $table->text('reject_reason')->nullable()->comment('却下理由');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
