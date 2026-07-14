<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\MaterialCatalogController;
use App\Http\Controllers\OrderController;
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
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ----- 発注申請 -----
    // 一覧・詳細は全ログインユーザー、作成は営業所ユーザーのみ
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders-export', [OrderController::class, 'export'])->name('orders.export');
    // create は {order} より先に定義する（"create" がIDとして解釈されるのを防ぐ）
    Route::middleware('role:sales')->group(function () {
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    });
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // 発注書PDF（発注待ち・発注済のみ・総務/管理者）。1申請＝1業者なので1申請1枚。
    // 出力すると「発注済」に進む（＝実際に業者へ発注した）ので、GETではなくPOSTで受ける
    Route::post('/orders/{order}/purchase-order', [PurchaseOrderController::class, 'download'])
        ->name('orders.purchaseOrder');

    // ----- 資材一覧（閲覧のみ。全ログインユーザー。編集は /admin/materials で管理者のみ） -----
    Route::get('/materials', [MaterialCatalogController::class, 'index'])->name('materials.index');

    // ----- 発注集計（カテゴリ別・業者別・営業所別・資材別） -----
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports-export', [ReportController::class, 'export'])->name('reports.export');

    // ----- 承認・却下アクション -----
    Route::post('/orders/{order}/manager-approve', [OrderApprovalController::class, 'managerApprove'])->name('orders.managerApprove');
    Route::post('/orders/{order}/affairs-approve', [OrderApprovalController::class, 'affairsApprove'])->name('orders.affairsApprove');
    Route::post('/orders/{order}/special-approve', [OrderApprovalController::class, 'specialApprove'])->name('orders.specialApprove');
    Route::post('/orders/{order}/reject', [OrderApprovalController::class, 'reject'])->name('orders.reject');

    // ----- マスタ管理 -----
    Route::prefix('admin')->name('admin.')->group(function () {
        // 管理者・総務が編集できる
        Route::middleware('role:admin,general_affairs')->group(function () {
            Route::resource('offices', OfficeController::class)->except('show');
            Route::resource('suppliers', SupplierController::class)->except('show');
            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('materials', MaterialController::class)->except('show');
        });

        // ユーザー管理は権限の付与・パスワード変更ができるので管理者のみ
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class)->except('show');
        });
    });
});
