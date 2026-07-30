{{-- クロス集計：縦＝営業所 / 横＝業者。セルは金額（その下に小さく発注件数） --}}
{{-- 業者が増えると横に伸びるので枠内スクロール。1列目（営業所）は横スクロールしても残す --}}
<div class="bg-white shadow rounded-lg overflow-auto max-h-[70vh]">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-500 text-left sticky top-0 z-20 shadow-[0_1px_0_0_var(--color-gray-200)]">
            <tr>
                <th class="px-4 py-3 sticky left-0 bg-gray-50 z-30">営業所</th>
                @foreach ($matrix['suppliers'] as $supplier)
                    <th class="px-4 py-3 text-right">{{ $supplier }}</th>
                @endforeach
                <th class="px-4 py-3 text-right">合計</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($matrix['offices'] as $office)
                <tr class="hover:bg-accent-light/40 transition-colors">
                    <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">{{ $office }}</td>
                    @foreach ($matrix['suppliers'] as $supplier)
                        @php
                            $amount = $matrix['amounts'][$office][$supplier] ?? null;
                            $count = $matrix['counts'][$office][$supplier] ?? 0;
                        @endphp
                        <td class="px-4 py-3 text-right {{ $amount === null ? 'text-gray-300' : '' }}">
                            @if ($amount === null)
                                —
                            @else
                                {{ \App\Support\Money::yen($amount) }}
                                <span class="block text-xs text-gray-400">{{ number_format($count) }}件</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-medium">{{ \App\Support\Money::yen($matrix['rowTotals'][$office]) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($matrix['suppliers']) + 2 }}" class="px-4 py-8 text-center text-gray-400">
                        該当する発注実績がありません。
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($matrix['offices'] !== [])
            <tfoot class="bg-gray-50">
                <tr>
                    <td class="px-4 py-3 font-medium sticky left-0 bg-gray-50 z-10">合計</td>
                    @foreach ($matrix['suppliers'] as $supplier)
                        <td class="px-4 py-3 text-right">{{ \App\Support\Money::yen($matrix['colTotals'][$supplier]) }}</td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Money::yen($matrix['total']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<p class="text-xs text-gray-400 mt-3">
    ※ セルは金額（発注済）と、その組み合わせの発注件数です。行は営業所の並び順、列は金額の大きい順。
    数量は資材が違うと足しても意味がないため出していません。
</p>
