<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 発注後メモを追加する。
     *
     * 発注（業者への発注）が済んだあとに、業者から言われたことや
     * その他の連絡事項を残しておくための自由記入メモ。
     * 更新できるのは総務・管理者だけだが、閲覧は全員できる。
     * 複数人で編集しうるので、最後に更新した人と日時も持つ。
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('post_order_note')->nullable()->after('return_reason')
                ->comment('発注後メモ（業者から言われたこと等。総務・管理者が更新、閲覧は全員）');
            $table->foreignId('post_order_note_updated_by')->nullable()->after('post_order_note')
                ->constrained('users')->nullOnDelete()->comment('発注後メモを最後に更新した人');
            $table->timestamp('post_order_note_updated_at')->nullable()->after('post_order_note_updated_by')
                ->comment('発注後メモを最後に更新した日時');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_order_note_updated_by');
            $table->dropColumn(['post_order_note', 'post_order_note_updated_at']);
        });
    }
};
