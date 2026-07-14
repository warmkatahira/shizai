<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'login_id', 'email', 'password', 'role', 'office_id', 'is_manager', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** 権限の定数 */
    public const ROLE_ADMIN = 'admin';            // 管理者
    public const ROLE_GENERAL_AFFAIRS = 'general_affairs'; // 総務
    public const ROLE_SALES = 'sales';            // 営業所

    /** 権限のラベル（画面表示用） */
    public const ROLE_LABELS = [
        self::ROLE_ADMIN => '管理者',
        self::ROLE_GENERAL_AFFAIRS => '総務',
        self::ROLE_SALES => '営業所',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_manager' => 'boolean',
        ];
    }

    /** 所属営業所 */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** 権限判定ヘルパー */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isGeneralAffairs(): bool
    {
        return $this->role === self::ROLE_GENERAL_AFFAIRS;
    }

    public function isSales(): bool
    {
        return $this->role === self::ROLE_SALES;
    }

    /**
     * マスタ（営業所・業者・カテゴリ・資材）を編集できるか。
     * ユーザー管理だけは権限の付与ができるので管理者のみ（isAdmin を使う）。
     */
    public function canManageMasters(): bool
    {
        return $this->isAdmin() || $this->isGeneralAffairs();
    }

    /** 発注書を作成・再発行できるか（＝業者へ発注する立場か） */
    public function canIssuePurchaseOrder(): bool
    {
        return $this->isAdmin() || $this->isGeneralAffairs();
    }

    /** 所長かどうか（営業所ユーザーで所長フラグが立っている） */
    public function isManager(): bool
    {
        return $this->isSales() && $this->is_manager;
    }

    /** 権限の日本語ラベル */
    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }
}
