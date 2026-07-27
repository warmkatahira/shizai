<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 操作ログ1件。記録は App\Support\ActivityLogger 経由で行う（直接 create しない）。
 *
 * action は「カテゴリ.操作」の形（例：order.manager_approved / master.material_updated）。
 * 先頭のカテゴリで大まかに分類でき、一覧の絞り込みに使う。
 */
#[Fillable(['user_id', 'user_name', 'action', 'description', 'subject_type', 'subject_id', 'office_id', 'ip_address', 'created_at'])]
class ActivityLog extends Model
{
    /** 更新はしない（作成日時のみ） */
    public const UPDATED_AT = null;

    /** action 先頭のカテゴリ → 画面表示ラベル。絞り込みプルダウンにも使う */
    public const CATEGORIES = [
        'order' => '発注',
        'master' => 'マスタ',
        'user' => 'ユーザー',
        'auth' => 'ログイン',
        'report' => '集計',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** 操作したユーザー（削除済みなら null。表示は user_name を使う） */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 操作対象の営業所 */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** action 先頭のカテゴリ（order / master / user / auth） */
    public function category(): string
    {
        return explode('.', $this->action)[0];
    }

    /** カテゴリの日本語ラベル */
    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category()] ?? $this->category();
    }
}
