<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use App\Support\MaterialCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    /** 資材一覧（カテゴリ順 → 品名順。業者・カテゴリ・品名・状態で絞り込み） */
    public function index(Request $request): View
    {
        return view('admin.materials.index', [
            'materials' => $this->filteredMaterials($request)->get(),
            // 絞り込みの選択肢は無効なものも含める（無効な業者・カテゴリで探したいこともある）
            'suppliers' => Supplier::orderBy('name')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'filters' => $request->only(['supplier_id', 'category_id', 'keyword', 'status']),
        ]);
    }

    /**
     * 一覧・CSV出力で共通の絞り込みクエリ。
     * 発注業者 / カテゴリ / 品名キーワード / 状態（有効・無効）。
     * 管理側は無効な資材も一覧に出すので、状態は既定では絞らない。
     */
    private function filteredMaterials(Request $request): Builder
    {
        return Material::with(['supplier', 'category'])
            ->when($request->filled('supplier_id'),
                fn ($q) => $q->where('materials.supplier_id', $request->input('supplier_id')))
            ->when($request->filled('category_id'),
                fn ($q) => $q->where('materials.category_id', $request->input('category_id')))
            ->when($request->filled('keyword'),
                fn ($q) => $q->where('materials.name', 'like', '%' . $request->input('keyword') . '%'))
            ->when($request->input('status') === 'active',
                fn ($q) => $q->where('materials.is_active', true))
            ->when($request->input('status') === 'inactive',
                fn ($q) => $q->where('materials.is_active', false))
            ->sortedByCategory();
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
        $material = Material::create($this->validateData($request));
        ActivityLogger::log('master.material_created', "資材「{$material->name}」を登録しました", $material);

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
        ActivityLogger::log('master.material_updated', "資材「{$material->name}」を更新しました", $material);

        return redirect()->route('admin.materials.index')->with('status', '資材を更新しました。');
    }

    /** 削除 */
    public function destroy(Material $material): RedirectResponse
    {
        $name = $material->name;
        $material->delete();
        ActivityLogger::log('master.material_deleted', "資材「{$name}」を削除しました");

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

    /**
     * 資材マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8）。
     * 一覧と同じ絞り込みを適用するので、いま表示している内容がそのまま出力される。
     */
    public function export(Request $request): StreamedResponse
    {
        $materials = $this->filteredMaterials($request)->get();

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

        ActivityLogger::log('master.material_imported', "資材CSVを取り込みました（新規 {$result['created']} 件 / 更新 {$result['updated']} 件）");

        return redirect()->route('admin.materials.index')->with(
            'status',
            "CSVを取り込みました（新規 {$result['created']} 件 / 更新 {$result['updated']} 件）。",
        );
    }
}
