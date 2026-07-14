<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注申請テーブル（ヘッダー）。2段階承認（所長→総務）と総務の特例承認に対応。
     * status: pending_manager=所長承認待ち / pending_affairs=総務承認待ち / ordered=発注済 / rejected=却下
     *
     * 1申請＝1業者。業者を選び、その業者の資材だけを申請する（発注書も1申請1枚）。
     *
     * 総務が承認すると「発注待ち」になり、発注書をダウンロードした時点で「発注済」になる
     * （＝実際に業者へ発注したタイミングでステータスが進む）。
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->comment('発注元の営業所');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()->comment('発注先の業者');
            $table->foreignId('requested_by')->constrained('users')->comment('申請したログインアカウント');
            // 営業所のアカウントは共通で使い回すため、実際の申請者の氏名を別に持つ
            $table->string('requester_name')->nullable()->comment('発注者の氏名（手入力）');
            $table->string('status')->default('pending_manager')
                ->comment('状態: pending_manager/pending_affairs/pending_order/ordered/rejected');

            // 所長承認
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('所長承認者');
            $table->timestamp('manager_approved_at')->nullable()->comment('所長承認日時');

            $table->text('note')->nullable()->comment('申請者メモ（社内用。発注書には印字しない）');
            $table->text('supplier_note')->nullable()->comment('業者への連絡事項（発注書の備考欄に印字する）');
            $table->date('desired_delivery_date')->nullable()->comment('希望納期（発注書に印字）');

            // 総務の確認・発注
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->comment('総務の確認者');
            $table->timestamp('reviewed_at')->nullable()->comment('総務が承認した日時');

            // 発注書を出した時点で「発注済」になる（＝実際に業者へ発注した日）
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete()->comment('発注書を出した人');
            $table->timestamp('ordered_at')->nullable()->comment('発注日（発注書を出した日）');

            // 総務による特例承認（所長承認を飛ばした場合）
            $table->boolean('is_special_approval')->default(false)->comment('総務の特例承認フラグ');
            $table->text('special_reason')->nullable()->comment('特例承認の理由');

            // 却下
            $table->text('reject_reason')->nullable()->comment('却下理由');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->comment('却下者（所長・総務のどちらか）');

            $table->timestamps();

            // 集計（/reports）は「発注済 × 発注日の期間」で絞るので、その複合インデックス
            $table->index(['status', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
