<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 業者マスタ。資材の仕入先を管理する。
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('業者名');
            $table->string('code')->nullable()->unique()->comment('業者コード');
            $table->string('contact_person')->nullable()->comment('担当者名');
            $table->string('phone')->nullable()->comment('電話番号');
            $table->string('fax')->nullable()->comment('FAX番号（発注書に印字）');
            $table->string('email')->nullable()->comment('メールアドレス');
            // 発注方法は業者ごとに決まる（mail/phone/fax/web）
            $table->string('order_method', 20)->nullable()->comment('発注方法: mail/phone/fax/web');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
