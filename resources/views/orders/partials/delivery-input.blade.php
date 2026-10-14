{{--
    納入希望日の入力（「最短」のチェックボックス＋日付）。発注申請・再申請と所長承認で共有。

    受け取るもの：
      $asap … 最短にチェックを入れておくか
      $date … 日付の初期値（Y-m-d。空なら未入力）

    「最短」にチェックがあるあいだは日付を disabled にして送らない（Order::deliveryRules も日付を見ない）。
    未チェックのときは 0 を送るため、チェックボックスの前に hidden を置いている。
--}}
@php
    // 当日納品は業者の締めに間に合わないので、明日以降しか選べないようにする
    $earliestDelivery = now()->addDay()->format('Y-m-d');
    // 差し戻しの再申請では、元の希望日がもう過去になっていることがある。その場合は空にする
    $date = $date && $date >= $earliestDelivery ? $date : '';
@endphp
<div data-delivery-input>
    <div class="flex flex-wrap items-center gap-3">
        <label class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 cursor-pointer select-none has-[:checked]:bg-accent-light has-[:checked]:border-accent-dark">
            <input type="hidden" name="is_delivery_asap" value="0">
            <input autocomplete="off" type="checkbox" name="is_delivery_asap" value="1" data-delivery-asap
                   class="accent-accent-strong" @checked($asap)>
            <span class="text-sm">最短</span>
        </label>
        <input autocomplete="off" id="desired_delivery_date" name="desired_delivery_date" type="date"
               min="{{ $earliestDelivery }}" value="{{ $date }}" data-delivery-date
               @disabled($asap) @required(! $asap)
               class="flex-1 min-w-40 rounded-md border border-gray-300 px-3 py-2 focus:border-accent-dark focus:ring-1 focus:ring-accent-dark outline-none disabled:bg-gray-100 disabled:text-gray-400">
    </div>
    <p class="text-xs text-gray-400 mt-1">
        日付を指定する場合は「最短」のチェックを外し、明日（{{ now()->addDay()->format('n/j') }}）以降を選んでください。
    </p>
</div>

@once
    <script>
        // 「最短」にチェックがあるあいだは日付を選べなくする（送信もしない）
        document.querySelectorAll('[data-delivery-input]').forEach((box) => {
            const asap = box.querySelector('[data-delivery-asap]');
            const date = box.querySelector('[data-delivery-date]');
            const sync = () => {
                date.disabled = asap.checked;
                date.required = ! asap.checked;
                if (! asap.checked) date.focus();
            };
            asap.addEventListener('change', sync);
        });
    </script>
@endonce
