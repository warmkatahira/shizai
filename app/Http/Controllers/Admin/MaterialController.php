<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    /** 資材一覧（カテゴリ順 → 品名順） */
    public function index(): View
    {
        $materials = Material::with(['supplier', 'category'])
            ->leftJoin('categories', 'materials.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order')->orderBy('categories.name')->orderBy('materials.name')
            ->select('materials.*')
            ->get();

        return view('admin.materials.index', compact('materials'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.materials.create', [
            'material' => new Material(),
        ] + $this->formOptions());
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
        ] + $this->formOptions());
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

    /** フォームの選択肢（業者・カテゴリ） */
    private function formOptions(): array
    {
        return [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    /** バリデーション */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:100'],
            'order_method' => ['nullable', 'string', 'max:50'],
            'length_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'width_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'height_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'unit' => ['required', 'string', 'max:20'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'min_lot_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'min_lot_unit' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => '品名',
            'category_id' => '商品カテゴリ',
            'supplier_id' => '発注業者',
            'contact_person' => '担当者名',
            'contact' => '連絡先',
            'order_method' => '発注方法',
            'length_mm' => '縦',
            'width_mm' => '横',
            'height_mm' => '高さ',
            'unit' => '単位',
            'unit_price' => '単価',
            'min_lot_qty' => '最低ロット数量',
            'min_lot_unit' => '最低ロットの単位',
            'note' => '備考',
        ]) + [
            'has_imprint' => $request->boolean('has_imprint'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
