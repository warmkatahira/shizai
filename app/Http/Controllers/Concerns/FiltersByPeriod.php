<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * 検索フォームの期間（date_from / date_to）の初期値を当月にする。
 *
 * 発注申請一覧も発注集計も、全期間を既定にするとデータが増えたときに重くなるので
 * 初回表示は当月に絞る。判定と既定値の入れ方が2箇所に散らないよう、ここに1つだけ置く。
 */
trait FiltersByPeriod
{
    /**
     * メニューから開いた初回表示のときだけ、期間に当月を入れる。
     *
     * フォームを送信すると date_from / date_to は空でも必ず送られてくるので、
     * `has()` で「初回表示かどうか」を判定できる。
     * つまり日付欄を空にして検索すれば、全期間も見られる。
     */
    protected function applyDefaultPeriod(Request $request): void
    {
        if ($request->has('date_from') || $request->has('date_to')) {
            return;
        }

        $request->merge([
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]);
    }
}
