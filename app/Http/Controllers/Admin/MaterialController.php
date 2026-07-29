<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RemembersLastSearch;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Unit;
use App\Support\MaterialCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    use RemembersLastSearch;

    /** 資材一覧（カテゴリ順 → 品名順。業者・カテゴリ・品名・状態で絞り込み） */
    public function index(Request $request): View
    {
        $this->applyDefaultStatus($request);

        // 編集画面から戻ったとき・保存したときに同じ絞り込みへ戻れるよう覚えておく
        $this->rememberSearch($request);

        return view('admin.materials.index', [
            'materials' => $this->filteredMaterials($request)->get(),
            // 絞り込みの選択肢は無効なものも含める（無効な業者・カテゴリで探したいこともある）
            'suppliers' => Supplier::orderBy('name')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'filters' => $request->only(['supplier_id', 'category_id', 'keyword', 'status']),
        ]);
    }

    /**
     * 状態の初期値。マスタ管理から開いた初回表示のときだけ「有効」にする
     * （普段使うのは有効な資材だけなので、無効を混ぜて見せない）。
     *
     * 「すべて」を選ぶと status= が空で送られ、ConvertEmptyStringsToNull で null になるため、
     * 値ではなく**キーの有無**で初回かどうかを判定する（発注一覧と同じやり方）。
     */
    private function applyDefaultStatus(Request $request): void
    {
        if (! $request->has('status')) {
            $request->merge(['status' => 'active']);
        }
    }

    /**
     * 一覧・CSV出力で共通の絞り込みクエリ。
     * 発注業者 / カテゴリ / 品名キーワード / 状態（有効・無効）。
     */
    private function filteredMaterials(Request $request): Builder
    {
        return Material::with(['supplier', 'category', 'unit'])
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
    public function create(Request $request): View
    {
        return view('admin.materials.create', [
            'material' => new Material(),
            'backUrl' => $this->backUrl($request),
        ] + $this->formOptions());
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($path = $this->uploadedImagePath($request)) {
            $data['image_path'] = $path;
        }

        Material::create($data);

        return redirect($this->backUrl($request))->with('status', '資材を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Request $request, Material $material): View
    {
        return view('admin.materials.edit', [
            'material' => $material,
            'backUrl' => $this->backUrl($request),
        ] + $this->formOptions());
    }

    /** 更新 */
    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($path = $this->uploadedImagePath($request)) {
            // 差し替え：古い画像は消す
            $this->deleteImage($material->image_path);
            $data['image_path'] = $path;
        } elseif ($request->boolean('remove_image')) {
            // 「画像を削除」にチェック
            $this->deleteImage($material->image_path);
            $data['image_path'] = null;
        }

        $material->update($data);

        return redirect($this->backUrl($request))->with('status', '資材を更新しました。');
    }

    /** 削除 */
    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $this->deleteImage($material->image_path);
        $material->delete();

        return redirect($this->backUrl($request))->with('status', '資材を削除しました。');
    }

    /**
     * 一覧へ戻る先＝直前の絞り込み結果。
     * 編集画面の「キャンセル」と、登録・更新・削除・CSV取り込み後のリダイレクトで使う。
     * 素の一覧に戻すと、状態の初期値「有効」が再適用されて絞り込みが消えてしまう。
     */
    private function backUrl(Request $request): string
    {
        return $this->lastSearchUrl($request, 'admin.materials.index');
    }

    /**
     * アップロードされた画像を検証して public ディスクに保存し、保存パスを返す。
     * ファイルが無ければ null。
     */
    private function uploadedImagePath(Request $request): ?string
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], ['image' => '画像']);

        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('materials', 'public');
    }

    /** public ディスク上の画像を削除する（パスが無ければ何もしない） */
    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** フォームの選択肢（業者・カテゴリ・単位） */
    private function formOptions(): array
    {
        return [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
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
        // 画面で見えているものがそのままCSVに出るよう、一覧と同じ初期値を適用する
        $this->applyDefaultStatus($request);

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

        return redirect($this->backUrl($request))->with(
            'status',
            "CSVを取り込みました（新規 {$result['created']} 件 / 更新 {$result['updated']} 件）。",
        );
    }
}
