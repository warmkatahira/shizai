@extends('layouts.app')

@section('title', '新規発注申請')

@section('content')
    <h1 class="text-xl font-bold mb-2">新規発注申請</h1>
    <p class="text-sm text-gray-500 mb-6">
        必要な資材の数量を入力して申請してください。数量が空欄または0の資材は申請されません。
    </p>

    @include('admin.partials.errors')

    @if ($materials->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-gray-500">
            発注できる資材が登録されていません。管理者に資材の登録を依頼してください。
        </div>
    @else
        <form method="POST" action="{{ route('orders.store') }}">
            @csrf

            <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr>
                            <th class="px-4 py-3">品名</th>
                            <th class="px-4 py-3">カテゴリ</th>
                            <th class="px-4 py-3">業者</th>
                            <th class="px-4 py-3 text-right">参考単価</th>
                            <th class="px-4 py-3">単位</th>
                            <th class="px-4 py-3 w-32">数量</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($materials as $material)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $material->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->category ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->supplier?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    {{ $material->unit_price !== null ? '¥' . number_format($material->unit_price) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $material->unit }}</td>
                                <td class="px-4 py-3">
                                    <input type="number" min="0" max="9999"
                                           name="quantities[{{ $material->id }}]"
                                           value="{{ old('quantities.' . $material->id) }}"
                                           class="w-24 rounded-md border border-gray-300 px-2 py-1 text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow rounded-lg p-6 mb-6 max-w-2xl">
                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">備考（任意）</label>
                <textarea id="note" name="note" rows="3"
                          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
                          placeholder="納期の希望や連絡事項があれば記入してください">{{ old('note') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-6 py-2 rounded-md">申請する</button>
                <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:underline">キャンセル</a>
            </div>
        </form>
    @endif
@endsection
