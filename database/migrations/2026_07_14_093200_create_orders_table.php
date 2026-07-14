<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注申請テーブル（ヘッダー）。2段階承認（所長→総務）と総務の特例承認に対応。
     * status: pending_manager=所長承認待ち / pending_affairs=総務承認待ち / ordered=発注済 / rejected=却下
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->comment('発注元の営業所');
            $table->foreignId('requested_by')->constrained('users')->comment('申請者');
            $table->string('status')->default('pending_manager')->comment('状態: pending_manager/pending_affairs/ordered/rejected');

            // 所長承認
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('所長承認者');
            $table->timestamp('manager_approved_at')->nullable()->comment('所長承認日時');

            $table->text('note')->nullable()->comment('申請者メモ');

            // 総務の確認・発注
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->comment('総務の確認者');
            $table->timestamp('reviewed_at')->nullable()->comment('確認日時');

            // 総務による特例承認（所長承認を飛ばした場合）
            $table->boolean('is_special_approval')->default(false)->comment('総務の特例承認フラグ');
            $table->text('special_reason')->nullable()->comment('特例承認の理由');

            // 却下
            $table->text('reject_reason')->nullable()->comment('却下理由');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->comment('却下者（所長・総務のどちらか）');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
