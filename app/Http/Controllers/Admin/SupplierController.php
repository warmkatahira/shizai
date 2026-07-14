<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /** 業者一覧 */
    public function index(): View
    {
        $suppliers = Supplier::withCount('materials')->orderBy('name')->get();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.suppliers.create', ['supplier' => new Supplier()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validateData($request));

        return redirect()->route('admin.suppliers.index')->with('status', '業者を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    /** 更新 */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateData($request, $supplier));

        return redirect()->route('admin.suppliers.index')->with('status', '業者を更新しました。');
    }

    /** 削除 */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->materials()->exists()) {
            return back()->with('status', 'この業者を仕入先とする資材があるため削除できません。無効化してください。');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', '業者を削除しました。');
    }

    /** バリデーション */
    private function validateData(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:suppliers,code' . ($supplier ? ",{$supplier->id}" : '')],
            'contact_person' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'order_method' => ['nullable', Rule::in(array_keys(Supplier::ORDER_METHODS))],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ], [], [
            'name' => '業者名',
            'code' => '業者コード',
            'contact_person' => '担当者名',
            'phone' => '電話番号',
            'fax' => 'FAX番号',
            'order_method' => '発注方法',
            'email' => 'メールアドレス',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
