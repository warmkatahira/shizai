<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * アプリのタイムゾーンを UTC → Asia/Tokyo に変えたことに伴い、保存済みの日時を +9時間する。
 *
 * それまでは日時をUTCで保存し、UTCのまま表示していた（＝画面の申請日時が実際より9時間前）。
 * これからは日本時間で保存・表示するので、過去のぶんも日本時間に直しておかないと、
 * 切り替え前のデータだけ9時間ずれて見える。
 *
 * 対象はアプリのテーブルのみ。framework の作業用テーブル（failed_jobs / password_reset_tokens /
 * sessions）は一時的なデータなので触らない。
 * 日付だけの列（orders.desired_delivery_date）は時刻を持たないのでずれない。
 */
return new class extends Migration
{
    private const HOURS = 9;

    /** テーブル => 変換する日時カラム */
    private const TARGETS = [
        'orders' => [
            'created_at', 'updated_at', 'manager_approved_at', 'reviewed_at',
            'ordered_at', 'returned_at', 'post_order_note_updated_at',
        ],
        'order_items' => ['created_at', 'updated_at'],
        'users' => ['created_at', 'updated_at', 'email_verified_at'],
        'offices' => ['created_at', 'updated_at'],
        'suppliers' => ['created_at', 'updated_at'],
        'categories' => ['created_at', 'updated_at'],
        'materials' => ['created_at', 'updated_at'],
        'units' => ['created_at', 'updated_at'],
        'activity_logs' => ['created_at'],
        'activity_log_settings' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->shift(self::HOURS);
    }

    public function down(): void
    {
        $this->shift(-self::HOURS);
    }

    /** 全対象カラムを $hours だけずらす（NULL は DATE_ADD の結果も NULL なのでそのまま残る） */
    private function shift(int $hours): void
    {
        foreach (self::TARGETS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // 1テーブル1文にまとめる（updated_at を触る回数を増やさないため）
            $sets = collect($columns)
                ->filter(fn (string $column) => Schema::hasColumn($table, $column))
                ->map(fn (string $column) => "`{$column}` = DATE_ADD(`{$column}`, INTERVAL {$hours} HOUR)")
                ->implode(', ');

            if ($sets === '') {
                continue;
            }

            DB::statement("UPDATE `{$table}` SET {$sets}");
        }
    }
};
