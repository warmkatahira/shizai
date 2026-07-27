<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 操作ログを1行残すヘルパー。
 *
 * 各コントローラーの操作直後に ActivityLogger::log(...) を1回呼ぶ。
 * 通知（OrderNotifier）と同じ方針で、ログ記録の失敗が業務（承認・保存など）を
 * 巻き添えにしないよう try/catch で握りつぶし、失敗自体はアプリログに残す。
 */
class ActivityLogger
{
    /**
     * @param  string       $action       「カテゴリ.操作」形式（例：order.manager_approved）
     * @param  string       $description  日本語の要約（一覧にそのまま出る）
     * @param  Model|null   $subject      操作対象（Order・Material など）。あれば種別とIDを控える
     * @param  int|null     $officeId     対象の営業所ID。未指定なら操作者の所属営業所を使う
     */
    public static function log(string $action, string $description, ?Model $subject = null, ?int $officeId = null): void
    {
        try {
            $user = auth()->user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'システム',
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'office_id' => $officeId ?? $user?->office_id,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('操作ログの記録に失敗しました', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
