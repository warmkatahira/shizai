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
- `admin`（管理者）… すべてのマスタ管理（**ユーザー管理は管理者だけ**）
- `general_affairs`（総務）… 承認・特例承認・差し戻し・却下、発注書の作成、**マスタ管理（ユーザー以外の4つ）**
- `sales`（営業所）… 発注申請。`is_manager=true` なら**所長**（自営業所の一次承認者。差し戻し・却下もできる）
  - 一覧・集計は**自営業所のぶんだけ**見える（営業所プルダウンも出さない）。総務・管理者は全営業所

## 発注の承認フロー（Order.status）
```
一般ユーザーの申請: pending_manager(所長承認待ち) → [所長承認] → pending_affairs(総務承認待ち)
                   → [総務承認] → pending_order(発注待ち) → [発注書を作成] → ordered(発注済)
所長本人の申請:     pending_affairs から開始（所長承認をスキップ）
特例承認:          pending_manager → [総務が理由必須で直接承認] → pending_order（is_special_approval=true）
差し戻し:          承認待ち／発注待ち → [理由必須] → returned(差し戻し) → [申請者が修正して再申請] → 最初から
却下:              承認待ちの各段階で reject（理由必須）→ rejected（ここで終了）
```
**「発注済」にするのは総務の承認ではなく発注書の作成**（＝実際に業者へ発注したタイミング）。
発注書を出すと `ordered_at`（発注日）と `ordered_by`（出した人）が入る。2回目以降は再発行で、状態は変わらない。

### 差し戻し（returned）と却下（rejected）の違い
- **却下＝そこで終了**。再申請はできない。
- **差し戻し＝申請者まで戻して直させる**。申請者が内容を修正して再申請できる（`OrderController::edit/update`）。
  - 差し戻せるのは **所長承認待ち・総務承認待ち・発注待ち**。
    発注待ち（総務承認済み）も戻せるのは、**発注書をまだ出していない＝業者に発注していない**ため。発注済は戻せない。
  - 誰が戻せるかは却下と同じ（所長承認待ち＝その営業所の所長か総務／それ以降＝総務）。理由は必須。
  - **再申請すると承認は最初からやり直し**（内容が変わっているので所長がもう一度見る）。
    そのため承認履歴（`manager_approved_*` / `reviewed_*` / `is_special_approval` / `special_reason`）は再申請時にクリアする。
    `return_reason` / `returned_by` / `returned_at` は「なぜ直したか」の記録として**残す**（詳細画面に経緯として出る）。
  - 明細は再申請のたびに作り直す（そのときの資材マスタでスナップショットし直す）。
- **削除**（`OrderController::destroy`）… **差し戻し中・却下のものだけ**。完全削除（明細は外部キーの cascade で一緒に消える）。
  その営業所の営業所ユーザー、または総務・管理者ができる。承認が進んでいるもの・発注済は消せない（実績が消えるため）。
- 承認・差し戻し・却下ロジックは `OrderApprovalController`、通知先の振り分けは `App\Support\OrderNotifier`。
- メール通知（`OrderNotifier`）：
  - 申請時・再申請時 → 次の承認者へ（一般の申請＝その営業所の所長 / 所長の申請＝総務全員）
  - 所長承認時 → 総務全員へ
  - 総務承認時・差し戻し時・却下時 → **その営業所の所長へ必ず送り、申請者にメールがあれば申請者にも送る**
    （発注書の作成は総務の事務作業なので通知しない）
    （申請用アカウントはメールを持たないため、所長を必ず宛先に含める）
  - メールが無いユーザーは宛先から除外する（`OrderNotifier::withEmail`）。

## データモデル
- `offices`（営業所・拠点） … name/code/postal_code/address/tel/fax/sort_order/is_active。社内の拠点マスタ（BaseSeeder）の9拠点を投入
- `users`（login_id・email・role・office_id・is_manager を保持）
  - **ログインは `login_id`**（メールではない）。営業所の申請用アカウントは共通で使い回すため、実在のメールを持たない
  - `email` は **null 許容の「通知先」**。無ければそのユーザーには通知を送らないだけで、ログインには影響しない
