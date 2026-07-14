<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 差し戻し（returned）を追加する。
     *
     * 却下（rejected）はそこで終わりだが、差し戻しは申請者まで戻して
     * 内容を修正させ、もう一度申請してもらうためのもの。
     * 再申請すると承認は最初からやり直しになる（承認履歴はクリアされる）が、
     * 「なぜ差し戻されたか」は承認者が見たいので、この3列は再申請後も残す。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('returned_by')->nullable()->after('rejected_by')
                ->constrained('users')->nullOnDelete()->comment('差し戻した人（所長・総務のどちらか）');
            $table->timestamp('returned_at')->nullable()->after('returned_by')->comment('差し戻した日時');
            $table->text('return_reason')->nullable()->after('returned_at')->comment('差し戻しの理由');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['returned_at', 'return_reason']);
        });
    }
};
