@extends('layouts.app')

@section('title', '発注申請 #' . $order->id)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold">発注申請 #{{ $order->id }}</h1>
        @include('orders.partials.status-badge')
    </div>

    {{-- 却下理由の表示 --}}
    @if ($order->isRejected() && $order->reject_reason)
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <span class="font-medium">却下理由：</span>{{ $order->reject_reason }}
            @if ($order->rejectedBy)
                <span class="text-red-500">（{{ $order->rejectedBy->name }}）</span>
            @endif
        </div>
    @endif

    {{-- 差し戻しの表示。申請者はここから修正して再申請する --}}
    @if ($order->isReturned())
        <div class="mb-6 rounded-md bg-orange-50 border border-orange-200 px-4 py-3 text-sm text-orange-800">
            <p>
                <span class="font-medium">差し戻し：</span>{{ $order->return_reason }}
                @if ($order->returnedBy)
                    <span class="text-orange-600">（{{ $order->returnedBy->name }} / {{ $order->returned_at?->format('Y/m/d H:i') }}）</span>
                @endif
            </p>
            @if ($actions['edit'])
                <p class="mt-2 text-orange-700">
                    内容を修正して再申請してください。再申請すると承認は最初からやり直しになります。
                </p>
            @endif
        </div>
    @elseif ($order->return_reason && ! $order->isRejected())
        {{-- 一度差し戻され、修正して再申請されたもの。承認者が経緯を追えるように残す --}}
        <div class="mb-6 rounded-md bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600">
            <span class="font-medium">差し戻し後に再申請された申請です。</span>
            差し戻しの理由：{{ $order->return_reason }}
            @if ($order->returnedBy)
                <span class="text-gray-400">（{{ $order->returnedBy->name }} / {{ $order->returned_at?->format('Y/m/d H:i') }}）</span>
            @endif
        </div>
    @endif

    {{-- 特例承認の表示 --}}
    @if ($order->is_special_approval && ! $order->isRejected())
        <div class="mb-6 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            <span class="font-medium">総務による特例承認：</span>{{ $order->special_reason }}
        </div>
    @endif

    {{-- 基本情報 --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">営業所</dt>
                <dd class="font-medium">{{ $order->office->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">発注業者</dt>
                <dd class="font-medium">{{ $order->supplier?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">発注者</dt>
                <dd class="font-medium">
                    {{ $order->requester_name ?? '—' }}
                    <span class="block text-xs text-gray-400 font-normal">アカウント：{{ $order->requester->name }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">申請日時</dt>
                <dd class="font-medium">{{ $order->created_at->format('Y/m/d H:i') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">所長承認</dt>
                <dd class="font-medium">
                    @if ($order->managerApprover)
                        {{ $order->managerApprover->name }}
                        <span class="text-gray-400 text-xs">{{ $order->manager_approved_at?->format('m/d H:i') }}</span>
                    @elseif ($order->is_special_approval)
                        <span class="text-gray-400">（特例でスキップ）</span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">総務承認</dt>
                <dd class="font-medium">
                    @if ($order->reviewer)
                        {{ $order->reviewer->name }}
                        <span class="text-gray-400 text-xs">{{ $order->reviewed_at?->format('m/d H:i') }}</span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">発注（発注書の作成）</dt>
                <dd class="font-medium">
                    @if ($order->orderedBy)
                        {{ $order->orderedBy->name }}
                        <span class="text-gray-400 text-xs">{{ $order->ordered_at?->format('m/d H:i') }}</span>
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>

        @if ($order->desired_delivery_date || $order->note || $order->supplier_note)
            <div class="mt-4 pt-4 border-t border-gray-100 text-sm space-y-3">
                @if ($order->desired_delivery_date)
                    <div>
                        <dt class="text-gray-500 mb-1">希望納期</dt>
                        <dd>{{ $order->desired_delivery_date->format('Y/m/d') }}</dd>
                    </div>
                @endif
                @if ($order->note)
                    <div>
                        <dt class="text-gray-500 mb-1">備考（社内向け）</dt>
                        <dd class="whitespace-pre-wrap">{{ $order->note }}</dd>
                    </div>
                @endif
                @if ($order->supplier_note)
                    <div>
                        <dt class="text-gray-500 mb-1">業者への連絡事項（発注書に印字）</dt>
                        <dd class="whitespace-pre-wrap">{{ $order->supplier_note }}</dd>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- 発注書PDF（発注待ち・発注済のみ・総務/管理者）。出すと「発注済」に進むのでPOST --}}
    @if (($order->isPendingOrder() || $order->isOrdered()) && auth()->user()->canIssuePurchaseOrder())
        <div class="bg-white shadow rounded-lg p-6 mb-6 border-l-4 border-emerald-400">
            <h2 class="font-semibold mb-1">発注書</h2>
            <p class="text-xs text-gray-500 mb-4">
                @if ($order->isPendingOrder())
                    <span class="text-emerald-700 font-medium">発注書を作成すると、この申請は「発注済」になります。</span>
                    担当者名には、いま操作しているあなたの氏名（{{ auth()->user()->name }}）が入ります。
                @else
                    発注済です（{{ $order->orderedBy?->name }} / {{ $order->ordered_at?->format('Y/m/d H:i') }}）。同じ内容で再発行できます。
                @endif
            </p>
            @if ($order->supplier)
                <form method="POST" action="{{ route('orders.purchaseOrder', $order) }}"
                      @if ($order->isPendingOrder())
                          onsubmit="return confirm('発注書を作成します。この申請は「発注済」になります。よろしいですか？')"
                      @endif>
                    @csrf
                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-md">
                        @if ($order->isPendingOrder())
                            発注書を作成して発注する（{{ $order->supplier->name }}）
                        @else
                            発注書を再ダウンロード（{{ $order->supplier->name }}）
                        @endif
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-400">業者が設定されていないため、発注書を作成できません。</p>
            @endif
        </div>
    @endif

    {{-- 申請者側の対応（差し戻し中の修正・再申請／不要になった申請の削除） --}}
    @if ($actions['edit'] || $actions['delete'])
        <div class="bg-white shadow rounded-lg p-6 mb-6 border-l-4 border-orange-300">
            <h2 class="font-semibold mb-1">この申請の取り扱い</h2>
            <p class="text-xs text-gray-500 mb-4">
                @if ($actions['edit'])
                    修正して再申請できます。この申請が不要になった場合は削除してください。
                @else
                    この申請は削除できます（削除すると一覧・CSVから完全になくなります）。
                @endif
            </p>
            <div class="flex flex-wrap items-center gap-3">
                @if ($actions['edit'])
                    <a href="{{ route('orders.edit', $order) }}"
                       class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">
                        修正して再申請する
                    </a>
                @endif

                @if ($actions['delete'])
                    <form method="POST" action="{{ route('orders.destroy', $order) }}"
                          onsubmit="return confirm('この発注申請を削除します。元に戻せません。よろしいですか？')">
                        @csrf
                        @method('DELETE')
                        <button class="bg-white border border-red-300 text-red-600 hover:bg-red-50 text-sm px-5 py-2 rounded-md">
                            この申請を削除する
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- 承認アクション --}}
    @if ($actions['managerApprove'] || $actions['affairsApprove'] || $actions['specialApprove'] || $actions['return'] || $actions['reject'])
        <div class="bg-white shadow rounded-lg p-6 mb-6 border-l-4 border-accent-dark">
            <h2 class="font-semibold mb-4">この申請への対応</h2>

            @include('admin.partials.errors')

            <div class="flex flex-wrap gap-3">
                {{-- 所長承認 --}}
                @if ($actions['managerApprove'])
                    <form method="POST" action="{{ route('orders.managerApprove', $order) }}"
                          onsubmit="return confirm('この申請を承認して総務へ回しますか？')">
                        @csrf
                        <button class="bg-accent hover:bg-accent-dark text-ink text-sm px-5 py-2 rounded-md">
                            承認する（総務へ）
                        </button>
                    </form>
                @endif

                {{-- 総務による承認（＝発注確定） --}}
                @if ($actions['affairsApprove'])
                    <form method="POST" action="{{ route('orders.affairsApprove', $order) }}"
                          onsubmit="return confirm('この申請を承認しますか？承認すると「発注待ち」になり、発注書を作成できるようになります。')">
                        @csrf
                        <button class="bg-green-600 hover:bg-green-700 text-white text-sm px-5 py-2 rounded-md">
                            承認する
                        </button>
                    </form>
                @endif
            </div>

            {{-- 総務の特例承認（所長承認待ちのとき、理由必須） --}}
            @if ($actions['specialApprove'])
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <p class="text-sm text-gray-600 mb-2">
                        <span class="font-medium text-amber-700">特例承認</span>：所長が不在などの場合、所長承認を飛ばして総務が直接承認できます（「発注待ち」になります）。
                    </p>
                    <form method="POST" action="{{ route('orders.specialApprove', $order) }}"
                          onsubmit="return confirm('所長承認を飛ばして特例で承認しますか？「発注待ち」になります。')">
                        @csrf
                        <textarea name="special_reason" rows="2" required
                                  class="w-full max-w-xl rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                                  placeholder="特例承認の理由（必須）例：所長不在のため総務判断で発注">{{ old('special_reason') }}</textarea>
                        <div class="mt-2">
                            <button class="bg-amber-600 hover:bg-amber-700 text-white text-sm px-5 py-2 rounded-md">
                                特例承認する
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 差し戻し（理由必須）。却下と違い、申請者が修正して再申請できる --}}
            @if ($actions['return'])
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <form method="POST" action="{{ route('orders.return', $order) }}"
                          onsubmit="return confirm('この申請を申請者に差し戻しますか？申請者が内容を修正して再申請できます。')">
                        @csrf
                        <label class="block text-sm text-gray-600 mb-1">
                            <span class="font-medium text-orange-700">差し戻し</span>：申請者に戻して直してもらう場合は理由を入力してください
                            @if ($order->isPendingOrder())
                                <span class="block text-xs text-gray-400 mt-0.5">
                                    まだ発注書を出していないので、この申請は取り消して申請者に戻せます。
                                </span>
                            @endif
                        </label>
                        <textarea name="return_reason" rows="2" required
                                  class="w-full max-w-xl rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none"
                                  placeholder="差し戻しの理由（必須）例：数量が多すぎます。3,000枚に直して再申請してください。">{{ old('return_reason') }}</textarea>
                        <div class="mt-2">
                            <button class="bg-white border border-orange-300 text-orange-700 hover:bg-orange-50 text-sm px-5 py-2 rounded-md">
                                差し戻す
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 却下（理由必須）。却下するとそこで終了（再申請はできない） --}}
            @if ($actions['reject'])
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <form method="POST" action="{{ route('orders.reject', $order) }}"
                          onsubmit="return confirm('この申請を却下しますか？却下するとこの申請は終了し、再申請はできません。')">
                        @csrf
                        <label class="block text-sm text-gray-600 mb-1">
                            <span class="font-medium text-red-700">却下</span>：この申請を終了させる場合は理由を入力してください（再申請はできません）
                        </label>
                        <textarea name="reject_reason" rows="2" required
                                  class="w-full max-w-xl rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
                                  placeholder="却下理由（必須）">{{ old('reject_reason') }}</textarea>
                        <div class="mt-2">
                            <button class="bg-white border border-red-300 text-red-600 hover:bg-red-50 text-sm px-5 py-2 rounded-md">
                                却下する
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif

    {{-- 明細 --}}
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">品名</th>
                    <th class="px-4 py-3">業者</th>
                    <th class="px-4 py-3">単位</th>
                    <th class="px-4 py-3 text-right">参考単価</th>
                    <th class="px-4 py-3 text-right">数量</th>
                    <th class="px-4 py-3 text-right">小計</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->material_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->supplier_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->unit }}</td>
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($item->unit_price) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($item->quantity) }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ $item->unit_price !== null ? \App\Support\Money::yen($item->subtotal()) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right font-medium">合計（参考）</td>
                    <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Money::yen($order->totalPrice()) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- 直前に見ていた検索結果へ戻る（OrderController がセッションに条件を覚えている） --}}
    <a href="{{ $backUrl }}" class="text-sm text-gray-500 hover:underline">← 一覧に戻る</a>
@endsection