- `suppliers`（業者マスタ） … name/formal_name/contact_person/phone/mobile_phone/fax/email/order_method/is_active。`materials.supplier_id` で参照
  - **名前は2つ持つ**。`name`＝短い表示名（「フレックス」）、`formal_name`＝正式名称（「株式会社フレックス」・任意）
    - 画面・集計・発注明細のスナップショットはすべて `name`。「株式会社」まで出すと一覧が長くなって邪魔なため
    - **`formal_name` を使うのは発注書の宛名（〜御中）だけ**。空なら `name` を使う（`Supplier::formalName()`）
  - **担当者名・連絡先・発注方法は業者ごとに決まる**ので、資材ではなくここに持つ（資材側に持つと同じ値が何十行も重複する）
  - **電話は2つ持つ**。`phone`＝固定電話 / `mobile_phone`＝携帯電話（担当者が外に出ている業者にかけるため。どちらも任意）
    - 発注書に印字するのは固定電話とFAX。**携帯は入っているときだけ**その下に添える
  - `order_method` は `mail` / `phone` / `fax` / `web` の4択（`Supplier::ORDER_METHODS`）。サイボウズ・ロジレスなどの専用システムは `web`
- `categories`（商品カテゴリマスタ） … name/sort_order/is_active。`materials.category_id` で参照
- `materials`（資材マスタ） … 社内の「資材発注 詳細確認リスト」の項目に対応
  - name（品名） / category_id / supplier_id（発注業者）
  - length_mm / width_mm / height_mm（縦・横・高）
  - unit（単位） / unit_price（単価） / min_lot_qty ＋ min_lot_unit（最低ロット。「2700枚」を数量と単位に分けて保持）
  - has_imprint（名入れフラグ） / note（備考） / is_active
    - **3辺計は列に持たない**。縦横高から求まるので `DescribesMaterial::girthMm()` / `girthText()` で計算して出す
      （列にすると寸法を直したときにズレる）。入力がある値だけを足すので、厚みを入れていない袋は縦＋横になる。
      表示は資材マスタ一覧・資材一覧・編集フォーム（入力に合わせてJSで再計算）、CSVは**出力だけ**（取り込みでは読み飛ばす）
  - shipping_size（発送時サイズ。「60サイズ」「80サイズ」など**実際に運送会社で測られたサイズ**）
    - 3辺計から機械的に決まるものではなく運用で分かる値なので**手入力**。任意（`nullable` / 10文字）
- `orders`（発注ヘッダー） + `order_items`（明細）
  - **明細は申請時点の情報をスナップショット保存**（material_name / category_name / supplier_name / unit / unit_price / 寸法 / 最低ロット）。マスタが後で変わっても過去の申請・集計・発注書は不変。
  - `orders.supplier_id`＝発注先の業者（1申請＝1業者）／`orders.requester_name`＝発注者の氏名（手入力）
  - 備考は2種類。`note`＝**社内メモ**（所長・総務向け。発注書には出ない） / `supplier_note`＝**業者への連絡事項**（発注書の備考欄に印字）
  - `desired_delivery_date`＝納入希望日（申請時に**必須**。発注書に印字）。列は `nullable`（必須にする前のデータが残るため）
  - 差し戻しは `return_reason` / `returned_by` / `returned_at`。再申請しても消さない（経緯の記録）

### 見た目（色・フォント）
- ヘッダーは `resources/views/layouts/partials/header.blade.php`。**上部固定＋半透明＋ぼかし**（`sticky top-0` / `bg-white/85` / `backdrop-blur`）
  - ロゴのマークは**ファビコン（`public/favicon.svg`）を `<img>` で使い回す**（同じ絵を2箇所に持たない）
  - 現在いるページのリンクはベージュの塗り（`aria-current="page"` も付ける）。判定は `request()->routeIs()`
  - マスタは5つあってナビが渋滞するので、**`<details>` のドロップダウン**にまとめている（JSフレームワークは使わない）。
    スマホはハンバーガーで同じリンクを畳む。外クリック・Esc で閉じる処理は `layouts/app.blade.php` の共通スクリプト
  - **一覧の表はすべて枠内スクロール**（`overflow-auto max-h-[70vh]`）なので、`thead` の `sticky` は `top-0` でよい。
    ページごとスクロールする表を作る場合だけ、固定ヘッダーの高さぶん `top-16` で止める必要がある
