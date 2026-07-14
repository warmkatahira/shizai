@extends('layouts.app')

@section('title', 'ホーム | 資材発注システム')

@section('content')
    @php
        $user = auth()->user();

        // 権限に応じた入口。ヘッダーのナビと同じ判定を使う
        $cards = [];

        if ($user->isSales()) {
            $cards[] = ['orders.create', '資材を発注する', '業者を選び、必要な資材の数量を入力して申請します。'];
        }

        $cards[] = $user->isGeneralAffairs()
            ? ['orders.index', '発注申請を確認する', '営業所からの申請を承認・却下します。']
            : ['orders.index', '発注申請を見る', '発注申請の状態を確認します。'];

        $cards[] = ['reports.index', '発注を集計する', 'カテゴリ別・業者別・営業所別に発注実績を集計します。'];

        if ($user->canManageMasters()) {
            $cards[] = ['admin.materials.index', '資材マスタ', '発注できる資材を登録・編集します。'];
            $cards[] = ['admin.suppliers.index', '業者マスタ', '仕入先の業者と発注方法を登録・編集します。'];
            $cards[] = ['admin.categories.index', 'カテゴリマスタ', '資材のカテゴリを登録・編集します。'];
            $cards[] = ['admin.offices.index', '営業所マスタ', '営業所（拠点）を登録・編集します。'];
        } else {
            $cards[] = ['materials.index', '資材を調べる', 'どの業者にどの資材がいくらであるかを確認します。'];
        }

        if ($user->isAdmin()) {
            $cards[] = ['admin.users.index', 'ユーザー管理', '利用者と権限を登録・編集します。'];
        }
    @endphp

    <h1 class="text-xl font-bold mb-2">こんにちは、{{ $user->name }} さん</h1>
    @if ($user->office)
        <p class="text-gray-600 mb-6">所属: {{ $user->office->name }}</p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as [$route, $title, $description])
            <a href="{{ route($route) }}" class="block bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-accent">
                <h2 class="font-semibold mb-1">{{ $title }}</h2>
                <p class="text-sm text-gray-500">{{ $description }}</p>
            </a>
        @endforeach
    </div>
@endsection
