@extends('layouts.app')

@section('title', '新規発注申請')

@section('content')
    <h1 class="text-xl font-bold mb-2">新規発注申請</h1>
    <p class="text-sm text-gray-500 mb-6">
        発注業者を選び、必要な資材の数量を入力して申請してください。1回の申請につき業者は1社です。
    </p>

    @php
        // 所長本人の申請は所長承認を飛ばして総務へ回る
        $nextApprover = auth()->user()->isManager() ? '総務' : '所長';
    @endphp

    @include('orders._form', [
        'switchUrl' => route('orders.create'),
        'formAction' => route('orders.store'),
        'formMethod' => 'POST',
        'submitLabel' => '申請する',
        'confirmMessage' => "この内容で発注申請を送信します。送信すると{$nextApprover}の承認待ちになります。よろしいですか？",
        'cancelUrl' => route('orders.index'),
        'order' => null,
    ])
@endsection