- パレットは `resources/css/app.css` の `@theme` に集約。アクセント `#D5BDAE` ／ ベース `#EBE9EA` ／ テキスト `#34251F`
  - `#D5BDAE` は明るいので**その上に白文字は載せられない**（コントラスト 1.7:1）。ボタンは「ベージュ地＋濃い文字（`text-ink`）」
  - 白地に置く文字・リンクには `accent-strong`（濃いブラウン `#8F6A50`）を使う
  - ニュートラル（`gray-*`）はスケールごと暖色グレーに差し替えてあるので、既存の `text-gray-500` などがそのまま馴染む
  - 却下＝赤 / 発注済＝緑 / 特例承認＝amber（金） / 差し戻し＝orange（テラコッタ） / 総務承認待ち＝blue（くすんだ青）。
    **意味のある色はすべて `@theme` で暖色寄りに置き換えてある**（Tailwind既定のままだと1色だけ浮く）
- フォントは **Kosugi Maru**（Bunny Fonts 経由でビルド時にDLして自己ホスト。`vite.config.js`）。400のみで太字は合成
- ファビコンは `public/favicon.svg`
- フォームの `<input>` はすべて `autocomplete="off"`

### 金額の扱い
単価は **`decimal(10,2)`**。実データに 34.5円 / 6.07円 のような小数が存在するため整数では持てない。
表示は `App\Support\Money::yen()` を使う（小数がある時だけ小数を出す：2532 → ¥2,532 ／ 34.5 → ¥34.5）。

### 日時の扱い
アプリのタイムゾーンは **`Asia/Tokyo`**（`config/app.php` の既定値。`APP_TIMEZONE` で上書き可）。
国内専用なのでDBにも日本時間で保存し、そのまま表示する（UTC保存＋表示時に変換、はしない）。
- 以前は UTC だったため、**画面の申請日時とバックアップのファイル名が9時間ずれていた**。
  spatie/laravel-backup はファイル名を `Carbon::now()`（＝アプリのTZ）で付けるので、
  スケジュールの `->timezone('Asia/Tokyo')` だけでは名前は直らない
- 切り替え時に既存データを +9時間する変換を入れてある（`..._shift_timestamps_to_jst`。`down` で戻せる）
- MySQLのセッションTZは触っていない（`SYSTEM`＝UTCのまま）。列は `timestamp` 型なので、
  **DBサーバーのシステムTZを変えると保存済みの値がずれる**点に注意

### DBバックアップ
`spatie/laravel-backup`。毎日 **AM3:00（JST）** に `backup:run --only-db`、**2:45** に `backup:clean`（`routes/console.php`）。
保存先は `/var/backups/shizai/`（`config/filesystems.php` の `backups` ディスク。`BACKUP_ROOT` で変更可）。
- 掃除は **新しい順に7個だけ残して残りは削除**（`App\Support\KeepLatestBackupsStrategy`。個数は `BACKUP_KEEP_COUNT`）。
  spatie 既定の「期間ごとに間引く」方式は何個残るか分かりにくいので、個数指定に差し替えている
- **本番サーバーで `* * * * * php artisan schedule:run` の cron 登録が必要**

