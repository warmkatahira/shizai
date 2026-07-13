<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    /** 資材一覧 */
    public function index(): View
    {
        $materials = Material::with('supplier')->orderBy('category')->orderBy('name')->get();

        return view('admin.materials.index', compact('materials'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.materials.create', [
            'material' => new Material(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        Material::create($this->validateData($request));

        return redirect()->route('admin.materials.index')->with('status', '資材を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Material $material): View
    {
        return view('admin.materials.edit', [
            'material' => $material,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** 更新 */
    public function update(Request $request, Material $material): RedirectResponse
    {
        $material->update($this->validateData($request));

        return redirect()->route('admin.materials.index')->with('status', '資材を更新しました。');
    }

    /** 削除 */
    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return redirect()->route('admin.materials.index')->with('status', '資材を削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'unit' => ['required', 'string', 'max:20'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => '品名',
            'category' => 'カテゴリ',
            'supplier_id' => '業者',
            'unit' => '単位',
            'unit_price' => '参考単価',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
