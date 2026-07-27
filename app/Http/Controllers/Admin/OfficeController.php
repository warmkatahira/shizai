<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeController extends Controller
{
    /** 営業所一覧 */
    public function index(): View
    {
        $offices = Office::withCount('users')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.offices.index', compact('offices'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.offices.create', ['office' => new Office()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $office = Office::create($data);
        ActivityLogger::log('master.office_created', "営業所「{$office->name}」を登録しました", $office);

        return redirect()->route('admin.offices.index')->with('status', '営業所を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Office $office): View
    {
        return view('admin.offices.edit', compact('office'));
    }

    /** 更新 */
    public function update(Request $request, Office $office): RedirectResponse
    {
        $office->update($this->validateData($request, $office));
        ActivityLogger::log('master.office_updated', "営業所「{$office->name}」を更新しました", $office);

        return redirect()->route('admin.offices.index')->with('status', '営業所を更新しました。');
    }

    /** 削除 */
    public function destroy(Office $office): RedirectResponse
    {
        if ($office->users()->exists()) {
            return back()->with('status', '所属ユーザーがいるため削除できません。無効化してください。');
        }

        $name = $office->name;
        $office->delete();
        ActivityLogger::log('master.office_deleted', "営業所「{$name}」を削除しました");

        return redirect()->route('admin.offices.index')->with('status', '営業所を削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request, ?Office $office = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:offices,code' . ($office ? ",{$office->id}" : '')],
            'postal_code' => ['nullable', 'string', 'max:8'],
            'address' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => '営業所名',
            'code' => '営業所コード',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'tel' => '電話番号',
            'fax' => 'FAX番号',
            'sort_order' => '表示順',
        ]) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
