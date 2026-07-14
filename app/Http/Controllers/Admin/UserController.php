<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /** ユーザー一覧 */
    public function index(): View
    {
        $users = User::with('office')->orderBy('id')->get();

        return view('admin.users.index', compact('users'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['role' => User::ROLE_SALES]),
            'offices' => Office::orderBy('sort_order')->get(),
        ]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['password'] = Hash::make($request->input('password'));

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'ユーザーを登録しました。');
    }

    /** 編集フォーム */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'offices' => Office::orderBy('sort_order')->get(),
        ]);
    }

    /** 更新 */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateData($request, $user);

        // パスワードは入力があったときだけ更新
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'ユーザーを更新しました。');
    }

    /** 削除 */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('status', '自分自身は削除できません。');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'ユーザーを削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request, ?User $user = null): array
    {
        // 新規はパスワード必須、編集は任意
        $passwordRule = $user
            ? ['nullable', 'string', 'min:8']
            : ['required', 'string', 'min:8'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            // ログインIDで認証する。メールは通知先なので任意
            'login_id' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'login_id')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'office_id' => ['nullable', 'exists:offices,id'],
            'password' => $passwordRule,
        ], [], [
            'name' => '氏名',
            'login_id' => 'ログインID',
            'email' => 'メールアドレス',
            'role' => '権限',
            'office_id' => '所属営業所',
            'password' => 'パスワード',
        ]);

        // 営業所ユーザーは所属営業所を必須にする
        if ($validated['role'] === User::ROLE_SALES && empty($validated['office_id'])) {
            throw ValidationException::withMessages([
                'office_id' => '営業所ユーザーには所属営業所が必要です。',
            ]);
        }

        // 営業所ユーザーのみ所長フラグを持てる。それ以外は所属・所長をクリア
        if ($validated['role'] === User::ROLE_SALES) {
            $validated['is_manager'] = $request->boolean('is_manager');
        } else {
            $validated['office_id'] = null;
            $validated['is_manager'] = false;
        }

        $validated['is_active'] = $request->boolean('is_active');
        unset($validated['password']); // パスワードは呼び出し側で扱う

        return $validated;
    }
}
