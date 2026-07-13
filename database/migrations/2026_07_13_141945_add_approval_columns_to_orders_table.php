<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 2段階承認（所長→総務）と特例承認のためのカラムを追加する。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 所長承認
            $table->foreignId('manager_approved_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete()->comment('所長承認者');
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by')->comment('所長承認日時');

            // 総務による特例承認（所長を飛ばした場合）
            $table->boolean('is_special_approval')->default(false)->after('reviewed_at')->comment('総務の特例承認フラグ');
            $table->text('special_reason')->nullable()->after('is_special_approval')->comment('特例承認の理由');

            // 却下者（所長・総務のどちらが却下したか）
            $table->foreignId('rejected_by')->nullable()->after('reject_reason')
                ->constrained('users')->nullOnDelete()->comment('却下者');
        });

        // 既存の 'pending' レコードを新ステータスへ移行
        DB::table('orders')->where('status', 'pending')->update(['status' => 'pending_manager']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['manager_approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'manager_approved_by', 'manager_approved_at',
                'is_special_approval', 'special_reason', 'rejected_by',
            ]);
        });
    }
};
