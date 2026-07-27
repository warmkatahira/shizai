<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
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
        $category = Category::create($this->validateData($request));
        ActivityLogger::log('master.category_created', "カテゴリ「{$category->name}」を登録しました", $category);

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
        ActivityLogger::log('master.category_updated', "カテゴリ「{$category->name}」を更新しました", $category);

        return redirect()->route('admin.categories.index')->with('status', 'カテゴリを更新しました。');
    }

    /**
     * 削除。資材が紐づいていても外部キーは nullOnDelete なので、
     * その資材はカテゴリ未設定になる（消えはしない）。
     */
    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();
        ActivityLogger::log('master.category_deleted', "カテゴリ「{$name}」を削除しました");

        return redirect()->route('admin.categories.index')->with('status', 'カテゴリを削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->ignore($category)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => 'カテゴリ名',
            'sort_order' => '表示順',
        ]) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
