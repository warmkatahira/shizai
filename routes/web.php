<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\OrderController;
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

    // ----- 承認・却下アクション -----
    Route::post('/orders/{order}/manager-approve', [OrderApprovalController::class, 'managerApprove'])->name('orders.managerApprove');
    Route::post('/orders/{order}/affairs-approve', [OrderApprovalController::class, 'affairsApprove'])->name('orders.affairsApprove');
    Route::post('/orders/{order}/special-approve', [OrderApprovalController::class, 'specialApprove'])->name('orders.specialApprove');
    Route::post('/orders/{order}/reject', [OrderApprovalController::class, 'reject'])->name('orders.reject');

    // ----- 管理者のみ：マスタ管理 -----
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('offices', OfficeController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('materials', MaterialController::class)->except('show');
    });
});
