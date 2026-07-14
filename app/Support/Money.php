<?php

namespace App\Support;

/**
 * 金額の表示整形。
 */
class Money
{
    /**
     * 円表示にする。単価は 34.5 / 6.07 のような小数があるため、
     * 小数がある場合だけ小数を出す（2532 → ¥2,532 ／ 34.5 → ¥34.5）。
     */
    public static function yen(int|float|string|null $value, string $emptyText = '—'): string
    {
        if ($value === null || $value === '') {
            return $emptyText;
        }

        $formatted = number_format((float) $value, 2);
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return '¥' . $formatted;
    }
}
