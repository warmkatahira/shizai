@php
    $classes = [
        \App\Models\Order::STATUS_PENDING_MANAGER => 'bg-amber-100 text-amber-700',
        \App\Models\Order::STATUS_PENDING_AFFAIRS => 'bg-blue-100 text-blue-700',
        \App\Models\Order::STATUS_PENDING_ORDER => 'bg-accent-light text-accent-strong',
        \App\Models\Order::STATUS_ORDERED => 'bg-green-100 text-green-700',
        \App\Models\Order::STATUS_RETURNED => 'bg-orange-100 text-orange-700',
        \App\Models\Order::STATUS_REJECTED => 'bg-red-100 text-red-700',
    ][$order->status] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-block px-2 py-0.5 rounded text-xs {{ $classes }}">
    {{ $order->statusLabel() }}
    @if ($order->is_special_approval && ! $order->isRejected())
        <span class="opacity-70">(特例)</span>
    @endif
</span>