## 主な画面
- `/orders` 発注一覧（検索：ステータス/営業所/業者/品名/期間、CSVダウンロード付き）
  - **1ページ50件のページネーション**（`OrderController::PER_PAGE`）。検索条件はページリンクに引き継ぐ（`withQueryString`）
  - **期間の初期値は当月**。総務は**ステータスの初期値が「総務承認待ち」**（自分が承認すべきものから見えるように）
  - `orders` に `(office_id, created_at)` の複合インデックス（一覧は営業所×申請日で絞り、申請日の新しい順に並べるため）
  - **列見出しを押すと並び替わる**（`OrderController::SORTS` / `applySort()`、見出しは `orders/partials/sort-header.blade.php`）
    - 既定は**申請日の新しい順**。不正なキーが来たら既定に落とす（`SORTS` に無いキーはSQLに渡さない）
    - 初回の向きは列ごとに決める（`SORTS` の値）。日付・申請番号は新しいものから、それ以外は昇順から
    - **営業所・業者は join せずサブクエリで並べる**。join すると `created_at` / `status` が
      `orders` と同名で曖昧になり、既存の絞り込みが壊れるため。営業所は名前ではなく `sort_order` 順
    - ステータスは文字列の並びに意味がないので `FIELD()` で承認フローの順に並べる
    - **最後は必ず申請番号で決める**（同着の並びがページ送りで前後しないように）
    - 並び順はクエリ文字列に乗るので、ページ送り・CSV・「一覧に戻る」にそのまま引き継がれる。**CSVも画面と同じ並び**
  - **詳細から「一覧に戻る」で検索結果に戻る**。直前の検索条件をセッションに覚えている（`Concerns\RemembersLastSearch`）
  - CSVには承認履歴（所長承認者/日時・総務承認者/日時・特例承認・発注書作成者/発注日・差し戻し者/日時/理由・却下者/却下理由）も出る。CSVは**ページ分割の影響を受けず全件**出力
- `/orders/create` 発注申請（営業所ユーザーのみ）
  - **1申請＝1業者**。発注業者をプルダウンで選ぶと、その業者の有効な資材だけが並ぶ（`onchange` で GET 再読込。JSフレームワークは使わない）
  - 数量を入れた資材だけが申請される。他業者の資材を混ぜられないよう `store` 側でも業者で絞って検証する
  - **最低ロットは「下限」であって「単位」ではない**。ロット以上なら端数でよい（10なら 10 / 11 / 100 …。9 は不可）。
    画面はJSで弾き（`setCustomValidity`）、`store` でもロット以上かどうか検証する（フォームを迂回されても通らないように）。
    増減ボタンはロットぶんまとめて動かすが、押してロット未満の中途半端な値にはしない（増やす＝ロット / 減らす＝0 に丸める）
  - 数量を入れるたびに小計・合計を素のJavaScriptで再計算して表示する
  - 発注者の氏名は、**所長アカウントなら本人の氏名を初期値**に入れる（所長は個人アカウントのため）
  - **発注者の氏名は手入力**（`orders.requester_name`）。営業所のアカウントは共通で使い回すため、ログインアカウント（`requested_by`）とは別に持つ
  - 納入希望日（`desired_delivery_date`）は発注単位で1つ。全品目に適用され、発注書の「希望納期」列に印字される
    - **入力必須**。空だと総務が発注の優先順位を判断できず、一覧の希望納期も意味をなさないため
    - **明日以降しか選べない**（当日納品は業者の締めに間に合わないため）。画面は `min` 属性、`store`/`update` は `after:today` で検証。
      差し戻しの再申請で元の希望日が過去になっている場合は、初期値を空にして選び直させる（必須なので選び直しになる）
- `/orders/{order}/edit` 差し戻された申請の修正・再申請（営業所ユーザー。`Order::canBeEditedBy` で自営業所＋差し戻し中のみ）
  - 画面は新規申請と同じ（`orders/_form.blade.php` を共有）。**業者も選び直せる**（差し戻しの理由が「業者違い」のこともあるため）。
    業者を変えると数量はクリアされる（他業者の資材は混ぜられないので当然そうなる）
  - 数量の初期値は今の明細。ロット未満は `update` 側でも弾く（`store` と同じ `buildItemSnapshots` を通す）
- `/orders/{order}` 詳細＋承認/差し戻し/却下アクション＋**発注書PDF**のボタン＋（差し戻し中・却下なら）修正・削除のボタン
  - 右側に**承認の経緯（タイムライン）**。申請 → 所長承認 → 総務承認 → 発注書の作成を縦に並べ、「いまここ」を出す。
    段の組み立ては `Order::approvalSteps()`（表示専用。列に入っている日時と担当者を並べ替えているだけ）、
    描画は `orders/partials/approval-timeline.blade.php`。承認者と日時は基本情報から外してここに集約している
  - 特例承認・所長本人の申請は「省略」として理由つきで出す。**却下は日時の列を持たない**ので担当者と理由のみ
