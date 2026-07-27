@php
    use App\Models\Order;

    // ステータスごとの配色。意味のある色は @theme で暖色寄りに置き換え済み。
    // large（詳細画面）は枠線＋色付きドット付きで大きく表示し、見やすくする。
    $styles = [
        Order::STATUS_PENDING_MANAGER => ['badge' => 'bg-amber-100 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
        Order::STATUS_PENDING_AFFAIRS => ['badge' => 'bg-blue-100 text-blue-700 border-blue-200', 'dot' => 'bg-blue-600'],
        Order::STATUS_PENDING_ORDER => ['badge' => 'bg-accent-light text-accent-strong border-accent-dark', 'dot' => 'bg-accent-strong'],
        Order::STATUS_ORDERED => ['badge' => 'bg-green-100 text-green-800 border-green-200', 'dot' => 'bg-green-600'],
        Order::STATUS_RETURNED => ['badge' => 'bg-orange-100 text-orange-800 border-orange-200', 'dot' => 'bg-orange-500'],
        Order::STATUS_REJECTED => ['badge' => 'bg-red-100 text-red-700 border-red-200', 'dot' => 'bg-red-600'],
    ];

    $style = $styles[$order->status] ?? ['badge' => 'bg-gray-100 text-gray-600 border-gray-200', 'dot' => 'bg-gray-400'];
    $isLarge = $large ?? false;
@endphp

@if ($isLarge)
    {{-- 詳細画面：色付きドット＋枠線＋太字で目立たせる --}}
    <span class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-base font-semibold {{ $style['badge'] }}">
        <span class="w-2.5 h-2.5 rounded-full {{ $style['dot'] }}" aria-hidden="true"></span>
        {{ $order->statusLabel() }}
        @if ($order->is_special_approval && ! $order->isRejected())
            <span class="opacity-70">(特例)</span>
        @endif
    </span>
@else
    {{-- 一覧：小さいバッジ --}}
    <span class="inline-block rounded px-2 py-0.5 text-xs {{ $style['badge'] }}">
        {{ $order->statusLabel() }}
        @if ($order->is_special_approval && ! $order->isRejected())
            <span class="opacity-70">(特例)</span>
        @endif
    </span>
@endif
