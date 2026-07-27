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

    <h1 class="text-xl font-bold mb-2">こんにちは、{{ $user->name }} さん</h1>
    @if ($user->office)
        <p class="text-gray-600 mb-6">所属: {{ $user->office->name }}</p>
    @endif

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