- `/orders/{order}/purchase-order` 発注書PDF（`PurchaseOrderController`。**POST**）
  - **1申請＝1業者なので、発注書は1申請1枚**
  - 出せるのは**発注待ち・発注済のみ・総務/管理者のみ**。担当者名はボタンを押した本人の氏名が入る
  - **出力するとステータスが「発注済」に進む**ため、リンク（GET）ではなくボタン（POST）にしている
    （GETだとブラウザの先読みや誤クリックで発注済になってしまう）
  - 発注NO＝`orders.id`（`Order::purchaseOrderNo()`）／発注日＝`ordered_at`／自社の連絡先＝`config/company.php`（本社）
  - 納入先＝発注元の営業所。備考欄＝`orders.supplier_note`（**業者向け**。社内メモの `orders.note` は印字しない）
  - 宛名は `Supplier::formalName()`＝正式名称（`formal_name`。空なら `name`）。**正式名称を使うのはここだけ**
  - PDFは **mPDF**。日本語フォントは `storage/fonts/ipaexg.ttf`（IPAexゴシック / IPAフォントライセンス）をリポジトリに同梱し、サブセット埋め込みしている
- `/reports` 発注集計（`ReportController`）
  - 集計軸をプルダウンで切替：カテゴリ別／業者別／営業所別／資材別
  - 絞り込み：期間（**発注日** = `orders.ordered_at` ＝ 発注書を出した日）／営業所／カテゴリ／業者。CSVダウンロード付き
  - **期間の初期値は当月**（全期間スキャンを既定にしない）。日付を空にして送信すれば全期間。
    日付パラメータが1つも無いときだけ当月を入れる（`applyDefaultPeriod`）ので、フォーム送信時は空欄が尊重される
  - `orders` に `(status, ordered_at)` の複合インデックスを張っている（集計の絞り込みがこの2列のため）
  - **対象は `ordered`（発注済）のみ**。承認待ち・却下は実績ではないので含めない
  - グループ化は明細のスナップショット列（`category_name` など）で行う。マスタを改名・削除しても過去の集計は動かない
  - 合計の「発注件数」は `COUNT(DISTINCT orders.id)`。1件の発注が複数カテゴリにまたがると各行で数えられるため、**行の合算では出せない**
- `/materials` 資材一覧（**閲覧のみ・全ログインユーザー**。`MaterialCatalogController`）
  - 営業所・総務が「どの業者に何がいくらであるか」を確認するための読み取り専用。編集は管理者のみ
  - ナビには管理者以外に表示（管理者はマスタ管理の「資材」から見られるため）
