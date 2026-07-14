<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ログイン・ログアウトを扱うコントローラー。
 * 新規登録は行わない（ユーザーは管理者が登録する）。
 */
class LoginController extends Controller
{
    /** ログイン画面を表示 */
    public function show(): View
    {
        return view('auth.login');
    }

    /**
     * ログイン処理。
     * 認証はメールではなく login_id で行う（営業所の申請用アカウントは
     * 共通で使い回すため、実在のメールアドレスを持たないことがある）。
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required'],
        ], [], [
            'login_id' => 'ログインID',
            'password' => 'パスワード',
        ]);

        // 有効なユーザーのみログイン可能
        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login_id' => 'ログインIDまたはパスワードが正しくありません。',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** ログアウト処理 */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
