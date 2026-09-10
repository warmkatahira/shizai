<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMasterCsv;
use App\Http\Controllers\Controller;
use App\Models\ShippingDestination;
use App\Support\ShippingDestinationCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 直送先マスタ（発注申請で自営業所以外へ送りたいときの送り先）。
 */
class ShippingDestinationController extends Controller
{
    use HandlesMasterCsv;

    /** 直送先一覧 */
    public function index(): View
    {
        $destinations = ShippingDestination::withCount('orders')->sorted()->get();

        return view('admin.shipping_destinations.index', compact('destinations'));
    }

    /** 新規作成フォーム */
    public function create(): View
    {
        return view('admin.shipping_destinations.create', ['destination' => new ShippingDestination()]);
    }

    /** 登録 */
    public function store(Request $request): RedirectResponse
    {
        ShippingDestination::create($this->validateData($request));

        return redirect()->route('admin.shipping_destinations.index')->with('status', '直送先を登録しました。');
    }

    /** 編集フォーム */
    public function edit(ShippingDestination $shippingDestination): View
    {
        return view('admin.shipping_destinations.edit', ['destination' => $shippingDestination]);
    }

    /** 更新 */
    public function update(Request $request, ShippingDestination $shippingDestination): RedirectResponse
    {
        $shippingDestination->update($this->validateData($request, $shippingDestination));

        return redirect()->route('admin.shipping_destinations.index')->with('status', '直送先を更新しました。');
    }

    /**
     * 削除。
     * 過去の発注申請が納入先として指しているものは消せない（履歴の納入先が分からなくなるため）。
     */
    public function destroy(ShippingDestination $shippingDestination): RedirectResponse
    {
        if ($shippingDestination->orders()->exists()) {
            return back()->with('status', 'この直送先を指定した発注申請があるため削除できません。無効化してください。');
        }

        $shippingDestination->delete();

        return redirect()->route('admin.shipping_destinations.index')->with('status', '直送先を削除しました。');
    }

    /** 直送先マスタをCSVでダウンロード（Excel対応のBOM付きUTF-8） */
    public function export(): StreamedResponse
    {
        return $this->streamCsv(
            ShippingDestinationCsv::class,
            ShippingDestination::sorted()->get(),
            'shipping_destinations',
        );
    }

    /**
     * CSVを取り込んで直送先を追加・更新する。
     * IDが入っている行は更新、空の行は新規追加。1行でもエラーがあれば何も取り込まない。
     */
    public function import(Request $request): RedirectResponse
    {
        return $this->importCsv(
            $request,
            ShippingDestinationCsv::class,
            route('admin.shipping_destinations.index'),
            '直送先',
        );
    }

    /** バリデーション（規則はモデルに集約。CSV取り込みと同じものを使う） */
    private function validateData(Request $request, ?ShippingDestination $destination = null): array
    {
        return $request->validate(
            ShippingDestination::validationRules($destination?->id),
            [],
            ShippingDestination::attributeNames(),
        ) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
