{{-- 単軸の集計結果（カテゴリ別・業者別・営業所別・資材別）。集計軸ごとに1行 --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">{{ rtrim($axisLabel, '別') }}</th>
                    <th class="px-4 py-3 text-right">発注件数</th>
                    <th class="px-4 py-3 text-right">明細数</th>
                    <th class="px-4 py-3 text-right">数量合計</th>
                    <th class="px-4 py-3 text-right">金額合計</th>
                    <th class="px-4 py-3 w-48">構成比（金額）</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    @php $share = $totals->amount > 0 ? $row->amount / $totals->amount * 100 : 0; @endphp
                    <tr class="hover:bg-accent-light/40 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $row->label }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format($row->order_count) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format($row->item_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row->quantity) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ \App\Support\Money::yen($row->amount) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-100 rounded overflow-hidden">
                                    <div class="h-2 bg-accent-dark" style="width: {{ number_format($share, 1) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-12 text-right">{{ number_format($share, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">該当する発注実績がありません。</td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-4 py-3 font-medium">合計</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->order_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->item_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals->quantity) }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ \App\Support\Money::yen($totals->amount) }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
