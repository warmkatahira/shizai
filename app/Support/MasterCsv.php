<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * マスタのCSV出力・取り込みの共通処理。
 *
 * 出したCSVをExcelで直して、そのまま取り込み直せるようにしている：
 * - **1列目のIDで突合する**。IDが入っていればその1件を更新、空なら新規追加。
 *   （名前で突合すると、名前を直したいときに「別のものの新規追加」になってしまう）
 * - **1行でもエラーがあれば全件取り込まない**（どこまで入ったか分からない状態を作らない）
 * - エラーは「3行目：〜」と行番号つきで全部出す
 * - CSVに書かなかった行は消さない（外すときは「有効」を いいえ にする）
 *
 * マスタごとの差分は、継承先で headers() / row() / attributes() などを実装する。
 * 入力チェックはモデルの validationRules() / attributeNames() を使うので、
 * 編集フォームとCSV取り込みで必ず同じ規則になる。
 */
abstract class MasterCsv
{
    /** 真偽値の書き方（出力はこの2つ。取り込みは parseBool が別表記も受ける） */
    protected const YES = 'はい';

    protected const NO = 'いいえ';

    /** CSVの列。この順で出力し、この順で読む。1列目は必ずID */
    abstract public static function headers(): array;

    /** 1件をCSVの1行にする */
    abstract public static function row(Model $model): array;

    /** 取り込み対象のモデル（FQCN） */
    abstract protected static function model(): string;

    /** エラー文言に使う呼び名（「業者」「営業所」など） */
    abstract protected static function label(): string;

    /**
     * CSVの1行（列の配列）を、保存する属性に変換する。
     * $context は context() が返した共有データ。
     */
    abstract protected static function attributes(array $cols, array $context): array;

    /**
     * 行ごとの処理で使い回す共有データ（名前→IDの対応表など）。
     * 1行ずつ問い合わせると行数ぶんSQLが飛ぶので、先に引いておく。
     */
    protected static function context(): array
    {
        return [];
    }

    /**
     * CSVの中での重複を弾く列。['列名' => '表示名']。
     * DBに unique が付いている列を書いておく（取り込み時に例外で落ちるのを防ぐ）。
     */
    protected static function uniqueColumns(): array
    {
        return [];
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
        $rows = static::readRows($path);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv' => 'CSVにデータ行がありません（1行目は見出し行として読み飛ばします）。',
            ]);
        }

        $model = static::model();
        $context = static::context();
        $existingIds = $model::pluck('id')->flip();

        $errors = [];
        $parsed = [];

        foreach ($rows as [$lineNo, $row]) {
            try {
                $parsed[] = [$lineNo, static::parseRow($row, $context, $existingIds)];
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $errors[] = "{$lineNo}行目：{$message}";
                    }
                }
            }
        }

        $errors = array_merge($errors, static::duplicateErrors($parsed));

        if ($errors !== []) {
            throw ValidationException::withMessages(['csv' => $errors]);
        }

        return DB::transaction(function () use ($parsed, $model) {
            $created = 0;
            $updated = 0;

            foreach ($parsed as [, ['id' => $id, 'data' => $data]]) {
                if ($id === null) {
                    $model::create($data);
                    $created++;

                    continue;
                }

                // 存在チェックは parseRow で済んでいる
                $model::findOrFail($id)->update($data);
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
    protected static function readRows(string $path): array
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
                static::assertHeaderRow($row); // 見出し行。列がずれたCSVはここで止める

                continue;
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
     * 見出し行が想定どおりか確かめる。
     *
     * 取り込みは**列の位置**で読むので、列を足す前の古いCSVや、列を消したCSVを
     * そのまま読むと隣の列の値を取り込んでしまう。中身を読む前にここで止める。
     *
     * @throws ValidationException
     */
    protected static function assertHeaderRow(array $row): void
    {
        $expected = static::headers();
        $actual = array_map(fn ($v) => trim((string) $v), $row);

        // Excelが末尾に空の列を付けることがあるので、後ろの空欄は無視する
        while ($actual !== [] && end($actual) === '') {
            array_pop($actual);
        }

        if ($actual === $expected) {
            return;
        }

        throw ValidationException::withMessages([
            'csv' => '1行目（見出し行）がCSV出力の形と違います。列を足したり消したり並べ替えたりせず、'
                . 'CSVダウンロードしたファイルをそのまま編集して取り込んでください'
                . '（想定：' . implode(' / ', $expected) . '）。',
        ]);
    }

    /**
     * CSVの1行を、保存する属性に変換して検証する。
     *
     * @return array{id:?int, data:array}
     *
     * @throws ValidationException
     */
    protected static function parseRow(array $row, array $context, Collection $existingIds): array
    {
        $headers = static::headers();

        // 列が足りない行でも落ちないように埋める
        $cols = array_pad(array_slice($row, 0, count($headers)), count($headers), '');
        $cols = array_map(fn ($v) => trim((string) $v), $cols);

        $id = $cols[0] === '' ? null : (int) $cols[0];

        if ($id !== null && ! $existingIds->has($id)) {
            $label = static::label();

            throw ValidationException::withMessages([
                'csv' => "ID「{$cols[0]}」の{$label}が見つかりません。新規追加したい場合はID列を空にしてください。",
            ]);
        }

        $data = static::attributes($cols, $context);

        $model = static::model();
        // 更新の行は自分自身を unique の対象から外す（名前を変えずに他の列だけ直せるように）
        Validator::make($data, $model::validationRules($id), [], $model::attributeNames())->validate();

        return ['id' => $id, 'data' => $data];
    }

    /**
     * CSVの中で同じ値が2行以上に出ていないか調べる。
     * DBの unique に任せると取り込みの途中で例外になり、行番号も分からないため先に見る。
     */
    protected static function duplicateErrors(array $parsed): array
    {
        $errors = [];

        foreach (static::uniqueColumns() as $column => $label) {
            $seen = [];

            foreach ($parsed as [$lineNo, ['data' => $data]]) {
                $value = $data[$column] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                if (isset($seen[$value])) {
                    $errors[] = "{$lineNo}行目：{$label}「{$value}」が {$seen[$value]}行目 と重複しています。";

                    continue;
                }

                $seen[$value] = $lineNo;
            }
        }

        return $errors;
    }

    /** 名前 → ID。空欄は未設定（null）。マスタに無い名前はエラー */
    protected static function lookup(Collection $map, string $name, string $label): ?int
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
    protected static function nullableNumber(string $value): int|float|string|null
    {
        if ($value === '') {
            return null;
        }

        // Excelで「1,000」のように桁区切りが入ることがある
        $value = str_replace(',', '', $value);

        return is_numeric($value) ? $value + 0 : $value;
    }

    /** 空欄は null、それ以外はそのまま。文字列の任意項目に使う */
    protected static function nullableText(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    /** 表示順。空欄は 0（並び順を書かなくても取り込めるように） */
    protected static function sortOrder(string $value): int|string
    {
        $value = static::nullableNumber($value);

        return $value === null ? 0 : $value;
    }

    /** 「はい/いいえ」だけでなく、手書きされがちな表記も受け取る。空欄は列ごとの既定値 */
    protected static function parseBool(string $value, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }

        return in_array(mb_strtolower($value), [
            'はい', 'あり', '有効', '有', '○', '1', 'true', 'yes',
        ], true);
    }

    /** 真偽値をCSVの「はい / いいえ」にする */
    protected static function boolText(bool $value): string
    {
        return $value ? self::YES : self::NO;
    }
}