- `/admin/{offices,suppliers,categories,materials}` マスタ管理（**管理者と総務**）
  - 資材の **状態は初期値が「有効」**（普段使うのは有効な資材だけ）。CSV出力にも同じ初期値がかかる。
    「すべて」は空文字で送られて `null` になるので、値ではなく**キーの有無**で初回表示か判定する（`applyDefaultStatus`）
  - **5つのマスタすべてが CSV出力・CSV取り込みに対応**（`/admin/{名前}-export` / `-import`）。
    **ユーザーマスタだけは対象外**（権限の付与を伴うため、画面から1件ずつ操作する）
    - 共通処理は `App\Support\MasterCsv`（抽象クラス）。マスタごとの差分は継承先
      （`OfficeCsv` / `SupplierCsv` / `CategoryCsv` / `UnitCsv` / `MaterialCsv`）が
      `headers()` / `row()` / `attributes()` で持つ
    - 入口は `Concerns\HandlesMasterCsv`（`streamCsv()` / `importCsv()`）。各コントローラーの export/import はこれを呼ぶだけ
    - **出したCSVをExcelで直して戻す**運用。突合は**1列目のID**：IDが入っていれば更新、空なら新規追加
      （名前で突合すると、名前を直したいときに「別のものの新規追加」になってしまうため）
    - 入力チェックは**モデルの `validationRules($ignoreId)` / `attributeNames()`**。編集フォームと同じものを使う。
      `$ignoreId` は更新行を unique の対象から外すため（名前を変えずに他の列だけ直せるように）
    - 資材のカテゴリ・発注業者・単位は**名前**で書く。マスタに無い名前は**エラー**（自動作成すると誤字がマスタに入る）
    - 業者の発注方法は**ラベル**（メール／電話／FAX／web）で出す。取り込みは保存値（`mail` など）でも受ける
    - **1行でもエラーがあれば1件も取り込まない**（トランザクション）。エラーは「3行目：〜」と行番号つきで全部出す
    - **CSVの中での重複も先に弾く**（`uniqueColumns()`）。DBの unique に任せると取り込みの途中で落ちて行番号が出ないため
    - CSVに無い行は消えない（削除はしない）。外すときは「有効」を いいえ にする
    - ExcelがCP932で保存したCSVも取り込める（UTF-8でなければ SJIS-win から変換する）。数量の「1,000」も読める
    - 列の位置で読むので、**見出し行がCSV出力と違うファイルは取り込まない**（`MasterCsv::assertHeaderRow`）。
      列を足す前の古いCSVを読んで隣の列を取り込んでしまうのを防ぐ。列を足したときは出し直してもらう
    - 取り込みパネルは `admin/partials/csv-panel.blade.php` で共有。**ドラッグ＆ドロップ対応**
      （`<label>` が `<input type="file">` を包んでいるので、JSが動かなくてもクリックで選べる。
      落とされたファイルを input に入れる処理だけ `layouts/app.blade.php` の共通スクリプトにある）
- `/admin/users` ユーザー管理（**管理者のみ**。権限の付与・パスワード変更ができるため）
- `/password` パスワードの変更（**本人が自分のものを変える**。全ログインユーザー。`PasswordController`）
  - 管理者のユーザー管理とは別物。あちらは他人のパスワードを管理者が付け替えるもの
  - 現在のパスワードの入力を必須にしている（`current_password` ルール）。開きっぱなしの端末から勝手に変えられないため
  - 確認用の再入力（`confirmed`）と、いまと同じものは不可（`different`）
  - 導線はヘッダー右上の**名前**（スマホはハンバーガーの中）。本人が変えられる設定は他に無いので、専用のプロフィール画面は作っていない
  - **営業所の申請用アカウントは拠点で共通**なので、変更すると同じアカウントの全員が入れなくなる。
    そのアカウント（`isSales()` かつ 所長でない）で開いたときだけ画面に警告を出す
  - 操作ログに `user.password_changed` として残る（パスワードそのものは記録しない）
- CSVは `OrderController@export` / `ReportController@export` / 各マスタの `export`（BOM付きUTF-8、Excel対応）

## テスト用アカウント（**ログインIDで**ログイン。パスワードは管理者以外すべて `password`）
| ログインID | 役割 |
|--------|------|
| `t.katahira` | 管理者（パスワードのみ `katahira134`）|
| `ooizumi` / `namiki` / `m.horiuchi` / `m.okano` | 総務 |
| `ty01` | 第1営業所・**所長**（里本さん）|
| `soneda` | 第2営業所・**所長**（曽根田さん）|
| `{コード}-manager` | 残り7拠点の**所長**（仮名。例：`ls-manager`）|
| `{コード}` | 各営業所の申請用ユーザー（拠点で1人を使い回す。例：`1st`, `ls`）|

`{コード}` は `offices.code` の小文字（`honsha` / `1st` / `2nd` / `3rd` / `ls` / `lp` / `lc` / `imp` / `hr`）。
第1・第2以外の所長は**実名が未確定のため仮名**（`UserSeeder::MANAGERS` に定義）。実名が分かったらここを差し替える。
**メールを持つのは管理者・総務・第1/第2の所長**（このうち通知が要らない人は `null`）。
申請用アカウントと仮名の所長はメールなし（通知が飛ばない）。誰が `null` かは `UserSeeder` を見ること。

### 通知を受け取らないユーザーの作り方
**メールアドレスを空（null）にするだけ**。権限はそのままで通知だけ止まる。
- 通知先は `OrderNotifier::withEmail()` の1か所で「メールを持つ人」に絞っているので、
  申請・承認・差し戻し・却下のすべての通知から自動で外れる
