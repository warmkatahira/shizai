# CLAUDE.md

社内用の **資材発注システム**（Laravel）。営業所が資材を発注申請し、所長・総務が承認して発注する。

## 開発環境（Laravel Sail / Docker）

PHPはホストに入っていない。すべて Sail（Docker）経由で実行する。

```bash
./vendor/bin/sail up -d          # 環境起動
./vendor/bin/sail down           # 環境停止（データは消えない）
./vendor/bin/sail artisan ...    # artisan コマンド
./vendor/bin/sail npm run build  # フロント（Tailwind/Vite）のビルド ※Blade変更後に必要
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan migrate:fresh --seed --force  # DB作り直し＋初期データ
```

### アクセスURL
| 用途 | URL |
|------|-----|
| アプリ本体 | http://localhost |
| phpMyAdmin | http://localhost:8080 （user: `sail` / pass: `password`）|
| Mailpit（送信メール確認）| http://localhost:8025 |

### 技術スタック
- Laravel 13 / PHP 8.3+ / MySQL 8.4
- Tailwind CSS v4 + Vite（サーバーサイドレンダリングのBlade。JSフレームワークは不使用）
- メール送信は Mailpit（開発用）

## 権限（User.role）
- `admin`（管理者）… マスタ管理（営業所・ユーザー・業者・資材）
- `general_affairs`（総務）… 発注確定・特例承認
- `sales`（営業所）… 発注申請。`is_manager=true` なら**所長**（自営業所の一次承認者）

## 発注の承認フロー（Order.status）
```
一般ユーザーの申請: pending_manager(所長承認待ち) → [所長承認] → pending_affairs(総務承認待ち) → [総務発注] → ordered(発注済)
所長本人の申請:     pending_affairs から開始（所長承認をスキップ）
特例承認:          pending_manager → [総務が理由必須で直接承認] → ordered（is_special_approval=true）
却下:              各段階で reject（理由必須）→ rejected
```
- 承認・却下ロジックは `OrderApprovalController`、通知先の振り分けは `App\Support\OrderNotifier`。
- メール通知：申請時→次の承認者へ / 所長承認時→総務へ / 発注・却下時→申請者へ。

## データモデル
- `offices`（営業所） / `users`（role・office_id・is_manager を保持）
- `suppliers`（業者マスタ） … `materials.supplier_id` で参照
- `materials`（資材マスタ） … name/category/supplier_id/unit/unit_price/is_active
- `orders`（発注ヘッダー） + `order_items`（明細）
  - **明細は申請時点の情報をスナップショット保存**（material_name / supplier_name / unit / unit_price）。マスタが後で変わっても過去の申請は不変。

## 主な画面
- `/orders` 発注一覧（検索：ステータス/営業所/業者/品名/期間、CSVダウンロード付き）
- `/orders/create` 発注申請（営業所ユーザーのみ）
- `/orders/{order}` 詳細＋承認/却下アクション
- `/admin/{offices,users,suppliers,materials}` マスタ管理（管理者のみ）
- CSVは `OrderController@export`（BOM付きUTF-8、明細1行ずつ、Excel対応）

## テスト用アカウント（パスワードは全員 `password`）
| メール | 役割 |
|--------|------|
| admin@example.com | 管理者 |
| soumu@example.com | 総務 |
| tokyo@example.com | 東京営業所・**所長** |
| tokyo2@example.com | 東京営業所・一般 |
| osaka@example.com | 大阪営業所・所長 |

## コード規約メモ
- Laravel 13 では fillable/hidden を **PHP属性** `#[Fillable([...])]` `#[Hidden([...])]` で書く（`$fillable` 配列ではない）。
- 権限チェックは `role` ミドルウェア（`bootstrap/app.php` でエイリアス登録、例：`->middleware('role:admin')`）。
- 画面文言・コメントは日本語。フォームは `_form.blade.php` パーシャルで共通化。
- リソースルートで `create` を `{model}` より先に定義する（"create" がIDと誤解釈されるのを防ぐ）。

## 進捗
Phase 1（認証）〜 Phase 5（一覧・検索・CSV）＋業者マスタまで完成・実機確認済み。
今後の候補：業者別の発注集計、発注書PDF出力、自動テスト整備。
