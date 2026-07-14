<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 資材マスタの閲覧（全ログインユーザー）。
 *
 * 編集は管理者だけ（/admin/materials）。こちらは営業所・総務が
 * 「どの業者に何がいくらであるか」を確認するための読み取り専用の一覧。
 */
class MaterialCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $materials = Material::with(['category', 'supplier'])
            ->active()
            ->when($request->filled('supplier_id'),
                fn ($q) => $q->where('materials.supplier_id', $request->input('supplier_id')))
            ->when($request->filled('category_id'),
                fn ($q) => $q->where('materials.category_id', $request->input('category_id')))
            ->when($request->filled('keyword'),
                fn ($q) => $q->where('materials.name', 'like', '%' . $request->input('keyword') . '%'))
            ->sortedByCategory()
            ->get();

        return view('materials.index', [
            'materials' => $materials,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => $request->only(['supplier_id', 'category_id', 'keyword']),
        ]);
    }
}
