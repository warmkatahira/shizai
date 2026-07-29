<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMasterCsv;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Support\SupplierCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    use HandlesMasterCsv;

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

    /** 業者マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        return $this->streamCsv(SupplierCsv::class, Supplier::orderBy('name')->get(), 'suppliers');
    }

    /**
     * CSVを取り込んで業者を追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->importCsv($request, SupplierCsv::class, route('admin.suppliers.index'), '業者');
    }

    /** バリデーション（規則はモデルに集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate(
            Supplier::validationRules($supplier?->id),
            [],
            Supplier::attributeNames(),
        ) + ['is_active' => $request->boolean('is_active')];
    }
}
