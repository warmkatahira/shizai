<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /** ログイン後のトップ画面 */
    public function index(): View
    {
        return view('dashboard');
    }
}
