@extends('layouts.app')

@section('title', '発注申請 #' . $order->id . ' の修正')

@section('content')
    @php
        // 所長本人の申請は所長承認を飛ばして総務へ回る
        $nextApprover = auth()->user()->isManager() ? '総務' : '所長';
    @endphp

    <h1 class="text-xl font-bold mb-2">発注申請 #{{ $order->id }} を修正して再申請</h1>
    <p class="text-sm text-gray-500 mb-6">
        内容を直して再申請すると、承認は最初からやり直しになります（{{ $nextApprover }}の確認から）。
    </p>

    {{-- なぜ戻されたのかを、直す画面でも見えるようにしておく --}}
    @if ($order->return_reason)
        <div class="mb-6 rounded-md bg-orange-50 border border-orange-200 px-4 py-3 text-sm text-orange-800">
            <span class="font-medium">差し戻しの理由：</span>{{ $order->return_reason }}
            @if ($order->returnedBy)
                <span class="text-orange-600">（{{ $order->returnedBy->name }} / {{ $order->returned_at?->format('Y/m/d H:i') }}）</span>
            @endif
        </div>
    @endif

    @include('orders._form', [
        'switchUrl' => route('orders.edit', $order),
        'formAction' => route('orders.update', $order),
        'formMethod' => 'PUT',
        'submitLabel' => '再申請する',
        'confirmMessage' => "この内容で再申請します。承認は最初（{$nextApprover}の確認）からやり直しになります。よろしいですか？",
        'cancelUrl' => route('orders.show', $order),
    ])
@endsection
