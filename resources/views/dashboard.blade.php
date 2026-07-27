@extends('layouts.app')

@section('title', 'ホーム | 資材発注システム')

@section('content')
    @php
        $user = auth()->user();

        // 権限に応じた入口。ヘッダーのナビと同じ判定を使う。
        // 各カードは [ルート, タイトル, 説明, アイコンキー]（アイコンは dashboard/_icon.blade.php）
        $cards = [];

        if ($user->isSales()) {
            $cards[] = ['orders.create', '資材を発注する', '業者を選び、必要な資材の数量を入力して申請します。', 'order-create'];
        }

        $cards[] = $user->isGeneralAffairs()
            ? ['orders.index', '発注申請を確認する', '営業所からの申請を承認・却下します。', 'orders']
            : ['orders.index', '発注申請を見る', '発注申請の状態を確認します。', 'orders'];

        $cards[] = ['reports.index', '発注を集計する', 'カテゴリ別・業者別・営業所別に発注実績を集計します。', 'reports'];

        if ($user->canManageMasters()) {
            $cards[] = ['admin.materials.index', '資材マスタ', '発注できる資材を登録・編集します。', 'materials'];
            $cards[] = ['admin.categories.index', 'カテゴリマスタ', '資材のカテゴリを登録・編集します。', 'categories'];
            $cards[] = ['admin.units.index', '単位マスタ', '資材の単位（枚・ケースなど）を登録・編集します。', 'units'];
            $cards[] = ['admin.suppliers.index', '業者マスタ', '仕入先の業者と発注方法を登録・編集します。', 'suppliers'];
            $cards[] = ['admin.offices.index', '営業所マスタ', '営業所（拠点）を登録・編集します。', 'offices'];
        } else {
            $cards[] = ['materials.index', '資材を調べる', 'どの業者にどの資材がいくらであるかを確認します。', 'materials-search'];
        }

        if ($user->isAdmin()) {
            $cards[] = ['admin.users.index', 'ユーザー管理', '利用者と権限を登録・編集します。', 'users'];
            $cards[] = ['admin.logs.index', '操作ログ', '誰がいつ何をしたかの記録を確認します。', 'logs'];
        }
    @endphp

    @if ($user->office)
        <p class="text-gray-600 mb-6">所属: {{ $user->office->name }}</p>
    @endif

    @php
        // 対応すべきこと（todo）のトーン別カラー
        $toneClasses = [
            'amber' => 'text-amber-700',
            'blue' => 'text-blue-700',
            'accent' => 'text-accent-strong',
            'orange' => 'text-orange-700',
        ];
        // グラフのスケール（最大金額。0除算を避ける）
        $trendMax = max(1, ...array_map(fn ($t) => $t['amount'], $trend));
    @endphp

    {{-- 対応が必要なもの（役割別）。件数が0のものも出すが、0のときは控えめに見せる --}}
    @if (! empty($todos))
        <h2 class="text-sm font-bold text-gray-500 mb-3">対応が必要なもの</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            @foreach ($todos as $todo)
                <a href="{{ route('orders.index', $todo['query']) }}"
                   class="flex items-center justify-between bg-white rounded-2xl shadow-sm px-5 py-4 transition hover:shadow-md hover:ring-2 hover:ring-accent">
                    <span class="text-sm text-gray-600">{{ $todo['label'] }}</span>
                    <span class="text-2xl font-bold {{ $todo['count'] > 0 ? $toneClasses[$todo['tone']] : 'text-gray-300' }}">
                        {{ number_format($todo['count']) }}<span class="text-sm font-normal ml-0.5">件</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- 当月KPI ＋ 月別の発注金額推移 --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-8">
        {{-- KPIタイル --}}
        <div class="grid grid-cols-3 gap-4 lg:col-span-1 lg:grid-cols-1">
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注件数</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ number_format($kpis['count']) }}<span class="text-sm font-normal ml-0.5">件</span></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の発注金額</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ \App\Support\Money::yen($kpis['amount']) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm px-5 py-4">
                <p class="text-xs text-gray-500">今月の申請件数</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ number_format($kpis['applied']) }}<span class="text-sm font-normal ml-0.5">件</span></p>
            </div>
        </div>

        {{-- 月別の発注金額推移（発注日ベース・発注済のみ）。JSは使わずCSSの棒グラフで描く --}}
        <div class="bg-white rounded-2xl shadow-sm px-5 py-4 lg:col-span-2">
            <p class="text-xs text-gray-500 mb-4">発注金額の推移（直近6ヶ月・発注済）</p>
            <div class="flex items-end gap-3 h-40">
                @foreach ($trend as $t)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-[10px] text-gray-400 mb-1">{{ $t['amount'] > 0 ? \App\Support\Money::yen($t['amount']) : '' }}</span>
                        <div class="w-full rounded-t bg-accent" style="height: {{ max(2, round($t['amount'] / $trendMax * 100)) }}%"></div>
                        <span class="text-xs text-gray-500 mt-2">{{ $t['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <h2 class="text-sm font-bold text-gray-500 mb-3">メニュー</h2>

    {{--
        丸っこいカード＋中央のアイコン。ホバーで説明が浮かび上がる。
        説明は常に場所を確保し（不透明度だけ切り替え）、ホバーでレイアウトがずれないようにする。
        auto-rows-fr で全行を同じ高さに揃え、各カードを h-full で伸ばすので、
        文字数に関係なく全カードが同じ大きさになる。
    --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
        @foreach ($cards as [$route, $title, $description, $icon])
            <a href="{{ route($route) }}"
               class="group flex flex-col items-center justify-center text-center h-full min-h-44 bg-white rounded-3xl shadow-sm p-6
                      transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-2 hover:ring-accent">
                {{-- 中央のアイコン。ホバーで少し拡大し、地の色が濃くなる --}}
                <span class="flex items-center justify-center w-16 h-16 rounded-full bg-accent-light text-accent-strong
                             transition-all duration-300 group-hover:bg-accent group-hover:text-ink group-hover:scale-110">
                    @include('dashboard._icon', ['icon' => $icon])
                </span>

                <h2 class="font-semibold mt-4">{{ $title }}</h2>

                {{-- 説明：ふだんは透明、ホバーで下からふわっと表示 --}}
                <p class="text-sm text-gray-500 mt-2 opacity-0 translate-y-1 transition-all duration-300
                          group-hover:opacity-100 group-hover:translate-y-0">
                    {{ $description }}
                </p>
            </a>
        @endforeach
    </div>
@endsection
