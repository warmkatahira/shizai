<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMasterCsv;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\UnitCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitController extends Controller
{
    use HandlesMasterCsv;

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

    /** 単位マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        return $this->streamCsv(UnitCsv::class, Unit::orderBy('sort_order')->orderBy('name')->get(), 'units');
    }

    /**
     * CSVを取り込んで単位を追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->importCsv($request, UnitCsv::class, route('admin.units.index'), '単位');
    }

    /** バリデーション（規則はモデルに集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request, ?Unit $unit = null): array
    {
        return $request->validate(
            Unit::validationRules($unit?->id),
            [],
            Unit::attributeNames(),
        ) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
