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

    /** トグルで送られてきたマスタのキーを、User::MASTERS にあるものだけ・その並び順で受け取る */
    private function pickMasters(mixed $input): array
    {
        return array_values(array_intersect(
            array_keys(User::MASTERS),
            array_keys((array) $input)
        ));
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
            // 半角英数字と . _ - だけ。ドットを許すのは `t.katahira` のように
            // 姓名を区切ったIDを実際に使っているため（alpha_dash はドットを弾く）
            'login_id' => ['required', 'string', 'max:50', 'regex:/\A[A-Za-z0-9._-]+\z/', Rule::unique('users', 'login_id')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'office_id' => ['nullable', 'exists:offices,id'],
            'password' => $passwordRule,
        ], [
            'login_id.regex' => 'ログインIDは半角英数字と . _ - だけで入力してください。',
        ], [
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

        // マスタごとの閲覧・編集。管理者・総務は常に全部なので画面のトグルも
        // 常時オン＋操作不可にしてある＝送られてこないので、ここで全部入れておく
        if ($validated['role'] === User::ROLE_SALES) {
            $editable = $this->pickMasters($request->input('editable_masters'));
            // 編集できるなら当然見られる（画面のJSでも連動させているが、迂回されても揃うように）
            $validated['editable_masters'] = $editable;
            $validated['visible_masters'] = array_values(array_intersect(
                array_keys(User::MASTERS),
                array_merge($this->pickMasters($request->input('visible_masters')), $editable)
            ));
        } else {
            $validated['visible_masters'] = array_keys(User::MASTERS);
            $validated['editable_masters'] = array_keys(User::MASTERS);
        }

        $validated['is_active'] = $request->boolean('is_active');
        // 次回ログイン時にパスワードの変更を強制するか（EnsurePasswordChanged が見る）。
        // 新規登録・パスワードの付け替えでは画面側で自動的にオンになる（外すこともできる）
        $validated['must_change_password'] = $request->boolean('must_change_password');
        unset($validated['password']); // パスワードは呼び出し側で扱う

        return $validated;
    }
}
