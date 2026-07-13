<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ユーザーに権限(role)と所属営業所(office_id)を追加する。
     * role: admin=管理者 / general_affairs=総務 / sales=営業所
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('sales')->after('email')->comment('権限: admin/general_affairs/sales');
            $table->foreignId('office_id')->nullable()->after('role')->constrained('offices')->nullOnDelete()->comment('所属営業所');
            $table->boolean('is_active')->default(true)->after('office_id')->comment('有効フラグ');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['role', 'office_id', 'is_active']);
        });
    }
};
