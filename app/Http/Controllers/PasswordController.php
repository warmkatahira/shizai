<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * ログイン中の本人によるパスワード変更。
 *
 * 管理者のユーザー管理（Admin\UserController）とは別物。あちらは他人のパスワードを
 * 管理者が付け替えるもので、こちらは本人が自分のものを変える。
 * 権限は問わない（全ログインユーザーが使える）。
 */
class PasswordController extends Controller
{
    /** パスワード変更フォーム */
    public function edit(): View
    {
        return view('password.edit');
    }

    /** パスワードの変更 */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            // 本人確認。画面を開きっぱなしの端末から勝手に変えられないようにする
            'current_password' => ['required', 'current_password'],
            // confirmed で password_confirmation と一致するか見る
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => '現在のパスワードが違います。',
            'password.different' => '新しいパスワードは現在のものと違うものにしてください。',
        ], [
            'current_password' => '現在のパスワード',
            'password' => '新しいパスワード',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()->route('dashboard')->with('status', 'パスワードを変更しました。');
    }
}
