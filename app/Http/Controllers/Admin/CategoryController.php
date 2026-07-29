<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMasterCsv;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\CategoryCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryController extends Controller
{
    use HandlesMasterCsv;

    /** カテゴリ一覧 */
    public function index(): View
    {
        $categories = Category::withCount('materials')
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.categories.create', ['category' => new Category()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validateData($request));

        return redirect()->route('admin.categories.index')->with('status', 'カテゴリを登録しました。');
    }

    /** 編集フォーム */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    /** 更新 */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validateData($request, $category));

        return redirect()->route('admin.categories.index')->with('status', 'カテゴリを更新しました。');
    }

    /**
     * 削除。資材が紐づいていても外部キーは nullOnDelete なので、
     * その資材はカテゴリ未設定になる（消えはしない）。
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'カテゴリを削除しました。');
    }

    /** カテゴリマスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        return $this->streamCsv(CategoryCsv::class, Category::orderBy('sort_order')->orderBy('name')->get(), 'categories');
    }

    /**
     * CSVを取り込んでカテゴリを追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->importCsv($request, CategoryCsv::class, route('admin.categories.index'), 'カテゴリ');
    }

    /** バリデーション（規則はモデルに集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request, ?Category $category = null): array
    {
        return $request->validate(
            Category::validationRules($category?->id),
            [],
            Category::attributeNames(),
        ) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
