<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\MaterialCatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// 未ログインならログイン画面、ログイン済みならダッシュボードへ
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// ----- 未ログインユーザー向け -----
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ----- ログイン必須 -----
// must_change_password が立っているユーザーは、パスワードを変えるまで
// パスワード変更画面とログアウト以外を開けない（EnsurePasswordChanged）
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 本人によるパスワード変更（権限は問わない）。
    // 他人のパスワードを変えるのは管理者のユーザー管理（/admin/users）
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // ----- 発注申請 -----
    // 一覧・詳細は全ログインユーザー、作成は営業所ユーザーのみ
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders-export', [OrderController::class, 'export'])->name('orders.export');
    // create は {order} より先に定義する（"create" がIDとして解釈されるのを防ぐ）
    Route::middleware('role:sales')->group(function () {
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        // 差し戻された申請を修正して再申請する（自営業所のもののみ。判定は Order::canBeEditedBy）
        Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    });
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    // 削除（差し戻し中・却下のみ。営業所ユーザー・総務・管理者。判定は Order::canBeDeletedBy）
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // 発注書PDF（発注待ち・発注済のみ・総務/管理者）。1申請＝1業者なので1申請1枚。
    // 「発注済にする」（POST）と「PDFを出す」（GET）を分けている。
    // POSTは状態を変えるのでボタン。詳細画面へ戻してから、その画面でGETを呼んで
    // ダウンロードを始める（PDFを直接返すとページが遷移せず、画面が発注待ちのままに見えるため）。
    // GETは状態を変えないので、先読み・誤クリックで発注済になる心配はない
    Route::post('/orders/{order}/purchase-order', [PurchaseOrderController::class, 'issue'])
        ->name('orders.purchaseOrder');
    Route::get('/orders/{order}/purchase-order', [PurchaseOrderController::class, 'download'])
        ->name('orders.purchaseOrder.file');

    // 発注後メモの更新（発注済のみ・総務/管理者。判定は Order::canUpdatePostOrderNote）
    Route::patch('/orders/{order}/post-order-note', [OrderController::class, 'updatePostOrderNote'])
        ->name('orders.postOrderNote');

    // ----- 資材一覧（閲覧のみ。全ログインユーザー。編集は /admin/materials で管理者のみ） -----
    Route::get('/materials', [MaterialCatalogController::class, 'index'])->name('materials.index');

    // ----- 発注集計（カテゴリ別・業者別・営業所別・資材別＋営業所×業者のクロス集計）。総務・管理者のみ -----
    Route::middleware('role:admin,general_affairs')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports-export', [ReportController::class, 'export'])->name('reports.export');
    });

    // ----- 承認・差し戻し・却下アクション -----
    Route::post('/orders/{order}/manager-approve', [OrderApprovalController::class, 'managerApprove'])->name('orders.managerApprove');
    Route::post('/orders/{order}/affairs-approve', [OrderApprovalController::class, 'affairsApprove'])->name('orders.affairsApprove');
    Route::post('/orders/{order}/special-approve', [OrderApprovalController::class, 'specialApprove'])->name('orders.specialApprove');
    // 差し戻し＝申請者に戻して修正・再申請させる（却下と違って終わりではない）
    Route::post('/orders/{order}/return', [OrderApprovalController::class, 'returnToRequester'])->name('orders.return');
    Route::post('/orders/{order}/reject', [OrderApprovalController::class, 'reject'])->name('orders.reject');

    // ----- マスタ管理 -----
    Route::prefix('admin')->name('admin.')->group(function () {
        // マスタごとに「閲覧」と「編集」で分ける。管理者・総務は常にどちらも通る。
        // それ以外の権限は、ユーザー管理でアカウントごとにオンにしたものだけ
        // （判定は User::canViewMaster / canEditMaster。ナビの出し分けと同じもの）。
        // ナビから消すだけではURL直打ちで入れてしまうので、ルートでも塞ぐ。
        // 各マスタのCSV出力・取り込み（取り込みはIDで突合。詳細は App\Support\MasterCsv とその継承先）は
        // 編集と同じ扱い。ユーザーマスタだけは権限の付与を伴うので対象外（下の role:admin）
        Route::get('materials', [MaterialController::class, 'index'])
            ->middleware('master:materials')->name('materials.index');
        Route::middleware('master:materials,edit')->group(function () {
            Route::get('materials-export', [MaterialController::class, 'export'])->name('materials.export');
            Route::post('materials-import', [MaterialController::class, 'import'])->name('materials.import');
            Route::resource('materials', MaterialController::class)->except(['show', 'index']);
        });

        Route::get('categories', [CategoryController::class, 'index'])
            ->middleware('master:categories')->name('categories.index');
        Route::middleware('master:categories,edit')->group(function () {
            Route::get('categories-export', [CategoryController::class, 'export'])->name('categories.export');
            Route::post('categories-import', [CategoryController::class, 'import'])->name('categories.import');
            Route::resource('categories', CategoryController::class)->except(['show', 'index']);
        });

        Route::get('units', [UnitController::class, 'index'])
            ->middleware('master:units')->name('units.index');
        Route::middleware('master:units,edit')->group(function () {
            Route::get('units-export', [UnitController::class, 'export'])->name('units.export');
            Route::post('units-import', [UnitController::class, 'import'])->name('units.import');
            Route::resource('units', UnitController::class)->except(['show', 'index']);
        });

        Route::get('suppliers', [SupplierController::class, 'index'])
            ->middleware('master:suppliers')->name('suppliers.index');
        Route::middleware('master:suppliers,edit')->group(function () {
            Route::get('suppliers-export', [SupplierController::class, 'export'])->name('suppliers.export');
            Route::post('suppliers-import', [SupplierController::class, 'import'])->name('suppliers.import');
            Route::resource('suppliers', SupplierController::class)->except(['show', 'index']);
        });

        Route::get('offices', [OfficeController::class, 'index'])
            ->middleware('master:offices')->name('offices.index');
        Route::middleware('master:offices,edit')->group(function () {
            Route::get('offices-export', [OfficeController::class, 'export'])->name('offices.export');
            Route::post('offices-import', [OfficeController::class, 'import'])->name('offices.import');
            Route::resource('offices', OfficeController::class)->except(['show', 'index']);
        });

        // ユーザー管理は権限の付与・パスワード変更ができるので管理者のみ。
        // 操作ログ（誰が何をしたかの記録）も管理者だけが見られる
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');
            Route::get('logs-export', [ActivityLogController::class, 'export'])->name('logs.export');
            // 記録オン/オフの設定
            Route::get('logs-settings', [ActivityLogController::class, 'settings'])->name('logs.settings');
            Route::put('logs-settings', [ActivityLogController::class, 'updateSettings'])->name('logs.settings.update');
        });
    });
});