- ログインは `login_id` なので、メールが無くてもログインには影響しない
- 承認・発注書の作成などの権限には一切影響しない（通知が届かなくなるだけ）
- 権限を新設したりフラグ列を足したりする必要はない

## 共通化した処理（重複させないこと）
- `Material::toOrderItemSnapshot()` … 資材 → 発注明細のスナップショット。申請もシーダーもこれを使う（数量だけ足す）
- `Material::scopeSortedByCategory()` / `scopeActive()` … 資材一覧の並び順・有効判定。3画面で共有
- `App\Models\Concerns\DescribesMaterial` … 寸法・最低ロットの表示整形。Material と OrderItem が同じ列を持つため共有
- `App\Http\Controllers\Concerns\FiltersByPeriod` … 検索期間の初期値（当月）。発注一覧と集計で共有
- `App\Http\Controllers\Concerns\RemembersLastSearch` … 直前の検索条件をセッションに覚えて一覧へ戻す。
  発注一覧（詳細から「一覧に戻る」）と資材マスタ（編集のキャンセル・保存後の戻り先）で共有。
  **条件はクエリ文字列のまま持つ**（配列だと「すべて＝空」が null になって消え、既定値が再適用される）
- `{Office,Supplier,Category,Unit,Material}::validationRules($ignoreId)` / `attributeNames()` …
  マスタ1件の入力チェック。**編集フォームとCSV取り込みで共有**（コントローラーに規則を書かない）
- `App\Support\MasterCsv` … マスタCSVの共通処理（読み込み・ID突合・検証・重複チェック・トランザクション）。
  マスタごとの列と変換だけ継承先が持つ
- `App\Http\Controllers\Concerns\HandlesMasterCsv` … CSV出力・取り込みの入口。5つのマスタで共有
- `resources/views/admin/partials/csv-panel.blade.php` … CSV取り込みパネル（説明文＋ドロップ枠）。5つのマスタで共有
- `App\Support\Money::yen()` … 金額表示（小数がある時だけ小数を出す）
- `App\Support\OrderNotifier` … 通知先の振り分け
- `OrderController::validateOrderInput()` / `buildItemSnapshots()` … 申請フォームの検証と明細の組み立て。新規申請（`store`）と再申請（`update`）で共有
- `resources/views/orders/_form.blade.php` … 発注申請の入力フォーム。新規申請と再申請で共有
- 権限判定は `User` のメソッドに寄せる（`canManageMasters()` / `canIssuePurchaseOrder()` / `isBackOffice()` / `isManager()` など）。
  ビューやコントローラで `isAdmin() || isGeneralAffairs()` のように書かない
- **申請1件ごとの「誰が何をできるか」は `Order` のメソッドに集約**（`canBeManagerApprovedBy()` / `canBeAffairsApprovedBy()` /
  `canBeSpecialApprovedBy()` / `canBeReturnedBy()` / `canBeRejectedBy()` / `canBeEditedBy()` / `canBeDeletedBy()`）。
  ステータス＋役割＋営業所の組み合わせ判定なので、コントローラー（実行時のチェック）とビュー（ボタンの出し分け）で必ず同じものを使う
- 営業所の並び順はどこでも `sort_order`

## 画面まわりの共通ルール
- **検索は自動実行**。`<form data-auto-submit>` を付けると、条件を変えた時点で送信される（`layouts/app.blade.php` の共通スクリプト）。
  `change` イベントを使うので、テキスト入力は Enter かフォーカスを外したときだけ発火し、1文字ごとには走らない。検索ボタンは `<noscript>` 内にのみ置く
- **一覧の表はすべて枠内スクロールに統一**（囲みを `overflow-auto max-h-[70vh]`、`thead` を `sticky top-0`）。
  発注申請一覧もマスタ系も同じ。`sticky` の基準は `overflow` を持つ親なので、枠内スクロールなら `top-0` でその枠の上端に貼り付く
  - `overflow-hidden` の中では sticky が効かないので、囲みに付けないこと
  - ページごとスクロールする表にする場合だけは、固定ヘッダー（`h-16`）に隠れないよう `top-16` が必要になる
