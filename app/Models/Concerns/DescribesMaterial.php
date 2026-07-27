<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * 資材の「寸法」と「最低ロット」の表示整形。
 *
 * 資材マスタ（Material）と、そのスナップショットである発注明細（OrderItem）は
 * 同じ形の列を持ち、同じ見せ方をするので、整形はここに1つだけ置く。
 *
 * 単位は Material では単位マスタへの参照（Unitモデル）、OrderItem では
 * スナップショットした文字列なので、unitName() で両方を吸収する。
 */
trait DescribesMaterial
{
    /** 単位名。Material は Unit モデル、OrderItem は文字列を持つのを吸収する */
    public function unitName(): string
    {
        $unit = $this->unit;

        return $unit instanceof Model ? (string) ($unit->name ?? '') : (string) ($unit ?? '');
    }

    /** 縦×横×高（mm）。入力がある値だけを × でつなぐ。1つも無ければ null */
    public function sizeText(): ?string
    {
        $parts = array_filter(
            [$this->length_mm, $this->width_mm, $this->height_mm],
            fn (?int $mm) => $mm !== null,
        );

        return $parts === [] ? null : implode('×', $parts);
    }

    /** 最低ロット（例：2,700枚）。数量が無ければ null */
    public function minLotText(): ?string
    {
        if ($this->min_lot_qty === null) {
            return null;
        }

        return number_format($this->min_lot_qty) . $this->unitName();
    }
}
