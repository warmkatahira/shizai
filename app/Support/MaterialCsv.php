<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 資材マスタのCSV出力・取り込み。
 *
 * 出力したCSVをExcelで直して、そのまま取り込み直せるようにしている：
 * - **1列目のIDで突合する**。IDが入っていればその資材を更新、空なら新規追加。
 *   （品名で突合すると、品名を直したいときに「別の資材の新規追加」になってしまう）
 * - カテゴリ・業者は**名前**で書く。マスタに無い名前はエラーにする
 *   （自動で作ると、誤字がそのままマスタに入ってしまうため）
 * - **1行でもエラーがあれば全件取り込まない**（どこまで入ったか分からない状態を作らない）
 */
class MaterialCsv
{
    /** CSVの列。この順で出力し、この順で読む */
    public const HEADERS = [
        'ID', '品名', 'カテゴリ', '発注業者',
        '縦(mm)', '横(mm)', '高さ(mm)',
        '単位', '単価', '最低ロット数量', '最低ロットの単位',
        '名入れ', '備考', '有効',
    ];

    /** 真偽値の書き方（出力はこの2つ。取り込みは下の parseBool が別表記も受ける） */
    private const YES = 'はい';
    private const NO = 'いいえ';

    /** 資材1件をCSVの1行にする */
    public static function row(Material $material): array
    {
        return [
            $material->id,
            $material->name,
            $material->category?->name ?? '',
            $material->supplier?->name ?? '',
            $material->length_mm,
            $material->width_mm,
            $material->height_mm,
            $material->unit,
            // 単価は decimal。「34.50」ではなく「34.5」で出す（Excelで見やすいように）
            $material->unit_price === null ? '' : (float) $material->unit_price,
            $material->min_lot_qty,
            $material->min_lot_unit,
            $material->has_imprint ? self::YES : self::NO,
            $material->note,
            $material->is_active ? self::YES : self::NO,
        ];
    }

    /**
     * CSVを取り込む（追加・更新）。
     *
     * @return array{created:int, updated:int} 追加・更新した件数
     *
     * @throws ValidationException 1行でもおかしければ、全行ぶんのエラーをまとめて投げる（何も取り込まない）
     */
    public static function import(string $path): array
    {
        $rows = self::readRows($path);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv' => 'CSVにデータ行がありません（1行目は見出し行として読み飛ばします）。',
            ]);
        }

        // 名前 → ID の対応表。1行ずつ問い合わせると行数ぶんSQLが飛ぶので先に引いておく
        $categories = Category::pluck('id', 'name');
        $suppliers = Supplier::pluck('id', 'name');
        $existingIds = Material::pluck('id')->flip();

        $errors = [];
        $parsed = [];

        foreach ($rows as [$lineNo, $row]) {
            try {
                $parsed[] = [$lineNo, self::parseRow($row, $categories, $suppliers, $existingIds)];
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $errors[] = "{$lineNo}行目：{$message}";
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['csv' => $errors]);
        }

        return DB::transaction(function () use ($parsed) {
            $created = 0;
            $updated = 0;

            foreach ($parsed as [, ['id' => $id, 'data' => $data]]) {
                if ($id === null) {
                    Material::create($data);
                    $created++;

                    continue;
                }

                // 存在チェックは parseRow で済んでいる
                Material::findOrFail($id)->update($data);
                $updated++;
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /**
     * CSVを読み、見出し行と空行を除いた [行番号, 列の配列] の一覧を返す。
     *
     * ExcelでそのままCSV保存するとShift-JIS（CP932）になることがあるので、
     * UTF-8でなければ変換する。BOMも取り除く。
     */
    private static function readRows(string $path): array
    {
        $content = file_get_contents($path);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'SJIS-win');
        }

        // ExcelのUTF-8 CSVにはBOMが付く
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // 引用符の中の改行を正しく扱うため、文字列を分割せずCSVとして読む
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        $lineNo = 0;

        while (($row = fgetcsv($stream)) !== false) {
            $lineNo++;

            if ($lineNo === 1) {
                continue; // 見出し行
            }

            // 空行（すべての列が空）は読み飛ばす
            if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rows[] = [$lineNo, $row];
        }

        fclose($stream);

        return $rows;
    }

    /**
     * CSVの1行を、資材の属性に変換して検証する。
     *
     * @return array{id:?int, data:array}
     *
     * @throws ValidationException
     */
    private static function parseRow(array $row, Collection $categories, Collection $suppliers, Collection $existingIds): array
    {
        // 列が足りない行でも落ちないように埋める
        $cols = array_pad(array_slice($row, 0, count(self::HEADERS)), count(self::HEADERS), '');
        $cols = array_map(fn ($v) => trim((string) $v), $cols);

        $id = $cols[0] === '' ? null : (int) $cols[0];

        if ($id !== null && ! $existingIds->has($id)) {
            throw ValidationException::withMessages([
                'csv' => "ID「{$cols[0]}」の資材が見つかりません。新規追加したい場合はID列を空にしてください。",
            ]);
        }

        $data = [
            'name' => $cols[1],
            'category_id' => self::lookup($categories, $cols[2], 'カテゴリ'),
            'supplier_id' => self::lookup($suppliers, $cols[3], '発注業者'),
            'length_mm' => self::nullableNumber($cols[4]),
            'width_mm' => self::nullableNumber($cols[5]),
            'height_mm' => self::nullableNumber($cols[6]),
            'unit' => $cols[7],
            'unit_price' => self::nullableNumber($cols[8]),
            'min_lot_qty' => self::nullableNumber($cols[9]),
            'min_lot_unit' => $cols[10] === '' ? null : $cols[10],
            'has_imprint' => self::parseBool($cols[11], default: false),
            'note' => $cols[12] === '' ? null : $cols[12],
            // 有効列が空欄なら「有効」として扱う（新規追加の行をいちいち書かなくて済むように）
            'is_active' => self::parseBool($cols[13], default: true),
        ];

        Validator::make($data, Material::validationRules(), [], Material::attributeNames())->validate();

        return ['id' => $id, 'data' => $data];
    }

    /** カテゴリ名・業者名 → ID。空欄は未設定（null）。マスタに無い名前はエラー */
    private static function lookup(Collection $map, string $name, string $label): ?int
    {
        if ($name === '') {
            return null;
        }

        $id = $map[$name] ?? null;

        if ($id === null) {
            throw ValidationException::withMessages([
                'csv' => "{$label}「{$name}」はマスタに登録されていません。先にマスタへ登録するか、名前を修正してください。",
            ]);
        }

        return (int) $id;
    }

    /** 空欄は null。それ以外はそのまま（数値かどうかはバリデーションで見る） */
    private static function nullableNumber(string $value): int|float|string|null
    {
        if ($value === '') {
            return null;
        }

        // Excelで「1,000」のように桁区切りが入ることがある
        $value = str_replace(',', '', $value);

        return is_numeric($value) ? $value + 0 : $value;
    }

    /** 「はい/いいえ」だけでなく、手書きされがちな表記も受け取る。空欄は列ごとの既定値 */
    private static function parseBool(string $value, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }

        return in_array(mb_strtolower($value), [
            'はい', 'あり', '有効', '有', '○', '1', 'true', 'yes',
        ], true);
    }
}