- **効いている絞り込みは `partials/filter-chips.blade.php` でタグ表示**（発注一覧・集計・資材マスタ）。呼び出し側で
  `['label' => ..., 'remove' => ['status' => '']]` を作って渡す。
  **条件を外すのは「キーを消す」ではなく「空で送る」**（キーが無いと初期値が再適用されて外れない）
- **アニメーションは `resources/css/app.css` の「アニメーション」セクションに集約**。業務システムなので0.3秒以内・控えめに。
  セクションごと `@media (prefers-reduced-motion: no-preference)` の中にあるので、動きを抑える設定の人には何も動かない
  - ページ遷移のクロスフェードは `@view-transition { navigation: auto }`（対応ブラウザのみ。非対応は今までどおり）
  - `.stagger` を付けた入れ物は、直下の子が少しずつ遅れて出る（ダッシュボードのタイル）
  - `data-countup="123"` を付けた要素は0から数字が回る（`layouts/app.blade.php` の共通スクリプト）。
    終わったら**元のテキストに戻す**ので、¥や小数を含む整形済みの表示が崩れない
  - ページ遷移の暗幕（`#page-loader`）は**250ms待ってから**出す。すぐ切り替わる画面で点滅させないため
- ページネーションのビューは `resources/views/vendor/pagination/` に取り込み済み。
  Laravel標準の「Showing X to Y of Z results」を消し、件数は呼び出し側で日本語表示している

## コード規約メモ
- Laravel 13 では fillable/hidden を **PHP属性** `#[Fillable([...])]` `#[Hidden([...])]` で書く（`$fillable` 配列ではない）。
- 権限チェックは `role` ミドルウェア（`bootstrap/app.php` でエイリアス登録、例：`->middleware('role:admin')`）。
- 画面文言・コメントは日本語。フォームは `_form.blade.php` パーシャルで共通化。
- **バリデーションのメッセージも日本語**（`APP_LOCALE=ja` ＋ `lang/ja/validation.php`）。項目名はコントローラー／モデルから日本語で渡す。
- リソースルートで `create` を `{model}` より先に定義する（"create" がIDと誤解釈されるのを防ぐ）。
- Blade で `@php(...)` の短縮形と `@php ... @endphp` ブロックを**同じファイルで併用しない**（コンパイルが壊れて "Undefined variable" になる）。ブロック形に統一する。
- シーダーは**テーブルごとに1ファイル**（`OfficeSeeder` `UserSeeder` `CategorySeeder` `SupplierSeeder` `MaterialSeeder` `OrderSeeder`）。`DatabaseSeeder` は外部キーの依存順に `call()` するだけ。
  - `OrderSeeder` は発注12件。6ステータス（所長承認待ち・総務承認待ち・発注待ち・発注済・差し戻し・却下）を網羅し、発注済は当月／先月に散らしてある（集計の期間絞り込みを確認できる）。数量は最低ロット以上を守る
- **空文字は `null` に変換される**（`ConvertEmptyStringsToNull` ミドルウェア）。検索条件を配列でセッションに保存すると
  「すべて＝空」が消えてURLから欠落し、既定値が再適用されてしまう。**クエリ文字列のまま保存する**こと。

## 性能（実測値。3,011発注 / 4,822明細で計測）
- 発注申請一覧：1ページ目も20ページ目も **0.08秒**（SQL側で50件だけ取るため何ページ目でも一定）
- 集計：全期間でも **0.08〜0.11秒**、当月なら 0.06秒。`GROUP BY` で集計するので**返る行数はカテゴリ数・業者数程度**にしかならず、
  ページネーションは不要。**期間の日数上限も設けない**（この規模なら10年分でも1秒かからず、年間集計ができないほうが不便）

## 進捗
認証・マスタ管理・発注申請・承認フロー（承認／特例承認／差し戻し・再申請／却下／削除）・発注集計・発注書PDFまで完成、実機確認済み。
今後の候補：自動テスト整備、発注書のメール添付（Outlook向けに `.eml` ダウンロード方式が有力）。
