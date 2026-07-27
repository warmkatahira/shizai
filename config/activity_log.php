<?php

/**
 * 操作ログのカタログ。
 *
 * 「どのルートを・どんな操作として記録するか」をここに集約する。
 * 記録は App\Http\Middleware\LogActivity（画面操作）と認証イベントのリスナー
 * （ログイン系）が、このカタログを見て自動で行う。各コントローラーには
 * ログ用のコードを書かない。新しく記録したい操作は、ここに1行足すだけ。
 *
 * default … 記録のオン/オフの初期値（設定画面で上書きできる）。
 *           変更操作は true（残す）、ダウンロードは false（必要時に画面でオンにする）。
 */
return [

    // カテゴリの表示名（設定画面・一覧の絞り込みで使う）
    'categories' => [
        'auth' => 'ログイン',
        'order' => '発注',
        'master' => 'マスタ',
        'user' => 'ユーザー',
        'report' => '集計',
    ],

    // ルート名 → 記録定義（ミドルウェアが使う）
    'routes' => [
        // 発注
        'orders.store' => ['action' => 'order.created', 'label' => '発注の申請', 'category' => 'order', 'default' => true],
        'orders.update' => ['action' => 'order.resubmitted', 'label' => '発注の再申請', 'category' => 'order', 'default' => true],
        'orders.destroy' => ['action' => 'order.deleted', 'label' => '発注の削除', 'category' => 'order', 'default' => true],
        'orders.managerApprove' => ['action' => 'order.manager_approved', 'label' => '所長承認', 'category' => 'order', 'default' => true],
        'orders.affairsApprove' => ['action' => 'order.affairs_approved', 'label' => '総務承認', 'category' => 'order', 'default' => true],
        'orders.specialApprove' => ['action' => 'order.special_approved', 'label' => '特例承認', 'category' => 'order', 'default' => true],
        'orders.return' => ['action' => 'order.returned', 'label' => '差し戻し', 'category' => 'order', 'default' => true],
        'orders.reject' => ['action' => 'order.rejected', 'label' => '却下', 'category' => 'order', 'default' => true],
        'orders.postOrderNote' => ['action' => 'order.post_note_updated', 'label' => '発注者メモの更新', 'category' => 'order', 'default' => true],
        'orders.purchaseOrder' => ['action' => 'order.purchase_order_issued', 'label' => '発注書の作成・再発行', 'category' => 'order', 'default' => true],

        // マスタ
        'admin.offices.store' => ['action' => 'master.office_created', 'label' => '営業所の登録', 'category' => 'master', 'default' => true],
        'admin.offices.update' => ['action' => 'master.office_updated', 'label' => '営業所の更新', 'category' => 'master', 'default' => true],
        'admin.offices.destroy' => ['action' => 'master.office_deleted', 'label' => '営業所の削除', 'category' => 'master', 'default' => true],
        'admin.suppliers.store' => ['action' => 'master.supplier_created', 'label' => '業者の登録', 'category' => 'master', 'default' => true],
        'admin.suppliers.update' => ['action' => 'master.supplier_updated', 'label' => '業者の更新', 'category' => 'master', 'default' => true],
        'admin.suppliers.destroy' => ['action' => 'master.supplier_deleted', 'label' => '業者の削除', 'category' => 'master', 'default' => true],
        'admin.categories.store' => ['action' => 'master.category_created', 'label' => 'カテゴリの登録', 'category' => 'master', 'default' => true],
        'admin.categories.update' => ['action' => 'master.category_updated', 'label' => 'カテゴリの更新', 'category' => 'master', 'default' => true],
        'admin.categories.destroy' => ['action' => 'master.category_deleted', 'label' => 'カテゴリの削除', 'category' => 'master', 'default' => true],
        'admin.materials.store' => ['action' => 'master.material_created', 'label' => '資材の登録', 'category' => 'master', 'default' => true],
        'admin.materials.update' => ['action' => 'master.material_updated', 'label' => '資材の更新', 'category' => 'master', 'default' => true],
        'admin.materials.destroy' => ['action' => 'master.material_deleted', 'label' => '資材の削除', 'category' => 'master', 'default' => true],
        'admin.materials.import' => ['action' => 'master.material_imported', 'label' => '資材CSVの取り込み', 'category' => 'master', 'default' => true],

        // ユーザー
        'admin.users.store' => ['action' => 'user.created', 'label' => 'ユーザーの登録', 'category' => 'user', 'default' => true],
        'admin.users.update' => ['action' => 'user.updated', 'label' => 'ユーザーの更新', 'category' => 'user', 'default' => true],
        'admin.users.destroy' => ['action' => 'user.deleted', 'label' => 'ユーザーの削除', 'category' => 'user', 'default' => true],

        // ダウンロード（既定オフ。必要になったら設定画面でオンにする）
        'orders.export' => ['action' => 'order.list_exported', 'label' => '発注一覧CSVのダウンロード', 'category' => 'order', 'default' => false],
        'reports.export' => ['action' => 'report.exported', 'label' => '集計CSVのダウンロード', 'category' => 'report', 'default' => false],
        'admin.materials.export' => ['action' => 'master.material_exported', 'label' => '資材マスタCSVのダウンロード', 'category' => 'master', 'default' => false],
    ],

    // 認証イベント → 記録定義（リスナーが使う。ルートでは成功/失敗を判別しづらいため）
    'auth' => [
        'login' => ['action' => 'auth.login', 'label' => 'ログイン成功', 'category' => 'auth', 'default' => true],
        'logout' => ['action' => 'auth.logout', 'label' => 'ログアウト', 'category' => 'auth', 'default' => true],
        'failed' => ['action' => 'auth.login_failed', 'label' => 'ログイン失敗', 'category' => 'auth', 'default' => true],
    ],
];
