<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    /** 単位一覧 */
    public function index(): View
    {
        $units = Unit::withCount('materials')
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.units.index', compact('units'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.units.create', ['unit' => new Unit()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        Unit::create($this->validateData($request));

        return redirect()->route('admin.units.index')->with('status', '単位を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Unit $unit): View
    {
        return view('admin.units.edit', compact('unit'));
    }

    /** 更新 */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validateData($request, $unit));

        return redirect()->route('admin.units.index')->with('status', '単位を更新しました。');
    }

    /**
     * 削除。単位は資材に必須なので、使われている単位は消せない（無効化で対応）。
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->materials()->exists()) {
            return back()->with('status', 'この単位を使う資材があるため削除できません。無効化してください。');
        }

        $unit->delete();

        return redirect()->route('admin.units.index')->with('status', '単位を削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:20', Rule::unique('units', 'name')->ignore($unit)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => '単位名',
            'sort_order' => '表示順',
        ]) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
