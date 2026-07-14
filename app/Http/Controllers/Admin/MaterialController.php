<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use App\Support\MaterialCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    /** 資材一覧（カテゴリ順 → 品名順） */
    public function index(): View
    {
        $materials = Material::with(['supplier', 'category'])->sortedByCategory()->get();

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

    /** バリデーション（規則は Material に集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request): array
    {
        // チェックボックスは未チェックだとキーごと送られてこないので、先に埋める
        $request->merge([
            'has_imprint' => $request->boolean('has_imprint'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $request->validate(
            Material::validationRules(),
            [],
            Material::attributeNames(),
        );
    }

    /** 資材マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        $materials = Material::with(['supplier', 'category'])->sortedByCategory()->get();

        $filename = 'materials_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($materials) {
            $out = fopen('php://output', 'w');
            // ExcelでUTF-8を正しく開くためのBOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, MaterialCsv::HEADERS);

            foreach ($materials as $material) {
                fputcsv($out, MaterialCsv::row($material));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * CSVを取り込んで資材を追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [], ['file' => 'CSVファイル']);

        $result = MaterialCsv::import($request->file('file')->getRealPath());

        return redirect()->route('admin.materials.index')->with(
            'status',
            "CSVを取り込みました（新規 {$result['created']} 件 / 更新 {$result['updated']} 件）。",
        );
    }
}
