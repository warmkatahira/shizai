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

#[Fillable(['name', 'login_id', 'email', 'password', 'role', 'office_id', 'is_manager', 'is_active', 'must_change_password', 'visible_masters'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** 権限の定数 */
    public const ROLE_ADMIN = 'admin';            // 管理者
    public const ROLE_GENERAL_AFFAIRS = 'general_affairs'; // 総務
    public const ROLE_SALES = 'sales';            // 営業所

    /**
     * マスタ管理の画面（キー → 画面名）。
     *
     * ユーザー管理はここに入れない。権限の付与ができるので管理者だけのままで、
     * アカウントごとの出し分けの対象にしない。
     */
    public const MASTERS = [
        'materials' => '資材',
        'categories' => 'カテゴリ',
        'units' => '単位',
        'suppliers' => '業者',
        'offices' => '営業所',
    ];

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
            'must_change_password' => 'boolean',
            'visible_masters' => 'array',
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

    /**
     * そのマスタの一覧を見られるか。
     *
     * 管理者・総務は常に全部見られる（編集もできる）。
     * それ以外の権限は、ユーザー管理の「表示するマスタ」でオンにしたものだけを**閲覧**できる。
     * 登録・編集・削除・CSVは canManageMasters()（＝管理者・総務）のまま。
     */
    public function canViewMaster(string $master): bool
    {
        if (! array_key_exists($master, self::MASTERS)) {
            return false;
        }

        return $this->canManageMasters()
            || in_array($master, $this->visible_masters ?? [], true);
    }

    /** 表示できるマスタが1つでもあるか（ナビにマスタのメニューを出すかの判定） */
    public function canViewAnyMaster(): bool
    {
        foreach (array_keys(self::MASTERS) as $master) {
            if ($this->canViewMaster($master)) {
                return true;
            }
        }

        return false;
    }

    /** 全営業所の申請を扱う側か（総務・管理者）。営業所ユーザーは自分の営業所だけ */
    public function isBackOffice(): bool
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
