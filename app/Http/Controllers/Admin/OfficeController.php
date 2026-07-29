<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMasterCsv;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Support\OfficeCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficeController extends Controller
{
    use HandlesMasterCsv;

    /** 営業所一覧 */
    public function index(): View
    {
        $offices = Office::withCount('users')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.offices.index', compact('offices'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.offices.create', ['office' => new Office()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        Office::create($data);

        return redirect()->route('admin.offices.index')->with('status', '営業所を登録しました。');
    }

    /** 編集フォーム */
    public function edit(Office $office): View
    {
        return view('admin.offices.edit', compact('office'));
    }

    /** 更新 */
    public function update(Request $request, Office $office): RedirectResponse
    {
        $office->update($this->validateData($request, $office));

        return redirect()->route('admin.offices.index')->with('status', '営業所を更新しました。');
    }

    /** 削除 */
    public function destroy(Office $office): RedirectResponse
    {
        if ($office->users()->exists()) {
            return back()->with('status', '所属ユーザーがいるため削除できません。無効化してください。');
        }

        $office->delete();

        return redirect()->route('admin.offices.index')->with('status', '営業所を削除しました。');
    }

    /** 営業所マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        return $this->streamCsv(OfficeCsv::class, Office::orderBy('sort_order')->orderBy('id')->get(), 'offices');
    }

    /**
     * CSVを取り込んで営業所を追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->importCsv($request, OfficeCsv::class, route('admin.offices.index'), '営業所');
    }

    /** バリデーション（規則はモデルに集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request, ?Office $office = null): array
    {
        return $request->validate(
            Office::validationRules($office?->id),
            [],
            Office::attributeNames(),
        ) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
